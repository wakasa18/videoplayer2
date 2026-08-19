<?php
// Self-contained: queries its own data so any page can include this
// partial with a single line, without its controller needing to fetch
// anything.
$__urgentAssignments = (new \App\Models\AssignmentModel())->getUrgent(2);
$__urgentCount        = count($__urgentAssignments);
$__overdueCount       = \App\Models\AssignmentModel::countOverdue($__urgentAssignments);
?>
<?php if ($__urgentCount > 0): ?>
  <div class="notif-bell-wrap">
    <button type="button" class="notif-bell" id="notifBell" aria-expanded="false" aria-controls="notifPanel" onclick="toggleNotifPanel()" aria-label="<?= $__urgentCount ?> assignment<?= $__urgentCount === 1 ? '' : 's' ?> due soon or overdue">
      <span class="notif-bell-icon" data-drive-icon="bell"></span>
      <span class="notif-bell-badge"><?= $__urgentCount > 9 ? '9+' : $__urgentCount ?></span>
    </button>

    <div class="notif-panel hidden" id="notifPanel">
      <div class="notif-panel-head">
        <?php if ($__urgentCount === 1): ?>
          <?php if ($__overdueCount === 1): ?>
            1 assignment overdue
          <?php else: ?>
            1 assignment due soon
          <?php endif; ?>
        <?php elseif ($__overdueCount === $__urgentCount): ?>
          <?= $__urgentCount ?> assignments overdue
        <?php elseif ($__overdueCount > 0): ?>
          <?= $__overdueCount ?> overdue &middot; <?= $__urgentCount - $__overdueCount ?> due soon
        <?php else: ?>
          <?= $__urgentCount ?> assignments due soon
        <?php endif; ?>
      </div>

      <ul class="notif-list">
        <?php foreach ($__urgentAssignments as $__ua): ?>
          <?php $__uaOverdue = \App\Models\AssignmentModel::isOverdue($__ua); ?>
          <li class="notif-item <?= $__uaOverdue ? 'overdue' : '' ?>">
            <span class="notif-dot"></span>
            <span class="notif-item-title"><?= esc($__ua['title']) ?></span>
            <span class="notif-item-due"><?= esc(\App\Models\AssignmentModel::relativeDueDate($__ua)) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>

      <a href="<?= base_url('assignments') ?>" class="notif-view-all">View all &rarr;</a>
    </div>
  </div>

  <script>
    function toggleNotifPanel() {
      const panel = document.getElementById('notifPanel');
      const bell  = document.getElementById('notifBell');
      if (!panel || !bell) return;
      const opening = panel.classList.contains('hidden');
      panel.classList.toggle('hidden');
      bell.setAttribute('aria-expanded', opening ? 'true' : 'false');
    }

    document.addEventListener('click', function (e) {
      const wrap  = document.querySelector('.notif-bell-wrap');
      const panel = document.getElementById('notifPanel');
      if (!wrap || !panel || panel.classList.contains('hidden')) return;
      if (!wrap.contains(e.target)) {
        panel.classList.add('hidden');
        document.getElementById('notifBell').setAttribute('aria-expanded', 'false');
      }
    });

    document.addEventListener('keydown', function (e) {
      const panel = document.getElementById('notifPanel');
      if (e.key === 'Escape' && panel && !panel.classList.contains('hidden')) {
        panel.classList.add('hidden');
        document.getElementById('notifBell').setAttribute('aria-expanded', 'false');
      }
    });
  </script>
<?php endif; ?>
