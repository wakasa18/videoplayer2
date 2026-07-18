<!DOCTYPE html>
<html lang="en">
<head>
<title><?= esc($pageTitle) ?></title>
<?= view('partials/theme_head') ?>
<style>
  .soon-panel{
    max-width:520px; margin:40px auto 0; text-align:center; padding:44px 32px;
  }
  .soon-panel .portal-icon{
    width:64px; height:64px; font-size:28px; margin:0 auto 20px;
    animation:fadeInUp .5s ease .3s both, spinSlow 30s linear infinite;
  }
  .soon-panel .badge{ margin-bottom:18px; }
  .soon-panel h2{
    font-family:'Cormorant Garamond', Georgia, serif; font-style:italic; font-weight:600;
    font-size:28px; margin:0 0 12px; color:var(--text);
  }
  .soon-panel p{ font-size:14px; color:var(--text-dim); line-height:1.6; margin:0 0 26px; }
  .soon-panel .btn-primary{ width:auto; padding:11px 26px; margin-top:0; }
</style>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<div class="wrap">

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
