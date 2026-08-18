<?php
$pageTotal   = (int) ($page['total'] ?? count($files));
$pageCurrent = max(1, (int) ($page['currentPage'] ?? 1));
$pagePerPage = max(1, (int) ($page['perPage'] ?? 20));
$pageFrom    = $pageTotal > 0 ? (($pageCurrent - 1) * $pagePerPage) + 1 : 0;
$pageTo      = $pageTotal > 0 ? min($pageTotal, $pageCurrent * $pagePerPage) : 0;
$clearParams = [];
if ($currentPath) {
    $clearParams['path'] = $currentPath;
}
if (($filters['favorite'] ?? '') === '1') {
    $clearParams['favorite'] = '1';
}
if (($filters['sort'] ?? 'name_asc') !== 'name_asc') {
    $clearParams['sort'] = $filters['sort'];
}
$clearFilterUrl = base_url('files') . ($clearParams ? '?' . http_build_query($clearParams) : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Important Files · Damon's Archive</title>
<?= view('partials/theme_head') ?>
<style>
  .vault-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin:18px 0 20px}.toolbar-links{display:flex;gap:8px;flex-wrap:wrap}.toolbar-link{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text-dim);text-decoration:none;border:1px solid var(--hairline);padding:8px 10px;border-radius:6px;background:var(--surface)}.toolbar-link:hover,.toolbar-link.active{color:var(--cyan);border-color:var(--cyan)}.lock-form{margin:0}.lock-link{background:transparent;color:var(--text-dim);font-family:'JetBrains Mono',monospace;font-size:11px;padding:8px 10px}.lock-link:hover{color:var(--red)}
  .summary-strip{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px}.summary-card{background:var(--surface);border:1px solid var(--hairline);border-radius:8px;padding:14px}.summary-label{font-size:10px;text-transform:uppercase;letter-spacing:.1em;color:var(--text-dim)}.summary-value{font-family:'Cormorant Garamond',serif;font-size:25px;margin-top:3px;color:var(--text)}
  .drive-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0 0 12px}.breadcrumbs{display:flex;align-items:center;gap:6px;min-width:0;overflow:auto;padding:4px 0;scrollbar-width:thin}.breadcrumb-link{flex:none;color:var(--text-dim);text-decoration:none;font-size:12px;padding:6px 8px;border-radius:5px}.breadcrumb-link:hover,.breadcrumb-link.current{color:var(--cyan);background:var(--surface-2)}.breadcrumb-sep{color:#4f5a77;font-size:11px}.view-switch{display:flex;gap:5px;flex:none}.view-button{width:34px;height:32px;background:var(--surface);border:1px solid var(--hairline);color:var(--text-dim);border-radius:6px;padding:0}.view-button:hover,.view-button.active{color:var(--cyan);border-color:var(--cyan)}
  .filters{display:grid;grid-template-columns:2fr repeat(3,1fr);gap:10px;align-items:end;margin-bottom:14px}.filters label{margin:0 0 5px}.filters select{width:100%;background:var(--surface-2);border:1px solid var(--hairline);border-radius:6px;padding:10px;color:var(--text)}
  .layout{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(310px,.7fr);gap:22px}.panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}.panel-head h2{margin:0}.panel-head-actions{display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-wrap:wrap}.result-count{font-size:11px;color:var(--text-dim);font-family:'JetBrains Mono',monospace}.folder-main-action{display:inline-flex;align-items:center;gap:6px;width:auto;margin:0;padding:7px 10px;background:var(--surface-2);border:1px solid var(--hairline);color:var(--text-dim);font:600 10px 'JetBrains Mono',monospace}.folder-main-action:hover{color:var(--cyan);border-color:var(--cyan)}
  .folder-section{margin-bottom:18px}.section-label{font:10px 'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.12em;color:var(--text-dim);margin:0 0 8px}.folder-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}.folder-card-shell{position:relative;min-width:0}.folder-card{display:flex;align-items:center;gap:10px;min-width:0;padding:11px 78px 11px 12px;border:1px solid var(--hairline);background:var(--surface-2);border-radius:8px;color:var(--text);text-decoration:none;transition:.15s}.folder-card:hover{border-color:var(--cyan);transform:translateY(-1px)}.folder-icon{font-size:22px;line-height:1;color:var(--gold)}.folder-copy{min-width:0}.folder-name{display:block;font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.folder-count{display:block;font:9px 'JetBrains Mono',monospace;color:var(--text-dim);margin-top:2px}.folder-card-actions{position:absolute;right:7px;top:50%;transform:translateY(-50%);display:flex;gap:3px}.folder-card-action{width:30px;height:30px;padding:0;border:1px solid transparent;background:transparent;color:var(--text-dim);font-size:15px}.folder-card-action:hover{border-color:var(--cyan);color:var(--cyan);background:var(--surface)}
  .file-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:9px;position:relative;isolation:isolate}.file-item{display:flex;align-items:center;gap:12px;background:var(--surface-2);border:1px solid var(--hairline);border-radius:8px;padding:11px 12px;position:relative;z-index:0;cursor:pointer;animation:fadeInUp .25s ease both;transition:.15s}.file-item:hover,.file-item:focus-visible{border-color:#536b9f;transform:translateY(-1px);outline:none}.file-item.menu-open{z-index:80;border-color:#536b9f}.file-type-badge{flex:none;width:46px;height:40px;border-radius:6px;background:var(--surface);border:1px solid var(--hairline);display:flex;align-items:center;justify-content:center;font-family:'JetBrains Mono',monospace;font-size:9px;font-weight:700;color:var(--cyan);overflow:hidden}.file-meta{flex:1;min-width:0}.file-title-row{display:flex;gap:7px;align-items:center}.file-title{font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.favorite-mark{color:var(--gold);font-size:12px}.original-name{font-family:'JetBrains Mono',monospace;font-size:10px;color:#7581a4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px}.file-desc{font-size:12px;color:var(--text-dim);margin-top:4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.file-sub{display:flex;align-items:center;gap:7px;margin-top:5px;flex-wrap:wrap}.file-sub-text{font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text-dim)}.category-tag{font-size:9px;text-transform:uppercase;letter-spacing:.06em;padding:2px 7px;border-radius:20px;border:1px solid var(--hairline);color:var(--text-dim)}
  .file-list.grid-view{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.grid-view .file-item{display:block;min-height:180px;padding:14px}.grid-view .file-type-badge{width:100%;height:78px;font-size:15px;margin-bottom:12px;background:linear-gradient(145deg,var(--surface),var(--surface-2))}.grid-view .file-desc{display:none}.grid-view .file-sub{margin-top:10px}.grid-view .action-menu{position:absolute;right:8px;top:8px}.grid-view .original-name{margin-top:5px}
  .action-menu{position:relative;z-index:1;flex:none}.action-menu[open]{z-index:90}.action-menu summary{list-style:none;width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:6px;color:var(--text-dim);cursor:pointer;font-size:22px}.action-menu summary::-webkit-details-marker{display:none}.action-menu[open] summary,.action-menu summary:hover{background:var(--surface);color:var(--cyan)}.action-menu-panel{position:absolute;right:0;top:38px;z-index:100;width:190px;max-height:min(60vh,360px);overflow-y:auto;background:#10172b;border:1px solid var(--hairline);border-radius:8px;padding:6px;box-shadow:0 15px 35px rgba(0,0,0,.45)}.action-menu.open-up .action-menu-panel{top:auto;bottom:38px}.action-menu-panel form{margin:0}.menu-action{display:block;width:100%;text-align:left;background:transparent;color:var(--text);text-decoration:none;font-size:12px;font-weight:500;padding:9px 10px;border-radius:5px;margin:0}.menu-action:hover{background:var(--surface-2);color:var(--cyan)}.menu-action.danger:hover{color:#ff9aa1;background:rgba(229,99,107,.09)}
  .empty{border:1px dashed var(--hairline);border-radius:8px;padding:28px;text-align:center;color:var(--text-dim);font-size:13px}.pagination{display:flex;gap:6px;list-style:none;padding:0;margin:18px 0 0;flex-wrap:wrap}.pagination a,.pagination span{display:block;padding:7px 10px;border:1px solid var(--hairline);border-radius:5px;color:var(--text-dim);text-decoration:none;font-size:12px}.pagination .active a,.pagination a:hover{color:var(--cyan);border-color:var(--cyan)}
  select,input[type="number"],input[type="password"]{width:100%;background:var(--surface-2);border:1px solid var(--hairline);border-radius:6px;padding:10px 12px;color:var(--text);font-size:14px;font-family:inherit}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 10px}.upload-location{display:flex;align-items:center;gap:7px;margin:-2px 0 12px;padding:8px 10px;border:1px solid var(--hairline);border-radius:6px;background:var(--surface-2);font:10px 'JetBrains Mono',monospace;color:var(--text-dim);overflow:hidden}.upload-location strong{color:var(--cyan);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.dropzone{margin-top:6px;border:1.5px dashed var(--hairline);border-radius:8px;padding:20px 14px;text-align:center;background:var(--surface-2);transition:.15s}.dropzone.drag,.dropzone.attention{border-color:var(--cyan);background:rgba(95,217,232,.08);transform:scale(1.01);box-shadow:0 0 0 3px rgba(95,217,232,.08)}.dropzone p{margin:0 0 10px;font-size:13px;color:var(--text-dim)}.upload-pickers{display:flex;justify-content:center;gap:8px;flex-wrap:wrap}.picker-button{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-width:120px;padding:9px 12px;border:1px solid var(--hairline);border-radius:6px;background:var(--surface);color:var(--text);font-size:12px;font-weight:600;cursor:pointer;transition:.15s}.picker-button:hover{border-color:var(--cyan);color:var(--cyan);transform:translateY(-1px)}.picker-button.folder{color:var(--gold)}.picker-button.folder:hover{border-color:var(--gold)}.upload-input-hidden{position:absolute!important;width:1px!important;height:1px!important;overflow:hidden!important;clip:rect(0 0 0 0)!important;white-space:nowrap!important;clip-path:inset(50%)!important}.selection-summary{margin-top:9px;font:10px 'JetBrains Mono',monospace;color:var(--text-dim)}.file-hint{font-size:11px;color:var(--text-dim);line-height:1.5}.selected-files{display:flex;flex-direction:column;gap:5px;margin:9px 0}.selected-file{display:flex;justify-content:space-between;gap:10px;font-size:11px;background:var(--surface-2);border:1px solid var(--hairline);padding:7px 9px;border-radius:5px}.selected-file-info{min-width:0}.selected-file-name{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.selected-file-path{display:block;margin-top:2px;font:9px 'JetBrains Mono',monospace;color:#677394;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.selected-file strong{flex:none}.progress-wrap{height:7px;background:var(--surface-2);border-radius:5px;overflow:hidden;margin:10px 0}.progress-bar{height:100%;width:0;background:linear-gradient(90deg,var(--cyan),var(--violet));transition:width .15s}.upload-actions{display:flex;gap:8px}.upload-actions .btn-primary{margin-top:12px}.btn-secondary{margin-top:12px;background:var(--surface-2);border:1px solid var(--hairline);color:var(--text-dim)}.btn-secondary:hover{color:var(--text);border-color:var(--cyan)}
  .modal{position:fixed;inset:0;z-index:100;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(2,4,10,.84);backdrop-filter:blur(5px)}.modal.open{display:flex}.modal-card{width:min(620px,100%);max-height:92vh;overflow:auto;background:var(--surface);border:1px solid var(--hairline);border-radius:12px;padding:20px;box-shadow:0 24px 70px rgba(0,0,0,.6)}.modal-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px}.modal-head h2{margin:0}.modal-close{background:transparent;color:var(--text-dim);font-size:22px;padding:4px 8px}.modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:16px}.modal-actions button{width:auto;margin:0}.danger-button{background:var(--red);color:white}.danger-button:hover{filter:brightness(1.08)}
  .share-help{margin:-4px 0 14px;color:var(--text-dim);font-size:12px;line-height:1.55}.share-options{display:grid;grid-template-columns:1fr 1fr;gap:10px}.share-result{margin-top:16px;padding:13px;border:1px solid rgba(95,217,232,.35);border-radius:8px;background:rgba(95,217,232,.07)}.share-result[hidden]{display:none}.share-result-label{font:9px 'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.1em;color:var(--cyan);margin-bottom:7px}.share-link-row{display:flex;gap:7px}.share-link-row input{min-width:0;flex:1;font:10px 'JetBrains Mono',monospace}.share-copy{flex:none;padding:9px 12px;background:var(--cyan);color:#061019}.share-once{margin:7px 0 0;color:var(--text-dim);font-size:10px}.share-list-title{margin:20px 0 8px;font:10px 'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.1em;color:var(--text-dim)}.share-list{display:flex;flex-direction:column;gap:7px}.share-list-empty{padding:12px;border:1px dashed var(--hairline);border-radius:7px;color:var(--text-dim);font-size:11px;text-align:center}.share-row-item{display:grid;grid-template-columns:1fr auto;gap:10px;align-items:center;padding:10px;border:1px solid var(--hairline);border-radius:7px;background:var(--surface-2)}.share-row-main{min-width:0}.share-row-line{font-size:11px;color:var(--text);line-height:1.45}.share-row-meta{margin-top:3px;font:9px 'JetBrains Mono',monospace;color:var(--text-dim)}.share-status{display:inline-block;margin-right:6px;padding:2px 6px;border:1px solid var(--hairline);border-radius:20px;font:8px 'JetBrains Mono',monospace;text-transform:uppercase}.share-status.active{color:#b9f0da;border-color:rgba(92,211,153,.4)}.share-status.expired,.share-status.used{color:var(--gold);border-color:rgba(242,195,107,.4)}.share-status.revoked{color:#ff9aa1;border-color:rgba(229,99,107,.45)}.share-revoke{padding:7px 9px;border:1px solid rgba(229,99,107,.35);background:transparent;color:#ff9aa1;font-size:10px}.share-revoke:disabled{opacity:.4}.share-loading{padding:12px;color:var(--text-dim);font-size:11px;text-align:center}
  .folder-download-card{width:min(520px,100%);text-align:center}.folder-download-symbol{width:72px;height:82px;margin:0 auto 14px;border:1px solid var(--hairline);border-radius:10px;background:var(--surface-2);display:flex;align-items:center;justify-content:center;font:700 15px 'JetBrains Mono',monospace;color:var(--cyan)}.folder-download-card h2{margin:0 0 7px}.folder-download-card p{margin:0;color:var(--text-dim);font-size:12px;line-height:1.55}.folder-download-progress{height:9px;margin:18px 0 8px;border-radius:8px;overflow:hidden;background:var(--surface-2);border:1px solid var(--hairline)}.folder-download-progress span{display:block;height:100%;width:0;background:linear-gradient(90deg,var(--cyan),var(--violet));transition:width .15s}.folder-download-stats{display:flex;justify-content:space-between;gap:10px;font:10px 'JetBrains Mono',monospace;color:var(--text-dim)}.folder-download-note{margin-top:10px!important;color:var(--gold)!important}.folder-download-actions{display:flex;justify-content:center;gap:8px;margin-top:18px}.folder-download-actions button{width:auto;margin:0}.folder-download-actions .btn-primary{padding:10px 18px}.folder-download-actions .btn-secondary{padding:10px 18px}
  .drive-preview{padding:10px}.drive-preview-card{width:min(1280px,100%);height:min(92vh,900px);display:flex;flex-direction:column;background:#0c1222;border:1px solid var(--hairline);border-radius:12px;overflow:hidden;box-shadow:0 28px 90px rgba(0,0,0,.65)}.preview-topbar{display:flex;align-items:center;gap:9px;padding:10px 12px;border-bottom:1px solid var(--hairline);background:#11182a}.preview-nav{display:flex;gap:5px}.preview-nav button,.preview-top-action{height:34px;min-width:34px;border:1px solid var(--hairline);border-radius:6px;background:var(--surface);color:var(--text-dim);padding:0 10px;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;font-size:12px}.preview-nav button:hover,.preview-top-action:hover{color:var(--cyan);border-color:var(--cyan)}.preview-nav button:disabled{opacity:.35;pointer-events:none}.preview-heading{min-width:0;flex:1}.preview-heading strong{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:13px}.preview-heading span{display:block;font:9px 'JetBrains Mono',monospace;color:var(--text-dim);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.preview-body{display:grid;grid-template-columns:minmax(0,1fr) 290px;min-height:0;flex:1}.preview-stage{position:relative;min-width:0;min-height:0;display:flex;align-items:center;justify-content:center;background:#080c15;overflow:auto}.preview-stage iframe{width:100%;height:100%;border:0;background:#fff}.preview-stage img{display:block;max-width:100%;max-height:100%;object-fit:contain}.preview-stage video{display:block;max-width:100%;max-height:100%;background:#000}.audio-preview,.unsupported-preview{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;text-align:center;padding:30px;color:var(--text-dim)}.audio-preview .big-file-icon,.unsupported-preview .big-file-icon{width:100px;height:112px;border:1px solid var(--hairline);border-radius:12px;background:var(--surface);display:flex;align-items:center;justify-content:center;font:700 18px 'JetBrains Mono',monospace;color:var(--cyan)}.audio-preview audio{width:min(520px,85vw)}.unsupported-preview h3{margin:0;color:var(--text)}.unsupported-preview p{max-width:460px;margin:0;line-height:1.6}.preview-loader{position:absolute;inset:0;z-index:3;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;background:#080c15;color:var(--text-dim);font-size:12px}.preview-loader[hidden]{display:none}.preview-spinner{width:30px;height:30px;border:3px solid #26314b;border-top-color:var(--cyan);border-radius:50%;animation:spinSlow .8s linear infinite}.preview-info{border-left:1px solid var(--hairline);padding:18px;overflow:auto;background:#101626}.preview-info h3{margin:0 0 14px}.detail-row{margin-bottom:14px}.detail-label{display:block;font:9px 'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.1em;color:var(--text-dim);margin-bottom:4px}.detail-value{font-size:12px;color:var(--text);word-break:break-word;line-height:1.5}.preview-info-actions{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:20px}.preview-info-actions a{margin:0;text-align:center;text-decoration:none;padding:9px 8px;border-radius:6px;font-size:11px}.primary-link{background:var(--cyan);color:#061019}.secondary-link{border:1px solid var(--hairline);color:var(--text-dim)}
  .drive-actions{display:flex;align-items:center;gap:7px;flex:none}.quick-upload{height:32px;padding:0 11px;border:1px solid rgba(95,217,232,.4);background:rgba(95,217,232,.08);color:var(--cyan);font:600 10px 'JetBrains Mono',monospace}.quick-upload:hover{border-color:var(--cyan);background:rgba(95,217,232,.14)}
  .filter-state{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:-3px 0 14px;padding:10px 12px;border:1px solid rgba(95,217,232,.25);border-radius:8px;background:rgba(95,217,232,.055)}.filter-state-copy{min-width:0;font-size:11px;color:var(--text-dim)}.filter-state-copy strong{color:var(--text)}.clear-filter-link{flex:none;color:var(--cyan);font:600 10px 'JetBrains Mono',monospace;text-decoration:none;padding:6px 8px;border:1px solid rgba(95,217,232,.3);border-radius:5px}.clear-filter-link:hover{border-color:var(--cyan)}
  .result-count strong{color:var(--text)}.empty-title{display:block;color:var(--text);font-size:15px;margin-bottom:5px}.empty-copy{display:block;line-height:1.55}.empty-actions{display:flex;justify-content:center;gap:8px;margin-top:14px}.empty-action{width:auto;margin:0;padding:8px 11px;border:1px solid var(--hairline);background:var(--surface-2);color:var(--text-dim);font-size:11px}.empty-action:hover{color:var(--cyan);border-color:var(--cyan)}
  .upload-panel{position:sticky;top:18px;align-self:start}.upload-panel.upload-attention{animation:uploadPulse .75s ease}@keyframes uploadPulse{0%,100%{box-shadow:0 1px 0 rgba(255,255,255,.02) inset}45%{box-shadow:0 0 0 3px rgba(95,217,232,.18),0 16px 38px rgba(0,0,0,.35)}}
  .selected-files{max-height:290px;overflow:auto;padding-right:2px}.selected-file{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;position:relative}.selected-file.status-uploading,.selected-file.status-finalizing,.selected-file.status-hashing{border-color:rgba(95,217,232,.45)}.selected-file.status-done{border-color:rgba(92,211,153,.38);background:rgba(92,211,153,.055)}.selected-file.status-error{border-color:rgba(229,99,107,.48);background:rgba(229,99,107,.055)}.selected-file-copy{min-width:0;flex:1}.selected-file-side{display:flex;align-items:center;gap:7px;flex:none}.upload-file-status{font:8px 'JetBrains Mono',monospace;text-transform:uppercase;letter-spacing:.06em;color:var(--text-dim);white-space:nowrap}.status-done .upload-file-status{color:#9de2c2}.status-error .upload-file-status{color:#ff9aa1}.status-uploading .upload-file-status,.status-finalizing .upload-file-status,.status-hashing .upload-file-status{color:var(--cyan)}.selected-file-remove{width:25px;height:25px;padding:0;border:1px solid transparent;background:transparent;color:var(--text-dim);font-size:16px;line-height:1}.selected-file-remove:hover{color:var(--red);border-color:rgba(229,99,107,.35)}.selected-file-remove:disabled{opacity:.25;cursor:not-allowed}.selected-file-progress{grid-column:1/-1;height:3px;border-radius:4px;background:#202a45;overflow:hidden;margin-top:6px}.selected-file-progress span{display:block;width:0;height:100%;background:var(--cyan);transition:width .12s}.selected-file-error{display:block;margin-top:3px;color:#ff9aa1;font-size:9px;white-space:normal}.upload-summary-success{color:#9de2c2}.upload-summary-error{color:#ff9aa1}
  @media(max-width:1050px){.filters{grid-template-columns:1fr 1fr 1fr}.layout{grid-template-columns:1fr}.upload-panel{position:static}.folder-grid,.file-list.grid-view{grid-template-columns:repeat(2,minmax(0,1fr))}}
  @media(max-width:760px){.summary-strip{grid-template-columns:1fr}.filters{grid-template-columns:1fr 1fr}.folder-grid{grid-template-columns:1fr 1fr}.file-list.grid-view{grid-template-columns:1fr 1fr}.preview-body{grid-template-columns:1fr}.preview-info{display:none}.drive-preview-card{height:94vh}.preview-top-action.open-new{display:none}}
  @media(max-width:520px){.filters,.folder-grid,.file-list.grid-view,.form-grid,.share-options{grid-template-columns:1fr}.drive-bar{align-items:flex-start}.drive-actions{width:100%;justify-content:flex-end}.filter-state{align-items:flex-start}.empty-actions{flex-direction:column}.empty-action{width:100%}.panel-head{align-items:flex-start;flex-direction:column}.panel-head-actions{width:100%;justify-content:space-between}.file-item{padding:10px}.grid-view .file-item{min-height:160px}.preview-topbar{gap:5px}.preview-heading span{display:none}.share-link-row{display:grid;grid-template-columns:1fr}.share-copy{width:100%}}
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
    <div class="toolbar-links"><a class="toolbar-link <?= empty($filters['favorite']) ? 'active' : '' ?>" href="<?= base_url('files') ?>">My Drive</a><a class="toolbar-link <?= $filters['favorite'] === '1' ? 'active' : '' ?>" href="<?= base_url('files') . '?favorite=1' ?>">Favorites</a><a class="toolbar-link" href="<?= base_url('files/recycle') ?>">Recycle Bin</a><a class="toolbar-link" href="<?= base_url('files/activity') ?>">Activity Log</a></div>
    <form class="lock-form" action="<?= base_url('files/lock') ?>" method="post"><?= csrf_field() ?><button class="lock-link" type="submit">Lock vault</button></form>
  </div>

  <div class="summary-strip">
    <div class="summary-card"><div class="summary-label">Active files</div><div class="summary-value" id="summaryCount"><?= (int) $summary['file_count'] ?></div></div>
    <div class="summary-card"><div class="summary-label">Storage used</div><div class="summary-value" id="summaryBytes" data-bytes="<?= (int) $summary['total_bytes'] ?>"><?= \App\Models\ImportantFileModel::formatBytes((int) $summary['total_bytes']) ?></div></div>
    <div class="summary-card"><div class="summary-label">Upload limit</div><div class="summary-value"><?= (int) $maxMb ?> MB</div></div>
  </div>

  <div class="drive-bar">
    <nav class="breadcrumbs" aria-label="Folder location">
      <a class="breadcrumb-link <?= $currentPath === null ? 'current' : '' ?>" href="<?= base_url('files') ?>">My Drive</a>
      <?php foreach ($breadcrumbs as $index => $crumb): ?><span class="breadcrumb-sep">/</span><a class="breadcrumb-link <?= $index === count($breadcrumbs)-1 ? 'current' : '' ?>" href="<?= base_url('files') . '?path=' . rawurlencode($crumb['path']) ?>"><?= esc($crumb['label']) ?></a><?php endforeach; ?>
    </nav>
    <div class="drive-actions"><button type="button" class="quick-upload" id="quickUploadBtn">+ Upload</button><div class="view-switch" aria-label="View style"><button type="button" class="view-button active" id="listViewBtn" title="List view">☰</button><button type="button" class="view-button" id="gridViewBtn" title="Grid view">▦</button></div></div>
  </div>

  <form class="filters panel" id="filterForm" method="get" action="<?= base_url('files') ?>">
    <div><label for="filterQ">Search this folder</label><input id="filterQ" type="search" name="q" value="<?= esc($filters['q'], 'attr') ?>" placeholder="Title, filename, category…"></div>
    <div><label for="filterCategory">Category</label><select id="filterCategory" name="category"><option value="">All</option><?php foreach ($categories as $category): ?><option value="<?= esc($category, 'attr') ?>" <?= $filters['category'] === $category ? 'selected' : '' ?>><?= esc($category) ?></option><?php endforeach; ?></select></div>
    <div><label for="filterType">Type</label><select id="filterType" name="type"><option value="">All</option><?php foreach ($extensions as $extension): ?><option value="<?= esc($extension, 'attr') ?>" <?= $filters['type'] === $extension ? 'selected' : '' ?>><?= esc(strtoupper($extension ?: 'FILE')) ?></option><?php endforeach; ?></select></div>
    <div><label for="filterSort">Sort</label><select id="filterSort" name="sort"><option value="name_asc" <?= $filters['sort']==='name_asc'?'selected':'' ?>>Name A–Z</option><option value="name_desc" <?= $filters['sort']==='name_desc'?'selected':'' ?>>Name Z–A</option><option value="newest" <?= $filters['sort']==='newest'?'selected':'' ?>>Newest</option><option value="oldest" <?= $filters['sort']==='oldest'?'selected':'' ?>>Oldest</option><option value="size_desc" <?= $filters['sort']==='size_desc'?'selected':'' ?>>Largest</option><option value="size_asc" <?= $filters['sort']==='size_asc'?'selected':'' ?>>Smallest</option></select></div>
    <input type="hidden" name="path" value="<?= esc((string) ($currentPath ?? ''), 'attr') ?>"><input type="hidden" name="favorite" value="<?= esc($filters['favorite'], 'attr') ?>">
  </form>
  <?php if ($hasFileFilters): ?>
    <div class="filter-state" role="status">
      <div class="filter-state-copy"><strong><?= number_format($pageTotal) ?> matching file<?= $pageTotal === 1 ? '' : 's' ?></strong> in <?= esc($currentPath ?: 'My Drive') ?>. Folder cards are hidden while filters are active.</div>
      <a class="clear-filter-link" href="<?= esc($clearFilterUrl, 'attr') ?>">Clear filters</a>
    </div>
  <?php endif; ?>

  <div class="layout">
    <section class="panel">
      <div class="panel-head">
        <h2><?= $currentPath ? esc(basename(str_replace('\\', '/', $currentPath))) : 'My Drive' ?></h2>
        <div class="panel-head-actions">
          <span class="result-count"><?php if ($pageTotal > 0): ?><strong><?= number_format($pageFrom) ?>–<?= number_format($pageTo) ?></strong> of <?= number_format($pageTotal) ?> files<?php else: ?>0 files<?php endif; ?><?php if (! $hasFileFilters && $filters['favorite'] !== '1'): ?> · <?= count($childFolders) ?> folder<?= count($childFolders) === 1 ? '' : 's' ?><?php endif; ?></span>
          <?php if ($currentPath): ?><button type="button" class="folder-main-action js-share-folder" data-folder-path="<?= esc((string) $currentPath, 'attr') ?>" data-folder-name="<?= esc(basename(str_replace('\\', '/', $currentPath)), 'attr') ?>">&#8599; Share folder</button><?php endif; ?>
          <button type="button" class="folder-main-action js-download-folder" data-folder-path="<?= esc((string) ($currentPath ?? ''), 'attr') ?>" data-folder-name="<?= esc($currentPath ? basename(str_replace('\\', '/', $currentPath)) : 'Important Files', 'attr') ?>">&#8681; <?= $currentPath ? 'Download folder' : 'Download all' ?></button>
        </div>
      </div>
      <?php if ($childFolders !== []): ?>
        <div class="folder-section">
          <p class="section-label">Folders</p>
          <div class="folder-grid">
            <?php foreach ($childFolders as $folder): ?>
              <div class="folder-card-shell">
                <a class="folder-card" href="<?= base_url('files') . '?path=' . rawurlencode($folder['path']) ?>"><span class="folder-icon">&#128193;</span><span class="folder-copy"><span class="folder-name"><?= esc($folder['name']) ?></span><span class="folder-count"><?= (int) $folder['count'] ?> item<?= (int) $folder['count'] === 1 ? '' : 's' ?></span></span></a>
                <div class="folder-card-actions"><button type="button" class="folder-card-action js-share-folder" title="Share <?= esc($folder['name'], 'attr') ?>" aria-label="Share <?= esc($folder['name'], 'attr') ?>" data-folder-path="<?= esc($folder['path'], 'attr') ?>" data-folder-name="<?= esc($folder['name'], 'attr') ?>">&#8599;</button><button type="button" class="folder-card-action js-download-folder" title="Download <?= esc($folder['name'], 'attr') ?>" aria-label="Download <?= esc($folder['name'], 'attr') ?>" data-folder-path="<?= esc($folder['path'], 'attr') ?>" data-folder-name="<?= esc($folder['name'], 'attr') ?>">&#8681;</button></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
      <?php if ($files !== []): ?><p class="section-label">Files</p><?php endif; ?>
      <div id="emptyState" class="empty" <?= ($files !== [] || $childFolders !== []) ? 'style="display:none"' : '' ?>>
        <?php if ($hasFileFilters): ?>
          <span class="empty-title">No files match these filters</span><span class="empty-copy">Try a different search, file type, or category filter.</span><div class="empty-actions"><a class="empty-action" href="<?= esc($clearFilterUrl, 'attr') ?>">Clear filters</a></div>
        <?php else: ?>
          <span class="empty-title">This folder is empty</span><span class="empty-copy">Upload individual files or choose a whole folder while keeping its structure.</span><div class="empty-actions"><button type="button" class="empty-action js-focus-upload">Upload files</button></div>
        <?php endif; ?>
      </div>
      <ul class="file-list" id="fileList"><?php foreach ($files as $f): ?><?= view('files/_file_card', ['f' => $f]) ?><?php endforeach; ?></ul>
      <?= $pager->links('files') ?>
    </section>

    <aside class="panel upload-panel" id="uploadPanel">
      <h2>Add to Vault</h2>
      <div class="upload-location">Upload location: <strong><?= esc($currentPath ?: 'My Drive') ?></strong></div>
      <form id="uploadForm">
        <label for="title">Title</label><input type="text" id="title" maxlength="255" placeholder="Used for one file; multiple files use their filenames">
        <label for="description">Description (optional)</label><textarea id="description" rows="2" maxlength="5000"></textarea>
        <label for="category">Category (optional)</label><input type="text" id="category" maxlength="100" placeholder="ID, Certificate, Tax…">
        <label for="documentDate">Document date (optional)</label><input type="date" id="documentDate">
        <label>Files or folder</label>
        <div class="dropzone" id="dropzone">
          <p>Drop files here, or choose any files/folder</p>
          <div class="upload-pickers"><label class="picker-button" for="fileInput">&#128196; Choose files</label><label class="picker-button folder" for="folderInput">&#128193; Choose folder</label></div>
          <input class="upload-input-hidden" type="file" id="fileInput" multiple>
          <input class="upload-input-hidden" type="file" id="folderInput" multiple webkitdirectory directory>
          <div class="selection-summary" id="selectionSummary">Nothing selected</div>
        </div>
        <p class="file-hint">All file extensions are accepted, up to <?= (int) $maxMb ?> MB per file. PDF, images, audio, video, and text/code files can be previewed. Other formats can still be opened in the viewer and downloaded.</p>
        <div class="selected-files" id="selectedFiles"></div><div id="uploadStatus" class="file-hint" style="display:none" role="status" aria-live="polite"></div><div id="progressWrap" class="progress-wrap" style="display:none"><div id="progressBar" class="progress-bar"></div></div>
        <div class="upload-actions"><button type="submit" class="btn-primary" id="uploadBtn">Add to vault</button><button type="button" class="btn-secondary" id="cancelBtn" style="display:none">Cancel</button></div>
      </form>
    </aside>
  </div>
</div>

<div class="modal drive-preview" id="previewModal" aria-hidden="true"><div class="drive-preview-card"><div class="preview-topbar"><div class="preview-nav"><button type="button" id="previewPrev" aria-label="Previous file">&#8592;</button><button type="button" id="previewNext" aria-label="Next file">&#8594;</button></div><div class="preview-heading"><strong id="previewTitle">File</strong><span id="previewFilename"></span></div><a class="preview-top-action open-new" id="previewOpenLink" href="#" target="_blank" rel="noopener">Open tab</a><a class="preview-top-action" id="previewDownloadTop" href="#" target="_blank" rel="noopener">Download</a><button class="preview-top-action" type="button" data-close-modal aria-label="Close">&#10005;</button></div><div class="preview-body"><div class="preview-stage" id="previewStage"><div class="preview-loader" id="previewLoading"><span class="preview-spinner"></span><span>Loading secure preview…</span></div></div><aside class="preview-info"><h3>File details</h3><div class="detail-row"><span class="detail-label">Name</span><div class="detail-value" id="detailName"></div></div><div class="detail-row"><span class="detail-label">Location</span><div class="detail-value" id="detailFolder"></div></div><div class="detail-row"><span class="detail-label">Type</span><div class="detail-value" id="detailType"></div></div><div class="detail-row"><span class="detail-label">Size</span><div class="detail-value" id="detailSize"></div></div><div class="detail-row"><span class="detail-label">Added</span><div class="detail-value" id="detailDate"></div></div><div class="detail-row"><span class="detail-label">Description</span><div class="detail-value" id="detailDescription"></div></div><div class="preview-info-actions"><a class="primary-link" id="previewDownloadSide" href="#" target="_blank" rel="noopener">Download</a><a class="secondary-link" id="previewOpenSide" href="#" target="_blank" rel="noopener">Open tab</a></div></aside></div></div></div>
<div class="modal" id="editModal" aria-hidden="true"><div class="modal-card"><div class="modal-head"><h2>Edit file details</h2><button class="modal-close" type="button" data-close-modal>&times;</button></div><form id="editForm" method="post"><?= csrf_field() ?><input type="hidden" name="return_to" id="editReturnTo"><label for="editTitle">Title</label><input id="editTitle" name="title" type="text" maxlength="255" required><label for="editDescription">Description</label><textarea id="editDescription" name="description" rows="3" maxlength="5000"></textarea><label for="editCategory">Category</label><input id="editCategory" name="category" type="text" maxlength="100"><label for="editFolderPath">Folder</label><input id="editFolderPath" name="folder_path" type="text" maxlength="1000" placeholder="Folder/Subfolder"><label for="editDocumentDate">Document date (optional)</label><input id="editDocumentDate" name="document_date" type="date"><div class="modal-actions"><button type="button" class="btn-secondary" data-close-modal>Cancel</button><button type="submit" class="btn-primary">Save changes</button></div></form></div></div>
<div class="modal" id="deleteModal" aria-hidden="true"><div class="modal-card"><div class="modal-head"><h2>Move to Recycle Bin?</h2><button class="modal-close" type="button" data-close-modal>&times;</button></div><p id="deleteMessage" class="file-hint"></p><form id="deleteForm" method="post"><?= csrf_field() ?><input type="hidden" name="return_to" id="deleteReturnTo"><div class="modal-actions"><button type="button" class="btn-secondary" data-close-modal>Cancel</button><button type="submit" class="danger-button">Move file</button></div></form></div></div>
<div class="modal" id="shareModal" aria-hidden="true"><div class="modal-card"><div class="modal-head"><h2 id="shareModalTitle">Share file</h2><button class="modal-close" type="button" data-close-modal>&times;</button></div><p class="share-help" id="shareHelp">Create a private link that works without signing in. Anyone who receives the link can open or download the selected item until the link expires or you disable it.</p><form id="shareForm"><div class="share-options"><div><label for="shareDuration">Link expiration</label><select id="shareDuration"><option value="1d">1 day</option><option value="7d" selected>7 days</option><option value="30d">30 days</option><option value="90d">90 days</option><option value="never">Never</option></select></div><div><label for="shareMaxDownloads">Download limit</label><input id="shareMaxDownloads" type="number" min="0" max="10000" value="0"><p class="file-hint" style="margin:5px 0 0">Use 0 for unlimited.</p></div></div><div class="modal-actions"><button type="button" class="btn-secondary" data-close-modal>Cancel</button><button type="submit" class="btn-primary" id="shareCreateBtn">Create link</button></div></form><div class="share-result" id="shareResult" hidden><div class="share-result-label">New share link</div><div class="share-link-row"><input id="shareLinkInput" type="text" readonly><button type="button" class="share-copy" id="shareCopyBtn">Copy</button></div><p class="share-once" id="shareResultNote">For security, copy this link now. The complete token is only displayed when the link is created.</p></div><div class="share-list-title">Link history</div><div class="share-list" id="shareList"><div class="share-loading">Loading links…</div></div></div></div>
<div class="modal" id="folderDownloadModal" aria-hidden="true" data-static="true">
  <div class="modal-card folder-download-card">
    <div class="folder-download-symbol">ZIP</div>
    <h2 id="folderDownloadTitle">Preparing folder</h2>
    <p id="folderDownloadMessage">Creating a secure download list…</p>
    <div class="folder-download-progress"><span id="folderDownloadBar"></span></div>
    <div class="folder-download-stats"><span id="folderDownloadFiles">0 files</span><span id="folderDownloadBytes">0 B</span></div>
    <p class="folder-download-note" id="folderDownloadNote" hidden></p>
    <div class="folder-download-actions">
      <button type="button" class="btn-secondary" id="folderDownloadCancel">Cancel</button>
      <button type="button" class="btn-primary" id="folderDownloadDone" style="display:none">Done</button>
    </div>
  </div>
</div>

<?= view('partials/theme_scripts') ?>
<script>
const CSRF_HEADER=<?= json_encode(csrf_header()) ?>,CSRF_HASH=<?= json_encode(csrf_hash()) ?>,MAX_BYTES=<?= (int) $maxBytes ?>,CURRENT_PATH=<?= json_encode((string) ($currentPath ?? '')) ?>;
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
    return '<div class="share-row-item"><div class="share-row-main"><div class="share-row-line"><span class="share-status ' + escapeHtml(row.status) + '">' + escapeHtml(row.statusLabel) + '</span>' + escapeHtml(expires) + '</div><div class="share-row-meta">' + escapeHtml(limit) + ' · ' + Number(row.viewCount || 0) + ' views · created ' + escapeHtml(formatShareDate(row.createdAt)) + '</div></div><button type="button" class="share-revoke js-revoke-share" data-share-record-id="' + Number(row.id) + '" ' + (active ? '' : 'disabled') + '>' + (active ? 'Disable' : 'Disabled') + '</button></div>';
  }).join('');
}

function shareEndpoint() {
  if (!currentShareTarget) return '';
  return currentShareTarget.type === 'folder'
    ? <?= json_encode(base_url('files/folder-shares')) ?>
    : <?= json_encode(base_url('files')) ?> + '/' + currentShareTarget.id + '/shares';
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
  shareResultNote.textContent = 'For security, copy this link now. The complete token is only displayed when the link is created.';
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
    shareResultNote.textContent = data.message || 'Copy this link now.';
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

shareCopyBtn.addEventListener('click', async () => {
  if (!shareLinkInput.value) return;
  try {
    await navigator.clipboard.writeText(shareLinkInput.value);
    shareCopyBtn.textContent = 'Copied';
  } catch (error) {
    shareLinkInput.focus();
    shareLinkInput.select();
    document.execCommand('copy');
    shareCopyBtn.textContent = 'Copied';
  }
  setTimeout(() => shareCopyBtn.textContent = 'Copy', 1800);
});

shareList.addEventListener('click', async event => {
  const button = event.target.closest('.js-revoke-share');
  if (!button || button.disabled) return;
  if (!confirm('Disable this share link? Anyone using it will immediately lose access.')) return;
  button.disabled = true;
  button.textContent = 'Disabling…';
  try {
    await fetchJson(<?= json_encode(base_url('files/shares')) ?> + '/' + button.dataset.shareRecordId + '/revoke', { method: 'POST', body: '{}' });
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
    await fetchJson(<?= json_encode(base_url('files/cancel-upload')) ?>, {
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
      const sign = await fetchJson(<?= json_encode(base_url('files/sign-upload')) ?>, {
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
      await fetchJson(<?= json_encode(base_url('files/store')) ?>, {
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

const filterForm=document.getElementById('filterForm');let searchTimer;filterForm.querySelectorAll('select').forEach(el=>el.addEventListener('change',()=>filterForm.submit()));document.getElementById('filterQ').addEventListener('input',()=>{clearTimeout(searchTimer);searchTimer=setTimeout(()=>filterForm.submit(),450);});
const fileList=document.getElementById('fileList'),listViewBtn=document.getElementById('listViewBtn'),gridViewBtn=document.getElementById('gridViewBtn');function setView(mode,persist=true){const grid=mode==='grid';fileList.classList.toggle('grid-view',grid);listViewBtn.classList.toggle('active',!grid);gridViewBtn.classList.toggle('active',grid);if(persist){try{localStorage.setItem('vault-view',grid?'grid':'list');}catch(error){}}}listViewBtn.addEventListener('click',()=>setView('list'));gridViewBtn.addEventListener('click',()=>setView('grid'));let savedView='list';try{savedView=localStorage.getItem('vault-view')||'list';}catch(error){}setView(savedView,false);
const previewModal=document.getElementById('previewModal'),previewStage=document.getElementById('previewStage'),previewLoading=document.getElementById('previewLoading'),previewPrev=document.getElementById('previewPrev'),previewNext=document.getElementById('previewNext');let previewFiles=[],previewIndex=0;
function closeActionMenus(except=null){document.querySelectorAll('.action-menu[open]').forEach(menu=>{if(menu!==except)menu.open=false;});}
function openModal(modal){closeActionMenus();modal.classList.add('open');modal.setAttribute('aria-hidden','false');document.body.style.overflow='hidden';}
function closeModal(modal){modal.classList.remove('open');modal.setAttribute('aria-hidden','true');if(!document.querySelector('.modal.open'))document.body.style.overflow='';if(modal.id==='previewModal')previewStage.querySelectorAll('iframe,img,video,audio,.audio-preview,.unsupported-preview').forEach(el=>el.remove());}
function parseFileData(element){try{return JSON.parse(element.dataset.openFile||'{}');}catch(e){return{};}}
function collectPreviewFiles(){const seen=new Set();return[...document.querySelectorAll('.file-item[data-open-file]')].map(parseFileData).filter(file=>file.id&&!seen.has(file.id)&&seen.add(file.id));}
function finishPreview(){previewLoading.hidden=true;}
function renderPreview(file){previewStage.querySelectorAll('iframe,img,video,audio,.audio-preview,.unsupported-preview').forEach(el=>el.remove());previewLoading.hidden=false;document.getElementById('previewTitle').textContent=file.title||file.filename;document.getElementById('previewFilename').textContent=file.filename||'';document.getElementById('detailName').textContent=file.filename||'—';document.getElementById('detailFolder').textContent=file.folder||'My Drive';document.getElementById('detailType').textContent=(file.typeLabel||'FILE')+(file.mimeType?' · '+file.mimeType:'');document.getElementById('detailSize').textContent=file.sizeLabel||'—';document.getElementById('detailDate').textContent=file.dateLabel||'—';document.getElementById('detailDescription').textContent=file.description||'No description';const openUrl=file.previewUrl||file.downloadUrl;['previewOpenLink','previewOpenSide'].forEach(id=>document.getElementById(id).href=openUrl);['previewDownloadTop','previewDownloadSide'].forEach(id=>document.getElementById(id).href=file.downloadUrl);previewPrev.disabled=previewIndex<=0;previewNext.disabled=previewIndex>=previewFiles.length-1;
  if(file.previewKind==='image'){const img=document.createElement('img');img.alt=file.title||file.filename;img.onload=finishPreview;img.onerror=finishPreview;img.src=file.previewUrl;previewStage.appendChild(img);}
  else if(file.previewKind==='video'){const video=document.createElement('video');video.controls=true;video.preload='metadata';video.onloadeddata=finishPreview;video.onerror=finishPreview;video.src=file.previewUrl;previewStage.appendChild(video);}
  else if(file.previewKind==='audio'){const box=document.createElement('div');box.className='audio-preview';box.innerHTML='<div class="big-file-icon"></div><strong></strong>';box.querySelector('.big-file-icon').textContent=file.typeLabel||'AUDIO';box.querySelector('strong').textContent=file.filename;const audio=document.createElement('audio');audio.controls=true;audio.preload='metadata';audio.onloadeddata=finishPreview;audio.onerror=finishPreview;audio.src=file.previewUrl;box.appendChild(audio);previewStage.appendChild(box);}
  else if(file.previewKind==='pdf'||file.previewKind==='text'){const frame=document.createElement('iframe');frame.title='Preview of '+(file.title||file.filename);frame.onload=finishPreview;frame.src=file.previewUrl;previewStage.appendChild(frame);}
  else{const box=document.createElement('div');box.className='unsupported-preview';box.innerHTML='<div class="big-file-icon"></div><h3>Preview unavailable</h3><p>This file type is safely stored, but the browser cannot display its contents here. Download it to open it with the correct application.</p><a class="btn-primary" target="_blank" rel="noopener">Download file</a>';box.querySelector('.big-file-icon').textContent=file.typeLabel||'FILE';box.querySelector('a').href=file.downloadUrl;previewStage.appendChild(box);finishPreview();}}
function openFile(file){previewFiles=collectPreviewFiles();previewIndex=Math.max(0,previewFiles.findIndex(item=>item.id===file.id));renderPreview(previewFiles[previewIndex]||file);openModal(previewModal);}previewPrev.addEventListener('click',()=>{if(previewIndex>0){previewIndex--;renderPreview(previewFiles[previewIndex]);}});previewNext.addEventListener('click',()=>{if(previewIndex<previewFiles.length-1){previewIndex++;renderPreview(previewFiles[previewIndex]);}});
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
async function startFolderDownload(button){if(folderDownloadBusy)return;const path=button.dataset.folderPath||'',folderName=button.dataset.folderName||'folder';let saveHandle=null;try{saveHandle=await chooseFolderSaveHandle(folderName+'.zip');if(saveHandle===false)return;}catch(error){alert(error.message||'The save location could not be opened.');return;}folderDownloadBusy=true;folderDownloadAbort=new AbortController();resetFolderDownloadUi(folderName);openModal(folderDownloadModal);try{const manifest=await fetchJson(<?= json_encode(base_url('files/folder-download-manifest')) ?>,{method:'POST',body:JSON.stringify({path}),signal:folderDownloadAbort.signal});if(!Array.isArray(manifest.files)||manifest.files.length===0)throw new Error('This folder does not contain any downloadable files.');const archiveName=safeZipName(manifest.archiveName||folderName+'.zip');folderDownloadTitle.textContent='Downloading '+folderName;folderDownloadFiles.textContent='0 / '+manifest.fileCount+' files';folderDownloadBytes.textContent='0 B / '+formatBytes(manifest.totalBytes);const notes=[];if(Number(manifest.skippedCount)>0)notes.push(manifest.skippedCount+' unavailable file'+(Number(manifest.skippedCount)===1?' was':'s were')+' skipped.');if(!saveHandle&&Number(manifest.totalBytes)>250*1024*1024)notes.push('Your browser will temporarily keep this ZIP in memory before saving it.');if(notes.length){folderDownloadNote.textContent=notes.join(' ');folderDownloadNote.hidden=false;}const stream=makeZipStream(manifest.files,folderDownloadAbort.signal,updateFolderDownloadProgress);if(saveHandle){const writable=await saveHandle.createWritable();await stream.pipeTo(writable,{signal:folderDownloadAbort.signal});}else{const blob=await new Response(stream,{headers:{'Content-Type':'application/zip'}}).blob();if(folderDownloadAbort.signal.aborted)throw new DOMException('Folder download cancelled.','AbortError');const url=URL.createObjectURL(blob),link=document.createElement('a');link.href=url;link.download=archiveName;link.style.display='none';document.body.appendChild(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),60000);}folderDownloadBar.style.width='100%';folderDownloadFiles.textContent=manifest.fileCount+' / '+manifest.fileCount+' files';folderDownloadBytes.textContent=formatBytes(manifest.totalBytes);folderDownloadTitle.textContent='Folder downloaded';folderDownloadMessage.textContent=archiveName+' is ready.';folderDownloadCancel.style.display='none';folderDownloadDone.style.display='inline-block';try{await fetchJson(<?= json_encode(base_url('files/folder-download-complete')) ?>,{method:'POST',body:JSON.stringify({downloadToken:manifest.downloadToken})});}catch(error){} }catch(error){const cancelled=folderDownloadAbort?.signal.aborted||error?.name==='AbortError';folderDownloadTitle.textContent=cancelled?'Download cancelled':'Folder download failed';folderDownloadMessage.textContent=cancelled?'No ZIP archive was saved.':(error.message||'The folder could not be downloaded.');folderDownloadBar.style.width='0%';folderDownloadCancel.style.display='none';folderDownloadDone.style.display='inline-block';}finally{folderDownloadBusy=false;folderDownloadAbort=null;}}
document.querySelectorAll('.js-download-folder').forEach(button=>button.addEventListener('click',event=>{event.preventDefault();event.stopPropagation();startFolderDownload(button);}));
folderDownloadCancel.addEventListener('click',()=>{if(folderDownloadBusy&&folderDownloadAbort){folderDownloadCancel.disabled=true;folderDownloadCancel.textContent='Cancelling…';folderDownloadMessage.textContent='Stopping the folder download…';folderDownloadAbort.abort();}else closeModal(folderDownloadModal);});
folderDownloadDone.addEventListener('click',()=>closeModal(folderDownloadModal));
document.addEventListener('toggle',e=>{const menu=e.target;if(!(menu instanceof HTMLDetailsElement)||!menu.classList.contains('action-menu'))return;const item=menu.closest('.file-item');if(menu.open){closeActionMenus(menu);item?.classList.add('menu-open');menu.classList.remove('open-up');requestAnimationFrame(()=>{const panel=menu.querySelector('.action-menu-panel');if(panel&&panel.getBoundingClientRect().bottom>window.innerHeight-12)menu.classList.add('open-up');});}else{item?.classList.remove('menu-open');menu.classList.remove('open-up');}},true);
document.addEventListener('click',e=>{const close=e.target.closest('[data-close-modal]');if(close){closeModal(close.closest('.modal'));return;}const preview=e.target.closest('.js-preview');if(preview){e.preventDefault();e.stopPropagation();openFile(parseFileData(preview));return;}const share=e.target.closest('.js-share,.js-share-folder');if(share){e.preventDefault();e.stopPropagation();openShareModal(share);return;}const edit=e.target.closest('.js-edit');if(edit){e.stopPropagation();const f=JSON.parse(edit.dataset.file),form=document.getElementById('editForm');form.action=<?= json_encode(base_url('files')) ?>+'/'+f.id+'/update';document.getElementById('editTitle').value=f.title;document.getElementById('editDescription').value=f.description;document.getElementById('editCategory').value=f.category;document.getElementById('editFolderPath').value=f.folder_path||'';document.getElementById('editDocumentDate').value=f.document_date;openModal(document.getElementById('editModal'));return;}const del=e.target.closest('.js-delete');if(del){e.stopPropagation();document.getElementById('deleteForm').action=del.dataset.deleteUrl;document.getElementById('deleteMessage').textContent='“'+del.dataset.deleteTitle+'” will stay recoverable for 30 days.';openModal(document.getElementById('deleteModal'));return;}const item=e.target.closest('.file-item.js-open-file');if(item&&!e.target.closest('.action-menu')&&!e.target.closest('a,button,form')){openFile(parseFileData(item));return;}if(e.target.classList.contains('modal')&&!e.target.dataset.static){closeModal(e.target);return;}if(!e.target.closest('.action-menu'))closeActionMenus();});
document.addEventListener('keydown',e=>{if(e.key==='Escape'){if(folderDownloadModal.classList.contains('open')&&folderDownloadBusy&&folderDownloadAbort){folderDownloadCancel.click();return;}const openModals=[...document.querySelectorAll('.modal.open')].filter(modal=>!modal.dataset.static);if(openModals.length)openModals.forEach(closeModal);else if(folderDownloadModal.classList.contains('open'))closeModal(folderDownloadModal);else closeActionMenus();return;}const item=e.target.closest?.('.file-item.js-open-file');if(item&&(e.key==='Enter'||e.key===' ')){e.preventDefault();openFile(parseFileData(item));return;}if(previewModal.classList.contains('open')&&e.key==='ArrowLeft'&&!previewPrev.disabled)previewPrev.click();if(previewModal.classList.contains('open')&&e.key==='ArrowRight'&&!previewNext.disabled)previewNext.click();});
</script>
</body>
</html>
