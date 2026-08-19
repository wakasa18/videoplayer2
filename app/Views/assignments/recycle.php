<!DOCTYPE html><html lang="en"><head>
<title><?= $mode==='archive'?'Assignment Archive':'Assignment Recycle Bin' ?> · Damon's Archive</title>
<?= view('partials/theme_head') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/drive-assignments.v1.css') ?>">
<?= view('partials/drive_theme') ?>
</head><body><div class="twinkle-layer" id="twinkleLayer"></div><div class="wrap recycle-shell">
<a href="<?= base_url('assignments') ?>" class="nav-back">← Assignments</a>
<header><p class="eyebrow"><?= $mode==='archive'?'Completed assignments':'Deleted assignments' ?></p><h1><?= $mode==='archive'?'Assignment Archive':'Recycle Bin' ?></h1><div class="starline"></div></header>
<?php if(session()->getFlashdata('success')): ?><div class="flash success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
<?php if(session()->getFlashdata('error')): ?><div class="flash error"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
<section class="workspace-panel"><div class="panel-head"><h2><?= (int)$count ?> assignment<?= $count===1?'':'s' ?></h2><p class="file-hint"><?= $mode==='archive'?'Archived assignments stay available until you restore or delete them.':'Deleted assignments are permanently removed automatically after 30 days.' ?></p></div>
<div class="recycle-list">
<?php foreach($items as $a): ?><article class="recycle-item"><div><h3><?= esc($a['title']) ?></h3><p><?= esc($a['description']?:'No description') ?></p><p><?= $mode==='archive'?'Archived':'Deleted' ?> <?= esc($mode==='archive'?($a['archived_at']??''):($a['deleted_at']??'')) ?></p></div><div class="recycle-actions">
<form action="<?= base_url('assignments/'.$a['id'].'/'.($mode==='archive'?'unarchive':'restore')) ?>" method="post"><?= csrf_field() ?><button type="submit">Restore</button></form>
<?php if($mode==='recycle'): ?><form action="<?= base_url('assignments/'.$a['id'].'/purge') ?>" method="post" onsubmit="return confirm('Permanently delete this assignment and all of its subtasks, notes, and attachments?')"><?= csrf_field() ?><button type="submit" class="danger">Delete forever</button></form><?php endif; ?>
</div></article><?php endforeach; ?>
<?php if(!$items): ?><div class="empty-mission"><div><?= $mode==='archive'?'Archive is empty':'Recycle Bin is empty' ?></div><p>Nothing is stored here right now.</p></div><?php endif; ?>
</div><?php if($pager): ?><div class="assignment-pagination"><?= $pager->links($mode==='archive'?'assignment_archive':'assignment_recycle','default_full') ?></div><?php endif; ?></section>
</div><?= view('partials/theme_scripts') ?></body></html>
