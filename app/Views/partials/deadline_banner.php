<?php
// Self-contained: queries its own data so any page can include this
// partial with a single line, without its controller needing to fetch
// anything.
$__urgentAssignments = (new \App\Models\AssignmentModel())->getUrgent(2);
$__urgentCount        = count($__urgentAssignments);
$__overdueCount       = \App\Models\AssignmentModel::countOverdue($__urgentAssignments);
?>
<?php if ($__urgentCount > 0): ?>
  <a href="<?= base_url('assignments') ?>" class="deadline-banner">
    <span class="deadline-banner-dot"></span>
    <?php if ($__urgentCount === 1): ?>
      <?php if ($__overdueCount === 1): ?>
        <?= esc($__urgentAssignments[0]['title']) ?> is overdue
      <?php else: ?>
        <?= esc($__urgentAssignments[0]['title']) ?> is due soon
      <?php endif; ?>
    <?php elseif ($__overdueCount === $__urgentCount): ?>
      <?= $__urgentCount ?> assignments overdue
    <?php elseif ($__overdueCount > 0): ?>
      <?= $__overdueCount ?> overdue &middot; <?= $__urgentCount - $__overdueCount ?> due soon
    <?php else: ?>
      <?= $__urgentCount ?> assignments due soon
    <?php endif; ?>
    <span class="deadline-banner-arrow">View &rarr;</span>
  </a>
<?php endif; ?>
