<script>
  // --- twinkling starfield overlay (shared across every page) ---
  (function initTwinkle() {
    const layer = document.getElementById('twinkleLayer');
    if (!layer) return;
    const count = window.innerWidth < 700 ? 26 : 46;
    for (let i = 0; i < count; i++) {
      const star = document.createElement('span');
      star.className = 'twinkle-star';
      const size = (Math.random() * 1.6 + 0.6).toFixed(1);
      star.style.width = size + 'px';
      star.style.height = size + 'px';
      star.style.left = (Math.random() * 100) + 'vw';
      star.style.top = (Math.random() * 100) + 'vh';
      star.style.animationDuration = (Math.random() * 3 + 2.5).toFixed(2) + 's';
      star.style.animationDelay = (Math.random() * 4).toFixed(2) + 's';
      layer.appendChild(star);
    }
  })();
</script>
