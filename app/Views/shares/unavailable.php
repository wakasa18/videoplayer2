<!DOCTYPE html>
<html lang="en">
<head>
<meta name="referrer" content="no-referrer">
<title>Shared File Unavailable</title>
<?= view('partials/theme_head') ?>
<style>.unavailable-shell{min-height:calc(100vh - 100px);display:grid;place-items:center}.unavailable-card{width:min(540px,100%);text-align:center;padding:34px}.unavailable-icon{width:76px;height:76px;margin:0 auto 18px;display:grid;place-items:center;border-radius:14px;font-size:29px}.unavailable-card h1{font-size:36px;margin-bottom:10px}.unavailable-card p{line-height:1.65;margin:0}</style>
<?= view('partials/retro_theme') ?>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<div class="wrap"><main class="unavailable-shell"><section class="unavailable-card"><div class="unavailable-icon">&#128279;</div><p class="eyebrow">Shared File</p><h1>Link unavailable</h1><p><?= esc($message) ?></p></section></main></div>
<?= view('partials/theme_scripts') ?>
</body>
</html>
