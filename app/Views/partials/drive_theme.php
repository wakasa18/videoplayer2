<link rel="stylesheet" href="<?= base_url('assets/css/drive-theme.v3.css') ?>">
<script src="<?= base_url('assets/js/drive-ui.v3.js') ?>" defer></script>
<?php if ((bool) session()->get('site_authenticated')): ?><script>document.documentElement.classList.add('site-authenticated-root');</script><?php endif; ?>
