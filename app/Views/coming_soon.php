<!DOCTYPE html>
<html lang="en">
<head>
<title><?= esc($pageTitle) ?></title>
<?= view('partials/theme_head') ?>
<style>.soon-panel{max-width:540px;margin:40px auto 0;text-align:center;padding:40px 30px}.soon-panel .portal-icon{width:66px;height:66px;font-size:29px;margin:0 auto 20px}.soon-panel .badge{margin-bottom:18px}.soon-panel h2{font-size:31px;margin:0 0 12px}.soon-panel p{font-size:15px;line-height:1.6;margin:0 0 26px}.soon-panel .btn-primary{width:auto;padding:11px 26px;margin-top:0}</style>
<?= view('partials/retro_theme') ?>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<div class="wrap">

  <?= view('partials/deadline_banner') ?>

  <a href="<?= esc($backUrl, 'attr') ?>" class="nav-back">&larr; <?= esc($backLabel) ?></a>

  <header>
    <p class="eyebrow"><?= esc($eyebrow) ?></p>
    <h1><?= esc($heading) ?></h1>
    <div class="starline"></div>
  </header>

  <div class="panel soon-panel">
    <div class="portal-icon"><?= $icon ?></div>
    <span class="badge soon"><span class="dot"></span>Coming soon</span>
    <h2><?= esc($heading) ?></h2>
    <p><?= esc($description) ?></p>
    <a href="<?= esc($backUrl, 'attr') ?>" class="btn-primary">&larr; Back to <?= esc($backLabel) ?></a>
  </div>

</div>

<?= view('partials/theme_scripts') ?>
</body>
</html>
