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

  /* -- task queue -- */
  .task-list{ list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:8px; }
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
  .task-title{ font-size:14px; font-weight:600; transition: color .2s ease; }
  .task-item.done .task-title{ text-decoration:line-through; color:var(--text-dim); opacity:.75; }
  .task-desc{ font-size:12px; color:var(--text-dim); margin-top:3px; line-height:1.45; }
  .task-foot{ display:flex; align-items:center; gap:10px; margin-top:9px; flex-wrap:wrap; }
  .task-due{ font-family:'JetBrains Mono', Menlo, monospace; font-size:11px; color:var(--text-dim); }
  .task-item.overdue .task-due{ color:#F7CDD0; }

  /* -- new assignment form -- */
  textarea#description{ min-height:64px; }
</style>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<div class="wrap">

  <a href="<?= base_url('others') ?>" class="nav-back">&larr; Others</a>

  <header>
    <p class="eyebrow">Task Log · Sector 22</p>
    <h1>Assignments</h1>
    <div class="starline"></div>
  </header>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="flash success"><?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="flash error"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <div class="layout">

    <div class="panel">
      <h2>Task Queue (<?= count($assignments) ?>)</h2>

      <?php if (empty($assignments)): ?>
        <div class="empty-state">No assignments logged yet. Add one to begin your queue.</div>
      <?php else: ?>
        <ul class="task-list">
          <?php foreach ($assignments as $a): ?>
            <?php $isDone = $a['status'] === 'done'; ?>
            <?php $isOverdue = \App\Models\AssignmentModel::isOverdue($a); ?>
            <li class="task-item <?= $isDone ? 'done' : '' ?> <?= $isOverdue ? 'overdue' : '' ?>">

              <form action="<?= base_url('assignments/' . $a['id'] . '/toggle') ?>" method="post" class="task-toggle-form">
                <?= csrf_field() ?>
                <button type="submit" class="task-check <?= $isDone ? 'checked' : '' ?>" title="<?= $isDone ? 'Mark as pending' : 'Mark as done' ?>">
                  <?= $isDone ? '&#10003;' : '' ?>
                </button>
              </form>

              <div class="task-meta">
                <div class="task-title"><?= esc($a['title']) ?></div>
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
                  <?php if (! empty($a['due_date'])): ?>
                    <span class="task-due">Due <?= esc(date('M j, Y', strtotime((string) $a['due_date']))) ?></span>
                  <?php endif; ?>
                </div>
              </div>

              <form action="<?= base_url('assignments/' . $a['id'] . '/delete') ?>" method="post" onsubmit="return confirm('Delete this assignment? This can\'t be undone.');">
                <?= csrf_field() ?>
                <button type="submit" class="icon-del" title="Delete">&times;</button>
              </form>

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

        <label for="due_date">Due date (optional)</label>
        <input type="date" id="due_date" name="due_date">

        <button type="submit" class="btn-primary">Add to queue</button>
      </form>
    </div>

  </div>

</div>

<?= view('partials/theme_scripts') ?>
</body>
</html>
