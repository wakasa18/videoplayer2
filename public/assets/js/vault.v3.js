const vaultConfig=window.VAULT_CONFIG||{},CSRF_HEADER=vaultConfig.csrfHeader||'X-CSRF-TOKEN',CSRF_HASH=vaultConfig.csrfHash||'',MAX_BYTES=Number(vaultConfig.maxBytes||0),CURRENT_PATH=vaultConfig.currentPath||'';
const uploadForm=document.getElementById('uploadForm'),fileInput=document.getElementById('fileInput'),folderInput=document.getElementById('folderInput'),selectionSummary=document.getElementById('selectionSummary'),dropzone=document.getElementById('dropzone'),selectedFiles=document.getElementById('selectedFiles'),uploadStatus=document.getElementById('uploadStatus'),progressWrap=document.getElementById('progressWrap'),progressBar=document.getElementById('progressBar'),uploadBtn=document.getElementById('uploadBtn'),cancelBtn=document.getElementById('cancelBtn');
const shareModal=document.getElementById('shareModal'),shareForm=document.getElementById('shareForm'),shareModalTitle=document.getElementById('shareModalTitle'),shareHelp=document.getElementById('shareHelp'),shareDuration=document.getElementById('shareDuration'),shareMaxDownloads=document.getElementById('shareMaxDownloads'),shareCreateBtn=document.getElementById('shareCreateBtn'),shareResult=document.getElementById('shareResult'),shareLinkInput=document.getElementById('shareLinkInput'),shareCopyBtn=document.getElementById('shareCopyBtn'),shareResultNote=document.getElementById('shareResultNote'),shareList=document.getElementById('shareList');let currentShareTarget=null;
let currentXhr = null;
let currentToken = null;
let activeUploadId = null;
let cancelled = false;
let uploadQueue = [];
let uploadQueueSource = 'files';
let skippedSelectionCount = 0;
let uploadIdCounter = 0;
let uploadBusy = false;
const HASH_LIMIT_BYTES = 32 * 1024 * 1024;

function formatBytes(bytes) {
  const units = ['B', 'KB', 'MB', 'GB', 'TB'];
  let index = 0;
  let value = Number(bytes) || 0;
  while (value >= 1024 && index < units.length - 1) {
    value /= 1024;
    index++;
  }
  return (index ? value.toFixed(1) : Math.round(value)) + ' ' + units[index];
}

function baseTitle(name) {
  return name.replace(/\.[^.]+$/, '').replace(/[_-]+/g, ' ').trim() || name;
}

function setStatus(message, error = false) {
  uploadStatus.style.display = 'block';
  uploadStatus.textContent = message;
  uploadStatus.style.color = error ? 'var(--red)' : 'var(--text-dim)';
}

function relativePath(file) {
  return String(file.webkitRelativePath || file.name)
    .replace(/\\/g, '/')
    .replace(/^\/+|\/+$/g, '');
}

function selectedFolder(file) {
  const path = relativePath(file);
  const cut = path.lastIndexOf('/');
  const inside = cut > 0 ? path.slice(0, cut) : '';
  return [CURRENT_PATH, inside].filter(Boolean).join('/');
}

function queueStatusLabel(entry) {
  const labels = {
    ready: 'Ready',
    hashing: 'Preparing',
    uploading: Math.max(0, Math.min(100, entry.progress || 0)) + '%',
    finalizing: 'Saving',
    done: 'Uploaded',
    error: 'Retry',
  };
  return labels[entry.status] || 'Ready';
}

function updateUploadButton() {
  const retryable = uploadQueue.filter(entry => entry.status === 'ready' || entry.status === 'error').length;
  const failed = uploadQueue.filter(entry => entry.status === 'error').length;
  if (uploadBusy) {
    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Uploading…';
    return;
  }
  uploadBtn.disabled = retryable === 0;
  if (retryable === 0) {
    uploadBtn.textContent = uploadQueue.some(entry => entry.status === 'done') ? 'Uploaded' : 'Add to vault';
    return;
  }
  uploadBtn.textContent = failed > 0
    ? 'Retry ' + failed + ' failed'
    : 'Upload ' + retryable + ' file' + (retryable === 1 ? '' : 's');
}

function renderUploadQueue() {
  selectedFiles.innerHTML = '';
  for (const entry of uploadQueue) {
    const row = document.createElement('div');
    row.className = 'selected-file status-' + entry.status;
    row.dataset.uploadId = entry.id;

    const copy = document.createElement('span');
    copy.className = 'selected-file-copy';

    const name = document.createElement('span');
    name.className = 'selected-file-name';
    name.textContent = entry.file.name;

    const path = document.createElement('span');
    path.className = 'selected-file-path';
    path.textContent = selectedFolder(entry.file) || 'My Drive';

    copy.append(name, path);
    if (entry.error) {
      const error = document.createElement('span');
      error.className = 'selected-file-error';
      error.textContent = entry.error;
      copy.appendChild(error);
    }

    const side = document.createElement('span');
    side.className = 'selected-file-side';

    const size = document.createElement('strong');
    size.textContent = formatBytes(entry.file.size);

    const status = document.createElement('span');
    status.className = 'upload-file-status';
    status.textContent = queueStatusLabel(entry);

    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'selected-file-remove';
    remove.dataset.removeUpload = entry.id;
    remove.setAttribute('aria-label', 'Remove ' + entry.file.name);
    remove.title = 'Remove from upload queue';
    remove.textContent = '×';
    remove.disabled = entry.id === activeUploadId || ['hashing', 'uploading', 'finalizing', 'done'].includes(entry.status);

    side.append(size, status, remove);
    row.append(copy, side);

    if (['hashing', 'uploading', 'finalizing'].includes(entry.status)) {
      const track = document.createElement('div');
      track.className = 'selected-file-progress';
      const bar = document.createElement('span');
      bar.style.width = (entry.status === 'uploading' ? entry.progress : entry.status === 'finalizing' ? 100 : 4) + '%';
      track.appendChild(bar);
      row.appendChild(track);
    }

    selectedFiles.appendChild(row);
  }

  const ready = uploadQueue.filter(entry => entry.status === 'ready').length;
  const failed = uploadQueue.filter(entry => entry.status === 'error').length;
  const done = uploadQueue.filter(entry => entry.status === 'done').length;
  const parts = [];
  if (ready) parts.push(ready + ' ready');
  if (done) parts.push('<span class="upload-summary-success">' + done + ' uploaded</span>');
  if (failed) parts.push('<span class="upload-summary-error">' + failed + ' failed</span>');
  if (uploadQueueSource === 'folder' && uploadQueue.length) parts.push('folder structure kept');
  if (skippedSelectionCount) parts.push(skippedSelectionCount + ' empty or oversized skipped');
  selectionSummary.innerHTML = parts.length ? parts.join(' · ') : 'Nothing selected';
  updateUploadButton();
}

function chooseUploadFiles(fileList, source = 'files') {
  const all = [...fileList];
  const valid = all.filter(file => file.size > 0 && file.size <= MAX_BYTES);
  skippedSelectionCount = all.length - valid.length;
  uploadQueueSource = source;
  uploadQueue = valid.map(file => ({
    id: 'upload-' + Date.now() + '-' + (++uploadIdCounter),
    file,
    status: 'ready',
    progress: 0,
    error: '',
  }));
  renderUploadQueue();
  if (skippedSelectionCount) {
    setStatus(skippedSelectionCount + ' file' + (skippedSelectionCount === 1 ? ' was' : 's were') + ' skipped because they were empty or over the upload limit.', true);
  } else {
    uploadStatus.style.display = 'none';
  }
}

fileInput.addEventListener('change', () => {
  chooseUploadFiles(fileInput.files, 'files');
  fileInput.value = '';
});
folderInput.addEventListener('change', () => {
  chooseUploadFiles(folderInput.files, 'folder');
  folderInput.value = '';
});
['dragenter', 'dragover'].forEach(eventName => dropzone.addEventListener(eventName, event => {
  event.preventDefault();
  dropzone.classList.add('drag');
}));
['dragleave', 'drop'].forEach(eventName => dropzone.addEventListener(eventName, event => {
  event.preventDefault();
  dropzone.classList.remove('drag');
}));
dropzone.addEventListener('drop', event => {
  if (event.dataTransfer.files.length) chooseUploadFiles(event.dataTransfer.files, 'files');
});
selectedFiles.addEventListener('click', event => {
  const button = event.target.closest('[data-remove-upload]');
  if (!button || button.disabled) return;
  uploadQueue = uploadQueue.filter(entry => entry.id !== button.dataset.removeUpload);
  renderUploadQueue();
});

async function fetchJson(url, options = {}) {
  options.headers = Object.assign({
    'Content-Type': 'application/json',
    [CSRF_HEADER]: CSRF_HASH,
  }, options.headers || {});
  const response = await fetch(url, options);
  const text = await response.text();
  let data = {};
  try {
    data = JSON.parse(text);
  } catch (error) {
    throw new Error(response.status === 403
      ? 'Your security token expired. Refresh the page and try again.'
      : 'The server returned an unexpected response.');
  }
  if (!response.ok) throw new Error(data.error || 'Request failed.');
  return data;
}

function formatShareDate(value) {
  if (!value) return 'No expiration';
  const date = new Date(String(value).replace(' ', 'T'));
  return Number.isFinite(date.getTime()) ? date.toLocaleString() : value;
}

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>'"]/g, character => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
  }[character]));
}

function renderShareRows(rows) {
  if (!Array.isArray(rows) || rows.length === 0) {
    shareList.innerHTML = '<div class="share-list-empty">No share links have been created for this ' + escapeHtml(currentShareTarget?.type || 'item') + '.</div>';
    return;
  }
  shareList.innerHTML = rows.map(row => {
    const active = row.status === 'active';
    const limit = row.maxDownloads ? row.downloadCount + ' / ' + row.maxDownloads + ' downloads' : row.downloadCount + ' downloads';
    const expires = row.expiresAt ? 'Expires ' + formatShareDate(row.expiresAt) : 'No expiration';
    let copyAction = '';
    if (active && row.canCopy) {
      copyAction = '<button type="button" class="share-history-copy js-copy-share" data-copy-mode="copy" data-share-record-id="' + Number(row.id) + '">Copy link</button>';
    } else if (active && row.canRotate) {
      copyAction = '<button type="button" class="share-history-copy rotate js-copy-share" data-copy-mode="rotate" data-share-record-id="' + Number(row.id) + '">Replace & copy</button>';
    }
    const revokeAction = '<button type="button" class="share-revoke js-revoke-share" data-share-record-id="' + Number(row.id) + '" ' + (active ? '' : 'disabled') + '>' + (active ? 'Disable' : 'Disabled') + '</button>';
    return '<div class="share-row-item"><div class="share-row-main"><div class="share-row-line"><span class="share-status ' + escapeHtml(row.status) + '">' + escapeHtml(row.statusLabel) + '</span>' + escapeHtml(expires) + '</div><div class="share-row-meta">' + escapeHtml(limit) + ' · ' + Number(row.viewCount || 0) + ' views · created ' + escapeHtml(formatShareDate(row.createdAt)) + '</div></div><div class="share-row-actions">' + copyAction + revokeAction + '</div></div>';
  }).join('');
}

function shareEndpoint() {
  if (!currentShareTarget) return '';
  return currentShareTarget.type === 'folder'
    ? vaultConfig.urls.folderShares
    : vaultConfig.urls.filesBase + '/' + currentShareTarget.id + '/shares';
}

async function loadShareRows() {
  if (!currentShareTarget) return;
  shareList.innerHTML = '<div class="share-loading">Loading links…</div>';
  try {
    let url = shareEndpoint();
    if (currentShareTarget.type === 'folder') url += '?path=' + encodeURIComponent(currentShareTarget.path);
    const data = await fetchJson(url, { method: 'GET' });
    renderShareRows(data.shares || []);
  } catch (error) {
    shareList.innerHTML = '<div class="share-list-empty">' + escapeHtml(error.message || 'Could not load share links.') + '</div>';
  }
}

async function openShareModal(button) {
  const folder = button.classList.contains('js-share-folder');
  currentShareTarget = folder
    ? { type: 'folder', path: button.dataset.folderPath || '', name: button.dataset.folderName || 'folder' }
    : { type: 'file', id: Number(button.dataset.shareId || 0), name: button.dataset.shareTitle || 'file' };
  shareModalTitle.textContent = 'Share “' + currentShareTarget.name + '”';
  shareHelp.textContent = folder
    ? 'Anyone with the link can browse this folder, open its files and subfolders, and download the folder as a ZIP.'
    : 'Anyone with the link can open or download only this file.';
  shareDuration.value = '7d';
  shareMaxDownloads.value = '0';
  shareResult.hidden = true;
  shareLinkInput.value = '';
  shareCopyBtn.textContent = 'Copy';
  shareResultNote.textContent = 'You can copy this link again later from Link History.';
  openModal(shareModal);
  await loadShareRows();
}

shareForm.addEventListener('submit', async event => {
  event.preventDefault();
  if (!currentShareTarget) return;
  shareCreateBtn.disabled = true;
  shareCreateBtn.textContent = 'Creating…';
  try {
    const body = { duration: shareDuration.value, maxDownloads: Number(shareMaxDownloads.value || 0) };
    if (currentShareTarget.type === 'folder') body.path = currentShareTarget.path;
    const data = await fetchJson(shareEndpoint(), { method: 'POST', body: JSON.stringify(body) });
    shareLinkInput.value = data.shareUrl || '';
    shareResult.hidden = false;
    shareCopyBtn.textContent = 'Copy';
    shareResultNote.textContent = data.message || 'You can copy this link again later from Link History.';
    await loadShareRows();
    shareLinkInput.focus();
    shareLinkInput.select();
  } catch (error) {
    alert(error.message || 'Could not create the share link.');
  } finally {
    shareCreateBtn.disabled = false;
    shareCreateBtn.textContent = 'Create link';
  }
});

async function copyText(value) {
  if (!value) return false;
  try {
    await navigator.clipboard.writeText(value);
    return true;
  } catch (error) {
    const temporary = document.createElement('textarea');
    temporary.value = value;
    temporary.setAttribute('readonly', '');
    temporary.style.position = 'fixed';
    temporary.style.opacity = '0';
    document.body.appendChild(temporary);
    temporary.select();
    const copied = document.execCommand('copy');
    temporary.remove();
    return copied;
  }
}

shareCopyBtn.addEventListener('click', async () => {
  if (!shareLinkInput.value) return;
  const copied = await copyText(shareLinkInput.value);
  shareCopyBtn.textContent = copied ? 'Copied' : 'Select link';
  if (!copied) {
    shareLinkInput.focus();
    shareLinkInput.select();
  }
  setTimeout(() => shareCopyBtn.textContent = 'Copy', 1800);
});

shareList.addEventListener('click', async event => {
  const copyButton = event.target.closest('.js-copy-share');
  if (copyButton && !copyButton.disabled) {
    const shareId = Number(copyButton.dataset.shareRecordId || 0);
    const rotate = copyButton.dataset.copyMode === 'rotate';
    if (!shareId) return;
    if (rotate && !confirm('This older link was created before repeat-copy support. Replace it with a new link? The old link will immediately stop working.')) return;

    const originalLabel = copyButton.textContent;
    copyButton.disabled = true;
    copyButton.textContent = rotate ? 'Replacing…' : 'Copying…';
    try {
      const endpoint = vaultConfig.urls.sharesBase + '/' + shareId + '/' + (rotate ? 'rotate' : 'link');
      const data = await fetchJson(endpoint, rotate ? { method: 'POST', body: '{}' } : { method: 'GET' });
      const copied = await copyText(data.shareUrl || '');
      shareLinkInput.value = data.shareUrl || '';
      shareResult.hidden = false;
      shareResultNote.textContent = data.message || 'This link can be copied again later from Link History.';
      copyButton.textContent = copied ? 'Copied' : 'Link ready';
      if (!copied) {
        shareLinkInput.focus();
        shareLinkInput.select();
      }
      if (rotate) await loadShareRows();
      else setTimeout(() => {
        if (document.body.contains(copyButton)) {
          copyButton.disabled = false;
          copyButton.textContent = originalLabel;
        }
      }, 1800);
    } catch (error) {
      copyButton.disabled = false;
      copyButton.textContent = originalLabel;
      alert(error.message || 'Could not copy the share link.');
    }
    return;
  }

  const button = event.target.closest('.js-revoke-share');
  if (!button || button.disabled) return;
  if (!confirm('Disable this share link? Anyone using it will immediately lose access.')) return;
  button.disabled = true;
  button.textContent = 'Disabling…';
  try {
    await fetchJson(vaultConfig.urls.sharesBase + '/' + button.dataset.shareRecordId + '/revoke', { method: 'POST', body: '{}' });
    await loadShareRows();
  } catch (error) {
    button.disabled = false;
    button.textContent = 'Disable';
    alert(error.message || 'Could not disable the share link.');
  }
});

async function sha256(file) {
  // Web Crypto requires a full ArrayBuffer. Skipping the optional checksum for
  // large files prevents hundreds of megabytes from being duplicated in RAM.
  if (!window.crypto?.subtle || file.size > HASH_LIMIT_BYTES) return '';
  const buffer = await file.arrayBuffer();
  const digest = await crypto.subtle.digest('SHA-256', buffer);
  return [...new Uint8Array(digest)].map(byte => byte.toString(16).padStart(2, '0')).join('');
}

function setQueueEntry(entry, status, progress = entry.progress, error = '') {
  entry.status = status;
  entry.progress = progress;
  entry.error = error;
  renderUploadQueue();
}

function putWithProgress(url, entry, completed, total) {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    currentXhr = xhr;
    xhr.open('PUT', url);
    xhr.setRequestHeader('Content-Type', entry.file.type || 'application/octet-stream');
    xhr.upload.onprogress = event => {
      if (!event.lengthComputable) return;
      entry.progress = Math.round((event.loaded / event.total) * 100);
      const overall = Math.round(((completed + entry.progress / 100) / total) * 100);
      progressBar.style.width = overall + '%';
      setStatus('Uploading ' + entry.file.name + ' — ' + entry.progress + '%');
      const row = [...selectedFiles.children].find(item => item.dataset.uploadId === entry.id);
      if (row) {
        const status = row.querySelector('.upload-file-status');
        const bar = row.querySelector('.selected-file-progress span');
        if (status) status.textContent = entry.progress + '%';
        if (bar) bar.style.width = entry.progress + '%';
      }
    };
    xhr.onload = () => xhr.status >= 200 && xhr.status < 300
      ? resolve()
      : reject(new Error('Storage upload failed with status ' + xhr.status + '.'));
    xhr.onerror = () => reject(new Error('Storage upload failed. Check your connection.'));
    xhr.onabort = () => reject(new DOMException('Upload cancelled.', 'AbortError'));
    xhr.send(entry.file);
  });
}

async function cleanupPendingUpload() {
  if (!currentToken) return;
  try {
    await fetchJson(vaultConfig.urls.cancelUpload, {
      method: 'POST',
      body: JSON.stringify({ uploadToken: currentToken }),
    });
  } catch (error) {
    // The scheduled cleanup can remove an abandoned pending upload later.
  }
  currentToken = null;
}

async function cancelPending() {
  cancelled = true;
  if (currentXhr) currentXhr.abort();
  await cleanupPendingUpload();
  const active = uploadQueue.find(entry => entry.id === activeUploadId);
  if (active && active.status !== 'done') {
    active.status = 'error';
    active.error = 'Upload cancelled. Retry when ready.';
    active.progress = 0;
  }
  activeUploadId = null;
  currentXhr = null;
  uploadBusy = false;
  uploadBtn.disabled = false;
  cancelBtn.style.display = 'none';
  setStatus('Upload cancelled. Completed files were kept; Retry only processes unfinished files.', true);
  renderUploadQueue();
}

cancelBtn.addEventListener('click', cancelPending);

uploadForm.addEventListener('submit', async event => {
  event.preventDefault();
  const pending = uploadQueue.filter(entry => entry.status === 'ready' || entry.status === 'error');
  if (!pending.length) {
    setStatus('Choose at least one non-empty file within the upload limit.', true);
    return;
  }

  cancelled = false;
  uploadBusy = true;
  uploadBtn.disabled = true;
  uploadBtn.textContent = 'Uploading…';
  cancelBtn.style.display = 'inline-block';
  progressWrap.style.display = 'block';
  progressBar.style.width = '0%';

  const sharedTitle = document.getElementById('title').value.trim();
  const singleSelection = uploadQueue.length === 1;
  let completedThisRun = 0;
  let failedThisRun = 0;

  for (const entry of pending) {
    if (cancelled) break;
    activeUploadId = entry.id;
    currentXhr = null;
    currentToken = null;
    entry.error = '';

    try {
      setQueueEntry(entry, 'hashing', 0);
      setStatus((entry.file.size > HASH_LIMIT_BYTES ? 'Preparing ' : 'Checking ') + entry.file.name + '…');
      const checksum = await sha256(entry.file);
      if (cancelled) throw new DOMException('Upload cancelled.', 'AbortError');

      const title = singleSelection && sharedTitle ? sharedTitle : baseTitle(entry.file.name);
      const sign = await fetchJson(vaultConfig.urls.signUpload, {
        method: 'POST',
        body: JSON.stringify({
          title,
          description: document.getElementById('description').value.trim(),
          category: document.getElementById('category').value.trim(),
          documentDate: document.getElementById('documentDate').value,
          filename: entry.file.name,
          folderPath: selectedFolder(entry.file),
          mimetype: entry.file.type || 'application/octet-stream',
          filesize: entry.file.size,
          checksum,
        }),
      });

      currentToken = sign.uploadToken;
      setQueueEntry(entry, 'uploading', 0);
      await putWithProgress(sign.uploadUrl, entry, completedThisRun, pending.length);
      if (cancelled) throw new DOMException('Upload cancelled.', 'AbortError');

      setQueueEntry(entry, 'finalizing', 100);
      setStatus('Saving ' + entry.file.name + ' to the vault…');
      await fetchJson(vaultConfig.urls.store, {
        method: 'POST',
        body: JSON.stringify({ uploadToken: currentToken }),
      });
      currentToken = null;
      completedThisRun++;
      setQueueEntry(entry, 'done', 100);
    } catch (error) {
      await cleanupPendingUpload();
      if (cancelled || error?.name === 'AbortError') {
        entry.status = 'error';
        entry.progress = 0;
        entry.error = 'Upload cancelled. Retry when ready.';
        renderUploadQueue();
        break;
      }
      failedThisRun++;
      entry.status = 'error';
      entry.progress = 0;
      entry.error = error.message || 'Upload failed.';
      renderUploadQueue();
    } finally {
      currentXhr = null;
      activeUploadId = null;
    }
  }

  cancelBtn.style.display = 'none';
  uploadBusy = false;
  uploadBtn.disabled = false;
  const remaining = uploadQueue.filter(entry => entry.status === 'ready' || entry.status === 'error').length;
  const totalDone = uploadQueue.filter(entry => entry.status === 'done').length;

  if (!cancelled && remaining === 0) {
    progressBar.style.width = '100%';
    setStatus(totalDone + ' file' + (totalDone === 1 ? '' : 's') + ' added. Refreshing folder…');
    setTimeout(() => location.reload(), 650);
    return;
  }

  progressBar.style.width = pending.length
    ? Math.round((completedThisRun / pending.length) * 100) + '%'
    : '0%';
  if (!cancelled) {
    setStatus(
      completedThisRun + ' uploaded' + (failedThisRun ? ' · ' + failedThisRun + ' failed. Retry only processes failed files.' : ''),
      failedThisRun > 0,
    );
  }
  renderUploadQueue();
});

const vaultReturnTo = location.pathname + location.search;
document.getElementById('editReturnTo').value = vaultReturnTo;
document.getElementById('deleteReturnTo').value = vaultReturnTo;
const uploadPanel = document.getElementById('uploadPanel');
function focusUploadPanel() {
  uploadPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
  uploadPanel.classList.add('upload-attention');
  dropzone.classList.add('attention');
  setTimeout(() => {
    uploadPanel.classList.remove('upload-attention');
    dropzone.classList.remove('attention');
  }, 1400);
}
document.getElementById('quickUploadBtn').addEventListener('click', focusUploadPanel);
document.querySelectorAll('.js-focus-upload').forEach(button => button.addEventListener('click', focusUploadPanel));
renderUploadQueue();

const filterForm=document.getElementById('filterForm');let searchTimer,lastSubmittedSearch=document.getElementById('filterQ').value.trim();filterForm.querySelectorAll('select').forEach(el=>el.addEventListener('change',()=>filterForm.requestSubmit()));document.getElementById('filterQ').addEventListener('input',event=>{clearTimeout(searchTimer);const value=event.target.value.trim();if(value.length===1)return;searchTimer=setTimeout(()=>{if(value!==lastSubmittedSearch){lastSubmittedSearch=value;filterForm.requestSubmit();}},700);});
const fileList=document.getElementById('fileList'),listViewBtn=document.getElementById('listViewBtn'),gridViewBtn=document.getElementById('gridViewBtn');function setView(mode,persist=true){const grid=mode==='grid';fileList.classList.toggle('grid-view',grid);listViewBtn.classList.toggle('active',!grid);gridViewBtn.classList.toggle('active',grid);if(persist){try{localStorage.setItem('vault-view',grid?'grid':'list');}catch(error){}}}listViewBtn.addEventListener('click',()=>setView('list'));gridViewBtn.addEventListener('click',()=>setView('grid'));let savedView='list';try{savedView=localStorage.getItem('vault-view')||'list';}catch(error){}setView(savedView,false);
const previewModal=document.getElementById('previewModal'),previewStage=document.getElementById('previewStage'),previewLoading=document.getElementById('previewLoading'),previewPrev=document.getElementById('previewPrev'),previewNext=document.getElementById('previewNext');
let previewFiles=[],previewIndex=0,previewAbortController=null,previewObjectUrl='',previewRenderId=0,pdfJsPromise=null,previewPdfTask=null,previewPdfDocument=null;
const PDFJS_MODULE='https://cdn.jsdelivr.net/npm/pdfjs-dist@6.2.108/build/pdf.min.mjs';
const PDFJS_WORKER='https://cdn.jsdelivr.net/npm/pdfjs-dist@6.2.108/build/pdf.worker.min.mjs';
function closeActionMenus(except=null){document.querySelectorAll('.action-menu[open]').forEach(menu=>{if(menu!==except)menu.open=false;});}
function openModal(modal){closeActionMenus();modal.classList.add('open');modal.setAttribute('aria-hidden','false');document.body.style.overflow='hidden';}
function clearPreviewContent(){
  previewRenderId++;
  if(previewAbortController){previewAbortController.abort();previewAbortController=null;}
  if(previewObjectUrl){URL.revokeObjectURL(previewObjectUrl);previewObjectUrl='';}
  if(previewPdfTask){try{previewPdfTask.destroy();}catch(error){}previewPdfTask=null;}
  if(previewPdfDocument){try{previewPdfDocument.destroy();}catch(error){}previewPdfDocument=null;}
  previewStage.classList.remove('pdf-mode','text-mode');
  previewStage.querySelectorAll('iframe,img,video,audio,.audio-preview,.unsupported-preview,.pdf-preview-pages,.text-preview,.preview-error,.native-pdf-fallback').forEach(el=>el.remove());
}
function closeModal(modal){modal.classList.remove('open');modal.setAttribute('aria-hidden','true');if(!document.querySelector('.modal.open'))document.body.style.overflow='';if(modal.id==='previewModal')clearPreviewContent();}
function parseFileData(element){try{return JSON.parse(element.dataset.openFile||'{}');}catch(e){return{};}}
function collectPreviewFiles(){const seen=new Set();return[...document.querySelectorAll('.file-item[data-open-file]')].map(parseFileData).filter(file=>file.id&&!seen.has(file.id)&&seen.add(file.id));}
function finishPreview(renderId=previewRenderId){if(renderId===previewRenderId)previewLoading.hidden=true;}
function previewUrl(url,full=false){const target=new URL(url,window.location.href);if(full)target.searchParams.set('full','1');target.searchParams.set('_preview',Date.now().toString());return target.toString();}
async function fetchPreviewResponse(url,signal,full=false){
  const response=await fetch(previewUrl(url,full),{credentials:'same-origin',cache:'no-store',redirect:'follow',signal,headers:{Accept:'application/pdf,text/plain,image/*,video/*,audio/*,*/*;q=0.8'}});
  const path=(()=>{try{return new URL(response.url).pathname;}catch(error){return'';}})();
  if(response.redirected&&(/\/login\/?$/.test(path)||/\/files\/gate\/?$/.test(path)))throw new Error('Your session or vault unlock expired. Refresh the page and unlock the vault again.');
  if(!response.ok){let message='Preview request failed ('+response.status+').';try{const body=(await response.clone().text()).trim();if(body&&body.length<240)message=body;}catch(error){}throw new Error(message);}
  return response;
}
function showPreviewError(file,message,renderId=previewRenderId){
  if(renderId!==previewRenderId)return;
  previewLoading.hidden=true;
  const box=document.createElement('div');box.className='preview-error';
  box.innerHTML='<div class="preview-error-icon">ERR</div><h3>Preview could not be displayed</h3><p></p><div class="preview-error-actions"><a class="btn-secondary" target="_blank" rel="noopener">Open in new tab</a><a class="btn-primary" target="_blank" rel="noopener">Download file</a></div>';
  box.querySelector('p').textContent=message||'The file is still safely stored. Open it in a new tab or download it instead.';
  const links=box.querySelectorAll('a');links[0].href=file.previewUrl||file.downloadUrl;links[1].href=file.downloadUrl;
  previewStage.appendChild(box);
}
function loadPdfJs(){
  if(!pdfJsPromise){pdfJsPromise=import(PDFJS_MODULE).then(pdfjs=>{pdfjs.GlobalWorkerOptions.workerSrc=PDFJS_WORKER;return pdfjs;});}
  return pdfJsPromise;
}
function pdfSignature(buffer){const bytes=new Uint8Array(buffer,0,Math.min(8,buffer.byteLength));return String.fromCharCode(...bytes);}
async function renderNativePdfFallback(buffer,file,renderId){
  if(renderId!==previewRenderId)return;
  const blob=new Blob([buffer],{type:'application/pdf'});previewObjectUrl=URL.createObjectURL(blob);
  const wrapper=document.createElement('div');wrapper.className='native-pdf-fallback';
  const notice=document.createElement('div');notice.className='native-pdf-notice';notice.textContent='The enhanced PDF renderer could not start, so the browser viewer is being used. Use Open tab if the page stays blank.';
  const frame=document.createElement('iframe');frame.title='Preview of '+(file.title||file.filename);frame.src=previewObjectUrl+'#toolbar=1&navpanes=0&view=FitH';
  wrapper.append(notice,frame);previewStage.appendChild(wrapper);finishPreview(renderId);
}
async function renderPdfPreview(file,renderId,signal){
  const sourceUrl=previewUrl(file.previewUrl,false);
  const probe=await fetch(sourceUrl,{credentials:'same-origin',cache:'no-store',redirect:'follow',signal,headers:{Range:'bytes=0-7',Accept:'application/pdf,*/*;q=0.8'}});
  const probePath=(()=>{try{return new URL(probe.url).pathname;}catch(error){return'';}})();
  if(probe.redirected&&(/\/login\/?$/.test(probePath)||/\/files\/gate\/?$/.test(probePath)))throw new Error('Your session or vault unlock expired. Refresh the page and unlock the vault again.');
  if(!probe.ok&&probe.status!==206)throw new Error('Preview request failed ('+probe.status+').');
  const signature=pdfSignature(await probe.arrayBuffer());
  if(!signature.startsWith('%PDF-'))throw new Error('The received file is not a valid PDF preview.');
  if(renderId!==previewRenderId||signal.aborted)return;

  const pdfjs=await loadPdfJs();
  if(renderId!==previewRenderId||signal.aborted)return;
  previewPdfTask=pdfjs.getDocument({
    url:sourceUrl,
    withCredentials:true,
    rangeChunkSize:131072,
    disableAutoFetch:true,
    disableStream:false,
    disableRange:false,
  });
  previewPdfDocument=await previewPdfTask.promise;
  if(renderId!==previewRenderId||signal.aborted)return;

  previewStage.classList.add('pdf-mode');
  const pages=document.createElement('div');pages.className='pdf-preview-pages';previewStage.appendChild(pages);
  const progress=document.createElement('div');progress.className='pdf-page-progress';pages.appendChild(progress);
  finishPreview(renderId);

  let rendered=0,isRendering=false;
  const batchSize=window.matchMedia('(max-width:760px)').matches?2:3;
  const renderBatch=async()=>{
    if(isRendering||rendered>=previewPdfDocument.numPages||renderId!==previewRenderId||signal.aborted)return;
    isRendering=true;
    const button=pages.querySelector('.pdf-load-more');if(button){button.disabled=true;button.textContent='Rendering…';}
    const target=Math.min(previewPdfDocument.numPages,rendered+batchSize);
    for(let pageNo=rendered+1;pageNo<=target;pageNo++){
      if(renderId!==previewRenderId||signal.aborted)break;
      const page=await previewPdfDocument.getPage(pageNo);
      const base=page.getViewport({scale:1});
      const available=Math.max(280,Math.min(980,previewStage.clientWidth-44));
      const cssScale=Math.max(.55,Math.min(1.55,available/base.width));
      const dpr=window.matchMedia('(max-width:760px)').matches?1:Math.max(1,Math.min(window.devicePixelRatio||1,1.35));
      const viewport=page.getViewport({scale:cssScale*dpr});
      const shell=document.createElement('div');shell.className='pdf-page-shell';
      const label=document.createElement('div');label.className='pdf-page-label';label.textContent='Page '+pageNo+' of '+previewPdfDocument.numPages;
      const canvas=document.createElement('canvas');canvas.className='pdf-page-canvas';canvas.width=Math.ceil(viewport.width);canvas.height=Math.ceil(viewport.height);canvas.style.width=Math.ceil(viewport.width/dpr)+'px';canvas.style.height=Math.ceil(viewport.height/dpr)+'px';
      shell.append(label,canvas);pages.insertBefore(shell,progress);
      const context=canvas.getContext('2d',{alpha:false});context.fillStyle='#fff';context.fillRect(0,0,canvas.width,canvas.height);
      await page.render({canvasContext:context,viewport}).promise;page.cleanup();rendered=pageNo;
      progress.textContent='Showing '+rendered+' of '+previewPdfDocument.numPages+' pages';
      await new Promise(resolve=>requestAnimationFrame(resolve));
    }
    pages.querySelector('.pdf-load-more-wrap')?.remove();
    if(rendered<previewPdfDocument.numPages&&renderId===previewRenderId&&!signal.aborted){
      const wrap=document.createElement('div');wrap.className='pdf-load-more-wrap';
      const loadMore=document.createElement('button');loadMore.type='button';loadMore.className='pdf-load-more';loadMore.textContent='Load next '+Math.min(batchSize,previewPdfDocument.numPages-rendered)+' pages';
      loadMore.addEventListener('click',renderBatch,{once:true});wrap.appendChild(loadMore);pages.appendChild(wrap);
    }
    isRendering=false;
  };
  await renderBatch();
}
async function renderTextPreview(file,renderId,signal){
  const response=await fetchPreviewResponse(file.previewUrl,signal,false);
  const type=(response.headers.get('content-type')||'').toLowerCase();
  if(type.includes('text/html'))throw new Error('The server returned a web page instead of the file contents. Refresh and unlock the vault again.');
  const value=await response.text();if(renderId!==previewRenderId||signal.aborted)return;
  previewStage.classList.add('text-mode');const pre=document.createElement('pre');pre.className='text-preview';pre.textContent=value||'(This file is empty.)';previewStage.appendChild(pre);finishPreview(renderId);
}
async function renderPreview(file){
  clearPreviewContent();const renderId=previewRenderId;previewAbortController=new AbortController();const signal=previewAbortController.signal;previewLoading.hidden=false;
  const loadingCopy=previewLoading.querySelector('span:last-child');if(loadingCopy)loadingCopy.textContent='Loading secure preview…';
  document.getElementById('previewTitle').textContent=file.title||file.filename;document.getElementById('previewFilename').textContent=file.filename||'';document.getElementById('detailName').textContent=file.filename||'—';document.getElementById('detailFolder').textContent=file.folder||'My Drive';document.getElementById('detailType').textContent=(file.typeLabel||'FILE')+(file.mimeType?' · '+file.mimeType:'');document.getElementById('detailSize').textContent=file.sizeLabel||'—';document.getElementById('detailDate').textContent=file.dateLabel||'—';document.getElementById('detailDescription').textContent=file.description||'No description';const openUrl=file.previewUrl||file.downloadUrl;['previewOpenLink','previewOpenSide'].forEach(id=>document.getElementById(id).href=openUrl);['previewDownloadTop','previewDownloadSide'].forEach(id=>document.getElementById(id).href=file.downloadUrl);previewPrev.disabled=previewIndex<=0;previewNext.disabled=previewIndex>=previewFiles.length-1;
  try{
    if(file.previewKind==='image'){const img=document.createElement('img');img.alt=file.title||file.filename;img.onload=()=>finishPreview(renderId);img.onerror=()=>{img.remove();showPreviewError(file,'The image response could not be displayed.',renderId);};img.src=previewUrl(file.previewUrl);previewStage.appendChild(img);}
    else if(file.previewKind==='video'){const video=document.createElement('video');video.controls=true;video.preload='metadata';video.onloadeddata=()=>finishPreview(renderId);video.onerror=()=>{video.remove();showPreviewError(file,'The video could not be loaded in this browser.',renderId);};video.src=previewUrl(file.previewUrl);previewStage.appendChild(video);}
    else if(file.previewKind==='audio'){const box=document.createElement('div');box.className='audio-preview';box.innerHTML='<div class="big-file-icon"></div><strong></strong>';box.querySelector('.big-file-icon').textContent=file.typeLabel||'AUDIO';box.querySelector('strong').textContent=file.filename;const audio=document.createElement('audio');audio.controls=true;audio.preload='metadata';audio.onloadeddata=()=>finishPreview(renderId);audio.onerror=()=>{box.remove();showPreviewError(file,'The audio file could not be loaded in this browser.',renderId);};audio.src=previewUrl(file.previewUrl);box.appendChild(audio);previewStage.appendChild(box);}
    else if(file.previewKind==='pdf')await renderPdfPreview(file,renderId,signal);
    else if(file.previewKind==='text')await renderTextPreview(file,renderId,signal);
    else{const box=document.createElement('div');box.className='unsupported-preview';box.innerHTML='<div class="big-file-icon"></div><h3>Preview unavailable</h3><p>This file type is safely stored, but the browser cannot display its contents here. Download it to open it with the correct application.</p><a class="btn-primary" target="_blank" rel="noopener">Download file</a>';box.querySelector('.big-file-icon').textContent=file.typeLabel||'FILE';box.querySelector('a').href=file.downloadUrl;previewStage.appendChild(box);finishPreview(renderId);}
  }catch(error){if(error?.name!=='AbortError'&&renderId===previewRenderId)showPreviewError(file,error.message||'The preview request failed.',renderId);}
}
function openFile(file){previewFiles=collectPreviewFiles();previewIndex=Math.max(0,previewFiles.findIndex(item=>item.id===file.id));openModal(previewModal);renderPreview(previewFiles[previewIndex]||file);}previewPrev.addEventListener('click',()=>{if(previewIndex>0){previewIndex--;renderPreview(previewFiles[previewIndex]);}});previewNext.addEventListener('click',()=>{if(previewIndex<previewFiles.length-1){previewIndex++;renderPreview(previewFiles[previewIndex]);}});
const folderDownloadModal=document.getElementById('folderDownloadModal'),folderDownloadTitle=document.getElementById('folderDownloadTitle'),folderDownloadMessage=document.getElementById('folderDownloadMessage'),folderDownloadBar=document.getElementById('folderDownloadBar'),folderDownloadFiles=document.getElementById('folderDownloadFiles'),folderDownloadBytes=document.getElementById('folderDownloadBytes'),folderDownloadNote=document.getElementById('folderDownloadNote'),folderDownloadCancel=document.getElementById('folderDownloadCancel'),folderDownloadDone=document.getElementById('folderDownloadDone');
let folderDownloadAbort=null,folderDownloadBusy=false;
const ZIP_ENCODER=new TextEncoder(),ZIP32_MAX=0xffffffff,ZIP_CRC_TABLE=(()=>{const table=new Uint32Array(256);for(let n=0;n<256;n++){let c=n;for(let k=0;k<8;k++)c=(c&1)?(0xedb88320^(c>>>1)):(c>>>1);table[n]=c>>>0;}return table;})();
function zipCrcUpdate(crc,bytes){let c=crc>>>0;for(let i=0;i<bytes.length;i++)c=(ZIP_CRC_TABLE[(c^bytes[i])&255]^(c>>>8))>>>0;return c>>>0;}
function zipU16(view,offset,value){view.setUint16(offset,value&0xffff,true);}
function zipU32(view,offset,value){view.setUint32(offset,value>>>0,true);}
function zipDosDate(value){const date=value?new Date(value):new Date();const valid=Number.isFinite(date.getTime())?date:new Date();const year=Math.max(1980,Math.min(2107,valid.getFullYear()));return{time:((valid.getHours()&31)<<11)|((valid.getMinutes()&63)<<5)|((Math.floor(valid.getSeconds()/2))&31),date:((year-1980)<<9)|(((valid.getMonth()+1)&15)<<5)|(valid.getDate()&31)};}
function zipLocalHeader(nameBytes,dos){const out=new Uint8Array(30+nameBytes.length),view=new DataView(out.buffer);zipU32(view,0,0x04034b50);zipU16(view,4,20);zipU16(view,6,0x0808);zipU16(view,8,0);zipU16(view,10,dos.time);zipU16(view,12,dos.date);zipU32(view,14,0);zipU32(view,18,0);zipU32(view,22,0);zipU16(view,26,nameBytes.length);zipU16(view,28,0);out.set(nameBytes,30);return out;}
function zipDataDescriptor(crc,size){const out=new Uint8Array(16),view=new DataView(out.buffer);zipU32(view,0,0x08074b50);zipU32(view,4,crc);zipU32(view,8,size);zipU32(view,12,size);return out;}
function zipCentralHeader(entry){const out=new Uint8Array(46+entry.nameBytes.length),view=new DataView(out.buffer);zipU32(view,0,0x02014b50);zipU16(view,4,20);zipU16(view,6,20);zipU16(view,8,0x0808);zipU16(view,10,0);zipU16(view,12,entry.dos.time);zipU16(view,14,entry.dos.date);zipU32(view,16,entry.crc);zipU32(view,20,entry.size);zipU32(view,24,entry.size);zipU16(view,28,entry.nameBytes.length);zipU16(view,30,0);zipU16(view,32,0);zipU16(view,34,0);zipU16(view,36,0);zipU32(view,38,0);zipU32(view,42,entry.offset);out.set(entry.nameBytes,46);return out;}
function zipEndRecord(count,centralSize,centralOffset){const out=new Uint8Array(22),view=new DataView(out.buffer);zipU32(view,0,0x06054b50);zipU16(view,4,0);zipU16(view,6,0);zipU16(view,8,count);zipU16(view,10,count);zipU32(view,12,centralSize);zipU32(view,16,centralOffset);zipU16(view,20,0);return out;}
async function* zipPieces(files,signal,onProgress){if(files.length>65535)throw new Error('This folder has too many files for one ZIP archive.');const entries=[];let offset=0,loaded=0,completed=0,total=files.reduce((sum,file)=>sum+(Number(file.size)||0),0);for(const file of files){if(signal.aborted)throw new DOMException('Folder download cancelled.','AbortError');const nameBytes=ZIP_ENCODER.encode(String(file.name||'file'));if(nameBytes.length>65535)throw new Error('A file path is too long for the ZIP archive.');const dos=zipDosDate(file.lastModified),localOffset=offset,header=zipLocalHeader(nameBytes,dos);if(localOffset>ZIP32_MAX)throw new Error('The ZIP archive is too large. Download a smaller folder.');yield header;offset+=header.length;let crc=0xffffffff,size=0;onProgress({loaded,total,completed,count:files.length,current:file.name});const response=await fetch(file.url,{signal,cache:'no-store',credentials:'omit'});if(!response.ok)throw new Error('Could not download “'+file.name+'” from storage.');if(response.body){const reader=response.body.getReader();try{while(true){const part=await reader.read();if(part.done)break;const chunk=part.value instanceof Uint8Array?part.value:new Uint8Array(part.value);crc=zipCrcUpdate(crc,chunk);size+=chunk.length;loaded+=chunk.length;offset+=chunk.length;if(size>ZIP32_MAX||offset>ZIP32_MAX)throw new Error('The ZIP archive is too large. Download a smaller folder.');yield chunk;onProgress({loaded,total,completed,count:files.length,current:file.name});}}finally{reader.releaseLock();}}else{const chunk=new Uint8Array(await response.arrayBuffer());crc=zipCrcUpdate(crc,chunk);size=chunk.length;loaded+=chunk.length;offset+=chunk.length;yield chunk;}crc=(crc^0xffffffff)>>>0;const descriptor=zipDataDescriptor(crc,size);yield descriptor;offset+=descriptor.length;entries.push({nameBytes,dos,crc,size,offset:localOffset});completed++;onProgress({loaded,total,completed,count:files.length,current:file.name});}const centralOffset=offset;for(const entry of entries){const header=zipCentralHeader(entry);yield header;offset+=header.length;if(offset>ZIP32_MAX)throw new Error('The ZIP archive is too large. Download a smaller folder.');}const centralSize=offset-centralOffset;yield zipEndRecord(entries.length,centralSize,centralOffset);}
function makeZipStream(files,signal,onProgress){const iterator=zipPieces(files,signal,onProgress);return new ReadableStream({async pull(controller){try{const result=await iterator.next();if(result.done)controller.close();else controller.enqueue(result.value);}catch(error){controller.error(error);}},async cancel(){if(iterator.return)await iterator.return();}});}
function safeZipName(name){let value=String(name||'folder.zip').replace(/[<>:"/\\|?*\x00-\x1f]/g,'_').replace(/[. ]+$/g,'').trim();if(!value)value='folder';return value.toLowerCase().endsWith('.zip')?value:value+'.zip';}
function resetFolderDownloadUi(folderName){folderDownloadTitle.textContent='Preparing '+(folderName||'folder');folderDownloadMessage.textContent='Creating a secure download list…';folderDownloadBar.style.width='0%';folderDownloadFiles.textContent='0 files';folderDownloadBytes.textContent='0 B';folderDownloadNote.hidden=true;folderDownloadNote.textContent='';folderDownloadCancel.style.display='inline-block';folderDownloadCancel.disabled=false;folderDownloadCancel.textContent='Cancel';folderDownloadDone.style.display='none';}
function updateFolderDownloadProgress(state){const total=Math.max(0,Number(state.total)||0),loaded=Math.max(0,Number(state.loaded)||0),count=Math.max(0,Number(state.count)||0),completed=Math.max(0,Number(state.completed)||0),percent=total>0?Math.min(98,Math.round((loaded/total)*98)):Math.min(98,Math.round((completed/Math.max(1,count))*98));folderDownloadBar.style.width=percent+'%';folderDownloadFiles.textContent=completed+' / '+count+' files';folderDownloadBytes.textContent=formatBytes(loaded)+' / '+formatBytes(total);folderDownloadMessage.textContent=state.current?'Adding '+state.current+' to the ZIP…':'Building ZIP archive…';}
async function chooseFolderSaveHandle(suggestedName){if(!window.isSecureContext||typeof window.showSaveFilePicker!=='function')return null;try{return await window.showSaveFilePicker({suggestedName:safeZipName(suggestedName),types:[{description:'ZIP archive',accept:{'application/zip':['.zip']}}]});}catch(error){if(error?.name==='AbortError')return false;throw error;}}
async function startFolderDownload(button){if(folderDownloadBusy)return;const path=button.dataset.folderPath||'',folderName=button.dataset.folderName||'folder';let saveHandle=null;try{saveHandle=await chooseFolderSaveHandle(folderName+'.zip');if(saveHandle===false)return;}catch(error){alert(error.message||'The save location could not be opened.');return;}folderDownloadBusy=true;folderDownloadAbort=new AbortController();resetFolderDownloadUi(folderName);openModal(folderDownloadModal);try{const manifest=await fetchJson(vaultConfig.urls.folderDownloadManifest,{method:'POST',body:JSON.stringify({path}),signal:folderDownloadAbort.signal});if(!Array.isArray(manifest.files)||manifest.files.length===0)throw new Error('This folder does not contain any downloadable files.');const archiveName=safeZipName(manifest.archiveName||folderName+'.zip');folderDownloadTitle.textContent='Downloading '+folderName;folderDownloadFiles.textContent='0 / '+manifest.fileCount+' files';folderDownloadBytes.textContent='0 B / '+formatBytes(manifest.totalBytes);const notes=[];if(Number(manifest.skippedCount)>0)notes.push(manifest.skippedCount+' unavailable file'+(Number(manifest.skippedCount)===1?' was':'s were')+' skipped.');if(!saveHandle&&Number(manifest.totalBytes)>250*1024*1024)notes.push('Your browser will temporarily keep this ZIP in memory before saving it.');if(notes.length){folderDownloadNote.textContent=notes.join(' ');folderDownloadNote.hidden=false;}const stream=makeZipStream(manifest.files,folderDownloadAbort.signal,updateFolderDownloadProgress);if(saveHandle){const writable=await saveHandle.createWritable();await stream.pipeTo(writable,{signal:folderDownloadAbort.signal});}else{const blob=await new Response(stream,{headers:{'Content-Type':'application/zip'}}).blob();if(folderDownloadAbort.signal.aborted)throw new DOMException('Folder download cancelled.','AbortError');const url=URL.createObjectURL(blob),link=document.createElement('a');link.href=url;link.download=archiveName;link.style.display='none';document.body.appendChild(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),60000);}folderDownloadBar.style.width='100%';folderDownloadFiles.textContent=manifest.fileCount+' / '+manifest.fileCount+' files';folderDownloadBytes.textContent=formatBytes(manifest.totalBytes);folderDownloadTitle.textContent='Folder downloaded';folderDownloadMessage.textContent=archiveName+' is ready.';folderDownloadCancel.style.display='none';folderDownloadDone.style.display='inline-block';try{await fetchJson(vaultConfig.urls.folderDownloadComplete,{method:'POST',body:JSON.stringify({downloadToken:manifest.downloadToken})});}catch(error){} }catch(error){const cancelled=folderDownloadAbort?.signal.aborted||error?.name==='AbortError';folderDownloadTitle.textContent=cancelled?'Download cancelled':'Folder download failed';folderDownloadMessage.textContent=cancelled?'No ZIP archive was saved.':(error.message||'The folder could not be downloaded.');folderDownloadBar.style.width='0%';folderDownloadCancel.style.display='none';folderDownloadDone.style.display='inline-block';}finally{folderDownloadBusy=false;folderDownloadAbort=null;}}
document.querySelectorAll('.js-download-folder').forEach(button=>button.addEventListener('click',event=>{event.preventDefault();event.stopPropagation();startFolderDownload(button);}));
folderDownloadCancel.addEventListener('click',()=>{if(folderDownloadBusy&&folderDownloadAbort){folderDownloadCancel.disabled=true;folderDownloadCancel.textContent='Cancelling…';folderDownloadMessage.textContent='Stopping the folder download…';folderDownloadAbort.abort();}else closeModal(folderDownloadModal);});
folderDownloadDone.addEventListener('click',()=>closeModal(folderDownloadModal));
document.addEventListener('toggle',e=>{const menu=e.target;if(!(menu instanceof HTMLDetailsElement)||!menu.classList.contains('action-menu'))return;const item=menu.closest('.file-item');if(menu.open){closeActionMenus(menu);item?.classList.add('menu-open');menu.classList.remove('open-up');requestAnimationFrame(()=>{const panel=menu.querySelector('.action-menu-panel');if(panel&&panel.getBoundingClientRect().bottom>window.innerHeight-12)menu.classList.add('open-up');});}else{item?.classList.remove('menu-open');menu.classList.remove('open-up');}},true);
document.addEventListener('click',e=>{const close=e.target.closest('[data-close-modal]');if(close){closeModal(close.closest('.modal'));return;}const preview=e.target.closest('.js-preview');if(preview){e.preventDefault();e.stopPropagation();openFile(parseFileData(preview));return;}const share=e.target.closest('.js-share,.js-share-folder');if(share){e.preventDefault();e.stopPropagation();openShareModal(share);return;}const edit=e.target.closest('.js-edit');if(edit){e.stopPropagation();const f=JSON.parse(edit.dataset.file),form=document.getElementById('editForm');form.action=vaultConfig.urls.filesBase+'/'+f.id+'/update';document.getElementById('editTitle').value=f.title;document.getElementById('editDescription').value=f.description;document.getElementById('editCategory').value=f.category;document.getElementById('editFolderPath').value=f.folder_path||'';document.getElementById('editDocumentDate').value=f.document_date;openModal(document.getElementById('editModal'));return;}const del=e.target.closest('.js-delete');if(del){e.stopPropagation();document.getElementById('deleteForm').action=del.dataset.deleteUrl;document.getElementById('deleteMessage').textContent='“'+del.dataset.deleteTitle+'” will stay recoverable for 30 days.';openModal(document.getElementById('deleteModal'));return;}const item=e.target.closest('.file-item.js-open-file');if(item&&!e.target.closest('.action-menu')&&!e.target.closest('a,button,form')){openFile(parseFileData(item));return;}if(e.target.classList.contains('modal')&&!e.target.dataset.static){closeModal(e.target);return;}if(!e.target.closest('.action-menu'))closeActionMenus();});
document.addEventListener('keydown',e=>{if(e.key==='Escape'){if(folderDownloadModal.classList.contains('open')&&folderDownloadBusy&&folderDownloadAbort){folderDownloadCancel.click();return;}const openModals=[...document.querySelectorAll('.modal.open')].filter(modal=>!modal.dataset.static);if(openModals.length)openModals.forEach(closeModal);else if(folderDownloadModal.classList.contains('open'))closeModal(folderDownloadModal);else closeActionMenus();return;}const item=e.target.closest?.('.file-item.js-open-file');if(item&&(e.key==='Enter'||e.key===' ')){e.preventDefault();openFile(parseFileData(item));return;}if(previewModal.classList.contains('open')&&e.key==='ArrowLeft'&&!previewPrev.disabled)previewPrev.click();if(previewModal.classList.contains('open')&&e.key==='ArrowRight'&&!previewNext.disabled)previewNext.click();});
