<?php
use App\Models\ImportantFileModel;

$folderUrl = static function (string $path, array $extra = []) use ($token): string {
    $query = $extra;
    if ($path !== '') {
        $query['path'] = $path;
    }

    return base_url('share/' . $token) . ($query ? '?' . http_build_query($query) : '');
};

$pageFiles = [];
foreach ($files as $file) {
    $kind = ImportantFileModel::previewKind($file);
    $pageFiles[] = [
        'id'          => (int) $file['id'],
        'title'       => (string) $file['title'],
        'name'        => (string) $file['original_filename'],
        'kind'        => $kind,
        'mime'        => (string) $file['mime_type'],
        'size'        => (int) $file['file_size'],
        'sizeLabel'   => ImportantFileModel::formatBytes((int) $file['file_size']),
        'description' => (string) ($file['description'] ?? ''),
        'previewUrl'  => base_url('share/' . $token . '/file/' . $file['id'] . '/preview'),
        'downloadUrl' => base_url('share/' . $token . '/file/' . $file['id'] . '/download'),
    ];
}

$sharedTitle = trim((string) ($share['share_title'] ?? '')) ?: $currentName;
$senderName  = trim((string) ($share['display_name'] ?? '')) ?: "Damon's Archive";
$hasFilters  = ($filters['q'] ?? '') !== ''
    || ($filters['type'] ?? '') !== ''
    || ($filters['sort'] ?? 'name_asc') !== 'name_asc';
$totalResults = (int) ($pager->getTotal('shared_files') ?? count($files));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="referrer" content="no-referrer">
<title><?= esc($sharedTitle) ?> · Shared Folder</title>
<?= view('partials/theme_head') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/shared-pages.v3.css') ?>">
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

<main class="sp-shell sp-folder-shell">
  <section class="sp-hero sp-folder-hero">
    <div class="sp-hero-topline" aria-hidden="true"><span>SHARED DIRECTORY</span><span>READ ACCESS</span></div>
    <div class="sp-hero-layout">
      <div class="sp-hero-main">
        <div class="sp-hero-icon sp-folder-hero-icon" aria-hidden="true"><span></span></div>
        <div class="sp-hero-copy">
          <p class="eyebrow">Shared by <?= esc($senderName) ?></p>
          <h1><?= esc($sharedTitle) ?></h1>
          <?php if ($sharedTitle !== $currentName): ?>
            <div class="sp-native-name"><span>Folder</span><?= esc($currentName) ?></div>
          <?php endif; ?>
          <?php if (! empty($share['share_message'])): ?>
            <div class="sp-message"><span class="sp-message-label">Message</span><p><?= nl2br(esc((string) $share['share_message'])) ?></p></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="sp-hero-actions">
        <button type="button" class="sp-btn sp-btn-primary sp-download-main" id="downloadFolderBtn" data-path="<?= esc($relativePath, 'attr') ?>" data-name="<?= esc($currentName, 'attr') ?>">
          <span class="sp-btn-icon" aria-hidden="true">↓</span>
          <span><strong>Download folder</strong><small>Save as ZIP archive</small></span>
        </button>
      </div>
    </div>

    <div class="sp-stat-grid" aria-label="Shared folder summary">
      <div class="sp-stat"><span class="sp-stat-icon" aria-hidden="true">#</span><span><small>Total files</small><strong><?= number_format((int) $summary['files']) ?></strong></span></div>
      <div class="sp-stat"><span class="sp-stat-icon" aria-hidden="true">MB</span><span><small>Storage size</small><strong><?= esc(ImportantFileModel::formatBytes((int) $summary['bytes'])) ?></strong></span></div>
      <div class="sp-stat"><span class="sp-stat-icon" aria-hidden="true">↻</span><span><small>Last updated</small><strong><?= ! empty($summary['last_updated']) ? esc(date('M j, Y', strtotime((string) $summary['last_updated']))) : 'Not available' ?></strong></span></div>
      <div class="sp-stat"><span class="sp-stat-icon" aria-hidden="true">⌁</span><span><small>Link access</small><strong><?= ! empty($share['expires_at']) ? 'Until ' . esc(date('M j, Y', strtotime((string) $share['expires_at']))) : 'No expiration' ?></strong></span></div>
    </div>
  </section>

  <div class="sp-pathbar">
    <div class="sp-path-label"><span aria-hidden="true">⌂</span> Current location</div>
    <nav class="sp-crumbs" aria-label="Shared folder breadcrumb">
      <?php foreach ($breadcrumbs as $index => $crumb): ?>
        <?php if ($index > 0): ?><span class="sp-crumb-sep" aria-hidden="true">›</span><?php endif; ?>
        <a class="sp-crumb <?= $index === count($breadcrumbs) - 1 ? 'current' : '' ?>" <?= $index === count($breadcrumbs) - 1 ? 'aria-current="page"' : '' ?> href="<?= esc($folderUrl((string) $crumb['path']), 'attr') ?>"><?= esc($index === 0 ? $rootName : $crumb['label']) ?></a>
      <?php endforeach; ?>
    </nav>
  </div>

  <form class="sp-toolbar" method="get" action="<?= esc(base_url('share/' . $token), 'attr') ?>" id="sharedFilterForm">
    <?php if ($relativePath !== ''): ?><input type="hidden" name="path" value="<?= esc($relativePath, 'attr') ?>"><?php endif; ?>
    <div class="sp-toolbar-search">
      <label class="sp-search">
        <span class="sp-search-icon" aria-hidden="true">⌕</span>
        <span class="sr-only">Search files and folders</span>
        <input type="search" name="q" id="sharedSearch" value="<?= esc((string) ($filters['q'] ?? ''), 'attr') ?>" placeholder="Search files and folders">
      </label>
      <div class="sp-view-switch" role="group" aria-label="View style">
        <button type="button" data-view="list" class="sp-view-btn active" aria-label="List view" title="List view"><span aria-hidden="true">☷</span></button>
        <button type="button" data-view="grid" class="sp-view-btn" aria-label="Grid view" title="Grid view"><span aria-hidden="true">▦</span></button>
      </div>
    </div>
    <div class="sp-toolbar-filters">
      <label class="sp-filter-field"><span>File type</span><select name="type" id="sharedType">
        <option value="">All file types</option>
        <?php foreach (['image' => 'Images', 'video' => 'Videos', 'audio' => 'Audio', 'pdf' => 'PDF', 'text' => 'Text & code', 'archive' => 'Archives', 'other' => 'Other'] as $value => $label): ?>
          <option value="<?= $value ?>" <?= ($filters['type'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select></label>
      <label class="sp-filter-field"><span>Sort by</span><select name="sort" id="sharedSort">
        <?php foreach (['name_asc' => 'Name', 'newest' => 'Newest', 'oldest' => 'Oldest', 'largest' => 'Largest', 'smallest' => 'Smallest'] as $value => $label): ?>
          <option value="<?= $value ?>" <?= ($filters['sort'] ?? 'name_asc') === $value ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select></label>
      <label class="sp-filter-field sp-page-size"><span>Show</span><select name="per_page" id="sharedPerPage">
        <?php foreach ([20, 30, 50, 100] as $size): ?><option value="<?= $size ?>" <?= (int) $perPage === $size ? 'selected' : '' ?>><?= $size ?> items</option><?php endforeach; ?>
      </select></label>
      <div class="sp-toolbar-actions">
        <button type="submit" class="sp-btn"><span aria-hidden="true">✓</span> Apply</button>
        <?php if ($hasFilters): ?><a class="sp-btn sp-btn-ghost" href="<?= esc($folderUrl($relativePath), 'attr') ?>"><span aria-hidden="true">×</span> Clear</a><?php endif; ?>
      </div>
    </div>
  </form>

  <div class="sp-selection-bar" id="selectionBar" hidden>
    <div class="sp-selection-copy"><span class="sp-selection-icon" aria-hidden="true">✓</span><span><strong id="selectionCount">0 selected</strong><small id="selectionSize">0 B</small></span></div>
    <div class="sp-selection-actions"><button type="button" class="sp-btn sp-btn-ghost" id="clearSelectionBtn">Clear</button><button type="button" class="sp-btn sp-btn-primary" id="downloadSelectedBtn"><span aria-hidden="true">↓</span> Download selected</button></div>
  </div>

  <div class="sp-download-status" id="downloadStatus" hidden role="status" aria-live="polite">
    <div class="sp-download-head">
      <div class="sp-download-copy"><span class="sp-download-pulse" aria-hidden="true"></span><span><strong id="downloadTitle">Preparing download</strong><small id="downloadCurrent">Creating secure file list…</small></span></div>
      <button type="button" class="sp-btn sp-btn-ghost" id="downloadCancel">Cancel</button>
    </div>
    <div class="sp-progress"><span id="downloadBar"></span></div>
    <div class="sp-download-meta"><span id="downloadFiles">0 files</span><span id="downloadBytes">0 B</span></div>
  </div>

  <?php if ($folders !== []): ?>
  <section class="sp-section sp-content-panel">
    <div class="sp-section-head">
      <div><span class="sp-section-kicker">Directory</span><h2>Folders</h2></div>
      <span class="sp-count-badge"><?= count($folders) ?> folder<?= count($folders) === 1 ? '' : 's' ?></span>
    </div>
    <div class="sp-folder-grid">
      <?php foreach ($folders as $folder): ?>
      <article class="sp-folder-card">
        <a class="sp-folder-link" href="<?= esc($folderUrl((string) $folder['relativePath']), 'attr') ?>">
          <span class="sp-folder-glyph" aria-hidden="true"><span></span></span>
          <span class="sp-folder-copy">
            <strong><?= esc($folder['name']) ?></strong>
            <small><?= number_format((int) $folder['count']) ?> files · <?= esc(ImportantFileModel::formatBytes((int) $folder['bytes'])) ?></small>
            <small class="sp-folder-detail"><?php
              $types = [];
              if ($folder['image_count']) $types[] = $folder['image_count'] . ' images';
              if ($folder['video_count']) $types[] = $folder['video_count'] . ' videos';
              if ($folder['pdf_count']) $types[] = $folder['pdf_count'] . ' PDFs';
              echo esc(implode(' · ', array_slice($types, 0, 2)) ?: 'Mixed files');
            ?><?php if (! empty($folder['last_updated'])): ?> · <?= esc(date('M j, Y', strtotime((string) $folder['last_updated']))) ?><?php endif; ?></small>
          </span>
          <span class="sp-folder-arrow" aria-hidden="true">→</span>
        </a>
        <button type="button" class="sp-folder-download js-download-subfolder" data-path="<?= esc($folder['relativePath'], 'attr') ?>" data-name="<?= esc($folder['name'], 'attr') ?>" aria-label="Download <?= esc($folder['name'], 'attr') ?>" title="Download folder"><span aria-hidden="true">↓</span></button>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="sp-section sp-content-panel">
    <div class="sp-section-head">
      <div><span class="sp-section-kicker">Shared content</span><h2>Files</h2></div>
      <div class="sp-section-actions"><span class="sp-count-badge"><?= number_format($totalResults) ?> result<?= $totalResults === 1 ? '' : 's' ?></span><?php if ($files !== []): ?><button type="button" class="sp-mini-action" id="selectPageBtn">Select page</button><?php endif; ?></div>
    </div>

    <?php if ($files === []): ?>
      <div class="sp-empty"><span class="sp-empty-icon" aria-hidden="true">⌕</span><strong>No files found</strong><p>Try a different search, clear the filters, or open another folder.</p><?php if ($hasFilters): ?><a class="sp-btn sp-btn-ghost" href="<?= esc($folderUrl($relativePath), 'attr') ?>">Clear filters</a><?php endif; ?></div>
    <?php else: ?>
    <div class="sp-file-list" id="sharedFileList" data-view="list">
      <?php foreach ($files as $file):
        $kind = ImportantFileModel::previewKind($file);
        $type = ImportantFileModel::typeLabel((string) $file['mime_type'], (string) $file['original_filename']);
      ?>
      <article class="sp-file-row" data-file-id="<?= (int) $file['id'] ?>" data-kind="<?= esc($kind, 'attr') ?>" tabindex="0">
        <label class="sp-check" title="Select <?= esc($file['title'], 'attr') ?>"><input type="checkbox" class="js-file-select" value="<?= (int) $file['id'] ?>" data-size="<?= (int) $file['file_size'] ?>"><span></span></label>
        <button type="button" class="sp-file-open js-open-preview" data-file-id="<?= (int) $file['id'] ?>" aria-label="Open <?= esc($file['title'], 'attr') ?>">
          <span class="sp-file-badge" data-kind="<?= esc($kind, 'attr') ?>"><?= esc($type) ?></span>
          <span class="sp-file-copy">
            <strong><?= esc($file['title']) ?></strong>
            <small><?= esc($file['original_filename']) ?></small>
            <small class="sp-file-meta-line"><span><?= esc(ImportantFileModel::formatBytes((int) $file['file_size'])) ?></span><?php if (! empty($file['created_at'])): ?><span><?= esc(date('M j, Y', strtotime((string) $file['created_at']))) ?></span><?php endif; ?></small>
          </span>
        </button>
        <div class="sp-file-actions"><button type="button" class="sp-btn js-open-preview" data-file-id="<?= (int) $file['id'] ?>"><span aria-hidden="true">◇</span> Preview</button><a class="sp-btn sp-btn-ghost" href="<?= esc(base_url('share/' . $token . '/file/' . $file['id'] . '/download'), 'attr') ?>"><span aria-hidden="true">↓</span> Download</a></div>
        <details class="sp-mobile-menu"><summary aria-label="File actions">•••</summary><div><button type="button" class="js-open-preview" data-file-id="<?= (int) $file['id'] ?>">Open preview</button><a href="<?= esc(base_url('share/' . $token . '/file/' . $file['id'] . '/download'), 'attr') ?>">Download</a></div></details>
      </article>
      <?php endforeach; ?>
    </div>
    <div class="sp-pagination"><?= $pager->links('shared_files') ?></div>
    <?php endif; ?>
  </section>

  <footer class="sp-security-note"><span class="sp-shield" aria-hidden="true">◆</span><span><strong>Secure shared access</strong><small>Anyone with this link can browse the shared folder. The owner can disable the link at any time.</small></span></footer>
</main>

<div class="sp-modal" id="previewModal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="previewTitle">
  <div class="sp-modal-card sp-preview-card">
    <header class="sp-preview-head">
      <div class="sp-preview-nav"><button type="button" id="previewPrev" aria-label="Previous file">←</button><span id="previewPosition"></span><button type="button" id="previewNext" aria-label="Next file">→</button></div>
      <div class="sp-preview-title"><span class="sp-preview-kicker">Secure preview</span><strong id="previewTitle">Preview</strong><span id="previewFilename"></span></div>
      <a id="previewDownload" class="sp-btn sp-btn-primary" href="#"><span aria-hidden="true">↓</span> Download</a>
      <button type="button" class="sp-modal-close" data-close-preview aria-label="Close preview">×</button>
    </header>
    <div class="sp-preview-body"><div class="sp-preview-stage" id="previewStage"></div><aside class="sp-preview-info" id="previewInfo"></aside></div>
  </div>
</div>

<script id="sharedFolderConfig" type="application/json"><?= json_encode([
    'files'                => $pageFiles,
    'folderManifestUrl'    => base_url('share/' . $token . '/folder-manifest'),
    'selectionManifestUrl' => base_url('share/' . $token . '/selection-manifest'),
    'currentPath'          => $relativePath,
    'pdfModuleUrl'         => base_url('share-assets/pdf.min.mjs'),
    'pdfWorkerUrl'         => base_url('share-assets/pdf.worker.min.mjs'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<script src="<?= base_url('assets/js/shared-folder.v2.js') ?>" defer></script>
<?= view('partials/theme_scripts') ?>
</body>
</html>
