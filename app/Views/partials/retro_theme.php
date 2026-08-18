<style>
  /* =========================================================
     ARCADE // SYNTHWAVE DESIGN SYSTEM v2
     This layer intentionally loads after page-local CSS.
     ========================================================= */
  :root{
    --bg:#03040d;
    --surface:#090d21;
    --surface-2:#0e1532;
    --surface-3:#151d43;
    --hairline:#2c3470;
    --text:#f6f8ff;
    --text-dim:#9aa7d7;
    --cyan:#35e9ff;
    --cyan-soft:#99f6ff;
    --violet:#8a63ff;
    --pink:#ff4fd8;
    --gold:#ffd166;
    --red:#ff647d;
    --green:#50f0b6;
    --font-display:'Orbitron','Arial Black',sans-serif;
    --font-body:'Rajdhani','Segoe UI',sans-serif;
    --font-mono:'Share Tech Mono','Consolas',monospace;
    --radius:12px;
    --clip:polygon(0 0,calc(100% - 10px) 0,100% 10px,100% 100%,10px 100%,0 calc(100% - 10px));
    --panel-gradient:linear-gradient(145deg,rgba(18,25,61,.97),rgba(7,11,29,.98));
    --control-gradient:linear-gradient(180deg,rgba(19,27,63,.96),rgba(10,15,39,.98));
    --glow-cyan:0 0 0 1px rgba(53,233,255,.12),0 0 16px rgba(53,233,255,.22),0 0 42px rgba(53,233,255,.08);
    --glow-pink:0 0 0 1px rgba(255,79,216,.12),0 0 18px rgba(255,79,216,.22),0 0 44px rgba(255,79,216,.08);
    --glow-violet:0 0 0 1px rgba(138,99,255,.12),0 0 22px rgba(138,99,255,.2);
  }

  html{background:var(--bg);color-scheme:dark;scroll-behavior:smooth}
  body{
    font-family:var(--font-body)!important;
    font-size:16px;
    letter-spacing:.012em;
    background:
      radial-gradient(circle at 8% -6%,rgba(53,233,255,.13),transparent 28%),
      radial-gradient(circle at 91% 2%,rgba(255,79,216,.15),transparent 31%),
      radial-gradient(circle at 50% 112%,rgba(138,99,255,.22),transparent 40%),
      linear-gradient(180deg,#02030a 0%,#050819 48%,#080d24 100%)!important;
    color:var(--text);
    min-height:100vh;
  }
  body::before{
    background-image:
      linear-gradient(115deg,transparent 0 44%,rgba(53,233,255,.028) 45%,transparent 46%),
      radial-gradient(ellipse 1100px 580px at 9% -14%,rgba(138,99,255,.22),transparent 58%),
      radial-gradient(ellipse 880px 520px at 98% 4%,rgba(53,233,255,.15),transparent 56%),
      radial-gradient(ellipse 760px 450px at 78% 88%,rgba(255,79,216,.12),transparent 61%)!important;
    opacity:.92;
  }
  body::after{opacity:.38!important}
  .wrap{max-width:1220px!important;padding:46px 28px 94px!important}
  html.site-authenticated-root .wrap{padding-top:112px!important}

  /* Background chrome */
  .retro-sun{
    position:fixed;left:50%;bottom:2vh;width:min(48vw,560px);aspect-ratio:1;
    transform:translateX(-50%);z-index:-3;pointer-events:none;border-radius:50%;
    background:
      repeating-linear-gradient(to bottom,rgba(255,100,213,.92) 0 7px,transparent 7px 15px),
      linear-gradient(180deg,#ffe08a 0%,#ff73d0 48%,#865eff 100%);
    box-shadow:0 0 100px rgba(255,79,216,.2),0 0 190px rgba(138,99,255,.13);
    opacity:.2;filter:saturate(1.15);mask-image:linear-gradient(to bottom,#000 0 64%,transparent 91%);
  }
  .retro-grid{
    z-index:-2!important;height:42vh!important;opacity:.62!important;
    background:
      linear-gradient(to top,rgba(53,233,255,.2),transparent 68%),
      repeating-linear-gradient(90deg,rgba(53,233,255,.18) 0 1px,transparent 1px 70px),
      repeating-linear-gradient(0deg,rgba(255,79,216,.16) 0 1px,transparent 1px 50px)!important;
    box-shadow:0 -44px 110px rgba(53,233,255,.04);
  }
  .retro-chevrons{position:fixed;inset:0;z-index:-1;pointer-events:none;overflow:hidden}
  .retro-chevron{
    position:absolute;font:800 clamp(18px,2.1vw,36px)/1 var(--font-mono)!important;
    letter-spacing:-.32em;opacity:.14;filter:drop-shadow(0 0 10px currentColor);
    animation:retroChevronFloat 12s ease-in-out infinite alternate;
    transform:scale(var(--scale,1));
  }
  .retro-chevron:nth-child(3n+1){color:var(--cyan)}
  .retro-chevron:nth-child(3n+2){color:var(--pink)}
  .retro-chevron:nth-child(3n){color:var(--violet)}
  @keyframes retroChevronFloat{from{transform:translate3d(-8px,0,0) scale(var(--scale,1))}to{transform:translate3d(12px,-10px,0) scale(var(--scale,1))}}
  .scanline-layer{opacity:.035!important;z-index:1200!important}

  /* Typography */
  h1,h2,h3,.portal-title,.site-brand-title,.login-intro h2,.unavailable-card h1{
    font-family:var(--font-display)!important;
    font-style:normal!important;
    text-transform:uppercase;
  }
  h1{
    color:#fff!important;font-weight:800!important;letter-spacing:.035em!important;
    line-height:1.02!important;text-shadow:0 0 9px rgba(255,255,255,.12),0 0 24px rgba(138,99,255,.34),0 0 48px rgba(255,79,216,.13)!important;
  }
  p,.portal-desc,.login-sub,.file-desc,.detail-value{font-family:var(--font-body)!important}
  .eyebrow,.section-label,.summary-label,label,.badge,.chip,.category-tag,.result-count,.site-nav-link,.site-user,.site-logout-button,.toolbar-link,.top-link,.file-sub-text,.original-name,.login-footer,.login-console-label,.home-status-chip,.portal-number,.portal-route{
    font-family:var(--font-mono)!important;
  }
  .eyebrow{
    color:var(--cyan)!important;font-size:11px!important;font-weight:400!important;
    letter-spacing:.18em!important;text-shadow:0 0 12px rgba(53,233,255,.38);
  }
  label{font-weight:600;letter-spacing:.035em;color:#aeb9e5!important}
  a{transition:color .16s ease,border-color .16s ease,background .16s ease,box-shadow .16s ease,transform .16s ease}

  /* Authenticated HUD */
  .site-topbar{
    position:fixed;inset:12px 14px auto;z-index:1100;
    border:1px solid rgba(53,233,255,.22);border-radius:11px;
    clip-path:var(--clip);
    background:linear-gradient(180deg,rgba(8,12,31,.95),rgba(11,17,43,.92));
    backdrop-filter:blur(18px) saturate(1.16);
    box-shadow:0 18px 52px rgba(0,0,0,.36),var(--glow-cyan);
  }
  .site-topbar::after{
    content:'';position:absolute;left:8%;right:8%;bottom:0;height:1px;
    background:linear-gradient(90deg,transparent,var(--pink),var(--cyan),transparent);opacity:.62;
  }
  .site-topbar-inner{max-width:1280px;margin:0 auto;display:flex;align-items:center;gap:18px;padding:9px 12px}
  .site-brand{display:flex;align-items:center;gap:10px;color:var(--text);text-decoration:none;min-width:0}
  .site-brand-mark{
    width:38px;height:38px;display:grid;place-items:center;border-radius:8px;clip-path:var(--clip);
    background:linear-gradient(135deg,rgba(255,79,216,.23),rgba(53,233,255,.16));
    border:1px solid rgba(53,233,255,.32);color:#fff;font:800 12px var(--font-display)!important;
    letter-spacing:.04em;box-shadow:inset 0 0 20px rgba(53,233,255,.1),var(--glow-cyan);
  }
  .site-brand-copy{display:grid;line-height:1.05}
  .site-brand-title{font-size:12px!important;font-weight:800!important;letter-spacing:.08em;color:#fff}
  .site-brand-sub{font:10px var(--font-mono)!important;text-transform:uppercase;letter-spacing:.12em;color:var(--text-dim);margin-top:4px}
  .site-nav{display:flex;align-items:center;gap:4px;overflow:auto;scrollbar-width:none;margin-left:auto}
  .site-nav::-webkit-scrollbar{display:none}
  .site-nav-link{
    position:relative;flex:none;padding:9px 11px;border-radius:7px;color:var(--text-dim);text-decoration:none;
    font-size:11px!important;text-transform:uppercase;letter-spacing:.07em;border:1px solid transparent;
  }
  .site-nav-link::after{content:'';position:absolute;left:18%;right:18%;bottom:3px;height:1px;background:var(--cyan);transform:scaleX(0);box-shadow:0 0 8px var(--cyan);transition:transform .16s ease}
  .site-nav-link:hover,.site-nav-link.active{color:#fff;border-color:rgba(53,233,255,.22);background:linear-gradient(180deg,rgba(53,233,255,.09),rgba(138,99,255,.06))}
  .site-nav-link:hover::after,.site-nav-link.active::after{transform:scaleX(1)}
  .site-account{display:flex;align-items:center;gap:8px;padding-left:10px;border-left:1px solid rgba(138,99,255,.23)}
  .site-user{max-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--text-dim);font-size:10px!important;text-transform:uppercase;letter-spacing:.06em}
  .site-account form{margin:0}
  .site-logout-button{
    padding:8px 10px;border-radius:7px;border:1px solid rgba(255,79,216,.28);
    background:rgba(255,79,216,.07);color:#ffc9f0;font-size:10px!important;text-transform:uppercase;letter-spacing:.06em;
  }
  .site-logout-button:hover{border-color:var(--pink);color:#fff;background:rgba(255,79,216,.13);box-shadow:var(--glow-pink)}

  /* Page header */
  header{position:relative;margin-bottom:26px}
  .starline{
    height:4px!important;border:0!important;border-radius:0!important;
    clip-path:polygon(0 25%,18% 25%,20% 0,45% 0,47% 25%,100% 25%,100% 75%,58% 75%,56% 100%,31% 100%,29% 75%,0 75%);
    background:linear-gradient(90deg,transparent,var(--pink) 18%,var(--violet) 50%,var(--cyan) 82%,transparent)!important;
    box-shadow:0 0 14px rgba(53,233,255,.24),0 0 22px rgba(255,79,216,.14);opacity:.78!important;
  }
  .nav-back{
    padding:8px 11px;border:1px solid rgba(138,99,255,.22);border-radius:7px;
    background:rgba(12,18,45,.66);font-family:var(--font-mono)!important;font-size:11px!important;
  }
  .nav-back:hover{border-color:rgba(53,233,255,.45);color:var(--cyan)!important;box-shadow:var(--glow-cyan);transform:translateX(-3px)}

  /* Cards / panels */
  .panel,.login-card,.gate-panel,.unavailable-card,.share-grid,.share-hero,.home-hero{
    background:var(--panel-gradient)!important;
    border:1px solid rgba(138,99,255,.25)!important;
    border-radius:12px!important;
    clip-path:var(--clip);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.035),0 18px 48px rgba(0,0,0,.28)!important;
  }
  .panel{position:relative}
  .panel::before,.login-card::before,.gate-panel::before,.home-hero::before{
    content:'';position:absolute;left:0;top:0;width:78px;height:2px;
    background:linear-gradient(90deg,var(--pink),var(--cyan));box-shadow:0 0 12px rgba(53,233,255,.28);z-index:3;
  }
  .panel:hover{border-color:rgba(53,233,255,.38)!important;box-shadow:var(--glow-cyan),0 22px 54px rgba(0,0,0,.34)!important}
  .panel h2,.section-label,.summary-label{color:#b3bde7!important}

  .portal-grid{gap:16px!important}
  .portal-card{
    position:relative!important;isolation:isolate;overflow:hidden!important;
    background:linear-gradient(150deg,rgba(18,25,61,.97),rgba(7,11,29,.98))!important;
    border:1px solid rgba(138,99,255,.23)!important;border-radius:11px!important;clip-path:var(--clip);
    box-shadow:0 16px 38px rgba(0,0,0,.24)!important;
  }
  .portal-card::before{content:'';position:absolute;inset:0 0 auto;height:2px;background:linear-gradient(90deg,var(--pink),var(--violet),var(--cyan));opacity:.78}
  .portal-card::after{content:'';position:absolute;inset:0;z-index:-1;opacity:0;background:radial-gradient(circle at 85% 5%,rgba(53,233,255,.14),transparent 34%),radial-gradient(circle at 8% 92%,rgba(255,79,216,.11),transparent 38%);transition:opacity .2s ease}
  .portal-card:hover{border-color:rgba(53,233,255,.52)!important;box-shadow:var(--glow-cyan),0 24px 56px rgba(0,0,0,.36)!important;transform:translateY(-4px)}
  .portal-card:hover::after{opacity:1}
  .portal-number{position:absolute;right:16px;top:14px;color:rgba(154,167,215,.45);font-size:10px;letter-spacing:.12em}
  .portal-icon{
    width:48px!important;height:48px!important;border-radius:8px!important;clip-path:var(--clip);
    background:linear-gradient(135deg,rgba(255,79,216,.17),rgba(53,233,255,.1))!important;
    border-color:rgba(53,233,255,.25)!important;color:var(--cyan)!important;box-shadow:inset 0 0 18px rgba(53,233,255,.07);
  }
  .portal-title{font-size:19px!important;letter-spacing:.045em;color:#fff;text-shadow:0 0 15px rgba(138,99,255,.18)}
  .portal-desc{font-size:15px!important;line-height:1.55!important}
  .portal-route{display:block;margin-top:3px;color:#7582b7;font-size:9px;letter-spacing:.08em;text-transform:uppercase}
  .portal-arrow{color:var(--cyan)!important;font-family:var(--font-mono)!important;font-size:11px!important;text-transform:uppercase;letter-spacing:.06em}

  /* Controls */
  input[type="text"],input[type="password"],input[type="number"],input[type="date"],input[type="search"],input[type="url"],input[type="time"],textarea,select{
    background:var(--control-gradient)!important;border:1px solid rgba(138,99,255,.29)!important;
    border-radius:8px!important;color:var(--text)!important;box-shadow:inset 0 1px 0 rgba(255,255,255,.025)!important;
    font-family:var(--font-body)!important;font-size:15px!important;font-weight:500;
  }
  input::placeholder,textarea::placeholder{color:#7481b8}
  input:focus,textarea:focus,select:focus{border-color:rgba(53,233,255,.75)!important;outline:0!important;box-shadow:var(--glow-cyan)!important}
  button,.btn-primary,.btn-secondary,.danger-button,.share-button,.primary-link,.secondary-link,.btn-small,.empty-action,.folder-main-action,.picker-button{
    font-family:var(--font-display)!important;font-size:10px!important;font-weight:700!important;letter-spacing:.07em!important;text-transform:uppercase;
  }
  .btn-primary,.share-button.primary,.primary-link{
    background:linear-gradient(90deg,var(--pink),var(--violet) 53%,var(--cyan))!important;
    color:#fff!important;border:1px solid rgba(255,255,255,.13)!important;border-radius:8px!important;clip-path:var(--clip);
    box-shadow:var(--glow-pink)!important;
  }
  .btn-primary:hover,.share-button.primary:hover,.primary-link:hover{filter:brightness(1.1);box-shadow:var(--glow-pink),var(--glow-cyan)!important;transform:translateY(-1px)}
  .btn-secondary,.secondary,.share-button.secondary,.secondary-link,.btn-small,.empty-action,.folder-main-action,.picker-button{
    background:var(--control-gradient)!important;border:1px solid rgba(138,99,255,.28)!important;color:#b7c0e8!important;border-radius:8px!important;
  }
  .btn-secondary:hover,.secondary:hover,.share-button.secondary:hover,.secondary-link:hover,.btn-small:hover,.empty-action:hover,.folder-main-action:hover,.picker-button:hover{
    border-color:var(--cyan)!important;color:var(--cyan)!important;box-shadow:var(--glow-cyan)!important;
  }
  .danger-button{background:linear-gradient(90deg,#b72d4b,var(--red))!important;color:#fff!important;border-radius:8px!important;box-shadow:0 0 22px rgba(255,100,125,.18)}

  /* Badges / tags */
  .badge,.chip,.category-tag,.subject-tag,.recur-badge,.snooze-btn,.notes-toggle,.bulk-btn,.hide-done-toggle{
    border-color:rgba(138,99,255,.3)!important;background:rgba(138,99,255,.06)!important;border-radius:6px!important;
  }
  .badge.live,.chip.active{color:#d8fcff!important;border-color:rgba(53,233,255,.48)!important;background:rgba(53,233,255,.08)!important;box-shadow:0 0 14px rgba(53,233,255,.1)}
  .badge.soon{border-color:rgba(255,209,102,.42)!important;background:rgba(255,209,102,.07)!important}

  /* Tables / toolbars */
  table{border-collapse:separate!important;border-spacing:0 7px!important}
  table thead th{border-bottom:0!important;color:#aeb8e5!important;font-family:var(--font-mono)!important;text-transform:uppercase;letter-spacing:.06em;font-size:10px!important}
  table tbody tr{background:linear-gradient(180deg,rgba(17,24,58,.86),rgba(9,14,37,.94))}
  table tbody td{border-top:1px solid rgba(138,99,255,.18)!important;border-bottom:1px solid rgba(138,99,255,.18)!important}
  table tbody td:first-child{border-left:1px solid rgba(138,99,255,.18)!important;border-radius:8px 0 0 8px}
  table tbody td:last-child{border-right:1px solid rgba(138,99,255,.18)!important;border-radius:0 8px 8px 0}
  .pagination a,.pagination span,.toolbar-link,.top-link,.view-button{
    background:var(--control-gradient)!important;border-color:rgba(138,99,255,.27)!important;border-radius:7px!important;font-family:var(--font-mono)!important;
  }
  .pagination a:hover,.pagination .active a,.toolbar-link:hover,.toolbar-link.active,.top-link:hover,.view-button:hover,.view-button.active{
    border-color:var(--cyan)!important;color:var(--cyan)!important;box-shadow:var(--glow-cyan)!important;
  }

  /* Video, task and vault lists */
  .player-frame{border-color:rgba(138,99,255,.28)!important;border-radius:10px!important;clip-path:var(--clip);box-shadow:inset 0 0 44px rgba(0,0,0,.65)}
  .player-frame.is-playing{border-color:rgba(53,233,255,.6)!important;box-shadow:var(--glow-cyan)!important}
  .video-item,.task-item,.file-item,.selected-file,.folder-card,.file-row,.summary-card,.upload-location,.filter-state{
    background:linear-gradient(155deg,rgba(18,25,61,.95),rgba(8,13,34,.97))!important;
    border-color:rgba(138,99,255,.21)!important;border-radius:9px!important;
  }
  .video-item:hover,.video-item.active,.task-item:hover,.file-item:hover,.file-item:focus-visible,.folder-card:hover,.file-row:hover{
    border-color:rgba(53,233,255,.54)!important;box-shadow:var(--glow-cyan)!important;
  }
  .video-thumb,.task-check,.file-type-badge,.badge-file,.share-file-icon,.hero-folder-icon{
    background:linear-gradient(135deg,rgba(255,79,216,.16),rgba(53,233,255,.09))!important;
    border-color:rgba(53,233,255,.25)!important;border-radius:8px!important;
  }
  .file-title,.folder-name,.video-title,.task-title{font-family:var(--font-body)!important;font-weight:700!important;letter-spacing:.015em}
  .file-type-badge{font-family:var(--font-mono)!important;box-shadow:inset 0 0 22px rgba(53,233,255,.08)}
  .dropzone{background:linear-gradient(160deg,rgba(18,25,61,.64),rgba(7,11,30,.74))!important;border-color:rgba(138,99,255,.36)!important;border-radius:10px!important}
  .dropzone.drag,.dropzone.attention{border-color:var(--cyan)!important;box-shadow:var(--glow-cyan)!important}
  .progress-bar,.selected-file-progress span,.folder-download-progress span,.progress-shimmer{background:linear-gradient(90deg,var(--pink),var(--violet),var(--cyan))!important;box-shadow:0 0 12px rgba(53,233,255,.24)}
  .summary-value{font-family:var(--font-display)!important;font-size:22px!important;letter-spacing:.04em;color:#fff!important;text-shadow:0 0 18px rgba(138,99,255,.24)}
  .folder-icon{filter:drop-shadow(0 0 10px rgba(255,209,102,.32))}
  .task-item.priority-high{border-left-color:var(--pink)!important}.task-item.priority-medium{border-left-color:var(--gold)!important}

  /* Menus / modals / preview */
  .modal{backdrop-filter:blur(8px);background:rgba(1,3,11,.78)!important}
  .action-menu-panel,.modal-card,.drive-preview-card,.folder-download-card,.notif-panel{
    background:linear-gradient(160deg,rgba(15,21,52,.995),rgba(5,9,25,.995))!important;
    border-color:rgba(53,233,255,.26)!important;border-radius:10px!important;
    box-shadow:0 30px 90px rgba(0,0,0,.62),var(--glow-cyan)!important;
  }
  .menu-action{font-family:var(--font-body)!important;font-size:14px!important;font-weight:600!important}
  .menu-action:hover{background:linear-gradient(90deg,rgba(255,79,216,.13),rgba(53,233,255,.09))!important}
  .preview-topbar,.preview-info,.share-info{background:linear-gradient(180deg,rgba(16,22,54,.98),rgba(8,13,34,.99))!important}
  .preview-stage,.share-stage,.preview-loader{background:#04060e!important}

  /* Login / lock screen */
  .login-shell{min-height:calc(100vh - 74px)!important}
  .login-stage{gap:16px!important}
  .login-intro{
    border-radius:12px!important;clip-path:var(--clip);
    background:linear-gradient(145deg,rgba(18,25,61,.9),rgba(6,10,27,.82))!important;
    border-color:rgba(138,99,255,.28)!important;
  }
  .login-intro::after{content:'SYSTEM // READY';position:absolute;right:20px;top:18px;color:rgba(53,233,255,.5);font:10px var(--font-mono);letter-spacing:.1em}
  .login-intro h2{font-size:clamp(34px,5vw,58px)!important;line-height:1.02!important;letter-spacing:.035em}
  .login-card,.gate-panel{position:relative;overflow:hidden;border-color:rgba(53,233,255,.3)!important;box-shadow:0 36px 110px rgba(0,0,0,.5),var(--glow-cyan)!important}
  .login-card::after,.gate-panel::after{content:'';position:absolute;inset:0;pointer-events:none;background:radial-gradient(circle at 100% 0,rgba(53,233,255,.13),transparent 34%),radial-gradient(circle at 0 100%,rgba(255,79,216,.12),transparent 38%)}
  .login-card>* ,.gate-panel>*{position:relative;z-index:1}
  .login-mark,.gate-icon{background:linear-gradient(135deg,rgba(255,79,216,.2),rgba(53,233,255,.1))!important;border-color:rgba(53,233,255,.38)!important;border-radius:9px!important;box-shadow:var(--glow-cyan)!important;font-family:var(--font-display)!important;font-size:14px!important}
  .password-toggle,.show-password{color:var(--cyan)!important;font-family:var(--font-mono)!important}
  .login-feature span{font-family:var(--font-mono)!important}.login-feature strong{font-family:var(--font-display)!important;font-size:10px!important;letter-spacing:.05em;text-transform:uppercase}

  /* Public shares */
  .shared-wrap{max-width:1240px!important;padding:48px 24px 74px!important}
  .share-grid,.share-hero{border-color:rgba(53,233,255,.23)!important}
  .share-note{border-color:rgba(53,233,255,.26)!important;background:rgba(53,233,255,.055)!important}
  .share-button{border-radius:8px!important}

  /* Notifications */
  .notif-bell-wrap{top:84px!important;right:20px!important;z-index:1060!important}
  .notif-bell{border-color:rgba(255,79,216,.32)!important;background:linear-gradient(180deg,rgba(18,24,58,.97),rgba(8,12,31,.97))!important;box-shadow:var(--glow-pink)!important}

  /* Home */
  .home-shell{position:relative}
  .home-hero{position:relative;padding:34px 36px 36px!important;margin-bottom:24px;overflow:hidden}
  .home-hero::after{content:'';position:absolute;right:-10%;top:-48%;width:390px;height:390px;border-radius:50%;background:radial-gradient(circle,rgba(255,79,216,.16),transparent 64%);pointer-events:none}
  .home-hero h1{font-size:clamp(42px,6.6vw,72px)!important;line-height:.98!important;margin-bottom:17px!important;max-width:820px}
  .home-sub{max-width:700px!important;font-size:17px!important;line-height:1.6!important}
  .home-status{display:flex;gap:8px;flex-wrap:wrap;margin-top:22px}
  .home-status-chip{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border:1px solid rgba(138,99,255,.24);border-radius:6px;background:rgba(13,18,45,.72);font-size:10px!important;text-transform:uppercase;letter-spacing:.07em;color:var(--text-dim)}
  .home-status-chip i{width:6px;height:6px;border-radius:50%;background:var(--green);box-shadow:0 0 9px rgba(80,240,182,.68)}

  /* Empty states and scrollbars */
  .empty,.empty-state,.player-empty{border:1px dashed rgba(138,99,255,.32)!important;border-radius:9px!important;background:linear-gradient(160deg,rgba(18,25,61,.34),rgba(7,11,30,.28))!important}
  ::selection{background:rgba(255,79,216,.3);color:#fff}
  ::-webkit-scrollbar{width:11px;height:11px}
  ::-webkit-scrollbar-track{background:#06091a}
  ::-webkit-scrollbar-thumb{background:linear-gradient(180deg,rgba(255,79,216,.82),rgba(53,233,255,.82));border:2px solid #06091a;border-radius:999px}

  @media(max-width:900px){
    .site-topbar{inset:8px 8px auto}.site-topbar-inner{gap:8px;padding:8px}.site-brand-copy{display:none}.site-account{padding-left:5px}.site-user{display:none}
    .wrap{padding:38px 18px 76px!important}html.site-authenticated-root .wrap{padding-top:96px!important}
    .home-hero{padding:27px 23px 29px!important}.portal-grid{gap:13px!important}
  }
  @media(max-width:650px){
    .site-topbar-inner{display:grid;grid-template-columns:auto 1fr auto;row-gap:6px}
    .site-nav{grid-column:1/-1;width:100%;margin:0;justify-content:space-between;border-top:1px solid rgba(138,99,255,.16);padding-top:6px}
    .site-nav-link{flex:1;text-align:center;padding:7px 5px;font-size:9px!important}
    .site-brand-mark{width:34px;height:34px}.site-logout-button{padding:7px 8px}
    html.site-authenticated-root .wrap{padding-top:132px!important}
    .notif-bell-wrap{top:119px!important;right:12px!important}
    .wrap{padding-left:13px!important;padding-right:13px!important}.shared-wrap{padding:28px 13px 58px!important}
    h1{font-size:34px!important}.home-hero h1{font-size:40px!important}.retro-sun{width:92vw;bottom:8vh;opacity:.15}
    .portal-card{padding:23px 18px!important}.home-hero{padding:24px 19px 26px!important}.home-sub{font-size:15px!important}
  }

  @media(prefers-reduced-motion:reduce){.retro-chevron,.retro-grid,.retro-sun{animation:none!important}}
</style>
<?php if ((bool) session()->get('site_authenticated')): ?><script>document.documentElement.classList.add('site-authenticated-root');</script><?php endif; ?>
