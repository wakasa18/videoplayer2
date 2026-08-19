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
<link rel="stylesheet" href="<?= base_url('assets/css/shared-file.v3.css') ?>">
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
    <div class="share-stage" id="sharePreviewStage" data-kind="<?= esc($previewKind, 'attr') ?>" data-preview-url="<?= esc($previewUrl, 'attr') ?>" data-download-url="<?= esc($downloadUrl, 'attr') ?>" data-title="<?= esc($file['title'], 'attr') ?>">
      <?php if ($previewKind === 'image'): ?><img src="<?= esc($previewUrl, 'attr') ?>" alt="<?= esc($file['title'], 'attr') ?>">
      <?php elseif ($previewKind === 'video'): ?><video controls preload="metadata" src="<?= esc($previewUrl, 'attr') ?>"></video>
      <?php elseif ($previewKind === 'audio'): ?><audio controls preload="metadata" src="<?= esc($previewUrl, 'attr') ?>"></audio>
      <?php elseif ($previewKind === 'pdf' || $previewKind === 'text'): ?><div class="share-preview-loader" id="sharePreviewLoader"><span class="share-preview-spinner"></span><span>Loading secure preview…</span></div>
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

<?php if ($previewKind === 'pdf' || $previewKind === 'text'): ?><script src="<?= base_url('assets/js/shared-file.v3.js') ?>" defer></script><?php endif; ?>
<?= view('partials/theme_scripts') ?>
</body>
</html>
