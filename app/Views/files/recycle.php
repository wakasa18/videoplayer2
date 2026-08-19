<!DOCTYPE html>
<html lang="en">
<head>
<title>Recycle Bin · Important Files</title>
<?= view('partials/theme_head') ?>
<style>
.top-links{display:flex;gap:9px;flex-wrap:wrap;margin:18px 0}.top-link{display:inline-flex;align-items:center;min-height:38px;padding:8px 12px;border:2px solid var(--game-border);border-radius:9px;background:#fff1ad;color:#533217;text-decoration:none;font-weight:700}.notice{font-size:13px;line-height:1.6;margin:0 0 16px}.file-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px}.file-item{display:flex;align-items:center;gap:12px;padding:12px}.badge-file{flex:none;width:48px;height:42px;display:grid;place-items:center;border-radius:8px;font-weight:700;font-size:11px}.file-meta{flex:1;min-width:0}.file-title{font-weight:700;font-size:15px}.file-sub{font-size:12px;margin-top:4px}.actions{display:flex;gap:7px;flex-wrap:wrap}.actions form{margin:0}.btn-small{min-height:36px;padding:7px 10px;font-size:12px}.empty{text-align:center;padding:34px;border:2px dashed var(--game-border);border-radius:10px}.secondary{background:#dff5ff!important}.modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:18px}@media(max-width:650px){.file-item{align-items:flex-start;flex-wrap:wrap}.file-meta{min-width:calc(100% - 62px)}.actions{width:100%;padding-left:60px}}
</style>
<?= view('partials/retro_theme') ?>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<div class="wrap">
  <a href="<?= base_url('files') ?>" class="nav-back">&larr; Important Files</a>
  <header><p class="eyebrow">Restricted Archive · Recovery</p><h1>Recycle Bin</h1><div class="starline"></div></header>
  <?php if (session()->getFlashdata('error')): ?><div class="flash error"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
  <?php if (session()->getFlashdata('success')): ?><div class="flash success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
  <div class="top-links"><a class="top-link" href="<?= base_url('files') ?>">Vault</a><a class="top-link" href="<?= base_url('files/activity') ?>">Activity Log</a></div>
  <section class="panel">
    <h2>Deleted files</h2>
    <p class="notice">Files remain in the private Supabase bucket for 30 days. Restore brings them back immediately. Permanent deletion cannot be undone.</p>
    <?php if ($files === []): ?><div class="empty">The Recycle Bin is empty.</div><?php else: ?>
      <ul class="file-list">
      <?php foreach ($files as $f): $purge = ! empty($f['purge_at']) ? date('M j, Y', strtotime($f['purge_at'])) : 'Not scheduled'; ?>
        <li class="file-item">
          <div class="badge-file"><?= esc(strtoupper($f['file_extension'] ?: pathinfo($f['original_filename'], PATHINFO_EXTENSION))) ?></div>
          <div class="file-meta"><div class="file-title"><?= esc($f['title']) ?></div><?php if (! empty($f['folder_path'])): ?><div class="file-sub">&#128193; <?= esc($f['folder_path']) ?></div><?php endif; ?><div class="file-sub"><?= esc($f['original_filename']) ?> · <?= \App\Models\ImportantFileModel::formatBytes((int) $f['file_size']) ?> · Purges <?= esc($purge) ?></div></div>
          <div class="actions">
            <form action="<?= base_url('files/' . $f['id'] . '/restore') ?>" method="post"><?= csrf_field() ?><button class="btn-small" type="submit">Restore</button></form>
            <button class="btn-small danger js-purge" type="button" data-url="<?= base_url('files/' . $f['id'] . '/purge') ?>" data-title="<?= esc($f['title'], 'attr') ?>">Delete permanently</button>
          </div>
        </li>
      <?php endforeach; ?>
      </ul>
      <?= $pager->links('recycle') ?>
    <?php endif; ?>
  </section>
</div>
<div class="modal" id="purgeModal"><div class="modal-card"><h2>Delete permanently?</h2><p class="notice" id="purgeText"></p><form id="purgeForm" method="post"><?= csrf_field() ?><div class="modal-actions"><button type="button" class="secondary" id="purgeCancel">Cancel</button><button type="submit" class="danger-button">Delete forever</button></div></form></div></div>
<?= view('partials/theme_scripts') ?>
<script>
const modal=document.getElementById('purgeModal');document.addEventListener('click',e=>{const b=e.target.closest('.js-purge');if(b){document.getElementById('purgeForm').action=b.dataset.url;document.getElementById('purgeText').textContent='“'+b.dataset.title+'” will be removed from Supabase storage and the database.';modal.classList.add('open');}if(e.target===modal||e.target.id==='purgeCancel')modal.classList.remove('open');});document.addEventListener('keydown',e=>{if(e.key==='Escape')modal.classList.remove('open');});
</script>
</body>
</html>
