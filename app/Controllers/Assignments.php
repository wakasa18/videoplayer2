<?php

namespace App\Controllers;

use App\Models\AssignmentModel;
use CodeIgniter\HTTP\RedirectResponse;

class Assignments extends BaseController
{
    protected $helpers = ['url', 'form'];

    protected AssignmentModel $assignmentModel;

    public function __construct()
    {
        $this->assignmentModel = new AssignmentModel();
    }

    /**
     * Task queue + new-assignment form.
     */
    public function index(): string
    {
        $assignments = $this->assignmentModel->getAllOrdered();

        $counts = ['pending' => 0, 'overdue' => 0, 'done' => 0];
        foreach ($assignments as $a) {
            if ($a['status'] === 'done') {
                $counts['done']++;
            } else {
                $counts['pending']++;
                if (AssignmentModel::isOverdue($a)) {
                    $counts['overdue']++;
                }
            }
        }

        return view('assignments/index', [
            'assignments' => $assignments,
            'counts'      => $counts,
        ]);
    }

    /**
     * Log a new assignment.
     */
    public function store(): RedirectResponse
    {
        $title       = trim((string) $this->request->getPost('title'));
        $description = trim((string) $this->request->getPost('description'));
        $dueDate     = trim((string) $this->request->getPost('due_date'));
        $subject     = trim((string) $this->request->getPost('subject'));
        $priority    = AssignmentModel::normalizePriority($this->request->getPost('priority'));

        if ($title === '') {
            return redirect()->to('/assignments')->with('error', 'Give the assignment a title first.');
        }

        if (mb_strlen($title) > 255) {
            return redirect()->to('/assignments')->with('error', 'That title is too long.');
        }

        $this->assignmentModel->insert([
            'title'       => $title,
            'description' => $description !== '' ? $description : null,
            'due_date'    => $dueDate !== '' ? $dueDate : null,
            'subject'     => $subject !== '' ? $subject : null,
            'priority'    => $priority,
            'status'      => 'pending',
        ]);

        return redirect()->to('/assignments')->with('success', 'Assignment added to the queue.');
    }

    /**
     * Edit an existing assignment's details.
     */
    public function update(int $id): RedirectResponse
    {
        $assignment = $this->assignmentModel->find($id);

        if (! $assignment) {
            return redirect()->to('/assignments')->with('error', 'Assignment not found.');
        }

        $title       = trim((string) $this->request->getPost('title'));
        $description = trim((string) $this->request->getPost('description'));
        $dueDate     = trim((string) $this->request->getPost('due_date'));
        $subject     = trim((string) $this->request->getPost('subject'));
        $priority    = AssignmentModel::normalizePriority($this->request->getPost('priority'));

        if ($title === '') {
            return redirect()->to('/assignments')->with('error', 'Give the assignment a title first.');
        }

        if (mb_strlen($title) > 255) {
            return redirect()->to('/assignments')->with('error', 'That title is too long.');
        }

        $this->assignmentModel->update($id, [
            'title'       => $title,
            'description' => $description !== '' ? $description : null,
            'due_date'    => $dueDate !== '' ? $dueDate : null,
            'subject'     => $subject !== '' ? $subject : null,
            'priority'    => $priority,
        ]);

        return redirect()->to('/assignments')->with('success', 'Assignment updated.');
    }

    /**
     * Flip an assignment between pending and done.
     */
    public function toggle(int $id): RedirectResponse
    {
        if (! $this->assignmentModel->toggleStatus($id)) {
            return redirect()->to('/assignments')->with('error', 'Assignment not found.');
        }

        return redirect()->to('/assignments');
    }

    /**
     * Soft-delete an assignment (recoverable via the Undo link).
     */
    public function destroy(int $id): RedirectResponse
    {
        $assignment = $this->assignmentModel->find($id);

        if (! $assignment) {
            return redirect()->to('/assignments')->with('error', 'Assignment not found.');
        }

        $this->assignmentModel->softDelete($id);

        return redirect()->to('/assignments')
            ->with('success', 'Assignment deleted.')
            ->with('undo_id', $id);
    }

    /**
     * Undo a delete.
     */
    public function restore(int $id): RedirectResponse
    {
        $this->assignmentModel->restore($id);

        return redirect()->to('/assignments')->with('success', 'Assignment restored.');
    }
}
