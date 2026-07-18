<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,500;1,600&family=JetBrains+Mono:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:        #05070f;
    --surface:   #0d1224;
    --surface-2: #131a30;
    --hairline:  #232d4d;
    --text:      #E9EDFB;
    --text-dim:  #8390B5;
    --cyan:      #5FD9E8;
    --violet:    #9B7DEE;
    --gold:      #F2C36B;
    --red:       #E5636B;
    --radius:    10px;
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

  @media (prefers-reduced-motion: reduce){
    *, *::before, *::after{
      animation-duration:.001ms !important; animation-iteration-count:1 !important;
      transition-duration:.001ms !important; scroll-behavior:auto !important;
    }
  }
  body{
    margin:0;
    background:var(--bg);
    color:var(--text);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    min-height:100vh;
    position:relative;
    overflow-x:hidden;
  }
  /* -- animated nebula glow, drifts + breathes slowly, stays fixed while page scrolls -- */
  body::before{
    content:''; position:fixed; inset:-15%; z-index:-2; pointer-events:none;
    background-repeat:no-repeat;
    background-image:
      radial-gradient(ellipse 900px 520px at 10% -12%, rgba(155,125,238,.18), transparent 60%),
      radial-gradient(ellipse 760px 520px at 96% 6%, rgba(95,217,232,.11), transparent 55%);
    animation:nebulaDrift 46s ease-in-out infinite alternate;
    will-change:transform;
  }
  /* -- animated starfield texture, slowly pans for a drifting-through-space feel -- */
  body::after{
    content:''; position:fixed; inset:0; z-index:-1; pointer-events:none;
    background-repeat:repeat;
    background-image:
      radial-gradient(1px 1px at 10% 18%, rgba(233,237,251,.9) 1px, transparent 0),
      radial-gradient(1px 1px at 34% 62%, rgba(233,237,251,.55) 1px, transparent 0),
      radial-gradient(1.5px 1.5px at 58% 24%, rgba(233,237,251,.8) 1px, transparent 0),
      radial-gradient(1px 1px at 78% 78%, rgba(233,237,251,.5) 1px, transparent 0),
      radial-gradient(1px 1px at 92% 40%, rgba(233,237,251,.7) 1px, transparent 0),
      radial-gradient(1.5px 1.5px at 15% 90%, rgba(233,237,251,.6) 1px, transparent 0),
      radial-gradient(1px 1px at 46% 8%, rgba(233,237,251,.65) 1px, transparent 0);
    background-size:240px 220px;
    animation:starDrift 160s linear infinite;
    will-change:background-position;
  }
  .wrap{ max-width:1080px; margin:0 auto; padding:36px 24px 80px; position:relative; z-index:1; }

  /* -- twinkling star overlay (generated in JS) -- */
  .twinkle-layer{ position:fixed; inset:0; pointer-events:none; z-index:0; overflow:hidden; }
  .twinkle-star{
    position:absolute; border-radius:50%; background:#fff;
    animation-name:twinkle; animation-timing-function:ease-in-out; animation-iteration-count:infinite;
  }

  /* -- back-to link -- */
  .nav-back{
    display:inline-flex; align-items:center; gap:6px;
    font-family:'JetBrains Mono', Menlo, monospace; font-size:12px; letter-spacing:.08em; text-transform:uppercase;
    color:var(--text-dim); text-decoration:none; margin-bottom:18px;
    opacity:0; animation:fadeInUp .5s ease forwards;
    transition: color .15s ease, transform .15s ease;
  }
  .nav-back:hover{ color:var(--cyan); transform:translateX(-3px); }

  /* -- header / star rule -- */
  header{ margin-bottom:28px; }
  .eyebrow{
    font-family: 'JetBrains Mono', 'SFMono-Regular', Menlo, monospace;
    font-size:12px; letter-spacing:.14em; text-transform:uppercase;
    color:var(--gold); margin:0 0 8px;
    opacity:0; animation:fadeInUp .55s ease .05s forwards;
  }
  h1{
    margin:0 0 16px; font-size:40px; font-weight:600; letter-spacing:.01em;
    font-family:'Cormorant Garamond', Georgia, serif; font-style:italic;
    color:var(--text); text-shadow: 0 0 24px rgba(155,125,238,.35);
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
  .flash.success{ background:rgba(95,217,232,.10); border:1px solid var(--cyan); color:#CFF3F8; }
  .flash.error{ background:rgba(229,99,107,.12); border:1px solid var(--red); color:#F7CDD0; }
  .flash ul{ margin:4px 0 0; padding-left:18px; }

  /* -- panel -- */
  .panel{
    background:var(--surface);
    border:1px solid var(--hairline);
    border-radius:var(--radius);
    padding:20px;
    box-shadow: 0 1px 0 rgba(255,255,255,.02) inset;
    opacity:0; animation:fadeInUp .55s ease forwards;
    transition: border-color .25s ease, box-shadow .25s ease, transform .25s ease;
  }
  .panel:hover{ border-color:#2c3a68; box-shadow:0 10px 28px rgba(0,0,0,.28), 0 1px 0 rgba(255,255,255,.02) inset; transform:translateY(-2px); }
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
  .corner.tl{ top:-6px;    left:-6px;  border-top:2px solid var(--cyan); border-left:2px solid var(--cyan); animation-delay:.45s; }
  .corner.tr{ top:-6px;    right:-6px; border-top:2px solid var(--cyan); border-right:2px solid var(--cyan); animation-delay:.52s; }
  .corner.bl{ bottom:-6px; left:-6px;  border-bottom:2px solid var(--cyan); border-left:2px solid var(--cyan); animation-delay:.59s; }
  .corner.br{ bottom:-6px; right:-6px; border-bottom:2px solid var(--cyan); border-right:2px solid var(--cyan); animation-delay:.66s; }
  .scope-frame.is-playing .corner{ animation:cornerDraw .5s ease forwards, cornerPulse 2.4s ease-in-out .5s infinite; }

  /* -- buttons -- */
  button{
    font-family:inherit; cursor:pointer; border:none; border-radius:6px;
    font-size:14px; font-weight:600; padding:11px 18px;
    transition: transform .12s ease;
  }
  button:active{ transform:scale(.97); }
  .btn-primary{
    background:var(--gold); color:#1B1430; width:100%; margin-top:18px; letter-spacing:.01em;
    position:relative; overflow:hidden; transition: background .15s ease, transform .12s ease;
    display:inline-block; text-decoration:none; text-align:center;
  }
  .btn-primary:hover{ background:#f6d182; }
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

  /* -- portal cards, used on the home hub and any sub-hub -- */
  .portal-grid{ display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; margin-top:28px; }
  .portal-grid.cols-2{ grid-template-columns:repeat(2, 1fr); }
  @media (max-width:860px){ .portal-grid, .portal-grid.cols-2{ grid-template-columns:1fr; } }
  .portal-card{
    position:relative; display:block; text-decoration:none; color:inherit;
    background:var(--surface); border:1px solid var(--hairline); border-radius:var(--radius);
    padding:28px 22px; overflow:visible;
    opacity:0; animation:fadeInUp .55s ease forwards;
    transition: border-color .25s ease, box-shadow .25s ease, transform .25s ease;
  }
  .portal-grid .portal-card:nth-child(1){ animation-delay:.25s; }
  .portal-grid .portal-card:nth-child(2){ animation-delay:.35s; }
  .portal-grid .portal-card:nth-child(3){ animation-delay:.45s; }
  .portal-card:hover{ border-color:#2c3a68; box-shadow:0 14px 34px rgba(0,0,0,.32); transform:translateY(-4px); }
  .portal-card:hover .portal-icon{ transform:scale(1.08) rotate(-4deg); color:var(--cyan); border-color:rgba(95,217,232,.4); }
  .portal-icon{
    width:46px; height:46px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    background:var(--surface-2); border:1px solid var(--hairline); color:var(--text-dim);
    font-size:21px; margin-bottom:16px; transition: transform .2s ease, color .2s ease, border-color .2s ease;
  }
  .portal-title{ font-family:'Cormorant Garamond', Georgia, serif; font-style:italic; font-size:24px; font-weight:600; margin:0 0 6px; }
  .portal-desc{ font-size:13px; color:var(--text-dim); line-height:1.55; margin:0 0 18px; }
  .portal-foot{ display:flex; align-items:center; justify-content:space-between; }
  .portal-arrow{ font-family:'JetBrains Mono', Menlo, monospace; font-size:13px; color:var(--text-dim); transition: transform .2s ease, color .2s ease; }
  .portal-card:hover .portal-arrow{ transform:translateX(4px); color:var(--cyan); }
</style>
