(() => {
  'use strict';
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

  document.addEventListener('visibilitychange', () => {
    body.classList.toggle('page-hidden', document.visibilityState === 'hidden');
  }, { passive: true });
})();
