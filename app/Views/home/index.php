<!DOCTYPE html>
<html lang="en">
<head>
<title>Damon's Archive</title>
<?= view('partials/theme_head') ?>
<style>
  .home-header{ margin-bottom:8px; }
  .home-sub{
    font-size:14px; color:var(--text-dim); max-width:520px; line-height:1.6;
    margin:0 0 8px; opacity:0; animation:fadeInUp .6s ease .26s forwards;
  }
</style>
<?= view('partials/retro_theme') ?>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<div class="wrap home-shell">

  <?= view('partials/deadline_banner') ?>

  <section class="home-hero">
    <header class="home-header">
      <p class="eyebrow">Personal Archive · Root Access</p>
      <h1>Damon's Archive</h1>
      <div class="starline"></div>
    </header>
    <p class="home-sub">A private command center for videos, assignments, notes, and important files. Choose a sector to continue.</p>
    <div class="home-status">
      <span class="home-status-chip"><i></i> System online</span>
      <span class="home-status-chip">Secure session</span>
      <span class="home-status-chip">Private workspace</span>
    </div>
  </section>

  <div class="portal-grid cols-2">

    <a href="<?= base_url('videos') ?>" class="portal-card">
      <span class="corner tl"></span><span class="corner tr"></span>
      <span class="corner bl"></span><span class="corner br"></span>
      <div class="portal-icon">&#9654;</div>
      <h2 class="portal-title">Videos</h2>
      <p class="portal-desc">Uploaded recordings and clips, ready to stream from the catalog.</p>
      <div class="portal-foot">
        <span class="badge live"><span class="dot"></span>Live</span>
        <span class="portal-arrow">Enter &rarr;</span>
      </div>
    </a>

    <a href="<?= base_url('pictures') ?>" class="portal-card">
      <span class="corner tl"></span><span class="corner tr"></span>
      <span class="corner bl"></span><span class="corner br"></span>
      <div class="portal-icon">&#9737;</div>
      <h2 class="portal-title">Pictures</h2>
      <p class="portal-desc">A photo gallery for stills and snapshots. Not built yet — on the way.</p>
      <div class="portal-foot">
        <span class="badge soon"><span class="dot"></span>Coming soon</span>
        <span class="portal-arrow">Enter &rarr;</span>
      </div>
    </a>

    <a href="<?= base_url('others') ?>" class="portal-card">
      <span class="corner tl"></span><span class="corner tr"></span>
      <span class="corner bl"></span><span class="corner br"></span>
      <div class="portal-icon">&#9776;</div>
      <h2 class="portal-title">Others</h2>
      <p class="portal-desc">Notes, assignments, and anything else that doesn't fit elsewhere.</p>
      <div class="portal-foot">
        <span class="badge live"><span class="dot"></span>Live</span>
        <span class="portal-arrow">Enter &rarr;</span>
      </div>
    </a>

    <a href="<?= base_url('files') ?>" class="portal-card">
      <span class="corner tl"></span><span class="corner tr"></span>
      <span class="corner bl"></span><span class="corner br"></span>
      <div class="portal-icon">&#128274;</div>
      <h2 class="portal-title">Important Files</h2>
      <p class="portal-desc">A password-locked vault for documents that matter — IDs, certificates, anything worth keeping close.</p>
      <div class="portal-foot">
        <span class="badge live"><span class="dot"></span>Live</span>
        <span class="portal-arrow">Enter &rarr;</span>
      </div>
    </a>

  </div>

</div>

<?= view('partials/theme_scripts') ?>
</body>
</html>
