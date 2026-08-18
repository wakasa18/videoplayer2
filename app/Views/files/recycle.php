<!DOCTYPE html>
<html lang="en">
<head>
<title>Recycle Bin · Important Files</title>
<?= view('partials/theme_head') ?>
<style>
  .top-links{display:flex;gap:9px;flex-wrap:wrap;margin:18px 0}.top-link{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text-dim);text-decoration:none;border:1px solid var(--hairline);padding:8px 10px;border-radius:6px;background:var(--surface)}.top-link:hover{color:var(--cyan);border-color:var(--cyan)}
  .notice{font-size:12px;color:var(--text-dim);line-height:1.6;margin:0 0 16px}.file-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:9px}.file-item{display:flex;align-items:center;gap:12px;background:var(--surface-2);border:1px solid var(--hairline);border-radius:8px;padding:12px}.badge-file{flex:none;width:45px;height:38px;display:flex;align-items:center;justify-content:center;border:1px solid var(--hairline);border-radius:6px;color:var(--red);font:700 9px 'JetBrains Mono',monospace}.file-meta{flex:1;min-width:0}.file-title{font-weight:600;font-size:14px}.file-sub{font:10px 'JetBrains Mono',monospace;color:var(--text-dim);margin-top:4px}.actions{display:flex;gap:7px;flex-wrap:wrap}.actions form{margin:0}.btn-small{padding:8px 10px;background:var(--surface);color:var(--text-dim);border:1px solid var(--hairline);font-size:11px}.btn-small:hover{color:var(--cyan);border-color:var(--cyan)}.btn-small.danger:hover{color:#ff9aa1;border-color:var(--red)}.empty{text-align:center;padding:34px;border:1px dashed var(--hairline);border-radius:8px;color:var(--text-dim)}
  .pagination{display:flex;gap:6px;list-style:none;padding:0;margin:18px 0 0;flex-wrap:wrap}.pagination a,.pagination span{display:block;padding:7px 10px;border:1px solid var(--hairline);border-radius:5px;color:var(--text-dim);text-decoration:none;font-size:12px}.pagination .active a,.pagination a:hover{color:var(--cyan);border-color:var(--cyan)}
  .modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;padding:18px;background:rgba(2,4,10,.82);z-index:100}.modal.open{display:flex}.modal-card{width:min(480px,100%);background:var(--surface);border:1px solid var(--hairline);border-radius:12px;padding:20px}.modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:18px}.modal-actions button{width:auto}.secondary{background:var(--surface-2);border:1px solid var(--hairline);color:var(--text)}.danger-button{background:var(--red);color:white}
  @media(max-width:650px){.file-item{align-items:flex-start;flex-wrap:wrap}.file-meta{min-width:calc(100% - 60px)}.actions{width:100%;padding-left:57px}}
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
