<!DOCTYPE html>
<html lang="en">
<head>
<title>Activity Log · Important Files</title>
<?= view('partials/theme_head') ?>
<style>
  .activity-table{width:100%;border-collapse:collapse}.activity-table th,.activity-table td{text-align:left;padding:11px 10px;border-bottom:1px solid var(--hairline);vertical-align:top}.activity-table th{font:10px 'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.08em;color:var(--text-dim)}.activity-table td{font-size:12px}.activity-action{color:var(--cyan);font-family:'JetBrains Mono',monospace;font-size:10px}.activity-file{font-weight:600}.activity-time{color:var(--text-dim);white-space:nowrap}.activity-details{color:var(--text-dim);max-width:360px;word-break:break-word}.pagination{display:flex;gap:6px;list-style:none;padding:0;margin:18px 0 0;flex-wrap:wrap}.pagination a,.pagination span{display:block;padding:7px 10px;border:1px solid var(--hairline);border-radius:5px;color:var(--text-dim);text-decoration:none;font-size:12px}.pagination .active a,.pagination a:hover{color:var(--cyan);border-color:var(--cyan)}.empty{text-align:center;padding:34px;color:var(--text-dim)}
  @media(max-width:700px){.activity-table,.activity-table tbody,.activity-table tr,.activity-table td{display:block}.activity-table thead{display:none}.activity-table tr{padding:10px 0;border-bottom:1px solid var(--hairline)}.activity-table td{border:0;padding:4px 0}.activity-time{white-space:normal}}
</style>
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
