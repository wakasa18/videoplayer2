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
      { color: '#FFF3D6', weight: 18 }, // pale yellow
      { color: '#FFD9B3', weight: 8  }, // pale orange
      { color: '#FFB3A1', weight: 4  }, // faint red giant
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

    const count = window.innerWidth < 700 ? 70 : 140;
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
