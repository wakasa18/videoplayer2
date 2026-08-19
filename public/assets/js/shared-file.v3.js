(() => {
  'use strict';
  const stage=document.getElementById('sharePreviewStage');
  if(!stage)return;
  const kind=stage.dataset.kind||'unsupported';
  const preview=stage.dataset.previewUrl||'';
  const download=stage.dataset.downloadUrl||'';
  if(kind!=='pdf'&&kind!=='text')return;

  const PDFJS_MODULE='https://cdn.jsdelivr.net/npm/pdfjs-dist@6.2.108/build/pdf.min.mjs';
  const PDFJS_WORKER='https://cdn.jsdelivr.net/npm/pdfjs-dist@6.2.108/build/pdf.worker.min.mjs';
  const targetUrl=()=>{const url=new URL(preview,location.href);url.searchParams.set('_preview',Date.now().toString());return url.toString();};
  const fail=message=>{stage.classList.remove('pdf-mode','text-mode');stage.innerHTML='';const box=document.createElement('div');box.className='share-preview-error';box.innerHTML='<h2>Preview could not be displayed</h2><p></p><a class="share-button primary">Download file</a>';box.querySelector('p').textContent=message||'The file is still available to download.';box.querySelector('a').href=download;stage.appendChild(box);};
  const response=async()=>{const result=await fetch(targetUrl(),{cache:'no-store',redirect:'follow'});if(!result.ok)throw new Error('Preview request failed ('+result.status+').');const type=(result.headers.get('content-type')||'').toLowerCase();if(type.includes('text/html'))throw new Error('The share link returned a web page instead of the file contents.');return result;};
  const renderText=async()=>{const result=await response(),value=await result.text();stage.classList.add('text-mode');stage.innerHTML='';const pre=document.createElement('pre');pre.className='share-text-preview';pre.textContent=value||'(This file is empty.)';stage.appendChild(pre);if(result.headers.get('x-preview-truncated')==='true'){const note=document.createElement('div');note.className='share-note';note.textContent='Large text preview shortened for faster loading. Download the file to view everything.';stage.appendChild(note);}};
  const renderPdf=async()=>{
    const source=targetUrl();
    const probe=await fetch(source,{cache:'no-store',redirect:'follow',headers:{Range:'bytes=0-7',Accept:'application/pdf,*/*;q=0.8'}});
    if(!probe.ok&&probe.status!==206)throw new Error('Preview request failed ('+probe.status+').');
    const bytes=new Uint8Array(await probe.arrayBuffer());
    if(!String.fromCharCode(...bytes).startsWith('%PDF-'))throw new Error('The received file is not a valid PDF preview.');
    const pdfjs=await import(PDFJS_MODULE);pdfjs.GlobalWorkerOptions.workerSrc=PDFJS_WORKER;
    const task=pdfjs.getDocument({url:source,rangeChunkSize:131072,disableAutoFetch:true,disableRange:false,disableStream:false});
    const pdf=await task.promise;
    stage.classList.add('pdf-mode');stage.innerHTML='';
    const pages=document.createElement('div');pages.className='share-pdf-pages';stage.appendChild(pages);
    const progress=document.createElement('div');progress.className='share-pdf-progress';pages.appendChild(progress);
    let rendered=0,busy=false;
    const batchSize=matchMedia('(max-width:800px)').matches?2:3;
    const renderBatch=async()=>{
      if(busy||rendered>=pdf.numPages)return;busy=true;
      pages.querySelector('.share-pdf-load-wrap')?.remove();
      const target=Math.min(pdf.numPages,rendered+batchSize);
      for(let pageNo=rendered+1;pageNo<=target;pageNo++){
        const page=await pdf.getPage(pageNo),base=page.getViewport({scale:1}),available=Math.max(280,Math.min(980,stage.clientWidth-44)),cssScale=Math.max(.55,Math.min(1.55,available/base.width)),dpr=matchMedia('(max-width:800px)').matches?1:Math.max(1,Math.min(devicePixelRatio||1,1.35)),viewport=page.getViewport({scale:cssScale*dpr}),shell=document.createElement('div'),label=document.createElement('div'),canvas=document.createElement('canvas');
        shell.className='share-pdf-page';label.className='share-pdf-label';label.textContent='Page '+pageNo+' of '+pdf.numPages;canvas.width=Math.ceil(viewport.width);canvas.height=Math.ceil(viewport.height);canvas.style.width=Math.ceil(viewport.width/dpr)+'px';canvas.style.height=Math.ceil(viewport.height/dpr)+'px';shell.append(label,canvas);pages.insertBefore(shell,progress);const context=canvas.getContext('2d',{alpha:false});context.fillStyle='#fff';context.fillRect(0,0,canvas.width,canvas.height);await page.render({canvasContext:context,viewport}).promise;page.cleanup();rendered=pageNo;progress.textContent='Showing '+rendered+' of '+pdf.numPages+' pages';await new Promise(resolve=>requestAnimationFrame(resolve));
      }
      if(rendered<pdf.numPages){const wrap=document.createElement('div');wrap.className='share-pdf-load-wrap';const button=document.createElement('button');button.type='button';button.className='share-button secondary share-pdf-load';button.textContent='Load next '+Math.min(batchSize,pdf.numPages-rendered)+' pages';button.addEventListener('click',renderBatch,{once:true});wrap.appendChild(button);pages.appendChild(wrap);}busy=false;
    };
    await renderBatch();
  };
  const start=()=>{(kind==='pdf'?renderPdf():renderText()).catch(error=>{console.error('Shared preview failed:',error);fail(error.message);});};
  if('requestIdleCallback' in window)requestIdleCallback(start,{timeout:350});else setTimeout(start,0);
})();

