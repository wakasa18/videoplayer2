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
}
