(() => {
  'use strict';

  const modal = document.getElementById('sharedPreviewModal');
  if (!modal) return;

  const stage = document.getElementById('sharedPreviewStage');
  const title = document.getElementById('sharedPreviewTitle');
  const filename = document.getElementById('sharedPreviewFilename');
  const openTab = document.getElementById('sharedPreviewOpenTab');
  const download = document.getElementById('sharedPreviewDownload');
  const detailName = document.getElementById('sharedPreviewDetailName');
  const detailType = document.getElementById('sharedPreviewDetailType');
  const detailSize = document.getElementById('sharedPreviewDetailSize');
  const detailDescription = document.getElementById('sharedPreviewDetailDescription');
  const triggers = [...document.querySelectorAll('.shared-preview-trigger')];
  const PDFJS_MODULE = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@6.2.108/build/pdf.min.mjs';
  const PDFJS_WORKER = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@6.2.108/build/pdf.worker.min.mjs';
  let controller = null;
  let objectUrl = null;
  let lastFocus = null;
  let requestId = 0;

  const loader = () => {
    stage.innerHTML = '<div class="shared-preview-loader"><span class="shared-preview-spinner"></span><span>Loading secure preview…</span></div>';
  };

  const cleanup = () => {
    requestId++;
    controller?.abort();
    controller = null;
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl = null;
    stage.querySelectorAll('video,audio').forEach(media => {
      media.pause();
      media.removeAttribute('src');
      media.load();
    });
    stage.innerHTML = '';
  };

  const fallback = (message, type, downloadUrl) => {
    stage.innerHTML = '';
    const box = document.createElement('div');
    box.className = 'shared-preview-fallback';
    const icon = document.createElement('div');
    icon.className = 'shared-preview-file-icon';
    icon.textContent = type || 'FILE';
    const heading = document.createElement('h3');
    heading.textContent = 'Preview unavailable';
    const copy = document.createElement('p');
    copy.textContent = message || 'This format can still be downloaded.';
    const link = document.createElement('a');
    link.className = 'shared-preview-action primary';
    link.href = downloadUrl;
    link.textContent = 'Download file';
    box.append(icon, heading, copy, link);
    stage.appendChild(box);
  };

  const checkedFetch = async (url, options = {}) => {
    const response = await fetch(url, {cache: 'no-store', redirect: 'follow', ...options});
    if (!response.ok && response.status !== 206) throw new Error(`Preview request failed (${response.status}).`);
    const type = (response.headers.get('content-type') || '').toLowerCase();
    if (type.includes('text/html')) throw new Error('The share link returned a web page instead of the file.');
    return response;
  };

  const cacheBustedUrl = value => {
    const url = new URL(value, location.href);
    url.searchParams.set('_preview', Date.now().toString());
    return url.toString();
  };

  const renderImage = async (url, id) => {
    const response = await checkedFetch(cacheBustedUrl(url), {signal: controller.signal});
    const blob = await response.blob();
    if (id !== requestId) return;
    objectUrl = URL.createObjectURL(blob);
    const image = new Image();
    image.alt = title.textContent;
    image.src = objectUrl;
    image.addEventListener('error', () => fallback('The image could not be displayed.', 'IMG', download.href), {once: true});
    stage.innerHTML = '';
    stage.appendChild(image);
  };

  const renderMedia = async (url, tag, id) => {
    const response = await checkedFetch(cacheBustedUrl(url), {signal: controller.signal});
    const blob = await response.blob();
    if (id !== requestId) return;
    objectUrl = URL.createObjectURL(blob);
    const media = document.createElement(tag);
    media.controls = true;
    media.preload = 'metadata';
    media.src = objectUrl;
    media.addEventListener('error', () => fallback(`The ${tag} could not be played in this browser.`, tag.toUpperCase(), download.href), {once: true});
    stage.innerHTML = '';
    stage.appendChild(media);
  };

  const renderText = async (url, id) => {
    const response = await checkedFetch(cacheBustedUrl(url), {signal: controller.signal});
    const value = await response.text();
    if (id !== requestId) return;
    stage.innerHTML = '';
    const pre = document.createElement('pre');
    pre.className = 'shared-preview-text';
    pre.textContent = value || '(This file is empty.)';
    stage.appendChild(pre);
    if (response.headers.get('x-preview-truncated') === 'true') {
      const note = document.createElement('div');
      note.className = 'shared-preview-note';
      note.textContent = 'Large text preview shortened for faster loading. Download the file to view everything.';
      stage.appendChild(note);
    }
  };

  const renderPdf = async (url, id) => {
    const source = cacheBustedUrl(url);
    const probe = await checkedFetch(source, {
      signal: controller.signal,
      headers: {Range: 'bytes=0-7', Accept: 'application/pdf,*/*;q=0.8'}
    });
    const probeBytes = new Uint8Array(await probe.arrayBuffer());
    if (!String.fromCharCode(...probeBytes).startsWith('%PDF-')) throw new Error('The received file is not a valid PDF.');
    if (id !== requestId) return;

    const pdfjs = await import(PDFJS_MODULE);
    pdfjs.GlobalWorkerOptions.workerSrc = PDFJS_WORKER;
    const task = pdfjs.getDocument({url: source, rangeChunkSize: 131072, disableAutoFetch: true, disableRange: false, disableStream: false});
    controller.signal.addEventListener('abort', () => task.destroy(), {once: true});
    const pdf = await task.promise;
    if (id !== requestId) return task.destroy();

    stage.innerHTML = '';
    const pages = document.createElement('div');
    pages.className = 'shared-preview-pdf-pages';
    stage.appendChild(pages);
    const progress = document.createElement('div');
    progress.className = 'shared-preview-pdf-progress';
    pages.appendChild(progress);
    let rendered = 0;
    let busy = false;
    const batchSize = matchMedia('(max-width:820px)').matches ? 2 : 3;

    const renderBatch = async () => {
      if (busy || rendered >= pdf.numPages || id !== requestId) return;
      busy = true;
      pages.querySelector('.shared-preview-load-wrap')?.remove();
      const target = Math.min(pdf.numPages, rendered + batchSize);
      for (let pageNo = rendered + 1; pageNo <= target; pageNo++) {
        if (id !== requestId) return;
        const page = await pdf.getPage(pageNo);
        const base = page.getViewport({scale: 1});
        const available = Math.max(280, Math.min(980, stage.clientWidth - 44));
        const cssScale = Math.max(.55, Math.min(1.45, available / base.width));
        const dpr = matchMedia('(max-width:820px)').matches ? 1 : Math.max(1, Math.min(devicePixelRatio || 1, 1.3));
        const viewport = page.getViewport({scale: cssScale * dpr});
        const shell = document.createElement('div');
        const label = document.createElement('div');
        const canvas = document.createElement('canvas');
        shell.className = 'shared-preview-pdf-page';
        label.className = 'shared-preview-pdf-label';
        label.textContent = `Page ${pageNo} of ${pdf.numPages}`;
        canvas.width = Math.ceil(viewport.width);
        canvas.height = Math.ceil(viewport.height);
        canvas.style.width = `${Math.ceil(viewport.width / dpr)}px`;
        canvas.style.height = `${Math.ceil(viewport.height / dpr)}px`;
        shell.append(label, canvas);
        pages.insertBefore(shell, progress);
        const context = canvas.getContext('2d', {alpha: false});
        context.fillStyle = '#fff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        await page.render({canvasContext: context, viewport}).promise;
        page.cleanup();
        rendered = pageNo;
        progress.textContent = `Showing ${rendered} of ${pdf.numPages} pages`;
        await new Promise(resolve => requestAnimationFrame(resolve));
      }
      if (rendered < pdf.numPages && id === requestId) {
        const wrap = document.createElement('div');
        wrap.className = 'shared-preview-load-wrap';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'shared-preview-load-more';
        button.textContent = `Load next ${Math.min(batchSize, pdf.numPages - rendered)} pages`;
        button.addEventListener('click', renderBatch, {once: true});
        wrap.appendChild(button);
        pages.appendChild(wrap);
      }
      busy = false;
    };
    await renderBatch();
  };

  const close = () => {
    if (!modal.classList.contains('is-open')) return;
    cleanup();
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('shared-preview-lock');
    lastFocus?.focus?.();
  };

  const open = async button => {
    cleanup();
    lastFocus = button;
    const data = button.dataset;
    title.textContent = data.title || 'File preview';
    filename.textContent = data.filename || '';
    openTab.href = data.pageUrl || '#';
    download.href = data.downloadUrl || '#';
    detailName.textContent = data.filename || '—';
    detailType.textContent = [data.type, data.mime].filter(Boolean).join(' · ') || '—';
    detailSize.textContent = data.size || '—';
    detailDescription.textContent = data.description || 'No description';
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('shared-preview-lock');
    loader();
    modal.querySelector('.shared-preview-close')?.focus();

    controller = new AbortController();
    const id = ++requestId;
    try {
      switch (data.previewKind) {
        case 'image': await renderImage(data.previewUrl, id); break;
        case 'video': await renderMedia(data.previewUrl, 'video', id); break;
        case 'audio': await renderMedia(data.previewUrl, 'audio', id); break;
        case 'text': await renderText(data.previewUrl, id); break;
        case 'pdf': await renderPdf(data.previewUrl, id); break;
        default: fallback('This format cannot be previewed in the browser, but it is available to download.', data.type, data.downloadUrl);
      }
    } catch (error) {
      if (error?.name === 'AbortError' || id !== requestId) return;
      console.error('Shared folder preview failed:', error);
      fallback(error?.message || 'The preview could not be loaded.', data.type, data.downloadUrl);
    }
  };

  triggers.forEach(button => button.addEventListener('click', () => open(button)));
  modal.querySelectorAll('[data-preview-close]').forEach(button => button.addEventListener('click', close));
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
  });
})();
