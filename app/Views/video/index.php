<!DOCTYPE html>
<html lang="en">
<head>
<title>Video Observatory</title>
<?= view('partials/theme_head') ?>
<style>
  /* -- layout -- */
  .layout{ display:grid; grid-template-columns: 1.4fr 1fr; gap:24px; margin-top:28px; }
  @media (max-width: 860px){ .layout{ grid-template-columns: 1fr; } }
  .layout > .panel:nth-of-type(1){ animation-delay:.28s; }
  .layout > .panel:nth-of-type(2){ animation-delay:.38s; }

  /* -- player, framed like a viewfinder -- */
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

  <a href="<?= base_url('/') ?>" class="nav-back">&larr; Home</a>

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

<?= view('partials/theme_scripts') ?>
<script>
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
