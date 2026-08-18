<!DOCTYPE html>
<html lang="en">
<head>
<title>Important Files · Damon's Archive</title>
<?= view('partials/theme_head') ?>
<style>
  .vault-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin:18px 0 22px}
  .toolbar-links{display:flex;gap:8px;flex-wrap:wrap}.toolbar-link{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text-dim);text-decoration:none;border:1px solid var(--hairline);padding:8px 10px;border-radius:6px;background:var(--surface)}.toolbar-link:hover{color:var(--cyan);border-color:var(--cyan)}
  .lock-form{margin:0}.lock-link{background:transparent;color:var(--text-dim);font-family:'JetBrains Mono',monospace;font-size:11px;padding:8px 10px}.lock-link:hover{color:var(--red)}
  .summary-strip{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px}.summary-card{background:var(--surface);border:1px solid var(--hairline);border-radius:8px;padding:14px}.summary-label{font-size:10px;text-transform:uppercase;letter-spacing:.1em;color:var(--text-dim)}.summary-value{font-family:'Cormorant Garamond',serif;font-size:25px;margin-top:3px;color:var(--text)}
  .filters{display:grid;grid-template-columns:2fr repeat(4,1fr);gap:10px;align-items:end;margin-bottom:14px}.filters label{margin:0 0 5px}.filters select{width:100%;background:var(--surface-2);border:1px solid var(--hairline);border-radius:6px;padding:10px;color:var(--text)}.clear-filter{display:flex;align-items:center;justify-content:center;text-decoration:none;color:var(--text-dim);height:40px;border:1px solid var(--hairline);border-radius:6px;font-size:12px}.clear-filter:hover{color:var(--cyan)}
  .layout{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(310px,.75fr);gap:22px}.panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}.panel-head h2{margin:0}.result-count{font-size:11px;color:var(--text-dim);font-family:'JetBrains Mono',monospace}
  .file-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:9px;position:relative;isolation:isolate}.file-item{display:flex;align-items:center;gap:12px;background:var(--surface-2);border:1px solid var(--hairline);border-radius:8px;padding:11px 12px;position:relative;z-index:0;animation:fadeInUp .35s ease both}.file-item:hover{border-color:#344573}.file-item.menu-open{z-index:80;border-color:#344573}.file-type-badge{flex:none;width:46px;height:38px;border-radius:6px;background:var(--surface);border:1px solid var(--hairline);display:flex;align-items:center;justify-content:center;font-family:'JetBrains Mono',monospace;font-size:9px;font-weight:700;color:var(--cyan)}.file-meta{flex:1;min-width:0}.file-title-row{display:flex;gap:7px;align-items:center}.file-title{font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.favorite-mark{color:var(--gold);font-size:12px}.original-name{font-family:'JetBrains Mono',monospace;font-size:10px;color:#677394;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px}.file-desc{font-size:12px;color:var(--text-dim);margin-top:4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.file-sub{display:flex;align-items:center;gap:7px;margin-top:5px;flex-wrap:wrap}.file-sub-text{font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text-dim)}.category-tag,.expiry-tag{font-size:9px;text-transform:uppercase;letter-spacing:.06em;padding:2px 7px;border-radius:20px;border:1px solid var(--hairline);color:var(--text-dim)}.expiry-tag.soon{color:var(--gold);border-color:rgba(242,195,107,.45)}.expiry-tag.expired{color:#ff9aa1;border-color:rgba(229,99,107,.55)}.expiry-tag.valid{color:#b7d8c7;border-color:rgba(120,200,160,.35)}
  .action-menu{position:relative;z-index:1;flex:none}.action-menu[open]{z-index:90}.action-menu summary{list-style:none;width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:6px;color:var(--text-dim);cursor:pointer;font-size:22px}.action-menu summary::-webkit-details-marker{display:none}.action-menu[open] summary,.action-menu summary:hover{background:var(--surface);color:var(--cyan)}.action-menu-panel{position:absolute;right:0;top:38px;z-index:100;width:190px;max-height:min(60vh,360px);overflow-y:auto;background:#10172b;border:1px solid var(--hairline);border-radius:8px;padding:6px;box-shadow:0 15px 35px rgba(0,0,0,.45)}.action-menu.open-up .action-menu-panel{top:auto;bottom:38px}.action-menu-panel form{margin:0}.menu-action{display:block;width:100%;text-align:left;background:transparent;color:var(--text);text-decoration:none;font-size:12px;font-weight:500;padding:9px 10px;border-radius:5px;margin:0}.menu-action:hover{background:var(--surface-2);color:var(--cyan)}.menu-action.danger:hover{color:#ff9aa1;background:rgba(229,99,107,.09)}
  .empty{border:1px dashed var(--hairline);border-radius:8px;padding:28px;text-align:center;color:var(--text-dim);font-size:13px}.pagination{display:flex;gap:6px;list-style:none;padding:0;margin:18px 0 0;flex-wrap:wrap}.pagination a,.pagination span{display:block;padding:7px 10px;border:1px solid var(--hairline);border-radius:5px;color:var(--text-dim);text-decoration:none;font-size:12px}.pagination .active a,.pagination a:hover{color:var(--cyan);border-color:var(--cyan)}
  select,input[type="number"],input[type="password"]{width:100%;background:var(--surface-2);border:1px solid var(--hairline);border-radius:6px;padding:10px 12px;color:var(--text);font-size:14px;font-family:inherit}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 10px}.dropzone{margin-top:6px;border:1.5px dashed var(--hairline);border-radius:8px;padding:20px 14px;text-align:center;background:var(--surface-2);transition:.15s}.dropzone.drag{border-color:var(--cyan);background:rgba(95,217,232,.08);transform:scale(1.01)}.dropzone p{margin:0 0 8px;font-size:13px;color:var(--text-dim)}.dropzone input{width:100%;font-size:12px;color:var(--text-dim)}.file-hint{font-size:11px;color:var(--text-dim);line-height:1.5}.selected-files{display:flex;flex-direction:column;gap:5px;margin:9px 0}.selected-file{display:flex;justify-content:space-between;gap:10px;font-size:11px;background:var(--surface-2);border:1px solid var(--hairline);padding:7px 9px;border-radius:5px}.progress-wrap{height:7px;background:var(--surface-2);border-radius:5px;overflow:hidden;margin:10px 0}.progress-bar{height:100%;width:0;background:linear-gradient(90deg,var(--cyan),var(--violet));transition:width .15s}.upload-actions{display:flex;gap:8px}.upload-actions .btn-primary{margin-top:12px}.btn-secondary{margin-top:12px;background:var(--surface-2);border:1px solid var(--hairline);color:var(--text-dim)}.btn-secondary:hover{color:var(--text);border-color:var(--cyan)}
  .modal{position:fixed;inset:0;z-index:100;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(2,4,10,.82);backdrop-filter:blur(5px)}.modal.open{display:flex}.modal-card{width:min(620px,100%);max-height:92vh;overflow:auto;background:var(--surface);border:1px solid var(--hairline);border-radius:12px;padding:20px;box-shadow:0 24px 70px rgba(0,0,0,.6)}.modal-card.wide{width:min(980px,100%)}.modal-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px}.modal-head h2{margin:0}.modal-close{background:transparent;color:var(--text-dim);font-size:22px;padding:4px 8px}.preview-shell{position:relative;width:100%;height:min(72vh,760px);min-height:360px;border:1px solid var(--hairline);border-radius:8px;overflow:hidden;background:#fff}.preview-frame{display:block;width:100%;height:100%;border:0;background:#fff}.preview-state{position:absolute;inset:0;z-index:2;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:24px;text-align:center;background:#f8f9fd;color:#26324e;font-size:13px}.preview-state[hidden]{display:none}.preview-spinner{width:30px;height:30px;border:3px solid #d8deeb;border-top-color:#516ba8;border-radius:50%;animation:spinSlow .8s linear infinite}.preview-tools{display:flex;align-items:center;justify-content:flex-end;gap:9px;margin-top:10px}.preview-open{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--cyan);text-decoration:none;border:1px solid var(--hairline);padding:7px 10px;border-radius:6px}.preview-open:hover{border-color:var(--cyan)}.modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:16px}.modal-actions button{width:auto;margin:0}.danger-button{background:var(--red);color:white}.danger-button:hover{filter:brightness(1.08)}
  @media(max-width:980px){.filters{grid-template-columns:1fr 1fr 1fr}.layout{grid-template-columns:1fr}.summary-strip{grid-template-columns:repeat(3,1fr)}}
  @media(max-width:620px){.summary-strip{grid-template-columns:1fr}.filters{grid-template-columns:1fr}.form-grid{grid-template-columns:1fr}.file-item{align-items:flex-start}.file-type-badge{width:40px;height:34px}.action-menu-panel,.action-menu.open-up .action-menu-panel{position:fixed;left:16px;right:16px;top:auto;bottom:16px;width:auto;max-height:70vh}.toolbar-links{width:100%}.toolbar-link{flex:1;text-align:center}.modal{padding:10px}.preview-shell{height:70vh;min-height:300px}}
</style>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<div class="wrap">
  <a href="<?= base_url('/') ?>" class="nav-back">&larr; Home</a>
  <header><p class="eyebrow">Restricted Archive · Sector 04</p><h1>Important Files</h1><div class="starline"></div></header>

  <?php if (session()->getFlashdata('error')): ?><div class="flash error" role="alert"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
  <?php if (session()->getFlashdata('success')): ?><div class="flash success" role="status"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>

  <div class="vault-toolbar">
    <div class="toolbar-links"><a class="toolbar-link" href="<?= base_url('files') ?>">All files</a><a class="toolbar-link" href="<?= base_url('files') . '?favorite=1' ?>">Favorites</a><a class="toolbar-link" href="<?= base_url('files/recycle') ?>">Recycle Bin</a><a class="toolbar-link" href="<?= base_url('files/activity') ?>">Activity Log</a></div>
    <form class="lock-form" action="<?= base_url('files/lock') ?>" method="post"><?= csrf_field() ?><button class="lock-link" type="submit">Lock vault</button></form>
  </div>

  <div class="summary-strip">
    <div class="summary-card"><div class="summary-label">Active files</div><div class="summary-value" id="summaryCount"><?= (int) $summary['file_count'] ?></div></div>
    <div class="summary-card"><div class="summary-label">Storage used</div><div class="summary-value" id="summaryBytes" data-bytes="<?= (int) $summary['total_bytes'] ?>"><?= \App\Models\ImportantFileModel::formatBytes((int) $summary['total_bytes']) ?></div></div>
    <div class="summary-card"><div class="summary-label">Upload limit</div><div class="summary-value"><?= (int) $maxMb ?> MB</div></div>
  </div>

  <form class="filters panel" id="filterForm" method="get" action="<?= base_url('files') ?>">
    <div><label for="filterQ">Search</label><input id="filterQ" type="search" name="q" value="<?= esc($filters['q'], 'attr') ?>" placeholder="Title, filename, category…"></div>
    <div><label for="filterCategory">Category</label><select id="filterCategory" name="category"><option value="">All</option><?php foreach ($categories as $category): ?><option value="<?= esc($category, 'attr') ?>" <?= $filters['category'] === $category ? 'selected' : '' ?>><?= esc($category) ?></option><?php endforeach; ?></select></div>
    <div><label for="filterType">Type</label><select id="filterType" name="type"><option value="">All</option><?php foreach ($extensions as $extension): ?><option value="<?= esc($extension, 'attr') ?>" <?= $filters['type'] === $extension ? 'selected' : '' ?>><?= esc(strtoupper($extension)) ?></option><?php endforeach; ?></select></div>
    <div><label for="filterExpiry">Expiration</label><select id="filterExpiry" name="expiry"><option value="">All</option><option value="soon" <?= $filters['expiry']==='soon'?'selected':'' ?>>Next 30 days</option><option value="expired" <?= $filters['expiry']==='expired'?'selected':'' ?>>Expired</option><option value="none" <?= $filters['expiry']==='none'?'selected':'' ?>>No expiration</option></select></div>
    <div><label for="filterSort">Sort</label><select id="filterSort" name="sort"><option value="newest" <?= $filters['sort']==='newest'?'selected':'' ?>>Newest</option><option value="oldest" <?= $filters['sort']==='oldest'?'selected':'' ?>>Oldest</option><option value="name_asc" <?= $filters['sort']==='name_asc'?'selected':'' ?>>Name A–Z</option><option value="name_desc" <?= $filters['sort']==='name_desc'?'selected':'' ?>>Name Z–A</option><option value="size_desc" <?= $filters['sort']==='size_desc'?'selected':'' ?>>Largest</option><option value="size_asc" <?= $filters['sort']==='size_asc'?'selected':'' ?>>Smallest</option><option value="expires" <?= $filters['sort']==='expires'?'selected':'' ?>>Expiration</option></select></div>
    <input type="hidden" name="favorite" value="<?= esc($filters['favorite'], 'attr') ?>">
  </form>

  <div class="layout">
    <section class="panel">
      <div class="panel-head"><h2>Your vault</h2><span class="result-count"><?= count($files) ?> shown</span></div>
      <div id="emptyState" class="empty" <?= $files !== [] ? 'style="display:none"' : '' ?>>No files match the current filters.</div>
      <ul class="file-list" id="fileList"><?php foreach ($files as $f): ?><?= view('files/_file_card', ['f' => $f]) ?><?php endforeach; ?></ul>
      <?= $pager->links('files') ?>
    </section>

    <aside class="panel">
      <h2>Add to Vault</h2>
      <form id="uploadForm">
        <label for="title">Title</label><input type="text" id="title" maxlength="255" placeholder="Used for one file; multiple files use their filenames">
        <label for="description">Description (optional)</label><textarea id="description" rows="2" maxlength="5000"></textarea>
        <label for="category">Category (optional)</label><input type="text" id="category" maxlength="100" placeholder="ID, Certificate, Tax…">
        <div class="form-grid"><div><label for="documentDate">Document date</label><input type="date" id="documentDate"></div><div><label for="expiresAt">Expiration date</label><input type="date" id="expiresAt"></div></div>
        <label for="reminderDays">Remind before expiration</label><select id="reminderDays"><option value="7">7 days</option><option value="14">14 days</option><option value="30" selected>30 days</option><option value="60">60 days</option><option value="90">90 days</option></select>
        <label for="fileInput">Files</label>
        <div class="dropzone" id="dropzone"><p>Drop one or more files here</p><input type="file" id="fileInput" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.png,.jpg,.jpeg,.webp,.gif,.heic" required></div>
        <p class="file-hint">Maximum <?= (int) $maxMb ?> MB per file. Files are checked by extension, content type, size, and file signature before activation.</p>
        <div class="selected-files" id="selectedFiles"></div>
        <div id="uploadStatus" class="file-hint" style="display:none"></div>
        <div id="progressWrap" class="progress-wrap" style="display:none"><div id="progressBar" class="progress-bar"></div></div>
        <div class="upload-actions"><button type="submit" class="btn-primary" id="uploadBtn">Add to vault</button><button type="button" class="btn-secondary" id="cancelBtn" style="display:none">Cancel</button></div>
      </form>
    </aside>
  </div>
</div>

<div class="modal" id="previewModal" aria-hidden="true"><div class="modal-card wide"><div class="modal-head"><h2 id="previewTitle">Preview</h2><button class="modal-close" type="button" data-close-modal>&times;</button></div><div class="preview-shell"><div class="preview-state" id="previewLoading"><span class="preview-spinner" aria-hidden="true"></span><span>Loading secure preview…</span></div><iframe class="preview-frame" id="previewFrame" title="File preview"></iframe></div><div class="preview-tools"><a class="preview-open" id="previewOpenLink" href="#" target="_blank" rel="noopener">Open in new tab</a></div></div></div>
<div class="modal" id="editModal" aria-hidden="true"><div class="modal-card"><div class="modal-head"><h2>Edit file details</h2><button class="modal-close" type="button" data-close-modal>&times;</button></div><form id="editForm" method="post"><?= csrf_field() ?><label for="editTitle">Title</label><input id="editTitle" name="title" type="text" maxlength="255" required><label for="editDescription">Description</label><textarea id="editDescription" name="description" rows="3" maxlength="5000"></textarea><label for="editCategory">Category</label><input id="editCategory" name="category" type="text" maxlength="100"><div class="form-grid"><div><label for="editDocumentDate">Document date</label><input id="editDocumentDate" name="document_date" type="date"></div><div><label for="editExpiresAt">Expiration date</label><input id="editExpiresAt" name="expires_at" type="date"></div></div><label for="editReminderDays">Reminder days</label><input id="editReminderDays" name="reminder_days" type="number" min="0" max="3650" value="30"><div class="modal-actions"><button type="button" class="btn-secondary" data-close-modal>Cancel</button><button type="submit" class="btn-primary">Save changes</button></div></form></div></div>
<div class="modal" id="deleteModal" aria-hidden="true"><div class="modal-card"><div class="modal-head"><h2>Move to Recycle Bin?</h2><button class="modal-close" type="button" data-close-modal>&times;</button></div><p id="deleteMessage" class="file-hint"></p><form id="deleteForm" method="post"><?= csrf_field() ?><div class="modal-actions"><button type="button" class="btn-secondary" data-close-modal>Cancel</button><button type="submit" class="danger-button">Move file</button></div></form></div></div>

<?= view('partials/theme_scripts') ?>
<script>
const CSRF_HEADER = <?= json_encode(csrf_header()) ?>;
const CSRF_HASH = <?= json_encode(csrf_hash()) ?>;
const MAX_BYTES = <?= (int) $maxBytes ?>;
const uploadForm = document.getElementById('uploadForm');
const fileInput = document.getElementById('fileInput');
const dropzone = document.getElementById('dropzone');
const selectedFiles = document.getElementById('selectedFiles');
const uploadStatus = document.getElementById('uploadStatus');
const progressWrap = document.getElementById('progressWrap');
const progressBar = document.getElementById('progressBar');
const uploadBtn = document.getElementById('uploadBtn');
const cancelBtn = document.getElementById('cancelBtn');
let currentXhr = null, currentToken = null, cancelled = false;

function formatBytes(bytes){const u=['B','KB','MB','GB'];let i=0,n=Number(bytes)||0;while(n>=1024&&i<u.length-1){n/=1024;i++;}return (i? n.toFixed(1):Math.round(n))+' '+u[i];}
function baseTitle(name){return name.replace(/\.[^.]+$/,'').replace(/[_-]+/g,' ').trim() || name;}
function setStatus(message,error=false){uploadStatus.style.display='block';uploadStatus.textContent=message;uploadStatus.style.color=error?'var(--red)':'var(--text-dim)';}
function refreshSelected(){selectedFiles.innerHTML='';[...fileInput.files].forEach(file=>{const row=document.createElement('div');row.className='selected-file';row.innerHTML='<span></span><strong></strong>';row.querySelector('span').textContent=file.name;row.querySelector('strong').textContent=formatBytes(file.size);selectedFiles.appendChild(row);});}
fileInput.addEventListener('change',refreshSelected);
['dragenter','dragover'].forEach(evt=>dropzone.addEventListener(evt,e=>{e.preventDefault();dropzone.classList.add('drag');}));
['dragleave','drop'].forEach(evt=>dropzone.addEventListener(evt,e=>{e.preventDefault();dropzone.classList.remove('drag');}));
dropzone.addEventListener('drop',e=>{if(e.dataTransfer.files.length){fileInput.files=e.dataTransfer.files;refreshSelected();}});

async function fetchJson(url, options={}){options.headers=Object.assign({'Content-Type':'application/json',[CSRF_HEADER]:CSRF_HASH},options.headers||{});const res=await fetch(url,options);const text=await res.text();let data={};try{data=JSON.parse(text);}catch(e){throw new Error(res.status===403?'Your security token expired. Refresh the page and try again.':'The server returned an unexpected response.');}if(!res.ok)throw new Error(data.error||'Request failed.');return data;}
async function sha256(file){if(!window.crypto?.subtle)return '';const buffer=await file.arrayBuffer();const digest=await crypto.subtle.digest('SHA-256',buffer);return [...new Uint8Array(digest)].map(b=>b.toString(16).padStart(2,'0')).join('');}
function putWithProgress(url,file,index,total){return new Promise((resolve,reject)=>{const xhr=new XMLHttpRequest();currentXhr=xhr;xhr.open('PUT',url);xhr.setRequestHeader('Content-Type',file.type||'application/octet-stream');xhr.upload.onprogress=e=>{if(e.lengthComputable){const current=Math.round(e.loaded/e.total*100);const overall=Math.round(((index+current/100)/total)*100);progressBar.style.width=overall+'%';setStatus('Uploading '+file.name+' — '+current+'%');}};xhr.onload=()=>xhr.status>=200&&xhr.status<300?resolve():reject(new Error('Storage upload failed with status '+xhr.status+'.'));xhr.onerror=()=>reject(new Error('Storage upload failed. Check your connection.'));xhr.onabort=()=>reject(new Error('Upload cancelled.'));xhr.send(file);});}
async function cancelPending(){cancelled=true;if(currentXhr)currentXhr.abort();if(currentToken){try{await fetchJson(<?= json_encode(base_url('files/cancel-upload')) ?>,{method:'POST',body:JSON.stringify({uploadToken:currentToken})});}catch(e){}}currentToken=null;uploadBtn.disabled=false;uploadBtn.textContent='Add to vault';cancelBtn.style.display='none';setStatus('Upload cancelled.',true);}
cancelBtn.addEventListener('click',cancelPending);

uploadForm.addEventListener('submit',async e=>{
  e.preventDefault(); const files=[...fileInput.files]; if(!files.length){setStatus('Choose at least one file.',true);return;}
  const tooLarge=files.find(f=>f.size<=0||f.size>MAX_BYTES);if(tooLarge){setStatus(tooLarge.name+' exceeds the allowed size.',true);return;}
  cancelled=false;uploadBtn.disabled=true;uploadBtn.textContent='Uploading…';cancelBtn.style.display='inline-block';progressWrap.style.display='block';progressBar.style.width='0%';
  const sharedTitle=document.getElementById('title').value.trim();let completed=0;
  try{
    for(let i=0;i<files.length;i++){
      if(cancelled)throw new Error('Upload cancelled.');const file=files[i];setStatus('Checking '+file.name+'…');
      const checksum=await sha256(file);
      const title=files.length===1&&sharedTitle?sharedTitle:baseTitle(file.name);
      const sign=await fetchJson(<?= json_encode(base_url('files/sign-upload')) ?>,{method:'POST',body:JSON.stringify({title,description:document.getElementById('description').value.trim(),category:document.getElementById('category').value.trim(),documentDate:document.getElementById('documentDate').value,expiresAt:document.getElementById('expiresAt').value,reminderDays:document.getElementById('reminderDays').value,filename:file.name,mimetype:file.type,filesize:file.size,checksum})});
      currentToken=sign.uploadToken;await putWithProgress(sign.uploadUrl,file,i,files.length);
      const saved=await fetchJson(<?= json_encode(base_url('files/store')) ?>,{method:'POST',body:JSON.stringify({uploadToken:currentToken})});
      currentToken=null;document.getElementById('emptyState').style.display='none';document.getElementById('fileList').insertAdjacentHTML('afterbegin',saved.cardHtml);completed++;
      const count=document.getElementById('summaryCount');count.textContent=Number(count.textContent||0)+1;const bytes=document.getElementById('summaryBytes');const total=Number(bytes.dataset.bytes||0)+Number(saved.fileSize||0);bytes.dataset.bytes=total;bytes.textContent=formatBytes(total);
    }
    progressBar.style.width='100%';setStatus(completed+' file'+(completed===1?'':'s')+' added successfully.');uploadForm.reset();selectedFiles.innerHTML='';uploadBtn.textContent='Add to vault';
  }catch(err){
    if(currentToken){try{await fetchJson(<?= json_encode(base_url('files/cancel-upload')) ?>,{method:'POST',body:JSON.stringify({uploadToken:currentToken})});}catch(cleanupError){}currentToken=null;}
    uploadBtn.textContent='Retry upload';setStatus(err.message||'Upload failed.',true);
  }
  finally{currentXhr=null;uploadBtn.disabled=false;cancelBtn.style.display='none';}
});

const filterForm=document.getElementById('filterForm');let searchTimer;filterForm.querySelectorAll('select').forEach(el=>el.addEventListener('change',()=>filterForm.submit()));document.getElementById('filterQ').addEventListener('input',()=>{clearTimeout(searchTimer);searchTimer=setTimeout(()=>filterForm.submit(),500);});
const previewFrame=document.getElementById('previewFrame');
const previewLoading=document.getElementById('previewLoading');
const previewOpenLink=document.getElementById('previewOpenLink');
function closeActionMenus(except=null){document.querySelectorAll('.action-menu[open]').forEach(menu=>{if(menu!==except)menu.open=false;});}
function openModal(modal){closeActionMenus();modal.classList.add('open');modal.setAttribute('aria-hidden','false');document.body.style.overflow='hidden';}
function closeModal(modal){modal.classList.remove('open');modal.setAttribute('aria-hidden','true');if(!document.querySelector('.modal.open'))document.body.style.overflow='';if(modal.id==='previewModal'){previewFrame.src='about:blank';previewOpenLink.href='#';previewLoading.hidden=false;}}
previewFrame.addEventListener('load',()=>{if(previewFrame.src!=='about:blank')previewLoading.hidden=true;});
document.addEventListener('toggle',e=>{const menu=e.target;if(!(menu instanceof HTMLDetailsElement)||!menu.classList.contains('action-menu'))return;const item=menu.closest('.file-item');if(menu.open){closeActionMenus(menu);item?.classList.add('menu-open');menu.classList.remove('open-up');requestAnimationFrame(()=>{const panel=menu.querySelector('.action-menu-panel');if(panel&&panel.getBoundingClientRect().bottom>window.innerHeight-12)menu.classList.add('open-up');});}else{item?.classList.remove('menu-open');menu.classList.remove('open-up');}},true);
document.addEventListener('click',e=>{const close=e.target.closest('[data-close-modal]');if(close){closeModal(close.closest('.modal'));return;}const preview=e.target.closest('.js-preview');if(preview){document.getElementById('previewTitle').textContent=preview.dataset.previewTitle;previewLoading.hidden=false;previewOpenLink.href=preview.dataset.previewUrl;previewFrame.src=preview.dataset.previewUrl;openModal(document.getElementById('previewModal'));return;}const edit=e.target.closest('.js-edit');if(edit){const f=JSON.parse(edit.dataset.file);const form=document.getElementById('editForm');form.action=<?= json_encode(base_url('files')) ?>+'/'+f.id+'/update';document.getElementById('editTitle').value=f.title;document.getElementById('editDescription').value=f.description;document.getElementById('editCategory').value=f.category;document.getElementById('editDocumentDate').value=f.document_date;document.getElementById('editExpiresAt').value=f.expires_at;document.getElementById('editReminderDays').value=f.reminder_days;openModal(document.getElementById('editModal'));return;}const del=e.target.closest('.js-delete');if(del){document.getElementById('deleteForm').action=del.dataset.deleteUrl;document.getElementById('deleteMessage').textContent='“'+del.dataset.deleteTitle+'” will stay recoverable for 30 days.';openModal(document.getElementById('deleteModal'));return;}if(e.target.classList.contains('modal')){closeModal(e.target);return;}if(!e.target.closest('.action-menu'))closeActionMenus();});
document.addEventListener('keydown',e=>{if(e.key==='Escape'){const openModals=document.querySelectorAll('.modal.open');if(openModals.length)openModals.forEach(closeModal);else closeActionMenus();}});
</script>
</body>
</html>
