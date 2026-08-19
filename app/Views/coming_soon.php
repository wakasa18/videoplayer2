<!DOCTYPE html>
<html lang="en">
<head>
<title><?= esc($pageTitle) ?></title>
<?= view('partials/theme_head') ?>
<?= view('partials/drive_theme') ?>
</head>
<body>
<div class="wrap">

  <?= view('partials/deadline_banner') ?>

  <a href="<?= esc($backUrl, 'attr') ?>" class="nav-back">&larr; <?= esc($backLabel) ?></a>

  <header>
    <p class="eyebrow"><?= esc($eyebrow) ?></p>
    <h1><?= esc($heading) ?></h1>
    <div class="starline"></div>
  </header>

  <div class="panel soon-panel">
    <div class="portal-icon" data-drive-icon="info"><?= $icon ?></div>
    <span class="badge soon"><span class="dot"></span>Coming soon</span>
    <h2><?= esc($heading) ?></h2>
    <p><?= esc($description) ?></p>
    <a href="<?= esc($backUrl, 'attr') ?>" class="btn-primary">&larr; Back to <?= esc($backLabel) ?></a>
  </div>

</div>

<?= view('partials/theme_scripts') ?>
</body>
</html>
