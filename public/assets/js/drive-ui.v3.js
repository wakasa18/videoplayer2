(() => {
  'use strict';

  const root = document.documentElement;
  const body = document.body;
  if (!body) return;

  const ICONS = {
    menu: '<path d="M4 7h16M4 12h16M4 17h16"/>',
    cloud: '<path d="M7.5 18A4.5 4.5 0 0 1 7 9a6 6 0 0 1 11.4 2A3.5 3.5 0 1 1 18 18Z"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    home: '<path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10M9 20v-6h6v6"/>',
    folder: '<path d="M3 6.5A2.5 2.5 0 0 1 5.5 4H10l2 2h6.5A2.5 2.5 0 0 1 21 8.5v8A2.5 2.5 0 0 1 18.5 19h-13A2.5 2.5 0 0 1 3 16.5Z"/>',
    folderPlus: '<path d="M3 6.5A2.5 2.5 0 0 1 5.5 4H10l2 2h6.5A2.5 2.5 0 0 1 21 8.5v8A2.5 2.5 0 0 1 18.5 19h-13A2.5 2.5 0 0 1 3 16.5Z"/><path d="M12 10v5M9.5 12.5h5"/>',
    task: '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="m8 9 2 2 4-4M8 15h8"/>',
    play: '<path d="m8 5 11 7-11 7Z"/>',
    image: '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/>',
    star: '<path d="m12 2.8 2.8 5.7 6.3.9-4.6 4.5 1.1 6.3-5.6-3-5.6 3 1.1-6.3-4.6-4.5 6.3-.9Z"/>',
    trash: '<path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5"/>',
    activity: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    logout: '<path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/>',
    bell: '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 8h18c0-1-3-1-3-8M10 20h4"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
    grid: '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>',
    list: '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="5" cy="6" r="1"/><circle cx="5" cy="12" r="1"/><circle cx="5" cy="18" r="1"/>',
    upload: '<path d="M12 16V4M7 9l5-5 5 5"/><path d="M5 20h14"/>',
    download: '<path d="M12 4v12M7 11l5 5 5-5"/><path d="M5 20h14"/>',
    share: '<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="m8.2 10.8 7.6-4.4M8.2 13.2l7.6 4.4"/>',
    eye: '<path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.5"/>',
    edit: '<path d="m4 20 4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10Z"/><path d="m14.5 7.5 3 3"/>',
    copy: '<rect x="8" y="8" width="11" height="11" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/>',
    link: '<path d="M10 13a5 5 0 0 0 7 0l2-2a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-2 2a5 5 0 0 0 7 7l1-1"/>',
    refresh: '<path d="M20 7v5h-5M4 17v-5h5"/><path d="M6.1 8A7 7 0 0 1 18.5 6.5L20 12M4 12l1.5 5.5A7 7 0 0 0 17.9 16"/>',
    restore: '<path d="M4 12a8 8 0 1 0 2.3-5.7L4 8"/><path d="M4 3v5h5"/>',
    save: '<path d="M5 3h12l2 2v16H5Z"/><path d="M8 3v6h8V3M8 15h8v6H8Z"/>',
    x: '<path d="m6 6 12 12M18 6 6 18"/>',
    check: '<path d="m5 12 4 4L19 6"/>',
    filter: '<path d="M4 6h16M7 12h10M10 18h4"/>',
    chevronRight: '<path d="m9 5 7 7-7 7"/>',
    arrowLeft: '<path d="m15 18-6-6 6-6M9 12h11"/>',
    arrowRight: '<path d="m9 18 6-6-6-6M4 12h11"/>',
    external: '<path d="M14 4h6v6M10 14 20 4"/><path d="M20 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h5"/>',
    more: '<circle cx="12" cy="5" r="1.2" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.2" fill="currentColor" stroke="none"/><circle cx="12" cy="19" r="1.2" fill="currentColor" stroke="none"/>',
    shield: '<path d="M12 3 5 6v5c0 4.8 2.9 8 7 10 4.1-2 7-5.2 7-10V6Z"/><path d="m9 12 2 2 4-4"/>',
    lock: '<rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
    file: '<path d="M6 3h8l4 4v14H6Z"/><path d="M14 3v5h5"/>',
    fileText: '<path d="M6 3h8l4 4v14H6Z"/><path d="M14 3v5h5M9 13h6M9 17h6M9 9h2"/>',
    code: '<path d="m9 8-4 4 4 4M15 8l4 4-4 4M13 5l-2 14"/>',
    archive: '<path d="M4 7h16v13H4Z"/><path d="M3 3h18v4H3ZM9 11h6"/>',
    audio: '<path d="M9 18V6l10-2v12"/><circle cx="6" cy="18" r="3"/><circle cx="16" cy="16" r="3"/>',
    calendar: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
    qr: '<rect x="3" y="3" width="6" height="6"/><rect x="15" y="3" width="6" height="6"/><rect x="3" y="15" width="6" height="6"/><path d="M15 15h2v2h-2zM19 15h2v6h-2zM15 19h2v2h-2z"/>',
    chart: '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
    spreadsheet: '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M4 9h16M4 15h16M10 3v18"/>',
    presentation: '<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M12 17v4M8 21h8M8 13l3-3 2 2 3-4"/>'
  };

  const svg = (name, className = 'drive-icon') => {
    const paths = ICONS[name] || ICONS.file;
    return `<svg class="${className}" viewBox="0 0 24 24" aria-hidden="true" focusable="false">${paths}</svg>`;
  };

  const replaceIcon = (element, name) => {
    if (!element || element.dataset.driveIconReady === '1') return;
    element.innerHTML = svg(name);
    element.dataset.driveIconReady = '1';
    element.classList.add('drive-motion-icon');
  };

  const prependIcon = (element, name) => {
    if (!element || element.dataset.driveActionIconReady === '1') return;
    const icon = document.createElement('span');
    icon.className = 'drive-action-icon';
    icon.innerHTML = svg(name);
    element.insertBefore(icon, element.firstChild);
    element.dataset.driveActionIconReady = '1';
    element.classList.add('drive-action-decorated');
  };

  const normalize = (text) => String(text || '').trim().toLowerCase().replace(/\s+/g, ' ');

  const actionIconFor = (element) => {
    const label = normalize(element.getAttribute('aria-label') || element.title || element.textContent);
    if (!label || label.length > 80) return null;
    if (/download/.test(label)) return 'download';
    if (/upload|choose files|add to vault/.test(label)) return 'upload';
    if (/choose folder|new folder/.test(label)) return 'folderPlus';
    if (/preview|open file|open tab|open in new tab|\bopen\b|\bview\b/.test(label)) return /tab/.test(label) ? 'external' : 'eye';
    if (/share/.test(label)) return 'share';
    if (/copy/.test(label)) return 'copy';
    if (/create link|link history|share link/.test(label)) return 'link';
    if (/qr/.test(label)) return 'qr';
    if (/analytics|activity/.test(label)) return /analytics/.test(label) ? 'chart' : 'activity';
    if (/edit|rename/.test(label)) return 'edit';
    if (/save|submit/.test(label)) return 'save';
    if (/restore|undo/.test(label)) return 'restore';
    if (/delete|remove|recycle|trash|disable/.test(label)) return 'trash';
    if (/favorite|starred|star/.test(label)) return 'star';
    if (/retry|refresh|reload/.test(label)) return 'refresh';
    if (/cancel|close|clear/.test(label)) return 'x';
    if (/apply|filter/.test(label)) return 'filter';
    if (/search/.test(label)) return 'search';
    if (/sign out|logout/.test(label)) return 'logout';
    if (/back/.test(label)) return 'arrowLeft';
    if (/next/.test(label)) return 'arrowRight';
    if (/previous/.test(label)) return 'arrowLeft';
    if (/new assignment|add assignment|add note|add subtask|new template|new subject|\bnew\b|\badd\b/.test(label)) return 'plus';
    if (/lock/.test(label)) return 'lock';
    return null;
  };

  const fileType = (label, filename = '') => {
    const value = `${label} ${filename}`.toLowerCase();
    if (/pdf/.test(value)) return ['fileText', 'pdf'];
    if (/png|jpe?g|gif|webp|bmp|svg|image/.test(value)) return ['image', 'image'];
    if (/mp4|mkv|mov|avi|webm|video/.test(value)) return ['play', 'video'];
    if (/mp3|wav|ogg|m4a|flac|audio/.test(value)) return ['audio', 'audio'];
    if (/zip|rar|7z|tar|tgz|gz|archive/.test(value)) return ['archive', 'archive'];
    if (/xlsx|xls|csv|sheet/.test(value)) return ['spreadsheet', 'sheet'];
    if (/pptx|ppt|presentation/.test(value)) return ['presentation', 'doc'];
    if (/docx|doc|txt|rtf|markdown|md|document/.test(value)) return ['fileText', 'doc'];
    if (/php|js|ts|tsx|jsx|css|html|sql|json|xml|yaml|yml|env|code|log/.test(value)) return ['code', 'code'];
    return ['file', 'generic'];
  };

  const decorateBadge = (element, label, filename = '') => {
    if (!element || element.dataset.driveFileBadgeReady === '1') return;
    const [name, type] = fileType(label, filename);
    const shortLabel = String(label || filename.split('.').pop() || 'FILE').trim().slice(0, 8);
    element.textContent = '';
    element.classList.add('drive-file-icon-badge', `drive-type-${type}`);
    const icon = document.createElement('span');
    icon.innerHTML = svg(name);
    const caption = document.createElement('span');
    caption.className = 'drive-badge-label';
    caption.textContent = shortLabel;
    element.append(icon, caption);
    element.dataset.driveFileBadgeReady = '1';
  };

  const decorateIcons = (scope = document) => {
    scope.querySelectorAll('[data-drive-icon]').forEach((element) => replaceIcon(element, element.dataset.driveIcon));

    const routeIcons = [
      ['a[href*="/videos"] .portal-icon', 'play'],
      ['a[href*="/pictures"] .portal-icon', 'image'],
      ['a[href*="/others"] .portal-icon', 'grid'],
      ['a[href*="/notes"] .portal-icon', 'fileText'],
      ['a[href*="/assignments"] .portal-icon', 'task'],
      ['a[href*="/files"] .portal-icon', 'folder']
    ];
    routeIcons.forEach(([selector, name]) => scope.querySelectorAll(selector).forEach((element) => replaceIcon(element, name)));

    scope.querySelectorAll('.folder-icon,.sp-folder-glyph,.sp-folder-hero-icon').forEach((element) => replaceIcon(element, 'folder'));
    scope.querySelectorAll('.notif-bell-icon').forEach((element) => replaceIcon(element, 'bell'));
    scope.querySelectorAll('.sp-search-icon').forEach((element) => replaceIcon(element, 'search'));
    scope.querySelectorAll('.sp-selection-icon').forEach((element) => replaceIcon(element, 'check'));
    scope.querySelectorAll('.sp-empty-icon').forEach((element) => replaceIcon(element, 'search'));
    scope.querySelectorAll('.sp-shield').forEach((element) => replaceIcon(element, 'shield'));
    scope.querySelectorAll('.sp-folder-arrow').forEach((element) => replaceIcon(element, 'chevronRight'));
    scope.querySelectorAll('.favorite-mark').forEach((element) => replaceIcon(element, 'star'));
    scope.querySelectorAll('.login-mark').forEach((element) => replaceIcon(element, 'cloud'));

    scope.querySelectorAll('.view-button,[data-view]').forEach((element) => {
      const view = element.dataset.view || normalize(element.getAttribute('aria-label'));
      replaceIcon(element, /grid/.test(view) ? 'grid' : 'list');
      element.classList.add('drive-icon-button');
    });

    scope.querySelectorAll('details.action-menu > summary,details.card-menu > summary,details.sp-mobile-menu > summary').forEach((element) => replaceIcon(element, 'more'));
    scope.querySelectorAll('.modal-close,.sp-modal-close').forEach((element) => replaceIcon(element, 'x'));

    scope.querySelectorAll('.sp-stat').forEach((stat) => {
      const label = normalize(stat.querySelector('small')?.textContent);
      let name = 'info';
      if (/file type/.test(label)) name = 'file';
      else if (/total files/.test(label)) name = 'folder';
      else if (/storage|file size/.test(label)) name = 'archive';
      else if (/updated/.test(label)) name = 'refresh';
      else if (/link access|expires/.test(label)) name = 'link';
      else if (/download/.test(label)) name = 'download';
      replaceIcon(stat.querySelector('.sp-stat-icon'), name);
    });

    scope.querySelectorAll('.file-type-badge').forEach((element) => {
      const item = element.closest('.file-item');
      const filename = item?.querySelector('.original-name')?.textContent || '';
      decorateBadge(element, element.textContent, filename);
    });
    scope.querySelectorAll('.sp-file-badge,.sp-single-file-badge,.badge-file').forEach((element) => {
      const parent = element.closest('.sp-file-row,.sp-file-item,.sp-hero,.file-item');
      const filename = parent?.querySelector('.sp-file-copy small,.sp-native-name,.original-name')?.textContent || '';
      decorateBadge(element, element.textContent, filename);
    });

    const actionSelector = [
      'button:not(.drive-menu-button):not(.notif-bell):not(.modal-close):not(.sp-modal-close)',
      'a.btn-primary','a.btn-secondary','a.sp-btn','a.toolbar-link','a.tool-button','a.arcade-button',
      'a.top-link','a.menu-action','button.menu-action','a.nav-back','a.sp-back-link','a.primary-link','a.secondary-link'
    ].join(',');
    scope.querySelectorAll(actionSelector).forEach((element) => {
      if (element.querySelector('.drive-action-icon,.drive-icon')) return;
      const name = actionIconFor(element);
      if (name) prependIcon(element, name);
    });
  };

  body.classList.add('drive-ui-ready');
  document.querySelectorAll('.game-world-bg,.twinkle-layer,.retro-sun,.retro-grid,.retro-chevrons,.scanline-layer,.milky-way,.galaxy,.shooting-star,.game-cloud,.game-rock').forEach((node) => node.remove());

  decorateIcons();

  const topbar = document.querySelector('.site-topbar, .sp-public-nav');
  const sidebar = document.querySelector('.drive-sidebar');
  const toggle = document.querySelector('[data-drive-sidebar-toggle]');
  const closeTargets = document.querySelectorAll('[data-drive-sidebar-close]');
  let resizeFrame = 0;

  const setSidebar = (open) => {
    body.classList.toggle('drive-sidebar-open', open);
    toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
    sidebar?.querySelectorAll('.drive-sidebar-link').forEach((item, index) => item.style.setProperty('--drive-nav-index', index));
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
      panel.classList.remove('drive-menu-pop');
      void panel.offsetWidth;
      panel.classList.add('drive-menu-pop');
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

  const updateScrollState = () => body.classList.toggle('drive-scrolled', window.scrollY > 8);
  updateHudOffset();
  updateScrollState();
  window.addEventListener('scroll', updateScrollState, { passive: true });
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

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!reducedMotion) {
    const selectors = [
      '.home-hero','.portal-card','.summary-card','.mission-stats > *',
      '.folder-card-shell','.file-item','.assignment-card','.kanban-column',
      '.sp-folder-card','.sp-file-row','.sp-stat','.panel','.workspace-panel',
      '.activity-table tr','.video-item','.recycle-item','.template-grid article'
    ].join(',');
    const animated = [...document.querySelectorAll(selectors)];
    const groupCounters = new Map();
    animated.forEach((node) => {
      const parent = node.parentElement;
      const index = groupCounters.get(parent) || 0;
      groupCounters.set(parent, index + 1);
      node.classList.add('drive-motion-item');
      node.style.setProperty('--drive-delay', `${Math.min(index, 10) * 45}ms`);
    });

    const observer = new IntersectionObserver((entries, io) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('drive-motion-visible');
        io.unobserve(entry.target);
      });
    }, { threshold: 0.06, rootMargin: '0px 0px -20px' });
    animated.forEach((node) => observer.observe(node));

    document.querySelectorAll('.modal,.sp-modal').forEach((modal) => {
      new MutationObserver(() => {
        const open = modal.classList.contains('open') || modal.classList.contains('is-open') || modal.getAttribute('aria-hidden') === 'false';
        if (!open) return;
        const card = modal.querySelector('.modal-card,.sp-modal-card');
        card?.animate([
          { opacity: 0, transform: 'translateY(18px) scale(.975)' },
          { opacity: 1, transform: 'translateY(-2px) scale(1.002)', offset: .72 },
          { opacity: 1, transform: 'translateY(0) scale(1)' }
        ], { duration: 330, easing: 'cubic-bezier(.16,1,.3,1)' });
        decorateIcons(modal);
      }).observe(modal, { attributes: true, attributeFilter: ['class', 'aria-hidden'] });
    });

    const rippleSelector = 'button,a.btn-primary,a.btn-secondary,a.sp-btn,a.toolbar-link,a.arcade-button,a.tool-button,a.top-link,.drive-new-button';
    document.addEventListener('pointerdown', (event) => {
      const target = event.target.closest(rippleSelector);
      if (!target || target.matches(':disabled')) return;
      const rect = target.getBoundingClientRect();
      const ripple = document.createElement('span');
      ripple.className = 'drive-ripple';
      const size = Math.max(rect.width, rect.height) * .45;
      ripple.style.width = `${size}px`;
      ripple.style.height = `${size}px`;
      ripple.style.left = `${event.clientX - rect.left}px`;
      ripple.style.top = `${event.clientY - rect.top}px`;
      target.appendChild(ripple);
      ripple.addEventListener('animationend', () => ripple.remove(), { once: true });
    });
  }

  const mutationObserver = new MutationObserver((mutations) => {
    const added = mutations.flatMap((mutation) => [...mutation.addedNodes]).filter((node) => node.nodeType === 1);
    added.forEach((node) => decorateIcons(node));
  });
  mutationObserver.observe(document.body, { childList: true, subtree: true });
})();
