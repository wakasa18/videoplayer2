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
        'folder_path',
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
        'folder_path'       => 'permit_empty|max_length[1000]',
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
                ->orLike('folder_path', $query)
                ->orLike('original_filename', $query)
                ->groupEnd();
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $this->where('category', $category);
        }

        $folder = trim((string) ($filters['folder'] ?? ''));
        if ($folder !== '') {
            $this->where('folder_path', $folder);
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

    public function getFolderFiles(?string $folderPath, array $filters, int $perPage = 20): array
    {
        $this->where('status', 'active');

        // Favorites is a vault-wide collection, while ordinary browsing and
        // searching stay inside the currently opened folder.
        if (($filters['favorite'] ?? '') !== '1') {
            if ($folderPath === null || $folderPath === '') {
                $this->groupStart()
                    ->where('folder_path IS NULL', null, false)
                    ->orWhere('folder_path', '')
                    ->groupEnd();
            } else {
                $this->where('folder_path', $folderPath);
            }
        }

        $this->applyFileFilters($filters);

        return $this->paginate($perPage, 'files');
    }

    /**
     * Return only the immediate folders inside the current path. Folder rows
     * are derived from file metadata, so existing uploaded folder structures
     * work without another database table.
     *
     * @return array<int, array{name:string,path:string,count:int}>
     */
    public function getChildFolders(?string $folderPath): array
    {
        $rows = $this->select('folder_path')
            ->where('status', 'active')
            ->where('folder_path IS NOT NULL', null, false)
            ->where('folder_path <>', '')
            ->findAll();

        $prefix = $folderPath ? trim($folderPath, '/') . '/' : '';
        $folders = [];

        foreach ($rows as $row) {
            $path = trim((string) ($row['folder_path'] ?? ''), '/');
            if ($path === '') {
                continue;
            }
            if ($prefix !== '' && ! str_starts_with($path . '/', $prefix)) {
                continue;
            }

            $remaining = $prefix === '' ? $path : substr($path, strlen($prefix));
            if ($remaining === '' || $remaining === false) {
                continue;
            }

            $name = explode('/', $remaining, 2)[0];
            if ($name === '') {
                continue;
            }

            $childPath = $prefix . $name;
            if (! isset($folders[$childPath])) {
                $folders[$childPath] = ['name' => $name, 'path' => $childPath, 'count' => 0];
            }
            $folders[$childPath]['count']++;
        }

        uasort($folders, static fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));

        return array_values($folders);
    }

    /**
     * Return every active file inside a folder and all of its descendants.
     * A null path represents the root and therefore returns the whole vault.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFolderTreeFiles(?string $folderPath, int $limit = 2001): array
    {
        $this->select([
            'id',
            'folder_path',
            'original_filename',
            'file_path',
            'file_size',
            'mime_type',
            'created_at',
            'updated_at',
        ])->where('status', 'active');

        if ($folderPath !== null && $folderPath !== '') {
            $this->groupStart()
                ->where('folder_path', $folderPath)
                ->orLike('folder_path', rtrim($folderPath, '/') . '/', 'after')
                ->groupEnd();
        }

        return $this->orderBy('folder_path', 'ASC')
            ->orderBy('original_filename', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll(max(1, $limit));
    }

    /**
     * Increment download counters after a browser confirms that a generated
     * folder archive was saved successfully.
     */
    public function recordBulkDownload(array $ids): bool
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return false;
        }

        return (bool) $this->builder()
            ->whereIn('id', $ids)
            ->where('status', 'active')
            ->set('download_count', 'COALESCE(download_count, 0) + 1', false)
            ->set('last_downloaded_at', date('Y-m-d H:i:s'))
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->update();
    }

    private function applyFileFilters(array $filters): void
    {
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
            'newest'    => ['created_at', 'DESC'],
            'oldest'    => ['created_at', 'ASC'],
            'name_asc'  => ['title', 'ASC'],
            'name_desc' => ['title', 'DESC'],
            'size_asc'  => ['file_size', 'ASC'],
            'size_desc' => ['file_size', 'DESC'],
            'expires'   => ['expires_at', 'ASC'],
        ];
        [$sortColumn, $sortDirection] = $sortMap[$filters['sort'] ?? 'name_asc'] ?? $sortMap['name_asc'];

        if ($sortColumn === 'expires_at') {
            $this->orderBy('expires_at IS NULL', 'ASC', false);
        }

        $this->orderBy('is_favorite', 'DESC')
            ->orderBy($sortColumn, $sortDirection)
            ->orderBy('id', 'DESC');
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


    public function getFolders(): array
    {
        $rows = $this->select('folder_path')
            ->where('status', 'active')
            ->where('folder_path IS NOT NULL', null, false)
            ->where('folder_path <>', '')
            ->groupBy('folder_path')
            ->orderBy('folder_path', 'ASC')
            ->findAll();

        return array_values(array_filter(array_column($rows, 'folder_path')));
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

    public static function previewKind(array $file): string
    {
        $extension = strtolower((string) ($file['file_extension'] ?? ''));
        if ($extension === '') {
            $extension = strtolower((string) pathinfo((string) ($file['original_filename'] ?? ''), PATHINFO_EXTENSION));
        }
        $mime = strtolower((string) ($file['mime_type'] ?? ''));

        if ($extension === 'pdf' || $mime === 'application/pdf') {
            return 'pdf';
        }
        if (in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'gif', 'bmp', 'avif', 'ico'], true)
            || str_starts_with($mime, 'image/') && ! in_array($extension, ['svg', 'svgz'], true)) {
            return 'image';
        }
        if (in_array($extension, ['mp4', 'webm', 'ogv', 'mov', 'm4v'], true) || str_starts_with($mime, 'video/')) {
            return 'video';
        }
        if (in_array($extension, ['mp3', 'wav', 'ogg', 'oga', 'm4a', 'aac', 'flac', 'opus'], true) || str_starts_with($mime, 'audio/')) {
            return 'audio';
        }

        $textExtensions = [
            'txt', 'csv', 'json', 'md', 'markdown', 'log', 'xml', 'yaml', 'yml',
            'ini', 'conf', 'config', 'sql', 'css', 'js', 'mjs', 'ts', 'jsx', 'tsx',
            'php', 'py', 'java', 'c', 'cpp', 'h', 'hpp', 'cs', 'go', 'rs', 'rb',
            'sh', 'bash', 'bat', 'cmd', 'ps1', 'env', 'gitignore', 'htaccess',
            'html', 'htm', 'svg', 'toml', 'properties', 'vue', 'svelte', 'dart',
        ];
        if (in_array($extension, $textExtensions, true) || str_starts_with($mime, 'text/')) {
            return 'text';
        }

        return 'unsupported';
    }

    public static function isPreviewable(array $file): bool
    {
        return self::previewKind($file) !== 'unsupported';
    }

    public static function previewMimeType(array $file, string $storageMime = ''): string
    {
        $kind = self::previewKind($file);
        $ext  = strtolower((string) ($file['file_extension'] ?? pathinfo((string) ($file['original_filename'] ?? ''), PATHINFO_EXTENSION)));

        if ($kind === 'text') {
            return 'text/plain; charset=UTF-8';
        }
        if ($kind === 'pdf') {
            return 'application/pdf';
        }

        $known = [
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp', 'gif' => 'image/gif', 'bmp' => 'image/bmp',
            'avif' => 'image/avif', 'ico' => 'image/x-icon',
            'mp4' => 'video/mp4', 'webm' => 'video/webm', 'ogv' => 'video/ogg',
            'mov' => 'video/quicktime', 'm4v' => 'video/x-m4v',
            'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg',
            'oga' => 'audio/ogg', 'm4a' => 'audio/mp4', 'aac' => 'audio/aac',
            'flac' => 'audio/flac', 'opus' => 'audio/ogg',
        ];

        if (isset($known[$ext])) {
            return $known[$ext];
        }

        $storageMime = strtolower(trim(explode(';', $storageMime)[0] ?? ''));
        return $storageMime !== '' ? $storageMime : 'application/octet-stream';
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
