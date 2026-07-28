<?php

namespace App\Models;

use CodeIgniter\Model;

class AssignmentModel extends Model
{
    protected $table            = 'assignments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'title',
        'description',
        'due_date',
        'status',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'title' => 'required|min_length[1]|max_length[255]',
    ];

    /**
     * Pending assignments first (soonest due date first, no-due-date last),
     * then done ones at the bottom. Sorted in PHP rather than SQL so the
     * ordering behaves identically regardless of DB driver (MySQL vs the
     * Postgres/Supabase connection this app actually runs on).
     */
    public function getAllOrdered(): array
    {
        $items = $this->orderBy('created_at', 'DESC')->findAll();

        usort($items, static function (array $a, array $b): int {
            if ($a['status'] !== $b['status']) {
                return $a['status'] === 'done' ? 1 : -1;
            }

            $aDate = $a['due_date'] ?: '9999-12-31';
            $bDate = $b['due_date'] ?: '9999-12-31';

            return strcmp($aDate, $bDate);
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
     * True if a pending assignment's due date has passed.
     */
    public static function isOverdue(array $assignment): bool
    {
        if ($assignment['status'] === 'done' || empty($assignment['due_date'])) {
            return false;
        }

        return strtotime((string) $assignment['due_date']) < strtotime(date('Y-m-d'));
    }
}
