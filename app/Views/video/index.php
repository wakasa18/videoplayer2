<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Video Observatory</title>
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

  /* -- layout -- */
  .layout{ display:grid; grid-template-columns: 1.4fr 1fr; gap:24px; margin-top:28px; }
  @media (max-width: 860px){ .layout{ grid-template-columns: 1fr; } }

  .panel{
    background:var(--surface);
    border:1px solid var(--hairline);
    border-radius:var(--radius);
    padding:20px;
    box-shadow: 0 1px 0 rgba(255,255,255,.02) inset;
    opacity:0; animation:fadeInUp .55s ease forwards;
    transition: border-color .25s ease, box-shadow .25s ease, transform .25s ease;
  }
  .layout > .panel:nth-of-type(1){ animation-delay:.28s; }
  .layout > .panel:nth-of-type(2){ animation-delay:.38s; }
  .panel:hover{ border-color:#2c3a68; box-shadow:0 10px 28px rgba(0,0,0,.28), 0 1px 0 rgba(255,255,255,.02) inset; transform:translateY(-2px); }
  .panel h2{
    margin:0 0 14px; font-size:12px; text-transform:uppercase; letter-spacing:.14em;
    color:var(--text-dim); font-weight:600; font-family:'JetBrains Mono', Menlo, monospace;
  }

  /* -- player, framed like a viewfinder -- */
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
  .player-frame{
    background:#000; border-radius:8px; overflow:hidden; aspect-ratio:16/9;
    display:flex; align-items:center; justify-content:center; border:1px solid var(--hairline);
    transition: box-shadow .3s ease, border-color .3s ease;
  }
  .player-frame.is-playing{ border-color:rgba(95,217,232,.4); box-shadow:0 0 0 1px rgba(95,217,232,.15), 0 0 26px rgba(95,217,232,.18); }
  .player-frame video{ width:100%; height:100%; display:block; background:#000; }
  .player-empty{ color:var(--text-dim); font-size:14px; text-align:center; padding:20px; }
  .now-playing{
    margin-top:14px; font-size:13px; color:var(--text-dim);
    font-family:'JetBrains Mono', Menlo, monospace; letter-spacing:.02em;
  }
  .now-playing::before{
    content:''; display:inline-block; width:6px; height:6px; margin-right:7px;
    border-radius:50%; background:var(--red); vertical-align:middle;
    animation:liveDot 1.4s ease-in-out infinite;
  }
  .now-playing strong{ color:var(--cyan); font-weight:500; }
  .now-playing strong.updated{ animation:flashIn .3s ease; }

  /* -- upload form -- */
  label{ display:block; font-size:12px; color:var(--text-dim); margin:14px 0 6px; }
  label:first-of-type{ margin-top:0; }
  input[type="text"], textarea{
    width:100%; background:var(--surface-2); border:1px solid var(--hairline);
    border-radius:6px; padding:10px 12px; color:var(--text); font-size:14px; font-family:inherit;
    resize:vertical;
  }
  input[type="text"]:focus, textarea:focus, input[type="file"]:focus{
    outline:2px solid var(--cyan); outline-offset:1px;
  }
  .dropzone{
    margin-top:6px; border:1.5px dashed var(--hairline); border-radius:8px;
    padding:22px 14px; text-align:center; background:var(--surface-2);
    transition: border-color .15s ease, background .15s ease, transform .15s ease, box-shadow .15s ease;
  }
  .dropzone.drag{ border-color:var(--cyan); background:rgba(95,217,232,.08); transform:scale(1.015); box-shadow:0 0 0 3px rgba(95,217,232,.12); }
  .dropzone p{ margin:0 0 8px; font-size:13px; color:var(--text-dim); }
  .dropzone input[type="file"]{ color:var(--text-dim); font-size:13px; width:100%; }
  .file-hint{ font-size:11px; color:var(--text-dim); margin-top:6px; }

  button{
    font-family:inherit; cursor:pointer; border:none; border-radius:6px;
    font-size:14px; font-weight:600; padding:11px 18px;
    transition: transform .12s ease;
  }
  button:active{ transform:scale(.97); }
  .btn-primary{
    background:var(--gold); color:#1B1430; width:100%; margin-top:18px; letter-spacing:.01em;
    position:relative; overflow:hidden; transition: background .15s ease, transform .12s ease;
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

  /* -- video list -- */
  .video-list{ list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:8px; }
  .video-item{
    display:flex; align-items:center; gap:12px;
    background:var(--surface-2); border:1px solid var(--hairline); border-radius:8px;
    padding:10px 12px; cursor:pointer;
    transition:border-color .15s ease, background .15s ease, transform .15s ease;
    opacity:0; animation:fadeInUp .4s ease forwards;
  }
  .video-item:nth-child(1){ animation-delay:.05s; }
  .video-item:nth-child(2){ animation-delay:.10s; }
  .video-item:nth-child(3){ animation-delay:.15s; }
  .video-item:nth-child(4){ animation-delay:.20s; }
  .video-item:nth-child(5){ animation-delay:.25s; }
  .video-item:nth-child(6){ animation-delay:.30s; }
  .video-item:nth-child(7){ animation-delay:.35s; }
  .video-item:nth-child(8){ animation-delay:.40s; }
  .video-item:hover{ border-color:var(--cyan); transform:translateX(3px); }
  .video-item.active{ border-color:var(--cyan); background:rgba(95,217,232,.08); }
  .video-thumb{
    width:56px; height:34px; border-radius:4px; background:#000; flex:none;
    display:flex; align-items:center; justify-content:center; color:var(--cyan); font-size:16px;
    border:1px solid var(--hairline);
    transition: transform .15s ease, color .15s ease;
  }
  .video-item:hover .video-thumb{ transform:scale(1.08); }
  .video-item.active .video-thumb{ animation:thumbPing 1.7s ease-out infinite; }
  .video-meta{ flex:1; min-width:0; }
  .video-title{ font-size:14px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .video-sub{
    font-family:'JetBrains Mono', Menlo, monospace; font-size:11px; color:var(--text-dim); margin-top:2px;
  }
  .video-del{
    flex:none; background:transparent; color:var(--text-dim); font-size:12px; padding:6px 8px;
    transition: color .15s ease, transform .15s ease;
  }
  .video-del:hover{ color:var(--red); transform:scale(1.15); }
  .empty-state{ color:var(--text-dim); font-size:14px; text-align:center; padding:24px 8px; animation:fadeInUp .5s ease both; }

  /* -- upload progress shimmer -- */
  .progress-shimmer{
    background-image:linear-gradient(90deg, var(--cyan), var(--violet), var(--cyan));
    background-size:200% 100%;
    animation:progressShimmer 1.2s linear infinite;
  }
</style>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<div class="wrap">

  <header>
    <p class="eyebrow">Transmission Log · Sector 07</p>
    <h1>Video Observatory</h1>
    <div class="starline"></div>
  </header>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="flash success"><?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="flash error"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('errors')): ?>
    <div class="flash error">
      <strong>Please fix the following:</strong>
      <ul>
        <?php foreach (session()->getFlashdata('errors') as $err): ?>
          <li><?= esc($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="layout">

    <!-- Player + list -->
    <div class="panel">
      <h2>Live Feed</h2>
      <div class="scope-frame">
        <span class="corner tl"></span><span class="corner tr"></span>
        <span class="corner bl"></span><span class="corner br"></span>
        <div class="player-frame" id="playerFrame">
          <video id="player" controls preload="metadata"></video>
          <div class="player-empty" id="playerEmpty">Select a recording from the log to begin playback.</div>
        </div>
      </div>
      <div class="now-playing" id="nowPlaying" style="display:none;">
        Now viewing: <strong id="nowPlayingTitle"></strong>
      </div>

      <h2 style="margin-top:26px;">Star Catalog (<?= count($videos) ?>)</h2>
      <?php if (empty($videos)): ?>
        <div class="empty-state">No recordings logged yet. Add one to begin your catalog.</div>
      <?php else: ?>
        <ul class="video-list" id="videoList">
          <?php foreach ($videos as $video): ?>
            <li class="video-item"
                data-src="<?= (str_starts_with($video['file_path'], 'http://') || str_starts_with($video['file_path'], 'https://')) ? esc($video['file_path'], 'attr') : base_url($video['file_path']) ?>"
                data-title="<?= esc($video['title']) ?>">
              <div class="video-thumb">&#9654;</div>
              <div class="video-meta">
                <div class="video-title"><?= esc($video['title']) ?></div>
                <div class="video-sub">
                  <?= \App\Models\VideoModel::formatBytes((int) $video['file_size']) ?>
                  &middot; <?= esc(strtoupper(pathinfo($video['filename'], PATHINFO_EXTENSION))) ?>
                  &middot; <?= esc(date('M j, Y', strtotime($video['created_at']))) ?>
                </div>
              </div>
              <form action="<?= base_url('videos/' . $video['id'] . '/delete') ?>" method="post"
                    onsubmit="return confirm('Delete this video? This cannot be undone.');" onclick="event.stopPropagation();">
                <?= csrf_field() ?>
                <button type="submit" class="video-del" title="Delete">&times;</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <!-- Upload form -->
    <div class="panel">
      <h2>Log New Recording</h2>
      <form id="uploadForm">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" required maxlength="255">

        <label for="description">Description (optional)</label>
        <textarea id="description" name="description" rows="3"></textarea>

        <label for="video">Video file</label>
        <div class="dropzone" id="dropzone">
          <p>MP4, WebM, OGG, MOV, AVI, or MKV</p>
          <input type="file" id="video" name="video" accept="video/*" required>
        </div>

        <div id="uploadStatus" style="display:none; margin: 10px 0; font-size: 14px; transition: opacity .2s ease;"></div>
        <div id="uploadProgressWrap" style="display:none; height:6px; background:var(--surface-2); border-radius:4px; overflow:hidden; margin: 10px 0;">
          <div id="uploadProgressBar" class="progress-shimmer" style="height:100%; width:0%; transition:width .15s;"></div>
        </div>

        <button type="submit" class="btn-primary" id="uploadSubmitBtn">Add to catalog</button>
      </form>
    </div>

  </div>
</div>

<script>
  // --- twinkling starfield overlay ---
  (function initTwinkle() {
    const layer = document.getElementById('twinkleLayer');
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

  const player      = document.getElementById('player');
  const playerEmpty = document.getElementById('playerEmpty');
  const scopeFrame  = document.querySelector('.scope-frame');
  const playerFrame = document.getElementById('playerFrame');
  const nowPlaying      = document.getElementById('nowPlaying');
  const nowPlayingTitle = document.getElementById('nowPlayingTitle');
  const items = document.querySelectorAll('.video-item');

  items.forEach(item => {
    item.addEventListener('click', () => {
      items.forEach(i => i.classList.remove('active'));
      item.classList.add('active');

      player.src = item.dataset.src;
      player.style.display = 'block';
      playerEmpty.style.display = 'none';
      nowPlaying.style.display = 'block';
      nowPlayingTitle.textContent = item.dataset.title;
      nowPlayingTitle.classList.remove('updated');
      // restart the flash animation even if the same title is clicked again
      void nowPlayingTitle.offsetWidth;
      nowPlayingTitle.classList.add('updated');
      player.play().catch(() => { /* autoplay may be blocked, that's fine */ });
    });
  });

  // pulse the viewfinder corners and glow the frame while a video is actually playing
  player.addEventListener('play',  () => { scopeFrame.classList.add('is-playing');    playerFrame.classList.add('is-playing'); });
  player.addEventListener('pause', () => { scopeFrame.classList.remove('is-playing'); playerFrame.classList.remove('is-playing'); });
  player.addEventListener('ended', () => { scopeFrame.classList.remove('is-playing'); playerFrame.classList.remove('is-playing'); });

  // hide the <video> element until something is selected
  player.style.display = 'none';

  // simple drag styling for the dropzone
  const dropzone  = document.getElementById('dropzone');
  const fileInput = document.getElementById('video');
  ['dragenter', 'dragover'].forEach(evt =>
    dropzone.addEventListener(evt, e => { e.preventDefault(); dropzone.classList.add('drag'); })
  );
  ['dragleave', 'drop'].forEach(evt =>
    dropzone.addEventListener(evt, e => { e.preventDefault(); dropzone.classList.remove('drag'); })
  );
  dropzone.addEventListener('drop', e => {
    if (e.dataTransfer.files.length) {
      fileInput.files = e.dataTransfer.files;
    }
  });

  // --- Direct-to-storage upload flow ---
  // The file never touches our own backend/server — we ask it for a
  // signed upload URL, PUT the file straight to storage from the browser,
  // then send back just a small JSON metadata record to save. This avoids
  // any server request-body size limits entirely.
  const uploadForm     = document.getElementById('uploadForm');
  const uploadStatus   = document.getElementById('uploadStatus');
  const progressWrap   = document.getElementById('uploadProgressWrap');
  const progressBar    = document.getElementById('uploadProgressBar');
  const submitBtn      = document.getElementById('uploadSubmitBtn');

  function setStatus(message, isError) {
    uploadStatus.style.display = 'block';
    uploadStatus.style.opacity = '0';
    uploadStatus.textContent = message;
    uploadStatus.style.color = isError ? 'var(--red)' : 'var(--text-dim)';
    requestAnimationFrame(() => { uploadStatus.style.opacity = '1'; });
  }

  function putWithProgress(url, file) {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open('PUT', url);
      xhr.setRequestHeader('Content-Type', file.type || 'application/octet-stream');
      xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
          const pct = Math.round((e.loaded / e.total) * 100);
          progressBar.style.width = pct + '%';
          setStatus('Uploading… ' + pct + '%', false);
        }
      });
      xhr.onload = () => {
        if (xhr.status >= 200 && xhr.status < 300) {
          resolve();
        } else {
          reject(new Error('Upload to storage failed (status ' + xhr.status + ').'));
        }
      };
      xhr.onerror = () => reject(new Error('Upload to storage failed. Check your connection.'));
      xhr.send(file);
    });
  }

  uploadForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const title = document.getElementById('title').value.trim();
    const description = document.getElementById('description').value.trim();
    const file = fileInput.files[0];

    if (!title || !file) {
      setStatus('Please provide a title and choose a file.', true);
      return;
    }

    submitBtn.disabled = true;
    progressWrap.style.display = 'block';
    progressBar.style.width = '0%';
    setStatus('Preparing upload…', false);

    try {
      // Step 1: ask our backend for a signed upload URL
      const signRes = await fetch('<?= base_url('videos/sign-upload') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ filename: file.name, mimetype: file.type }),
      });
      const signData = await signRes.json();
      if (!signRes.ok) {
        throw new Error(signData.error || 'Could not prepare the upload.');
      }

      // Step 2: upload the file bytes straight to storage from the browser
      setStatus('Uploading… 0%', false);
      await putWithProgress(signData.uploadUrl, file);

      // Step 3: save just the metadata
      setStatus('Saving…', false);
      const saveRes = await fetch('<?= base_url('videos') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          title: title,
          description: description,
          storedName: signData.storedName,
          originalName: signData.originalName,
          mimeType: file.type,
          publicUrl: signData.publicUrl,
          fileSize: file.size,
        }),
      });
      const saveData = await saveRes.json();
      if (!saveRes.ok) {
        throw new Error(saveData.error || 'Could not save the video.');
      }

      setStatus('Done! Reloading…', false);
      window.location.href = '<?= base_url('videos') ?>';
    } catch (err) {
      submitBtn.disabled = false;
      setStatus(err.message || 'Something went wrong. Please try again.', true);
    }
  });
</script>
</body>
</html>
