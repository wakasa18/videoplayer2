<?php

namespace App\Models;

use CodeIgniter\Model;
use DateTimeImmutable;

class AssignmentModel extends Model
{
    protected $table            = 'assignments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false; // handled manually below, see softDelete()/restore()

    protected $allowedFields = [
        'title',
        'description',
        'due_date',
        'due_time',
        'status',
        'priority',
        'subject',
        'link_url',
        'recurrence',
        'notes_log',
        'sort_order',
        'deleted_at',
        'reminder_sent_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'title' => 'required|min_length[1]|max_length[255]',
    ];

    public const PRIORITIES  = ['low', 'medium', 'high'];
    public const RECURRENCES = ['weekly', 'biweekly', 'monthly'];

    /**
     * Pending assignments first (soonest due date first, no-due-date last),
     * then done ones at the bottom. Same-due-date ties break by priority,
     * highest first. Sorted in PHP rather than SQL so the ordering behaves
     * identically regardless of DB driver (MySQL vs the Postgres/Supabase
     * connection this app actually runs on). Soft-deleted rows are excluded.
     */
    public function getAllOrdered(): array
    {
        $items = $this->where('deleted_at', null)->orderBy('created_at', 'DESC')->findAll();

        usort($items, static function (array $a, array $b): int {
            if ($a['status'] !== $b['status']) {
                return $a['status'] === 'done' ? 1 : -1;
            }

            $aDate = $a['due_date'] ?: '9999-12-31';
            $bDate = $b['due_date'] ?: '9999-12-31';

            if ($aDate !== $bDate) {
                return strcmp($aDate, $bDate);
            }

            return self::priorityWeight($b['priority'] ?? 'medium') <=> self::priorityWeight($a['priority'] ?? 'medium');
        });

        return $items;
    }

    /**
     * Flip an assignment between pending and done. If it's being completed
     * and has a recurrence set with a due date, spins off the next
     * occurrence automatically.
     */
    public function toggleStatus(int $id): bool
    {
        $assignment = $this->find($id);

        if (! $assignment) {
            return false;
        }

        $completing = $assignment['status'] !== 'done';
        $newStatus  = $completing ? 'done' : 'pending';

        $ok = $this->update($id, ['status' => $newStatus]);

        if ($ok && $completing && ! empty($assignment['recurrence']) && ! empty($assignment['due_date'])) {
            $this->createNextOccurrence($assignment);
        }

        return $ok;
    }

    /**
     * Insert the next occurrence of a recurring assignment, due date
     * advanced by its repeat interval. Everything else carries over as a
     * fresh copy — notes and the reminder flag deliberately do not, since
     * this is effectively a new task.
     */
    private function createNextOccurrence(array $assignment): void
    {
        $this->insert([
            'title'       => $assignment['title'],
            'description' => $assignment['description'],
            'due_date'    => self::nextDueDate((string) $assignment['due_date'], (string) $assignment['recurrence']),
            'due_time'    => $assignment['due_time'],
            'status'      => 'pending',
            'priority'    => $assignment['priority'],
            'subject'     => $assignment['subject'],
            'link_url'    => $assignment['link_url'],
            'recurrence'  => $assignment['recurrence'],
        ]);
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
            // Deliberately not strtotime('+1 month') here: PHP overflows
            // rather than clamping (Jan 31 "+1 month" lands in early March,
            // not Feb 28). Compute the target month/year directly and clamp
            // the day to whatever that month actually has.
            $date  = new DateTimeImmutable($dueDate);
            $year  = (int) $date->format('Y');
            $month = (int) $date->format('n') + 1;
            $day   = (int) $date->format('j');

            if ($month > 12) {
                $month = 1;
                $year++;
            }

            $daysInTargetMonth = (int) (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
            $day = min($day, $daysInTargetMonth);

            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        return $dueDate;
    }

    public static function normalizeRecurrence(?string $recurrence): ?string
    {
        return in_array($recurrence, self::RECURRENCES, true) ? $recurrence : null;
    }

    /**
     * Soft-delete: hide it from the queue without losing the row, so a
     * delete can be undone.
     */
    public function softDelete(int $id): bool
    {
        return $this->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Undo a soft delete.
     */
    public function restore(int $id): bool
    {
        return $this->update($id, ['deleted_at' => null]);
    }

    /**
     * Append a timestamped line to an assignment's running notes log.
     */
    public function addNote(int $id, string $note): bool
    {
        $assignment = $this->find($id);

        if (! $assignment) {
            return false;
        }

        $line     = '[' . date('M j, g:i A') . '] ' . $note;
        $existing = $assignment['notes_log'] ?? '';
        $updated  = $existing !== '' && $existing !== null ? $existing . "\n" . $line : $line;

        return $this->update($id, ['notes_log' => $updated]);
    }

    /**
     * Persist a manual drag-and-drop order: $ids is the full list of
     * assignment ids in their new top-to-bottom order.
     */
    public function saveOrder(array $ids): void
    {
        foreach (array_values($ids) as $index => $id) {
            $id = (int) $id;
            if ($id > 0) {
                $this->update($id, ['sort_order' => $index]);
            }
        }
    }

    /**
     * Normalize a submitted priority value to one of the allowed options.
     */
    public static function normalizePriority(?string $priority): string
    {
        return in_array($priority, self::PRIORITIES, true) ? $priority : 'medium';
    }

    public static function priorityWeight(string $priority): int
    {
        return match ($priority) {
            'high'  => 3,
            'low'   => 1,
            default => 2,
        };
    }

    /**
     * True if a pending assignment's due date (and due time, if set) has
     * passed. Assignments with no due_time default to end-of-day (23:59),
     * so a date-only assignment behaves exactly as before — safe all day,
     * overdue starting the next calendar day. Setting an explicit time
     * enables same-day overdue detection.
     */
    public static function isOverdue(array $assignment): bool
    {
        if ($assignment['status'] === 'done' || empty($assignment['due_date'])) {
            return false;
        }

        $time = ! empty($assignment['due_time']) ? $assignment['due_time'] : '23:59';

        return strtotime($assignment['due_date'] . ' ' . $time) < time();
    }

    /**
     * How many assignments in a list are currently overdue. Shared by the
     * site-wide banner and the reminder email so both describe urgency
     * the same way.
     */
    public static function countOverdue(array $assignments): int
    {
        return count(array_filter($assignments, [self::class, 'isOverdue']));
    }

    /**
     * Pending assignments due within the next $daysAhead days, or already
     * overdue. Powers the site-wide "due soon" banner (daysAhead=2) and
     * the weekly planning digest (daysAhead=7).
     */
    public function getUrgent(int $daysAhead = 2): array
    {
        $limit = date('Y-m-d', strtotime("+{$daysAhead} days"));

        return $this->where('deleted_at', null)
            ->where('status', 'pending')
            ->where('due_date <=', $limit)
            ->orderBy('due_date', 'ASC')
            ->findAll();
    }

    /**
     * Pending assignments that are due soon (within $daysAhead) OR already
     * overdue, and haven't already been emailed today. Re-includes an
     * assignment on each new day it's still urgent — so it nags daily
     * until you mark it done, push the due date out, or delete it —
     * instead of emailing once and going silent.
     */
    public function getDueSoonForReminder(int $daysAhead = 2): array
    {
        $limit      = date('Y-m-d', strtotime("+{$daysAhead} days"));
        $startOfDay = date('Y-m-d 00:00:00');

        return $this->where('deleted_at', null)
            ->where('status', 'pending')
            ->where('due_date <=', $limit)
            ->groupStart()
                ->where('reminder_sent_at', null)
                ->orWhere('reminder_sent_at <', $startOfDay)
            ->groupEnd()
            ->orderBy('due_date', 'ASC')
            ->findAll();
    }

    /**
     * Mark that a reminder email went out today for this assignment. Since
     * this only guards against re-sending on the *same* day, it'll be
     * eligible again automatically once tomorrow starts.
     */
    public function markReminderSent(int $id): bool
    {
        return $this->update($id, ['reminder_sent_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Human, urgency-aware due-date text: "Due today, 11:59 PM", "Due in
     * 3 days", "2 days overdue"... Done items just get a plain
     * "Due <date>" since urgency no longer applies. The due_time suffix is
     * only appended for today/tomorrow/future — once something's overdue
     * by whole days, the exact time it was due stops being useful.
     * Returns null when there's no due date.
     */
    public static function relativeDueDate(array $assignment): ?string
    {
        if (empty($assignment['due_date'])) {
            return null;
        }

        $timeSuffix = ! empty($assignment['due_time'])
            ? ', ' . date('g:i A', strtotime($assignment['due_time']))
            : '';

        $formatted = date('M j, Y', strtotime((string) $assignment['due_date'])) . $timeSuffix;

        if ($assignment['status'] === 'done') {
            return "Due {$formatted}";
        }

        $today = new DateTimeImmutable(date('Y-m-d'));
        $due   = new DateTimeImmutable(date('Y-m-d', strtotime((string) $assignment['due_date'])));
        $diff  = (int) $today->diff($due)->format('%r%a');

        return match (true) {
            $diff === 0  => 'Due today' . $timeSuffix,
            $diff === 1  => 'Due tomorrow' . $timeSuffix,
            $diff > 1    => "Due in {$diff} days" . $timeSuffix,
            $diff === -1 => 'Due yesterday',
            default      => abs($diff) . ' days overdue',
        };
    }

    /**
     * Soft-delete every currently-done, non-deleted assignment at once.
     * Returns the affected ids so the caller can offer an Undo.
     */
    public function clearCompleted(): array
    {
        $done = $this->where('deleted_at', null)->where('status', 'done')->findAll();
        $ids  = array_column($done, 'id');

        if ($ids !== []) {
            $this->whereIn('id', $ids)->update(null, ['deleted_at' => date('Y-m-d H:i:s')]);
        }

        return $ids;
    }

    /**
     * Mark every currently-pending, non-deleted assignment as done at once.
     * Returns the affected ids so the caller can offer an Undo.
     */
    public function markAllDone(): array
    {
        $pending = $this->where('deleted_at', null)->where('status', 'pending')->findAll();
        $ids     = array_column($pending, 'id');

        if ($ids !== []) {
            $this->whereIn('id', $ids)->update(null, ['status' => 'done']);
        }

        return $ids;
    }

    /**
     * Undo for clearCompleted(): un-delete a batch of ids.
     */
    public function restoreMany(array $ids): void
    {
        $ids = array_filter(array_map('intval', $ids));

        if ($ids !== []) {
            $this->whereIn('id', $ids)->update(null, ['deleted_at' => null]);
        }
    }

    /**
     * Undo for markAllDone(): put a batch of ids back to pending.
     */
    public function unmarkMany(array $ids): void
    {
        $ids = array_filter(array_map('intval', $ids));

        if ($ids !== []) {
            $this->whereIn('id', $ids)->update(null, ['status' => 'pending']);
        }
    }

    /**
     * Deterministic color for a subject tag, derived from the subject text
     * itself so the same subject always renders the same color with no
     * configuration needed. Returns CSS custom-property-friendly "r,g,b".
     */
    public static function subjectColorRgb(string $subject): string
    {
        $palette = [
            '95,217,232',  // cyan
            '155,125,238', // violet
            '242,195,107', // gold
            '229,99,107',  // red
            '111,207,151', // green
            '242,153,74',  // orange
            '187,107,217', // purple
            '86,204,242',  // sky blue
        ];

        $hash = crc32(strtolower(trim($subject)));

        return $palette[$hash % count($palette)];
    }
}
