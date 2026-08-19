<!DOCTYPE html>
<html lang="en">
<head>
<meta name="referrer" content="no-referrer">
<title>Shared File Unavailable</title>
<?= view('partials/theme_head') ?>
<?= view('partials/drive_theme') ?>
</head>
<body>
<div class="wrap"><main class="unavailable-shell"><section class="unavailable-card"><div class="unavailable-icon">&#128279;</div><p class="eyebrow">Shared File</p><h1>Link unavailable</h1><p><?= esc($message) ?></p></section></main></div>
<?= view('partials/theme_scripts') ?>
</body>
</html>
