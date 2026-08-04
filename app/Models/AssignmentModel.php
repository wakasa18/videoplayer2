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
        'status',
        'priority',
        'subject',
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

    public const PRIORITIES = ['low', 'medium', 'high'];

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
     * Flip an assignment between pending and done.
     */
    public function toggleStatus(int $id): bool
    {
        $assignment = $this->find($id);

        if (! $assignment) {
            return false;
        }

        $newStatus = $assignment['status'] === 'done' ? 'pending' : 'done';

        return $this->update($id, ['status' => $newStatus]);
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
     * True if a pending assignment's due date has passed.
     */
    public static function isOverdue(array $assignment): bool
    {
        if ($assignment['status'] === 'done' || empty($assignment['due_date'])) {
            return false;
        }

        return strtotime((string) $assignment['due_date']) < strtotime(date('Y-m-d'));
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
     * overdue. Used to power the site-wide "due soon" banner shown on
     * every page.
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
     * Human, urgency-aware due-date text: "Due today", "Due in 3 days",
     * "2 days overdue"... Done items just get a plain "Due <date>" since
     * urgency no longer applies. Returns null when there's no due date.
     */
    public static function relativeDueDate(array $assignment): ?string
    {
        if (empty($assignment['due_date'])) {
            return null;
        }

        $formatted = date('M j, Y', strtotime((string) $assignment['due_date']));

        if ($assignment['status'] === 'done') {
            return "Due {$formatted}";
        }

        $today = new DateTimeImmutable(date('Y-m-d'));
        $due   = new DateTimeImmutable(date('Y-m-d', strtotime((string) $assignment['due_date'])));
        $diff  = (int) $today->diff($due)->format('%r%a');

        return match (true) {
            $diff === 0  => 'Due today',
            $diff === 1  => 'Due tomorrow',
            $diff > 1    => "Due in {$diff} days",
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
