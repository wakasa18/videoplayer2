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
        return view('assignments/index', [
            'assignments' => $this->assignmentModel->getAllOrdered(),
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
            'status'      => 'pending',
        ]);

        return redirect()->to('/assignments')->with('success', 'Assignment added to the queue.');
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
     * Remove an assignment for good.
     */
    public function destroy(int $id): RedirectResponse
    {
        $assignment = $this->assignmentModel->find($id);

        if (! $assignment) {
            return redirect()->to('/assignments')->with('error', 'Assignment not found.');
        }

        $this->assignmentModel->delete($id);

        return redirect()->to('/assignments')->with('success', 'Assignment deleted.');
    }
}

