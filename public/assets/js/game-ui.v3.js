(() => {
  'use strict';

  const root = document.documentElement;
  const body = document.body;
  if (!body) return;

  const fontStyles = document.getElementById('gameFontStyles');
  if (fontStyles) fontStyles.media = 'all';

  document.querySelectorAll('.retro-sun,.retro-grid,.retro-chevrons,.scanline-layer,.milky-way,.galaxy').forEach((node) => node.remove());

  if (!document.querySelector('.game-world-bg')) {
    const world = document.createElement('div');
    world.className = 'game-world-bg';
    world.setAttribute('aria-hidden', 'true');
    world.innerHTML = [
      '<span class="game-cloud c1"></span>',
      '<span class="game-cloud c2"></span>',
      '<span class="game-cloud c3"></span>',
      '<span class="game-rock r1"></span>',
      '<span class="game-rock r2"></span>',
      '<span class="game-rock r3"></span>'
    ].join('');
    body.prepend(world);
  }

  const topbar = document.querySelector('.site-topbar');

  const updateViewportMode = () => {
    const width = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
    root.classList.toggle('game-compact', width < 620);
    root.classList.toggle('game-ultra-compact', width < 380);
  };

  const positionOpenMenus = () => {
    document.querySelectorAll('details.action-menu[open],details.card-menu[open]').forEach((details) => {
      details.classList.remove('open-up', 'align-left');
      const panel = details.querySelector('.action-menu-panel,.card-menu-panel');
      if (!panel) return;
      const rect = panel.getBoundingClientRect();
      if (rect.bottom > window.innerHeight - 8) details.classList.add('open-up');
      requestAnimationFrame(() => {
        const adjusted = panel.getBoundingClientRect();
        if (adjusted.left < 8) details.classList.add('align-left');
      });
    });
  };

  const updateHudOffset = () => {
    const bottom = topbar ? Math.ceil(topbar.getBoundingClientRect().bottom) : 0;
    root.style.setProperty('--hud-bottom', `${Math.max(0, bottom)}px`);
  };

  updateViewportMode();
  updateHudOffset();
  window.addEventListener('resize', () => { updateViewportMode(); updateHudOffset(); positionOpenMenus(); }, { passive: true });
  window.addEventListener('orientationchange', () => { updateViewportMode(); updateHudOffset(); positionOpenMenus(); }, { passive: true });

  if ('ResizeObserver' in window && topbar) {
    const observer = new ResizeObserver(updateHudOffset);
    observer.observe(topbar);
  }

  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(updateHudOffset).catch(() => {});
  }

  document.addEventListener('toggle', (event) => {
    const details = event.target;
    if (!(details instanceof HTMLDetailsElement) || !details.open) return;
    if (!details.matches('details.action-menu,details.card-menu,details.sp-mobile-menu')) return;
    document.querySelectorAll('details.action-menu[open],details.card-menu[open],details.sp-mobile-menu[open]').forEach((other) => {
      if (other !== details) other.removeAttribute('open');
    });
    requestAnimationFrame(positionOpenMenus);
  }, true);

  // Close popup action menus when clicking elsewhere. This prevents a menu
  // from staying above unrelated cards after scrolling or changing views.
  document.addEventListener('click', (event) => {
    document.querySelectorAll('details.action-menu[open],details.card-menu[open],details.sp-mobile-menu[open]').forEach((details) => {
      if (!details.contains(event.target)) details.removeAttribute('open');
    });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const openMenu = document.querySelector('details.action-menu[open],details.card-menu[open],details.sp-mobile-menu[open]');
    if (openMenu) {
      openMenu.removeAttribute('open');
      const summary = openMenu.querySelector('summary');
      if (summary) summary.focus();
    }
  });

  document.addEventListener('visibilitychange', () => {
    body.classList.toggle('page-hidden', document.visibilityState === 'hidden');
  }, { passive: true });

  requestAnimationFrame(() => body.classList.add('game-ui-ready'));
})();
