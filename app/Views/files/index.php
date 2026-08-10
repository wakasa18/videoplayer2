<!DOCTYPE html>
<html lang="en">
<head>
<title>Important Files · Damon's Archive</title>
<?= view('partials/theme_head') ?>
<style>
  /* -- layout -- */
  .layout{ display:grid; grid-template-columns: 1.4fr 1fr; gap:24px; margin-top:28px; }
  @media (max-width: 860px){ .layout{ grid-template-columns: 1fr; } }
  .layout > .panel:nth-of-type(1){ animation-delay:.28s; }
  .layout > .panel:nth-of-type(2){ animation-delay:.38s; }

  .panel-head-row{ display:flex; align-items:baseline; justify-content:space-between; gap:12px; margin-bottom:14px; }
  .panel-head-row h2{ margin-bottom:0; }
  .lock-link{
    font-family:'JetBrains Mono', Menlo, monospace; font-size:11px; color:var(--text-dim);
    background:transparent; border:none; cursor:pointer; text-decoration:none;
    transition:color .15s ease;
  }
  .lock-link:hover{ color:var(--red); }

  /* -- file list -- */
  .file-list{ list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:8px; }
  .file-item{
    display:flex; align-items:center; gap:12px;
    background:var(--surface-2); border:1px solid var(--hairline); border-radius:8px;
    padding:10px 12px;
    transition:border-color .15s ease, transform .15s ease;
    opacity:0; animation:fadeInUp .4s ease forwards;
  }
  .file-item:nth-child(1){ animation-delay:.05s; }
  .file-item:nth-child(2){ animation-delay:.10s; }
  .file-item:nth-child(3){ animation-delay:.15s; }
  .file-item:nth-child(4){ animation-delay:.20s; }
  .file-item:nth-child(5){ animation-delay:.25s; }
  .file-item:nth-child(6){ animation-delay:.30s; }
  .file-item:nth-child(7){ animation-delay:.35s; }
  .file-item:nth-child(8){ animation-delay:.40s; }
  .file-item:hover{ border-color:#2c3a68; transform:translateX(3px); }

  .file-type-badge{
    flex:none; width:44px; height:36px; border-radius:6px; background:var(--surface);
    border:1px solid var(--hairline); display:flex; align-items:center; justify-content:center;
    font-family:'JetBrains Mono', Menlo, monospace; font-size:9px; font-weight:700; letter-spacing:.03em;
    color:var(--cyan);
  }
  .file-meta{ flex:1; min-width:0; }
  .file-title{ font-size:14px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .file-sub{ display:flex; align-items:center; gap:8px; margin-top:3px; flex-wrap:wrap; }
  .file-sub-text{ font-family:'JetBrains Mono', Menlo, monospace; font-size:11px; color:var(--text-dim); }
  .category-tag{
    display:inline-block; font-size:10px; text-transform:uppercase; letter-spacing:.06em;
    color:var(--text-dim); background:var(--surface); border:1px solid var(--hairline);
    padding:2px 7px; border-radius:20px;
  }
  .file-desc{ font-size:12px; color:var(--text-dim); margin-top:3px; }
  .file-actions{ display:flex; align-items:center; gap:4px; flex:none; }
  .download-btn{
    flex:none; background:transparent; color:var(--text-dim); text-decoration:none;
    font-size:14px; padding:6px 8px; border-radius:6px;
    transition: color .15s ease, transform .15s ease;
  }
  .download-btn:hover{ color:var(--cyan); transform:scale(1.15); }

  /* -- upload form -- */
  select{
    width:100%; background:var(--surface-2); border:1px solid var(--hairline);
    border-radius:6px; padding:10px 12px; color:var(--text); font-size:14px; font-family:inherit;
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
    <p class="eyebrow">Restricted Archive · Sector 04</p>
    <h1>Important Files</h1>
    <div class="starline"></div>
  </header>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="flash success" role="status" aria-live="polite"><?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?>
    <div class="flash error" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <div class="layout">

    <div class="panel">
      <div class="panel-head-row">
        <h2>Secured Files (<?= count($files) ?>)</h2>
        <form action="<?= base_url('files/lock') ?>" method="post">
          <?= csrf_field() ?>
          <button type="submit" class="lock-link">&#128274; Lock</button>
        </form>
      </div>

      <?php if (empty($files)): ?>
        <div class="empty-state">No files stored yet. Add one to begin your vault.</div>
      <?php else: ?>
        <ul class="file-list">
          <?php foreach ($files as $f): ?>
            <?php $typeLabel = \App\Models\ImportantFileModel::typeLabel($f['mime_type'], $f['original_filename']); ?>
            <li class="file-item">
              <div class="file-type-badge"><?= esc($typeLabel) ?></div>
              <div class="file-meta">
                <div class="file-title"><?= esc($f['title']) ?></div>
                <?php if (! empty($f['description'])): ?>
                  <div class="file-desc"><?= esc($f['description']) ?></div>
                <?php endif; ?>
                <div class="file-sub">
                  <?php if (! empty($f['category'])): ?>
                    <span class="category-tag"><?= esc($f['category']) ?></span>
                  <?php endif; ?>
                  <span class="file-sub-text"><?= \App\Models\ImportantFileModel::formatBytes((int) $f['file_size']) ?> &middot; <?= esc(date('M j, Y', strtotime((string) $f['created_at']))) ?></span>
                </div>
              </div>
              <div class="file-actions">
                <a href="<?= base_url('files/' . $f['id'] . '/download') ?>" class="download-btn" title="Download" aria-label="Download <?= esc($f['title'], 'attr') ?>" target="_blank" rel="noopener">&#8681;</a>
                <form action="<?= base_url('files/' . $f['id'] . '/delete') ?>" method="post" onsubmit="return confirm('Delete this file? This can\'t be undone.');">
                  <?= csrf_field() ?>
                  <button type="submit" class="icon-del" title="Delete" aria-label="Delete <?= esc($f['title'], 'attr') ?>">&times;</button>
                </form>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="panel">
      <h2>Add to Vault</h2>
      <form id="uploadForm">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" maxlength="255" required>

        <label for="description">Description (optional)</label>
        <textarea id="description" name="description" rows="2"></textarea>

        <label for="category">Category (optional)</label>
        <input type="text" id="category" name="category" maxlength="100" placeholder="e.g. ID, Certificate, Tax">

        <label for="fileInput">File</label>
        <div class="dropzone" id="dropzone">
          <p>PDF, Word, Excel, PowerPoint, images, text, or ZIP</p>
          <input type="file" id="fileInput" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.png,.jpg,.jpeg,.webp,.gif,.heic" required>
        </div>
        <p class="file-hint">Stored in a private bucket — never a public link, only short-lived signed downloads.</p>

        <div id="uploadStatus" style="display:none; margin: 10px 0; font-size: 14px; transition: opacity .2s ease;"></div>
        <div id="uploadProgressWrap" style="display:none; height:6px; background:var(--surface-2); border-radius:4px; overflow:hidden; margin: 10px 0;">
          <div id="uploadProgressBar" class="progress-shimmer" style="height:100%; width:0%; transition:width .15s;"></div>
        </div>

        <button type="submit" class="btn-primary" id="uploadSubmitBtn">Add to vault</button>
      </form>
    </div>

  </div>

</div>

<?= view('partials/theme_scripts') ?>
<script>
  // simple drag styling for the dropzone
  const dropzone  = document.getElementById('dropzone');
  const fileInput = document.getElementById('fileInput');
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

  // --- Direct-to-storage upload flow (private bucket) ---
  // Same shape as the Videos upload: the file never touches our own
  // backend — we ask for a signed upload URL, PUT the file straight to
  // the private Supabase bucket from the browser, then send back just a
  // small JSON metadata record to save.
  const uploadForm   = document.getElementById('uploadForm');
  const uploadStatus = document.getElementById('uploadStatus');
  const progressWrap = document.getElementById('uploadProgressWrap');
  const progressBar  = document.getElementById('uploadProgressBar');
  const submitBtn    = document.getElementById('uploadSubmitBtn');

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
    const category = document.getElementById('category').value.trim();
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
      const signRes = await fetch('<?= base_url('files/sign-upload') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ filename: file.name, mimetype: file.type }),
      });
      const signData = await signRes.json();
      if (!signRes.ok) {
        throw new Error(signData.error || 'Could not prepare the upload.');
      }

      setStatus('Uploading… 0%', false);
      await putWithProgress(signData.uploadUrl, file);

      setStatus('Saving…', false);
      const saveRes = await fetch('<?= base_url('files/store') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          title: title,
          description: description,
          category: category,
          storedName: signData.storedName,
          originalName: signData.originalName,
          mimeType: file.type,
          fileSize: file.size,
        }),
      });
      const saveData = await saveRes.json();
      if (!saveRes.ok) {
        throw new Error(saveData.error || 'Could not save the file.');
      }

      setStatus('Done! Reloading…', false);
      window.location.href = '<?= base_url('files') ?>';
    } catch (err) {
      submitBtn.disabled = false;
      setStatus(err.message || 'Something went wrong. Please try again.', true);
    }
  });
</script>
</body>
</html>
