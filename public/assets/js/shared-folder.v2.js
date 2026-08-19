(() => {
  'use strict';
  const configEl = document.getElementById('sharedFolderConfig');
  if (!configEl) return;
  const cfg = JSON.parse(configEl.textContent || '{}');
  const files = Array.isArray(cfg.files) ? cfg.files : [];
  const fileMap = new Map(files.map((file, index) => [Number(file.id), { ...file, index }]));
  const formatBytes = bytes => {
    let value = Number(bytes) || 0, unit = 0;
    const units = ['B','KB','MB','GB','TB'];
    while (value >= 1024 && unit < units.length - 1) { value /= 1024; unit++; }
    return (unit ? value.toFixed(1) : Math.round(value)) + ' ' + units[unit];
  };
  const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));

  // View mode and filter behavior.
  const list = document.getElementById('sharedFileList');
  const viewButtons = [...document.querySelectorAll('.sp-view-btn')];
  const savedView = localStorage.getItem('shared-folder-view') || 'list';
  const setView = view => {
    if (!list) return;
    list.dataset.view = view === 'grid' ? 'grid' : 'list';
    viewButtons.forEach(button => button.classList.toggle('active', button.dataset.view === list.dataset.view));
    localStorage.setItem('shared-folder-view', list.dataset.view);
  };
  setView(savedView);
  viewButtons.forEach(button => button.addEventListener('click', () => setView(button.dataset.view)));
  ['sharedType','sharedSort','sharedPerPage'].forEach(id => document.getElementById(id)?.addEventListener('change', () => document.getElementById('sharedFilterForm')?.requestSubmit()));
  let searchTimer;
  document.getElementById('sharedSearch')?.addEventListener('input', event => {
    clearTimeout(searchTimer);
    if (event.target.value.trim().length === 1) return;
    searchTimer = setTimeout(() => document.getElementById('sharedFilterForm')?.requestSubmit(), 500);
  });

  // Multi-file selection.
  const selectionBar = document.getElementById('selectionBar');
  const selectionCount = document.getElementById('selectionCount');
  const selectionSize = document.getElementById('selectionSize');
  const selected = new Map();
  const updateSelection = () => {
    let total = 0;
    selected.forEach(size => total += size);
    if (selectionBar) selectionBar.hidden = selected.size === 0;
    if (selectionCount) selectionCount.textContent = selected.size + ' selected';
    if (selectionSize) selectionSize.textContent = formatBytes(total);
  };
  document.querySelectorAll('.js-file-select').forEach(input => input.addEventListener('change', () => {
    const id = Number(input.value), size = Number(input.dataset.size) || 0;
    input.checked ? selected.set(id, size) : selected.delete(id);
    updateSelection();
  }));
  document.getElementById('clearSelectionBtn')?.addEventListener('click', () => {
    selected.clear(); document.querySelectorAll('.js-file-select').forEach(input => input.checked = false); updateSelection();
    const pageButton = document.getElementById('selectPageBtn'); if (pageButton) pageButton.textContent = 'Select page';
  });
  document.getElementById('selectPageBtn')?.addEventListener('click', event => {
    const inputs = [...document.querySelectorAll('.js-file-select')];
    const allSelected = inputs.length > 0 && inputs.every(input => input.checked);
    inputs.forEach(input => {
      input.checked = !allSelected;
      const id = Number(input.value), size = Number(input.dataset.size) || 0;
      allSelected ? selected.delete(id) : selected.set(id, size);
    });
    event.currentTarget.textContent = allSelected ? 'Select page' : 'Clear page';
    updateSelection();
  });

  // Accessible preview modal.
  const modal = document.getElementById('previewModal');
  const stage = document.getElementById('previewStage');
  const title = document.getElementById('previewTitle');
  const filename = document.getElementById('previewFilename');
  const position = document.getElementById('previewPosition');
  const info = document.getElementById('previewInfo');
  const downloadLink = document.getElementById('previewDownload');
  const prevButton = document.getElementById('previewPrev');
  const nextButton = document.getElementById('previewNext');
  let previewIndex = -1, previewAbort = null, restoreFocus = null, touchStartX = null;
  const focusable = () => [...modal.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),[tabindex]:not([tabindex="-1"])')].filter(el => !el.hidden && el.offsetParent !== null);
  const closePreview = () => {
    previewAbort?.abort(); previewAbort = null;
    modal.hidden = true; modal.setAttribute('aria-hidden','true'); document.body.style.overflow = '';
    stage.innerHTML = '';
    if (restoreFocus?.isConnected) restoreFocus.focus();
  };
  const showLoader = text => { stage.innerHTML = `<div class="sp-loader"><span class="sp-spinner"></span><span>${escapeHtml(text || 'Loading preview…')}</span></div>`; };
  const showError = (file, message) => { stage.innerHTML = `<div class="sp-preview-error"><h2>Preview unavailable</h2><p>${escapeHtml(message || 'This file could not be previewed.')}</p><a class="sp-btn sp-btn-primary" href="${escapeHtml(file.downloadUrl)}">Download file</a></div>`; };
  const renderInfo = file => {
    info.innerHTML = `<h3>File details</h3><dl><dt>Name</dt><dd>${escapeHtml(file.name)}</dd><dt>Type</dt><dd>${escapeHtml(file.mime || 'Unknown')}</dd><dt>Size</dt><dd>${escapeHtml(file.sizeLabel || formatBytes(file.size))}</dd>${file.description ? `<dt>Description</dt><dd>${escapeHtml(file.description)}</dd>` : ''}</dl>`;
  };
  const fetchPreview = async (file, options = {}) => {
    previewAbort?.abort(); previewAbort = new AbortController();
    const url = new URL(file.previewUrl, location.href); url.searchParams.set('_preview', Date.now());
    const response = await fetch(url, { cache:'no-store', signal:previewAbort.signal, ...options });
    if (!response.ok && response.status !== 206) throw new Error('Preview request failed (' + response.status + ').');
    const type = (response.headers.get('content-type') || '').toLowerCase();
    if (type.includes('text/html')) throw new Error('The server returned a web page instead of the file.');
    return response;
  };
  const mediaStateKey = file => 'shared-media:' + file.id;
  const renderImage = file => {
    stage.innerHTML = `<div class="sp-image-wrap sp-fullscreen-target"><div class="sp-image-toolbar"><button class="sp-btn" data-action="zoom-out">−</button><button class="sp-btn" data-action="zoom-in">+</button><button class="sp-btn" data-action="fit">Fit</button><button class="sp-btn" data-action="actual">100%</button><button class="sp-btn" data-action="rotate">Rotate</button><button class="sp-btn" data-action="fullscreen">Fullscreen</button></div><div class="sp-image-canvas"><img alt="${escapeHtml(file.title)}"></div></div>`;
    const wrap = stage.querySelector('.sp-image-wrap'), img = stage.querySelector('img'); let scale = 1, rotation = 0, fitted = true;
    const apply = () => { img.style.maxWidth = fitted ? '100%' : 'none'; img.style.maxHeight = fitted ? '100%' : 'none'; img.style.transform = `scale(${scale}) rotate(${rotation}deg)`; };
    img.addEventListener('error', () => showError(file, 'The image could not be loaded.'));
    img.src = file.previewUrl + (file.previewUrl.includes('?') ? '&' : '?') + '_preview=' + Date.now();
    wrap.addEventListener('click', event => {
      const action = event.target.closest('[data-action]')?.dataset.action; if (!action) return;
      if (action === 'zoom-in') { fitted = false; scale = Math.min(5, scale + .25); }
      if (action === 'zoom-out') { fitted = false; scale = Math.max(.25, scale - .25); }
      if (action === 'fit') { fitted = true; scale = 1; }
      if (action === 'actual') { fitted = false; scale = 1; }
      if (action === 'rotate') rotation = (rotation + 90) % 360;
      if (action === 'fullscreen') wrap.requestFullscreen?.();
      apply();
    });
  };
  const renderMedia = file => {
    const video = file.kind === 'video';
    stage.innerHTML = `<div class="sp-media-wrap sp-fullscreen-target"><div class="sp-media-toolbar"><label>Speed <select data-speed><option>.5</option><option>.75</option><option selected>1</option><option>1.25</option><option>1.5</option><option>2</option></select></label>${video ? '<button type="button" class="sp-btn" data-pip>Picture in picture</button><button type="button" class="sp-btn" data-fullscreen>Fullscreen</button>' : ''}</div><${video ? 'video' : 'audio'} controls preload="metadata"></${video ? 'video' : 'audio'}></div>`;
    const media = stage.querySelector(video ? 'video' : 'audio'), wrap = stage.querySelector('.sp-media-wrap');
    media.src = file.previewUrl; const saved = JSON.parse(sessionStorage.getItem(mediaStateKey(file)) || '{}');
    media.volume = Number.isFinite(saved.volume) ? saved.volume : Number(localStorage.getItem('shared-media-volume') || .8);
    media.addEventListener('loadedmetadata', () => { if (saved.time && saved.time < media.duration - 2) media.currentTime = saved.time; });
    media.addEventListener('timeupdate', () => sessionStorage.setItem(mediaStateKey(file), JSON.stringify({time:media.currentTime,volume:media.volume})));
    media.addEventListener('volumechange', () => localStorage.setItem('shared-media-volume', media.volume));
    wrap.querySelector('[data-speed]').addEventListener('change', event => media.playbackRate = Number(event.target.value) || 1);
    wrap.querySelector('[data-pip]')?.addEventListener('click', () => media.requestPictureInPicture?.());
    wrap.querySelector('[data-fullscreen]')?.addEventListener('click', () => wrap.requestFullscreen?.());
    media.addEventListener('error', () => showError(file, 'The media file could not be loaded.'));
  };
  const tokenClass = token => {
    if (/^\s*(\/\/|#|--|\/\*)/.test(token)) return 'tok-comment';
    if (/^["'`]/.test(token)) return 'tok-string';
    if (/^\d/.test(token)) return 'tok-number';
    if (/^(SELECT|FROM|WHERE|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|JOIN|AND|OR|NOT|NULL|TRUE|FALSE|function|const|let|var|class|public|private|protected|return|if|else|for|while|try|catch|new|import|export|def|async|await|echo|namespace|use)$/i.test(token)) return 'tok-keyword';
    return 'tok-operator';
  };
  const highlightLine = line => {
    const regex = /(\/\/.*$|#.*$|--.*$|\/\*[\s\S]*?\*\/|"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'|`(?:\\.|[^`\\])*`|\b(?:SELECT|FROM|WHERE|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|JOIN|AND|OR|NOT|NULL|TRUE|FALSE|function|const|let|var|class|public|private|protected|return|if|else|for|while|try|catch|new|import|export|def|async|await|echo|namespace|use)\b|\b\d+(?:\.\d+)?\b|[{}()[\];,.=+\-*/<>!&|:]+)/gi;
    let out = '', last = 0, match;
    while ((match = regex.exec(line))) { out += escapeHtml(line.slice(last, match.index)); out += `<span class="${tokenClass(match[0])}">${escapeHtml(match[0])}</span>`; last = match.index + match[0].length; }
    return out + escapeHtml(line.slice(last));
  };
  const renderText = async file => {
    showLoader('Loading text preview…'); const response = await fetchPreview(file); const raw = await response.text();
    const truncated = response.headers.get('x-preview-truncated') === 'true';
    stage.innerHTML = `<div class="sp-code-wrap"><div class="sp-code-toolbar"><input class="sp-code-search" type="search" placeholder="Find in preview"><button type="button" class="sp-btn" data-copy>Copy</button><button type="button" class="sp-btn" data-wrap>Wrap</button><span class="sp-code-tools-spacer"></span>${truncated ? '<span>Preview shortened</span>' : ''}</div><div class="sp-code-scroll"><pre class="sp-code"></pre></div></div>`;
    const pre = stage.querySelector('.sp-code'); const lines = (raw || '(This file is empty.)').replace(/\r\n?/g,'\n').split('\n');
    pre.innerHTML = lines.map(line => `<span class="sp-code-line">${highlightLine(line)}</span>`).join('');
    stage.querySelector('[data-copy]').addEventListener('click', async event => { await navigator.clipboard.writeText(raw); const old=event.target.textContent; event.target.textContent='Copied'; setTimeout(()=>event.target.textContent=old,1200); });
    stage.querySelector('[data-wrap]').addEventListener('click', event => { pre.classList.toggle('wrap'); event.target.textContent = pre.classList.contains('wrap') ? 'No wrap' : 'Wrap'; });
    stage.querySelector('.sp-code-search').addEventListener('input', event => { const term=event.target.value.trim().toLowerCase(); pre.querySelectorAll('.sp-code-line').forEach(line=>line.classList.toggle('match',term!==''&&line.textContent.toLowerCase().includes(term))); });
  };
  const renderPdf = async file => {
    showLoader('Loading PDF renderer…');
    try {
      const pdfjs = await import(cfg.pdfModuleUrl); pdfjs.GlobalWorkerOptions.workerSrc = cfg.pdfWorkerUrl;
      const source = new URL(file.previewUrl, location.href); source.searchParams.set('_preview',Date.now());
      const pdf = await pdfjs.getDocument({url:source.toString(),rangeChunkSize:131072,disableAutoFetch:true,disableRange:false}).promise;
      stage.innerHTML = '<div class="sp-pdf-pages"></div>'; const pages = stage.firstElementChild;
      const renderCount = Math.min(pdf.numPages, matchMedia('(max-width:760px)').matches ? 2 : 3);
      for (let no=1; no<=renderCount; no++) {
        const page=await pdf.getPage(no), base=page.getViewport({scale:1}), available=Math.max(280,Math.min(980,stage.clientWidth-48)), scale=Math.max(.55,Math.min(1.5,available/base.width)), dpr=Math.min(devicePixelRatio||1,1.35), viewport=page.getViewport({scale:scale*dpr});
        const shell=document.createElement('div'); shell.className='sp-pdf-page'; const label=document.createElement('div'); label.className='sp-pdf-label'; label.textContent=`Page ${no} of ${pdf.numPages}`; const canvas=document.createElement('canvas'); canvas.width=Math.ceil(viewport.width);canvas.height=Math.ceil(viewport.height);canvas.style.width=Math.ceil(viewport.width/dpr)+'px';canvas.style.height=Math.ceil(viewport.height/dpr)+'px'; shell.append(label,canvas);pages.append(shell); const ctx=canvas.getContext('2d',{alpha:false});ctx.fillStyle='#fff';ctx.fillRect(0,0,canvas.width,canvas.height);await page.render({canvasContext:ctx,viewport}).promise; page.cleanup();
      }
      if (pdf.numPages > renderCount) { const note=document.createElement('div');note.className='sp-fallback-warning';note.innerHTML=`Showing ${renderCount} of ${pdf.numPages} pages for faster loading. <a href="${escapeHtml(file.previewUrl)}" target="_blank" rel="noopener">Open the full PDF</a>.`;pages.append(note); }
    } catch (error) {
      console.warn('Local PDF renderer unavailable, using browser viewer.',error);
      stage.innerHTML = `<object class="sp-native-pdf" type="application/pdf" data="${escapeHtml(file.previewUrl)}"><div class="sp-preview-error"><p>Your browser could not display this PDF.</p><a class="sp-btn sp-btn-primary" href="${escapeHtml(file.downloadUrl)}">Download PDF</a></div></object>`;
    }
  };
  const renderUnsupported = file => { stage.innerHTML = `<div class="sp-unsupported"><div class="sp-file-badge">FILE</div><h2>No browser preview</h2><p>This format can still be downloaded with its original filename.</p><a class="sp-btn sp-btn-primary" href="${escapeHtml(file.downloadUrl)}">Download file</a></div>`; };
  const renderPreview = async file => {
    showLoader(); title.textContent=file.title||file.name; filename.textContent=file.name||''; downloadLink.href=file.downloadUrl; position.textContent=(file.index+1)+' / '+files.length; prevButton.disabled=file.index<=0;nextButton.disabled=file.index>=files.length-1;renderInfo(file);
    try { if(file.kind==='image')renderImage(file); else if(file.kind==='video'||file.kind==='audio')renderMedia(file); else if(file.kind==='text')await renderText(file); else if(file.kind==='pdf')await renderPdf(file); else renderUnsupported(file); } catch(error){ if(error.name!=='AbortError')showError(file,error.message); }
  };
  const openPreview = (id, trigger) => {
    const file=fileMap.get(Number(id)); if(!file)return; restoreFocus=trigger||document.activeElement; previewIndex=file.index; modal.hidden=false;modal.setAttribute('aria-hidden','false');document.body.style.overflow='hidden';renderPreview(file); setTimeout(()=>modal.querySelector('[data-close-preview]')?.focus(),0);
  };
  document.addEventListener('click', event => { const button=event.target.closest('.js-open-preview'); if(button){event.preventDefault();openPreview(button.dataset.fileId,button);} if(event.target.matches('[data-close-preview]')||event.target===modal)closePreview(); });
  prevButton?.addEventListener('click',()=>{if(previewIndex>0){previewIndex--;renderPreview(files[previewIndex]);}}); nextButton?.addEventListener('click',()=>{if(previewIndex<files.length-1){previewIndex++;renderPreview(files[previewIndex]);}});
  modal?.addEventListener('touchstart',e=>touchStartX=e.changedTouches[0].clientX,{passive:true}); modal?.addEventListener('touchend',e=>{if(touchStartX===null)return;const dx=e.changedTouches[0].clientX-touchStartX;touchStartX=null;if(Math.abs(dx)>70)(dx>0?prevButton:nextButton)?.click();},{passive:true});
  document.addEventListener('keydown',event=>{if(modal?.hidden)return;if(event.key==='Escape')closePreview();if(event.key==='ArrowLeft')prevButton.click();if(event.key==='ArrowRight')nextButton.click();if(event.key==='Tab'){const items=focusable();if(!items.length)return;const first=items[0],last=items[items.length-1];if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}}});

  // ZIP creation. Files are stored (not recompressed), which is fast and preserves exact names.
  const CRC_TABLE=(()=>{const table=new Uint32Array(256);for(let n=0;n<256;n++){let c=n;for(let k=0;k<8;k++)c=(c&1)?0xedb88320^(c>>>1):c>>>1;table[n]=c>>>0;}return table;})();
  const encoder=new TextEncoder(),MAX32=0xffffffff;
  const crcUpdate=(crc,bytes)=>{let value=crc>>>0;for(let i=0;i<bytes.length;i++)value=(CRC_TABLE[(value^bytes[i])&255]^(value>>>8))>>>0;return value>>>0;};
  const u16=(v,o,x)=>v.setUint16(o,x&65535,true),u32=(v,o,x)=>v.setUint32(o,x>>>0,true);
  const dosDate=value=>{const d=value?new Date(value):new Date(),date=Number.isFinite(d.getTime())?d:new Date(),year=Math.max(1980,Math.min(2107,date.getFullYear()));return{time:((date.getHours()&31)<<11)|((date.getMinutes()&63)<<5)|(Math.floor(date.getSeconds()/2)&31),date:((year-1980)<<9)|(((date.getMonth()+1)&15)<<5)|(date.getDate()&31)};};
  const localHeader=(name,date)=>{const out=new Uint8Array(30+name.length),v=new DataView(out.buffer);u32(v,0,0x04034b50);u16(v,4,20);u16(v,6,0x0808);u16(v,10,date.time);u16(v,12,date.date);u16(v,26,name.length);out.set(name,30);return out;};
  const descriptor=(crc,size)=>{const out=new Uint8Array(16),v=new DataView(out.buffer);u32(v,0,0x08074b50);u32(v,4,crc);u32(v,8,size);u32(v,12,size);return out;};
  const central=e=>{const out=new Uint8Array(46+e.name.length),v=new DataView(out.buffer);u32(v,0,0x02014b50);u16(v,4,20);u16(v,6,20);u16(v,8,0x0808);u16(v,12,e.date.time);u16(v,14,e.date.date);u32(v,16,e.crc);u32(v,20,e.size);u32(v,24,e.size);u16(v,28,e.name.length);u32(v,42,e.offset);out.set(e.name,46);return out;};
  const endRecord=(count,size,offset)=>{const out=new Uint8Array(22),v=new DataView(out.buffer);u32(v,0,0x06054b50);u16(v,8,count);u16(v,10,count);u32(v,12,size);u32(v,16,offset);return out;};
  async function* zipPieces(items,signal,onProgress){const entries=[];let offset=0,loaded=0,completed=0,total=items.reduce((s,f)=>s+(Number(f.size)||0),0);for(const file of items){if(signal.aborted)throw new DOMException('Cancelled','AbortError');const name=encoder.encode(file.name||'file'),date=dosDate(file.lastModified),start=offset,head=localHeader(name,date);yield head;offset+=head.length;let crc=0xffffffff,size=0;onProgress({loaded,total,completed,count:items.length,current:file.name});const response=await fetch(file.url,{signal,credentials:'omit',cache:'default'});if(!response.ok)throw new Error('Could not download “'+file.name+'”.');if(response.body){const reader=response.body.getReader();while(true){const part=await reader.read();if(part.done)break;const chunk=part.value instanceof Uint8Array?part.value:new Uint8Array(part.value);crc=crcUpdate(crc,chunk);size+=chunk.length;loaded+=chunk.length;offset+=chunk.length;if(size>MAX32||offset>MAX32)throw new Error('ZIP is too large.');yield chunk;onProgress({loaded,total,completed,count:items.length,current:file.name});}}else{const chunk=new Uint8Array(await response.arrayBuffer());crc=crcUpdate(crc,chunk);size=chunk.length;loaded+=chunk.length;offset+=chunk.length;yield chunk;}crc=(crc^0xffffffff)>>>0;const foot=descriptor(crc,size);yield foot;offset+=foot.length;entries.push({name,date,crc,size,offset:start});completed++;onProgress({loaded,total,completed,count:items.length,current:file.name});}const centralOffset=offset;for(const entry of entries){const head=central(entry);yield head;offset+=head.length;}yield endRecord(entries.length,offset-centralOffset,centralOffset);}
  const zipStream=(items,signal,onProgress)=>{const iterator=zipPieces(items,signal,onProgress);return new ReadableStream({async pull(controller){try{const r=await iterator.next();r.done?controller.close():controller.enqueue(r.value);}catch(e){controller.error(e);}},async cancel(){if(iterator.return)await iterator.return();}});};
  const safeName=value=>{let name=String(value||'shared-files.zip').replace(/[<>:"/\\|?*\x00-\x1f]/g,'_').replace(/[. ]+$/g,'').trim()||'shared-files';return name.toLowerCase().endsWith('.zip')?name:name+'.zip';};
  const statusBox=document.getElementById('downloadStatus'),statusTitle=document.getElementById('downloadTitle'),statusCurrent=document.getElementById('downloadCurrent'),statusBar=document.getElementById('downloadBar'),statusFiles=document.getElementById('downloadFiles'),statusBytes=document.getElementById('downloadBytes'),cancelButton=document.getElementById('downloadCancel');let downloadController=null,downloadBusy=false;
  const setProgress=s=>{const total=Number(s.total)||0,loaded=Number(s.loaded)||0,count=Math.max(1,Number(s.count)||1),completed=Number(s.completed)||0;statusBar.style.width=(total?Math.min(98,loaded/total*98):Math.min(98,completed/count*98))+'%';statusCurrent.textContent=s.current?'Adding '+s.current+'…':'Building ZIP…';statusFiles.textContent=completed+' / '+count+' files';statusBytes.textContent=formatBytes(loaded)+' / '+formatBytes(total);};
  const postQuiet=async url=>{if(!url)return;try{await fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},body:'{}',keepalive:true,cache:'no-store'});}catch(_){}};
  const startArchive=async(manifestUrl,payload,name)=>{if(downloadBusy)return;downloadBusy=true;downloadController=new AbortController();statusBox.hidden=false;statusTitle.textContent='Preparing '+name;statusCurrent.textContent='Creating secure download list…';statusBar.style.width='0';cancelButton.disabled=false;try{const response=await fetch(manifestUrl,{method:payload?'POST':'GET',headers:payload?{'Content-Type':'application/json'}:{},body:payload?JSON.stringify(payload):undefined,signal:downloadController.signal,cache:'no-store'});const data=await response.json();if(!response.ok)throw new Error(data.error||'Could not prepare download.');const canStream=window.isSecureContext&&typeof window.showSaveFilePicker==='function';if(!canStream&&Number(data.totalBytes)>Number(data.fallbackMaxBytes)){await postQuiet(data.cancelUrl);throw new Error('This download is too large for your browser’s memory mode. Use Chrome or Edge on desktop, or download a smaller subfolder/selection.');}const archive=safeName(data.archiveName),stream=zipStream(data.files,downloadController.signal,setProgress);if(canStream){let handle;try{handle=await window.showSaveFilePicker({suggestedName:archive,types:[{description:'ZIP archive',accept:{'application/zip':['.zip']}}]});}catch(e){if(e.name==='AbortError'){await postQuiet(data.cancelUrl);throw e;}throw e;}const writable=await handle.createWritable();await stream.pipeTo(writable,{signal:downloadController.signal});}else{const blob=await new Response(stream,{headers:{'Content-Type':'application/zip'}}).blob();const href=URL.createObjectURL(blob),a=document.createElement('a');a.href=href;a.download=archive;a.hidden=true;document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(href),60000);}await postQuiet(data.confirmUrl);statusBar.style.width='100%';statusTitle.textContent='Download ready';statusCurrent.textContent=archive;statusFiles.textContent=data.fileCount+' files';statusBytes.textContent=formatBytes(data.totalBytes);}catch(error){const cancelled=downloadController?.signal.aborted||error.name==='AbortError';statusTitle.textContent=cancelled?'Download cancelled':'Download failed';statusCurrent.textContent=cancelled?'Nothing was counted against the link limit.':(error.message||'Try again.');statusBar.style.width='0';}finally{downloadBusy=false;downloadController=null;cancelButton.disabled=false;}};
  const folderUrl=path=>{const url=new URL(cfg.folderManifestUrl,location.href);if(path)url.searchParams.set('path',path);return url.toString();};
  document.getElementById('downloadFolderBtn')?.addEventListener('click',event=>startArchive(folderUrl(event.currentTarget.dataset.path),null,event.currentTarget.dataset.name||'folder'));
  document.querySelectorAll('.js-download-subfolder').forEach(button=>button.addEventListener('click',()=>startArchive(folderUrl(button.dataset.path),null,button.dataset.name||'folder')));
  document.getElementById('downloadSelectedBtn')?.addEventListener('click',()=>startArchive(cfg.selectionManifestUrl,{fileIds:[...selected.keys()]},'selected files'));
  cancelButton?.addEventListener('click',()=>{if(downloadController){cancelButton.disabled=true;downloadController.abort();}});
})();
