<style>
  /* =========================================================
     RETRO ARCADE UI LAYER
     Loaded after each page's local styles so every section uses
     one consistent synthwave design system.
     ========================================================= */
  :root{
    --bg:#040511;
    --surface:#0a0d22;
    --surface-2:#101637;
    --surface-3:#171d46;
    --hairline:#303875;
    --text:#f4f7ff;
    --text-dim:#9aa6d8;
    --cyan:#39e7ff;
    --violet:#8c62ff;
    --pink:#ff4fd8;
    --gold:#ffd166;
    --red:#ff6b82;
    --green:#55efb6;
    --radius:14px;
    --glow-cyan:0 0 0 1px rgba(57,231,255,.12),0 0 18px rgba(57,231,255,.22),0 0 44px rgba(57,231,255,.08);
    --glow-pink:0 0 0 1px rgba(255,79,216,.12),0 0 18px rgba(255,79,216,.2),0 0 44px rgba(255,79,216,.08);
    --glow-violet:0 0 0 1px rgba(140,98,255,.13),0 0 22px rgba(140,98,255,.2);
    --panel-gradient:linear-gradient(155deg,rgba(17,23,57,.96),rgba(7,11,30,.96));
    --control-gradient:linear-gradient(180deg,rgba(22,29,68,.96),rgba(11,16,42,.98));
  }

  html{background:#040511;color-scheme:dark;scroll-behavior:smooth}
  body{
    background:
      radial-gradient(circle at 16% -8%,rgba(57,231,255,.16),transparent 31%),
      radial-gradient(circle at 86% 2%,rgba(255,79,216,.17),transparent 32%),
      radial-gradient(circle at 50% 105%,rgba(140,98,255,.24),transparent 38%),
      linear-gradient(180deg,#03040d 0%,#060919 45%,#0a1028 100%) !important;
    color:var(--text);
    min-height:100vh;
  }
  body::before{
    background-image:
      radial-gradient(ellipse 1100px 560px at 10% -12%,rgba(140,98,255,.22),transparent 58%),
      radial-gradient(ellipse 860px 540px at 97% 3%,rgba(57,231,255,.15),transparent 56%),
      radial-gradient(ellipse 820px 420px at 78% 88%,rgba(255,79,216,.13),transparent 60%) !important;
    opacity:.92;
  }
  body::after{opacity:.52}
  .wrap{max-width:1180px;padding:44px 26px 92px}
  html.site-authenticated-root .wrap{padding-top:104px}

  .retro-sun{
    position:fixed;left:50%;bottom:4vh;width:min(44vw,520px);aspect-ratio:1;
    transform:translateX(-50%);z-index:-3;pointer-events:none;border-radius:50%;
    background:
      repeating-linear-gradient(to bottom,rgba(255,93,207,.9) 0 8px,rgba(255,93,207,0) 8px 17px),
      linear-gradient(180deg,#ffd166 0%,#ff7ccf 47%,#8c62ff 100%);
    box-shadow:0 0 90px rgba(255,79,216,.24),0 0 150px rgba(140,98,255,.18);
    opacity:.25;filter:saturate(1.2);
    mask-image:linear-gradient(to bottom,#000 0 63%,transparent 92%);
  }
  .retro-grid{
    z-index:-2 !important;height:39vh !important;opacity:.72 !important;
    background:
      linear-gradient(to top,rgba(57,231,255,.22),rgba(57,231,255,0) 66%),
      repeating-linear-gradient(90deg,rgba(57,231,255,.2) 0 1px,transparent 1px 68px),
      repeating-linear-gradient(0deg,rgba(255,79,216,.18) 0 1px,transparent 1px 48px) !important;
    box-shadow:0 -38px 100px rgba(57,231,255,.05);
  }
  .retro-chevrons{position:fixed;inset:0;z-index:-1;pointer-events:none;overflow:hidden}
  .retro-chevron{
    position:absolute;font:800 clamp(18px,2.2vw,38px)/1 'JetBrains Mono',monospace;
    letter-spacing:-.35em;opacity:.19;filter:drop-shadow(0 0 10px currentColor);
    animation:retroChevronFloat 11s ease-in-out infinite alternate;
    transform:scale(var(--scale,1));
  }
  .retro-chevron:nth-child(3n+1){color:var(--cyan)}
  .retro-chevron:nth-child(3n+2){color:var(--pink)}
  .retro-chevron:nth-child(3n){color:var(--violet)}
  @keyframes retroChevronFloat{from{transform:translate3d(-8px,0,0) scale(var(--scale,1))}to{transform:translate3d(12px,-10px,0) scale(var(--scale,1))}}
  .scanline-layer{opacity:.055 !important;z-index:1200 !important}

  /* Fixed authenticated navigation */
  .site-topbar{
    position:fixed;inset:12px 14px auto;z-index:1100;
    border:1px solid rgba(57,231,255,.22);border-radius:16px;
    background:linear-gradient(180deg,rgba(8,11,31,.92),rgba(12,17,43,.88));
    backdrop-filter:blur(18px) saturate(1.18);
    box-shadow:0 16px 48px rgba(0,0,0,.34),var(--glow-cyan);
  }
  .site-topbar-inner{max-width:1240px;margin:0 auto;display:flex;align-items:center;gap:18px;padding:9px 11px}
  .site-brand{display:flex;align-items:center;gap:10px;color:var(--text);text-decoration:none;min-width:0}
  .site-brand-mark{
    width:34px;height:34px;display:grid;place-items:center;border-radius:10px;
    background:linear-gradient(135deg,rgba(255,79,216,.22),rgba(57,231,255,.16));
    border:1px solid rgba(57,231,255,.3);color:var(--cyan);font:800 16px 'JetBrains Mono',monospace;
    box-shadow:inset 0 0 18px rgba(57,231,255,.1),var(--glow-cyan);
  }
  .site-brand-copy{display:grid;line-height:1.05}
  .site-brand-title{font:700 13px 'JetBrains Mono',monospace;letter-spacing:.04em}
  .site-brand-sub{font:8px 'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.16em;color:var(--text-dim);margin-top:4px}
  .site-nav{display:flex;align-items:center;gap:5px;overflow:auto;scrollbar-width:none;margin-left:auto}
  .site-nav::-webkit-scrollbar{display:none}
  .site-nav-link{
    flex:none;padding:8px 10px;border-radius:9px;color:var(--text-dim);text-decoration:none;
    font:600 10px 'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.06em;
    border:1px solid transparent;transition:.16s ease;
  }
  .site-nav-link:hover,.site-nav-link.active{color:var(--cyan);border-color:rgba(57,231,255,.24);background:rgba(57,231,255,.07);box-shadow:0 0 16px rgba(57,231,255,.1)}
  .site-account{display:flex;align-items:center;gap:8px;padding-left:9px;border-left:1px solid rgba(140,98,255,.24)}
  .site-user{max-width:110px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--text-dim);font:9px 'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.06em}
  .site-account form{margin:0}
  .site-logout-button{
    padding:8px 10px;border-radius:9px;border:1px solid rgba(255,79,216,.24);
    background:rgba(255,79,216,.07);color:#ffc4ef;font:700 9px 'JetBrains Mono',monospace;
    text-transform:uppercase;letter-spacing:.06em;
  }
  .site-logout-button:hover{border-color:var(--pink);color:#fff;box-shadow:var(--glow-pink)}

  /* Page headings */
  header{position:relative;margin-bottom:25px}
  .eyebrow{
    color:var(--cyan)!important;font-weight:700;letter-spacing:.18em!important;
    text-shadow:0 0 12px rgba(57,231,255,.35);
  }
  h1{
    color:#fff!important;font-weight:700!important;
    text-shadow:0 0 10px rgba(255,255,255,.12),0 0 25px rgba(140,98,255,.34),0 0 52px rgba(255,79,216,.14)!important;
  }
  .starline{
    height:8px!important;border:0!important;border-radius:999px;
    background:linear-gradient(90deg,transparent 0%,var(--pink) 14%,var(--violet) 50%,var(--cyan) 86%,transparent 100%)!important;
    box-shadow:0 0 14px rgba(57,231,255,.18),0 0 22px rgba(255,79,216,.12);
    opacity:.62!important;
  }
  .nav-back{padding:8px 10px;border:1px solid rgba(140,98,255,.2);border-radius:9px;background:rgba(14,19,48,.62)}
  .nav-back:hover{border-color:rgba(57,231,255,.36);box-shadow:var(--glow-cyan)}

  /* Panels and cards */
  .panel,.login-card,.unavailable-card,.share-grid,.share-hero{
    background:var(--panel-gradient)!important;
    border:1px solid rgba(140,98,255,.24)!important;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.035),0 18px 44px rgba(0,0,0,.26)!important;
  }
  .panel:hover{border-color:rgba(57,231,255,.36)!important;box-shadow:var(--glow-cyan),0 20px 50px rgba(0,0,0,.32)!important}
  .panel h2,.section-label,.summary-label{color:#aeb8e8!important}
  .portal-card{
    background:linear-gradient(155deg,rgba(18,24,61,.96),rgba(7,11,30,.96))!important;
    border:1px solid rgba(140,98,255,.22)!important;
    box-shadow:0 16px 34px rgba(0,0,0,.22)!important;
    isolation:isolate;
  }
  .portal-card::before{
    content:'';position:absolute;inset:0 0 auto;height:3px;border-radius:var(--radius) var(--radius) 0 0;
    background:linear-gradient(90deg,var(--pink),var(--violet),var(--cyan));opacity:.7;
  }
  .portal-card::after{
    content:'';position:absolute;inset:0;z-index:-1;border-radius:inherit;opacity:0;
    background:radial-gradient(circle at 80% 10%,rgba(57,231,255,.13),transparent 35%),radial-gradient(circle at 12% 90%,rgba(255,79,216,.1),transparent 38%);
    transition:opacity .2s ease;
  }
  .portal-card:hover{border-color:rgba(57,231,255,.5)!important;box-shadow:var(--glow-cyan),0 22px 50px rgba(0,0,0,.34)!important}
  .portal-card:hover::after{opacity:1}
  .portal-icon{border-radius:12px!important;background:linear-gradient(135deg,rgba(255,79,216,.15),rgba(57,231,255,.09))!important;border-color:rgba(57,231,255,.22)!important}
  .portal-title{color:#fff;text-shadow:0 0 16px rgba(140,98,255,.18)}
  .portal-arrow{color:var(--cyan)!important}

  /* Inputs, selects, buttons */
  input[type="text"],input[type="password"],input[type="number"],input[type="date"],input[type="search"],input[type="url"],input[type="time"],textarea,select{
    background:var(--control-gradient)!important;border:1px solid rgba(140,98,255,.28)!important;
    border-radius:10px!important;color:var(--text)!important;box-shadow:inset 0 1px 0 rgba(255,255,255,.025)!important;
  }
  input::placeholder,textarea::placeholder{color:#7784bd}
  input:focus,textarea:focus,select:focus{border-color:rgba(57,231,255,.72)!important;outline:0!important;box-shadow:var(--glow-cyan)!important}
  .btn-primary,.share-button.primary,.primary-link{
    background:linear-gradient(90deg,var(--pink),var(--violet) 52%,var(--cyan))!important;
    color:#fff!important;border:1px solid rgba(255,255,255,.12)!important;border-radius:10px!important;
    box-shadow:var(--glow-pink)!important;text-transform:uppercase;letter-spacing:.05em;
  }
  .btn-primary:hover,.share-button.primary:hover,.primary-link:hover{filter:brightness(1.1);box-shadow:var(--glow-pink),var(--glow-cyan)!important}
  .btn-secondary,.secondary,.share-button.secondary,.secondary-link,.btn-small,.empty-action,.folder-main-action{
    background:var(--control-gradient)!important;border:1px solid rgba(140,98,255,.27)!important;color:var(--text-dim)!important;border-radius:9px!important;
  }
  .btn-secondary:hover,.secondary:hover,.share-button.secondary:hover,.secondary-link:hover,.btn-small:hover,.empty-action:hover,.folder-main-action:hover{
    border-color:var(--cyan)!important;color:var(--cyan)!important;box-shadow:var(--glow-cyan)!important;
  }
  .danger-button{background:linear-gradient(90deg,#cc3454,var(--red))!important;color:#fff!important;border-radius:9px!important;box-shadow:0 0 22px rgba(255,107,130,.18)}
  .danger-button:hover{filter:brightness(1.08)}

  /* Badges and status chips */
  .badge,.chip,.category-tag,.subject-tag,.recur-badge,.snooze-btn,.notes-toggle,.bulk-btn,.hide-done-toggle{
    border-color:rgba(140,98,255,.28)!important;background:rgba(140,98,255,.055)!important;
  }
  .badge.live,.chip.active{color:#d7fbff!important;border-color:rgba(57,231,255,.46)!important;background:rgba(57,231,255,.08)!important;box-shadow:0 0 14px rgba(57,231,255,.1)}
  .badge.soon{border-color:rgba(255,209,102,.4)!important;background:rgba(255,209,102,.07)!important}

  /* Shared tables and lists */
  table{border-collapse:separate!important;border-spacing:0 7px!important}
  table thead th{border-bottom:0!important;color:#aeb8e8!important}
  table tbody tr{background:linear-gradient(180deg,rgba(17,24,58,.84),rgba(10,15,38,.92))}
  table tbody td{border-top:1px solid rgba(140,98,255,.17)!important;border-bottom:1px solid rgba(140,98,255,.17)!important}
  table tbody td:first-child{border-left:1px solid rgba(140,98,255,.17)!important;border-radius:10px 0 0 10px}
  table tbody td:last-child{border-right:1px solid rgba(140,98,255,.17)!important;border-radius:0 10px 10px 0}
  .pagination a,.pagination span,.toolbar-link,.top-link,.view-button{
    background:var(--control-gradient)!important;border-color:rgba(140,98,255,.26)!important;border-radius:9px!important;
  }
  .pagination a:hover,.pagination .active a,.toolbar-link:hover,.toolbar-link.active,.top-link:hover,.view-button:hover,.view-button.active{
    border-color:var(--cyan)!important;color:var(--cyan)!important;box-shadow:var(--glow-cyan)!important;
  }

  /* Video and task pages */
  .player-frame{border-color:rgba(140,98,255,.28)!important;border-radius:12px!important;box-shadow:inset 0 0 40px rgba(0,0,0,.65)}
  .player-frame.is-playing{border-color:rgba(57,231,255,.58)!important;box-shadow:var(--glow-cyan)!important}
  .video-item,.task-item,.file-item,.selected-file{
    background:linear-gradient(160deg,rgba(18,25,61,.94),rgba(8,13,34,.96))!important;
    border-color:rgba(140,98,255,.2)!important;border-radius:11px!important;
  }
  .video-item:hover,.video-item.active,.task-item:hover,.file-item:hover,.file-item:focus-visible{
    border-color:rgba(57,231,255,.52)!important;box-shadow:var(--glow-cyan)!important;
  }
  .video-thumb,.task-check,.file-type-badge,.badge-file{
    background:linear-gradient(135deg,rgba(255,79,216,.15),rgba(57,231,255,.08))!important;
    border-color:rgba(57,231,255,.24)!important;
  }
  .dropzone{
    background:linear-gradient(160deg,rgba(18,25,61,.66),rgba(7,11,30,.72))!important;
    border-color:rgba(140,98,255,.34)!important;border-radius:12px!important;
  }
  .dropzone.drag,.dropzone.attention{border-color:var(--cyan)!important;box-shadow:var(--glow-cyan)!important}
  .task-item.priority-high{border-left-color:var(--pink)!important}
  .task-item.priority-medium{border-left-color:var(--gold)!important}
  .task-check.checked{background:rgba(57,231,255,.13)!important;box-shadow:var(--glow-cyan)}

  /* Vault / Drive */
  .summary-card,.folder-card,.upload-location,.filter-state{
    background:linear-gradient(160deg,rgba(18,25,61,.9),rgba(8,13,34,.94))!important;
    border-color:rgba(140,98,255,.22)!important;
  }
  .summary-card{box-shadow:0 12px 28px rgba(0,0,0,.18)}
  .summary-value{color:#fff!important;text-shadow:0 0 18px rgba(140,98,255,.24)}
  .folder-card:hover{border-color:var(--cyan)!important;box-shadow:var(--glow-cyan)!important}
  .folder-icon{filter:drop-shadow(0 0 10px rgba(255,209,102,.3))}
  .action-menu-panel,.modal-card,.drive-preview-card,.folder-download-card{
    background:linear-gradient(165deg,rgba(14,19,48,.99),rgba(6,10,27,.99))!important;
    border-color:rgba(57,231,255,.25)!important;box-shadow:0 28px 80px rgba(0,0,0,.55),var(--glow-cyan)!important;
  }
  .menu-action:hover{background:linear-gradient(90deg,rgba(255,79,216,.12),rgba(57,231,255,.09))!important}
  .preview-topbar,.preview-info,.share-info{background:linear-gradient(180deg,rgba(16,22,54,.98),rgba(9,14,35,.98))!important}
  .preview-stage,.share-stage,.preview-loader{background:#050711!important}
  .file-type-badge{box-shadow:inset 0 0 22px rgba(57,231,255,.08)}
  .progress-bar,.selected-file-progress span,.folder-download-progress span,.progress-shimmer{background:linear-gradient(90deg,var(--pink),var(--violet),var(--cyan))!important;box-shadow:0 0 12px rgba(57,231,255,.24)}

  /* Login and locked vault */
  .login-shell{min-height:calc(100vh - 70px)!important}
  .login-card,.gate-panel{
    position:relative;overflow:hidden;border-radius:18px!important;
    border-color:rgba(57,231,255,.28)!important;box-shadow:0 34px 100px rgba(0,0,0,.48),var(--glow-cyan)!important;
  }
  .login-card::after,.gate-panel::after{
    content:'';position:absolute;inset:0;pointer-events:none;
    background:radial-gradient(circle at 100% 0,rgba(57,231,255,.13),transparent 34%),radial-gradient(circle at 0 100%,rgba(255,79,216,.12),transparent 38%);
  }
  .login-card>* ,.gate-panel>*{position:relative;z-index:1}
  .login-mark,.gate-icon{
    background:linear-gradient(135deg,rgba(255,79,216,.18),rgba(57,231,255,.1))!important;
    border-color:rgba(57,231,255,.36)!important;border-radius:15px!important;box-shadow:var(--glow-cyan)!important;
  }
  .password-toggle,.show-password{color:var(--cyan)!important}

  /* Public share pages */
  .shared-wrap{max-width:1240px!important;padding:48px 24px 72px!important}
  .share-button{border-radius:9px!important}
  .share-grid{border-color:rgba(57,231,255,.22)!important;border-radius:16px!important}
  .share-file-icon,.hero-folder-icon{
    background:linear-gradient(135deg,rgba(255,79,216,.16),rgba(57,231,255,.1))!important;
    border-color:rgba(57,231,255,.28)!important;box-shadow:var(--glow-cyan)!important;
  }
  .share-note{border-color:rgba(57,231,255,.25)!important;background:rgba(57,231,255,.055)!important}
  .folder-card,.file-row{background:linear-gradient(160deg,rgba(18,25,61,.92),rgba(8,13,34,.94))!important;border-color:rgba(140,98,255,.2)!important}
  .folder-card:hover,.file-row:hover{border-color:var(--cyan)!important;box-shadow:var(--glow-cyan)!important}

  .notif-bell-wrap{top:82px!important;right:20px!important;z-index:1060!important}
  .notif-bell{border-color:rgba(255,79,216,.3)!important;background:linear-gradient(180deg,rgba(18,24,58,.96),rgba(8,12,31,.96))!important;box-shadow:var(--glow-pink)!important}
  .notif-panel{background:linear-gradient(165deg,rgba(14,19,48,.99),rgba(6,10,27,.99))!important;border-color:rgba(57,231,255,.25)!important;box-shadow:var(--glow-cyan),0 24px 60px rgba(0,0,0,.5)!important}

  /* Home dashboard enhancements */
  .home-shell{position:relative}
  .home-hero{
    position:relative;padding:30px 32px 32px;margin-bottom:26px;border-radius:20px;
    background:linear-gradient(145deg,rgba(17,23,58,.88),rgba(7,10,28,.78));
    border:1px solid rgba(57,231,255,.2);box-shadow:0 26px 70px rgba(0,0,0,.26),var(--glow-violet);overflow:hidden;
  }
  .home-hero::after{content:'';position:absolute;right:-12%;top:-40%;width:360px;height:360px;border-radius:50%;background:radial-gradient(circle,rgba(255,79,216,.16),transparent 65%);pointer-events:none}
  .home-hero h1{font-size:clamp(44px,7vw,76px)!important;line-height:.9;margin-bottom:18px!important}
  .home-sub{max-width:650px!important;font-size:15px!important}
  .home-status{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}
  .home-status-chip{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border:1px solid rgba(140,98,255,.22);border-radius:999px;background:rgba(13,18,45,.72);font:9px 'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.08em;color:var(--text-dim)}
  .home-status-chip i{width:6px;height:6px;border-radius:50%;background:var(--green);box-shadow:0 0 9px rgba(85,239,182,.65)}

  /* Empty states */
  .empty,.empty-state,.player-empty{
    border:1px dashed rgba(140,98,255,.3)!important;border-radius:12px!important;
    background:linear-gradient(160deg,rgba(18,25,61,.34),rgba(7,11,30,.28))!important;
  }

  /* Mobile */
  @media(max-width:900px){
    .site-topbar{inset:8px 8px auto}.site-topbar-inner{gap:8px;padding:8px}.site-brand-copy{display:none}.site-account{padding-left:5px}.site-user{display:none}
    .wrap{padding:38px 18px 72px}
    html.site-authenticated-root .wrap{padding-top:88px}
    .home-hero{padding:25px 21px}
  }
  @media(max-width:620px){
    .site-nav-link{padding:8px 8px;font-size:9px}.site-brand-mark{width:32px;height:32px}.site-logout-button{padding:8px}
    .notif-bell-wrap{top:74px!important;right:12px!important}
    .wrap{padding-left:13px;padding-right:13px}.shared-wrap{padding:28px 13px 54px!important}
    h1{font-size:36px!important}.home-hero h1{font-size:48px!important}.retro-sun{width:88vw;bottom:7vh;opacity:.18}
    .portal-card{padding:24px 18px!important}
  }
</style>
<?php if ((bool) session()->get('site_authenticated')): ?><script>document.documentElement.classList.add('site-authenticated-root');</script><?php endif; ?>
