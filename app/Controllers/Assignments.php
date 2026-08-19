<?php

namespace App\Controllers;

use App\Models\AssignmentFileLinkModel;
use App\Models\AssignmentModel;
use App\Models\AssignmentNoteModel;
use App\Models\AssignmentSubjectModel;
use App\Models\AssignmentSubtaskModel;
use App\Models\AssignmentTemplateModel;
use App\Models\ImportantFileModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class Assignments extends BaseController
{
    protected $helpers = ['url', 'form'];

    private AssignmentModel $assignments;
    private AssignmentSubtaskModel $subtasks;
    private AssignmentNoteModel $notes;
    private AssignmentSubjectModel $subjects;
    private AssignmentTemplateModel $templates;
    private AssignmentFileLinkModel $fileLinks;

    public function __construct()
    {
        $this->assignments = new AssignmentModel();
        $this->subtasks = new AssignmentSubtaskModel();
        $this->notes = new AssignmentNoteModel();
        $this->subjects = new AssignmentSubjectModel();
        $this->templates = new AssignmentTemplateModel();
        $this->fileLinks = new AssignmentFileLinkModel();
    }

    public function index(): string
    {
        $filters = $this->filtersFromRequest();
        $view = in_array($this->request->getGet('view'), ['list', 'board', 'calendar'], true)
            ? (string) $this->request->getGet('view') : 'list';
        $perPage = max(10, min((int) ($this->request->getGet('per_page') ?: 25), 100));

        $items = $view === 'board'
            ? $this->assignments->getBoardItems($filters)
            : ($view === 'calendar' ? [] : $this->assignments->getFilteredPage($filters, $perPage));
        $items = $this->hydrate($items);

        $month = preg_match('/^\d{4}-\d{2}$/', (string) $this->request->getGet('month'))
            ? (string) $this->request->getGet('month') : date('Y-m');
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        $calendarItems = $view === 'calendar'
            ? $this->hydrate($this->assignments->getCalendarItems($monthStart, $monthEnd, $filters)) : [];

        $importantFiles = (new ImportantFileModel())
            ->select('id,title,original_filename,file_extension,file_size')
            ->where('status', 'active')
            ->orderBy('title', 'ASC')->findAll(250);

        return view('assignments/index', [
            'assignments' => $items,
            'calendarItems' => $calendarItems,
            'calendarMonth' => $month,
            'filters' => $filters,
            'viewMode' => $view,
            'perPage' => $perPage,
            'pager' => $view === 'list' ? $this->assignments->pager : null,
            'counts' => $this->assignments->getSummaryCounts(),
            'analytics' => $this->safeAnalytics(),
            'subjects' => $this->subjects->active(),
            'templates' => $this->templates->orderBy('name', 'ASC')->findAll(),
            'importantFiles' => $importantFiles,
        ]);
    }

    public function recycle(): string
    {
        $perPage = max(10, min((int) ($this->request->getGet('per_page') ?: 25), 100));
        return view('assignments/recycle', [
            'items' => $this->assignments->getRecyclePage($perPage),
            'pager' => $this->assignments->pager,
            'mode' => 'recycle',
            'count' => $this->assignments->getSummaryCounts()['recycle'],
        ]);
    }

    public function archivePage(): string
    {
        $perPage = max(10, min((int) ($this->request->getGet('per_page') ?: 25), 100));
        return view('assignments/recycle', [
            'items' => $this->assignments->getArchivedPage($perPage),
            'pager' => $this->assignments->pager,
            'mode' => 'archive',
            'count' => $this->assignments->getSummaryCounts()['archive'],
        ]);
    }

    public function calendarFeed(): ResponseInterface
    {
        $start = $this->validDate((string) $this->request->getGet('start')) ?: date('Y-m-01');
        $end = $this->validDate((string) $this->request->getGet('end')) ?: date('Y-m-t');
        $rows = $this->assignments->getCalendarItems($start, $end, $this->filtersFromRequest());
        $events = array_map(static fn (array $a): array => [
            'id' => (int) $a['id'],
            'title' => $a['title'],
            'date' => $a['due_date'],
            'time' => $a['due_time'],
            'status' => AssignmentModel::normalizeStatus($a['status']),
            'priority' => $a['priority'],
            'subject' => $a['subject_name'] ?: $a['subject'],
            'color' => $a['subject_color'] ?: null,
        ], $rows);
        return $this->json(['ok' => true, 'events' => $events]);
    }

    public function store(): ResponseInterface|RedirectResponse
    {
        [$fields, $error] = $this->fieldsFromRequest();
        if ($error) {
            return $this->fail($error);
        }
        $fields['status'] = AssignmentModel::normalizeStatus($this->request->getPost('status'));
        $id = $this->assignments->insert($fields, true);
        if (! $id) {
            return $this->fail('The assignment could not be added.');
        }
        $this->saveInitialExtras((int) $id);
        return $this->success('Assignment added.', ['assignment' => $this->payload((int) $id)]);
    }

    public function update(int $id): ResponseInterface|RedirectResponse
    {
        $existing = $this->assignments->find($id);
        if (! $existing) {
            return $this->fail('Assignment not found.', 404);
        }
        [$fields, $error] = $this->fieldsFromRequest();
        if ($error) {
            return $this->fail($error);
        }
        $newStatus = AssignmentModel::normalizeStatus($this->request->getPost('status') ?: $existing['status']);
        if ($fields['due_date'] !== $existing['due_date'] || $fields['due_time'] !== $existing['due_time']
            || $fields['priority'] !== $existing['priority'] || $newStatus !== AssignmentModel::normalizeStatus($existing['status'])) {
            $fields['reminder_sent_at'] = null;
        }
        $this->assignments->update($id, $fields);
        if ($newStatus !== AssignmentModel::normalizeStatus($existing['status'])) {
            $this->assignments->setStatus($id, $newStatus);
        }
        return $this->success('Assignment updated.', ['assignment' => $this->payload($id)]);
    }

    public function status(int $id): ResponseInterface|RedirectResponse
    {
        $status = AssignmentModel::normalizeStatus((string) $this->request->getPost('status'));
        if (! $this->assignments->setStatus($id, $status)) {
            return $this->fail('Assignment not found.', 404);
        }
        return $this->success('Status changed.', ['assignment' => $this->payload($id)]);
    }

    public function toggle(int $id): ResponseInterface|RedirectResponse
    {
        if (! $this->assignments->toggleStatus($id)) {
            return $this->fail('Assignment not found.', 404);
        }
        return $this->success('Assignment updated.', ['assignment' => $this->payload($id)]);
    }

    public function duplicate(int $id): ResponseInterface|RedirectResponse
    {
        $newId = $this->assignments->duplicateAssignment($id);
        if (! $newId) {
            return $this->fail('Assignment not found.', 404);
        }
        // Duplicate subtasks without carrying their completion state.
        foreach ($this->subtasks->where('assignment_id', $id)->orderBy('sort_order', 'ASC')->findAll() as $subtask) {
            $this->subtasks->insert(['assignment_id' => $newId, 'title' => $subtask['title'], 'is_done' => false, 'sort_order' => $subtask['sort_order']]);
        }
        return $this->success('Assignment duplicated.', ['assignment' => $this->payload($newId)]);
    }

    public function destroy(int $id): ResponseInterface|RedirectResponse
    {
        if (! $this->assignments->softDelete($id)) {
            return $this->fail('Assignment not found.', 404);
        }
        return $this->success('Moved to Recycle Bin.', ['removed_id' => $id, 'undo' => ['action' => base_url("assignments/{$id}/restore")]]);
    }

    public function archive(int $id): ResponseInterface|RedirectResponse
    {
        if (! $this->assignments->archive($id)) {
            return $this->fail('Assignment not found.', 404);
        }
        return $this->success('Assignment archived.', ['removed_id' => $id]);
    }

    public function restore(int $id): ResponseInterface|RedirectResponse
    {
        if (! $this->assignments->restore($id)) {
            return $this->fail('Assignment not found.', 404);
        }
        return $this->success('Assignment restored.');
    }

    public function unarchive(int $id): ResponseInterface|RedirectResponse
    {
        if (! $this->assignments->unarchive($id)) {
            return $this->fail('Assignment not found.', 404);
        }
        return $this->success('Assignment returned to the active queue.');
    }

    public function purge(int $id): ResponseInterface|RedirectResponse
    {
        if (! $this->assignments->permanentlyDelete($id)) {
            return $this->fail('Assignment not found.', 404);
        }
        return $this->success('Assignment permanently deleted.');
    }

    public function addNote(int $id): ResponseInterface|RedirectResponse
    {
        if (! $this->assignments->find($id)) {
            return $this->fail('Assignment not found.', 404);
        }
        $content = trim((string) $this->request->getPost('content'));
        if ($content === '' || mb_strlen($content) > 2000) {
            return $this->fail('Write a note of up to 2,000 characters.');
        }
        $noteId = $this->notes->insert(['assignment_id' => $id, 'content' => $content, 'is_pinned' => false], true);
        return $this->success('Note added.', ['note' => $this->notes->find($noteId)]);
    }

    public function updateNote(int $id, int $noteId): ResponseInterface|RedirectResponse
    {
        $note = $this->notes->where('assignment_id', $id)->find($noteId);
        if (! $note) {
            return $this->fail('Note not found.', 404);
        }
        $content = trim((string) $this->request->getPost('content'));
        if ($content === '' || mb_strlen($content) > 2000) {
            return $this->fail('Write a note of up to 2,000 characters.');
        }
        $this->notes->update($noteId, ['content' => $content]);
        return $this->success('Note updated.', ['note' => $this->notes->find($noteId)]);
    }

    public function pinNote(int $id, int $noteId): ResponseInterface|RedirectResponse
    {
        $note = $this->notes->where('assignment_id', $id)->find($noteId);
        if (! $note) {
            return $this->fail('Note not found.', 404);
        }
        $this->notes->update($noteId, ['is_pinned' => ! (bool) $note['is_pinned']]);
        return $this->success('Note updated.', ['note' => $this->notes->find($noteId)]);
    }

    public function deleteNote(int $id, int $noteId): ResponseInterface|RedirectResponse
    {
        if (! $this->notes->where('assignment_id', $id)->find($noteId)) {
            return $this->fail('Note not found.', 404);
        }
        $this->notes->delete($noteId);
        return $this->success('Note deleted.', ['removed_note_id' => $noteId]);
    }

    public function addSubtask(int $id): ResponseInterface|RedirectResponse
    {
        if (! $this->assignments->find($id)) {
            return $this->fail('Assignment not found.', 404);
        }
        $title = trim((string) $this->request->getPost('title'));
        if ($title === '' || mb_strlen($title) > 255) {
            return $this->fail('Enter a subtask title of up to 255 characters.');
        }
        $max = $this->subtasks->selectMax('sort_order')->where('assignment_id', $id)->first();
        $subtaskId = $this->subtasks->insert(['assignment_id' => $id, 'title' => $title, 'is_done' => false, 'sort_order' => ((int) ($max['sort_order'] ?? 0)) + 1], true);
        return $this->success('Subtask added.', ['subtask' => $this->subtasks->find($subtaskId)]);
    }

    public function updateSubtask(int $id, int $subtaskId): ResponseInterface|RedirectResponse
    {
        $subtask = $this->subtasks->where('assignment_id', $id)->find($subtaskId);
        if (! $subtask) {
            return $this->fail('Subtask not found.', 404);
        }
        $title = trim((string) $this->request->getPost('title'));
        if ($title === '' || mb_strlen($title) > 255) {
            return $this->fail('Enter a subtask title of up to 255 characters.');
        }
        $this->subtasks->update($subtaskId, ['title' => $title]);
        return $this->success('Subtask updated.', ['subtask' => $this->subtasks->find($subtaskId)]);
    }

    public function toggleSubtask(int $id, int $subtaskId): ResponseInterface|RedirectResponse
    {
        $subtask = $this->subtasks->where('assignment_id', $id)->find($subtaskId);
        if (! $subtask) {
            return $this->fail('Subtask not found.', 404);
        }
        $this->subtasks->update($subtaskId, ['is_done' => ! (bool) $subtask['is_done']]);
        return $this->success('Subtask updated.', ['subtask' => $this->subtasks->find($subtaskId), 'progress' => $this->subtaskProgress($id)]);
    }

    public function deleteSubtask(int $id, int $subtaskId): ResponseInterface|RedirectResponse
    {
        if (! $this->subtasks->where('assignment_id', $id)->find($subtaskId)) {
            return $this->fail('Subtask not found.', 404);
        }
        $this->subtasks->delete($subtaskId);
        return $this->success('Subtask deleted.', ['removed_subtask_id' => $subtaskId, 'progress' => $this->subtaskProgress($id)]);
    }

    public function attachFile(int $id): ResponseInterface|RedirectResponse
    {
        if (! $this->assignments->find($id)) {
            return $this->fail('Assignment not found.', 404);
        }
        $fileId = (int) $this->request->getPost('important_file_id');
        $file = (new ImportantFileModel())->where('status', 'active')->find($fileId);
        if (! $file) {
            return $this->fail('File not found.');
        }
        $existing = $this->fileLinks->where('assignment_id', $id)->where('important_file_id', $fileId)->first();
        if (! $existing) {
            $this->fileLinks->insert(['assignment_id' => $id, 'important_file_id' => $fileId, 'created_at' => date('Y-m-d H:i:s')]);
        }
        return $this->success('File attached.', ['attachments' => $this->fileLinks->forAssignments([$id])[$id] ?? []]);
    }

    public function detachFile(int $id, int $linkId): ResponseInterface|RedirectResponse
    {
        if (! $this->fileLinks->where('assignment_id', $id)->find($linkId)) {
            return $this->fail('Attachment not found.', 404);
        }
        $this->fileLinks->delete($linkId);
        return $this->success('Attachment removed.', ['removed_attachment_id' => $linkId]);
    }

    public function snooze(int $id): ResponseInterface|RedirectResponse
    {
        $assignment = $this->assignments->find($id);
        if (! $assignment) {
            return $this->fail('Assignment not found.', 404);
        }
        $choice = (string) $this->request->getPost('choice');
        $date = $assignment['due_date'] ?: date('Y-m-d');
        $time = $assignment['due_time'];
        switch ($choice) {
            case 'later_today':
                $date = date('Y-m-d');
                $time = date('H:i', strtotime('+3 hours'));
                break;
            case 'tomorrow': $date = date('Y-m-d', strtotime('+1 day')); break;
            case 'next_monday': $date = date('Y-m-d', strtotime('next monday')); break;
            case '3days': $date = date('Y-m-d', strtotime('+3 days')); break;
            case 'week': $date = date('Y-m-d', strtotime('+1 week')); break;
            case 'custom':
                $date = $this->validDate((string) $this->request->getPost('custom_date')) ?: $date;
                $time = $this->validTime((string) $this->request->getPost('custom_time'));
                break;
            default:
                $date = date('Y-m-d', strtotime($date . ' +1 day'));
        }
        $this->assignments->update($id, ['due_date' => $date, 'due_time' => $time ?: null, 'reminder_sent_at' => null, 'custom_reminder_at' => null]);
        return $this->success('Deadline moved.', ['assignment' => $this->payload($id)]);
    }

    public function updateDeadline(int $id): ResponseInterface|RedirectResponse
    {
        $assignment = $this->assignments->find($id);
        if (! $assignment) {
            return $this->fail('Assignment not found.', 404);
        }
        $dateRaw = trim((string) $this->request->getPost('due_date'));
        $timeRaw = trim((string) $this->request->getPost('due_time'));
        $date = $dateRaw === '' ? null : $this->validDate($dateRaw);
        $time = $timeRaw === '' ? null : $this->validTime($timeRaw);
        if ($dateRaw !== '' && ! $date) {
            return $this->fail('Enter a valid due date.');
        }
        if ($timeRaw !== '' && ! $time) {
            return $this->fail('Enter a valid due time.');
        }
        if (! $date) {
            $time = null;
        }
        $this->assignments->update($id, [
            'due_date' => $date,
            'due_time' => $time,
            'reminder_sent_at' => null,
            'custom_reminder_at' => null,
        ]);
        return $this->success('Deadline updated.', ['assignment' => $this->payload($id)]);
    }

    public function reorder(): ResponseInterface
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $this->request->getPost('ids'))));
        $this->assignments->saveOrder($ids);
        return $this->json(['ok' => true, 'message' => 'Order saved.']);
    }

    public function bulkAction(): ResponseInterface|RedirectResponse
    {
        $ids = array_values(array_filter(array_map('intval', (array) $this->request->getPost('ids'))));
        $action = (string) $this->request->getPost('action');
        if (! $ids) {
            return $this->fail('Select at least one assignment.');
        }
        foreach ($ids as $id) {
            match ($action) {
                'done' => $this->assignments->setStatus($id, 'done'),
                'archive' => $this->assignments->archive($id),
                'delete' => $this->assignments->softDelete($id),
                'restore' => $this->assignments->restore($id),
                default => null,
            };
        }
        return $this->success(count($ids) . ' assignments updated.', ['ids' => $ids, 'action' => $action]);
    }

    public function markAllDone(): ResponseInterface|RedirectResponse
    {
        $ids = $this->assignments->markAllDone();
        return $ids ? $this->success(count($ids) . ' assignments marked done.') : $this->fail('Nothing active to mark done.');
    }

    public function clearCompleted(): ResponseInterface|RedirectResponse
    {
        $ids = $this->assignments->clearCompleted();
        return $ids ? $this->success(count($ids) . ' completed assignments archived.') : $this->fail('Nothing completed to archive.');
    }

    public function bulkUndo(): ResponseInterface|RedirectResponse
    {
        $type = (string) $this->request->getPost('type');
        $ids = (array) $this->request->getPost('ids');
        if ($type === 'restore') {
            $this->assignments->restoreMany($ids);
        } elseif ($type === 'unmark') {
            $this->assignments->unmarkMany($ids);
        }
        return $this->success('Undone.');
    }

    public function export(): ResponseInterface
    {
        $rows = $this->assignments->where('deleted_at', null)->orderBy('created_at', 'DESC')->findAll(10000);
        $rows = $this->hydrate($rows);
        $data = array_map(static function (array $a): array {
            unset($a['upload_token_hash'], $a['reminder_sent_at']);
            return $a;
        }, $rows);
        return $this->response
            ->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="assignments-export-' . date('Y-m-d') . '.json"')
            ->setBody((string) json_encode(['version' => 2, 'timezone' => 'Asia/Manila', 'assignments' => $data], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function importPreview(): ResponseInterface
    {
        $file = $this->request->getFile('import_file');
        $allowedMimes = ['application/json', 'text/json', 'text/plain', 'application/octet-stream'];
        $extension = strtolower((string) ($file?->getClientExtension() ?? ''));
        if (! $file || ! $file->isValid() || $file->getSize() > 2 * 1024 * 1024
            || $extension !== 'json' || ! in_array(strtolower((string) $file->getMimeType()), $allowedMimes, true)) {
            return $this->json(['ok' => false, 'message' => 'Choose a valid JSON file smaller than 2 MB.'], 422);
        }
        $decoded = json_decode((string) file_get_contents($file->getTempName()), true);
        $rows = is_array($decoded) && isset($decoded['assignments']) ? $decoded['assignments'] : $decoded;
        if (! is_array($rows)) {
            return $this->json(['ok' => false, 'message' => 'The selected file is not a valid assignments export.'], 422);
        }
        $rows = array_slice($rows, 0, 500);
        $existingRows = $this->assignments->select('title,due_date')->where('deleted_at', null)->findAll(10000);
        $existing = [];
        foreach ($existingRows as $r) {
            $existing[strtolower(trim($r['title'])) . '|' . ($r['due_date'] ?: '')] = true;
        }
        $valid = []; $errors = []; $duplicates = 0;
        foreach ($rows as $index => $row) {
            [$clean, $error] = $this->validateImportRow($row, $index + 1);
            if ($error) { $errors[] = $error; continue; }
            $key = strtolower($clean['title']) . '|' . ($clean['due_date'] ?: '');
            if (isset($existing[$key])) { $duplicates++; continue; }
            $existing[$key] = true;
            $valid[] = $clean;
        }
        $token = bin2hex(random_bytes(20));
        session()->set('assignment_import_' . $token, ['rows' => $valid, 'expires' => time() + 1800]);
        return $this->json(['ok' => true, 'token' => $token, 'valid_count' => count($valid), 'duplicate_count' => $duplicates, 'error_count' => count($errors), 'preview' => array_slice($valid, 0, 30), 'errors' => array_slice($errors, 0, 30)]);
    }

    public function importConfirm(): ResponseInterface|RedirectResponse
    {
        $token = preg_replace('/[^a-f0-9]/', '', (string) $this->request->getPost('token'));
        $batch = session()->get('assignment_import_' . $token);
        if (! is_array($batch) || ($batch['expires'] ?? 0) < time()) {
            return $this->fail('The import preview expired. Preview the file again.');
        }
        $db = db_connect(); $db->transStart(); $count = 0;
        foreach ($batch['rows'] as $row) {
            $subtasks = $row['subtasks'] ?? []; $notes = $row['notes'] ?? []; $attachments = $row['attachments'] ?? [];
            unset($row['subtasks'], $row['notes'], $row['attachments']);
            $id = $this->assignments->insert($row, true);
            if (! $id) { continue; }
            foreach ($subtasks as $i => $subtask) {
                $title = trim((string) ($subtask['title'] ?? ''));
                if ($title !== '') $this->subtasks->insert(['assignment_id' => $id, 'title' => mb_substr($title,0,255), 'is_done' => (bool)($subtask['is_done'] ?? false), 'sort_order' => $i]);
            }
            foreach ($notes as $note) {
                $content = trim((string) ($note['content'] ?? ''));
                if ($content !== '') $this->notes->insert(['assignment_id' => $id, 'content' => mb_substr($content,0,2000), 'is_pinned' => (bool)($note['is_pinned'] ?? false)]);
            }
            foreach ($attachments as $attachment) {
                $fileId = (int) ($attachment['important_file_id'] ?? 0);
                if ($fileId > 0 && (new ImportantFileModel())->where('status', 'active')->find($fileId)) {
                    $this->fileLinks->insert(['assignment_id' => $id, 'important_file_id' => $fileId, 'created_at' => date('Y-m-d H:i:s')]);
                }
            }
            $count++;
        }
        $db->transComplete(); session()->remove('assignment_import_' . $token);
        if (! $db->transStatus()) return $this->fail('The import could not be completed.');
        return $this->success("Imported {$count} assignment" . ($count === 1 ? '' : 's') . '.');
    }

    public function saveSubject(): ResponseInterface|RedirectResponse
    {
        $id = (int) $this->request->getPost('id');
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '' || mb_strlen($name) > 100) return $this->fail('Enter a subject name of up to 100 characters.');
        $duplicate = db_connect()->table('assignment_subjects')
            ->where('LOWER(name) =', strtolower($name), false)
            ->get()->getRowArray();
        if ($duplicate && (int) $duplicate['id'] !== $id) {
            return $this->fail('A subject with that name already exists.');
        }
        $fields = [
            'name' => $name,
            'code' => $this->nullableText('code', 30),
            'instructor' => $this->nullableText('instructor', 100),
            'color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string)$this->request->getPost('color')) ? $this->request->getPost('color') : '#42E9FF',
            'schedule' => $this->nullableText('schedule', 255),
            'semester' => $this->nullableText('semester', 100),
            'is_archived' => false,
        ];
        if ($id > 0) $this->subjects->update($id, $fields); else $id = (int)$this->subjects->insert($fields, true);
        return $this->success('Subject saved.', ['subject' => $this->subjects->find($id)]);
    }

    public function archiveSubject(int $id): ResponseInterface|RedirectResponse
    {
        if (! $this->subjects->find($id)) return $this->fail('Subject not found.', 404);
        $this->subjects->update($id, ['is_archived' => true]);
        return $this->success('Subject archived.');
    }

    public function saveTemplate(): ResponseInterface|RedirectResponse
    {
        $name = trim((string) $this->request->getPost('name'));
        $title = trim((string) $this->request->getPost('title'));
        if ($name === '' || $title === '') return $this->fail('Template name and assignment title are required.');
        $id = (int) $this->templates->insert([
            'name' => mb_substr($name,0,100), 'title' => mb_substr($title,0,255),
            'description' => $this->nullableText('description',5000),
            'priority' => AssignmentModel::normalizePriority($this->request->getPost('priority')),
            'recurrence' => AssignmentModel::normalizeRecurrence($this->request->getPost('recurrence')),
            'subject_id' => ((int)$this->request->getPost('subject_id')) ?: null,
            'due_time' => $this->validTime((string)$this->request->getPost('due_time')),
            'reminder_minutes_before' => max(0,min((int)$this->request->getPost('reminder_minutes_before'),43200)),
            'link_url' => $this->safeUrl((string)$this->request->getPost('link_url')),
        ], true);
        return $this->success('Template saved.', ['template' => $this->templates->find($id)]);
    }

    public function deleteTemplate(int $id): ResponseInterface|RedirectResponse
    {
        if (! $this->templates->find($id)) return $this->fail('Template not found.',404);
        $this->templates->delete($id);
        return $this->success('Template deleted.');
    }

    private function fieldsFromRequest(): array
    {
        $title = trim((string) $this->request->getPost('title'));
        if ($title === '' || mb_strlen($title) > 255) return [[], 'Enter a title of up to 255 characters.'];
        $description = trim((string) $this->request->getPost('description'));
        if (mb_strlen($description) > 5000) return [[], 'Description must be 5,000 characters or fewer.'];
        $dueDateRaw = trim((string) $this->request->getPost('due_date'));
        $dueDate = $dueDateRaw === '' ? null : $this->validDate($dueDateRaw);
        if ($dueDateRaw !== '' && ! $dueDate) return [[], 'Enter a valid due date.'];
        $dueTimeRaw = trim((string) $this->request->getPost('due_time'));
        $dueTime = $dueTimeRaw === '' ? null : $this->validTime($dueTimeRaw);
        if ($dueTimeRaw !== '' && ! $dueTime) return [[], 'Enter a valid due time.'];
        if (! $dueDate) $dueTime = null;
        $urlRaw = trim((string) $this->request->getPost('link_url'));
        $url = $this->safeUrl($urlRaw);
        if ($urlRaw !== '' && ! $url) return [[], 'Links must begin with http:// or https://.'];
        $subjectId = (int) $this->request->getPost('subject_id');
        $subject = $subjectId > 0 ? $this->subjects->find($subjectId) : null;
        $customReminderRaw = trim((string) $this->request->getPost('custom_reminder_at'));
        $customReminder = null;
        if ($customReminderRaw !== '') {
            $stamp = strtotime($customReminderRaw);
            if (! $stamp) return [[], 'Enter a valid custom reminder time.'];
            $customReminder = date('Y-m-d H:i:s', $stamp);
        }
        return [[
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'due_date' => $dueDate,
            'due_time' => $dueTime,
            'priority' => AssignmentModel::normalizePriority($this->request->getPost('priority')),
            'subject_id' => $subject ? $subjectId : null,
            'subject' => $subject ? $subject['name'] : $this->nullableText('subject',100),
            'link_url' => $url,
            'recurrence' => AssignmentModel::normalizeRecurrence($this->request->getPost('recurrence')),
            'reminder_minutes_before' => max(0,min((int)($this->request->getPost('reminder_minutes_before') ?: 1440),43200)),
            'custom_reminder_at' => $customReminder,
            'template_id' => ((int)$this->request->getPost('template_id')) ?: null,
        ], null];
    }

    private function filtersFromRequest(): array
    {
        return [
            'q' => trim((string) $this->request->getGet('q')),
            'tab' => (string) ($this->request->getGet('tab') ?: 'all'),
            'status' => (string) $this->request->getGet('status'),
            'priority' => (string) $this->request->getGet('priority'),
            'subject_id' => (int) $this->request->getGet('subject_id'),
            'sort' => (string) ($this->request->getGet('sort') ?: 'due'),
        ];
    }

    private function hydrate(array $items): array
    {
        $ids = array_map('intval', array_column($items, 'id'));
        if (! $ids) return $items;
        $subtasks = $this->subtasks->forAssignments($ids);
        $notes = $this->notes->forAssignments($ids);
        $attachments = $this->fileLinks->forAssignments($ids);
        foreach ($items as &$a) {
            $id = (int) $a['id'];
            $a['status'] = AssignmentModel::normalizeStatus($a['status'] ?? null);
            $a['subtasks'] = $subtasks[$id] ?? [];
            $a['notes'] = $notes[$id] ?? [];
            $a['attachments'] = $attachments[$id] ?? [];
            $a['subtask_progress'] = $this->progressFromRows($a['subtasks']);
            $a['is_overdue'] = AssignmentModel::isOverdue($a);
            $a['relative_due'] = AssignmentModel::relativeDueDate($a);
        }
        unset($a);
        return $items;
    }

    private function payload(int $id): ?array
    {
        $row = $this->assignments->select('assignments.*, assignment_subjects.name AS subject_name, assignment_subjects.code AS subject_code, assignment_subjects.color AS subject_color')
            ->join('assignment_subjects','assignment_subjects.id=assignments.subject_id','left')->find($id);
        return $row ? $this->hydrate([$row])[0] : null;
    }

    private function subtaskProgress(int $assignmentId): array
    {
        return $this->progressFromRows($this->subtasks->where('assignment_id',$assignmentId)->findAll());
    }

    private function progressFromRows(array $rows): array
    {
        $total = count($rows); $done = count(array_filter($rows, static fn(array $r): bool => (bool)$r['is_done']));
        return ['total'=>$total,'done'=>$done,'percent'=>$total ? (int)round(($done/$total)*100) : 0];
    }

    private function saveInitialExtras(int $id): void
    {
        $subtasks = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)$this->request->getPost('subtasks_text')) ?: [])));
        foreach (array_slice($subtasks,0,50) as $i=>$title) {
            $this->subtasks->insert(['assignment_id'=>$id,'title'=>mb_substr($title,0,255),'is_done'=>false,'sort_order'=>$i]);
        }
    }

    private function validateImportRow(mixed $row, int $line): array
    {
        if (! is_array($row)) return [[], "Row {$line}: expected an object."];
        $title = trim((string)($row['title'] ?? ''));
        if ($title === '' || mb_strlen($title)>255) return [[], "Row {$line}: invalid title."];
        $due = !empty($row['due_date']) ? $this->validDate((string)$row['due_date']) : null;
        if (!empty($row['due_date']) && !$due) return [[], "Row {$line}: invalid due date."];
        $time = !empty($row['due_time']) ? $this->validTime((string)$row['due_time']) : null;
        if (!empty($row['due_time']) && !$time) return [[], "Row {$line}: invalid due time."];
        $url = $this->safeUrl((string)($row['link_url'] ?? ''));
        if (!empty($row['link_url']) && !$url) return [[], "Row {$line}: unsafe link URL."];
        return [[
            'title'=>mb_substr($title,0,255),
            'description'=>isset($row['description']) ? mb_substr((string)$row['description'],0,5000) : null,
            'due_date'=>$due,'due_time'=>$due ? $time : null,
            'status'=>AssignmentModel::normalizeStatus($row['status'] ?? null),
            'priority'=>AssignmentModel::normalizePriority($row['priority'] ?? null),
            'subject'=>isset($row['subject']) ? mb_substr(trim((string)$row['subject']),0,100) : null,
            'link_url'=>$url,'recurrence'=>AssignmentModel::normalizeRecurrence($row['recurrence'] ?? null),
            'reminder_minutes_before'=>max(0,min((int)($row['reminder_minutes_before'] ?? 1440),43200)),
            'subtasks'=>is_array($row['subtasks'] ?? null) ? array_slice($row['subtasks'],0,100) : [],
            'notes'=>is_array($row['notes'] ?? null) ? array_slice($row['notes'],0,100) : [],
            'attachments'=>is_array($row['attachments'] ?? null) ? array_slice($row['attachments'],0,100) : [],
        ], null];
    }

    private function safeAnalytics(): array
    {
        try { return $this->assignments->getAnalytics(); }
        catch (Throwable $e) { log_message('error','Assignment analytics failed: '.$e->getMessage()); return ['completed_week'=>0,'completed_month'=>0,'on_time_percent'=>0,'active_total'=>0,'average_delay_hours'=>0,'top_subject'=>'None','top_subject_count'=>0]; }
    }

    private function validDate(string $value): ?string
    {
        $dt = \DateTimeImmutable::createFromFormat('!Y-m-d',$value);
        return $dt && $dt->format('Y-m-d') === $value ? $value : null;
    }

    private function validTime(string $value): ?string
    {
        if (! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$value)) return null;
        return $value;
    }

    private function safeUrl(string $value): ?string
    {
        $value = trim($value); if ($value==='') return null;
        if (! preg_match('#^https?://#i',$value)) $value='https://'.$value;
        return filter_var($value,FILTER_VALIDATE_URL) && in_array(strtolower((string)parse_url($value,PHP_URL_SCHEME)),['http','https'],true) ? mb_substr($value,0,500) : null;
    }

    private function nullableText(string $field, int $max): ?string
    {
        $value=trim((string)$this->request->getPost($field));return $value!==''?mb_substr($value,0,$max):null;
    }

    private function wantsJson(): bool
    {
        return $this->request->isAJAX() || str_contains(strtolower($this->request->getHeaderLine('Accept')),'application/json');
    }

    private function json(array $payload, int $status=200): ResponseInterface
    {
        $payload['csrf']=['name'=>csrf_token(),'hash'=>csrf_hash()];
        return $this->response->setStatusCode($status)->setJSON($payload);
    }

    private function success(string $message, array $data=[]): ResponseInterface|RedirectResponse
    {
        if ($this->wantsJson()) return $this->json(array_merge(['ok'=>true,'message'=>$message],$data));
        return redirect()->to($this->returnUrl())->with('success',$message);
    }

    private function fail(string $message, int $status=422): ResponseInterface|RedirectResponse
    {
        if ($this->wantsJson()) return $this->json(['ok'=>false,'message'=>$message],$status);
        return redirect()->to($this->returnUrl())->with('error',$message);
    }

    private function returnUrl(): string
    {
        $return = (string)$this->request->getPost('return_to');
        return str_starts_with($return,'/') && !str_starts_with($return,'//') ? $return : '/assignments';
    }
}
