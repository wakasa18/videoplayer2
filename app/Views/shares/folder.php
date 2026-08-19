<?php
use App\Models\ImportantFileModel;

$folderUrl = static function (string $path) use ($token): string {
    $base = base_url('share/' . $token);
    return $path === '' ? $base : $base . '?path=' . rawurlencode($path);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="referrer" content="no-referrer">
<title><?= esc($currentName) ?> · Shared Folder</title>
<?= view('partials/theme_head') ?>
<style>
.shared-folder{max-width:1160px;margin:0 auto;padding:30px 20px 56px}.share-hero{position:relative;overflow:hidden;padding:22px;border:1px solid var(--hairline);border-radius:14px;background:linear-gradient(145deg,rgba(95,217,232,.08),rgba(126,99,214,.05) 55%,var(--surface));box-shadow:0 18px 55px rgba(0,0,0,.15)}.share-hero:after{content:"";position:absolute;width:240px;height:240px;border-radius:50%;right:-100px;top:-130px;background:rgba(95,217,232,.08);filter:blur(3px);pointer-events:none}.folder-head{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:18px}.folder-title-wrap{display:flex;align-items:center;gap:15px;min-width:0}.hero-folder-icon{flex:none;width:54px;height:54px;display:grid;place-items:center;border:1px solid rgba(242,195,107,.28);border-radius:12px;background:rgba(242,195,107,.08);font-size:27px}.folder-title-copy{min-width:0}.folder-title-wrap h1{font-size:36px;margin:4px 0 3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.folder-sub{display:flex;gap:7px;align-items:center;flex-wrap:wrap;font:10px 'JetBrains Mono',monospace;color:var(--text-dim)}.security-dot{width:6px;height:6px;border-radius:50%;background:#75d8aa;box-shadow:0 0 0 4px rgba(117,216,170,.1)}.folder-actions{display:flex;gap:8px;flex:none}.share-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:10px 15px;border-radius:8px;border:1px solid var(--hairline);background:var(--surface);color:var(--text-dim);font-size:12px;font-weight:700;text-decoration:none}.share-btn:hover{border-color:var(--cyan);color:var(--cyan)}.share-btn.primary{border-color:var(--cyan);background:var(--cyan);color:#061019}.share-btn:disabled{opacity:.65;cursor:not-allowed}.hero-meta{position:relative;z-index:1;display:flex;gap:9px;flex-wrap:wrap;margin-top:17px;padding-top:15px;border-top:1px solid var(--hairline)}.hero-meta span{padding:7px 10px;border:1px solid var(--hairline);border-radius:20px;background:rgba(8,13,25,.25);font:10px 'JetBrains Mono',monospace;color:var(--text-dim)}.crumbs{display:flex;gap:5px;align-items:center;overflow:auto;margin:15px 0 14px;padding:4px 0;scrollbar-width:thin}.crumb{flex:none;color:var(--text-dim);text-decoration:none;font-size:12px;padding:6px 8px;border-radius:5px}.crumb:hover,.crumb.current{color:var(--cyan);background:var(--surface-2)}.sep{color:#4f5a77}.download-status{display:none;margin:0 0 16px;padding:14px;border:1px solid var(--hairline);border-radius:10px;background:var(--surface);box-shadow:0 12px 35px rgba(0,0,0,.12)}.download-status.show{display:block}.download-status-head{display:flex;align-items:center;justify-content:space-between;gap:12px}.download-status-title{min-width:0}.download-status-title strong{display:block;font-size:13px}.download-status-title span{display:block;margin-top:3px;font:9px 'JetBrains Mono',monospace;color:var(--text-dim);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.download-cancel{flex:none;width:auto;padding:7px 10px;border:1px solid var(--hairline);border-radius:6px;background:var(--surface-2);color:var(--text-dim);font-size:11px}.download-cancel:hover{color:#ff9aa1;border-color:rgba(229,99,107,.55)}.progress-track{height:7px;border-radius:20px;background:var(--surface-2);overflow:hidden;margin:11px 0 8px}.progress-bar{height:100%;width:0;background:linear-gradient(90deg,var(--cyan),var(--violet));transition:width .12s}.download-copy{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;font:10px 'JetBrains Mono',monospace;color:var(--text-dim)}.section{margin-top:18px}.section-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:0 0 9px}.section-label{margin:0;font:10px 'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.12em;color:var(--text-dim)}.section-count{font:9px 'JetBrains Mono',monospace;color:#65718f}.folder-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px}.folder-card{display:flex;align-items:center;gap:11px;padding:13px;border:1px solid var(--hairline);border-radius:9px;background:var(--surface-2);color:var(--text);text-decoration:none;min-width:0;transition:.15s}.folder-card:hover{border-color:var(--cyan);transform:translateY(-1px);box-shadow:0 12px 25px rgba(0,0,0,.12)}.folder-icon{font-size:24px}.folder-copy{min-width:0}.folder-name{display:block;font-size:13px;font-weight:650;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.folder-count{display:block;margin-top:3px;font:9px 'JetBrains Mono',monospace;color:var(--text-dim)}.folder-arrow{margin-left:auto;color:#56617c}.file-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px}.file-row{display:grid;grid-template-columns:48px minmax(0,1fr) auto;gap:12px;align-items:center;padding:11px 12px;border:1px solid var(--hairline);border-radius:9px;background:var(--surface-2);transition:.15s}.file-row:hover{border-color:#536b9f;transform:translateY(-1px)}.file-badge{width:46px;height:40px;display:grid;place-items:center;border:1px solid var(--hairline);border-radius:7px;background:var(--surface);font:700 9px 'JetBrains Mono',monospace;color:var(--cyan)}.file-copy{min-width:0}.file-title{font-size:14px;font-weight:650;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.file-name{margin-top:2px;font:10px 'JetBrains Mono',monospace;color:#7581a4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.file-meta{margin-top:5px;font:9px 'JetBrains Mono',monospace;color:var(--text-dim)}.file-actions{display:flex;gap:6px}.file-actions a,.file-actions button{padding:8px 10px;border:1px solid var(--hairline);border-radius:6px;color:var(--text-dim);text-decoration:none;font-size:11px;font-weight:650;background:transparent;font-family:inherit;line-height:1.2}.file-actions button{color:var(--cyan)}.file-actions a:hover,.file-actions button:hover{border-color:var(--cyan);color:var(--cyan)}.empty{padding:34px;border:1px dashed var(--hairline);border-radius:10px;text-align:center;color:var(--text-dim)}.share-note{display:flex;gap:10px;align-items:flex-start;margin-top:24px;padding:13px;border:1px solid rgba(95,217,232,.25);border-radius:9px;background:rgba(95,217,232,.06);font-size:11px;color:var(--text-dim);line-height:1.6}.share-note-icon{flex:none;color:var(--cyan);font-size:15px}@media(max-width:800px){.folder-head{align-items:flex-start}.folder-actions{margin-top:3px}.folder-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:650px){.shared-folder{padding:18px 13px 42px}.share-hero{padding:17px}.folder-head{display:block}.folder-title-wrap{align-items:flex-start}.hero-folder-icon{width:46px;height:46px}.folder-title-wrap h1{font-size:29px}.folder-actions{display:grid;grid-template-columns:1fr;margin-top:16px}.folder-grid{grid-template-columns:1fr}.file-row{grid-template-columns:42px minmax(0,1fr)}.file-badge{width:40px}.file-actions{grid-column:1/-1;justify-content:flex-end}.file-actions a{flex:1;text-align:center}.download-status-head{align-items:flex-start}.download-copy{display:block}.download-copy span{display:block;margin-top:4px}}
</style>
<link rel="stylesheet" href="<?= base_url('assets/css/shared-folder-preview.v1.css') ?>">
<?= view('partials/retro_theme') ?>
</head>
<body>
<div class="twinkle-layer" id="twinkleLayer"></div>
<main class="shared-folder">
  <section class="share-hero">
    <header class="folder-head">
      <div class="folder-title-wrap">
        <div class="hero-folder-icon" aria-hidden="true">&#128193;</div>
        <div class="folder-title-copy"><p class="eyebrow">Shared folder from Damon's Archive</p><h1><?= esc($currentName) ?></h1><div class="folder-sub"><span class="security-dot"></span><span>Anyone with this link can view</span><span>·</span><span><?= esc($rootName) ?></span></div></div>
      </div>
      <div class="folder-actions"><button type="button" class="share-btn primary" id="downloadFolderBtn" data-path="<?= esc($relativePath, 'attr') ?>" data-name="<?= esc($currentName, 'attr') ?>"><span aria-hidden="true">&#8681;</span> Download folder</button></div>
    </header>
    <div class="hero-meta"><span><?= number_format((int) $summary['files']) ?> file<?= (int) $summary['files'] === 1 ? '' : 's' ?></span><span><?= esc(ImportantFileModel::formatBytes((int) $summary['bytes'])) ?></span><?php if (! empty($share['expires_at'])): ?><span>Link expires <?= esc(date('M j, Y', strtotime((string) $share['expires_at']))) ?></span><?php else: ?><span>No link expiration</span><?php endif; ?></div>
  </section>

  <nav class="crumbs" aria-label="Shared folder breadcrumb">
    <?php foreach ($breadcrumbs as $index => $crumb): ?>
      <?php if ($index > 0): ?><span class="sep">/</span><?php endif; ?>
      <a class="crumb <?= $index === count($breadcrumbs) - 1 ? 'current' : '' ?>" href="<?= esc($folderUrl((string) $crumb['path']), 'attr') ?>"><?= esc($index === 0 ? $rootName : $crumb['label']) ?></a>
    <?php endforeach; ?>
  </nav>

  <div class="download-status" id="downloadStatus" role="status" aria-live="polite">
    <div class="download-status-head"><div class="download-status-title"><strong id="downloadTitle">Preparing folder…</strong><span id="downloadCurrent">Creating secure download list…</span></div><button type="button" class="download-cancel" id="downloadCancel">Cancel</button></div>
    <div class="progress-track"><div class="progress-bar" id="downloadBar"></div></div>
    <div class="download-copy"><span id="downloadFiles">0 files</span><span id="downloadBytes">0 B</span></div>
  </div>

  <?php if ($folders !== []): ?><section class="section"><div class="section-head"><h2 class="section-label">Folders</h2><span class="section-count"><?= count($folders) ?> folder<?= count($folders) === 1 ? '' : 's' ?></span></div><div class="folder-grid">
    <?php foreach ($folders as $folder): ?><a class="folder-card" href="<?= esc($folderUrl((string) $folder['relativePath']), 'attr') ?>"><span class="folder-icon">&#128193;</span><span class="folder-copy"><span class="folder-name"><?= esc($folder['name']) ?></span><span class="folder-count"><?= (int) $folder['count'] ?> item<?= (int) $folder['count'] === 1 ? '' : 's' ?></span></span><span class="folder-arrow" aria-hidden="true">&#8250;</span></a><?php endforeach; ?>
  </div></section><?php endif; ?>

  <?php if ($files !== []): ?><section class="section"><div class="section-head"><h2 class="section-label">Files</h2><span class="section-count"><?= count($files) ?> file<?= count($files) === 1 ? '' : 's' ?></span></div><ul class="file-list">
    <?php foreach ($files as $file):
      $type = ImportantFileModel::typeLabel((string) $file['mime_type'], (string) $file['original_filename']);
      $kind = ImportantFileModel::previewKind($file);
      $filePageUrl = base_url('share/' . $token . '/file/' . $file['id']);
      $filePreviewUrl = $filePageUrl . '/preview';
      $fileDownloadUrl = $filePageUrl . '/download';
    ?>
      <li class="file-row">
        <div class="file-badge"><?= esc($type) ?></div>
        <div class="file-copy"><div class="file-title"><?= esc($file['title']) ?></div><div class="file-name"><?= esc($file['original_filename']) ?></div><div class="file-meta"><?= esc(ImportantFileModel::formatBytes((int) $file['file_size'])) ?> · <?= esc(date('M j, Y', strtotime((string) $file['created_at']))) ?></div></div>
        <div class="file-actions">
          <button
            type="button"
            class="shared-preview-trigger"
            data-preview-kind="<?= esc($kind, 'attr') ?>"
            data-preview-url="<?= esc($filePreviewUrl, 'attr') ?>"
            data-page-url="<?= esc($filePageUrl, 'attr') ?>"
            data-download-url="<?= esc($fileDownloadUrl, 'attr') ?>"
            data-title="<?= esc((string) $file['title'], 'attr') ?>"
            data-filename="<?= esc((string) $file['original_filename'], 'attr') ?>"
            data-type="<?= esc($type, 'attr') ?>"
            data-mime="<?= esc((string) $file['mime_type'], 'attr') ?>"
            data-size="<?= esc(ImportantFileModel::formatBytes((int) $file['file_size']), 'attr') ?>"
            data-description="<?= esc((string) ($file['description'] ?? ''), 'attr') ?>"
          >Open</button>
          <a href="<?= esc($fileDownloadUrl, 'attr') ?>">Download</a>
        </div>
      </li>
    <?php endforeach; ?>
  </ul></section><?php endif; ?>

  <?php if ($folders === [] && $files === []): ?><div class="empty">This shared folder is empty.</div><?php endif; ?>
  <div class="share-note"><span class="share-note-icon" aria-hidden="true">&#128274;</span><span>Only files inside the folder selected by the owner are available through this link. The rest of the website and vault remain private.</span></div>
</main>

<div class="shared-preview-modal" id="sharedPreviewModal" aria-hidden="true">
  <div class="shared-preview-backdrop" data-preview-close></div>
  <section class="shared-preview-dialog" role="dialog" aria-modal="true" aria-labelledby="sharedPreviewTitle">
    <header class="shared-preview-header">
      <div class="shared-preview-heading">
        <strong id="sharedPreviewTitle">File preview</strong>
        <span id="sharedPreviewFilename"></span>
      </div>
      <div class="shared-preview-header-actions">
        <a id="sharedPreviewOpenTab" class="shared-preview-action" href="#" target="_blank" rel="noopener">Open tab</a>
        <a id="sharedPreviewDownload" class="shared-preview-action primary" href="#">Download</a>
        <button type="button" class="shared-preview-close" data-preview-close aria-label="Close preview">&times;</button>
      </div>
    </header>
    <div class="shared-preview-layout">
      <div class="shared-preview-stage" id="sharedPreviewStage">
        <div class="shared-preview-loader"><span class="shared-preview-spinner"></span><span>Loading secure preview…</span></div>
      </div>
      <aside class="shared-preview-info">
        <h2>File details</h2>
        <div class="shared-preview-detail"><span>Name</span><strong id="sharedPreviewDetailName"></strong></div>
        <div class="shared-preview-detail"><span>Type</span><strong id="sharedPreviewDetailType"></strong></div>
        <div class="shared-preview-detail"><span>Size</span><strong id="sharedPreviewDetailSize"></strong></div>
        <div class="shared-preview-detail description"><span>Description</span><strong id="sharedPreviewDetailDescription"></strong></div>
      </aside>
    </div>
  </section>
</div>
<?= view('partials/theme_scripts') ?>
<script src="<?= base_url('assets/js/shared-folder-preview.v1.js') ?>" defer></script>
<script>
const downloadButton=document.getElementById('downloadFolderBtn'),statusBox=document.getElementById('downloadStatus'),statusTitle=document.getElementById('downloadTitle'),statusCurrent=document.getElementById('downloadCurrent'),statusBar=document.getElementById('downloadBar'),statusFiles=document.getElementById('downloadFiles'),statusBytes=document.getElementById('downloadBytes'),cancelButton=document.getElementById('downloadCancel');
let downloadController=null,downloadBusy=false;
const ENCODER=new TextEncoder(),MAX32=0xffffffff,CRC_TABLE=(()=>{const table=new Uint32Array(256);for(let n=0;n<256;n++){let crc=n;for(let k=0;k<8;k++)crc=(crc&1)?(0xedb88320^(crc>>>1)):(crc>>>1);table[n]=crc>>>0;}return table;})();
function crcUpdate(crc,bytes){let value=crc>>>0;for(let i=0;i<bytes.length;i++)value=(CRC_TABLE[(value^bytes[i])&255]^(value>>>8))>>>0;return value>>>0;}
function u16(view,offset,value){view.setUint16(offset,value&65535,true);}function u32(view,offset,value){view.setUint32(offset,value>>>0,true);}
function dosDate(value){const parsed=value?new Date(value):new Date(),date=Number.isFinite(parsed.getTime())?parsed:new Date(),year=Math.max(1980,Math.min(2107,date.getFullYear()));return{time:((date.getHours()&31)<<11)|((date.getMinutes()&63)<<5)|(Math.floor(date.getSeconds()/2)&31),date:((year-1980)<<9)|(((date.getMonth()+1)&15)<<5)|(date.getDate()&31)};}
function localHeader(name,date){const out=new Uint8Array(30+name.length),view=new DataView(out.buffer);u32(view,0,0x04034b50);u16(view,4,20);u16(view,6,0x0808);u16(view,10,date.time);u16(view,12,date.date);u16(view,26,name.length);out.set(name,30);return out;}
function descriptor(crc,size){const out=new Uint8Array(16),view=new DataView(out.buffer);u32(view,0,0x08074b50);u32(view,4,crc);u32(view,8,size);u32(view,12,size);return out;}
function central(entry){const out=new Uint8Array(46+entry.name.length),view=new DataView(out.buffer);u32(view,0,0x02014b50);u16(view,4,20);u16(view,6,20);u16(view,8,0x0808);u16(view,12,entry.date.time);u16(view,14,entry.date.date);u32(view,16,entry.crc);u32(view,20,entry.size);u32(view,24,entry.size);u16(view,28,entry.name.length);u32(view,42,entry.offset);out.set(entry.name,46);return out;}
function endRecord(count,size,offset){const out=new Uint8Array(22),view=new DataView(out.buffer);u32(view,0,0x06054b50);u16(view,8,count);u16(view,10,count);u32(view,12,size);u32(view,16,offset);return out;}
const PREFETCH_MAX_FILE=12*1024*1024,PREFETCH_BUDGET=48*1024*1024,PREFETCH_CONCURRENCY=3;
function createPrefetch(files,signal){let budget=PREFETCH_BUDGET;const jobs=[];for(const file of files){const size=Math.max(0,Number(file.size)||0);if(size>0&&size<=PREFETCH_MAX_FILE&&size<=budget){jobs.push(file);budget-=size;}}const records=new Map();for(const file of jobs){let resolve;const promise=new Promise(done=>{resolve=done;});records.set(file,{promise,resolve});}let cursor=0;const worker=async()=>{while(cursor<jobs.length&&!signal.aborted){const file=jobs[cursor++],record=records.get(file);try{const response=await fetch(file.url,{signal,cache:'default',credentials:'omit'});if(!response.ok)throw new Error('prefetch failed');record.resolve(new Uint8Array(await response.arrayBuffer()));}catch(error){record.resolve(null);}}};for(let index=0;index<Math.min(PREFETCH_CONCURRENCY,jobs.length);index++)worker();return{get(file){return records.get(file)?.promise||null;}};}
async function* zipPieces(files,signal,onProgress){if(files.length>65535)throw new Error('This folder contains too many files for one ZIP.');const entries=[];let offset=0,loaded=0,completed=0,total=files.reduce((sum,file)=>sum+(Number(file.size)||0),0);const prefetch=createPrefetch(files,signal);for(const file of files){if(signal.aborted)throw new DOMException('Download cancelled.','AbortError');const name=ENCODER.encode(file.name||'file'),date=dosDate(file.lastModified),start=offset,header=localHeader(name,date);yield header;offset+=header.length;let crc=0xffffffff,size=0;onProgress({loaded,total,completed,count:files.length,current:file.name});const bufferedPromise=prefetch.get(file),buffered=bufferedPromise?await bufferedPromise:null;if(buffered){crc=crcUpdate(crc,buffered);size=buffered.length;loaded+=buffered.length;offset+=buffered.length;if(size>MAX32||offset>MAX32)throw new Error('This ZIP is too large. Download a smaller subfolder.');yield buffered;onProgress({loaded,total,completed,count:files.length,current:file.name});}else{const response=await fetch(file.url,{signal,cache:'default',credentials:'omit'});if(!response.ok)throw new Error('Could not download “'+file.name+'”.');if(response.body){const reader=response.body.getReader();try{while(true){const part=await reader.read();if(part.done)break;const chunk=part.value instanceof Uint8Array?part.value:new Uint8Array(part.value);crc=crcUpdate(crc,chunk);size+=chunk.length;loaded+=chunk.length;offset+=chunk.length;if(size>MAX32||offset>MAX32)throw new Error('This ZIP is too large. Download a smaller subfolder.');yield chunk;onProgress({loaded,total,completed,count:files.length,current:file.name});}}finally{reader.releaseLock();}}else{const chunk=new Uint8Array(await response.arrayBuffer());crc=crcUpdate(crc,chunk);size=chunk.length;loaded+=chunk.length;offset+=chunk.length;yield chunk;}}crc=(crc^0xffffffff)>>>0;const footer=descriptor(crc,size);yield footer;offset+=footer.length;entries.push({name,date,crc,size,offset:start});completed++;onProgress({loaded,total,completed,count:files.length,current:file.name});}const centralOffset=offset;for(const entry of entries){const header=central(entry);yield header;offset+=header.length;}yield endRecord(entries.length,offset-centralOffset,centralOffset);}
function zipStream(files,signal,onProgress){const iterator=zipPieces(files,signal,onProgress);return new ReadableStream({async pull(controller){try{const result=await iterator.next();result.done?controller.close():controller.enqueue(result.value);}catch(error){controller.error(error);}},async cancel(){if(iterator.return)await iterator.return();}});}
function bytes(value){const number=Number(value)||0;if(number<=0)return'0 B';const units=['B','KB','MB','GB'],index=Math.min(Math.floor(Math.log(number)/Math.log(1024)),units.length-1);return(number/1024**index).toFixed(index?1:0)+' '+units[index];}
function safeName(value){let name=String(value||'shared-folder.zip').replace(/[<>:"/\\|?*\x00-\x1f]/g,'_').replace(/[. ]+$/g,'').trim()||'shared-folder';return name.toLowerCase().endsWith('.zip')?name:name+'.zip';}
async function chooseSaveHandle(name){if(!window.isSecureContext||typeof window.showSaveFilePicker!=='function')return null;try{return await window.showSaveFilePicker({suggestedName:safeName(name),types:[{description:'ZIP archive',accept:{'application/zip':['.zip']}}]});}catch(error){if(error?.name==='AbortError')return false;throw error;}}
function setProgress(state){const total=Math.max(0,Number(state.total)||0),loaded=Math.max(0,Number(state.loaded)||0),count=Math.max(1,Number(state.count)||1),completed=Math.max(0,Number(state.completed)||0),percent=total?Math.min(98,Math.round(loaded/total*98)):Math.min(98,Math.round(completed/count*98));statusBar.style.width=percent+'%';statusCurrent.textContent=state.current?'Adding '+state.current+'…':'Building ZIP archive…';statusFiles.textContent=completed+' / '+count+' files';statusBytes.textContent=bytes(loaded)+' / '+bytes(total);}
async function startDownload(){if(downloadBusy)return;let saveHandle=null;try{saveHandle=await chooseSaveHandle((downloadButton.dataset.name||'shared-folder')+'.zip');if(saveHandle===false)return;}catch(error){statusBox.classList.add('show');statusTitle.textContent='Could not open save location';statusCurrent.textContent=error.message||'Try the download again.';return;}downloadBusy=true;downloadController=new AbortController();downloadButton.disabled=true;downloadButton.innerHTML='<span aria-hidden="true">&#8987;</span> Preparing…';cancelButton.hidden=false;cancelButton.disabled=false;cancelButton.textContent='Cancel';statusBox.classList.add('show');statusTitle.textContent='Preparing folder…';statusCurrent.textContent='Creating secure download list…';statusBar.style.width='0%';statusFiles.textContent='0 files';statusBytes.textContent='0 B';try{const url=new URL(<?= json_encode(base_url('share/' . $token . '/folder-manifest')) ?>);if(downloadButton.dataset.path)url.searchParams.set('path',downloadButton.dataset.path);const response=await fetch(url,{signal:downloadController.signal,cache:'no-store'}),data=await response.json();if(!response.ok)throw new Error(data.error||'Folder download failed.');if(!Array.isArray(data.files)||!data.files.length)throw new Error('This folder does not contain downloadable files.');const archiveName=safeName(data.archiveName);statusTitle.textContent='Downloading '+(downloadButton.dataset.name||'folder');statusFiles.textContent='0 / '+data.fileCount+' files';statusBytes.textContent='0 B / '+bytes(data.totalBytes);const stream=zipStream(data.files,downloadController.signal,setProgress);if(saveHandle){const writable=await saveHandle.createWritable();await stream.pipeTo(writable,{signal:downloadController.signal});}else{const blob=await new Response(stream,{headers:{'Content-Type':'application/zip'}}).blob();if(downloadController.signal.aborted)throw new DOMException('Download cancelled.','AbortError');const href=URL.createObjectURL(blob),link=document.createElement('a');link.href=href;link.download=archiveName;link.hidden=true;document.body.appendChild(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(href),60000);}statusBar.style.width='100%';statusTitle.textContent='Folder downloaded';statusCurrent.textContent=archiveName+' is ready.';statusFiles.textContent=data.fileCount+' / '+data.fileCount+' files';statusBytes.textContent=bytes(data.totalBytes);cancelButton.hidden=true;}catch(error){const cancelled=downloadController?.signal.aborted||error?.name==='AbortError';statusBar.style.width='0%';statusTitle.textContent=cancelled?'Download cancelled':'Download failed';statusCurrent.textContent=cancelled?'No ZIP archive was saved.':(error.message||'Could not download this folder.');statusFiles.textContent='';statusBytes.textContent='';cancelButton.hidden=true;}finally{downloadBusy=false;downloadController=null;downloadButton.disabled=false;downloadButton.innerHTML='<span aria-hidden="true">&#8681;</span> Download folder';}}
downloadButton.addEventListener('click',startDownload);
cancelButton.addEventListener('click',()=>{if(!downloadController)return;cancelButton.disabled=true;cancelButton.textContent='Cancelling…';statusCurrent.textContent='Stopping the folder download…';downloadController.abort();});
</script>
</body>
</html>
