(() => {
  'use strict';
  const body = document.body;
  const base = (body.dataset.baseUrl || '').replace(/\/$/, '');
  const readJson = id => { try { return JSON.parse(document.getElementById(id)?.textContent || '[]'); } catch { return []; } };
  const assignmentRows = readJson('assignmentsData');
  const templateRows = readJson('templatesData');
  const assignments = new Map(assignmentRows.map(row => [Number(row.id), row]));
  const templates = new Map(templateRows.map(row => [Number(row.id), row]));
  const csrfNameMeta = document.querySelector('meta[name="csrf-name"]');
  const csrfHashMeta = document.querySelector('meta[name="csrf-hash"]');
  let activeAssignmentId = null;
  let lastFocused = null;

  function csrf() { return { name: csrfNameMeta?.content || '', hash: csrfHashMeta?.content || '' }; }
  function updateCsrf(data) {
    if (data?.csrf?.name && csrfNameMeta) csrfNameMeta.content = data.csrf.name;
    if (data?.csrf?.hash && csrfHashMeta) csrfHashMeta.content = data.csrf.hash;
    document.querySelectorAll(`input[name="${CSS.escape(data?.csrf?.name || csrf().name)}"]`).forEach(input => { input.value = data?.csrf?.hash || csrf().hash; });
  }
  async function api(path, payload = {}, method = 'POST') {
    const url = /^https?:/i.test(path) ? path : base + path;
    let form;
    if (payload instanceof FormData) form = payload;
    else { form = new FormData(); Object.entries(payload).forEach(([key, value]) => { if (Array.isArray(value)) value.forEach(v => form.append(key + '[]', String(v))); else if (value !== undefined && value !== null) form.append(key, String(value)); }); }
    const token = csrf(); if (token.name && !form.has(token.name)) form.append(token.name, token.hash);
    const response = await fetch(url, { method, body: method === 'GET' ? undefined : form, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' });
    let data; try { data = await response.json(); } catch { data = { ok: false, message: 'The server returned an unreadable response.' }; }
    updateCsrf(data);
    if (!response.ok || !data.ok) throw new Error(data.message || 'Request failed.');
    return data;
  }
  function toast(message, error = false, action = null) {
    const stack = document.getElementById('toastStack'); if (!stack) return;
    const node = document.createElement('div'); node.className = 'toast' + (error ? ' error' : '');
    const text=document.createElement('span');text.textContent=message;node.appendChild(text);
    if(action){const button=document.createElement('button');button.type='button';button.textContent=action.label||'Undo';button.style.marginLeft='10px';button.onclick=async()=>{try{await api(action.path);toast('Restored.');location.reload();}catch(e){toast(e.message,true);}node.remove();};node.appendChild(button);}
    stack.appendChild(node); setTimeout(() => node.remove(), action ? 7000 : 4200);
  }
  function escapeHtml(value) { const div = document.createElement('div'); div.textContent = value == null ? '' : String(value); return div.innerHTML; }
  function formatDate(value) { if (!value) return 'No deadline'; const d = new Date(value.includes('T') ? value : value + 'T00:00:00'); return new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' }).format(d); }
  function formatBytes(bytes) { const units = ['B','KB','MB','GB']; let value = Number(bytes)||0, i=0; while(value>=1024&&i<units.length-1){value/=1024;i++;} return (i?value.toFixed(1):Math.round(value))+' '+units[i]; }

  function openModal(modal) {
    if (!modal) return; lastFocused = document.activeElement; modal.classList.add('is-open'); modal.setAttribute('aria-hidden','false'); document.body.style.overflow='hidden';
    const focusable = modal.querySelector('button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),a[href]'); focusable?.focus();
  }
  function closeModal(modal) {
    if (!modal) return; modal.classList.remove('is-open'); modal.setAttribute('aria-hidden','true'); if (!document.querySelector('.modal.is-open')) document.body.style.overflow=''; lastFocused?.focus?.();
    if (modal.id === 'attachmentPreviewModal') document.getElementById('attachmentPreviewFrame').src='about:blank';
  }
  document.addEventListener('click', event => {
    const close = event.target.closest('[data-close-modal]'); if (close) closeModal(close.closest('.modal'));
    if (event.target.classList.contains('modal')) closeModal(event.target);
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') { const modal = document.querySelector('.modal.is-open'); if (modal) closeModal(modal); }
    if (event.key === 'Tab') {
      const modal = document.querySelector('.modal.is-open'); if (!modal) return;
      const list = [...modal.querySelectorAll('button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),a[href]')].filter(el => el.offsetParent !== null);
      if (!list.length) return; const first=list[0], last=list[list.length-1];
      if (event.shiftKey && document.activeElement===first){event.preventDefault();last.focus();} else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
    }
  });

  async function customConfirm(message, danger = false) {
    return new Promise(resolve => {
      const modal = document.createElement('div'); modal.className='modal is-open'; modal.setAttribute('aria-hidden','false');
      modal.innerHTML=`<div class="modal-card" style="max-width:460px"><div class="modal-head"><h2>Confirm action</h2><button class="modal-close" type="button">×</button></div><p style="color:var(--text-dim);line-height:1.6">${escapeHtml(message)}</p><div class="modal-actions"><button class="arcade-button cancel" type="button">Cancel</button><button class="arcade-button ${danger?'danger':'primary'} confirm" type="button">Confirm</button></div></div>`;
      document.body.appendChild(modal); document.body.style.overflow='hidden';
      const finish = value => { modal.remove(); document.body.style.overflow=''; resolve(value); };
      modal.querySelector('.confirm').onclick=()=>finish(true); modal.querySelector('.cancel').onclick=()=>finish(false); modal.querySelector('.modal-close').onclick=()=>finish(false); modal.onclick=e=>{if(e.target===modal)finish(false);};
      modal.querySelector('.confirm').focus();
    });
  }

  const assignmentModal = document.getElementById('assignmentModal');
  const assignmentForm = document.getElementById('assignmentForm');
  function resetAssignmentForm() {
    assignmentForm.reset(); assignmentForm.dataset.id=''; document.getElementById('assignmentModalTitle').textContent='New Assignment'; document.getElementById('assignmentSubmit').textContent='Save assignment'; document.getElementById('subtasksTextWrap').hidden=false; document.getElementById('formStatus').value='to_do'; document.getElementById('formPriority').value='medium'; document.getElementById('formReminder').value='1440';
  }
  function openNewAssignment(templateId = null) {
    resetAssignmentForm();
    if (templateId && templates.has(Number(templateId))) applyTemplate(templates.get(Number(templateId)));
    openModal(assignmentModal);
  }
  function openEditAssignment(id) {
    const a=assignments.get(Number(id)); if(!a){toast('Assignment data is not available on this page.',true);return;}
    resetAssignmentForm(); assignmentForm.dataset.id=String(id); document.getElementById('assignmentModalTitle').textContent='Edit Assignment'; document.getElementById('assignmentSubmit').textContent='Save changes'; document.getElementById('subtasksTextWrap').hidden=true;
    const fields={formTitle:a.title,formDescription:a.description||'',formStatus:a.status||'to_do',formDueDate:a.due_date||'',formDueTime:a.due_time||'',formPriority:a.priority||'medium',formSubject:a.subject_id||0,formRecurrence:a.recurrence||'',formReminder:a.reminder_minutes_before??1440,formLink:a.link_url||'',formCustomReminder:a.custom_reminder_at?String(a.custom_reminder_at).replace(' ','T').slice(0,16):'',formTemplateId:a.template_id||''};
    Object.entries(fields).forEach(([id,value])=>{const el=document.getElementById(id);if(el)el.value=value;}); openModal(assignmentModal);
  }
  function applyTemplate(t) {
    document.getElementById('formTemplateId').value=t.id||''; document.getElementById('formTitle').value=t.title||''; document.getElementById('formDescription').value=t.description||''; document.getElementById('formPriority').value=t.priority||'medium'; document.getElementById('formSubject').value=t.subject_id||0; document.getElementById('formRecurrence').value=t.recurrence||''; document.getElementById('formDueTime').value=t.due_time||''; document.getElementById('formReminder').value=t.reminder_minutes_before??1440; document.getElementById('formLink').value=t.link_url||'';
  }
  document.addEventListener('click', event => {
    if (event.target.closest('[data-open-assignment-modal]')) openNewAssignment();
    const edit=event.target.closest('[data-edit-assignment]'); if(edit) openEditAssignment(edit.dataset.editAssignment);
    const use=event.target.closest('[data-use-template]'); if(use){closeModal(document.getElementById('templateModal'));openNewAssignment(use.dataset.useTemplate);}
  });
  assignmentForm?.addEventListener('submit', async event => {
    event.preventDefault(); const button=document.getElementById('assignmentSubmit'); button.disabled=true; button.textContent='Saving…';
    try { const id=assignmentForm.dataset.id; const data=await api(id?`/assignments/${id}/update`:'/assignments',new FormData(assignmentForm)); toast(data.message); location.reload(); }
    catch(error){toast(error.message,true);button.disabled=false;button.textContent=assignmentForm.dataset.id?'Save changes':'Save assignment';}
  });

  function updateCard(a) {
    if(!a)return; assignments.set(Number(a.id),a); const card=document.querySelector(`.assignment-card[data-assignment-id="${a.id}"]`); if(!card)return;
    [...card.classList].filter(c=>c.startsWith('status-')).forEach(c=>card.classList.remove(c)); card.classList.add('status-'+a.status); card.dataset.status=a.status; card.classList.toggle('is-overdue',!!a.is_overdue);
    const select=card.querySelector('.status-select'); if(select)select.value=a.status;
    const due=card.querySelector('.due-label'); if(due){due.textContent=a.relative_due||'No deadline';due.classList.toggle('overdue',!!a.is_overdue);}
    const progress=card.querySelector('.task-progress span'); if(progress&&a.subtask_progress)progress.style.width=a.subtask_progress.percent+'%';
    const progressCopy=card.querySelector('.progress-copy'); if(progressCopy&&a.subtask_progress)progressCopy.textContent=`${a.subtask_progress.done}/${a.subtask_progress.total} subtasks · ${a.subtask_progress.percent}%`;
  }
  document.addEventListener('change', async event => {
    const select=event.target.closest('.status-select'); if(!select)return;
    const id=Number(select.dataset.statusFor), previous=assignments.get(id)?.status||'to_do'; select.disabled=true;
    try{const data=await api(`/assignments/${id}/status`,{status:select.value});updateCard(data.assignment);toast(data.message);}catch(error){select.value=previous;toast(error.message,true);}finally{select.disabled=false;}
  });
  document.addEventListener('click', async event => {
    const action=event.target.closest('[data-assignment-action]'); if(!action)return;
    const id=Number(action.dataset.id), type=action.dataset.assignmentAction;
    if(['delete','archive'].includes(type) && !(await customConfirm(type==='delete'?'Move this assignment to the Recycle Bin?':'Archive this assignment?',type==='delete')))return;
    try{const path=type==='duplicate'?`/assignments/${id}/duplicate`:`/assignments/${id}/${type}`;const data=await api(path);toast(data.message,false,data.undo?{label:'Undo',path:new URL(data.undo.action,location.origin).pathname}:null);if(type==='duplicate'){location.reload();return;}document.querySelector(`.assignment-card[data-assignment-id="${id}"]`)?.remove();assignments.delete(id);}catch(error){toast(error.message,true);}
  });

  async function persistListOrder() {
    const ids=[...document.querySelectorAll('#assignmentList .assignment-card')].map(card=>Number(card.dataset.assignmentId));
    if(!ids.length)return;
    try{await api('/assignments/reorder',{ids:ids.join(',')});toast('Manual order saved.');}catch(error){toast(error.message,true);}
  }
  document.addEventListener('click',async event=>{
    const move=event.target.closest('[data-move-card]');if(!move)return;
    const card=document.querySelector(`.assignment-card[data-assignment-id="${move.dataset.id}"]`);if(!card)return;
    if(move.dataset.moveCard==='up'&&card.previousElementSibling)card.parentNode.insertBefore(card,card.previousElementSibling);
    if(move.dataset.moveCard==='down'&&card.nextElementSibling)card.parentNode.insertBefore(card.nextElementSibling,card);
    await persistListOrder();
  });
  document.addEventListener('click',event=>{
    const button=event.target.closest('[data-quick-deadline]');if(!button)return;
    const id=Number(button.dataset.quickDeadline),a=assignments.get(id);if(!a)return;
    const modal=document.createElement('div');modal.className='modal is-open';modal.setAttribute('aria-hidden','false');
    modal.innerHTML=`<div class="modal-card" style="max-width:500px"><div class="modal-head"><div><p class="eyebrow">Quick Edit</p><h2>Update deadline</h2></div><button class="modal-close" type="button">×</button></div><form><label>Due date<input type="date" name="due_date" value="${escapeHtml(a.due_date||'')}"></label><label>Due time<input type="time" name="due_time" value="${escapeHtml(a.due_time||'')}"></label><div class="modal-actions"><button class="arcade-button cancel" type="button">Cancel</button><button class="arcade-button primary" type="submit">Save deadline</button></div></form></div>`;
    document.body.appendChild(modal);document.body.style.overflow='hidden';
    const close=()=>{modal.remove();document.body.style.overflow='';};modal.querySelector('.cancel').onclick=close;modal.querySelector('.modal-close').onclick=close;modal.onclick=e=>{if(e.target===modal)close();};
    modal.querySelector('form').onsubmit=async e=>{e.preventDefault();try{const data=await api(`/assignments/${id}/deadline`,new FormData(e.target));assignments.set(id,data.assignment);updateCard(data.assignment);toast(data.message);close();}catch(err){toast(err.message,true);}};
    modal.querySelector('input').focus();
  });

  const detailsModal=document.getElementById('detailsModal');
  function openDetails(id){const a=assignments.get(Number(id));if(!a){toast('Assignment details are not available on this page.',true);return;}activeAssignmentId=Number(id);renderDetails(a);openModal(detailsModal);}
  function renderDetails(a){
    document.getElementById('detailsTitle').textContent=a.title; document.getElementById('detailsEyebrow').textContent=(a.subject_name||a.subject||'General')+' · '+String(a.priority||'medium').toUpperCase();
    const statusOptions={to_do:'To Do',in_progress:'In Progress',blocked:'Blocked',submitted:'Submitted',done:'Done'};
    document.getElementById('detailsSummary').innerHTML=`<span>${escapeHtml(a.relative_due||'No deadline')}</span><span>${escapeHtml(a.priority||'medium')} priority</span><span><select id="detailsStatusSelect">${Object.entries(statusOptions).map(([v,l])=>`<option value="${v}" ${a.status===v?'selected':''}>${l}</option>`).join('')}</select></span>${a.link_url?`<span><a href="${escapeHtml(a.link_url)}" target="_blank" rel="noopener">Open reference ↗</a></span>`:''}`;
    document.getElementById('detailsProgress').textContent=a.subtask_progress?.total?`${a.subtask_progress.done}/${a.subtask_progress.total} · ${a.subtask_progress.percent}%`:'No steps yet';
    renderSubtasks(a);renderNotes(a);renderAttachments(a);document.getElementById('detailsEdit').dataset.editAssignment=String(a.id);
    document.getElementById('detailsStatusSelect')?.addEventListener('change',async e=>{try{const data=await api(`/assignments/${a.id}/status`,{status:e.target.value});assignments.set(Number(a.id),data.assignment);updateCard(data.assignment);renderDetails(data.assignment);toast(data.message);}catch(err){toast(err.message,true);}});
  }
  function renderSubtasks(a){const list=document.getElementById('subtaskList');list.innerHTML=(a.subtasks||[]).map(s=>`<div class="subtask-row ${s.is_done?'done':''}" data-subtask-id="${s.id}"><input type="checkbox" ${s.is_done?'checked':''} data-toggle-subtask="${s.id}"><span class="grow">${escapeHtml(s.title)}</span><div class="row-actions"><button data-delete-subtask="${s.id}">×</button></div></div>`).join('')||'<p class="file-hint">Break the assignment into smaller steps.</p>';}
  function renderNotes(a){const list=document.getElementById('noteList');list.innerHTML=(a.notes||[]).map(n=>`<div class="note-row ${n.is_pinned?'pinned':''}" data-note-id="${n.id}"><div class="grow"><div>${escapeHtml(n.content)}</div><small>${escapeHtml(n.created_at||'')}</small></div><div class="row-actions"><button data-pin-note="${n.id}">${n.is_pinned?'Unpin':'Pin'}</button><button data-delete-note="${n.id}">×</button></div></div>`).join('')||'<p class="file-hint">No notes yet.</p>';}
  function renderAttachments(a){const list=document.getElementById('attachmentList');list.innerHTML=(a.attachments||[]).map(f=>`<div class="attachment-row" data-attachment-id="${f.id}"><div class="grow"><strong>${escapeHtml(f.title)}</strong><small>${escapeHtml(f.original_filename)} · ${formatBytes(f.file_size)}</small></div><button data-preview-file="${f.important_file_id}" data-file-title="${escapeHtml(f.title)}">Preview</button><button data-detach-file="${f.id}">×</button></div>`).join('')||'<p class="file-hint">No vault files attached.</p>';}
  document.addEventListener('click',event=>{const open=event.target.closest('[data-open-details]');if(open&&!event.target.closest('.card-menu,.status-select,.assignment-select'))openDetails(open.dataset.openDetails);});
  document.getElementById('detailsEdit')?.addEventListener('click',()=>{closeModal(detailsModal);openEditAssignment(activeAssignmentId);});
  document.getElementById('subtaskForm')?.addEventListener('submit',async event=>{event.preventDefault();try{const data=await api(`/assignments/${activeAssignmentId}/subtasks`,new FormData(event.target));const a=assignments.get(activeAssignmentId);a.subtasks=[...(a.subtasks||[]),data.subtask];a.subtask_progress=progress(a.subtasks);assignments.set(activeAssignmentId,a);renderDetails(a);updateCard(a);event.target.reset();toast(data.message);}catch(e){toast(e.message,true);}});
  document.addEventListener('click',async event=>{
    const toggle=event.target.closest('[data-toggle-subtask]');const del=event.target.closest('[data-delete-subtask]');if(!toggle&&!del)return;const sid=Number((toggle||del).dataset.toggleSubtask||(toggle||del).dataset.deleteSubtask);try{const data=await api(`/assignments/${activeAssignmentId}/subtasks/${sid}/${toggle?'toggle':'delete'}`);const a=assignments.get(activeAssignmentId);if(toggle)a.subtasks=a.subtasks.map(s=>Number(s.id)===sid?data.subtask:s);else a.subtasks=a.subtasks.filter(s=>Number(s.id)!==sid);a.subtask_progress=data.progress||progress(a.subtasks);assignments.set(activeAssignmentId,a);renderDetails(a);updateCard(a);toast(data.message);}catch(e){toast(e.message,true);}
  });
  document.getElementById('noteForm')?.addEventListener('submit',async event=>{event.preventDefault();try{const data=await api(`/assignments/${activeAssignmentId}/notes`,new FormData(event.target));const a=assignments.get(activeAssignmentId);a.notes=[data.note,...(a.notes||[])];assignments.set(activeAssignmentId,a);renderNotes(a);event.target.reset();toast(data.message);}catch(e){toast(e.message,true);}});
  document.addEventListener('click',async event=>{const pin=event.target.closest('[data-pin-note]');const del=event.target.closest('[data-delete-note]');if(!pin&&!del)return;const nid=Number((pin||del).dataset.pinNote||(pin||del).dataset.deleteNote);try{const data=await api(`/assignments/${activeAssignmentId}/notes/${nid}/${pin?'pin':'delete'}`);const a=assignments.get(activeAssignmentId);if(pin)a.notes=a.notes.map(n=>Number(n.id)===nid?data.note:n);else a.notes=a.notes.filter(n=>Number(n.id)!==nid);assignments.set(activeAssignmentId,a);renderNotes(a);toast(data.message);}catch(e){toast(e.message,true);}});
  document.getElementById('attachmentForm')?.addEventListener('submit',async event=>{event.preventDefault();try{const data=await api(`/assignments/${activeAssignmentId}/attachments`,new FormData(event.target));const a=assignments.get(activeAssignmentId);a.attachments=data.attachments;assignments.set(activeAssignmentId,a);renderAttachments(a);event.target.reset();toast(data.message);}catch(e){toast(e.message,true);}});
  document.addEventListener('click',async event=>{const detach=event.target.closest('[data-detach-file]');if(!detach)return;const linkId=Number(detach.dataset.detachFile);try{const data=await api(`/assignments/${activeAssignmentId}/attachments/${linkId}/delete`);const a=assignments.get(activeAssignmentId);a.attachments=a.attachments.filter(f=>Number(f.id)!==linkId);assignments.set(activeAssignmentId,a);renderAttachments(a);toast(data.message);}catch(e){toast(e.message,true);}});
  document.addEventListener('click',event=>{const preview=event.target.closest('[data-preview-file]');if(!preview)return;document.getElementById('attachmentPreviewTitle').textContent=preview.dataset.fileTitle||'File preview';document.getElementById('attachmentPreviewFrame').src=base+`/files/${preview.dataset.previewFile}/preview`;openModal(document.getElementById('attachmentPreviewModal'));});
  document.addEventListener('click',async event=>{const snooze=event.target.closest('[data-snooze]');if(!snooze)return;try{const data=await api(`/assignments/${activeAssignmentId}/snooze`,{choice:snooze.dataset.snooze});assignments.set(activeAssignmentId,data.assignment);updateCard(data.assignment);renderDetails(data.assignment);toast(data.message);}catch(e){toast(e.message,true);}});
  function progress(rows){const total=rows.length,done=rows.filter(r=>r.is_done).length;return{total,done,percent:total?Math.round(done/total*100):0};}

  // Selection and bulk actions.
  function updateBulk(){const selected=[...document.querySelectorAll('.assignment-select:checked')];const bar=document.getElementById('bulkBar');if(!bar)return;bar.hidden=selected.length===0;document.getElementById('bulkCount').textContent=`${selected.length} selected`;}
  document.addEventListener('change',e=>{if(e.target.classList.contains('assignment-select'))updateBulk();});
  document.getElementById('selectPage')?.addEventListener('change',e=>{document.querySelectorAll('.assignment-select').forEach(c=>c.checked=e.target.checked);updateBulk();});
  document.getElementById('clearSelection')?.addEventListener('click',()=>{document.querySelectorAll('.assignment-select').forEach(c=>c.checked=false);const all=document.getElementById('selectPage');if(all)all.checked=false;updateBulk();});
  document.addEventListener('click',async e=>{const btn=e.target.closest('[data-bulk-action]');if(!btn)return;const ids=[...document.querySelectorAll('.assignment-select:checked')].map(c=>Number(c.value));if(!ids.length)return;if(['delete','archive'].includes(btn.dataset.bulkAction)&&!(await customConfirm(`${btn.dataset.bulkAction==='delete'?'Delete':'Archive'} ${ids.length} selected assignments?`,btn.dataset.bulkAction==='delete')))return;try{const data=await api('/assignments/bulk-action',{ids,action:btn.dataset.bulkAction});toast(data.message);if(['delete','archive'].includes(btn.dataset.bulkAction))ids.forEach(id=>document.querySelector(`.assignment-card[data-assignment-id="${id}"]`)?.remove());else location.reload();updateBulk();}catch(err){toast(err.message,true);}});

  // Board drag and drop.
  let draggedId=null;document.querySelectorAll('.kanban-card').forEach(card=>card.addEventListener('dragstart',()=>{draggedId=Number(card.dataset.assignmentId);}));
  document.querySelectorAll('.kanban-dropzone').forEach(zone=>{zone.addEventListener('dragover',e=>{e.preventDefault();zone.classList.add('drag-active');});zone.addEventListener('dragleave',()=>zone.classList.remove('drag-active'));zone.addEventListener('drop',async e=>{e.preventDefault();zone.classList.remove('drag-active');if(!draggedId)return;const column=zone.closest('[data-board-status]');const status=column.dataset.boardStatus;const card=document.querySelector(`.kanban-card[data-assignment-id="${draggedId}"]`);zone.appendChild(card);try{const data=await api(`/assignments/${draggedId}/status`,{status});assignments.set(draggedId,data.assignment);toast(data.message);}catch(err){toast(err.message,true);location.reload();}});});

  document.addEventListener('click',async event=>{
    const button=event.target.closest('[data-global-action]');if(!button)return;
    const action=button.dataset.globalAction;
    const message=action==='mark-all-done'?'Mark every active assignment as done? Recurring assignments will create their next occurrence.':'Archive every submitted or completed assignment?';
    if(!(await customConfirm(message)))return;
    try{const data=await api('/assignments/'+action);toast(data.message);location.reload();}catch(error){toast(error.message,true);}
  });

  // Remember the preferred display mode when a user returns to Assignments.
  document.querySelectorAll('.view-switch a').forEach(link=>link.addEventListener('click',()=>{const url=new URL(link.href);localStorage.setItem('assignmentView',url.searchParams.get('view')||'list');}));
  const currentParams=new URLSearchParams(location.search);if(!currentParams.has('view')){const savedView=localStorage.getItem('assignmentView');if(savedView&&savedView!=='list'){currentParams.set('view',savedView);location.replace(location.pathname+'?'+currentParams.toString());}}

  // Subject and template managers.
  document.querySelector('[data-open-subjects]')?.addEventListener('click',()=>openModal(document.getElementById('subjectModal')));
  document.querySelector('[data-open-templates]')?.addEventListener('click',()=>openModal(document.getElementById('templateModal')));
  document.getElementById('subjectForm')?.addEventListener('submit',async e=>{e.preventDefault();try{const data=await api('/assignments/subjects',new FormData(e.target));toast(data.message);location.reload();}catch(err){toast(err.message,true);}});
  document.addEventListener('click',async e=>{const btn=e.target.closest('[data-archive-subject]');if(!btn)return;if(!(await customConfirm('Archive this subject? Existing assignments keep their subject.')))return;try{const data=await api(`/assignments/subjects/${btn.dataset.archiveSubject}/archive`);toast(data.message);location.reload();}catch(err){toast(err.message,true);}});
  document.getElementById('templateForm')?.addEventListener('submit',async e=>{e.preventDefault();try{const data=await api('/assignments/templates',new FormData(e.target));toast(data.message);location.reload();}catch(err){toast(err.message,true);}});
  document.addEventListener('click',async e=>{const btn=e.target.closest('[data-delete-template]');if(!btn)return;if(!(await customConfirm('Delete this template?',true)))return;try{const data=await api(`/assignments/templates/${btn.dataset.deleteTemplate}/delete`);toast(data.message);btn.closest('article')?.remove();}catch(err){toast(err.message,true);}});

  // Import preview / confirmation.
  document.querySelector('[data-open-import]')?.addEventListener('click',()=>openModal(document.getElementById('importModal')));
  let importToken='';document.getElementById('importPreviewForm')?.addEventListener('submit',async e=>{e.preventDefault();const button=e.submitter;button.disabled=true;button.textContent='Checking…';try{const data=await api('/assignments/import/preview',new FormData(e.target));importToken=data.token;document.getElementById('importPreview').hidden=false;document.getElementById('importStats').innerHTML=`<div><strong>${data.valid_count}</strong><span>Ready</span></div><div><strong>${data.duplicate_count}</strong><span>Duplicates</span></div><div><strong>${data.error_count}</strong><span>Errors</span></div>`;document.getElementById('importErrors').innerHTML=(data.errors||[]).map(x=>`<div>${escapeHtml(x)}</div>`).join('');toast('Import preview ready.');}catch(err){toast(err.message,true);}finally{button.disabled=false;button.textContent='Preview import';}});
  document.getElementById('confirmImport')?.addEventListener('click',async e=>{e.target.disabled=true;try{const data=await api('/assignments/import/confirm',{token:importToken});toast(data.message);location.reload();}catch(err){toast(err.message,true);e.target.disabled=false;}});

  // Density and search highlighting.
  const wrap=document.querySelector('.assignments-wrap');const density=localStorage.getItem('assignmentDensity');if(density==='compact')wrap?.classList.add('compact');
  document.getElementById('densityToggle')?.addEventListener('click',()=>{const compact=wrap.classList.toggle('compact');localStorage.setItem('assignmentDensity',compact?'compact':'comfortable');});
  const q=new URLSearchParams(location.search).get('q')?.trim();if(q){document.querySelectorAll('.assignment-card h3').forEach(h=>{const text=h.textContent;const i=text.toLowerCase().indexOf(q.toLowerCase());if(i>=0)h.innerHTML=escapeHtml(text.slice(0,i))+`<mark>${escapeHtml(text.slice(i,i+q.length))}</mark>`+escapeHtml(text.slice(i+q.length));});}

  // Close native details menus when clicking elsewhere.
  document.addEventListener('click',e=>document.querySelectorAll('.card-menu[open]').forEach(menu=>{if(!menu.contains(e.target))menu.removeAttribute('open');}));
})();
