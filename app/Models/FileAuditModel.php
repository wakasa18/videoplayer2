<?php

namespace App\Models;

use CodeIgniter\Model;

class FileAuditModel extends Model
{
    protected $table            = 'important_file_audits';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'file_id',
        'action',
        'details',
        'actor_ip_hash',
        'user_agent',
        'created_at',
    ];

    public function logAction(string $action, ?int $fileId = null, array $details = []): bool
    {
        $request   = service('request');
        $ip        = method_exists($request, 'getIPAddress') ? $request->getIPAddress() : '';
        $pepper    = getenv('APP_KEY') ?: getenv('CRON_SECRET') ?: 'archive-file-audit';
        $userAgent = method_exists($request, 'getUserAgent') ? (string) $request->getUserAgent() : '';

        return (bool) $this->insert([
            'file_id'       => $fileId,
            'action'        => mb_substr($action, 0, 80),
            'details'       => $details === [] ? null : json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'actor_ip_hash' => $ip === '' ? null : substr(hash_hmac('sha256', $ip, $pepper), 0, 32),
            'user_agent'    => $userAgent === '' ? null : mb_substr($userAgent, 0, 255),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    public function recent(int $perPage = 20): array
    {
        return $this->select('important_file_audits.*, important_files.title AS file_title')
            ->join('important_files', 'important_files.id = important_file_audits.file_id', 'left')
            ->orderBy('important_file_audits.created_at', 'DESC')
            ->paginate($perPage, 'activity');
    }
}
