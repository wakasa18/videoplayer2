<?php if ((bool) session()->get('site_authenticated')):
  $segment = service('uri')->getSegment(1) ?: '';
  $active = static fn (array $segments): string => in_array($segment, $segments, true) ? ' active' : '';
?>
<script>document.body.classList.add('site-authenticated');</script>
<nav class="site-topbar" aria-label="Main navigation">
  <div class="site-topbar-inner">
    <a class="site-brand" href="<?= base_url('/') ?>">
      <span class="site-brand-mark">DA</span>
      <span class="site-brand-copy"><span class="site-brand-title">Damon's Archive</span><span class="site-brand-sub">Arcade archive</span></span>
    </a>
    <div class="site-nav" aria-label="Archive sections">
      <a class="site-nav-link<?= $active(['']) ?>" href="<?= base_url('/') ?>">Home</a>
      <a class="site-nav-link<?= $active(['videos']) ?>" href="<?= base_url('videos') ?>">Videos</a>
      <a class="site-nav-link<?= $active(['others', 'assignments', 'notes']) ?>" href="<?= base_url('others') ?>">Tasks</a>
      <a class="site-nav-link<?= $active(['files']) ?>" href="<?= base_url('files') ?>">Vault</a>
    </div>
    <div class="site-account">
      <span class="site-user"><?= esc((string) session()->get('site_username')) ?></span>
      <form action="<?= base_url('logout') ?>" method="post">
        <?= csrf_field() ?>
        <button class="site-logout-button" type="submit">Sign out</button>
      </form>
    </div>
  </div>
</nav>
<?php endif; ?>
<script src="<?= base_url('assets/js/retro-ui.v3.js') ?>" defer></script>
