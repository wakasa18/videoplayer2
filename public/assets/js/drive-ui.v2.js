(() => {
  'use strict';

  const root = document.documentElement;
  const body = document.body;
  if (!body) return;

  body.classList.add('drive-ui-ready');
  document.querySelectorAll('.game-world-bg,.twinkle-layer,.retro-sun,.retro-grid,.retro-chevrons,.scanline-layer,.milky-way,.galaxy,.shooting-star,.game-cloud,.game-rock').forEach((node) => node.remove());

  const topbar = document.querySelector('.site-topbar, .sp-public-nav');
  const sidebar = document.querySelector('.drive-sidebar');
  const toggle = document.querySelector('[data-drive-sidebar-toggle]');
  const closeTargets = document.querySelectorAll('[data-drive-sidebar-close]');
  let resizeFrame = 0;

  const setSidebar = (open) => {
    body.classList.toggle('drive-sidebar-open', open);
    toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) sidebar?.querySelector('a,button')?.focus({ preventScroll: true });
  };

  toggle?.addEventListener('click', () => setSidebar(!body.classList.contains('drive-sidebar-open')));
  closeTargets.forEach((node) => node.addEventListener('click', () => setSidebar(false)));

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
    const estimatedHeight = Math.min(panel.scrollHeight || 260, viewportHeight * 0.72);
    const availableBelow = viewportHeight - anchorRect.bottom;
    const availableAbove = anchorRect.top;
    if (availableBelow < estimatedHeight + 16 && availableAbove > availableBelow) details.classList.add('open-up');
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
      if (window.innerWidth > 960) setSidebar(false);
    });
  };

  updateHudOffset();
  window.addEventListener('resize', scheduleLayoutUpdate, { passive: true });
  window.addEventListener('orientationchange', scheduleLayoutUpdate, { passive: true });
  if ('ResizeObserver' in window && topbar) new ResizeObserver(scheduleLayoutUpdate).observe(topbar);
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
    if (body.classList.contains('drive-sidebar-open')) {
      setSidebar(false);
      toggle?.focus();
      return;
    }
    const openMenu = document.querySelector('details.action-menu[open],details.card-menu[open],details.sp-mobile-menu[open]');
    if (!openMenu) return;
    openMenu.removeAttribute('open');
    openMenu.querySelector('summary')?.focus();
  });

  // Motion effects inspired by the supplied Google Drive UI reference.
  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const selectors = [
      '.home-hero', '.portal-card', '.summary-card', '.mission-stats > *',
      '.folder-card-shell', '.file-item', '.assignment-card', '.kanban-column',
      '.sp-folder-card', '.sp-file-row', '.sp-stat', '.panel', '.workspace-panel'
    ].join(',');
    const animated = [...document.querySelectorAll(selectors)];
    animated.forEach((node, index) => {
      node.classList.add('drive-motion-item');
      node.style.setProperty('--drive-delay', `${Math.min(index, 12) * 42}ms`);
    });

    const observer = new IntersectionObserver((entries, io) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('drive-motion-visible');
        io.unobserve(entry.target);
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -24px' });
    animated.forEach((node) => observer.observe(node));

    document.querySelectorAll('.modal,.sp-modal').forEach((modal) => {
      new MutationObserver(() => {
        const open = modal.classList.contains('open') || modal.classList.contains('is-open') || modal.getAttribute('aria-hidden') === 'false';
        if (!open) return;
        const card = modal.querySelector('.modal-card,.sp-modal-card');
        card?.animate([
          { opacity: 0, transform: 'translateY(14px) scale(.98)' },
          { opacity: 1, transform: 'translateY(0) scale(1)' }
        ], { duration: 210, easing: 'cubic-bezier(.2,.8,.2,1)' });
      }).observe(modal, { attributes: true, attributeFilter: ['class', 'aria-hidden'] });
    });
  }
})();
