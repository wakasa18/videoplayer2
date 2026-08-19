<?php
use App\Models\AssignmentModel;
$statusLabels=['to_do'=>'To Do','in_progress'=>'In Progress','blocked'=>'Blocked','submitted'=>'Submitted','done'=>'Done'];
$tabLabels=['all'=>'All','today'=>'Today','upcoming'=>'Upcoming','overdue'=>'Overdue','no_deadline'=>'No deadline','completed'=>'Completed'];
$queryBase=$_GET; unset($queryBase['page_assignments']);
function assignmentQuery(array $base,array $changes=[]): string { return base_url('assignments').'?'.http_build_query(array_filter(array_merge($base,$changes),static fn($v)=>$v!==''&&$v!==null)); }
function fmtBytes(int $bytes): string { $u=['B','KB','MB','GB'];$i=0;$v=$bytes;while($v>=1024&&$i<count($u)-1){$v/=1024;$i++;}return ($i?number_format($v,1):$v).' '.$u[$i]; }
$calendarMap=[];foreach($calendarItems as $item){$calendarMap[$item['due_date']][]=$item;}
$monthStart=new DateTimeImmutable($calendarMonth.'-01');$days=(int)$monthStart->format('t');$firstWeekday=(int)$monthStart->format('N');
$prevMonth=$monthStart->modify('-1 month')->format('Y-m');$nextMonth=$monthStart->modify('+1 month')->format('Y-m');
?>
<!DOCTYPE html><html lang="en"><head>
<title>Assignments · Damon's Archive</title>
<?= view('partials/theme_head') ?>
<meta name="csrf-name" content="<?= esc(csrf_token(),'attr') ?>"><meta name="csrf-hash" content="<?= esc(csrf_hash(),'attr') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/assignments.v3.css') ?>">
<?= view('partials/retro_theme') ?>
</head><body data-base-url="<?= esc(rtrim(base_url(),'/'),'attr') ?>">
<div class="twinkle-layer" id="twinkleLayer"></div>
<div class="wrap assignments-wrap">
  <?= view('partials/deadline_banner') ?>
  <a href="<?= base_url('others') ?>" class="nav-back">← Others</a>
  <header class="assignment-hero">
    <div><p class="eyebrow">Mission Control · Sector 22</p><h1>Assignments</h1><p class="hero-copy">Plan, track, attach files, and finish every academic mission from one command center.</p></div>
    <div class="hero-actions">
      <button class="arcade-button primary" type="button" data-open-assignment-modal>+ New assignment</button>
      <button class="arcade-button" type="button" data-open-import>Import</button>
      <a class="arcade-button" href="<?= base_url('assignments/export') ?>">Export</a>
    </div>
  </header>

  <?php if(session()->getFlashdata('success')): ?><div class="flash success" role="status"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
  <?php if(session()->getFlashdata('error')): ?><div class="flash error" role="alert"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

  <section class="mission-stats" aria-label="Assignment summary">
    <a href="<?= assignmentQuery($queryBase,['tab'=>'today']) ?>"><span>Due today</span><strong><?= (int)$counts['today'] ?></strong></a>
    <a href="<?= assignmentQuery($queryBase,['tab'=>'overdue']) ?>" class="danger-stat"><span>Overdue</span><strong><?= (int)$counts['overdue'] ?></strong></a>
    <a href="<?= assignmentQuery($queryBase,['tab'=>'upcoming']) ?>"><span>Next 7 days</span><strong><?= (int)$counts['upcoming'] ?></strong></a>
    <div><span>Completed this week</span><strong><?= (int)$analytics['completed_week'] ?></strong></div>
    <div><span>Completed this month</span><strong><?= (int)$analytics['completed_month'] ?></strong></div>
    <div><span>On-time rate</span><strong><?= (int)$analytics['on_time_percent'] ?>%</strong></div>
  </section>
  <div class="analytics-ribbon"><span><strong><?= (int)$analytics['active_total'] ?></strong> active missions</span><span><strong><?= esc($analytics['top_subject']) ?></strong> busiest subject (<?= (int)$analytics['top_subject_count'] ?>)</span><span><strong><?= (int)$analytics['average_delay_hours'] ?>h</strong> average late completion</span></div>

  <nav class="assignment-tabs" aria-label="Assignment views">
    <?php foreach($tabLabels as $key=>$label): ?><a class="<?= ($filters['tab']??'all')===$key?'active':'' ?>" href="<?= assignmentQuery($queryBase,['tab'=>$key]) ?>"><?= $label ?><span><?= (int)($counts[$key]??$counts['all']) ?></span></a><?php endforeach; ?>
  </nav>

  <section class="workspace-panel">
    <div class="workspace-head">
      <form class="filter-form" method="get" action="<?= base_url('assignments') ?>">
        <input type="hidden" name="tab" value="<?= esc($filters['tab'],'attr') ?>"><input type="hidden" name="view" value="<?= esc($viewMode,'attr') ?>">
        <label class="search-field"><span>Search</span><input type="search" name="q" value="<?= esc($filters['q'],'attr') ?>" placeholder="Title, description, subject, notes…"></label>
        <label><span>Status</span><select name="status"><option value="">All statuses</option><?php foreach($statusLabels as $v=>$l): ?><option value="<?= $v ?>" <?= $filters['status']===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></label>
        <label><span>Priority</span><select name="priority"><option value="">All priorities</option><?php foreach(AssignmentModel::PRIORITIES as $p): ?><option value="<?= $p ?>" <?= $filters['priority']===$p?'selected':'' ?>><?= ucfirst($p) ?></option><?php endforeach; ?></select></label>
        <label><span>Subject</span><select name="subject_id"><option value="0">All subjects</option><?php foreach($subjects as $s): ?><option value="<?= (int)$s['id'] ?>" <?= (int)$filters['subject_id']===(int)$s['id']?'selected':'' ?>><?= esc(($s['code']?$s['code'].' · ':'').$s['name']) ?></option><?php endforeach; ?></select></label>
        <label><span>Sort</span><select name="sort"><option value="due" <?= $filters['sort']==='due'?'selected':'' ?>>Due date</option><option value="priority" <?= $filters['sort']==='priority'?'selected':'' ?>>Priority</option><option value="newest" <?= $filters['sort']==='newest'?'selected':'' ?>>Newest</option><option value="oldest" <?= $filters['sort']==='oldest'?'selected':'' ?>>Oldest</option><option value="alpha" <?= $filters['sort']==='alpha'?'selected':'' ?>>A–Z</option><option value="subject" <?= $filters['sort']==='subject'?'selected':'' ?>>Subject</option><option value="manual" <?= $filters['sort']==='manual'?'selected':'' ?>>Manual</option></select></label>
        <button class="filter-submit" type="submit">Apply</button><a class="clear-filters" href="<?= base_url('assignments') ?>">Clear</a>
      </form>
      <div class="workspace-tools">
        <div class="view-switch" aria-label="Display mode">
          <a class="<?= $viewMode==='list'?'active':'' ?>" href="<?= assignmentQuery($queryBase,['view'=>'list']) ?>">☷ List</a>
          <a class="<?= $viewMode==='board'?'active':'' ?>" href="<?= assignmentQuery($queryBase,['view'=>'board','tab'=>'all']) ?>">▦ Board</a>
          <a class="<?= $viewMode==='calendar'?'active':'' ?>" href="<?= assignmentQuery($queryBase,['view'=>'calendar','tab'=>'all']) ?>">▣ Calendar</a>
        </div>
        <button type="button" class="tool-button" data-open-subjects>Subjects</button>
        <button type="button" class="tool-button" data-open-templates>Templates</button>
        <a class="tool-button" href="<?= base_url('assignments/archive') ?>">Archive <?= (int)$counts['archive'] ?></a>
        <a class="tool-button" href="<?= base_url('assignments/recycle') ?>">Recycle <?= (int)$counts['recycle'] ?></a>
        <button type="button" class="tool-button" data-global-action="mark-all-done">Mark all done</button>
        <button type="button" class="tool-button" data-global-action="clear-completed">Archive completed</button>
        <button type="button" class="tool-button" id="densityToggle">Density</button>
      </div>
    </div>

    <div class="bulk-bar" id="bulkBar" hidden><strong id="bulkCount">0 selected</strong><button data-bulk-action="done">Mark done</button><button data-bulk-action="archive">Archive</button><button class="danger" data-bulk-action="delete">Delete</button><button id="clearSelection">Clear</button></div>

    <?php if($viewMode==='list'): ?>
      <div class="list-heading"><span><?= (int)($pager?->getTotal('assignments') ?? count($assignments)) ?> results</span><label><input type="checkbox" id="selectPage"> Select page</label></div>
      <div class="assignment-list" id="assignmentList">
        <?php foreach($assignments as $a): ?><?= view('assignments/_card',['assignment'=>$a]) ?><?php endforeach; ?>
      </div>
      <?php if(!$assignments): ?><div class="empty-mission"><div>NO MISSIONS FOUND</div><p>Change the filters or log a new assignment.</p><button class="arcade-button primary" data-open-assignment-modal>+ New assignment</button></div><?php endif; ?>
      <?php if($pager): ?><div class="assignment-pagination"><?= $pager->links('assignments','default_full') ?></div><?php endif; ?>
    <?php elseif($viewMode==='board'): ?>
      <div class="kanban-board" id="kanbanBoard">
        <?php foreach($statusLabels as $status=>$label): ?><section class="kanban-column" data-board-status="<?= $status ?>"><header><span><?= $label ?></span><strong><?= count(array_filter($assignments,fn($a)=>$a['status']===$status)) ?></strong></header><div class="kanban-dropzone"><?php foreach($assignments as $a): if($a['status']!==$status)continue; ?><article class="kanban-card" draggable="true" data-assignment-id="<?= (int)$a['id'] ?>" data-open-details="<?= (int)$a['id'] ?>"><span class="priority-dot <?= esc($a['priority']) ?>"></span><h3><?= esc($a['title']) ?></h3><p><?= esc($a['relative_due'] ?: 'No deadline') ?></p><small><?= esc($a['subject_name'] ?: ($a['subject'] ?: 'General')) ?></small></article><?php endforeach; ?></div></section><?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="calendar-toolbar"><a href="<?= assignmentQuery($queryBase,['month'=>$prevMonth]) ?>">←</a><strong><?= esc($monthStart->format('F Y')) ?></strong><a href="<?= assignmentQuery($queryBase,['month'=>$nextMonth]) ?>">→</a></div>
      <div class="assignment-calendar"><div class="weekday">Mon</div><div class="weekday">Tue</div><div class="weekday">Wed</div><div class="weekday">Thu</div><div class="weekday">Fri</div><div class="weekday">Sat</div><div class="weekday">Sun</div>
        <?php for($blank=1;$blank<$firstWeekday;$blank++): ?><div class="calendar-cell is-empty"></div><?php endfor; ?>
        <?php for($day=1;$day<=$days;$day++): $date=$calendarMonth.'-'.str_pad((string)$day,2,'0',STR_PAD_LEFT); ?><div class="calendar-cell<?= $date===date('Y-m-d')?' is-today':'' ?>"><span class="calendar-day"><?= $day ?></span><?php foreach($calendarMap[$date]??[] as $event): ?><button type="button" class="calendar-event priority-<?= esc($event['priority']) ?>" data-open-details="<?= (int)$event['id'] ?>"><span><?= esc($event['due_time']?date('g:i A',strtotime($event['due_time'])):'') ?></span><?= esc($event['title']) ?></button><?php endforeach; ?></div><?php endfor; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<div class="assignment-fab"><button type="button" data-open-assignment-modal aria-label="Add assignment">+</button></div>
<div class="toast-stack" id="toastStack" aria-live="polite"></div>

<div class="modal assignment-modal" id="assignmentModal" aria-hidden="true"><div class="modal-card wide"><div class="modal-head"><div><p class="eyebrow">Mission Editor</p><h2 id="assignmentModalTitle">New Assignment</h2></div><button type="button" class="modal-close" data-close-modal>×</button></div>
<form id="assignmentForm" data-assignment-form><input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>"><input type="hidden" name="return_to" value="<?= esc($_SERVER['REQUEST_URI']??'/assignments','attr') ?>"><input type="hidden" name="template_id" id="formTemplateId"><div class="form-grid three">
<label class="span-2"><span>Title</span><input name="title" id="formTitle" maxlength="255" required></label><label><span>Status</span><select name="status" id="formStatus"><?php foreach($statusLabels as $v=>$l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?></select></label>
<label class="span-3"><span>Description</span><textarea name="description" id="formDescription" rows="3" maxlength="5000"></textarea></label>
<label><span>Due date</span><input type="date" name="due_date" id="formDueDate"></label><label><span>Due time</span><input type="time" name="due_time" id="formDueTime"></label><label><span>Priority</span><select name="priority" id="formPriority"><?php foreach(AssignmentModel::PRIORITIES as $p): ?><option value="<?= $p ?>"><?= ucfirst($p) ?></option><?php endforeach; ?></select></label>
<label><span>Subject</span><select name="subject_id" id="formSubject"><option value="0">General / none</option><?php foreach($subjects as $s): ?><option value="<?= (int)$s['id'] ?>"><?= esc(($s['code']?$s['code'].' · ':'').$s['name']) ?></option><?php endforeach; ?></select></label><label><span>Repeat</span><select name="recurrence" id="formRecurrence"><option value="">No repeat</option><option value="weekly">Weekly</option><option value="biweekly">Every 2 weeks</option><option value="monthly">Monthly</option></select></label><label><span>Reminder</span><select name="reminder_minutes_before" id="formReminder"><option value="0">At due time</option><option value="60">1 hour before</option><option value="1440" selected>1 day before</option><option value="2880">2 days before</option><option value="10080">1 week before</option></select></label>
<label class="span-2"><span>Reference link</span><input type="url" name="link_url" id="formLink" placeholder="https://…"></label><label><span>Custom reminder</span><input type="datetime-local" name="custom_reminder_at" id="formCustomReminder"></label>
<label class="span-3" id="subtasksTextWrap"><span>Initial subtasks — one per line</span><textarea name="subtasks_text" id="formSubtasks" rows="4" placeholder="Research sources&#10;Create outline&#10;Submit final file"></textarea></label>
</div><div class="modal-actions"><button type="button" class="arcade-button" data-close-modal>Cancel</button><button type="submit" class="arcade-button primary" id="assignmentSubmit">Save assignment</button></div></form></div></div>

<div class="modal" id="detailsModal" aria-hidden="true"><div class="modal-card details-card"><div class="modal-head"><div><p class="eyebrow" id="detailsEyebrow">Assignment</p><h2 id="detailsTitle"></h2></div><button type="button" class="modal-close" data-close-modal>×</button></div><div class="details-summary" id="detailsSummary"></div><div class="details-grid">
<section><div class="section-head"><h3>Subtasks</h3><span id="detailsProgress"></span></div><div id="subtaskList" class="subtask-list"></div><form id="subtaskForm" class="inline-add"><input name="title" maxlength="255" placeholder="Add a step…" required><button>Add</button></form></section>
<section><div class="section-head"><h3>Notes</h3><span>Editable · pinnable</span></div><div id="noteList" class="note-list"></div><form id="noteForm" class="inline-add"><textarea name="content" maxlength="2000" rows="2" placeholder="Add a note…" required></textarea><button>Add</button></form></section>
<section><div class="section-head"><h3>Important Files</h3><span>Vault attachments</span></div><div id="attachmentList" class="attachment-list"></div><form id="attachmentForm" class="inline-add"><select name="important_file_id" required><option value="">Choose file…</option><?php foreach($importantFiles as $f): ?><option value="<?= (int)$f['id'] ?>"><?= esc($f['title'].' · '.$f['original_filename']) ?></option><?php endforeach; ?></select><button>Attach</button></form></section>
<section><div class="section-head"><h3>Quick actions</h3><span>Move the deadline</span></div><div class="snooze-grid"><button data-snooze="later_today">Later today</button><button data-snooze="tomorrow">Tomorrow</button><button data-snooze="next_monday">Next Monday</button><button data-snooze="3days">+3 days</button><button data-snooze="week">+1 week</button></div></section>
</div><div class="modal-actions"><button class="arcade-button" id="detailsEdit">Edit</button><button class="arcade-button" data-close-modal>Close</button></div></div></div>

<div class="modal" id="subjectModal" aria-hidden="true"><div class="modal-card wide"><div class="modal-head"><div><p class="eyebrow">Catalog</p><h2>Subject Management</h2></div><button class="modal-close" data-close-modal>×</button></div><div class="manager-layout"><div class="manager-list"><?php foreach($subjects as $s): ?><article><span class="subject-swatch" style="background:<?= esc($s['color'],'attr') ?>"></span><div><strong><?= esc($s['name']) ?></strong><small><?= esc(trim(($s['code']??'').' · '.($s['instructor']??''),' ·')) ?></small></div><button data-archive-subject="<?= (int)$s['id'] ?>">Archive</button></article><?php endforeach; ?></div><form id="subjectForm"><input type="hidden" name="id" value=""><label>Name<input name="name" maxlength="100" required></label><label>Code<input name="code" maxlength="30"></label><label>Instructor<input name="instructor" maxlength="100"></label><label>Color<input name="color" type="color" value="#42E9FF"></label><label>Schedule<input name="schedule" maxlength="255"></label><label>Semester<input name="semester" maxlength="100"></label><button class="arcade-button primary">Save subject</button></form></div></div></div>

<div class="modal" id="templateModal" aria-hidden="true"><div class="modal-card wide"><div class="modal-head"><div><p class="eyebrow">Loadout</p><h2>Assignment Templates</h2></div><button class="modal-close" data-close-modal>×</button></div><div class="template-grid" id="templateGrid"><?php foreach($templates as $t): ?><article><strong><?= esc($t['name']) ?></strong><p><?= esc($t['title']) ?></p><div><button data-use-template="<?= (int)$t['id'] ?>">Use</button><button class="danger" data-delete-template="<?= (int)$t['id'] ?>">Delete</button></div></article><?php endforeach; ?></div><form id="templateForm" class="form-grid three"><label><span>Template name</span><input name="name" required maxlength="100"></label><label class="span-2"><span>Assignment title</span><input name="title" required maxlength="255"></label><label class="span-3"><span>Description</span><textarea name="description" rows="2"></textarea></label><label><span>Priority</span><select name="priority"><option>low</option><option selected>medium</option><option>high</option></select></label><label><span>Subject</span><select name="subject_id"><option value="0">None</option><?php foreach($subjects as $s): ?><option value="<?= (int)$s['id'] ?>"><?= esc($s['name']) ?></option><?php endforeach; ?></select></label><label><span>Reminder</span><select name="reminder_minutes_before"><option value="0">At due time</option><option value="60">1 hour</option><option value="1440" selected>1 day</option></select></label><div class="span-3"><button class="arcade-button primary">Save template</button></div></form></div></div>

<div class="modal" id="importModal" aria-hidden="true"><div class="modal-card"><div class="modal-head"><div><p class="eyebrow">Restore Data</p><h2>Import Assignments</h2></div><button class="modal-close" data-close-modal>×</button></div><form id="importPreviewForm"><label>JSON export<input type="file" name="import_file" accept="application/json,.json" required></label><button class="arcade-button primary">Preview import</button></form><div id="importPreview" hidden><div class="import-stats" id="importStats"></div><div class="import-errors" id="importErrors"></div><button class="arcade-button primary" id="confirmImport">Confirm import</button></div></div></div>

<div class="modal" id="attachmentPreviewModal" aria-hidden="true"><div class="modal-card preview-card"><div class="modal-head"><h2 id="attachmentPreviewTitle">File preview</h2><button class="modal-close" data-close-modal>×</button></div><iframe id="attachmentPreviewFrame" title="Attached file preview"></iframe></div></div>

<script type="application/json" id="assignmentsData"><?= json_encode(array_values($viewMode==='calendar'?$calendarItems:$assignments),JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_UNESCAPED_SLASHES) ?></script>
<script type="application/json" id="templatesData"><?= json_encode(array_values($templates),JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?></script>
<?= view('partials/theme_scripts') ?>
<script defer src="<?= base_url('assets/js/assignments.v2.js') ?>"></script>
</body></html>
