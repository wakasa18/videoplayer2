<!DOCTYPE html>
<html lang="en">
<head>
<title>Others · Damon's Archive</title>
<?= view('partials/theme_head') ?>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<div class="wrap">

  <a href="<?= base_url('/') ?>" class="nav-back">&larr; Home</a>

  <header>
    <p class="eyebrow">Miscellany · Sector 19</p>
    <h1>Others</h1>
    <div class="starline"></div>
  </header>

  <div class="portal-grid cols-2">

    <a href="<?= base_url('notes') ?>" class="portal-card">
      <span class="corner tl"></span><span class="corner tr"></span>
      <span class="corner bl"></span><span class="corner br"></span>
      <div class="portal-icon">&#128221;</div>
      <h2 class="portal-title">Notes</h2>
      <p class="portal-desc">Quick write-ups, references, and things worth remembering.</p>
      <div class="portal-foot">
        <span class="badge soon"><span class="dot"></span>Coming soon</span>
        <span class="portal-arrow">Enter &rarr;</span>
      </div>
    </a>

    <a href="<?= base_url('assignments') ?>" class="portal-card">
      <span class="corner tl"></span><span class="corner tr"></span>
      <span class="corner bl"></span><span class="corner br"></span>
      <div class="portal-icon">&#128203;</div>
      <h2 class="portal-title">Assignments</h2>
      <p class="portal-desc">Coursework and tasks, tracked from assigned to done.</p>
      <div class="portal-foot">
        <span class="badge soon"><span class="dot"></span>Coming soon</span>
        <span class="portal-arrow">Enter &rarr;</span>
      </div>
    </a>

  </div>

</div>

<?= view('partials/theme_scripts') ?>
</body>
</html>
