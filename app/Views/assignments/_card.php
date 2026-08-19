<?php
/** @var array $assignment */
$a = $assignment;
$statusLabels = ['to_do'=>'To Do','in_progress'=>'In Progress','blocked'=>'Blocked','submitted'=>'Submitted','done'=>'Done'];
$status = $a['status'] ?? 'to_do';
$subjectName = $a['subject_name'] ?: ($a['subject'] ?? '');
$subjectColor = $a['subject_color'] ?: '#42E9FF';
$progress = $a['subtask_progress'] ?? ['total'=>0,'done'=>0,'percent'=>0];
?>
<article class="assignment-card status-<?= esc($status,'attr') ?> priority-<?= esc($a['priority'],'attr') ?><?= !empty($a['is_overdue']) ? ' is-overdue' : '' ?>"
         data-assignment-id="<?= (int)$a['id'] ?>" data-status="<?= esc($status,'attr') ?>" data-priority="<?= esc($a['priority'],'attr') ?>">
  <label class="select-box" title="Select assignment"><input type="checkbox" class="assignment-select" value="<?= (int)$a['id'] ?>"><span></span></label>
  <button class="card-open" type="button" data-open-details="<?= (int)$a['id'] ?>" aria-label="Open <?= esc($a['title'],'attr') ?> details">
    <div class="card-topline">
      <span class="priority-signal priority-<?= esc($a['priority'],'attr') ?>"><?= strtoupper(esc($a['priority'])) ?></span>
      <?php if ($subjectName): ?><span class="subject-chip" style="--subject-color:<?= esc($subjectColor,'attr') ?>"><?= esc($a['subject_code'] ?: $subjectName) ?></span><?php endif; ?>
      <?php if (!empty($a['recurrence'])): ?><span class="micro-tag">↻ <?= esc($a['recurrence']) ?></span><?php endif; ?>
    </div>
    <h3><?= esc($a['title']) ?></h3>
    <?php if (!empty($a['description'])): ?><p class="card-description"><?= esc($a['description']) ?></p><?php endif; ?>
    <div class="card-meta">
      <span class="due-label<?= !empty($a['is_overdue']) ? ' overdue' : '' ?>"><?= esc($a['relative_due'] ?: 'No deadline') ?></span>
      <?php if (!empty($a['attachments'])): ?><span>📎 <?= count($a['attachments']) ?></span><?php endif; ?>
      <?php if (!empty($a['notes'])): ?><span>✎ <?= count($a['notes']) ?></span><?php endif; ?>
    </div>
    <?php if (($progress['total'] ?? 0) > 0): ?>
      <div class="task-progress" aria-label="<?= (int)$progress['done'] ?> of <?= (int)$progress['total'] ?> subtasks complete">
        <span style="width:<?= (int)$progress['percent'] ?>%"></span>
      </div>
      <div class="progress-copy"><?= (int)$progress['done'] ?>/<?= (int)$progress['total'] ?> subtasks · <?= (int)$progress['percent'] ?>%</div>
    <?php endif; ?>
  </button>
  <div class="card-controls">
    <select class="status-select" data-status-for="<?= (int)$a['id'] ?>" aria-label="Change status for <?= esc($a['title'],'attr') ?>">
      <?php foreach ($statusLabels as $value=>$label): ?><option value="<?= $value ?>" <?= $status===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?>
    </select>
    <details class="card-menu">
      <summary aria-label="Actions for <?= esc($a['title'],'attr') ?>">•••</summary>
      <div class="card-menu-panel">
        <button type="button" data-open-details="<?= (int)$a['id'] ?>">Open details</button>
        <button type="button" data-edit-assignment="<?= (int)$a['id'] ?>">Edit</button>
        <button type="button" data-quick-deadline="<?= (int)$a['id'] ?>">Quick due date</button>
        <button type="button" data-move-card="up" data-id="<?= (int)$a['id'] ?>">Move up</button>
        <button type="button" data-move-card="down" data-id="<?= (int)$a['id'] ?>">Move down</button>
        <button type="button" data-assignment-action="duplicate" data-id="<?= (int)$a['id'] ?>">Duplicate</button>
        <button type="button" data-assignment-action="archive" data-id="<?= (int)$a['id'] ?>">Archive</button>
        <button type="button" class="danger" data-assignment-action="delete" data-id="<?= (int)$a['id'] ?>">Move to Recycle Bin</button>
      </div>
    </details>
  </div>
</article>
