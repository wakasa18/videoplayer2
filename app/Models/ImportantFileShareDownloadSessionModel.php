<?php

namespace App\Models;

use CodeIgniter\Model;

class ImportantFileShareDownloadSessionModel extends Model
{
    protected $table            = 'important_file_share_download_sessions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'session_token_hash', 'share_id', 'folder_path', 'file_ids', 'file_count',
        'total_bytes', 'status', 'expires_at', 'completed_at', 'created_at',
    ];

    public function createPending(int $shareId, array $fileIds, ?string $folderPath, int $totalBytes): string
    {
        $fileIds = array_values(array_unique(array_filter(array_map('intval', $fileIds), static fn (int $id): bool => $id > 0)));
        $raw = bin2hex(random_bytes(32));
        $this->insert([
            'session_token_hash' => hash('sha256', $raw),
            'share_id'           => $shareId,
            'folder_path'        => $folderPath,
            'file_ids'           => json_encode($fileIds),
            'file_count'         => count($fileIds),
            'total_bytes'        => max(0, $totalBytes),
            'status'             => 'pending',
            'expires_at'         => date('Y-m-d H:i:s', time() + 1800),
            'completed_at'       => null,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        return $raw;
    }

    public function findPending(string $rawToken, int $shareId): ?array
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $rawToken)) {
            return null;
        }

        $row = $this->where('session_token_hash', hash('sha256', $rawToken))
            ->where('share_id', $shareId)
            ->where('status', 'pending')
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->first();

        if (! $row) {
            return null;
        }
        $ids = json_decode((string) ($row['file_ids'] ?? '[]'), true);
        $row['file_ids'] = is_array($ids) ? array_map('intval', $ids) : [];

        return $row;
    }

    public function complete(int $id): bool
    {
        return (bool) $this->builder()->where('id', $id)->where('status', 'pending')
            ->set('status', 'completed')
            ->set('completed_at', date('Y-m-d H:i:s'))
            ->update();
    }

    public function cancelByToken(string $rawToken, int $shareId): bool
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $rawToken)) {
            return false;
        }
        return (bool) $this->builder()
            ->where('session_token_hash', hash('sha256', $rawToken))
            ->where('share_id', $shareId)
            ->where('status', 'pending')
            ->set('status', 'cancelled')
            ->update();
    }

    public function expireOld(): int
    {
        $this->builder()->where('status', 'pending')
            ->where('expires_at <=', date('Y-m-d H:i:s'))
            ->set('status', 'expired')
            ->update();
        return $this->db->affectedRows();
    }
}
