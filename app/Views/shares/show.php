<?php
use App\Models\ImportantFileModel;

$typeLabel  = ImportantFileModel::typeLabel((string) $file['mime_type'], (string) $file['original_filename']);
$previewUrl = $previewUrl ?? base_url('share/' . $token . '/preview');
$downloadUrl = $downloadUrl ?? base_url('share/' . $token . '/download');
$backUrl = $backUrl ?? null;
$sharedTitle = trim((string) ($share['share_title'] ?? '')) ?: (string) $file['title'];
$senderName  = trim((string) ($share['display_name'] ?? '')) ?: "Damon's Archive";
$kindLabel = match ($previewKind) {
    'image' => 'Image',
    'video' => 'Video',
    'audio' => 'Audio',
    'pdf'   => 'PDF document',
    'text'  => 'Text or code',
    default => 'Downloadable file',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="referrer" content="no-referrer">
<title><?= esc($sharedTitle) ?> · Shared File</title>
<?= view('partials/theme_head') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/shared-pages.v5.css') ?>">
<?= view('partials/retro_theme') ?>
</head>
<body class="sp-public-body">
<div class="twinkle-layer" id="twinkleLayer"></div>

<header class="sp-public-nav" aria-label="Shared link header">
  <div class="sp-public-brand">
    <span class="sp-brand-mark" aria-hidden="true">DA</span>
    <span class="sp-brand-copy"><strong>Damon's Archive</strong><small>Public share portal</small></span>
  </div>
  <div class="sp-secure-pill"><span aria-hidden="true"></span> Secure shared link</div>
</header>

<main class="sp-shell sp-single-shell">
  <?php if ($backUrl): ?><a class="sp-back-link" href="<?= esc($backUrl, 'attr') ?>"><span aria-hidden="true">←</span> Back to shared folder</a><?php endif; ?>

  <section class="sp-hero sp-file-hero" data-kind="<?= esc($previewKind, 'attr') ?>">
    <div class="sp-hero-topline" aria-hidden="true"><span>SHARED FILE</span><span>READ ACCESS</span></div>
    <div class="sp-hero-layout">
      <div class="sp-hero-main">
        <div class="sp-single-file-badge" data-kind="<?= esc($previewKind, 'attr') ?>"><span><?= esc($typeLabel) ?></span></div>
        <div class="sp-hero-copy">
          <p class="eyebrow">Shared by <?= esc($senderName) ?></p>
          <h1><?= esc($sharedTitle) ?></h1>
          <?php if ($sharedTitle !== $file['title']): ?><div class="sp-native-name"><span>Title</span><?= esc($file['title']) ?></div><?php endif; ?>
          <div class="sp-native-name"><span>File</span><?= esc($file['original_filename']) ?></div>
          <?php if (! empty($share['share_message'])): ?><div class="sp-message"><span class="sp-message-label">Message</span><p><?= nl2br(esc((string) $share['share_message'])) ?></p></div><?php endif; ?>
        </div>
      </div>
      <div class="sp-hero-actions sp-single-actions">
        <?php if ($previewKind !== 'unsupported'): ?><a class="sp-btn sp-btn-ghost" href="<?= esc($previewUrl, 'attr') ?>" target="_blank" rel="noopener"><span aria-hidden="true">↗</span> Open tab</a><?php endif; ?>
        <a class="sp-btn sp-btn-primary" href="<?= esc($downloadUrl, 'attr') ?>"><span aria-hidden="true">↓</span> Download file</a>
      </div>
    </div>

    <div class="sp-stat-grid sp-single-stats">
      <div class="sp-stat"><span class="sp-stat-icon" aria-hidden="true">TYPE</span><span><small>File type</small><strong><?= esc($kindLabel) ?></strong></span></div>
      <div class="sp-stat"><span class="sp-stat-icon" aria-hidden="true">MB</span><span><small>File size</small><strong><?= esc(ImportantFileModel::formatBytes((int) $file['file_size'])) ?></strong></span></div>
      <div class="sp-stat"><span class="sp-stat-icon" aria-hidden="true">⌁</span><span><small>Link access</small><strong><?= ! empty($share['expires_at']) ? 'Until ' . esc(date('M j, Y', strtotime((string) $share['expires_at']))) : 'No expiration' ?></strong></span></div>
      <div class="sp-stat"><span class="sp-stat-icon" aria-hidden="true">↓</span><span><small>Downloads</small><strong><?= ! empty($share['max_downloads']) ? (int) $share['download_count'] . ' of ' . (int) $share['max_downloads'] : 'Unlimited' ?></strong></span></div>
    </div>
  </section>

  <section class="sp-single-workspace">
    <div class="sp-single-preview-panel">
      <header class="sp-panel-head">
        <div><span class="sp-section-kicker">Browser viewer</span><h2>File preview</h2></div>
        <span class="sp-live-indicator"><i></i> Secure stream</span>
      </header>
      <div class="share-stage sp-preview-stage sp-single-stage" id="sharePreviewStage" data-kind="<?= esc($previewKind, 'attr') ?>" data-preview-url="<?= esc($previewUrl, 'attr') ?>" data-download-url="<?= esc($downloadUrl, 'attr') ?>" data-title="<?= esc($file['title'], 'attr') ?>" data-name="<?= esc($file['original_filename'], 'attr') ?>" data-id="<?= (int) $file['id'] ?>">
        <div class="sp-loader"><span class="sp-spinner"></span><span>Loading secure preview…</span></div>
      </div>
    </div>

    <aside class="sp-single-info-panel">
      <header class="sp-panel-head"><div><span class="sp-section-kicker">Metadata</span><h2>File details</h2></div></header>
      <dl class="sp-detail-list">
        <div><dt>Name</dt><dd><?= esc($file['original_filename']) ?></dd></div>
        <div><dt>Type</dt><dd><?= esc($typeLabel) ?><small><?= esc($file['mime_type']) ?></small></dd></div>
        <div><dt>Size</dt><dd><?= esc(ImportantFileModel::formatBytes((int) $file['file_size'])) ?></dd></div>
        <?php if (! empty($file['description'])): ?><div><dt>Description</dt><dd><?= nl2br(esc($file['description'])) ?></dd></div><?php endif; ?>
        <?php if (! empty($share['expires_at'])): ?><div><dt>Link expires</dt><dd><?= esc(date('M j, Y · g:i A', strtotime((string) $share['expires_at']))) ?></dd></div><?php endif; ?>
        <?php if (! empty($share['max_downloads'])): ?><div><dt>Download usage</dt><dd><?= (int) $share['download_count'] ?> of <?= (int) $share['max_downloads'] ?></dd></div><?php endif; ?>
      </dl>
      <div class="sp-side-actions">
        <a class="sp-btn sp-btn-primary" href="<?= esc($downloadUrl, 'attr') ?>"><span aria-hidden="true">↓</span> Download</a>
        <?php if ($previewKind !== 'unsupported'): ?><a class="sp-btn sp-btn-ghost" href="<?= esc($previewUrl, 'attr') ?>" target="_blank" rel="noopener"><span aria-hidden="true">↗</span> Open in new tab</a><?php endif; ?>
      </div>
      <div class="sp-info-note"><span class="sp-shield" aria-hidden="true">◆</span><span><strong>Private link access</strong><small>This page is available only through the shared link. The owner can disable access at any time.</small></span></div>
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
