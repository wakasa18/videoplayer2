<!DOCTYPE html>
<html lang="en">
<head>
<title>Assignments · Damon's Archive</title>
<?= view('partials/theme_head') ?>
<style>
  /* -- layout -- */
  .layout{ display:grid; grid-template-columns: 1.4fr 1fr; gap:24px; margin-top:28px; }
  @media (max-width: 860px){ .layout{ grid-template-columns: 1fr; } }
  .layout > .panel:nth-of-type(1){ animation-delay:.28s; }
  .layout > .panel:nth-of-type(2){ animation-delay:.38s; }

  /* -- flash + undo -- */
  .undo-link{
    background:transparent; border:none; padding:0; margin-left:10px;
    color:inherit; text-decoration:underline; font-size:inherit; font-weight:600; cursor:pointer;
  }
  .undo-link:hover{ color:var(--cyan); }

  /* -- task summary bar -- */
  .task-summary{
    display:flex; align-items:center; gap:14px; flex-wrap:wrap;
    font-size:12px; color:var(--text-dim); margin-bottom:14px;
    font-family:'JetBrains Mono', Menlo, monospace;
  }
  .task-summary .count-overdue{ color:#F7CDD0; }
  .hide-done-toggle{
    margin-left:auto; background:transparent; border:1px solid var(--hairline); color:var(--text-dim);
    font-family:inherit; font-size:11px; padding:5px 10px; border-radius:20px;
    transition: border-color .15s ease, color .15s ease;
  }
  .hide-done-toggle:hover{ border-color:var(--cyan); color:var(--cyan); }

  /* -- task queue -- */
  .task-list{ list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:8px; }
  .task-list.hide-done .task-item.done{ display:none; }
  .task-item{
    display:flex; align-items:flex-start; gap:12px;
    background:var(--surface-2); border:1px solid var(--hairline); border-radius:8px;
    padding:12px 14px;
    transition:border-color .15s ease, transform .15s ease;
    opacity:0; animation:fadeInUp .4s ease forwards;
  }
  .task-item:nth-child(1){ animation-delay:.05s; }
  .task-item:nth-child(2){ animation-delay:.10s; }
  .task-item:nth-child(3){ animation-delay:.15s; }
  .task-item:nth-child(4){ animation-delay:.20s; }
  .task-item:nth-child(5){ animation-delay:.25s; }
  .task-item:nth-child(6){ animation-delay:.30s; }
  .task-item:nth-child(7){ animation-delay:.35s; }
  .task-item:nth-child(8){ animation-delay:.40s; }
  .task-item:hover{ border-color:#2c3a68; transform:translateX(3px); }
  .task-item.overdue{ border-color:rgba(229,99,107,.35); }
  .task-item.priority-high{ border-left:3px solid var(--red); }
  .task-item.priority-medium{ border-left:3px solid var(--gold); }

  .task-toggle-form{ flex:none; margin-top:1px; }
  .task-check{
    width:26px; height:26px; border-radius:50%; background:var(--surface);
    border:1.5px solid var(--hairline); color:var(--cyan); font-size:13px; font-weight:700;
    padding:0; display:flex; align-items:center; justify-content:center;
    transition: border-color .15s ease, background .15s ease, transform .15s ease;
  }
  .task-check:hover{ border-color:var(--cyan); transform:scale(1.08); }
  .task-check.checked{ background:rgba(95,217,232,.15); border-color:var(--cyan); animation:checkPop .3s ease; }
  @keyframes checkPop{ 0%{ transform:scale(.6); } 60%{ transform:scale(1.18); } 100%{ transform:scale(1); } }

  .task-meta{ flex:1; min-width:0; }
  .task-title-row{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
  .task-title{ font-size:14px; font-weight:600; transition: color .2s ease; }
  .task-item.done .task-title{ text-decoration:line-through; color:var(--text-dim); opacity:.75; }
  .subject-tag{
    display:inline-block; font-size:10px; text-transform:uppercase; letter-spacing:.06em;
    color:var(--text-dim); background:var(--surface); border:1px solid var(--hairline);
    padding:2px 7px; border-radius:20px;
  }
  .task-desc{ font-size:12px; color:var(--text-dim); margin-top:3px; line-height:1.45; }
  .task-foot{ display:flex; align-items:center; gap:10px; margin-top:9px; flex-wrap:wrap; }
  .task-due{ font-family:'JetBrains Mono', Menlo, monospace; font-size:11px; color:var(--text-dim); cursor:default; }
  .task-item.overdue .task-due{ color:#F7CDD0; }

  .task-actions{ display:flex; flex-direction:column; flex:none; }

  /* -- inline edit form -- */
  .task-edit-form{ flex:1; display:flex; flex-direction:column; gap:8px; }
  .task-edit-row{ display:flex; gap:8px; }
  .task-edit-row > *{ flex:1; }
  .task-edit-form input[type="text"], .task-edit-form input[type="date"],
  .task-edit-form textarea, .task-edit-form select{
    padding:8px 10px; font-size:13px;
  }
  .task-edit-form textarea{ min-height:44px; }
  .task-edit-actions{ display:flex; gap:8px; }
  .task-edit-actions .btn-primary{ width:auto; margin-top:0; padding:8px 16px; font-size:13px; }
  .task-edit-cancel{
    background:transparent; border:1px solid var(--hairline); color:var(--text-dim);
    border-radius:6px; padding:8px 16px; font-size:13px;
  }
  .task-edit-cancel:hover{ border-color:var(--text-dim); color:var(--text); }

  /* -- new assignment form -- */
  textarea#description{ min-height:64px; }
  .field-row{ display:flex; gap:12px; }
  .field-row > *{ flex:1; }

  /* -- panel header row (title + export link) -- */
  .panel-head-row{ display:flex; align-items:baseline; justify-content:space-between; gap:12px; margin-bottom:14px; }
  .panel-head-row h2{ margin-bottom:0; }
  .export-link{
    font-family:'JetBrains Mono', Menlo, monospace; font-size:11px; color:var(--text-dim);
    text-decoration:none; white-space:nowrap;
    transition: color .15s ease;
  }
  .export-link:hover{ color:var(--cyan); }
  select{
    width:100%; background:var(--surface-2); border:1px solid var(--hairline);
    border-radius:6px; padding:10px 12px; color:var(--text); font-size:14px; font-family:inherit;
  }
  select:focus{ outline:2px solid var(--cyan); outline-offset:1px; }
</style>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<div class="wrap">

  <?= view('partials/deadline_banner') ?>

  <a href="<?= base_url('others') ?>" class="nav-back">&larr; Others</a>

  <header>
    <p class="eyebrow">Task Log · Sector 22</p>
    <h1>Assignments</h1>
    <div class="starline"></div>
  </header>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="flash success" role="status" aria-live="polite">
      <?= esc(session()->getFlashdata('success')) ?>
      <?php if (session()->getFlashdata('undo_id')): ?>
        <form action="<?= base_url('assignments/' . (int) session()->getFlashdata('undo_id') . '/restore') ?>" method="post" style="display:inline;">
          <?= csrf_field() ?>
          <button type="submit" class="undo-link">Undo</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="flash error" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <div class="layout">

    <div class="panel">
      <div class="panel-head-row">
        <h2>Task Queue (<?= count($assignments) ?>)</h2>
        <?php if (! empty($assignments)): ?>
          <a href="<?= base_url('assignments/export') ?>" class="export-link">&#8681; Export JSON</a>
        <?php endif; ?>
      </div>

      <?php if (empty($assignments)): ?>
        <div class="empty-state">No assignments logged yet. Add one to begin your queue.</div>
      <?php else: ?>

        <div class="task-summary">
          <span><?= $counts['pending'] ?> pending</span>
          <?php if ($counts['overdue'] > 0): ?>
            <span class="count-overdue"><?= $counts['overdue'] ?> overdue</span>
          <?php endif; ?>
          <span><?= $counts['done'] ?> done</span>
          <?php if ($counts['done'] > 0): ?>
            <button type="button" class="hide-done-toggle" id="hideDoneToggle" onclick="toggleHideDone()" aria-pressed="false">Hide completed</button>
          <?php endif; ?>
        </div>

        <ul class="task-list" id="taskList">
          <?php foreach ($assignments as $a): ?>
            <?php
              $isDone     = $a['status'] === 'done';
              $isOverdue  = \App\Models\AssignmentModel::isOverdue($a);
              $priority   = $a['priority'] ?? 'medium';
              $dueText    = \App\Models\AssignmentModel::relativeDueDate($a);
              $exactDate  = ! empty($a['due_date']) ? date('M j, Y', strtotime((string) $a['due_date'])) : '';
            ?>
            <li class="task-item <?= $isDone ? 'done' : '' ?> <?= $isOverdue ? 'overdue' : '' ?> priority-<?= esc($priority, 'attr') ?>">

              <form action="<?= base_url('assignments/' . $a['id'] . '/toggle') ?>" method="post" class="task-toggle-form">
                <?= csrf_field() ?>
                <button type="submit" class="task-check <?= $isDone ? 'checked' : '' ?>"
                        title="<?= $isDone ? 'Mark as pending' : 'Mark as done' ?>"
                        aria-label="<?= $isDone ? 'Mark ' . esc($a['title'], 'attr') . ' as pending' : 'Mark ' . esc($a['title'], 'attr') . ' as done' ?>"
                        aria-pressed="<?= $isDone ? 'true' : 'false' ?>">
                  <?= $isDone ? '&#10003;' : '' ?>
                </button>
              </form>

              <div class="task-meta" id="view-<?= $a['id'] ?>">
                <div class="task-title-row">
                  <div class="task-title"><?= esc($a['title']) ?></div>
                  <?php if (! empty($a['subject'])): ?>
                    <span class="subject-tag"><?= esc($a['subject']) ?></span>
                  <?php endif; ?>
                </div>
                <?php if (! empty($a['description'])): ?>
                  <div class="task-desc"><?= esc($a['description']) ?></div>
                <?php endif; ?>
                <div class="task-foot">
                  <?php if ($isDone): ?>
                    <span class="badge live"><span class="dot"></span>Done</span>
                  <?php elseif ($isOverdue): ?>
                    <span class="badge overdue"><span class="dot"></span>Overdue</span>
                  <?php else: ?>
                    <span class="badge soon"><span class="dot"></span>Pending</span>
                  <?php endif; ?>
                  <?php if ($dueText): ?>
                    <span class="task-due" title="<?= esc($exactDate, 'attr') ?>"><?= esc($dueText) ?></span>
                  <?php endif; ?>
                </div>
              </div>

              <form action="<?= base_url('assignments/' . $a['id'] . '/update') ?>" method="post" class="task-edit-form hidden" id="edit-<?= $a['id'] ?>">
                <?= csrf_field() ?>
                <input type="text" name="title" value="<?= esc($a['title'], 'attr') ?>" maxlength="255" required aria-label="Title">
                <textarea name="description" rows="2" placeholder="Description (optional)" aria-label="Description"><?= esc($a['description']) ?></textarea>
                <div class="task-edit-row">
                  <input type="date" name="due_date" value="<?= esc($a['due_date'] ?? '', 'attr') ?>" aria-label="Due date">
                  <select name="priority" aria-label="Priority">
                    <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Low</option>
                    <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>High</option>
                  </select>
                </div>
                <input type="text" name="subject" value="<?= esc($a['subject'] ?? '', 'attr') ?>" placeholder="Subject (optional)" maxlength="100" aria-label="Subject">
                <div class="task-edit-actions">
                  <button type="submit" class="btn-primary">Save</button>
                  <button type="button" class="task-edit-cancel" onclick="toggleEdit(<?= $a['id'] ?>)">Cancel</button>
                </div>
              </form>

              <div class="task-actions" id="actions-<?= $a['id'] ?>">
                <button type="button" class="icon-edit" title="Edit" aria-label="Edit <?= esc($a['title'], 'attr') ?>" aria-expanded="false" aria-controls="edit-<?= $a['id'] ?>" onclick="toggleEdit(<?= $a['id'] ?>)">&#9998;</button>
                <form action="<?= base_url('assignments/' . $a['id'] . '/delete') ?>" method="post" onsubmit="return confirm('Delete this assignment?');">
                  <?= csrf_field() ?>
                  <button type="submit" class="icon-del" title="Delete" aria-label="Delete <?= esc($a['title'], 'attr') ?>">&times;</button>
                </form>
              </div>

            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="panel">
      <h2>Log New Assignment</h2>
      <form action="<?= base_url('assignments') ?>" method="post">
        <?= csrf_field() ?>

        <label for="title">Title</label>
        <input type="text" id="title" name="title" maxlength="255" required>

        <label for="description">Description (optional)</label>
        <textarea id="description" name="description" rows="3"></textarea>

        <div class="field-row">
          <div>
            <label for="due_date">Due date (optional)</label>
            <input type="date" id="due_date" name="due_date">
          </div>
          <div>
            <label for="priority">Priority</label>
            <select id="priority" name="priority">
              <option value="low">Low</option>
              <option value="medium" selected>Medium</option>
              <option value="high">High</option>
            </select>
          </div>
        </div>

        <label for="subject">Subject (optional)</label>
        <input type="text" id="subject" name="subject" maxlength="100" placeholder="e.g. Math, History">

        <button type="submit" class="btn-primary">Add to queue</button>
      </form>
    </div>

  </div>

</div>

<?= view('partials/theme_scripts') ?>
<script>
  function toggleEdit(id) {
    document.getElementById('view-' + id).classList.toggle('hidden');
    document.getElementById('edit-' + id).classList.toggle('hidden');
    const actions = document.getElementById('actions-' + id);
    actions.classList.toggle('hidden');

    const editBtn = document.querySelector('.icon-edit[aria-controls="edit-' + id + '"]');
    if (editBtn) {
      const nowEditing = !document.getElementById('edit-' + id).classList.contains('hidden');
      editBtn.setAttribute('aria-expanded', nowEditing ? 'true' : 'false');
    }
  }

  function toggleHideDone() {
    const list = document.getElementById('taskList');
    const btn  = document.getElementById('hideDoneToggle');
    const hidden = list.classList.toggle('hide-done');
    btn.textContent = hidden ? 'Show completed' : 'Hide completed';
    btn.setAttribute('aria-pressed', hidden ? 'true' : 'false');
  }
</script>
</body>
</html>
