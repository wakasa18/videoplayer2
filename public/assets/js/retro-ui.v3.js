(() => {
  'use strict';
  const body = document.body;
  if (!body) return;

  const fontStyles = document.getElementById('retroFontStyles');
  if (fontStyles) fontStyles.media = 'all';

  const addChrome = () => {
    if (document.visibilityState === 'hidden') return;
    if (!document.querySelector('.retro-sun')) {
      const sun = document.createElement('div');
      sun.className = 'retro-sun';
      sun.setAttribute('aria-hidden', 'true');
      body.appendChild(sun);
    }
    if (!document.querySelector('.retro-grid')) {
      const grid = document.createElement('div');
      grid.className = 'retro-grid';
      grid.setAttribute('aria-hidden', 'true');
      body.appendChild(grid);
    }
    if (window.matchMedia('(min-width: 901px) and (pointer: fine)').matches && !document.querySelector('.scanline-layer')) {
      const scan = document.createElement('div');
      scan.className = 'scanline-layer';
      scan.setAttribute('aria-hidden', 'true');
      body.appendChild(scan);
    }
  };

  if ('requestIdleCallback' in window) {
    window.requestIdleCallback(addChrome, { timeout: 900 });
  } else {
    window.setTimeout(addChrome, 120);
  }

  document.addEventListener('visibilitychange', () => {
    body.classList.toggle('page-hidden', document.visibilityState === 'hidden');
  }, { passive: true });
})();
