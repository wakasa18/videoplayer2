(() => {
  'use strict';

  const root = document.documentElement;
  const body = document.body;
  if (!body) return;

  document.querySelectorAll('.retro-sun,.retro-grid,.retro-chevrons,.scanline-layer,.milky-way,.galaxy').forEach((node) => node.remove());

  if (!document.querySelector('.game-world-bg')) {
    const world = document.createElement('div');
    world.className = 'game-world-bg';
    world.setAttribute('aria-hidden', 'true');
    world.innerHTML = [
      '<span class="game-cloud c1"></span>',
      '<span class="game-cloud c2"></span>',
      '<span class="game-cloud c3"></span>'
    ].join('');
    body.prepend(world);
  }

  const topbar = document.querySelector('.site-topbar');
  let resizeFrame = 0;

  const updateHudOffset = () => {
    const bottom = topbar ? Math.ceil(topbar.getBoundingClientRect().bottom) : 0;
    root.style.setProperty('--hud-bottom', `${Math.max(0, bottom)}px`);
  };

  const positionMenu = (details) => {
    details.classList.remove('open-up', 'align-left');
    const panel = details.querySelector('.action-menu-panel,.card-menu-panel,.sp-mobile-menu>div');
    if (!panel) return;
    const anchorRect = details.getBoundingClientRect();
    const viewportWidth = document.documentElement.clientWidth;
    const viewportHeight = window.innerHeight;
    const availableBelow = viewportHeight - anchorRect.bottom;
    const availableAbove = anchorRect.top;
    const panelHeight = Math.min(panel.scrollHeight || 260, viewportHeight * 0.7);
    if (availableBelow < panelHeight + 12 && availableAbove > availableBelow) details.classList.add('open-up');
    requestAnimationFrame(() => {
      const rect = panel.getBoundingClientRect();
      if (rect.right > viewportWidth - 8 || rect.left < 8) details.classList.add('align-left');
    });
  };

  const repositionOpenMenus = () => {
    document.querySelectorAll('details.action-menu[open],details.card-menu[open],details.sp-mobile-menu[open]').forEach(positionMenu);
  };

  const scheduleLayoutUpdate = () => {
    cancelAnimationFrame(resizeFrame);
    resizeFrame = requestAnimationFrame(() => {
      updateHudOffset();
      repositionOpenMenus();
    });
  };

  updateHudOffset();
  window.addEventListener('resize', scheduleLayoutUpdate, { passive: true });
  window.addEventListener('orientationchange', scheduleLayoutUpdate, { passive: true });

  if ('ResizeObserver' in window && topbar) {
    const observer = new ResizeObserver(scheduleLayoutUpdate);
    observer.observe(topbar);
  }
  if (document.fonts?.ready) document.fonts.ready.then(scheduleLayoutUpdate).catch(() => {});

  document.addEventListener('toggle', (event) => {
    const details = event.target;
    if (!(details instanceof HTMLDetailsElement) || !details.open) return;
    if (!details.matches('details.action-menu,details.card-menu,details.sp-mobile-menu')) return;
    document.querySelectorAll('details.action-menu[open],details.card-menu[open],details.sp-mobile-menu[open]').forEach((other) => {
      if (other !== details) other.removeAttribute('open');
    });
    requestAnimationFrame(() => positionMenu(details));
  }, true);

  document.addEventListener('click', (event) => {
    document.querySelectorAll('details.action-menu[open],details.card-menu[open],details.sp-mobile-menu[open]').forEach((details) => {
      if (!details.contains(event.target)) details.removeAttribute('open');
    });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const openMenu = document.querySelector('details.action-menu[open],details.card-menu[open],details.sp-mobile-menu[open]');
    if (!openMenu) return;
    openMenu.removeAttribute('open');
    openMenu.querySelector('summary')?.focus();
  });

  requestAnimationFrame(() => body.classList.add('game-ui-ready'));
})();
