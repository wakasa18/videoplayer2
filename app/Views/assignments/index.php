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

  /* -- panel header row (title + export/import) -- */
  .panel-head-row{ display:flex; align-items:baseline; justify-content:space-between; gap:12px; margin-bottom:14px; flex-wrap:wrap; }
  .panel-head-row h2{ margin-bottom:0; }
  .panel-head-actions{ display:flex; align-items:center; gap:14px; }
  .export-link, .import-label{
    font-family:'JetBrains Mono', Menlo, monospace; font-size:11px; color:var(--text-dim);
    text-decoration:none; white-space:nowrap; cursor:pointer;
    transition: color .15s ease;
  }
  .export-link:hover, .import-label:hover{ color:var(--cyan); }
  .import-form{ display:inline-block; }

  /* -- search + sort controls -- */
  .task-controls{ display:flex; gap:10px; margin-bottom:10px; }
  .task-controls input[type="search"]{ flex:1; }
  .task-controls select{ flex:none; width:auto; min-width:130px; }

  /* -- filter chips -- */
  .filter-chips{ display:flex; flex-wrap:wrap; gap:6px; margin-bottom:14px; }
  .chip{
    font-family:'JetBrains Mono', Menlo, monospace; font-size:10px; letter-spacing:.05em; text-transform:uppercase;
    background:transparent; border:1px solid var(--hairline); color:var(--text-dim);
    padding:4px 10px; border-radius:20px;
    transition: border-color .15s ease, color .15s ease, background .15s ease;
  }
  .chip:hover{ border-color:var(--text-dim); color:var(--text); }
  .chip.active{ border-color:var(--cyan); color:#CFF3F8; background:rgba(95,217,232,.10); }

  /* -- task summary bar + bulk actions -- */
  .task-summary{
    display:flex; align-items:center; gap:14px; flex-wrap:wrap;
    font-size:12px; color:var(--text-dim); margin-bottom:14px;
    font-family:'JetBrains Mono', Menlo, monospace;
  }
  .task-summary .count-overdue{ color:#F7CDD0; }
  .bulk-actions{ margin-left:auto; display:flex; gap:8px; }
  .bulk-btn, .hide-done-toggle{
    background:transparent; border:1px solid var(--hairline); color:var(--text-dim);
    font-family:inherit; font-size:11px; padding:5px 10px; border-radius:20px;
    transition: border-color .15s ease, color .15s ease;
  }
  .bulk-btn:hover, .hide-done-toggle:hover{ border-color:var(--cyan); color:var(--cyan); }

  /* -- task queue -- */
  .task-list{ list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:8px; }
  .task-list.hide-done .task-item.done{ display:none; }
  .task-item.filtered-out{ display:none; }
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
  .task-item.drag-mode{ cursor:grab; }
  .task-item.drag-mode:active{ cursor:grabbing; }
  .task-item.drag-over{ border-color:var(--cyan); box-shadow:0 0 0 1px rgba(95,217,232,.3); }

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
    padding:2px 7px; border-radius:20px; border:1px solid;
  }
  .link-icon{ color:var(--text-dim); text-decoration:none; font-size:13px; transition:color .15s ease; }
  .link-icon:hover{ color:var(--cyan); }
  .recur-badge{
    font-family:'JetBrains Mono', Menlo, monospace; font-size:10px; color:var(--text-dim);
    display:inline-flex; align-items:center; gap:3px;
  }
  .task-desc{ font-size:12px; color:var(--text-dim); margin-top:3px; line-height:1.45; }
  .task-foot{ display:flex; align-items:center; gap:10px; margin-top:9px; flex-wrap:wrap; }
  .task-due{ font-family:'JetBrains Mono', Menlo, monospace; font-size:11px; color:var(--text-dim); cursor:default; }
  .task-item.overdue .task-due{ color:#F7CDD0; }
  .snooze-btn{
    font-family:'JetBrains Mono', Menlo, monospace; font-size:10px; color:var(--text-dim);
    background:transparent; border:1px solid var(--hairline); border-radius:20px; padding:2px 8px;
    transition: border-color .15s ease, color .15s ease;
  }
  .snooze-btn:hover{ border-color:var(--cyan); color:var(--cyan); }

  /* -- notes -- */
  .notes-toggle{
    font-family:'JetBrains Mono', Menlo, monospace; font-size:10px; color:var(--text-dim);
    background:transparent; border:1px solid var(--hairline); border-radius:20px; padding:2px 8px;
    transition: border-color .15s ease, color .15s ease;
  }
  .notes-toggle:hover{ border-color:var(--cyan); color:var(--cyan); }
  .notes-panel{ margin-top:10px; padding-top:10px; border-top:1px dashed var(--hairline); }
  .notes-log{
    font-size:12px; color:var(--text-dim); line-height:1.6; white-space:pre-wrap;
    margin:0 0 8px; max-height:140px; overflow-y:auto;
  }
  .notes-add-row{ display:flex; gap:6px; }
  .notes-add-row input[type="text"]{ flex:1; padding:7px 10px; font-size:12px; }
  .notes-add-row button{
    flex:none; background:var(--surface); border:1px solid var(--hairline); color:var(--text-dim);
    border-radius:6px; padding:7px 12px; font-size:12px;
    transition: border-color .15s ease, color .15s ease;
  }
  .notes-add-row button:hover{ border-color:var(--cyan); color:var(--cyan); }

  .task-actions{ display:flex; flex-direction:column; flex:none; }

  /* -- inline edit form -- */
  .task-edit-form{ flex:1; display:flex; flex-direction:column; gap:8px; }
  .task-edit-row{ display:flex; gap:8px; }
  .task-edit-row > *{ flex:1; }
  .task-edit-form input[type="text"], .task-edit-form input[type="date"], .task-edit-form input[type="time"], .task-edit-form input[type="url"],
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
      <?php if (session()->getFlashdata('bulk_undo_ids')): ?>
        <form action="<?= base_url('assignments/bulk-undo') ?>" method="post" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="type" value="<?= esc(session()->getFlashdata('bulk_undo_type'), 'attr') ?>">
          <?php foreach (session()->getFlashdata('bulk_undo_ids') as $bid): ?>
            <input type="hidden" name="ids[]" value="<?= (int) $bid ?>">
          <?php endforeach; ?>
          <button type="submit" class="undo-link">Undo</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="flash error" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <form id="reorderForm" class="hidden"><?= csrf_field() ?></form>

  <div class="layout">

    <div class="panel">
      <div class="panel-head-row">
        <h2>Task Queue (<?= count($assignments) ?>)</h2>
        <?php if (! empty($assignments)): ?>
          <div class="panel-head-actions">
            <form action="<?= base_url('assignments/import') ?>" method="post" enctype="multipart/form-data" class="import-form">
              <?= csrf_field() ?>
              <label class="import-label" for="importFile">&#8679; Import JSON</label>
              <input type="file" id="importFile" name="import_file" accept="application/json" class="sr-only" onchange="this.form.submit()">
            </form>
            <a href="<?= base_url('assignments/export') ?>" class="export-link">&#8681; Export JSON</a>
          </div>
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
          <div class="bulk-actions">
            <?php if ($counts['pending'] > 0): ?>
              <form action="<?= base_url('assignments/mark-all-done') ?>" method="post" onsubmit="return confirm('Mark all pending assignments as done?');" style="display:inline;">
                <?= csrf_field() ?>
                <button type="submit" class="bulk-btn">Mark all done</button>
              </form>
            <?php endif; ?>
            <?php if ($counts['done'] > 0): ?>
              <form action="<?= base_url('assignments/clear-completed') ?>" method="post" onsubmit="return confirm('Clear every completed assignment?');" style="display:inline;">
                <?= csrf_field() ?>
                <button type="submit" class="bulk-btn">Clear completed</button>
              </form>
              <button type="button" class="hide-done-toggle" id="hideDoneToggle" onclick="toggleHideDone()" aria-pressed="false">Hide completed</button>
            <?php endif; ?>
          </div>
        </div>

        <div class="task-controls">
          <input type="search" id="taskSearch" placeholder="Search titles..." aria-label="Search assignments" oninput="filterTasks()">
          <select id="sortSelect" aria-label="Sort by" onchange="sortTasks(); saveSortPref(); toggleDragMode();">
            <option value="due">Sort: Due date</option>
            <option value="priority">Sort: Priority</option>
            <option value="alpha">Sort: Alphabetical</option>
            <option value="subject">Sort: Subject</option>
            <option value="manual">Sort: Manual (drag)</option>
          </select>
        </div>

        <?php if (! empty($subjects)): ?>
          <div class="filter-chips" id="filterChips">
            <?php foreach ($subjects as $subj): ?>
              <button type="button" class="chip" data-filter-type="subject" data-filter-value="<?= esc($subj, 'attr') ?>" aria-pressed="false" onclick="toggleChip(this)"><?= esc($subj) ?></button>
            <?php endforeach; ?>
            <button type="button" class="chip" data-filter-type="priority" data-filter-value="high" aria-pressed="false" onclick="toggleChip(this)">High</button>
            <button type="button" class="chip" data-filter-type="priority" data-filter-value="medium" aria-pressed="false" onclick="toggleChip(this)">Medium</button>
            <button type="button" class="chip" data-filter-type="priority" data-filter-value="low" aria-pressed="false" onclick="toggleChip(this)">Low</button>
          </div>
        <?php endif; ?>

        <ul class="task-list" id="taskList">
          <?php foreach ($assignments as $a): ?>
            <?php
              $isDone     = $a['status'] === 'done';
              $isOverdue  = \App\Models\AssignmentModel::isOverdue($a);
              $priority   = $a['priority'] ?? 'medium';
              $priorityWeight = \App\Models\AssignmentModel::priorityWeight($priority);
              $dueText    = \App\Models\AssignmentModel::relativeDueDate($a);
              $exactDate  = ! empty($a['due_date']) ? date('M j, Y', strtotime((string) $a['due_date'])) : '';
              $subjectRgb = ! empty($a['subject']) ? \App\Models\AssignmentModel::subjectColorRgb($a['subject']) : '';
              $noteCount  = ! empty($a['notes_log']) ? substr_count((string) $a['notes_log'], "\n") + 1 : 0;
              $recurLabel = ['weekly' => 'Weekly', 'biweekly' => 'Every 2 wks', 'monthly' => 'Monthly'][$a['recurrence'] ?? ''] ?? null;
            ?>
            <li class="task-item <?= $isDone ? 'done' : '' ?> <?= $isOverdue ? 'overdue' : '' ?> priority-<?= esc($priority, 'attr') ?>"
                data-id="<?= $a['id'] ?>"
                data-title="<?= esc($a['title'], 'attr') ?>"
                data-subject="<?= esc($a['subject'] ?? '', 'attr') ?>"
                data-priority="<?= esc($priority, 'attr') ?>"
                data-priority-weight="<?= $priorityWeight ?>"
                data-due="<?= esc($a['due_date'] ?? '', 'attr') ?>"
                data-sort-order="<?= (int) ($a['sort_order'] ?? 0) ?>">

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
                  <?php if (! empty($a['link_url'])): ?>
                    <a href="<?= esc($a['link_url'], 'attr') ?>" class="link-icon" target="_blank" rel="noopener" title="Open link" aria-label="Open link for <?= esc($a['title'], 'attr') ?>">&#128279;</a>
                  <?php endif; ?>
                  <?php if (! empty($a['subject'])): ?>
                    <span class="subject-tag" style="border-color:rgba(<?= $subjectRgb ?>,.4); color:rgb(<?= $subjectRgb ?>); background:rgba(<?= $subjectRgb ?>,.10);"><?= esc($a['subject']) ?></span>
                  <?php endif; ?>
                  <?php if ($recurLabel): ?>
                    <span class="recur-badge" title="Repeats">&#8635; <?= esc($recurLabel) ?></span>
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
                  <?php if (! empty($a['due_date']) && ! $isDone): ?>
                    <form action="<?= base_url('assignments/' . $a['id'] . '/snooze') ?>" method="post" style="display:inline;">
                      <?= csrf_field() ?>
                      <button type="submit" class="snooze-btn" title="Push due date back one day" aria-label="Push <?= esc($a['title'], 'attr') ?> back one day">+1 day</button>
                    </form>
                  <?php endif; ?>
                  <button type="button" class="notes-toggle" aria-expanded="false" aria-controls="notes-<?= $a['id'] ?>" onclick="toggleNotes(<?= $a['id'] ?>)">Notes<?= $noteCount > 0 ? ' (' . $noteCount . ')' : '' ?></button>
                </div>

                <div class="notes-panel hidden" id="notes-<?= $a['id'] ?>">
                  <?php if (! empty($a['notes_log'])): ?>
                    <div class="notes-log"><?= esc($a['notes_log']) ?></div>
                  <?php endif; ?>
                  <form action="<?= base_url('assignments/' . $a['id'] . '/notes') ?>" method="post" class="notes-add-row">
                    <?= csrf_field() ?>
                    <input type="text" name="note" placeholder="Add a note..." maxlength="500" aria-label="Add a note">
                    <button type="submit">Add</button>
                  </form>
                </div>
              </div>

              <form action="<?= base_url('assignments/' . $a['id'] . '/update') ?>" method="post" class="task-edit-form hidden" id="edit-<?= $a['id'] ?>">
                <?= csrf_field() ?>
                <input type="text" name="title" value="<?= esc($a['title'], 'attr') ?>" maxlength="255" required aria-label="Title">
                <textarea name="description" rows="2" placeholder="Description (optional)" aria-label="Description"><?= esc($a['description']) ?></textarea>
                <div class="task-edit-row">
                  <input type="date" name="due_date" value="<?= esc($a['due_date'] ?? '', 'attr') ?>" aria-label="Due date">
                  <input type="time" name="due_time" value="<?= esc($a['due_time'] ?? '', 'attr') ?>" aria-label="Due time">
                </div>
                <div class="task-edit-row">
                  <select name="priority" aria-label="Priority">
                    <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Low</option>
                    <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>High</option>
                  </select>
                  <select name="recurrence" aria-label="Repeat">
                    <option value="" <?= empty($a['recurrence']) ? 'selected' : '' ?>>No repeat</option>
                    <option value="weekly" <?= ($a['recurrence'] ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                    <option value="biweekly" <?= ($a['recurrence'] ?? '') === 'biweekly' ? 'selected' : '' ?>>Every 2 weeks</option>
                    <option value="monthly" <?= ($a['recurrence'] ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                  </select>
                </div>
                <input type="text" name="subject" value="<?= esc($a['subject'] ?? '', 'attr') ?>" placeholder="Subject (optional)" maxlength="100" aria-label="Subject">
                <input type="url" name="link_url" value="<?= esc($a['link_url'] ?? '', 'attr') ?>" placeholder="Link (optional)" aria-label="Link URL">
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
        <div class="empty-state hidden" id="noMatches">No assignments match your search or filters.</div>
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
            <label for="due_time">Due time (optional)</label>
            <input type="time" id="due_time" name="due_time">
          </div>
        </div>

        <div class="field-row">
          <div>
            <label for="priority">Priority</label>
            <select id="priority" name="priority">
              <option value="low">Low</option>
              <option value="medium" selected>Medium</option>
              <option value="high">High</option>
            </select>
          </div>
          <div>
            <label for="recurrence">Repeat</label>
            <select id="recurrence" name="recurrence">
              <option value="">No repeat</option>
              <option value="weekly">Weekly</option>
              <option value="biweekly">Every 2 weeks</option>
              <option value="monthly">Monthly</option>
            </select>
          </div>
        </div>

        <div class="field-row">
          <div>
            <label for="subject">Subject (optional)</label>
            <input type="text" id="subject" name="subject" maxlength="100" placeholder="e.g. Math, History">
          </div>
          <div>
            <label for="link_url">Link (optional)</label>
            <input type="url" id="link_url" name="link_url" placeholder="Portal or instructions URL">
          </div>
        </div>

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

  function toggleNotes(id) {
    const panel = document.getElementById('notes-' + id);
    panel.classList.toggle('hidden');
    const btn = document.querySelector('.notes-toggle[aria-controls="notes-' + id + '"]');
    if (btn) {
      btn.setAttribute('aria-expanded', panel.classList.contains('hidden') ? 'false' : 'true');
    }
  }

  function toggleHideDone() {
    const list = document.getElementById('taskList');
    const btn  = document.getElementById('hideDoneToggle');
    const hidden = list.classList.toggle('hide-done');
    btn.textContent = hidden ? 'Show completed' : 'Hide completed';
    btn.setAttribute('aria-pressed', hidden ? 'true' : 'false');
  }

  // --- search + filter chips ---
  function toggleChip(btn) {
    btn.classList.toggle('active');
    btn.setAttribute('aria-pressed', btn.classList.contains('active') ? 'true' : 'false');
    filterTasks();
  }

  function filterTasks() {
    const searchEl = document.getElementById('taskSearch');
    const search = searchEl ? searchEl.value.trim().toLowerCase() : '';
    const activeSubjects = Array.from(document.querySelectorAll('.chip[data-filter-type="subject"].active')).map(c => c.dataset.filterValue);
    const activePriorities = Array.from(document.querySelectorAll('.chip[data-filter-type="priority"].active')).map(c => c.dataset.filterValue);

    const items = document.querySelectorAll('.task-item');
    let visibleCount = 0;

    items.forEach(item => {
      const title = (item.dataset.title || '').toLowerCase();
      const subject = item.dataset.subject || '';
      const priority = item.dataset.priority || '';

      const matchesSearch = search === '' || title.includes(search);
      const matchesSubject = activeSubjects.length === 0 || activeSubjects.includes(subject);
      const matchesPriority = activePriorities.length === 0 || activePriorities.includes(priority);

      const visible = matchesSearch && matchesSubject && matchesPriority;
      item.classList.toggle('filtered-out', !visible);
      if (visible) visibleCount++;
    });

    const noMatches = document.getElementById('noMatches');
    if (noMatches) {
      noMatches.classList.toggle('hidden', visibleCount !== 0);
    }
  }

  // --- sort ---
  function sortTasks() {
    const select = document.getElementById('sortSelect');
    const list = document.getElementById('taskList');
    if (!select || !list) return;
    const mode = select.value;
    const items = Array.from(list.children);

    items.sort((a, b) => {
      const aDone = a.classList.contains('done') ? 1 : 0;
      const bDone = b.classList.contains('done') ? 1 : 0;
      if (aDone !== bDone) return aDone - bDone;

      const aTitle = (a.dataset.title || '').toLowerCase();
      const bTitle = (b.dataset.title || '').toLowerCase();
      const aDue = a.dataset.due || '9999-12-31';
      const bDue = b.dataset.due || '9999-12-31';
      const aPri = parseInt(a.dataset.priorityWeight || '2', 10);
      const bPri = parseInt(b.dataset.priorityWeight || '2', 10);
      const aSub = (a.dataset.subject || '\uffff').toLowerCase();
      const bSub = (b.dataset.subject || '\uffff').toLowerCase();
      const aOrder = parseInt(a.dataset.sortOrder || '0', 10);
      const bOrder = parseInt(b.dataset.sortOrder || '0', 10);

      switch (mode) {
        case 'priority':
          if (bPri !== aPri) return bPri - aPri;
          return aDue.localeCompare(bDue);
        case 'alpha':
          return aTitle.localeCompare(bTitle);
        case 'subject':
          if (aSub !== bSub) return aSub.localeCompare(bSub);
          return aTitle.localeCompare(bTitle);
        case 'manual':
          return aOrder - bOrder;
        case 'due':
        default:
          if (aDue !== bDue) return aDue.localeCompare(bDue);
          return bPri - aPri;
      }
    });

    items.forEach(item => list.appendChild(item));
  }

  function saveSortPref() {
    const select = document.getElementById('sortSelect');
    if (select) sessionStorage.setItem('assignmentsSort', select.value);
  }

  // --- manual drag-to-reorder ---
  let dragSrcEl = null;

  function toggleDragMode() {
    const select = document.getElementById('sortSelect');
    const isManual = select && select.value === 'manual';
    document.querySelectorAll('.task-item').forEach(item => {
      item.classList.toggle('drag-mode', isManual);
      if (isManual) {
        item.setAttribute('draggable', 'true');
      } else {
        item.removeAttribute('draggable');
      }
    });
  }

  function handleDragStart(e) {
    dragSrcEl = e.currentTarget;
    e.dataTransfer.effectAllowed = 'move';
  }

  function handleDragOver(e) {
    if (e.currentTarget.getAttribute('draggable') !== 'true') return true;
    e.preventDefault();
    e.currentTarget.classList.add('drag-over');
    return false;
  }

  function handleDragLeave(e) {
    e.currentTarget.classList.remove('drag-over');
  }

  function handleDrop(e) {
    if (e.stopPropagation) e.stopPropagation();
    const target = e.currentTarget;
    target.classList.remove('drag-over');

    if (dragSrcEl && dragSrcEl !== target) {
      const list = document.getElementById('taskList');
      const items = Array.from(list.children);
      const srcIndex = items.indexOf(dragSrcEl);
      const targetIndex = items.indexOf(target);
      if (srcIndex < targetIndex) {
        target.after(dragSrcEl);
      } else {
        target.before(dragSrcEl);
      }
      persistOrder();
    }
    return false;
  }

  function persistOrder() {
    const ids = Array.from(document.querySelectorAll('#taskList .task-item')).map(el => el.dataset.id);
    const csrfInput = document.querySelector('#reorderForm input[type="hidden"]');
    const body = new URLSearchParams();
    body.set('ids', ids.join(','));
    if (csrfInput) body.set(csrfInput.name, csrfInput.value);

    fetch('<?= base_url('assignments/reorder') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
    }).catch(() => { /* best-effort; a failed reorder save just reverts on next reload */ });
  }

  document.querySelectorAll('.task-item').forEach(item => {
    item.addEventListener('dragstart', handleDragStart);
    item.addEventListener('dragover', handleDragOver);
    item.addEventListener('dragleave', handleDragLeave);
    item.addEventListener('drop', handleDrop);
  });

  (function restoreSortPref() {
    const saved = sessionStorage.getItem('assignmentsSort');
    const select = document.getElementById('sortSelect');
    if (saved && select) {
      select.value = saved;
      sortTasks();
    }
    toggleDragMode();
  })();
</script>
</body>
</html>
