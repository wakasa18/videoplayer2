<!DOCTYPE html>
<html lang="en">
<head>
<title>Activity Log · Important Files</title>
<?= view('partials/theme_head') ?>
<style>
.activity-table{width:100%;border-collapse:separate;border-spacing:0 8px}.activity-table th{text-align:left;padding:8px 10px;color:var(--game-muted);font-size:12px;text-transform:uppercase}.activity-table td{padding:11px 10px;vertical-align:top;border-top:2px solid var(--game-border);border-bottom:2px solid var(--game-border);font-size:13px}.activity-table td:first-child{border-left:2px solid var(--game-border);border-radius:10px 0 0 10px}.activity-table td:last-child{border-right:2px solid var(--game-border);border-radius:0 10px 10px 0}.activity-action{font-weight:700}.activity-file{font-weight:700}.activity-time{white-space:nowrap}.activity-details{max-width:360px;word-break:break-word}.empty{text-align:center;padding:34px}@media(max-width:700px){.activity-table,.activity-table tbody,.activity-table tr,.activity-table td{display:block}.activity-table thead{display:none}.activity-table tr{margin-bottom:10px}.activity-table td{border:0!important;border-bottom:1px solid #d7bd80!important;border-radius:0!important;padding:7px 10px}.activity-table td:last-child{border-bottom:0!important}.activity-time{white-space:normal}}
</style>
<?= view('partials/retro_theme') ?>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
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
