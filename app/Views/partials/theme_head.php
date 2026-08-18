<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;600;700;800&family=Rajdhani:wght@400;500;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:        #050816;
    --surface:   #0a1028;
    --surface-2: #11193b;
    --hairline:  #2b3270;
    --text:      #F4F7FF;
    --text-dim:  #93A0D9;
    --cyan:      #42E9FF;
    --violet:    #8B63FF;
    --pink:      #FF4FD8;
    --gold:      #FFD166;
    --red:       #FF697F;
    --glow-cyan: 0 0 16px rgba(66,233,255,.35), 0 0 36px rgba(66,233,255,.15);
    --glow-pink: 0 0 16px rgba(255,79,216,.32), 0 0 36px rgba(255,79,216,.14);
    --glow-violet: 0 0 18px rgba(139,99,255,.34), 0 0 40px rgba(139,99,255,.16);
    --radius:    12px;
  }
  * { box-sizing: border-box; }

  @keyframes fadeInUp{ from{ opacity:0; transform:translateY(14px); } to{ opacity:1; transform:translateY(0); } }
  @keyframes flashIn{ from{ opacity:0; transform:translateY(-8px); } to{ opacity:1; transform:translateY(0); } }
  @keyframes starSweep{ 0%{ transform:translateX(-120%); } 60%,100%{ transform:translateX(340%); } }
  @keyframes cornerDraw{ from{ opacity:0; transform:scale(.5); } to{ opacity:.9; transform:scale(1); } }
  @keyframes cornerPulse{ 0%,100%{ filter:drop-shadow(0 0 0 rgba(95,217,232,0)); } 50%{ filter:drop-shadow(0 0 5px rgba(95,217,232,.85)); } }
  @keyframes thumbPing{ 0%{ box-shadow:0 0 0 0 rgba(95,217,232,.55); } 100%{ box-shadow:0 0 0 8px rgba(95,217,232,0); } }
  @keyframes liveDot{ 0%,100%{ opacity:1; } 50%{ opacity:.25; } }
  @keyframes progressShimmer{ to{ background-position:-200% 0; } }
  @keyframes twinkle{ 0%,100%{ opacity:.15; transform:scale(.7); } 50%{ opacity:1; transform:scale(1); } }
  @keyframes spinSlow{ to{ transform:rotate(360deg); } }
  @keyframes nebulaDrift{
    0%{   transform:translate3d(0,0,0) scale(1);        filter:hue-rotate(0deg); }
    50%{  transform:translate3d(-2.5%, 2%, 0) scale(1.08); filter:hue-rotate(8deg); }
    100%{ transform:translate3d(2.5%, -1.5%, 0) scale(1); filter:hue-rotate(-6deg); }
  }
  @keyframes starDrift{
    from{ background-position: 0 0; }
    to{   background-position: -480px -440px; }
  }
  @keyframes horizonDrift{
    from{ transform:perspective(720px) rotateX(76deg) translateY(0) scale(1.02); opacity:.92; }
    to{   transform:perspective(720px) rotateX(76deg) translateY(16px) scale(1.06); opacity:1; }
  }
  @keyframes glowFloat{
    0%,100%{ filter:drop-shadow(0 0 0 rgba(66,233,255,0)); }
    50%{ filter:drop-shadow(0 0 10px rgba(66,233,255,.32)); }
  }
  @keyframes scanlineMove{
    from{ background-position:0 0; }
    to{ background-position:0 10px; }
  }

  @media (prefers-reduced-motion: reduce){
    *, *::before, *::after{
      animation-duration:.001ms !important; animation-iteration-count:1 !important;
      transition-duration:.001ms !important; scroll-behavior:auto !important;
    }
  }
  body{
    margin:0;
    background:
      radial-gradient(circle at 20% 0%, rgba(66,233,255,.11), transparent 28%),
      radial-gradient(circle at 82% 10%, rgba(255,79,216,.14), transparent 30%),
      radial-gradient(circle at 50% 120%, rgba(139,99,255,.18), transparent 38%),
      linear-gradient(180deg, #040613 0%, #070b1f 42%, #090f26 100%);
    color:var(--text);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    min-height:100vh;
    position:relative;
    overflow-x:hidden;
  }
  /* -- synthwave-style neon glow field -- */
  body::before{
    content:''; position:fixed; inset:-10%; z-index:-3; pointer-events:none;
    background-repeat:no-repeat;
    background-image:
      radial-gradient(ellipse 1100px 640px at 12% -16%, rgba(139,99,255,.24), transparent 56%),
      radial-gradient(ellipse 860px 540px at 96% 8%, rgba(66,233,255,.16), transparent 54%),
      radial-gradient(ellipse 760px 460px at 78% 88%, rgba(255,79,216,.12), transparent 58%),
      radial-gradient(ellipse 820px 380px at 50% 100%, rgba(66,233,255,.08), transparent 58%);
    animation:nebulaDrift 42s ease-in-out infinite alternate;
    will-change:transform;
  }
  /* -- subtle diagonal bloom band for extra depth -- */
  .milky-way{
    position:fixed; z-index:-2; pointer-events:none;
    top:50%; left:50%; width:200vmax; height:42vmax;
    transform:translate(-50%,-50%) rotate(-24deg);
    background:linear-gradient(
      to bottom,
      transparent,
      rgba(66,233,255,.035) 38%,
      rgba(255,79,216,.08) 50%,
      rgba(139,99,255,.05) 58%,
      transparent
    );
    animation:milkyWayDrift 90s ease-in-out infinite alternate;
    will-change:transform;
  }
  @keyframes milkyWayDrift{
    0%{   transform:translate(-50%,-50%) rotate(-24deg) scale(1); }
    100%{ transform:translate(-50%,-50%) rotate(-21deg) scale(1.05); }
  }
  /* -- distant galaxies: small, soft, mostly static smudges -- */
  .galaxy{ position:fixed; z-index:-2; pointer-events:none; border-radius:50%; filter:blur(1px); }
  .galaxy::after{ content:''; position:absolute; inset:0; border-radius:inherit; filter:blur(3px); }
  .galaxy-1{
    top:14%; left:82%; width:46px; height:18px;
    background:radial-gradient(ellipse, rgba(220,200,255,.20), transparent 70%);
    transform:rotate(-20deg); animation:galaxySpin 240s linear infinite;
  }
  .galaxy-2{
    top:68%; left:8%; width:36px; height:14px;
    background:radial-gradient(ellipse, rgba(180,225,235,.16), transparent 70%);
    transform:rotate(35deg); animation:galaxySpin 300s linear infinite reverse;
  }
  .galaxy-3{
    top:82%; left:70%; width:30px; height:12px;
    background:radial-gradient(ellipse, rgba(240,200,210,.14), transparent 70%);
    transform:rotate(-52deg); animation:galaxySpin 260s linear infinite;
  }
  @keyframes galaxySpin{ to{ transform:rotate(360deg); } }

  /* -- animated starfield texture, slowly pans for a drifting-through-space feel -- */
  body::after{
    content:''; position:fixed; inset:0; z-index:-1; pointer-events:none;
    background-repeat:repeat;
    background-image:
      radial-gradient(1px 1px at 10% 18%, rgba(244,247,255,.9) 1px, transparent 0),
      radial-gradient(1px 1px at 34% 62%, rgba(244,247,255,.55) 1px, transparent 0),
      radial-gradient(1.5px 1.5px at 58% 24%, rgba(244,247,255,.82) 1px, transparent 0),
      radial-gradient(1px 1px at 78% 78%, rgba(244,247,255,.5) 1px, transparent 0),
      radial-gradient(1px 1px at 92% 40%, rgba(244,247,255,.7) 1px, transparent 0),
      radial-gradient(1.5px 1.5px at 15% 90%, rgba(244,247,255,.6) 1px, transparent 0),
      radial-gradient(1px 1px at 46% 8%, rgba(66,233,255,.7) 1px, transparent 0),
      radial-gradient(1px 1px at 68% 14%, rgba(255,79,216,.55) 1px, transparent 0);
    background-size:240px 220px;
    animation:starDrift 160s linear infinite;
    will-change:background-position;
  }
  .retro-grid, .scanline-layer{
    position:fixed; inset:auto 0 0; pointer-events:none;
  }
  .retro-grid{
    z-index:-2; left:-10vw; width:120vw; height:40vh; overflow:hidden;
    transform-origin:center bottom;
    background:
      linear-gradient(to top, rgba(66,233,255,.28), rgba(66,233,255,0) 60%),
      repeating-linear-gradient(90deg, rgba(66,233,255,.22) 0 2px, transparent 2px 72px),
      repeating-linear-gradient(0deg, rgba(255,79,216,.18) 0 2px, transparent 2px 58px);
    box-shadow:0 -30px 80px rgba(66,233,255,.08);
    transform:perspective(720px) rotateX(76deg);
    animation:horizonDrift 14s ease-in-out infinite alternate;
    opacity:.9;
    mask-image:linear-gradient(to top, rgba(0,0,0,1), rgba(0,0,0,.8) 55%, transparent 100%);
  }
  .scanline-layer{
    z-index:5; inset:0; opacity:.12; background:repeating-linear-gradient(to bottom, rgba(255,255,255,.08) 0 1px, transparent 1px 5px);
    mix-blend-mode:soft-light; animation:scanlineMove .18s linear infinite;
  }
  .wrap{ max-width:1080px; margin:0 auto; padding:36px 24px 80px; position:relative; z-index:1; }
  .site-session-bar{position:fixed;top:12px;right:14px;z-index:950;display:flex;align-items:center;gap:8px;padding:6px 7px 6px 11px;border:1px solid rgba(66,233,255,.32);border-radius:10px;background:linear-gradient(180deg,rgba(8,14,34,.94),rgba(13,20,46,.92));backdrop-filter:blur(10px);box-shadow:var(--glow-cyan), 0 18px 40px rgba(0,0,0,.35)}
  .site-session-user{max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font:9px 'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.08em;color:var(--text-dim)}
  .site-session-bar form{margin:0}.site-logout-button{padding:7px 9px;border:1px solid rgba(255,79,216,.26);background:linear-gradient(180deg,rgba(17,25,59,.96),rgba(11,18,42,.95));color:var(--text-dim);font:600 10px 'JetBrains Mono',monospace;box-shadow:var(--glow-pink)}.site-logout-button:hover{color:#fff;border-color:var(--pink);background:linear-gradient(180deg,rgba(255,79,216,.16),rgba(17,25,59,.95))}
  @media(max-width:620px){.site-session-bar{top:8px;right:8px}.site-session-user{display:none}.wrap{padding-top:54px}}

  /* -- twinkling star overlay (generated in JS): varied real star colors + sizes -- */
  .twinkle-layer{ position:fixed; inset:0; pointer-events:none; z-index:0; overflow:hidden; }
  .twinkle-star{
    position:absolute; border-radius:50%;
    animation-name:twinkle; animation-timing-function:ease-in-out; animation-iteration-count:infinite;
  }
  .twinkle-star.bright{ box-shadow:0 0 4px 1px currentColor; color:inherit; }

  /* -- shooting stars, generated in JS at random intervals -- */
  .shooting-star{
    position:fixed; z-index:0; pointer-events:none; height:2px; border-radius:2px;
    background:linear-gradient(90deg, rgba(255,255,255,.95), rgba(255,255,255,0));
    animation:shootingStar 1.1s linear forwards;
    will-change:transform, opacity;
  }
  @keyframes shootingStar{
    0%{   transform:translate(0,0) rotate(var(--angle)); opacity:0; }
    8%{   opacity:1; }
    100%{ transform:translate(var(--dx), var(--dy)) rotate(var(--angle)); opacity:0; }
  }

  /* -- back-to link -- */
  .nav-back{
    display:inline-flex; align-items:center; gap:6px;
    font-family:'JetBrains Mono', Menlo, monospace; font-size:12px; letter-spacing:.08em; text-transform:uppercase;
    color:var(--text-dim); text-decoration:none; margin-bottom:18px;
    opacity:0; animation:fadeInUp .5s ease forwards;
    transition: color .15s ease, transform .15s ease;
  }
  .nav-back:hover{ color:var(--cyan); transform:translateX(-3px); text-shadow:0 0 12px rgba(66,233,255,.45); }

  /* -- header / star rule -- */
  header{ margin-bottom:28px; }
  .eyebrow{
    font-family: 'JetBrains Mono', 'SFMono-Regular', Menlo, monospace;
    font-size:12px; letter-spacing:.14em; text-transform:uppercase;
    color:var(--gold); margin:0 0 8px;
    opacity:0; animation:fadeInUp .55s ease .05s forwards;
  }
  h1{
    margin:0 0 16px; font-size:40px; font-weight:700; letter-spacing:.02em;
    font-family:'Cormorant Garamond', Georgia, serif; font-style:italic;
    color:var(--text); text-shadow:0 0 10px rgba(255,255,255,.16), 0 0 24px rgba(139,99,255,.35), 0 0 42px rgba(255,79,216,.18);
    opacity:0; animation:fadeInUp .6s ease .12s forwards;
  }
  .starline{
    position:relative; overflow:hidden;
    height:14px; opacity:0;
    border-top:1px solid var(--hairline);
    border-bottom:1px solid var(--hairline);
    background-repeat: repeat-x; background-position: left center;
    background-image:
      radial-gradient(1px 1px at 6% 50%, rgba(233,237,251,.55) 1px, transparent 0),
      radial-gradient(1px 1px at 18% 50%, rgba(233,237,251,.35) 1px, transparent 0),
      radial-gradient(1.5px 1.5px at 33% 50%, var(--gold) 1px, transparent 0),
      radial-gradient(1px 1px at 47% 50%, rgba(233,237,251,.4) 1px, transparent 0),
      radial-gradient(1px 1px at 61% 50%, rgba(233,237,251,.3) 1px, transparent 0),
      radial-gradient(1.5px 1.5px at 76% 50%, var(--cyan) 1px, transparent 0),
      radial-gradient(1px 1px at 89% 50%, rgba(233,237,251,.45) 1px, transparent 0);
    background-size: 240px 14px;
    animation:fadeInUp .6s ease .2s forwards;
  }
  .starline::after{
    content:''; position:absolute; inset:0; width:34%;
    background:linear-gradient(90deg, transparent, rgba(255,255,255,.4), transparent);
    animation:starSweep 6s ease-in-out .9s infinite;
  }

  /* -- flash messages -- */
  .flash{ margin:20px 0; padding:12px 16px; border-radius:var(--radius); font-size:14px; animation:flashIn .35s ease both; }
  .flash.success{ background:linear-gradient(180deg,rgba(66,233,255,.12),rgba(66,233,255,.06)); border:1px solid rgba(66,233,255,.55); color:#D8FAFF; box-shadow:var(--glow-cyan); }
  .flash.error{ background:linear-gradient(180deg,rgba(255,105,127,.16),rgba(255,105,127,.06)); border:1px solid rgba(255,105,127,.55); color:#FFD5DB; box-shadow:0 0 22px rgba(255,105,127,.16); }
  .flash ul{ margin:4px 0 0; padding-left:18px; }

  /* -- panel -- */
  .panel{
    background:linear-gradient(180deg, rgba(12,18,44,.96), rgba(10,16,40,.94));
    border:1px solid rgba(66,233,255,.18);
    border-radius:var(--radius);
    padding:20px;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.04), 0 10px 28px rgba(0,0,0,.22);
    opacity:0; animation:fadeInUp .55s ease forwards;
    transition: border-color .25s ease, box-shadow .25s ease, transform .25s ease;
  }
  .panel:hover{ border-color:rgba(66,233,255,.4); box-shadow:var(--glow-cyan), 0 18px 40px rgba(0,0,0,.34), inset 0 1px 0 rgba(255,255,255,.04); transform:translateY(-2px); }
  .panel h2{
    margin:0 0 14px; font-size:12px; text-transform:uppercase; letter-spacing:.14em;
    color:var(--text-dim); font-weight:600; font-family:'JetBrains Mono', Menlo, monospace;
  }

  /* -- viewfinder corners, the site's recurring "scanned/observed" motif -- */
  .scope-frame{ position:relative; }
  .corner{
    position:absolute; width:16px; height:16px; z-index:2; pointer-events:none; opacity:0;
    animation:cornerDraw .5s ease forwards;
  }
  .corner.tl{ top:-6px; left:-6px; border-top:2px solid var(--cyan); border-left:2px solid var(--cyan); box-shadow:var(--glow-cyan); animation-delay:.45s; }
  .corner.tr{ top:-6px; right:-6px; border-top:2px solid var(--pink); border-right:2px solid var(--pink); box-shadow:var(--glow-pink); animation-delay:.52s; }
  .corner.bl{ bottom:-6px; left:-6px; border-bottom:2px solid var(--cyan); border-left:2px solid var(--cyan); box-shadow:var(--glow-cyan); animation-delay:.59s; }
  .corner.br{ bottom:-6px; right:-6px; border-bottom:2px solid var(--pink); border-right:2px solid var(--pink); box-shadow:var(--glow-pink); animation-delay:.66s; }
  .scope-frame.is-playing .corner{ animation:cornerDraw .5s ease forwards, cornerPulse 2.4s ease-in-out .5s infinite; }

  /* -- buttons -- */
  button{
    font-family:inherit; cursor:pointer; border:none; border-radius:6px;
    font-size:14px; font-weight:600; padding:11px 18px;
    transition: transform .12s ease;
  }
  button:active{ transform:scale(.97); }
  .btn-primary{
    background:linear-gradient(90deg,var(--pink),var(--violet) 55%,var(--cyan)); color:#fff; width:100%; margin-top:18px; letter-spacing:.04em; text-transform:uppercase;
    position:relative; overflow:hidden; transition: background .15s ease, transform .12s ease, box-shadow .18s ease;
    display:inline-block; text-decoration:none; text-align:center; box-shadow:var(--glow-pink);
  }
  .btn-primary:hover{ filter:brightness(1.08); box-shadow:var(--glow-cyan), var(--glow-pink); }
  .btn-primary::after{
    content:''; position:absolute; top:0; left:-60%; width:35%; height:100%;
    background:linear-gradient(115deg, transparent, rgba(255,255,255,.55), transparent);
    transform:skewX(-18deg); transition:left .55s ease;
  }
  .btn-primary:hover::after{ left:140%; }
  .btn-primary:disabled{ opacity:.65; cursor:default; }
  .btn-primary:disabled::after{ display:none; }

  /* -- status dot + badge, used for LIVE / COMING SOON tags -- */
  .dot{ width:6px; height:6px; border-radius:50%; display:inline-block; }
  .badge{
    display:inline-flex; align-items:center; gap:6px;
    font-family:'JetBrains Mono', Menlo, monospace; font-size:10px; letter-spacing:.1em; text-transform:uppercase;
    padding:4px 9px; border-radius:20px; border:1px solid var(--hairline); color:var(--text-dim);
  }
  .badge.live{ border-color:rgba(95,217,232,.4); color:#CFF3F8; background:rgba(95,217,232,.08); }
  .badge.live .dot{ background:var(--cyan); box-shadow:0 0 6px rgba(95,217,232,.8); animation:liveDot 1.4s ease-in-out infinite; }
  .badge.soon{ border-color:rgba(242,195,107,.35); color:#F6DDA8; background:rgba(242,195,107,.08); }
  .badge.soon .dot{ background:var(--gold); }
  .badge.overdue{ border-color:rgba(229,99,107,.4); color:#F7CDD0; background:rgba(229,99,107,.10); }
  .badge.overdue .dot{ background:var(--red); animation:liveDot 1.2s ease-in-out infinite; }

  /* -- form fields, shared by any page with a form -- */
  label{ display:block; font-size:12px; color:var(--text-dim); margin:14px 0 6px; letter-spacing:.03em; }
  label:first-of-type{ margin-top:0; }
  input[type="text"], input[type="password"], input[type="number"], input[type="date"], input[type="search"], input[type="url"], input[type="time"], textarea{
    width:100%; background:linear-gradient(180deg, rgba(17,25,59,.92), rgba(11,18,42,.94)); border:1px solid rgba(139,99,255,.24);
    border-radius:8px; padding:10px 12px; color:var(--text); font-size:14px; font-family:inherit;
    resize:vertical; box-shadow:inset 0 0 0 1px rgba(255,255,255,.02);
  }
  input[type="text"]:focus, input[type="password"]:focus, input[type="number"]:focus, input[type="date"]:focus, input[type="search"]:focus, input[type="url"]:focus, input[type="time"]:focus, textarea:focus, input[type="file"]:focus{
    outline:2px solid rgba(66,233,255,.65); outline-offset:1px; box-shadow:var(--glow-cyan);
  }
  /* -- visible keyboard focus ring, site-wide -- */
  a:focus-visible, button:focus-visible, select:focus-visible{
    outline:2px solid var(--cyan); outline-offset:2px; border-radius:4px;
  }
  input[type="date"]::-webkit-calendar-picker-indicator, input[type="time"]::-webkit-calendar-picker-indicator{ filter:invert(1) brightness(1.4); cursor:pointer; }

  /* -- visually hidden but still accessible to keyboard/screen readers -- */
  .sr-only{
    position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden;
    clip:rect(0,0,0,0); white-space:nowrap; border:0;
  }

  /* -- small icon-only buttons, used in any list item -- */
  .icon-del{
    flex:none; background:transparent; color:var(--text-dim); font-size:12px; padding:6px 8px;
    transition: color .15s ease, transform .15s ease;
  }
  .icon-del:hover{ color:var(--red); transform:scale(1.15); }
  .icon-edit{
    flex:none; background:transparent; color:var(--text-dim); font-size:12px; padding:6px 8px;
    transition: color .15s ease, transform .15s ease;
  }
  .icon-edit:hover{ color:var(--cyan); transform:scale(1.15); }

  /* -- utility -- */
  .hidden{ display:none !important; }

  /* -- site-wide notification bell + expandable panel -- */
  .notif-bell-wrap{ position:fixed; top:18px; right:20px; z-index:50; }
  @media (max-width:600px){ .notif-bell-wrap{ top:12px; right:14px; } }
  .notif-bell{
    width:38px; height:38px; border-radius:50%; position:relative;
    background:linear-gradient(180deg,rgba(11,17,41,.96),rgba(16,23,51,.95)); border:1px solid rgba(255,79,216,.25);
    display:flex; align-items:center; justify-content:center; padding:0; box-shadow:var(--glow-pink);
    opacity:0; animation:fadeInUp .5s ease .15s forwards;
    transition: border-color .15s ease, transform .15s ease, background .15s ease;
  }
  .notif-bell:hover{ border-color:rgba(66,233,255,.6); background:rgba(66,233,255,.08); transform:scale(1.06); box-shadow:var(--glow-cyan); }
  .notif-bell-icon{ font-size:16px; line-height:1; filter:grayscale(1) brightness(1.3); }
  .notif-bell-badge{
    position:absolute; top:-4px; right:-4px; min-width:17px; height:17px; padding:0 4px;
    border-radius:20px; background:var(--red); color:#fff;
    font-family:'JetBrains Mono', Menlo, monospace; font-size:10px; font-weight:700;
    display:flex; align-items:center; justify-content:center;
    box-shadow:0 0 0 2px var(--bg);
    animation:liveDot 1.6s ease-in-out infinite;
  }
  .notif-panel{
    position:absolute; top:48px; right:0; width:min(320px, 82vw);
    background:linear-gradient(180deg, rgba(10,16,40,.98), rgba(15,23,54,.98)); border:1px solid rgba(66,233,255,.24); border-radius:12px;
    box-shadow:var(--glow-cyan), 0 22px 48px rgba(0,0,0,.45);
    padding:14px; opacity:1;
    animation:fadeInUp .2s ease both;
  }
  .notif-panel.hidden{ display:none; }
  .notif-panel-head{
    font-family:'JetBrains Mono', Menlo, monospace; font-size:11px; text-transform:uppercase; letter-spacing:.06em;
    color:#F7CDD0; margin:0 0 10px; padding-bottom:10px; border-bottom:1px solid var(--hairline);
  }
  .notif-list{ list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:9px; max-height:220px; overflow-y:auto; }
  .notif-item{ display:flex; align-items:center; gap:8px; }
  .notif-dot{ width:6px; height:6px; border-radius:50%; background:var(--gold); flex:none; }
  .notif-item.overdue .notif-dot{ background:var(--red); }
  .notif-item-title{
    flex:1; min-width:0; font-size:13px; color:var(--text);
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
  }
  .notif-item-due{ font-family:'JetBrains Mono', Menlo, monospace; font-size:10px; color:var(--text-dim); flex:none; }
  .notif-item.overdue .notif-item-due{ color:#F7CDD0; }
  .notif-view-all{
    display:block; text-align:center; margin-top:12px; padding-top:10px; border-top:1px solid var(--hairline);
    font-family:'JetBrains Mono', Menlo, monospace; font-size:11px; color:var(--cyan); text-decoration:none;
  }
  .notif-view-all:hover{ text-decoration:underline; }

  /* -- portal cards, used on the home hub and any sub-hub -- */
  .portal-grid{ display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; margin-top:28px; }
  .portal-grid.cols-2{ grid-template-columns:repeat(2, 1fr); }
  @media (max-width:860px){ .portal-grid, .portal-grid.cols-2{ grid-template-columns:1fr; } }
  .portal-card{
    position:relative; display:block; text-decoration:none; color:inherit;
    background:linear-gradient(180deg, rgba(10,16,40,.94), rgba(15,23,54,.96)); border:1px solid rgba(139,99,255,.24); border-radius:var(--radius);
    padding:28px 22px; overflow:visible; box-shadow:0 10px 28px rgba(0,0,0,.2);
    opacity:0; animation:fadeInUp .55s ease forwards;
    transition: border-color .25s ease, box-shadow .25s ease, transform .25s ease;
  }
  .portal-grid .portal-card:nth-child(1){ animation-delay:.25s; }
  .portal-grid .portal-card:nth-child(2){ animation-delay:.35s; }
  .portal-grid .portal-card:nth-child(3){ animation-delay:.45s; }
  .portal-grid .portal-card:nth-child(4){ animation-delay:.55s; }
  .portal-card:hover{ border-color:rgba(66,233,255,.48); box-shadow:var(--glow-cyan), 0 18px 40px rgba(0,0,0,.34); transform:translateY(-4px); }
  .portal-card:hover .portal-icon{ transform:scale(1.08) rotate(-4deg); color:var(--cyan); border-color:rgba(66,233,255,.54); box-shadow:var(--glow-cyan); }
  .portal-icon{
    width:46px; height:46px; border-radius:14px; display:flex; align-items:center; justify-content:center;
    background:linear-gradient(180deg, rgba(17,25,59,.94), rgba(9,14,34,.96)); border:1px solid rgba(255,79,216,.22); color:var(--text-dim);
    font-size:21px; margin-bottom:16px; transition: transform .2s ease, color .2s ease, border-color .2s ease, box-shadow .2s ease;
  }
  .portal-title{ font-family:'Cormorant Garamond', Georgia, serif; font-style:italic; font-size:24px; font-weight:700; margin:0 0 6px; }
  .portal-desc{ font-size:13px; color:var(--text-dim); line-height:1.55; margin:0 0 18px; }
  .portal-foot{ display:flex; align-items:center; justify-content:space-between; }
  .portal-arrow{ font-family:'JetBrains Mono', Menlo, monospace; font-size:13px; color:var(--text-dim); transition: transform .2s ease, color .2s ease; }
  .portal-card:hover .portal-arrow{ transform:translateX(4px); color:var(--cyan); text-shadow:0 0 12px rgba(66,233,255,.42); }

  select{
    background:linear-gradient(180deg, rgba(17,25,59,.92), rgba(11,18,42,.94));
    color:var(--text); border:1px solid rgba(139,99,255,.24); border-radius:8px;
  }
  ::selection{ background:rgba(255,79,216,.28); color:#fff; }
  ::-webkit-scrollbar{ width:12px; height:12px; }
  ::-webkit-scrollbar-track{ background:rgba(9,12,28,.8); }
  ::-webkit-scrollbar-thumb{ background:linear-gradient(180deg, rgba(255,79,216,.8), rgba(66,233,255,.8)); border-radius:999px; border:2px solid rgba(9,12,28,.9); }
  ::-webkit-scrollbar-thumb:hover{ filter:brightness(1.08); }
  .mono-glow{ color:var(--cyan); text-shadow:0 0 10px rgba(66,233,255,.36); }

</style>
