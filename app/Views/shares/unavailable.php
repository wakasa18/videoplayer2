<!DOCTYPE html>
<html lang="en">
<head>
<meta name="referrer" content="no-referrer">
<title>Shared File Unavailable</title>
<?= view('partials/theme_head') ?>
<style>.unavailable-shell{min-height:calc(100vh - 100px);display:grid;place-items:center}.unavailable-card{width:min(520px,100%);text-align:center;background:var(--surface);border:1px solid var(--hairline);border-radius:14px;padding:34px;box-shadow:0 24px 80px rgba(0,0,0,.5)}.unavailable-icon{width:76px;height:86px;margin:0 auto 18px;display:grid;place-items:center;border:1px solid var(--hairline);border-radius:12px;background:var(--surface-2);font-size:28px}.unavailable-card h1{font-size:32px;margin-bottom:10px}.unavailable-card p{color:var(--text-dim);line-height:1.65;margin:0}</style>
<?= view('partials/retro_theme') ?>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<div class="wrap"><main class="unavailable-shell"><section class="unavailable-card"><div class="unavailable-icon">&#128279;</div><p class="eyebrow">Shared File</p><h1>Link unavailable</h1><p><?= esc($message) ?></p></section></main></div>
<?= view('partials/theme_scripts') ?>
</body>
</html>
