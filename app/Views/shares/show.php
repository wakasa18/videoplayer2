<?php
use App\Models\ImportantFileModel;
$typeLabel = ImportantFileModel::typeLabel((string) $file['mime_type'], (string) $file['original_filename']);
$previewUrl = $previewUrl ?? base_url('share/' . $token . '/preview');
$downloadUrl = $downloadUrl ?? base_url('share/' . $token . '/download');
$backUrl = $backUrl ?? null;
$sharedTitle = trim((string) ($share['share_title'] ?? '')) ?: (string) $file['title'];
$senderName = trim((string) ($share['display_name'] ?? '')) ?: "Damon's Archive";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="referrer" content="no-referrer">
<title><?= esc($sharedTitle) ?> · Shared File</title>
<?= view('partials/theme_head') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/shared-file.v3.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/shared-pages.v2.css') ?>">
<?= view('partials/retro_theme') ?>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<main class="shared-wrap">
  <header class="share-header">
    <div class="share-heading"><p class="eyebrow">Shared by <?= esc($senderName) ?></p><h1><?= esc($sharedTitle) ?></h1><?php if ($sharedTitle !== $file['title']): ?><div class="share-filename"><?= esc($file['title']) ?></div><?php endif; ?><div class="share-filename"><?= esc($file['original_filename']) ?></div><?php if (! empty($share['share_message'])): ?><p class="sp-message"><?= nl2br(esc((string) $share['share_message'])) ?></p><?php endif; ?></div>
    <div class="share-actions"><?php if ($backUrl): ?><a class="share-button secondary" href="<?= esc($backUrl, 'attr') ?>">Back to folder</a><?php endif; ?><a class="share-button secondary" href="<?= esc($previewKind !== 'unsupported' ? $previewUrl : $downloadUrl, 'attr') ?>" target="_blank" rel="noopener">Open tab</a><a class="share-button primary" href="<?= esc($downloadUrl, 'attr') ?>">Download</a></div>
  </header>

  <section class="share-grid">
    <div class="share-stage sp-preview-stage" id="sharePreviewStage" data-kind="<?= esc($previewKind, 'attr') ?>" data-preview-url="<?= esc($previewUrl, 'attr') ?>" data-download-url="<?= esc($downloadUrl, 'attr') ?>" data-title="<?= esc($file['title'], 'attr') ?>" data-name="<?= esc($file['original_filename'], 'attr') ?>" data-id="<?= (int) $file['id'] ?>">
      <div class="sp-loader"><span class="sp-spinner"></span><span>Loading secure preview…</span></div>
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
<script id="sharedFileConfig" type="application/json"><?= json_encode([
  'pdfModuleUrl' => base_url('share-assets/pdf.min.mjs'),
  'pdfWorkerUrl' => base_url('share-assets/pdf.worker.min.mjs'),
], JSON_UNESCAPED_SLASHES) ?></script>
<script src="<?= base_url('assets/js/shared-file.v4.js') ?>" defer></script>
<?= view('partials/theme_scripts') ?>
</body>
</html>
