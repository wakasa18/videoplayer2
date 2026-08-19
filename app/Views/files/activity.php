<!DOCTYPE html>
<html lang="en">
<head>
<title>Activity Log · Important Files</title>
<?= view('partials/theme_head') ?>
<?= view('partials/drive_theme') ?>
</head>
<body>
<div class="wrap">
  <a href="<?= base_url('files') ?>" class="nav-back">&larr; Important Files</a>
  <header><p class="eyebrow">Restricted Archive · Audit Trail</p><h1>Activity Log</h1><div class="starline"></div></header>
  <section class="panel">
    <?php if ($events === []): ?><div class="empty">No file activity has been recorded yet.</div><?php else: ?>
    <table class="activity-table"><thead><tr><th>Action</th><th>File</th><th>Details</th><th>Date</th></tr></thead><tbody>
    <?php foreach ($events as $event): $details = is_array($event['details'] ?? null) ? $event['details'] : (($event['details'] ?? null) ? (json_decode((string) $event['details'], true) ?: []) : []); ?>
      <tr><td><span class="activity-action"><?= esc(str_replace('_', ' ', strtoupper($event['action']))) ?></span></td><td class="activity-file"><?= esc($event['file_title'] ?: ($details['title'] ?? 'Vault')) ?></td><td class="activity-details"><?= esc($details ? json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '—') ?></td><td class="activity-time"><?= esc(date('M j, Y g:i A', strtotime($event['created_at']))) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table><?= $pager->links('activity') ?>
    <?php endif; ?>
  </section>
</div>
<?= view('partials/theme_scripts') ?>
</body>
</html>
