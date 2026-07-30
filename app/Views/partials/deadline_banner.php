<?php
// Self-contained: queries its own data so any page can include this
// partial with a single line, without its controller needing to fetch
// anything.
$__urgentAssignments = (new \App\Models\AssignmentModel())->getUrgent(2);
$__urgentCount        = count($__urgentAssignments);
?>
<?php if ($__urgentCount > 0): ?>
  <a href="<?= base_url('assignments') ?>" class="deadline-banner">
    <span class="deadline-banner-dot"></span>
    <?php if ($__urgentCount === 1): ?>
      <?= esc($__urgentAssignments[0]['title']) ?> is due soon
    <?php else: ?>
      <?= $__urgentCount ?> assignments due soon
    <?php endif; ?>
    <span class="deadline-banner-arrow">View &rarr;</span>
  </a>
<?php endif; ?>
