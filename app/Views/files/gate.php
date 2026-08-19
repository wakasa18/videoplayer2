<!DOCTYPE html>
<html lang="en">
<head>
<title>Important Files · Damon's Archive</title>
<?= view('partials/theme_head') ?>
<?= view('partials/drive_theme') ?>
</head>
<body>
<div class="wrap">
  <a href="<?= base_url('/') ?>" class="nav-back">&larr; Home</a>
  <header><p class="eyebrow">Private cloud storage</p><h1>Important Files</h1><div class="starline"></div></header>
  <?php if (session()->getFlashdata('error')): ?><div class="flash error" role="alert"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
  <?php if (session()->getFlashdata('success')): ?><div class="flash success" role="status"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
  <div class="panel gate-panel"><div class="gate-icon">&#128274;</div><h2>This section is locked</h2><p>Enter the vault password to view, upload, preview, or manage private files.</p><form action="<?= base_url('files/unlock') ?>" method="post"><?= csrf_field() ?><label for="password" class="sr-only">Password</label><div class="password-wrap"><input type="password" id="password" name="password" placeholder="Password" required autofocus autocomplete="current-password"><button type="button" class="show-password" id="showPassword">Show</button></div><button type="submit" class="btn-primary">Unlock</button></form></div>
</div>
<?= view('partials/theme_scripts') ?>
<script>const input=document.getElementById('password'),button=document.getElementById('showPassword');button.addEventListener('click',()=>{const show=input.type==='password';input.type=show?'text':'password';button.textContent=show?'Hide':'Show';input.focus();});</script>
</body>
</html>
