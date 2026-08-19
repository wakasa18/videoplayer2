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
        'id' => (int) $file['id'],
        'title' => (string) $file['title'],
        'name' => (string) $file['original_filename'],
        'kind' => $kind,
        'mime' => (string) $file['mime_type'],
        'size' => (int) $file['file_size'],
        'sizeLabel' => ImportantFileModel::formatBytes((int) $file['file_size']),
        'description' => (string) ($file['description'] ?? ''),
        'previewUrl' => base_url('share/' . $token . '/file/' . $file['id'] . '/preview'),
        'downloadUrl' => base_url('share/' . $token . '/file/' . $file['id'] . '/download'),
    ];
}
$sharedTitle = trim((string) ($share['share_title'] ?? '')) ?: $currentName;
$senderName = trim((string) ($share['display_name'] ?? '')) ?: "Damon's Archive";
$hasFilters = ($filters['q'] ?? '') !== '' || ($filters['type'] ?? '') !== '' || ($filters['sort'] ?? 'name_asc') !== 'name_asc';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="referrer" content="no-referrer">
<title><?= esc($sharedTitle) ?> · Shared Folder</title>
<?= view('partials/theme_head') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/shared-pages.v2.css') ?>">
<?= view('partials/retro_theme') ?>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<main class="sp-shell">
  <section class="sp-hero">
    <div class="sp-hero-main">
      <div class="sp-hero-icon" aria-hidden="true">&#128193;</div>
      <div class="sp-hero-copy">
        <p class="eyebrow">Shared by <?= esc($senderName) ?></p>
        <h1><?= esc($sharedTitle) ?></h1>
        <?php if ($sharedTitle !== $currentName): ?><div class="sp-native-name"><?= esc($currentName) ?></div><?php endif; ?>
        <?php if (! empty($share['share_message'])): ?><p class="sp-message"><?= nl2br(esc((string) $share['share_message'])) ?></p><?php endif; ?>
      </div>
    </div>
    <div class="sp-hero-actions">
      <button type="button" class="sp-btn sp-btn-primary" id="downloadFolderBtn" data-path="<?= esc($relativePath, 'attr') ?>" data-name="<?= esc($currentName, 'attr') ?>">Download folder</button>
    </div>
    <div class="sp-stats">
      <span><?= number_format((int) $summary['files']) ?> files</span>
      <span><?= esc(ImportantFileModel::formatBytes((int) $summary['bytes'])) ?></span>
      <?php if (! empty($summary['last_updated'])): ?><span>Updated <?= esc(date('M j, Y', strtotime((string) $summary['last_updated']))) ?></span><?php endif; ?>
      <?php if (! empty($share['expires_at'])): ?><span>Expires <?= esc(date('M j, Y', strtotime((string) $share['expires_at']))) ?></span><?php else: ?><span>No expiration</span><?php endif; ?>
    </div>
  </section>

  <nav class="sp-crumbs" aria-label="Shared folder breadcrumb">
    <?php foreach ($breadcrumbs as $index => $crumb): ?>
      <?php if ($index > 0): ?><span class="sp-crumb-sep">/</span><?php endif; ?>
      <a class="sp-crumb <?= $index === count($breadcrumbs) - 1 ? 'current' : '' ?>" href="<?= esc($folderUrl((string) $crumb['path']), 'attr') ?>"><?= esc($index === 0 ? $rootName : $crumb['label']) ?></a>
    <?php endforeach; ?>
  </nav>

  <form class="sp-toolbar" method="get" action="<?= esc(base_url('share/' . $token), 'attr') ?>" id="sharedFilterForm">
    <?php if ($relativePath !== ''): ?><input type="hidden" name="path" value="<?= esc($relativePath, 'attr') ?>"><?php endif; ?>
    <label class="sp-search"><span class="sr-only">Search files and folders</span><input type="search" name="q" id="sharedSearch" value="<?= esc((string) ($filters['q'] ?? ''), 'attr') ?>" placeholder="Search this folder"></label>
    <label><span class="sr-only">File type</span><select name="type" id="sharedType">
      <option value="">All file types</option>
      <?php foreach (['image'=>'Images','video'=>'Videos','audio'=>'Audio','pdf'=>'PDF','text'=>'Text & code','archive'=>'Archives','other'=>'Other'] as $value => $label): ?><option value="<?= $value ?>" <?= ($filters['type'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
    </select></label>
    <label><span class="sr-only">Sort</span><select name="sort" id="sharedSort">
      <?php foreach (['name_asc'=>'Name','newest'=>'Newest','oldest'=>'Oldest','largest'=>'Largest','smallest'=>'Smallest'] as $value => $label): ?><option value="<?= $value ?>" <?= ($filters['sort'] ?? 'name_asc') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
    </select></label>
    <label><span class="sr-only">Items per page</span><select name="per_page" id="sharedPerPage"><?php foreach ([20,30,50,100] as $size): ?><option value="<?= $size ?>" <?= (int) $perPage === $size ? 'selected' : '' ?>><?= $size ?>/page</option><?php endforeach; ?></select></label>
    <button type="submit" class="sp-btn">Apply</button>
    <?php if ($hasFilters): ?><a class="sp-btn sp-btn-ghost" href="<?= esc($folderUrl($relativePath), 'attr') ?>">Clear</a><?php endif; ?>
    <div class="sp-view-switch" role="group" aria-label="View style"><button type="button" data-view="list" class="sp-view-btn active" aria-label="List view">&#9776;</button><button type="button" data-view="grid" class="sp-view-btn" aria-label="Grid view">&#9638;</button></div>
  </form>

  <div class="sp-selection-bar" id="selectionBar" hidden>
    <div><strong id="selectionCount">0 selected</strong><span id="selectionSize">0 B</span></div>
    <div><button type="button" class="sp-btn sp-btn-ghost" id="clearSelectionBtn">Clear</button><button type="button" class="sp-btn sp-btn-primary" id="downloadSelectedBtn">Download selected</button></div>
  </div>

  <div class="sp-download-status" id="downloadStatus" hidden role="status" aria-live="polite">
    <div class="sp-download-head"><div><strong id="downloadTitle">Preparing download</strong><span id="downloadCurrent">Creating secure file list…</span></div><button type="button" class="sp-btn sp-btn-ghost" id="downloadCancel">Cancel</button></div>
    <div class="sp-progress"><span id="downloadBar"></span></div>
    <div class="sp-download-meta"><span id="downloadFiles">0 files</span><span id="downloadBytes">0 B</span></div>
  </div>

  <?php if ($folders !== []): ?>
  <section class="sp-section">
    <div class="sp-section-head"><h2>Folders</h2><span><?= count($folders) ?></span></div>
    <div class="sp-folder-grid">
      <?php foreach ($folders as $folder): ?>
      <article class="sp-folder-card">
        <a class="sp-folder-link" href="<?= esc($folderUrl((string) $folder['relativePath']), 'attr') ?>">
          <span class="sp-folder-icon" aria-hidden="true">&#128193;</span>
          <span class="sp-folder-copy"><strong><?= esc($folder['name']) ?></strong><small><?= number_format((int) $folder['count']) ?> files · <?= esc(ImportantFileModel::formatBytes((int) $folder['bytes'])) ?></small><small><?php $types=[]; if($folder['image_count'])$types[]=$folder['image_count'].' images'; if($folder['video_count'])$types[]=$folder['video_count'].' videos'; if($folder['pdf_count'])$types[]=$folder['pdf_count'].' PDFs'; echo esc(implode(' · ', array_slice($types,0,2)) ?: 'Mixed files'); ?><?php if(!empty($folder['last_updated'])): ?> · updated <?= esc(date('M j, Y', strtotime((string)$folder['last_updated']))) ?><?php endif; ?></small></span>
          <span class="sp-folder-arrow">&#8594;</span>
        </a>
        <button type="button" class="sp-folder-download js-download-subfolder" data-path="<?= esc($folder['relativePath'], 'attr') ?>" data-name="<?= esc($folder['name'], 'attr') ?>" aria-label="Download <?= esc($folder['name'], 'attr') ?>">&#8681;</button>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="sp-section">
    <div class="sp-section-head"><h2>Files</h2><div class="sp-section-actions"><span><?= number_format((int) ($pager->getTotal('shared_files') ?? count($files))) ?> result<?= (int) ($pager->getTotal('shared_files') ?? count($files)) === 1 ? '' : 's' ?></span><?php if ($files !== []): ?><button type="button" class="sp-mini-action" id="selectPageBtn">Select page</button><?php endif; ?></div></div>
    <?php if ($files === []): ?><div class="sp-empty"><strong>No files found</strong><p>Try clearing the filters or opening another folder.</p></div>
    <?php else: ?>
    <div class="sp-file-list" id="sharedFileList" data-view="list">
      <?php foreach ($files as $file): $kind=ImportantFileModel::previewKind($file); $type=ImportantFileModel::typeLabel((string)$file['mime_type'],(string)$file['original_filename']); ?>
      <article class="sp-file-row" data-file-id="<?= (int) $file['id'] ?>" tabindex="0">
        <label class="sp-check"><input type="checkbox" class="js-file-select" value="<?= (int) $file['id'] ?>" data-size="<?= (int) $file['file_size'] ?>"><span></span></label>
        <button type="button" class="sp-file-open js-open-preview" data-file-id="<?= (int) $file['id'] ?>" aria-label="Open <?= esc($file['title'], 'attr') ?>">
          <span class="sp-file-badge"><?= esc($type) ?></span>
          <span class="sp-file-copy"><strong><?= esc($file['title']) ?></strong><small><?= esc($file['original_filename']) ?></small><small><?= esc(ImportantFileModel::formatBytes((int)$file['file_size'])) ?><?php if(!empty($file['created_at'])): ?> · <?= esc(date('M j, Y', strtotime((string)$file['created_at']))) ?><?php endif; ?></small></span>
        </button>
        <div class="sp-file-actions"><button type="button" class="sp-btn js-open-preview" data-file-id="<?= (int) $file['id'] ?>">Open</button><a class="sp-btn sp-btn-ghost" href="<?= esc(base_url('share/'.$token.'/file/'.$file['id'].'/download'),'attr') ?>">Download</a></div>
        <details class="sp-mobile-menu"><summary aria-label="File actions">&#8942;</summary><div><button type="button" class="js-open-preview" data-file-id="<?= (int) $file['id'] ?>">Open preview</button><a href="<?= esc(base_url('share/'.$token.'/file/'.$file['id'].'/download'),'attr') ?>">Download</a></div></details>
      </article>
      <?php endforeach; ?>
    </div>
    <div class="sp-pagination"><?= $pager->links('shared_files') ?></div>
    <?php endif; ?>
  </section>

  <aside class="sp-security-note">Anyone with this link can browse the shared folder. The owner can disable the link at any time.</aside>
</main>

<div class="sp-modal" id="previewModal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="previewTitle">
  <div class="sp-modal-card sp-preview-card">
    <header class="sp-preview-head"><div class="sp-preview-nav"><button type="button" id="previewPrev" aria-label="Previous file">&#8592;</button><span id="previewPosition"></span><button type="button" id="previewNext" aria-label="Next file">&#8594;</button></div><div class="sp-preview-title"><strong id="previewTitle">Preview</strong><span id="previewFilename"></span></div><a id="previewDownload" class="sp-btn sp-btn-ghost" href="#">Download</a><button type="button" class="sp-modal-close" data-close-preview aria-label="Close preview">&#10005;</button></header>
    <div class="sp-preview-body"><div class="sp-preview-stage" id="previewStage"></div><aside class="sp-preview-info" id="previewInfo"></aside></div>
  </div>
</div>

<script id="sharedFolderConfig" type="application/json"><?= json_encode([
  'files' => $pageFiles,
  'folderManifestUrl' => base_url('share/'.$token.'/folder-manifest'),
  'selectionManifestUrl' => base_url('share/'.$token.'/selection-manifest'),
  'currentPath' => $relativePath,
  'pdfModuleUrl' => base_url('share-assets/pdf.min.mjs'),
  'pdfWorkerUrl' => base_url('share-assets/pdf.worker.min.mjs'),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?></script>
<script src="<?= base_url('assets/js/shared-folder.v2.js') ?>" defer></script>
<?= view('partials/theme_scripts') ?>
</body>
</html>
