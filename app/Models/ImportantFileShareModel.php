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
        'file_id',
        'token_hash',
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
        'file_id'       => 'required|integer|greater_than[0]',
        'token_hash'    => 'required|exact_length[64]|alpha_numeric',
        'max_downloads' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[10000]',
    ];

    public function recentForFile(int $fileId): array
    {
        return $this->where('file_id', $fileId)
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
