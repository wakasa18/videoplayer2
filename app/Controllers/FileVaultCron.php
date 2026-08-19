<?php

namespace App\Controllers;

use App\Libraries\SupabaseStorage;
use App\Models\FileAuditModel;
use App\Models\ImportantFileModel;
use App\Models\ImportantFileShareModel;
use App\Models\ImportantFileShareEventModel;
use App\Models\ImportantFileShareDownloadSessionModel;
use Config\Supabase as SupabaseConfig;
use Throwable;

class FileVaultCron extends BaseController
{
    public function maintenance()
    {
        if (! $this->cronSecretIsValid()) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $model   = new ImportantFileModel();
        $storage = new SupabaseStorage(config(SupabaseConfig::class)->filesBucket);
        $audit   = new FileAuditModel();
        $result  = [
            'pending_removed' => 0,
            'recycle_purged'  => 0,
            'download_sessions_expired' => 0,
            'share_expiry_notifications' => 0,
        ];

        foreach ($model->stalePendingFiles(3600) as $file) {
            try {
                $storage->delete($file['file_path']);
            } catch (Throwable) {
            }
            try {
                $audit->logAction('stale_upload_cleaned', (int) $file['id']);
            } catch (Throwable) {
            }
            $model->delete((int) $file['id'], true);
            $result['pending_removed']++;
        }

        foreach ((new ImportantFileModel())->expiredRecycleFiles() as $file) {
            try {
                $deleted = $storage->delete($file['file_path']);
            } catch (Throwable) {
                $deleted = false;
            }

            if (! $deleted) {
                continue;
            }

            try {
                $audit->logAction('recycle_auto_purged', (int) $file['id'], ['title' => $file['title']]);
            } catch (Throwable) {
            }
            (new ImportantFileModel())->delete((int) $file['id'], true);
            $result['recycle_purged']++;
        }

        $result['download_sessions_expired'] = (new ImportantFileShareDownloadSessionModel())->expireOld();

        $shareModel = new ImportantFileShareModel();
        $eventModel = new ImportantFileShareEventModel();
        foreach ($shareModel->expiringForNotification(24) as $share) {
            if (! $shareModel->markNotificationSent((int) $share['id'], 'expiring_notified_at')) {
                continue;
            }
            try {
                $eventModel->recordEvent((int) $share['id'], 'notification_expiring', null, [
                    'message' => 'This share link expires within 24 hours.',
                    'expires_at' => $share['expires_at'],
                ], true, hash('sha256', 'cron'));
            } catch (Throwable) {
            }
            $this->sendShareEmail(
                'Share link expiring soon',
                'A shared ' . (($share['share_type'] ?? 'file') === 'folder' ? 'folder' : 'file') . ' link expires at ' . (string) $share['expires_at'] . '.'
            );
            $result['share_expiry_notifications']++;
        }

        return $this->response->setJSON($result);
    }

    private function sendShareEmail(string $subject, string $message): void
    {
        $to = trim((string) getenv('EMAIL_TO'));
        if ($to === '') {
            return;
        }
        try {
            $email = service('email');
            $from = trim((string) getenv('EMAIL_FROM'));
            if ($from !== '') {
                $email->setFrom($from, 'Damon\'s Archive');
            }
            $email->setTo($to)->setSubject($subject)->setMessage($message)->send(false);
        } catch (Throwable) {
        }
    }

    private function cronSecretIsValid(): bool
    {
        $expected = getenv('CRON_SECRET');
        if ($expected === false || $expected === '') {
            return true;
        }

        $given = $this->request->getHeaderLine('Authorization');
        if ($given === '' && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $given = $_SERVER['HTTP_AUTHORIZATION'];
        }
        $given = trim(str_replace('Bearer', '', $given));
        if ($given === '') {
            $given = (string) $this->request->getGet('secret');
        }

        return hash_equals($expected, $given);
    }
}
