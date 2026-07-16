<?php

namespace App\Models;

use CodeIgniter\Model;

class VideoModel extends Model
{
    protected $table            = 'videos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false; // we use a 'status' column instead, see below

    protected $allowedFields = [
        'title',
        'description',
        'filename',
        'original_filename',
        'file_path',
        'mime_type',
        'file_size',
        'duration_seconds',
        'thumbnail_path',
        'status',
        'deleted_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'title' => 'required|min_length[1]|max_length[255]',
    ];

    /**
     * Only videos that haven't been soft-"deleted" via status.
     */
    public function getActiveVideos(): array
    {
        return $this->where('status', 'active')
                     ->orderBy('created_at', 'DESC')
                     ->findAll();
    }

    /**
     * Soft-delete: flips status to 'deleted' instead of removing the row,
     * so the DB record (and history) is preserved even after the file is gone.
     */
    public function softDeleteVideo(int $id): bool
    {
        return $this->update($id, [
            'status'     => 'deleted',
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Human-readable file size, e.g. "12.4 MB".
     */
    public static function formatBytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);
        $bytes /= (1024 ** $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Format seconds as H:MM:SS or M:SS timecode.
     */
    public static function formatDuration(?int $seconds): string
    {
        if (!$seconds) {
            return '--:--';
        }

        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;

        return $h > 0
            ? sprintf('%d:%02d:%02d', $h, $m, $s)
            : sprintf('%d:%02d', $m, $s);
    }
}
