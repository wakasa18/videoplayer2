<?php

namespace App\Models;

use CodeIgniter\Model;

class ImportantFileShareModel extends Model
{
    protected $table            = 'important_file_shares';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'share_type',
        'file_id',
        'folder_path',
        'token_hash',
        'token_ciphertext',
        'share_title',
        'share_message',
        'display_name',
        'notify_first_open',
        'notify_download_limit',
        'notify_expiring',
        'first_open_notified_at',
        'limit_notified_at',
        'expiring_notified_at',
        'expires_at',
        'max_downloads',
        'view_count',
        'download_count',
        'last_accessed_at',
        'revoked_at',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'share_type'    => 'required|in_list[file,folder]',
        'file_id'       => 'permit_empty|integer|greater_than[0]',
        'folder_path'   => 'permit_empty|max_length[1000]',
        'token_hash'       => 'required|exact_length[64]|alpha_numeric',
        'token_ciphertext' => 'permit_empty|max_length[500]',
        'share_title'      => 'permit_empty|max_length[255]',
        'display_name'     => 'permit_empty|max_length[100]',
        'max_downloads' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[10000]',
    ];

    public function recentForFile(int $fileId): array
    {
        return $this->where('file_id', $fileId)
            ->orderBy('created_at', 'DESC')
            ->findAll(25);
    }

    public function recentForFolder(string $folderPath): array
    {
        return $this->where('share_type', 'folder')
            ->where('folder_path', $folderPath)
            ->orderBy('created_at', 'DESC')
            ->findAll(25);
    }

    public function findUsableByToken(string $rawToken): ?array
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $rawToken)) {
            return null;
        }

        $now = date('Y-m-d H:i:s');

        $share = $this->where('token_hash', hash('sha256', $rawToken))
            ->where('revoked_at IS NULL', null, false)
            ->groupStart()
                ->where('expires_at IS NULL', null, false)
                ->orWhere('expires_at >', $now)
            ->groupEnd()
            ->groupStart()
                ->where('max_downloads IS NULL', null, false)
                ->orWhere('download_count < max_downloads', null, false)
            ->groupEnd()
            ->first();

        return $share ?: null;
    }

    public function recordView(int $shareId): bool
    {
        return (bool) $this->builder()
            ->where('id', $shareId)
            ->set('view_count', 'COALESCE(view_count, 0) + 1', false)
            ->set('last_accessed_at', date('Y-m-d H:i:s'))
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->update();
    }

    public function claimDownload(int $shareId): bool
    {
        $now = date('Y-m-d H:i:s');

        $builder = $this->builder();
        $builder->where('id', $shareId)
            ->where('revoked_at IS NULL', null, false)
            ->groupStart()
                ->where('expires_at IS NULL', null, false)
                ->orWhere('expires_at >', $now)
            ->groupEnd()
            ->groupStart()
                ->where('max_downloads IS NULL', null, false)
                ->orWhere('download_count < max_downloads', null, false)
            ->groupEnd()
            ->set('download_count', 'COALESCE(download_count, 0) + 1', false)
            ->set('last_accessed_at', $now)
            ->set('updated_at', $now)
            ->update();

        return $this->db->affectedRows() === 1;
    }

    public function revoke(int $shareId): bool
    {
        return (bool) $this->builder()
            ->where('id', $shareId)
            ->set('revoked_at', date('Y-m-d H:i:s'))
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->update();
    }

    public function revokeForFile(int $fileId): bool
    {
        return (bool) $this->builder()
            ->where('file_id', $fileId)
            ->where('revoked_at IS NULL', null, false)
            ->set('revoked_at', date('Y-m-d H:i:s'))
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->update();
    }

    public function revokeForFolder(string $folderPath): bool
    {
        return (bool) $this->builder()
            ->where('share_type', 'folder')
            ->where('folder_path', $folderPath)
            ->where('revoked_at IS NULL', null, false)
            ->set('revoked_at', date('Y-m-d H:i:s'))
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->update();
    }


    public function markNotificationSent(int $shareId, string $column): bool
    {
        $allowed = ['first_open_notified_at', 'limit_notified_at', 'expiring_notified_at'];
        if (! in_array($column, $allowed, true)) {
            return false;
        }
        $this->builder()
            ->where('id', $shareId)
            ->where($column . ' IS NULL', null, false)
            ->set($column, date('Y-m-d H:i:s'))
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->update();

        return $this->db->affectedRows() === 1;
    }

    public function expiringForNotification(int $hours = 24): array
    {
        $now = date('Y-m-d H:i:s');
        $until = date('Y-m-d H:i:s', time() + max(1, min($hours, 168)) * 3600);
        return $this->where('notify_expiring', true)
            ->where('expiring_notified_at IS NULL', null, false)
            ->where('revoked_at IS NULL', null, false)
            ->where('expires_at IS NOT NULL', null, false)
            ->where('expires_at >', $now)
            ->where('expires_at <=', $until)
            ->findAll(200);
    }

    public static function status(array $share): array
    {
        if (! empty($share['revoked_at'])) {
            return ['key' => 'revoked', 'label' => 'Revoked'];
        }

        if (! empty($share['expires_at']) && strtotime((string) $share['expires_at']) <= time()) {
            return ['key' => 'expired', 'label' => 'Expired'];
        }

        $max = $share['max_downloads'] ?? null;
        if ($max !== null && (int) $share['download_count'] >= (int) $max) {
            return ['key' => 'used', 'label' => 'Limit reached'];
        }

        return ['key' => 'active', 'label' => 'Active'];
    }
}
