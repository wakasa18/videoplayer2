<?php

namespace App\Models;

use CodeIgniter\Model;

class ImportantFileModel extends Model
{
    protected $table            = 'important_files';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'title',
        'description',
        'category',
        'stored_filename',
        'original_filename',
        'file_path',
        'file_extension',
        'mime_type',
        'file_size',
        'checksum_sha256',
        'upload_token_hash',
        'status',
        'document_date',
        'expires_at',
        'reminder_days',
        'expiration_reminded_at',
        'is_favorite',
        'download_count',
        'last_downloaded_at',
        'finalized_at',
        'deleted_at',
        'purge_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'title'             => 'required|min_length[1]|max_length[255]',
        'original_filename' => 'required|max_length[255]',
        'stored_filename'   => 'required|max_length[255]',
        'file_path'         => 'required|max_length[500]',
        'file_extension'    => 'permit_empty|max_length[20]',
        'mime_type'         => 'required|max_length[150]',
        'file_size'         => 'required|integer|greater_than[0]',
        'status'            => 'required|in_list[pending,active,deleted,failed]',
        'reminder_days'     => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[3650]',
    ];

    public function getFilteredActive(array $filters, int $perPage = 10): array
    {
        $this->where('status', 'active');

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $this->groupStart()
                ->like('title', $query)
                ->orLike('description', $query)
                ->orLike('category', $query)
                ->orLike('original_filename', $query)
                ->groupEnd();
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $this->where('category', $category);
        }

        $extension = strtolower(trim((string) ($filters['type'] ?? '')));
        if ($extension !== '') {
            $this->where('file_extension', $extension);
        }

        $expiry = (string) ($filters['expiry'] ?? '');
        $today  = date('Y-m-d');
        $soon   = date('Y-m-d', strtotime('+30 days'));

        if ($expiry === 'expired') {
            $this->where('expires_at IS NOT NULL', null, false)->where('expires_at <', $today);
        } elseif ($expiry === 'soon') {
            $this->where('expires_at IS NOT NULL', null, false)
                ->where('expires_at >=', $today)
                ->where('expires_at <=', $soon);
        } elseif ($expiry === 'none') {
            $this->where('expires_at IS NULL', null, false);
        }

        if (($filters['favorite'] ?? '') === '1') {
            $this->where('is_favorite', true);
        }

        $sortMap = [
            'newest'     => ['created_at', 'DESC'],
            'oldest'     => ['created_at', 'ASC'],
            'name_asc'   => ['title', 'ASC'],
            'name_desc'  => ['title', 'DESC'],
            'size_asc'   => ['file_size', 'ASC'],
            'size_desc'  => ['file_size', 'DESC'],
            'expires'    => ['expires_at', 'ASC'],
        ];
        [$sortColumn, $sortDirection] = $sortMap[$filters['sort'] ?? 'newest'] ?? $sortMap['newest'];

        if ($sortColumn === 'expires_at') {
            $this->orderBy('expires_at IS NULL', 'ASC', false);
        }

        $this->orderBy('is_favorite', 'DESC')
            ->orderBy($sortColumn, $sortDirection)
            ->orderBy('id', 'DESC');

        return $this->paginate($perPage, 'files');
    }

    public function getDeletedFiles(int $perPage = 10): array
    {
        return $this->where('status', 'deleted')
            ->orderBy('deleted_at', 'DESC')
            ->paginate($perPage, 'recycle');
    }

    public function getCategories(): array
    {
        $rows = $this->select('category')
            ->where('status', 'active')
            ->where('category IS NOT NULL', null, false)
            ->where('category <>', '')
            ->groupBy('category')
            ->orderBy('category', 'ASC')
            ->findAll();

        return array_values(array_filter(array_column($rows, 'category')));
    }

    public function getExtensions(): array
    {
        $rows = $this->select('file_extension')
            ->where('status', 'active')
            ->where('file_extension IS NOT NULL', null, false)
            ->where('file_extension <>', '')
            ->groupBy('file_extension')
            ->orderBy('file_extension', 'ASC')
            ->findAll();

        return array_values(array_filter(array_column($rows, 'file_extension')));
    }

    public function getVaultSummary(): array
    {
        $row = $this->select('COUNT(*) AS file_count, COALESCE(SUM(file_size), 0) AS total_bytes', false)
            ->where('status', 'active')
            ->first();

        return [
            'file_count'  => (int) ($row['file_count'] ?? 0),
            'total_bytes' => (int) ($row['total_bytes'] ?? 0),
        ];
    }

    public function markDeleted(int $id, int $retentionDays = 30): bool
    {
        return $this->update($id, [
            'status'     => 'deleted',
            'deleted_at' => date('Y-m-d H:i:s'),
            'purge_at'   => date('Y-m-d H:i:s', strtotime('+' . $retentionDays . ' days')),
        ]);
    }

    public function restoreFile(int $id): bool
    {
        return $this->update($id, [
            'status'     => 'active',
            'deleted_at' => null,
            'purge_at'   => null,
        ]);
    }

    public function recordDownload(int $id): bool
    {
        $file = $this->find($id);
        if (! $file) {
            return false;
        }

        return $this->update($id, [
            'download_count'    => ((int) ($file['download_count'] ?? 0)) + 1,
            'last_downloaded_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function stalePendingFiles(int $olderThanSeconds = 3600): array
    {
        return $this->whereIn('status', ['pending', 'failed'])
            ->where('created_at <', date('Y-m-d H:i:s', time() - $olderThanSeconds))
            ->findAll();
    }

    public function expiredRecycleFiles(): array
    {
        return $this->where('status', 'deleted')
            ->where('purge_at IS NOT NULL', null, false)
            ->where('purge_at <=', date('Y-m-d H:i:s'))
            ->findAll();
    }

    public function expirationReminderCandidates(): array
    {
        return $this->where('status', 'active')
            ->where('expires_at IS NOT NULL', null, false)
            ->orderBy('expires_at', 'ASC')
            ->findAll();
    }

    public static function formatBytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);
        $value = $bytes / (1024 ** $pow);

        return round($value, $precision) . ' ' . $units[$pow];
    }

    public static function typeLabel(string $mimeType, string $originalFilename): string
    {
        $ext = strtoupper((string) pathinfo($originalFilename, PATHINFO_EXTENSION));

        return $ext !== '' ? $ext : 'FILE';
    }

    public static function isPreviewable(array $file): bool
    {
        $extension = strtolower((string) ($file['file_extension'] ?? pathinfo((string) $file['original_filename'], PATHINFO_EXTENSION)));

        return in_array($extension, ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'txt', 'csv'], true);
    }

    public static function expirationState(?string $expiresAt): ?array
    {
        if (! $expiresAt) {
            return null;
        }

        $today = new \DateTimeImmutable('today');
        $date  = new \DateTimeImmutable($expiresAt);
        $days  = (int) $today->diff($date)->format('%r%a');

        if ($days < 0) {
            return ['key' => 'expired', 'label' => 'Expired ' . abs($days) . 'd ago'];
        }
        if ($days === 0) {
            return ['key' => 'soon', 'label' => 'Expires today'];
        }
        if ($days <= 30) {
            return ['key' => 'soon', 'label' => 'Expires in ' . $days . 'd'];
        }

        return ['key' => 'valid', 'label' => 'Expires ' . $date->format('M j, Y')];
    }
}
