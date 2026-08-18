<?php
use App\Models\ImportantFileModel;

$typeLabel = ImportantFileModel::typeLabel((string) $f['mime_type'], (string) $f['original_filename']);
$expiry    = ImportantFileModel::expirationState($f['expires_at'] ?? null);
$preview   = ImportantFileModel::isPreviewable($f);
$editData  = [
    'id'            => (int) $f['id'],
    'title'         => (string) $f['title'],
    'description'   => (string) ($f['description'] ?? ''),
    'category'      => (string) ($f['category'] ?? ''),
    'document_date' => (string) ($f['document_date'] ?? ''),
    'expires_at'    => (string) ($f['expires_at'] ?? ''),
    'reminder_days' => (int) ($f['reminder_days'] ?? 30),
];
?>
<li class="file-item" data-file-id="<?= (int) $f['id'] ?>">
  <div class="file-type-badge"><?= esc($typeLabel) ?></div>
  <div class="file-meta">
    <div class="file-title-row">
      <div class="file-title"><?= esc($f['title']) ?></div>
      <?php if (! empty($f['is_favorite'])): ?><span class="favorite-mark" title="Favorite">&#9733;</span><?php endif; ?>
    </div>
    <div class="original-name" title="<?= esc($f['original_filename'], 'attr') ?>"><?= esc($f['original_filename']) ?></div>
    <?php if (! empty($f['description'])): ?>
      <div class="file-desc"><?= esc($f['description']) ?></div>
    <?php endif; ?>
    <div class="file-sub">
      <?php if (! empty($f['category'])): ?><span class="category-tag"><?= esc($f['category']) ?></span><?php endif; ?>
      <?php if ($expiry): ?><span class="expiry-tag <?= esc($expiry['key']) ?>"><?= esc($expiry['label']) ?></span><?php endif; ?>
      <span class="file-sub-text"><?= ImportantFileModel::formatBytes((int) $f['file_size']) ?> &middot; <?= esc(date('M j, Y', strtotime((string) $f['created_at']))) ?></span>
      <?php if ((int) ($f['download_count'] ?? 0) > 0): ?><span class="file-sub-text"><?= (int) $f['download_count'] ?> download<?= (int) $f['download_count'] === 1 ? '' : 's' ?></span><?php endif; ?>
    </div>
  </div>
  <details class="action-menu">
    <summary aria-label="Actions for <?= esc($f['title'], 'attr') ?>">&#8942;</summary>
    <div class="action-menu-panel">
      <?php if ($preview): ?>
        <button type="button" class="menu-action js-preview" data-preview-url="<?= base_url('files/' . $f['id'] . '/preview') ?>" data-preview-title="<?= esc($f['title'], 'attr') ?>">Preview</button>
      <?php endif; ?>
      <a class="menu-action" href="<?= base_url('files/' . $f['id'] . '/download') ?>" target="_blank" rel="noopener">Download</a>
      <button type="button" class="menu-action js-edit" data-file="<?= esc(json_encode($editData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'attr') ?>">Edit details</button>
      <form action="<?= base_url('files/' . $f['id'] . '/favorite') ?>" method="post">
        <?= csrf_field() ?>
        <button type="submit" class="menu-action"><?= ! empty($f['is_favorite']) ? 'Remove favorite' : 'Add favorite' ?></button>
      </form>
      <button type="button" class="menu-action danger js-delete" data-delete-url="<?= base_url('files/' . $f['id'] . '/delete') ?>" data-delete-title="<?= esc($f['title'], 'attr') ?>">Move to Recycle Bin</button>
    </div>
  </details>
</li>
