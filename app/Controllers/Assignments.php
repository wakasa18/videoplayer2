<?php

namespace App\Controllers;

use App\Models\AssignmentModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

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
        $subjects = [];
        foreach ($assignments as $a) {
            if ($a['status'] === 'done') {
                $counts['done']++;
            } else {
                $counts['pending']++;
                if (AssignmentModel::isOverdue($a)) {
                    $counts['overdue']++;
                }
            }
            if (! empty($a['subject'])) {
                $subjects[$a['subject']] = true;
            }
        }
        $subjects = array_keys($subjects);
        sort($subjects, SORT_NATURAL | SORT_FLAG_CASE);

        return view('assignments/index', [
            'assignments' => $assignments,
            'counts'      => $counts,
            'subjects'    => $subjects,
        ]);
    }

    /**
     * Pull the common set of fields this controller accepts from a
     * store/update POST, normalizing/trimming each one. Shared by store()
     * and update() so both handle new fields identically.
     */
    private function fieldsFromRequest(): array
    {
        $title       = trim((string) $this->request->getPost('title'));
        $description = trim((string) $this->request->getPost('description'));
        $dueDate     = trim((string) $this->request->getPost('due_date'));
        $dueTime     = trim((string) $this->request->getPost('due_time'));
        $subject     = trim((string) $this->request->getPost('subject'));
        $linkUrl     = trim((string) $this->request->getPost('link_url'));
        $priority    = AssignmentModel::normalizePriority($this->request->getPost('priority'));
        $recurrence  = AssignmentModel::normalizeRecurrence($this->request->getPost('recurrence'));

        // A due_time without a due_date doesn't mean anything.
        if ($dueDate === '') {
            $dueTime = '';
        }

        // Quietly add a scheme so "example.com/portal" still works as a link.
        if ($linkUrl !== '' && ! preg_match('#^https?://#i', $linkUrl)) {
            $linkUrl = 'https://' . $linkUrl;
        }

        return [
            'title'       => $title,
            'description' => $description !== '' ? $description : null,
            'due_date'    => $dueDate !== '' ? $dueDate : null,
            'due_time'    => $dueTime !== '' ? $dueTime : null,
            'subject'     => $subject !== '' ? $subject : null,
            'link_url'    => $linkUrl !== '' ? $linkUrl : null,
            'priority'    => $priority,
            'recurrence'  => $recurrence,
        ];
    }

    /**
     * Log a new assignment.
     */
    public function store(): RedirectResponse
    {
        $fields = $this->fieldsFromRequest();

        if ($fields['title'] === '') {
            return redirect()->to('/assignments')->with('error', 'Give the assignment a title first.');
        }

        if (mb_strlen($fields['title']) > 255) {
            return redirect()->to('/assignments')->with('error', 'That title is too long.');
        }

        $fields['status'] = 'pending';

        $this->assignmentModel->insert($fields);

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

        $fields = $this->fieldsFromRequest();

        if ($fields['title'] === '') {
            return redirect()->to('/assignments')->with('error', 'Give the assignment a title first.');
        }

        if (mb_strlen($fields['title']) > 255) {
            return redirect()->to('/assignments')->with('error', 'That title is too long.');
        }

        // If the due date changed, this assignment should be eligible for a
        // fresh reminder rather than staying silenced by an old one.
        if ($fields['due_date'] !== $assignment['due_date']) {
            $fields['reminder_sent_at'] = null;
        }

        $this->assignmentModel->update($id, $fields);

        return redirect()->to('/assignments')->with('success', 'Assignment updated.');
    }

    /**
     * Flip an assignment between pending and done. Completing a recurring
     * assignment spins off its next occurrence automatically (see
     * AssignmentModel::toggleStatus()).
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

    /**
     * Add a timestamped line to an assignment's notes log.
     */
    public function addNote(int $id): RedirectResponse
    {
        $note = trim((string) $this->request->getPost('note'));

        if ($note === '') {
            return redirect()->to('/assignments')->with('error', 'Write something before adding a note.');
        }

        if (! $this->assignmentModel->addNote($id, mb_substr($note, 0, 500))) {
            return redirect()->to('/assignments')->with('error', 'Assignment not found.');
        }

        return redirect()->to('/assignments')->with('success', 'Note added.');
    }

    /**
     * Persist a drag-and-drop reorder from the "Manual" sort mode.
     */
    public function reorder(): ResponseInterface
    {
        $ids = explode(',', (string) $this->request->getPost('ids'));
        $this->assignmentModel->saveOrder($ids);

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Download everything currently in the queue (pending + done, not
     * soft-deleted) as a single JSON file — the only backup this data has
     * outside Supabase itself.
     */
    public function export(): ResponseInterface
    {
        $assignments = $this->assignmentModel->getAllOrdered();

        // Trim to the fields that are actually meaningful to the person
        // reading this file back — leave out internal bookkeeping like the
        // numeric id or the reminder-dedupe timestamp.
        $data = array_map(static fn (array $a): array => [
            'title'       => $a['title'],
            'description' => $a['description'],
            'due_date'    => $a['due_date'],
            'due_time'    => $a['due_time'],
            'status'      => $a['status'],
            'priority'    => $a['priority'],
            'subject'     => $a['subject'],
            'link_url'    => $a['link_url'],
            'recurrence'  => $a['recurrence'],
            'notes_log'   => $a['notes_log'],
            'created_at'  => $a['created_at'],
            'updated_at'  => $a['updated_at'],
        ], $assignments);

        $filename = 'assignments-export-' . date('Y-m-d') . '.json';
        $json     = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($json);
    }

    /**
     * Soft-delete every Done assignment at once.
     */
    public function clearCompleted(): RedirectResponse
    {
        $ids = $this->assignmentModel->clearCompleted();

        if ($ids === []) {
            return redirect()->to('/assignments')->with('error', 'Nothing marked done to clear.');
        }

        return redirect()->to('/assignments')
            ->with('success', count($ids) . ' assignment' . (count($ids) === 1 ? '' : 's') . ' cleared.')
            ->with('bulk_undo_type', 'restore')
            ->with('bulk_undo_ids', $ids);
    }

    /**
     * Mark every pending assignment as done at once.
     */
    public function markAllDone(): RedirectResponse
    {
        $ids = $this->assignmentModel->markAllDone();

        if ($ids === []) {
            return redirect()->to('/assignments')->with('error', 'Nothing pending to mark done.');
        }

        return redirect()->to('/assignments')
            ->with('success', count($ids) . ' assignment' . (count($ids) === 1 ? '' : 's') . ' marked done.')
            ->with('bulk_undo_type', 'unmark')
            ->with('bulk_undo_ids', $ids);
    }

    /**
     * Undo for either bulk action above, based on which type was stashed
     * in the flash data that produced the Undo link.
     */
    public function bulkUndo(): RedirectResponse
    {
        $type = (string) $this->request->getPost('type');
        $ids  = (array) $this->request->getPost('ids');

        if ($type === 'restore') {
            $this->assignmentModel->restoreMany($ids);
        } elseif ($type === 'unmark') {
            $this->assignmentModel->unmarkMany($ids);
        }

        return redirect()->to('/assignments')->with('success', 'Undone.');
    }

    /**
     * Push a due date out by one day without opening the full edit form.
     */
    public function snooze(int $id): RedirectResponse
    {
        $assignment = $this->assignmentModel->find($id);

        if (! $assignment || empty($assignment['due_date'])) {
            return redirect()->to('/assignments')->with('error', 'Assignment not found.');
        }

        $newDate = date('Y-m-d', strtotime((string) $assignment['due_date'] . ' +1 day'));

        $this->assignmentModel->update($id, [
            'due_date'         => $newDate,
            'reminder_sent_at' => null,
        ]);

        return redirect()->to('/assignments')->with('success', 'Pushed to ' . date('M j, Y', strtotime($newDate)) . '.');
    }

    /**
     * Bulk-add assignments from a JSON file in the same shape export()
     * produces (an array of objects with at least a title).
     */
    public function import(): RedirectResponse
    {
        $file = $this->request->getFile('import_file');

        if (! $file || ! $file->isValid()) {
            return redirect()->to('/assignments')->with('error', 'Choose a JSON file to import first.');
        }

        $contents = file_get_contents($file->getTempName());
        $rows     = json_decode((string) $contents, true);

        if (! is_array($rows)) {
            return redirect()->to('/assignments')->with('error', "That file isn't valid JSON.");
        }

        $rows = array_slice($rows, 0, 500); // sane upper bound for a personal import

        $imported = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            if (! is_array($row) || empty($row['title']) || ! is_string($row['title'])) {
                $skipped++;
                continue;
            }

            $status = ($row['status'] ?? 'pending') === 'done' ? 'done' : 'pending';

            $this->assignmentModel->insert([
                'title'       => mb_substr(trim($row['title']), 0, 255),
                'description' => ! empty($row['description']) ? (string) $row['description'] : null,
                'due_date'    => ! empty($row['due_date']) ? (string) $row['due_date'] : null,
                'due_time'    => ! empty($row['due_time']) ? (string) $row['due_time'] : null,
                'subject'     => ! empty($row['subject']) ? mb_substr((string) $row['subject'], 0, 100) : null,
                'link_url'    => ! empty($row['link_url']) ? mb_substr((string) $row['link_url'], 0, 500) : null,
                'recurrence'  => AssignmentModel::normalizeRecurrence($row['recurrence'] ?? null),
                'priority'    => AssignmentModel::normalizePriority($row['priority'] ?? null),
                'status'      => $status,
            ]);
            $imported++;
        }

        $message = "Imported {$imported} assignment" . ($imported === 1 ? '' : 's') . '.';
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} row" . ($skipped === 1 ? '' : 's') . ' without a valid title.';
        }

        return redirect()->to('/assignments')->with($skipped > 0 && $imported === 0 ? 'error' : 'success', $message);
    }
}
