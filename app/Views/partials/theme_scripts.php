<?php if ((bool) session()->get('site_authenticated')):
  $segment = service('uri')->getSegment(1) ?: '';
  $active = static fn (array $segments): string => in_array($segment, $segments, true) ? ' active' : '';
  $sectionTitle = match ($segment) {
      'videos' => 'Videos',
      'others', 'assignments', 'notes' => 'Assignments',
      'files' => 'Important Files',
      'pictures' => 'Pictures',
      default => 'Home',
  };
  $username = (string) session()->get('site_username');
  $initial = strtoupper(substr($username !== '' ? $username : 'A', 0, 1));
?>
<script>document.body.classList.add('site-authenticated');</script>
<header class="site-topbar" aria-label="Application header">
  <div class="site-topbar-inner">
    <button class="drive-menu-button" type="button" data-drive-sidebar-toggle aria-label="Open navigation" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <a class="site-brand" href="<?= base_url('/') ?>">
      <span class="site-brand-mark" aria-hidden="true">DA</span>
      <span class="site-brand-copy">
        <span class="site-brand-title">Damon's Archive</span>
        <span class="site-brand-sub">Private cloud workspace</span>
      </span>
    </a>
    <div class="site-section-title"><?= esc($sectionTitle) ?></div>
    <div class="site-account">
      <span class="site-user" title="<?= esc($username) ?>"><?= esc($username) ?></span>
      <span class="site-avatar" aria-hidden="true"><?= esc($initial) ?></span>
      <form action="<?= base_url('logout') ?>" method="post">
        <?= csrf_field() ?>
        <button class="site-logout-button" type="submit">Sign out</button>
      </form>
    </div>
  </div>
</header>

<aside class="drive-sidebar" aria-label="Main navigation" id="driveSidebar">
  <a class="drive-new-button" href="<?= base_url('files') ?>#uploadPanel">
    <span class="drive-new-plus" aria-hidden="true">+</span>
    <span>New</span>
  </a>
  <nav class="drive-sidebar-nav">
    <a class="drive-sidebar-link<?= $active(['']) ?>" href="<?= base_url('/') ?>"><span class="drive-nav-icon">⌂</span><span>Home</span></a>
    <a class="drive-sidebar-link<?= $active(['files']) ?>" href="<?= base_url('files') ?>"><span class="drive-nav-icon">▱</span><span>Important Files</span></a>
    <a class="drive-sidebar-link<?= $active(['assignments', 'others', 'notes']) ?>" href="<?= base_url('assignments') ?>"><span class="drive-nav-icon">✓</span><span>Assignments</span></a>
    <a class="drive-sidebar-link<?= $active(['videos']) ?>" href="<?= base_url('videos') ?>"><span class="drive-nav-icon">▶</span><span>Videos</span></a>
    <a class="drive-sidebar-link<?= $active(['pictures']) ?>" href="<?= base_url('pictures') ?>"><span class="drive-nav-icon">▧</span><span>Pictures</span></a>
  </nav>
  <div class="drive-sidebar-divider"></div>
  <nav class="drive-sidebar-nav">
    <a class="drive-sidebar-link" href="<?= base_url('files?favorite=1') ?>"><span class="drive-nav-icon">★</span><span>Starred</span></a>
    <a class="drive-sidebar-link" href="<?= base_url('files/recycle') ?>"><span class="drive-nav-icon">♲</span><span>Recycle Bin</span></a>
    <a class="drive-sidebar-link" href="<?= base_url('files/activity') ?>"><span class="drive-nav-icon">◷</span><span>Activity</span></a>
  </nav>
  <div class="drive-storage-card">
    <strong>Private storage</strong>
    <span>Supabase file vault</span>
    <a href="<?= base_url('files') ?>">View storage</a>
  </div>
  <form class="drive-sidebar-logout" action="<?= base_url('logout') ?>" method="post">
    <?= csrf_field() ?>
    <button type="submit"><span class="drive-nav-icon">↪</span><span>Sign out</span></button>
  </form>
</aside>
<div class="drive-sidebar-overlay" data-drive-sidebar-close></div>
<?php endif; ?>
