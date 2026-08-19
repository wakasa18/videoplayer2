<!DOCTYPE html>
<html lang="en">
<head>
<title>Sign In · Damon's Archive</title>
<?= view('partials/theme_head') ?>
<style>
  .login-shell{min-height:calc(100vh - 116px);display:grid;place-items:center;padding:24px 0}.login-card{width:min(430px,100%);background:rgba(13,18,36,.94);border:1px solid var(--hairline);border-radius:14px;padding:28px;box-shadow:0 28px 90px rgba(0,0,0,.55);position:relative;overflow:hidden}.login-card::before{content:'';position:absolute;inset:0 0 auto;height:2px;background:linear-gradient(90deg,var(--cyan),var(--violet),var(--gold))}.login-mark{width:62px;height:62px;margin:0 auto 18px;display:grid;place-items:center;border:1px solid var(--hairline);border-radius:14px;background:var(--surface-2);font-size:26px;color:var(--cyan);box-shadow:0 0 30px rgba(95,217,232,.12)}.login-card .eyebrow{text-align:center}.login-card h1{text-align:center;font-size:34px;margin-bottom:8px}.login-sub{text-align:center;color:var(--text-dim);font-size:13px;line-height:1.6;margin:0 0 22px}.login-card label{display:block;margin:13px 0 6px}.password-wrap{position:relative}.password-wrap input{padding-right:70px}.password-toggle{position:absolute;right:7px;top:50%;transform:translateY(-50%);padding:7px 9px;background:transparent;color:var(--text-dim);font:600 10px 'JetBrains Mono',monospace}.password-toggle:hover{color:var(--cyan)}.login-card .btn-primary{margin-top:20px}.config-warning{margin-top:16px;padding:10px 12px;border:1px solid rgba(242,195,107,.45);border-radius:7px;background:rgba(242,195,107,.08);color:#f6dda8;font-size:11px;line-height:1.5}.login-footer{text-align:center;margin-top:18px;color:#657092;font:10px 'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.1em}

  .login-stage{width:min(920px,100%);display:grid;grid-template-columns:minmax(0,1fr) minmax(360px,430px);gap:20px;align-items:stretch}.login-intro{position:relative;display:flex;flex-direction:column;justify-content:flex-end;padding:36px;border:1px solid rgba(140,98,255,.22);border-radius:18px;background:linear-gradient(145deg,rgba(18,24,60,.78),rgba(7,10,28,.58));overflow:hidden;box-shadow:0 32px 90px rgba(0,0,0,.35)}.login-intro::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 20% 10%,rgba(57,231,255,.14),transparent 34%),radial-gradient(circle at 90% 80%,rgba(255,79,216,.16),transparent 38%)}.login-intro>*{position:relative;z-index:1}.login-console-label{font:700 10px 'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.16em;color:var(--cyan)}.login-intro h2{font-family:'Cormorant Garamond',serif;font-style:italic;font-size:46px;line-height:.95;margin:12px 0;color:#fff}.login-intro p{max-width:420px;color:var(--text-dim);line-height:1.7;margin:0 0 28px}.login-feature{display:flex;align-items:center;gap:12px;padding:10px 0;border-top:1px solid rgba(140,98,255,.16);color:var(--text-dim)}.login-feature span{font:700 10px 'JetBrains Mono',monospace;color:var(--pink)}.login-feature strong{font-size:12px;color:#dfe5ff}.login-card{width:100%!important;margin:0}.login-shell{padding-top:0!important}@media(max-width:820px){.login-stage{grid-template-columns:1fr;max-width:470px}.login-intro{display:none}}
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
