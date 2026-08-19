<!DOCTYPE html>
<html lang="en">
<head>
<title>Sign In · Damon's Archive</title>
<?= view('partials/theme_head') ?>
<?= view('partials/drive_theme') ?>
</head>
<body>
<div class="wrap">
  <main class="login-shell">
    <section class="login-stage">
      <aside class="login-intro">
        <span class="login-console-label">Private cloud workspace</span>
        <h2>Damon's Archive</h2>
        <p>Keep your files, videos, assignments, and secure shared links in one private workspace.</p>
        <div class="login-feature"><span>01</span><strong>Credential protected</strong></div>
        <div class="login-feature"><span>02</span><strong>Encrypted file vault</strong></div>
        <div class="login-feature"><span>03</span><strong>Controlled share links</strong></div>
      </aside>
      <section class="login-card">
      <div class="login-mark">DA</div>
      <p class="eyebrow">Secure workspace</p>
      <h1>Sign in</h1>
      <p class="login-sub">Enter your credentials to access your private workspace.</p>

      <?php if (session()->getFlashdata('error')): ?><div class="flash error" role="alert"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
      <?php if (session()->getFlashdata('success')): ?><div class="flash success" role="status"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>

      <form action="<?= base_url('login') ?>" method="post" autocomplete="on">
        <?= csrf_field() ?>
        <label for="username">Username</label>
        <input id="username" name="username" type="text" maxlength="100" value="<?= esc(old('username'), 'attr') ?>" autocomplete="username" required autofocus>
        <label for="password">Password</label>
        <div class="password-wrap">
          <input id="password" name="password" type="password" maxlength="500" autocomplete="current-password" required>
          <button class="password-toggle" id="passwordToggle" type="button" aria-pressed="false">Show</button>
        </div>
        <button class="btn-primary" type="submit" <?= $configured ? '' : 'disabled' ?>>Sign in</button>
      </form>

      <?php if (! $configured): ?><div class="config-warning">Set <strong>SITE_LOGIN_USERNAME</strong> and either <strong>SITE_LOGIN_PASSWORD_HASH</strong> or <strong>SITE_LOGIN_PASSWORD</strong> in Vercel before signing in.</div><?php endif; ?>
      <div class="login-footer">Private access · Authorized users only</div>
      </section>
    </section>
  </main>
</div>
<?= view('partials/theme_scripts') ?>
<script>
const password=document.getElementById('password'),toggle=document.getElementById('passwordToggle');
toggle.addEventListener('click',()=>{const showing=password.type==='text';password.type=showing?'password':'text';toggle.textContent=showing?'Show':'Hide';toggle.setAttribute('aria-pressed',showing?'false':'true');password.focus();});
</script>
</body>
</html>
