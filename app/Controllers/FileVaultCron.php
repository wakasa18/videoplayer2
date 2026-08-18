<?php

namespace App\Controllers;

use App\Libraries\SupabaseStorage;
use App\Models\FileAuditModel;
use App\Models\ImportantFileModel;
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

        return $this->response->setJSON($result);
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
