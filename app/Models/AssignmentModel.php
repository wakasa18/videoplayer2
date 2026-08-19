<?php

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;
use DateTimeImmutable;
use Throwable;

class AssignmentModel extends Model
{
    protected $table            = 'assignments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'title', 'description', 'due_date', 'due_time', 'status', 'priority',
        'subject', 'subject_id', 'link_url', 'recurrence', 'recurrence_series_id',
        'next_occurrence_id', 'notes_log', 'sort_order', 'deleted_at', 'archived_at',
        'completed_at', 'reminder_sent_at', 'reminder_minutes_before',
        'custom_reminder_at', 'template_id',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'title' => 'required|min_length[1]|max_length[255]',
    ];

    public const PRIORITIES = ['low', 'medium', 'high'];
    public const RECURRENCES = ['weekly', 'biweekly', 'monthly'];
    public const STATUSES = ['to_do', 'in_progress', 'blocked', 'submitted', 'done'];
    public const ACTIVE_STATUSES = ['to_do', 'in_progress', 'blocked'];

    public static function normalizeStatus(?string $status): string
    {
        if ($status === 'pending') {
            return 'to_do';
        }

        return in_array($status, self::STATUSES, true) ? $status : 'to_do';
    }

    public static function normalizePriority(?string $priority): string
    {
        return in_array($priority, self::PRIORITIES, true) ? $priority : 'medium';
    }

    public static function normalizeRecurrence(?string $recurrence): ?string
    {
        return in_array($recurrence, self::RECURRENCES, true) ? $recurrence : null;
    }

    public static function priorityWeight(string $priority): int
    {
        return match ($priority) {
            'high' => 3,
            'low'  => 1,
            default => 2,
        };
    }

    /**
     * Build the server-side filtered queue used by list view and pagination.
     */
    public function getFilteredPage(array $filters, int $perPage = 25): array
    {
        $this->select('assignments.*, assignment_subjects.name AS subject_name, assignment_subjects.code AS subject_code, assignment_subjects.color AS subject_color')
            ->join('assignment_subjects', 'assignment_subjects.id = assignments.subject_id', 'left')
            ->where('assignments.deleted_at', null)
            ->where('assignments.archived_at', null);

        $this->applyFilters($this->builder(), $filters);
        $this->applySort($filters['sort'] ?? 'due');

        return $this->paginate(max(10, min($perPage, 100)), 'assignments');
    }

    public function getBoardItems(array $filters, int $limit = 500): array
    {
        $this->select('assignments.*, assignment_subjects.name AS subject_name, assignment_subjects.code AS subject_code, assignment_subjects.color AS subject_color')
            ->join('assignment_subjects', 'assignment_subjects.id = assignments.subject_id', 'left')
            ->where('assignments.deleted_at', null)
            ->where('assignments.archived_at', null);

        $this->applyFilters($this->builder(), array_merge($filters, ['tab' => 'all']));
        $this->orderBy('assignments.sort_order', 'ASC')
            ->orderBy('assignments.due_date', 'ASC')
            ->orderBy('assignments.id', 'DESC');

        return $this->findAll(max(1, min($limit, 1000)));
    }

    public function getCalendarItems(string $start, string $end, array $filters = []): array
    {
        $this->select('assignments.*, assignment_subjects.name AS subject_name, assignment_subjects.code AS subject_code, assignment_subjects.color AS subject_color')
            ->join('assignment_subjects', 'assignment_subjects.id = assignments.subject_id', 'left')
            ->where('assignments.deleted_at', null)
            ->where('assignments.archived_at', null)
            ->where('assignments.due_date IS NOT NULL', null, false)
            ->where('assignments.due_date >=', $start)
            ->where('assignments.due_date <=', $end);

        $this->applySearchAndSimpleFilters($filters);

        return $this->orderBy('assignments.due_date', 'ASC')
            ->orderBy('assignments.due_time', 'ASC')
            ->findAll(1000);
    }

    public function getRecyclePage(int $perPage = 25): array
    {
        return $this->where('deleted_at IS NOT NULL', null, false)
            ->orderBy('deleted_at', 'DESC')
            ->paginate(max(10, min($perPage, 100)), 'assignment_recycle');
    }

    public function getArchivedPage(int $perPage = 25): array
    {
        return $this->where('deleted_at', null)
            ->where('archived_at IS NOT NULL', null, false)
            ->orderBy('archived_at', 'DESC')
            ->paginate(max(10, min($perPage, 100)), 'assignment_archive');
    }

    private function applyFilters(BaseBuilder $builder, array $filters): void
    {
        $this->applySearchAndSimpleFilters($filters);

        $tab = (string) ($filters['tab'] ?? 'all');
        $today = date('Y-m-d');
        $weekEnd = date('Y-m-d', strtotime('+7 days'));

        match ($tab) {
            'today' => $builder->where('assignments.due_date', $today)
                ->whereIn('assignments.status', self::ACTIVE_STATUSES),
            'upcoming' => $builder->where('assignments.due_date >', $today)
                ->where('assignments.due_date <=', $weekEnd)
                ->whereIn('assignments.status', self::ACTIVE_STATUSES),
            'overdue' => $builder->where('assignments.due_date <', $today)
                ->whereIn('assignments.status', self::ACTIVE_STATUSES),
            'no_deadline' => $builder->where('assignments.due_date', null)
                ->whereIn('assignments.status', self::ACTIVE_STATUSES),
            'completed' => $builder->whereIn('assignments.status', ['submitted', 'done']),
            default => null,
        };
    }

    private function applySearchAndSimpleFilters(array $filters): void
    {
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $db = db_connect();
            $noteSubquery = $db->table('assignment_notes')->select('assignment_id')->like('content', $q);
            $subtaskSubquery = $db->table('assignment_subtasks')->select('assignment_id')->like('title', $q);
            $fileSubquery = $db->table('assignment_file_links')
                ->select('assignment_file_links.assignment_id')
                ->join('important_files', 'important_files.id = assignment_file_links.important_file_id', 'inner')
                ->groupStart()->like('important_files.title', $q)->orLike('important_files.original_filename', $q)->groupEnd();
            $this->groupStart()
                ->like('assignments.title', $q)
                ->orLike('assignments.description', $q)
                ->orLike('assignments.subject', $q)
                ->orLike('assignment_subjects.name', $q)
                ->orLike('assignment_subjects.code', $q)
                ->orLike('assignments.link_url', $q)
                ->orLike('assignments.notes_log', $q)
                ->orWhereIn('assignments.id', $noteSubquery)
                ->orWhereIn('assignments.id', $subtaskSubquery)
                ->orWhereIn('assignments.id', $fileSubquery)
                ->groupEnd();
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $this->where('assignments.status', $status);
        }

        $priority = trim((string) ($filters['priority'] ?? ''));
        if ($priority !== '' && in_array($priority, self::PRIORITIES, true)) {
            $this->where('assignments.priority', $priority);
        }

        $subjectId = (int) ($filters['subject_id'] ?? 0);
        if ($subjectId > 0) {
            $this->where('assignments.subject_id', $subjectId);
        }
    }

    private function applySort(string $sort): void
    {
        switch ($sort) {
            case 'priority':
                // Portable priority ordering for PostgreSQL and MySQL.
                $this->orderBy("CASE assignments.priority WHEN 'high' THEN 3 WHEN 'medium' THEN 2 ELSE 1 END", 'DESC', false)
                    ->orderBy('assignments.due_date', 'ASC');
                break;
            case 'newest':
                $this->orderBy('assignments.created_at', 'DESC');
                break;
            case 'oldest':
                $this->orderBy('assignments.created_at', 'ASC');
                break;
            case 'alpha':
                $this->orderBy('assignments.title', 'ASC');
                break;
            case 'subject':
                $this->orderBy('assignment_subjects.name', 'ASC')
                    ->orderBy('assignments.title', 'ASC');
                break;
            case 'manual':
                $this->orderBy('assignments.sort_order', 'ASC')
                    ->orderBy('assignments.id', 'DESC');
                break;
            case 'due':
            default:
                $this->orderBy("CASE WHEN assignments.status IN ('submitted','done') THEN 1 ELSE 0 END", 'ASC', false)
                    ->orderBy("CASE WHEN assignments.due_date IS NULL THEN 1 ELSE 0 END", 'ASC', false)
                    ->orderBy('assignments.due_date', 'ASC')
                    ->orderBy('assignments.due_time', 'ASC')
                    ->orderBy("CASE assignments.priority WHEN 'high' THEN 3 WHEN 'medium' THEN 2 ELSE 1 END", 'DESC', false);
                break;
        }

        $this->orderBy('assignments.id', 'DESC');
    }

    public function getSummaryCounts(): array
    {
        $base = static fn () => db_connect()->table('assignments')->where('deleted_at', null)->where('archived_at', null);
        $today = date('Y-m-d');
        $weekEnd = date('Y-m-d', strtotime('+7 days'));

        return [
            'all' => $base()->countAllResults(),
            'today' => $base()->where('due_date', $today)->whereIn('status', self::ACTIVE_STATUSES)->countAllResults(),
            'upcoming' => $base()->where('due_date >', $today)->where('due_date <=', $weekEnd)->whereIn('status', self::ACTIVE_STATUSES)->countAllResults(),
            'overdue' => $base()->where('due_date <', $today)->whereIn('status', self::ACTIVE_STATUSES)->countAllResults(),
            'no_deadline' => $base()->where('due_date', null)->whereIn('status', self::ACTIVE_STATUSES)->countAllResults(),
            'completed' => $base()->whereIn('status', ['submitted', 'done'])->countAllResults(),
            'archive' => db_connect()->table('assignments')->where('deleted_at', null)->where('archived_at IS NOT NULL', null, false)->countAllResults(),
            'recycle' => db_connect()->table('assignments')->where('deleted_at IS NOT NULL', null, false)->countAllResults(),
        ];
    }

    public function getAnalytics(): array
    {
        $db = db_connect();
        $startWeek = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $startMonth = date('Y-m-01 00:00:00');
        $completedWeek = $db->table('assignments')->where('completed_at >=', $startWeek)->countAllResults();
        $completedMonth = $db->table('assignments')->where('completed_at >=', $startMonth)->countAllResults();
        $completedRows = $db->table('assignments')->select('completed_at,due_date,due_time')
            ->where('completed_at IS NOT NULL', null, false)->get(10000)->getResultArray();
        $onTime = 0;
        $lateHours = [];
        foreach ($completedRows as $row) {
            if (empty($row['due_date'])) {
                continue;
            }
            $due = strtotime($row['due_date'] . ' ' . ($row['due_time'] ?: '23:59'));
            $completed = strtotime((string) $row['completed_at']);
            if ($completed <= $due) {
                $onTime++;
            } else {
                $lateHours[] = ($completed - $due) / 3600;
            }
        }
        $withDeadline = count(array_filter($completedRows, static fn (array $row): bool => ! empty($row['due_date'])));
        $topSubject = $db->table('assignments')
            ->select("COALESCE(assignment_subjects.name, assignments.subject, 'General') AS label, COUNT(*) AS workload", false)
            ->join('assignment_subjects', 'assignment_subjects.id = assignments.subject_id', 'left')
            ->where('assignments.deleted_at', null)->where('assignments.archived_at', null)
            ->whereIn('assignments.status', self::ACTIVE_STATUSES)
            ->groupBy("COALESCE(assignment_subjects.name, assignments.subject, 'General')", false)
            ->orderBy('workload', 'DESC')->limit(1)->get()->getRowArray();
        return [
            'completed_week' => $completedWeek,
            'completed_month' => $completedMonth,
            'on_time_percent' => $withDeadline > 0 ? (int) round(($onTime / $withDeadline) * 100) : 0,
            'active_total' => $db->table('assignments')->where('deleted_at', null)->where('archived_at', null)->whereIn('status', self::ACTIVE_STATUSES)->countAllResults(),
            'average_delay_hours' => $lateHours ? (int) round(array_sum($lateHours) / count($lateHours)) : 0,
            'top_subject' => $topSubject['label'] ?? 'None',
            'top_subject_count' => (int) ($topSubject['workload'] ?? 0),
        ];
    }

    /**
     * Set a workflow status and safely create one recurring occurrence.
     */
    public function setStatus(int $id, string $status): bool
    {
        $status = self::normalizeStatus($status);
        $assignment = $this->find($id);
        if (! $assignment) {
            return false;
        }

        $db = db_connect();
        $db->transStart();

        $fields = ['status' => $status];
        if ($status === 'done') {
            $fields['completed_at'] = date('Y-m-d H:i:s');
        } elseif ($assignment['status'] === 'done') {
            $fields['completed_at'] = null;
        }
        if ($status !== AssignmentModel::normalizeStatus($assignment['status'] ?? null) && in_array($status, self::ACTIVE_STATUSES, true)) {
            $fields['reminder_sent_at'] = null;
        }
        $this->update($id, $fields);

        if ($status === 'done' && ! empty($assignment['recurrence']) && ! empty($assignment['due_date']) && empty($assignment['next_occurrence_id'])) {
            $seriesId = (string) ($assignment['recurrence_series_id'] ?: self::uuid());
            $nextDue = self::nextDueDateAfterToday((string) $assignment['due_date'], (string) $assignment['recurrence']);
            $nextId = $this->insert([
                'title' => $assignment['title'],
                'description' => $assignment['description'],
                'due_date' => $nextDue,
                'due_time' => $assignment['due_time'],
                'status' => 'to_do',
                'priority' => $assignment['priority'],
                'subject' => $assignment['subject'],
                'subject_id' => $assignment['subject_id'] ?? null,
                'link_url' => $assignment['link_url'],
                'recurrence' => $assignment['recurrence'],
                'recurrence_series_id' => $seriesId,
                'reminder_minutes_before' => $assignment['reminder_minutes_before'] ?? 1440,
                'template_id' => $assignment['template_id'] ?? null,
            ], true);

            if ($nextId) {
                $this->update($id, [
                    'recurrence_series_id' => $seriesId,
                    'next_occurrence_id' => (int) $nextId,
                ]);
            }
        }

        $db->transComplete();
        return $db->transStatus();
    }

    public function toggleStatus(int $id): bool
    {
        $assignment = $this->find($id);
        if (! $assignment) {
            return false;
        }

        return $this->setStatus($id, $assignment['status'] === 'done' ? 'to_do' : 'done');
    }

    public static function nextDueDateAfterToday(string $dueDate, string $recurrence): string
    {
        $next = self::nextDueDate($dueDate, $recurrence);
        $today = date('Y-m-d');
        $guard = 0;
        while ($next < $today && $guard < 120) {
            $next = self::nextDueDate($next, $recurrence);
            $guard++;
        }
        return $next;
    }

    public static function nextDueDate(string $dueDate, string $recurrence): string
    {
        if ($recurrence === 'weekly') {
            return date('Y-m-d', strtotime($dueDate . ' +1 week'));
        }
        if ($recurrence === 'biweekly') {
            return date('Y-m-d', strtotime($dueDate . ' +2 weeks'));
        }
        if ($recurrence === 'monthly') {
            $date = new DateTimeImmutable($dueDate);
            $year = (int) $date->format('Y');
            $month = (int) $date->format('n') + 1;
            $day = (int) $date->format('j');
            if ($month > 12) {
                $month = 1;
                $year++;
            }
            $days = (int) (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
            return sprintf('%04d-%02d-%02d', $year, $month, min($day, $days));
        }
        return $dueDate;
    }

    public function softDelete(int $id): bool
    {
        return $this->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
    }

    public function restore(int $id): bool
    {
        return $this->update($id, ['deleted_at' => null]);
    }

    public function archive(int $id): bool
    {
        return $this->update($id, ['archived_at' => date('Y-m-d H:i:s')]);
    }

    public function unarchive(int $id): bool
    {
        return $this->update($id, ['archived_at' => null]);
    }

    public function permanentlyDelete(int $id): bool
    {
        return (bool) $this->builder()->where('id', $id)->delete();
    }

    public function duplicateAssignment(int $id): ?int
    {
        $a = $this->find($id);
        if (! $a) {
            return null;
        }

        return (int) $this->insert([
            'title' => $a['title'] . ' (Copy)',
            'description' => $a['description'],
            'due_date' => $a['due_date'],
            'due_time' => $a['due_time'],
            'status' => 'to_do',
            'priority' => $a['priority'],
            'subject' => $a['subject'],
            'subject_id' => $a['subject_id'] ?? null,
            'link_url' => $a['link_url'],
            'recurrence' => $a['recurrence'],
            'reminder_minutes_before' => $a['reminder_minutes_before'] ?? 1440,
            'template_id' => $a['template_id'] ?? null,
        ], true);
    }

    public function saveOrder(array $ids): void
    {
        foreach (array_values($ids) as $index => $id) {
            if (($id = (int) $id) > 0) {
                $this->update($id, ['sort_order' => $index]);
            }
        }
    }

    public static function isOverdue(array $assignment): bool
    {
        $status = self::normalizeStatus($assignment['status'] ?? null);
        if (! in_array($status, self::ACTIVE_STATUSES, true) || empty($assignment['due_date'])) {
            return false;
        }
        return strtotime($assignment['due_date'] . ' ' . ($assignment['due_time'] ?: '23:59')) < time();
    }

    public static function countOverdue(array $assignments): int
    {
        return count(array_filter($assignments, [self::class, 'isOverdue']));
    }

    public function getUrgent(int $daysAhead = 2): array
    {
        $limit = date('Y-m-d', strtotime("+{$daysAhead} days"));
        return $this->where('deleted_at', null)
            ->where('archived_at', null)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where('due_date <=', $limit)
            ->orderBy('due_date', 'ASC')
            ->findAll();
    }

    /**
     * Custom reminder-aware queue. A custom timestamp takes precedence;
     * otherwise the due timestamp minus reminder_minutes_before is used.
     */
    public function getDueSoonForReminder(int $daysAhead = 7): array
    {
        $candidates = $this->where('deleted_at', null)
            ->where('archived_at', null)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->groupStart()
                ->where('due_date <=', date('Y-m-d', strtotime("+{$daysAhead} days")))
                ->orWhere('custom_reminder_at IS NOT NULL', null, false)
            ->groupEnd()
            ->orderBy('due_date', 'ASC')
            ->findAll(1000);

        $now = time();
        $todayStart = strtotime(date('Y-m-d 00:00:00'));
        return array_values(array_filter($candidates, static function (array $a) use ($now, $todayStart): bool {
            if (! empty($a['reminder_sent_at']) && strtotime((string) $a['reminder_sent_at']) >= $todayStart) {
                return false;
            }
            if (! empty($a['custom_reminder_at'])) {
                return strtotime((string) $a['custom_reminder_at']) <= $now;
            }
            if (empty($a['due_date'])) {
                return false;
            }
            $due = strtotime($a['due_date'] . ' ' . ($a['due_time'] ?: '23:59'));
            $offset = max(0, (int) ($a['reminder_minutes_before'] ?? 1440)) * 60;
            return ($due - $offset) <= $now;
        }));
    }

    public function markReminderSent(int $id): bool
    {
        return $this->update($id, ['reminder_sent_at' => date('Y-m-d H:i:s')]);
    }

    public static function relativeDueDate(array $assignment): ?string
    {
        if (empty($assignment['due_date'])) {
            return null;
        }
        $suffix = ! empty($assignment['due_time']) ? ', ' . date('g:i A', strtotime($assignment['due_time'])) : '';
        if (in_array(self::normalizeStatus($assignment['status'] ?? null), ['submitted', 'done'], true)) {
            return 'Due ' . date('M j, Y', strtotime($assignment['due_date'])) . $suffix;
        }
        $today = new DateTimeImmutable(date('Y-m-d'));
        $due = new DateTimeImmutable($assignment['due_date']);
        $diff = (int) $today->diff($due)->format('%r%a');
        return match (true) {
            $diff === 0 => 'Due today' . $suffix,
            $diff === 1 => 'Due tomorrow' . $suffix,
            $diff > 1 => "Due in {$diff} days" . $suffix,
            $diff === -1 => 'Due yesterday',
            default => abs($diff) . ' days overdue',
        };
    }

    public function clearCompleted(): array
    {
        $rows = $this->where('deleted_at', null)->whereIn('status', ['submitted', 'done'])->findAll();
        $ids = array_map('intval', array_column($rows, 'id'));
        if ($ids) {
            $this->whereIn('id', $ids)->set(['archived_at' => date('Y-m-d H:i:s')])->update();
        }
        return $ids;
    }

    public function markAllDone(): array
    {
        $rows = $this->where('deleted_at', null)->where('archived_at', null)->whereIn('status', self::ACTIVE_STATUSES)->findAll();
        $ids = [];
        foreach ($rows as $row) {
            if ($this->setStatus((int) $row['id'], 'done')) {
                $ids[] = (int) $row['id'];
            }
        }
        return $ids;
    }

    public function restoreMany(array $ids): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids) {
            $this->whereIn('id', $ids)->set(['deleted_at' => null, 'archived_at' => null])->update();
        }
    }

    public function unmarkMany(array $ids): void
    {
        foreach (array_values(array_filter(array_map('intval', $ids))) as $id) {
            $this->setStatus($id, 'to_do');
        }
    }

    public function purgeDeletedOlderThan(int $days = 30): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-' . max(1, $days) . ' days'));
        $rows = $this->select('id')->where('deleted_at <', $cutoff)->findAll(5000);
        $ids = array_map('intval', array_column($rows, 'id'));
        if (! $ids) {
            return 0;
        }
        $this->builder()->whereIn('id', $ids)->delete();
        return count($ids);
    }

    public static function subjectColorRgb(string $subject): string
    {
        $palette = ['66,233,255', '139,99,255', '255,209,102', '255,105,127', '111,207,151', '242,153,74', '187,107,217', '86,204,242'];
        return $palette[crc32(strtolower(trim($subject))) % count($palette)];
    }

    private static function uuid(): string
    {
        $hex = bin2hex(random_bytes(16));
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }
}
