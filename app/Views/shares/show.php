<?php
use App\Models\ImportantFileModel;

$typeLabel = ImportantFileModel::typeLabel((string) $file['mime_type'], (string) $file['original_filename']);
$previewUrl = $previewUrl ?? base_url('share/' . $token . '/preview');
$downloadUrl = $downloadUrl ?? base_url('share/' . $token . '/download');
$backUrl = $backUrl ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="referrer" content="no-referrer">
<title><?= esc($file['title']) ?> · Shared File</title>
<?= view('partials/theme_head') ?>
<style>
  .shared-wrap{max-width:1180px;margin:0 auto;padding:30px 20px 50px}.share-header{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:16px}.share-heading{min-width:0}.share-heading .eyebrow{margin-bottom:6px}.share-heading h1{font-size:34px;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.share-filename{font:11px 'JetBrains Mono',monospace;color:var(--text-dim);word-break:break-word}.share-actions{display:flex;gap:8px;flex:none}.share-button{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border-radius:7px;text-decoration:none;font-size:12px;font-weight:700}.share-button.primary{background:var(--cyan);color:#061019}.share-button.secondary{border:1px solid var(--hairline);color:var(--text-dim);background:var(--surface)}.share-grid{display:grid;grid-template-columns:minmax(0,1fr) 290px;min-height:70vh;border:1px solid var(--hairline);border-radius:12px;overflow:hidden;background:var(--surface);box-shadow:0 22px 70px rgba(0,0,0,.45)}.share-stage{display:flex;align-items:center;justify-content:center;min-width:0;min-height:620px;background:#080c15;overflow:auto}.share-stage iframe{width:100%;height:100%;min-height:620px;border:0;background:white}.share-stage img,.share-stage video{display:block;max-width:100%;max-height:75vh;object-fit:contain}.share-stage audio{width:min(650px,85%)}.share-fallback{text-align:center;padding:30px;color:var(--text-dim)}.share-file-icon{width:110px;height:126px;margin:0 auto 18px;border:1px solid var(--hairline);border-radius:13px;background:var(--surface-2);display:grid;place-items:center;font:700 18px 'JetBrains Mono',monospace;color:var(--cyan)}.share-fallback h2{color:var(--text);margin:0 0 8px}.share-fallback p{max-width:460px;line-height:1.6}.share-info{border-left:1px solid var(--hairline);background:#101626;padding:20px;overflow:auto}.share-info h2{font-size:13px;text-transform:uppercase;letter-spacing:.1em;color:var(--text-dim);margin:0 0 18px}.share-row{margin-bottom:15px}.share-label{display:block;font:9px 'JetBrains Mono',monospace;color:var(--text-dim);text-transform:uppercase;letter-spacing:.1em;margin-bottom:4px}.share-value{font-size:12px;line-height:1.55;word-break:break-word}.share-note{margin-top:18px;padding:11px;border:1px solid rgba(95,217,232,.25);border-radius:7px;background:rgba(95,217,232,.06);font-size:11px;color:var(--text-dim);line-height:1.55}@media(max-width:800px){.share-header{display:block}.share-actions{margin-top:14px}.share-grid{grid-template-columns:1fr}.share-info{border-left:0;border-top:1px solid var(--hairline)}.share-stage,.share-stage iframe{min-height:62vh}}@media(max-width:520px){.share-heading h1{font-size:28px}.share-actions{display:grid;grid-template-columns:1fr 1fr}.share-button{padding:10px 8px}}
</style>
<?= view('partials/retro_theme') ?>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<main class="shared-wrap">
  <header class="share-header">
    <div class="share-heading"><p class="eyebrow">Shared from Damon's Archive</p><h1><?= esc($file['title']) ?></h1><div class="share-filename"><?= esc($file['original_filename']) ?></div></div>
    <div class="share-actions"><?php if ($backUrl): ?><a class="share-button secondary" href="<?= esc($backUrl, 'attr') ?>">Back to folder</a><?php endif; ?><a class="share-button secondary" href="<?= esc($previewKind !== 'unsupported' ? $previewUrl : $downloadUrl, 'attr') ?>" target="_blank" rel="noopener">Open tab</a><a class="share-button primary" href="<?= esc($downloadUrl, 'attr') ?>">Download</a></div>
  </header>

  <section class="share-grid">
    <div class="share-stage">
      <?php if ($previewKind === 'image'): ?><img src="<?= esc($previewUrl, 'attr') ?>" alt="<?= esc($file['title'], 'attr') ?>">
      <?php elseif ($previewKind === 'video'): ?><video controls preload="metadata" src="<?= esc($previewUrl, 'attr') ?>"></video>
      <?php elseif ($previewKind === 'audio'): ?><audio controls preload="metadata" src="<?= esc($previewUrl, 'attr') ?>"></audio>
      <?php elseif ($previewKind === 'pdf' || $previewKind === 'text'): ?><iframe src="<?= esc($previewUrl, 'attr') ?>" title="Preview of <?= esc($file['title'], 'attr') ?>"></iframe>
      <?php else: ?><div class="share-fallback"><div class="share-file-icon"><?= esc($typeLabel) ?></div><h2>Preview unavailable</h2><p>This file is ready to download, but your browser cannot preview this format.</p><a class="share-button primary" href="<?= esc($downloadUrl, 'attr') ?>">Download file</a></div><?php endif; ?>
    </div>
    <aside class="share-info">
      <h2>File details</h2>
      <div class="share-row"><span class="share-label">Name</span><div class="share-value"><?= esc($file['original_filename']) ?></div></div>
      <div class="share-row"><span class="share-label">Type</span><div class="share-value"><?= esc($typeLabel) ?> · <?= esc($file['mime_type']) ?></div></div>
      <div class="share-row"><span class="share-label">Size</span><div class="share-value"><?= esc(ImportantFileModel::formatBytes((int) $file['file_size'])) ?></div></div>
      <?php if (! empty($file['description'])): ?><div class="share-row"><span class="share-label">Description</span><div class="share-value"><?= nl2br(esc($file['description'])) ?></div></div><?php endif; ?>
      <?php if (! empty($share['expires_at'])): ?><div class="share-row"><span class="share-label">Link expires</span><div class="share-value"><?= esc(date('M j, Y · g:i A', strtotime((string) $share['expires_at']))) ?></div></div><?php endif; ?>
      <?php if (! empty($share['max_downloads'])): ?><div class="share-row"><span class="share-label">Downloads</span><div class="share-value"><?= (int) $share['download_count'] ?> of <?= (int) $share['max_downloads'] ?></div></div><?php endif; ?>
      <div class="share-note">Anyone with the share link can open or download this file until the owner disables the link, it expires, or its download limit is reached.</div>
    </aside>
  </section>
</main>
<?= view('partials/theme_scripts') ?>
</body>
</html>
