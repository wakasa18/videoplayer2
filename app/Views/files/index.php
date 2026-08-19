<?php
$pageTotal   = (int) ($page['total'] ?? count($files));
$pageCurrent = max(1, (int) ($page['currentPage'] ?? 1));
$pagePerPage = max(1, (int) ($page['perPage'] ?? 20));
$pageFrom    = $pageTotal > 0 ? (($pageCurrent - 1) * $pagePerPage) + 1 : 0;
$pageTo      = $pageTotal > 0 ? min($pageTotal, $pageCurrent * $pagePerPage) : 0;
$clearParams = [];
if ($currentPath) {
    $clearParams['path'] = $currentPath;
}
if (($filters['favorite'] ?? '') === '1') {
    $clearParams['favorite'] = '1';
}
if (($filters['sort'] ?? 'name_asc') !== 'name_asc') {
    $clearParams['sort'] = $filters['sort'];
}
$clearFilterUrl = base_url('files') . ($clearParams ? '?' . http_build_query($clearParams) : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Important Files · Damon's Archive</title>
<?= view('partials/theme_head') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/vault.v6.css') ?>">
<?= view('partials/retro_theme') ?>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<div class="wrap">
  <a href="<?= base_url('/') ?>" class="nav-back">&larr; Home</a>
  <header><p class="eyebrow">Restricted Archive · Sector 04</p><h1>Important Files</h1><div class="starline"></div></header>

  <?php if (session()->getFlashdata('error')): ?><div class="flash error" role="alert"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
  <?php if (session()->getFlashdata('success')): ?><div class="flash success" role="status"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>

  <div class="vault-toolbar">
    <div class="toolbar-links"><a class="toolbar-link <?= empty($filters['favorite']) ? 'active' : '' ?>" href="<?= base_url('files') ?>">My Drive</a><a class="toolbar-link <?= $filters['favorite'] === '1' ? 'active' : '' ?>" href="<?= base_url('files') . '?favorite=1' ?>">Favorites</a><a class="toolbar-link" href="<?= base_url('files/recycle') ?>">Recycle Bin</a><a class="toolbar-link" href="<?= base_url('files/activity') ?>">Activity Log</a></div>
    <form class="lock-form" action="<?= base_url('files/lock') ?>" method="post"><?= csrf_field() ?><button class="lock-link" type="submit">Lock vault</button></form>
  </div>

  <div class="summary-strip">
    <div class="summary-card"><div class="summary-label">Active files</div><div class="summary-value" id="summaryCount"><?= (int) $summary['file_count'] ?></div></div>
    <div class="summary-card"><div class="summary-label">Storage used</div><div class="summary-value" id="summaryBytes" data-bytes="<?= (int) $summary['total_bytes'] ?>"><?= \App\Models\ImportantFileModel::formatBytes((int) $summary['total_bytes']) ?></div></div>
    <div class="summary-card"><div class="summary-label">Upload limit</div><div class="summary-value"><?= (int) $maxMb ?> MB</div></div>
  </div>

  <div class="drive-bar">
    <nav class="breadcrumbs" aria-label="Folder location">
      <a class="breadcrumb-link <?= $currentPath === null ? 'current' : '' ?>" href="<?= base_url('files') ?>">My Drive</a>
      <?php foreach ($breadcrumbs as $index => $crumb): ?><span class="breadcrumb-sep">/</span><a class="breadcrumb-link <?= $index === count($breadcrumbs)-1 ? 'current' : '' ?>" href="<?= base_url('files') . '?path=' . rawurlencode($crumb['path']) ?>"><?= esc($crumb['label']) ?></a><?php endforeach; ?>
    </nav>
    <div class="drive-actions"><button type="button" class="quick-upload" id="quickUploadBtn">+ Upload</button><div class="view-switch" aria-label="View style"><button type="button" class="view-button active" id="listViewBtn" title="List view">☰</button><button type="button" class="view-button" id="gridViewBtn" title="Grid view">▦</button></div></div>
  </div>

  <form class="filters panel" id="filterForm" method="get" action="<?= base_url('files') ?>">
    <div><label for="filterQ">Search this folder</label><input id="filterQ" type="search" name="q" value="<?= esc($filters['q'], 'attr') ?>" placeholder="Title, filename, category…"></div>
    <div><label for="filterCategory">Category</label><select id="filterCategory" name="category"><option value="">All</option><?php foreach ($categories as $category): ?><option value="<?= esc($category, 'attr') ?>" <?= $filters['category'] === $category ? 'selected' : '' ?>><?= esc($category) ?></option><?php endforeach; ?></select></div>
    <div><label for="filterType">Type</label><select id="filterType" name="type"><option value="">All</option><?php foreach ($extensions as $extension): ?><option value="<?= esc($extension, 'attr') ?>" <?= $filters['type'] === $extension ? 'selected' : '' ?>><?= esc(strtoupper($extension ?: 'FILE')) ?></option><?php endforeach; ?></select></div>
    <div><label for="filterSort">Sort</label><select id="filterSort" name="sort"><option value="name_asc" <?= $filters['sort']==='name_asc'?'selected':'' ?>>Name A–Z</option><option value="name_desc" <?= $filters['sort']==='name_desc'?'selected':'' ?>>Name Z–A</option><option value="newest" <?= $filters['sort']==='newest'?'selected':'' ?>>Newest</option><option value="oldest" <?= $filters['sort']==='oldest'?'selected':'' ?>>Oldest</option><option value="size_desc" <?= $filters['sort']==='size_desc'?'selected':'' ?>>Largest</option><option value="size_asc" <?= $filters['sort']==='size_asc'?'selected':'' ?>>Smallest</option></select></div>
    <input type="hidden" name="path" value="<?= esc((string) ($currentPath ?? ''), 'attr') ?>"><input type="hidden" name="favorite" value="<?= esc($filters['favorite'], 'attr') ?>">
  </form>
  <?php if ($hasFileFilters): ?>
    <div class="filter-state" role="status">
      <div class="filter-state-copy"><strong><?= number_format($pageTotal) ?> matching file<?= $pageTotal === 1 ? '' : 's' ?></strong> in <?= esc($currentPath ?: 'My Drive') ?>. Folder cards are hidden while filters are active.</div>
      <a class="clear-filter-link" href="<?= esc($clearFilterUrl, 'attr') ?>">Clear filters</a>
    </div>
  <?php endif; ?>

  <div class="layout">
    <section class="panel">
      <div class="panel-head">
        <h2><?= $currentPath ? esc(basename(str_replace('\\', '/', $currentPath))) : 'My Drive' ?></h2>
        <div class="panel-head-actions">
          <span class="result-count"><?php if ($pageTotal > 0): ?><strong><?= number_format($pageFrom) ?>–<?= number_format($pageTo) ?></strong> of <?= number_format($pageTotal) ?> files<?php else: ?>0 files<?php endif; ?><?php if (! $hasFileFilters && $filters['favorite'] !== '1'): ?> · <?= count($childFolders) ?> folder<?= count($childFolders) === 1 ? '' : 's' ?><?php endif; ?></span>
          <?php if ($currentPath): ?><button type="button" class="folder-main-action js-share-folder" data-folder-path="<?= esc((string) $currentPath, 'attr') ?>" data-folder-name="<?= esc(basename(str_replace('\\', '/', $currentPath)), 'attr') ?>">&#8599; Share folder</button><?php endif; ?>
          <button type="button" class="folder-main-action js-download-folder" data-folder-path="<?= esc((string) ($currentPath ?? ''), 'attr') ?>" data-folder-name="<?= esc($currentPath ? basename(str_replace('\\', '/', $currentPath)) : 'Important Files', 'attr') ?>">&#8681; <?= $currentPath ? 'Download folder' : 'Download all' ?></button>
        </div>
      </div>
      <?php if ($childFolders !== []): ?>
        <div class="folder-section">
          <p class="section-label">Folders</p>
          <div class="folder-grid">
            <?php foreach ($childFolders as $folder): ?>
              <div class="folder-card-shell">
                <a class="folder-card" href="<?= base_url('files') . '?path=' . rawurlencode($folder['path']) ?>"><span class="folder-icon">&#128193;</span><span class="folder-copy"><span class="folder-name"><?= esc($folder['name']) ?></span><span class="folder-count"><?= (int) $folder['count'] ?> item<?= (int) $folder['count'] === 1 ? '' : 's' ?></span></span></a>
                <div class="folder-card-actions"><button type="button" class="folder-card-action js-share-folder" title="Share <?= esc($folder['name'], 'attr') ?>" aria-label="Share <?= esc($folder['name'], 'attr') ?>" data-folder-path="<?= esc($folder['path'], 'attr') ?>" data-folder-name="<?= esc($folder['name'], 'attr') ?>">&#8599;</button><button type="button" class="folder-card-action js-download-folder" title="Download <?= esc($folder['name'], 'attr') ?>" aria-label="Download <?= esc($folder['name'], 'attr') ?>" data-folder-path="<?= esc($folder['path'], 'attr') ?>" data-folder-name="<?= esc($folder['name'], 'attr') ?>">&#8681;</button></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
      <?php if ($files !== []): ?><p class="section-label">Files</p><?php endif; ?>
      <div id="emptyState" class="empty" <?= ($files !== [] || $childFolders !== []) ? 'style="display:none"' : '' ?>>
        <?php if ($hasFileFilters): ?>
          <span class="empty-title">No files match these filters</span><span class="empty-copy">Try a different search, file type, or category filter.</span><div class="empty-actions"><a class="empty-action" href="<?= esc($clearFilterUrl, 'attr') ?>">Clear filters</a></div>
        <?php else: ?>
          <span class="empty-title">This folder is empty</span><span class="empty-copy">Upload individual files or choose a whole folder while keeping its structure.</span><div class="empty-actions"><button type="button" class="empty-action js-focus-upload">Upload files</button></div>
        <?php endif; ?>
      </div>
      <ul class="file-list" id="fileList"><?php foreach ($files as $f): ?><?= view('files/_file_card', ['f' => $f]) ?><?php endforeach; ?></ul>
      <?= $pager->links('files') ?>
    </section>

    <aside class="panel upload-panel" id="uploadPanel">
      <h2>Add to Vault</h2>
      <div class="upload-location">Upload location: <strong><?= esc($currentPath ?: 'My Drive') ?></strong></div>
      <form id="uploadForm">
        <label for="title">Title</label><input type="text" id="title" maxlength="255" placeholder="Used for one file; multiple files use their filenames">
        <label for="description">Description (optional)</label><textarea id="description" rows="2" maxlength="5000"></textarea>
        <label for="category">Category (optional)</label><input type="text" id="category" maxlength="100" placeholder="ID, Certificate, Tax…">
        <label for="documentDate">Document date (optional)</label><input type="date" id="documentDate">
        <label>Files or folder</label>
        <div class="dropzone" id="dropzone">
          <p>Drop files here, or choose any files/folder</p>
          <div class="upload-pickers"><label class="picker-button" for="fileInput">&#128196; Choose files</label><label class="picker-button folder" for="folderInput">&#128193; Choose folder</label></div>
          <input class="upload-input-hidden" type="file" id="fileInput" multiple>
          <input class="upload-input-hidden" type="file" id="folderInput" multiple webkitdirectory directory>
          <div class="selection-summary" id="selectionSummary">Nothing selected</div>
        </div>
        <p class="file-hint">All file extensions are accepted, up to <?= (int) $maxMb ?> MB per file. PDF, images, audio, video, and text/code files can be previewed. Other formats can still be opened in the viewer and downloaded.</p>
        <div class="selected-files" id="selectedFiles"></div><div id="uploadStatus" class="file-hint" style="display:none" role="status" aria-live="polite"></div><div id="progressWrap" class="progress-wrap" style="display:none"><div id="progressBar" class="progress-bar"></div></div>
        <div class="upload-actions"><button type="submit" class="btn-primary" id="uploadBtn">Add to vault</button><button type="button" class="btn-secondary" id="cancelBtn" style="display:none">Cancel</button></div>
      </form>
    </aside>
  </div>
</div>

<div class="modal drive-preview" id="previewModal" aria-hidden="true"><div class="drive-preview-card"><div class="preview-topbar"><div class="preview-nav"><button type="button" id="previewPrev" aria-label="Previous file">&#8592;</button><button type="button" id="previewNext" aria-label="Next file">&#8594;</button></div><div class="preview-heading"><strong id="previewTitle">File</strong><span id="previewFilename"></span></div><a class="preview-top-action open-new" id="previewOpenLink" href="#" target="_blank" rel="noopener">Open tab</a><a class="preview-top-action" id="previewDownloadTop" href="#" target="_blank" rel="noopener">Download</a><button class="preview-top-action" type="button" data-close-modal aria-label="Close">&#10005;</button></div><div class="preview-body"><div class="preview-stage" id="previewStage"><div class="preview-loader" id="previewLoading"><span class="preview-spinner"></span><span>Loading secure preview…</span></div></div><aside class="preview-info"><h3>File details</h3><div class="detail-row"><span class="detail-label">Name</span><div class="detail-value" id="detailName"></div></div><div class="detail-row"><span class="detail-label">Location</span><div class="detail-value" id="detailFolder"></div></div><div class="detail-row"><span class="detail-label">Type</span><div class="detail-value" id="detailType"></div></div><div class="detail-row"><span class="detail-label">Size</span><div class="detail-value" id="detailSize"></div></div><div class="detail-row"><span class="detail-label">Added</span><div class="detail-value" id="detailDate"></div></div><div class="detail-row"><span class="detail-label">Description</span><div class="detail-value" id="detailDescription"></div></div><div class="preview-info-actions"><a class="primary-link" id="previewDownloadSide" href="#" target="_blank" rel="noopener">Download</a><a class="secondary-link" id="previewOpenSide" href="#" target="_blank" rel="noopener">Open tab</a></div></aside></div></div></div>
<div class="modal" id="editModal" aria-hidden="true"><div class="modal-card"><div class="modal-head"><h2>Edit file details</h2><button class="modal-close" type="button" data-close-modal>&times;</button></div><form id="editForm" method="post"><?= csrf_field() ?><input type="hidden" name="return_to" id="editReturnTo"><label for="editTitle">Title</label><input id="editTitle" name="title" type="text" maxlength="255" required><label for="editDescription">Description</label><textarea id="editDescription" name="description" rows="3" maxlength="5000"></textarea><label for="editCategory">Category</label><input id="editCategory" name="category" type="text" maxlength="100"><label for="editFolderPath">Folder</label><input id="editFolderPath" name="folder_path" type="text" maxlength="1000" placeholder="Folder/Subfolder"><label for="editDocumentDate">Document date (optional)</label><input id="editDocumentDate" name="document_date" type="date"><div class="modal-actions"><button type="button" class="btn-secondary" data-close-modal>Cancel</button><button type="submit" class="btn-primary">Save changes</button></div></form></div></div>
<div class="modal" id="deleteModal" aria-hidden="true"><div class="modal-card"><div class="modal-head"><h2>Move to Recycle Bin?</h2><button class="modal-close" type="button" data-close-modal>&times;</button></div><p id="deleteMessage" class="file-hint"></p><form id="deleteForm" method="post"><?= csrf_field() ?><input type="hidden" name="return_to" id="deleteReturnTo"><div class="modal-actions"><button type="button" class="btn-secondary" data-close-modal>Cancel</button><button type="submit" class="danger-button">Move file</button></div></form></div></div>
<div class="modal" id="shareModal" aria-hidden="true">
  <div class="modal-card share-manager-card">
    <div class="modal-head"><h2 id="shareModalTitle">Share file</h2><button class="modal-close" type="button" data-close-modal aria-label="Close sharing window">&times;</button></div>
    <p class="share-help" id="shareHelp">Create a private link that works without signing in.</p>
    <form id="shareForm">
      <div class="share-custom-grid">
        <div class="share-span-2"><label for="sharePageTitle">Shared-page title (optional)</label><input id="sharePageTitle" type="text" maxlength="255" placeholder="Example: Project delivery files"></div>
        <div><label for="shareDisplayName">Shared by (optional)</label><input id="shareDisplayName" type="text" maxlength="100" placeholder="Your name or team"></div>
        <div><label for="shareDuration">Link expiration</label><select id="shareDuration"><option value="1d">1 day</option><option value="7d" selected>7 days</option><option value="30d">30 days</option><option value="90d">90 days</option><option value="never">Never</option></select></div>
        <div class="share-span-2"><label for="shareMessage">Message or instructions (optional)</label><textarea id="shareMessage" rows="3" maxlength="3000" placeholder="Add notes for the recipient…"></textarea></div>
        <div><label for="shareMaxDownloads">Download limit</label><input id="shareMaxDownloads" type="number" min="0" max="10000" value="0"><p class="file-hint share-field-hint">Use 0 for unlimited.</p></div>
        <fieldset class="share-notifications"><legend>Owner notifications</legend><label><input id="shareNotifyFirstOpen" type="checkbox"> First time opened</label><label><input id="shareNotifyLimit" type="checkbox"> Download limit reached</label><label><input id="shareNotifyExpiring" type="checkbox"> Link expiring soon</label></fieldset>
      </div>
      <div class="modal-actions"><button type="button" class="btn-secondary" data-close-modal>Cancel</button><button type="submit" class="btn-primary" id="shareCreateBtn">Create link</button></div>
    </form>
    <div class="share-result" id="shareResult" hidden>
      <div class="share-result-label">Share link ready</div>
      <div class="share-link-row"><input id="shareLinkInput" type="text" readonly><button type="button" class="share-copy" id="shareCopyBtn">Copy</button><button type="button" class="share-qr-button" id="shareQrBtn">QR</button></div>
      <p class="share-once" id="shareResultNote">You can copy this link again later from Link History.</p>
    </div>
    <div class="share-list-title"><span>Link history</span><span class="share-list-caption">Copy, view QR, check activity, or disable a link.</span></div>
    <div class="share-list" id="shareList"><div class="share-loading">Loading links…</div></div>
  </div>
</div>
<div class="modal" id="qrModal" aria-hidden="true">
  <div class="modal-card qr-modal-card">
    <div class="modal-head"><h2>Share QR code</h2><button class="modal-close" type="button" data-close-modal aria-label="Close QR code">&times;</button></div>
    <div class="qr-code-shell" id="qrCodeCanvas" aria-label="QR code"></div>
    <p class="share-help">Scan this code to open the same share link.</p>
    <div class="share-link-row"><input id="qrLinkInput" type="text" readonly><button type="button" class="share-copy" id="qrCopyBtn">Copy link</button></div>
  </div>
</div>
<div class="modal" id="analyticsModal" aria-hidden="true">
  <div class="modal-card analytics-modal-card">
    <div class="modal-head"><h2>Share activity</h2><button class="modal-close" type="button" data-close-modal aria-label="Close activity">&times;</button></div>
    <div id="shareAnalyticsBody" class="share-analytics-body"><div class="share-loading">Loading activity…</div></div>
  </div>
</div>
<div class="modal" id="folderDownloadModal" aria-hidden="true" data-static="true">
  <div class="modal-card folder-download-card">
    <div class="folder-download-symbol">ZIP</div>
    <h2 id="folderDownloadTitle">Preparing folder</h2>
    <p id="folderDownloadMessage">Creating a secure download list…</p>
    <div class="folder-download-progress"><span id="folderDownloadBar"></span></div>
    <div class="folder-download-stats"><span id="folderDownloadFiles">0 files</span><span id="folderDownloadBytes">0 B</span></div>
    <p class="folder-download-note" id="folderDownloadNote" hidden></p>
    <div class="folder-download-actions">
      <button type="button" class="btn-secondary" id="folderDownloadCancel">Cancel</button>
      <button type="button" class="btn-primary" id="folderDownloadDone" style="display:none">Done</button>
    </div>
  </div>
</div>

<?= view('partials/theme_scripts') ?>
<script id="vaultConfig" type="application/json"><?= json_encode([
  'csrfHeader' => csrf_header(),
  'csrfHash' => csrf_hash(),
  'maxBytes' => (int) $maxBytes,
  'currentPath' => (string) ($currentPath ?? ''),
  'siteUsername' => (string) session()->get('site_username'),
  'urls' => [
    'filesBase' => base_url('files'),
    'folderShares' => base_url('files/folder-shares'),
    'sharesBase' => base_url('files/shares'),
    'cancelUpload' => base_url('files/cancel-upload'),
    'signUpload' => base_url('files/sign-upload'),
    'store' => base_url('files/store'),
    'folderDownloadManifest' => base_url('files/folder-download-manifest'),
    'folderDownloadComplete' => base_url('files/folder-download-complete'),
  ],
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script>window.VAULT_CONFIG=JSON.parse(document.getElementById('vaultConfig').textContent);</script>
<script src="<?= base_url('share-assets/qrcode.min.js') ?>" defer></script>
<script src="<?= base_url('assets/js/vault.v5.js') ?>" defer></script>
</body>
</html>
