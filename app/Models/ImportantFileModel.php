<?php

namespace App\Models;

use CodeIgniter\Model;

class ImportantFileModel extends Model
{
    protected $table            = 'important_files';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false; // we use a 'status' column instead, see below

    protected $allowedFields = [
        'title',
        'description',
        'category',
        'stored_filename',
        'original_filename',
        'file_path',
        'mime_type',
        'file_size',
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
     * Only files that haven't been soft-"deleted" via status.
     */
    public function getActiveFiles(): array
    {
        return $this->where('status', 'active')
                     ->orderBy('created_at', 'DESC')
                     ->findAll();
    }

    /**
     * Soft-delete: flips status to 'deleted' instead of removing the row,
     * so the DB record (and history) is preserved even after the file
     * itself is gone from storage.
     */
    public function softDeleteFile(int $id): bool
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
     * Short label for a mime type, used for the file-type badge —
     * "application/pdf" -> "PDF", "image/png" -> "PNG", etc.
     */
    public static function typeLabel(string $mimeType, string $originalFilename): string
    {
        $known = [
            'application/pdf'                                                          => 'PDF',
            'application/msword'                                                       => 'DOC',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'DOCX',
            'application/vnd.ms-excel'                                                  => 'XLS',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'XLSX',
            'application/vnd.ms-powerpoint'                                             => 'PPT',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PPTX',
            'application/zip'                                                           => 'ZIP',
            'text/plain'                                                                => 'TXT',
            'image/png'                                                                 => 'PNG',
            'image/jpeg'                                                                => 'JPG',
            'image/webp'                                                                => 'WEBP',
            'image/gif'                                                                 => 'GIF',
        ];

        if (isset($known[$mimeType])) {
            return $known[$mimeType];
        }

        $ext = strtoupper((string) pathinfo($originalFilename, PATHINFO_EXTENSION));

        return $ext !== '' ? $ext : 'FILE';
    }
}
