<!DOCTYPE html>
<html lang="en">
<head>
<title>Important Files · Damon's Archive</title>
<?= view('partials/theme_head') ?>
<style>
  .gate-panel{
    max-width:400px; margin:60px auto 0; text-align:center; padding:40px 32px;
  }
  .gate-icon{
    width:56px; height:56px; margin:0 auto 18px; border-radius:50%;
    background:var(--surface-2); border:1px solid var(--hairline);
    display:flex; align-items:center; justify-content:center; font-size:22px;
    opacity:0; animation:fadeInUp .5s ease .25s both;
  }
  .gate-panel h2{
    font-family:'Cormorant Garamond', Georgia, serif; font-style:italic; font-weight:600;
    font-size:24px; margin:0 0 8px; color:var(--text); text-transform:none; letter-spacing:0;
  }
  .gate-panel p{ font-size:13px; color:var(--text-dim); margin:0 0 22px; line-height:1.6; }
  .gate-panel label{ text-align:left; }
  .gate-panel .btn-primary{ margin-top:16px; }
</style>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<div class="wrap">

  <a href="<?= base_url('/') ?>" class="nav-back">&larr; Home</a>

  <header>
    <p class="eyebrow">Restricted Archive · Sector 04</p>
    <h1>Important Files</h1>
    <div class="starline"></div>
  </header>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="flash error" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('success')): ?>
    <div class="flash success" role="status"><?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif; ?>

  <div class="panel gate-panel">
    <div class="gate-icon">&#128274;</div>
    <h2>This section is locked</h2>
    <p>Important Files is kept separate from the rest of the archive. Enter the password to continue.</p>
    <form action="<?= base_url('files/unlock') ?>" method="post">
      <?= csrf_field() ?>
      <label for="password" class="sr-only">Password</label>
      <input type="password" id="password" name="password" placeholder="Password" required autofocus>
      <button type="submit" class="btn-primary">Unlock</button>
    </form>
  </div>

</div>

<?= view('partials/theme_scripts') ?>
</body>
</html>
