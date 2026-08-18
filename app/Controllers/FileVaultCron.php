<?php

namespace App\Controllers;

use App\Libraries\SupabaseStorage;
use App\Models\FileAuditModel;
use App\Models\ImportantFileModel;
use Config\Services;
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
            'expiry_checked'  => 0,
            'expiry_sent'     => false,
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

        $due = $this->filesDueForReminder((new ImportantFileModel())->expirationReminderCandidates());
        $result['expiry_checked'] = count($due);

        if ($due !== [] && $this->sendExpirationDigest($due)) {
            foreach ($due as $file) {
                (new ImportantFileModel())->update((int) $file['id'], [
                    'expiration_reminded_at' => date('Y-m-d H:i:s'),
                ]);
            }
            $result['expiry_sent'] = true;
        }

        return $this->response->setJSON($result);
    }

    private function filesDueForReminder(array $files): array
    {
        $today = new \DateTimeImmutable('today');
        $due   = [];

        foreach ($files as $file) {
            try {
                $expires = new \DateTimeImmutable((string) $file['expires_at']);
            } catch (Throwable) {
                continue;
            }

            $days = (int) $today->diff($expires)->format('%r%a');
            if ($days > (int) ($file['reminder_days'] ?? 30)) {
                continue;
            }

            $last = $file['expiration_reminded_at'] ?? null;
            if ($last && strtotime((string) $last) > strtotime('-7 days')) {
                continue;
            }

            $file['days_until_expiry'] = $days;
            $due[] = $file;
        }

        return $due;
    }

    private function sendExpirationDigest(array $files): bool
    {
        $to   = getenv('EMAIL_TO');
        $from = getenv('EMAIL_FROM') ?: $to;
        if (! $to) {
            log_message('warning', 'File vault expiration reminders skipped because EMAIL_TO is not configured.');
            return false;
        }

        $rows = '';
        foreach ($files as $file) {
            $days  = (int) $file['days_until_expiry'];
            $state = $days < 0 ? abs($days) . ' day(s) expired' : ($days === 0 ? 'Expires today' : 'Expires in ' . $days . ' day(s)');
            $rows .= '<tr>'
                . '<td style="padding:10px;border-bottom:1px solid #e5e7eb">' . esc($file['title']) . '</td>'
                . '<td style="padding:10px;border-bottom:1px solid #e5e7eb">' . esc((string) $file['expires_at']) . '</td>'
                . '<td style="padding:10px;border-bottom:1px solid #e5e7eb">' . esc($state) . '</td>'
                . '</tr>';
        }

        $email = Services::email();
        $email->initialize([
            'protocol'   => 'smtp',
            'SMTPHost'   => getenv('EMAIL_SMTP_HOST') ?: 'smtp.gmail.com',
            'SMTPUser'   => getenv('EMAIL_SMTP_USER'),
            'SMTPPass'   => getenv('EMAIL_SMTP_PASS'),
            'SMTPPort'   => (int) (getenv('EMAIL_SMTP_PORT') ?: 587),
            'SMTPCrypto' => 'tls',
            'mailType'   => 'html',
            'charset'    => 'utf-8',
            'newline'    => "\r\n",
        ]);
        $email->setFrom($from, "Damon's Archive");
        $email->setTo($to);
        $email->setSubject(count($files) . ' important file expiration reminder' . (count($files) === 1 ? '' : 's'));
        $email->setMessage('<div style="font-family:Arial,sans-serif;max-width:680px;margin:auto"><h2>Important Files expiration reminder</h2><p>The following documents are already expired or within their reminder period.</p><table style="width:100%;border-collapse:collapse"><thead><tr><th style="text-align:left;padding:10px">File</th><th style="text-align:left;padding:10px">Expiration</th><th style="text-align:left;padding:10px">Status</th></tr></thead><tbody>' . $rows . '</tbody></table><p><a href="' . esc(base_url('files'), 'attr') . '">Open Important Files</a></p></div>');

        $sent = $email->send();
        if (! $sent) {
            log_message('error', 'File vault expiration reminder email failed.');
        }

        return $sent;
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
