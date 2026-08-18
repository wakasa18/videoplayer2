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
<script>
  // --- realistic starfield overlay (shared across every page) ---
  // Real starlight isn't all the same: most visible stars read white or
  // blue-white, a good share are pale yellow, and a small handful are
  // orange or red giants. Sizes skew small too — a few standout "bright"
  // stars, many faint ones.
  (function initTwinkle() {
    const layer = document.getElementById('twinkleLayer');
    if (!layer) return;

    const STAR_COLORS = [
      { color: '#EAF2FF', weight: 45 }, // blue-white
      { color: '#FFFFFF', weight: 25 }, // white
      { color: '#BFF8FF', weight: 18 }, // cyan-white
      { color: '#E5D8FF', weight: 8  }, // violet-white
      { color: '#FFC9F1', weight: 4  }, // pink-white
    ];
    const totalWeight = STAR_COLORS.reduce((sum, c) => sum + c.weight, 0);
    function pickColor() {
      let r = Math.random() * totalWeight;
      for (const c of STAR_COLORS) {
        if (r < c.weight) return c.color;
        r -= c.weight;
      }
      return STAR_COLORS[0].color;
    }

    const count = window.innerWidth < 700 ? 42 : 84;
    for (let i = 0; i < count; i++) {
      const star = document.createElement('span');
      const roll = Math.random();
      // Most stars are small and dim; a small fraction are bright standouts.
      const isBright = roll > 0.94;
      const size = isBright
        ? (Math.random() * 1.1 + 2.2).toFixed(1)
        : (Math.random() * 1.3 + 0.5).toFixed(1);

      star.className = 'twinkle-star' + (isBright ? ' bright' : '');
      star.style.width = size + 'px';
      star.style.height = size + 'px';
      star.style.left = (Math.random() * 100) + 'vw';
      star.style.top = (Math.random() * 100) + 'vh';
      star.style.background = pickColor();
      star.style.color = pickColor();
      star.style.animationDuration = (Math.random() * 3.5 + 2.5).toFixed(2) + 's';
      star.style.animationDelay = (Math.random() * 5).toFixed(2) + 's';
      layer.appendChild(star);
    }
  })();

  // --- Milky Way band + distant galaxies (static chrome, injected once) ---
  (function initDeepSky() {
    if (document.querySelector('.milky-way')) return; // don't duplicate

    const band = document.createElement('div');
    band.className = 'milky-way';
    document.body.appendChild(band);

    for (let i = 1; i <= 3; i++) {
      const galaxy = document.createElement('div');
      galaxy.className = 'galaxy galaxy-' + i;
      document.body.appendChild(galaxy);
    }
  })();


  // --- retro synthwave chrome overlays ---
  (function initRetroChrome() {
    if (!document.querySelector('.retro-sun')) {
      const sun = document.createElement('div');
      sun.className = 'retro-sun';
      document.body.appendChild(sun);
    }
    if (!document.querySelector('.retro-grid')) {
      const grid = document.createElement('div');
      grid.className = 'retro-grid';
      document.body.appendChild(grid);
    }
    if (!document.querySelector('.retro-chevrons')) {
      const layer = document.createElement('div');
      layer.className = 'retro-chevrons';
      const points = [
        [5,18,1.0],[21,31,.7],[38,14,.9],[61,28,.65],[82,16,.85],[94,37,.7],
        [10,69,.7],[29,80,.9],[52,66,.65],[73,77,.8],[89,62,.65],[46,43,.55]
      ];
      points.forEach(([left,top,scale],index) => {
        const item = document.createElement('span');
        item.className = 'retro-chevron';
        item.textContent = '›››';
        item.style.left = left + '%';
        item.style.top = top + '%';
        item.style.setProperty('--scale', scale);
        item.style.animationDelay = (-index * .7) + 's';
        layer.appendChild(item);
      });
      document.body.appendChild(layer);
    }
    if (!document.querySelector('.scanline-layer')) {
      const scan = document.createElement('div');
      scan.className = 'scanline-layer';
      document.body.appendChild(scan);
    }
  })();

  // --- occasional shooting stars ---
  (function initShootingStars() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    function launch() {
      const star = document.createElement('div');
      const startX = Math.random() * 70 + 5;   // vw
      const startY = Math.random() * 40;        // vh, upper portion of sky
      const length = Math.random() * 70 + 90;   // px
      const angle  = 24 + Math.random() * 10;   // degrees, downward-right
      const rad    = angle * Math.PI / 180;
      const travel = 260 + Math.random() * 160; // px

      star.className = 'shooting-star';
      star.style.left   = startX + 'vw';
      star.style.top    = startY + 'vh';
      star.style.width  = length + 'px';
      star.style.setProperty('--angle', angle + 'deg');
      star.style.setProperty('--dx', (Math.cos(rad) * travel).toFixed(0) + 'px');
      star.style.setProperty('--dy', (Math.sin(rad) * travel).toFixed(0) + 'px');

      document.body.appendChild(star);
      star.addEventListener('animationend', () => star.remove());
    }

    function scheduleNext() {
      const delay = 7000 + Math.random() * 14000; // every 7-21s
      setTimeout(() => {
        launch();
        scheduleNext();
      }, delay);
    }

    scheduleNext();
  })();
</script>
