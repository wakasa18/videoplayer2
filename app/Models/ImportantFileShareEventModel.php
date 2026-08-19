<?php

namespace App\Models;

use CodeIgniter\Model;

class ImportantFileShareEventModel extends Model
{
    protected $table            = 'important_file_share_events';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'share_id', 'file_id', 'event_type', 'session_hash', 'details',
        'is_notification', 'read_at', 'created_at',
    ];

    public function recordEvent(
        int $shareId,
        string $eventType,
        ?int $fileId = null,
        array $details = [],
        bool $notification = false,
        ?string $sessionHash = null
    ): bool {
        return (bool) $this->insert([
            'share_id'        => $shareId,
            'file_id'         => $fileId,
            'event_type'      => mb_substr($eventType, 0, 60),
            'session_hash'    => $sessionHash ?: $this->visitorHash(),
            'details'         => $details === [] ? null : json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'is_notification' => $notification,
            'read_at'         => null,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    public function visitorHash(): string
    {
        $request = service('request');
        $ip = method_exists($request, 'getIPAddress') ? (string) $request->getIPAddress() : '';
        $ua = method_exists($request, 'getUserAgent') ? (string) $request->getUserAgent() : '';
        $pepper = getenv('APP_KEY') ?: getenv('CRON_SECRET') ?: 'shared-file-visitor';

        return hash_hmac('sha256', $ip . "\0" . $ua, $pepper);
    }

    public function analyticsForShare(int $shareId): array
    {
        $db = $this->db;
        $summary = $db->table($this->table)
            ->select("COUNT(*) AS event_count, COUNT(DISTINCT NULLIF(session_hash, '')) AS unique_visitors", false)
            ->select("MAX(CASE WHEN event_type IN ('share_view','folder_view','file_open') THEN created_at END) AS last_opened_at", false)
            ->select("MAX(CASE WHEN event_type IN ('file_download','folder_download','selection_download') THEN created_at END) AS last_downloaded_at", false)
            ->where('share_id', $shareId)
            ->get()->getRowArray() ?: [];

        $counts = [];
        foreach ($db->table($this->table)
            ->select('event_type, COUNT(*) AS total', false)
            ->where('share_id', $shareId)
            ->groupBy('event_type')
            ->orderBy('total', 'DESC')
            ->get()->getResultArray() as $row) {
            $counts[(string) $row['event_type']] = (int) $row['total'];
        }

        $topFiles = $db->table($this->table . ' events')
            ->select("events.file_id, COALESCE(files.title, files.original_filename, 'File') AS file_name", false)
            ->select("SUM(CASE WHEN events.event_type = 'file_open' THEN 1 ELSE 0 END) AS opens", false)
            ->select("SUM(CASE WHEN events.event_type = 'file_download' THEN 1 ELSE 0 END) AS downloads", false)
            ->join('important_files files', 'files.id = events.file_id', 'left')
            ->where('events.share_id', $shareId)
            ->where('events.file_id IS NOT NULL', null, false)
            ->groupBy('events.file_id, files.title, files.original_filename')
            ->orderBy("(SUM(CASE WHEN events.event_type = 'file_open' THEN 1 ELSE 0 END) + SUM(CASE WHEN events.event_type = 'file_download' THEN 1 ELSE 0 END))", 'DESC', false)
            ->limit(8)
            ->get()->getResultArray();
        foreach ($topFiles as &$topFile) {
            $topFile['file_id'] = (int) ($topFile['file_id'] ?? 0);
            $topFile['opens'] = (int) ($topFile['opens'] ?? 0);
            $topFile['downloads'] = (int) ($topFile['downloads'] ?? 0);
        }
        unset($topFile);

        $recent = $this->where('share_id', $shareId)
            ->orderBy('created_at', 'DESC')
            ->findAll(30);
        foreach ($recent as &$event) {
            $decoded = json_decode((string) ($event['details'] ?? ''), true);
            $event['details'] = is_array($decoded) ? $decoded : [];
        }
        unset($event);

        $unread = $this->where('share_id', $shareId)
            ->where('is_notification', true)
            ->where('read_at IS NULL', null, false)
            ->countAllResults();

        return [
            'eventCount'       => (int) ($summary['event_count'] ?? 0),
            'uniqueVisitors'   => (int) ($summary['unique_visitors'] ?? 0),
            'lastOpenedAt'     => $summary['last_opened_at'] ?? null,
            'lastDownloadedAt' => $summary['last_downloaded_at'] ?? null,
            'counts'           => $counts,
            'topFiles'         => $topFiles,
            'recent'           => $recent,
            'unreadNotifications' => (int) $unread,
        ];
    }

    public function markNotificationsRead(int $shareId): bool
    {
        return (bool) $this->builder()
            ->where('share_id', $shareId)
            ->where('is_notification', true)
            ->where('read_at IS NULL', null, false)
            ->set('read_at', date('Y-m-d H:i:s'))
            ->update();
    }

    public function unreadCountForShare(int $shareId): int
    {
        return $this->where('share_id', $shareId)
            ->where('is_notification', true)
            ->where('read_at IS NULL', null, false)
            ->countAllResults();
    }
}
