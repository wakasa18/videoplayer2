<!DOCTYPE html>
<html lang="en">
<head>
<title>Video Observatory</title>
<?= view('partials/theme_head') ?>
<style>
.layout{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(300px,1fr);gap:22px;margin-top:26px}.player-frame{background:#111b2b;border-radius:11px;overflow:hidden;aspect-ratio:16/9;display:flex;align-items:center;justify-content:center;border:2px solid var(--game-border)}.player-frame video{width:100%;height:100%;display:block;background:#000}.player-empty{color:#dcecf4;font-size:14px;text-align:center;padding:20px}.now-playing{margin-top:13px;font-size:13px;color:var(--game-muted)}.now-playing::before{content:'';display:inline-block;width:7px;height:7px;margin-right:7px;border-radius:50%;background:var(--red);vertical-align:middle;animation:liveDot 1.4s ease-in-out infinite}.now-playing strong{color:#225d72}.dropzone{margin-top:6px;padding:22px 14px;text-align:center}.dropzone p{margin:0 0 8px;font-size:13px}.dropzone input[type=file]{width:100%;font-size:13px}.video-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:9px}.video-item{display:flex;align-items:center;gap:12px;padding:11px 12px;cursor:pointer}.video-thumb{width:58px;height:36px;border-radius:7px;background:#18283d;flex:none;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;border:2px solid #31556a}.video-meta{flex:1;min-width:0}.video-title{font-size:15px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.video-sub{font-size:12px;margin-top:2px}.empty-state{text-align:center;padding:24px 8px}.progress-shimmer{background-image:linear-gradient(90deg,#42c94d,#27bfe3,#42c94d);background-size:200% 100%;animation:progressShimmer 1.2s linear infinite}@media(max-width:860px){.layout{grid-template-columns:1fr}}
</style>
<?= view('partials/retro_theme') ?>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<div class="wrap">

  <?= view('partials/deadline_banner') ?>

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
                <button type="submit" class="icon-del" title="Delete">&times;</button>
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
