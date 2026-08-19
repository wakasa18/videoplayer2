<!DOCTYPE html>
<html lang="en">
<head>
<title>Sign In · Damon's Archive</title>
<?= view('partials/theme_head') ?>
<style>
.login-shell{min-height:calc(100vh - var(--hud-bottom) - 36px);display:grid;place-items:center;padding:26px 0}.login-stage{width:min(980px,100%);display:grid;grid-template-columns:minmax(0,1.08fr) minmax(350px,.92fr);gap:18px;align-items:stretch}.login-intro{position:relative;display:flex;flex-direction:column;justify-content:flex-end;padding:32px;overflow:hidden}.login-intro>*{position:relative;z-index:1}.login-console-label{font:700 13px var(--font-body);text-transform:uppercase;letter-spacing:.05em}.login-intro h2{font-size:clamp(40px,5vw,58px);line-height:1;margin:12px 0}.login-intro p{max-width:430px;line-height:1.65;margin:0 0 24px}.login-feature{display:flex;align-items:center;gap:12px;padding:10px 0}.login-feature span{font:400 18px var(--font-display)}.login-feature strong{font-size:14px}.login-card{width:100%;margin:0;padding:28px;position:relative;overflow:hidden}.login-mark{width:64px;height:64px;margin:0 auto 18px;display:grid;place-items:center;border-radius:14px;font:400 24px var(--font-display)}.login-card .eyebrow,.login-card h1,.login-sub{text-align:center}.login-card h1{font-size:38px;margin-bottom:8px}.login-sub{font-size:14px;line-height:1.55;margin:0 0 20px}.password-wrap{position:relative}.password-wrap input{padding-right:76px}.password-toggle{position:absolute;right:6px;top:50%;transform:translateY(-50%);min-height:32px;padding:5px 10px;background:transparent!important;border:0!important;box-shadow:none!important}.login-card .btn-primary{width:100%;margin-top:20px}.config-warning{margin-top:16px;padding:11px 12px;border-radius:9px;font-size:12px;line-height:1.5}.login-footer{text-align:center;margin-top:18px;font-size:12px;font-weight:700}.login-card label{margin-top:13px}@media(max-width:820px){.login-stage{grid-template-columns:1fr;max-width:480px}.login-intro{display:none}.login-shell{align-items:start}}@media(max-width:480px){.login-card{padding:20px}.login-card h1{font-size:34px}}
</style>
<?= view('partials/retro_theme') ?>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<div class="wrap">
  <main class="login-shell">
    <section class="login-stage">
      <aside class="login-intro">
        <span class="login-console-label">Private Adventure Hub</span>
        <h2>Damon's Archive</h2>
        <p>Your private game-style hub for files, videos, assignments, and secure shared links.</p>
        <div class="login-feature"><span>01</span><strong>Credential protected</strong></div>
        <div class="login-feature"><span>02</span><strong>Encrypted file vault</strong></div>
        <div class="login-feature"><span>03</span><strong>Controlled share links</strong></div>
      </aside>
      <section class="login-card">
      <div class="login-mark">DA</div>
      <p class="eyebrow">Secure Player Portal</p>
      <h1>Player Login</h1>
      <p class="login-sub">Enter your access credentials to continue to your private hub.</p>

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
      <div class="login-footer">Ready to play · Authorized access only</div>
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
