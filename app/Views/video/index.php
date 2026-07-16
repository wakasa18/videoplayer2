<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Video Library</title>
<style>
  :root{
    --bg:        #1B1917;
    --surface:   #242220;
    --surface-2: #2C2A27;
    --hairline:  #3A3733;
    --text:      #EDE7DD;
    --text-dim:  #A39C90;
    --amber:     #E8A33D;
    --teal:      #3FA9A4;
    --red:       #D6584A;
    --radius:    10px;
  }
  * { box-sizing: border-box; }
  body{
    margin:0;
    background:
      repeating-linear-gradient(180deg, rgba(255,255,255,0.015) 0 2px, transparent 2px 4px),
      var(--bg);
    color:var(--text);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    min-height:100vh;
  }
  .wrap{ max-width:1080px; margin:0 auto; padding:36px 24px 80px; }

  /* -- header / sprocket rule -- */
  header{ margin-bottom:28px; }
  .eyebrow{
    font-family: 'JetBrains Mono', 'SFMono-Regular', Menlo, monospace;
    font-size:12px; letter-spacing:.14em; text-transform:uppercase;
    color:var(--amber); margin:0 0 6px;
  }
  h1{ margin:0 0 14px; font-size:32px; font-weight:700; letter-spacing:-.01em; }
  .sprockets{
    height:14px;
    background-image: radial-gradient(circle, var(--hairline) 2.5px, transparent 2.6px);
    background-size: 22px 14px;
    background-repeat: repeat-x;
    background-position: left center;
    border-top:1px solid var(--hairline);
    border-bottom:1px solid var(--hairline);
    opacity:.7;
  }

  /* -- flash messages -- */
  .flash{ margin:20px 0; padding:12px 16px; border-radius:var(--radius); font-size:14px; }
  .flash.success{ background:rgba(63,169,164,.12); border:1px solid var(--teal); color:#BFEAE7; }
  .flash.error{ background:rgba(214,88,74,.12); border:1px solid var(--red); color:#F3C6C0; }
  .flash ul{ margin:4px 0 0; padding-left:18px; }

  /* -- layout -- */
  .layout{ display:grid; grid-template-columns: 1.4fr 1fr; gap:24px; margin-top:28px; }
  @media (max-width: 860px){ .layout{ grid-template-columns: 1fr; } }

  .panel{
    background:var(--surface);
    border:1px solid var(--hairline);
    border-radius:var(--radius);
    padding:20px;
  }
  .panel h2{
    margin:0 0 14px; font-size:13px; text-transform:uppercase; letter-spacing:.1em;
    color:var(--text-dim); font-weight:600;
  }

  /* -- player -- */
  .player-frame{
    background:#000; border-radius:8px; overflow:hidden; aspect-ratio:16/9;
    display:flex; align-items:center; justify-content:center; border:1px solid var(--hairline);
  }
  .player-frame video{ width:100%; height:100%; display:block; background:#000; }
  .player-empty{ color:var(--text-dim); font-size:14px; text-align:center; padding:20px; }
  .now-playing{ margin-top:12px; font-size:14px; color:var(--text-dim); }
  .now-playing strong{ color:var(--text); font-weight:600; }

  /* -- upload form -- */
  label{ display:block; font-size:12px; color:var(--text-dim); margin:14px 0 6px; }
  label:first-of-type{ margin-top:0; }
  input[type="text"], textarea{
    width:100%; background:var(--surface-2); border:1px solid var(--hairline);
    border-radius:6px; padding:10px 12px; color:var(--text); font-size:14px; font-family:inherit;
    resize:vertical;
  }
  input[type="text"]:focus, textarea:focus, input[type="file"]:focus{
    outline:2px solid var(--amber); outline-offset:1px;
  }
  .dropzone{
    margin-top:6px; border:1.5px dashed var(--hairline); border-radius:8px;
    padding:22px 14px; text-align:center; background:var(--surface-2);
    transition: border-color .15s ease, background .15s ease;
  }
  .dropzone.drag{ border-color:var(--amber); background:rgba(232,163,61,.08); }
  .dropzone p{ margin:0 0 8px; font-size:13px; color:var(--text-dim); }
  .dropzone input[type="file"]{ color:var(--text-dim); font-size:13px; width:100%; }
  .file-hint{ font-size:11px; color:var(--text-dim); margin-top:6px; }

  button{
    font-family:inherit; cursor:pointer; border:none; border-radius:6px;
    font-size:14px; font-weight:600; padding:11px 18px;
  }
  .btn-primary{ background:var(--amber); color:#221A0C; width:100%; margin-top:18px; }
  .btn-primary:hover{ background:#f0af52; }

  /* -- video list -- */
  .video-list{ list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:8px; }
  .video-item{
    display:flex; align-items:center; gap:12px;
    background:var(--surface-2); border:1px solid var(--hairline); border-radius:8px;
    padding:10px 12px; cursor:pointer; transition:border-color .12s ease, background .12s ease;
  }
  .video-item:hover{ border-color:var(--amber); }
  .video-item.active{ border-color:var(--amber); background:rgba(232,163,61,.08); }
  .video-thumb{
    width:56px; height:34px; border-radius:4px; background:#000; flex:none;
    display:flex; align-items:center; justify-content:center; color:var(--amber); font-size:16px;
    border:1px solid var(--hairline);
  }
  .video-meta{ flex:1; min-width:0; }
  .video-title{ font-size:14px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .video-sub{
    font-family:'JetBrains Mono', Menlo, monospace; font-size:11px; color:var(--text-dim); margin-top:2px;
  }
  .video-del{
    flex:none; background:transparent; color:var(--text-dim); font-size:12px; padding:6px 8px;
  }
  .video-del:hover{ color:var(--red); }
  .empty-state{ color:var(--text-dim); font-size:14px; text-align:center; padding:24px 8px; }
</style>
</head>
<body>
<div class="wrap">

  <header>
    <p class="eyebrow">Local library / 001</p>
    <h1>Video Library</h1>
    <div class="sprockets"></div>
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
      <h2>Now Playing</h2>
      <div class="player-frame" id="playerFrame">
        <video id="player" controls preload="metadata"></video>
        <div class="player-empty" id="playerEmpty">Select a video from the list to start playback.</div>
      </div>
      <div class="now-playing" id="nowPlaying" style="display:none;">
        Playing: <strong id="nowPlayingTitle"></strong>
      </div>

      <h2 style="margin-top:26px;">Library (<?= count($videos) ?>)</h2>
      <?php if (empty($videos)): ?>
        <div class="empty-state">No videos yet. Upload one to get started.</div>
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
                    onsubmit="return confirm('Delete this video? This cannot be undone.');">
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
      <h2>Add Video</h2>
      <form action="<?= base_url('videos') ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?= old('title') ?>" required maxlength="255">

        <label for="description">Description (optional)</label>
        <textarea id="description" name="description" rows="3"><?= old('description') ?></textarea>

        <label for="video">Video file</label>
        <div class="dropzone" id="dropzone">
          <p>MP4, WebM, OGG, MOV, AVI, or MKV &middot; up to 200MB</p>
          <input type="file" id="video" name="video" accept="video/*" required>
        </div>

        <button type="submit" class="btn-primary">Upload video</button>
      </form>
    </div>

  </div>
</div>

<script>
  const player      = document.getElementById('player');
  const playerEmpty = document.getElementById('playerEmpty');
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
      player.play().catch(() => { /* autoplay may be blocked, that's fine */ });
    });
  });

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
</script>
</body>
</html>
