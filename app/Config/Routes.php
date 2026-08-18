<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Website owner authentication
$routes->get('login', 'Auth::index');
$routes->post('login', 'Auth::login');
$routes->post('logout', 'Auth::logout');

// Public file-sharing links. These routes intentionally bypass website login.
$routes->get('share/(:segment)', 'SharedFiles::show/$1');
$routes->get('share/(:segment)/preview', 'SharedFiles::preview/$1');
$routes->get('share/(:segment)/download', 'SharedFiles::download/$1');
$routes->get('share/(:segment)/file/(:num)', 'SharedFiles::folderFile/$1/$2');
$routes->get('share/(:segment)/file/(:num)/preview', 'SharedFiles::folderFilePreview/$1/$2');
$routes->get('share/(:segment)/file/(:num)/download', 'SharedFiles::folderFileDownload/$1/$2');
$routes->get('share/(:segment)/folder-manifest', 'SharedFiles::folderManifest/$1');

$routes->get('/', 'Home::index');

// Video library
$routes->get('videos', 'Video::index');
$routes->post('videos/sign-upload', 'Video::signUpload');
$routes->post('videos', 'Video::store');
$routes->post('videos/(:num)/delete', 'Video::destroy/$1');

// Pictures (placeholder)
$routes->get('pictures', 'Pictures::index');

// Important Files (password-gated)
$routes->get('files', 'Files::index');
$routes->get('files/gate', 'Files::gate');
$routes->get('files/recycle', 'Files::recycle');
$routes->get('files/activity', 'Files::activity');
$routes->post('files/unlock', 'Files::unlock');
$routes->post('files/lock', 'Files::lock');
$routes->post('files/sign-upload', 'Files::signUpload');
$routes->post('files/store', 'Files::store');
$routes->post('files/cancel-upload', 'Files::cancelUpload');
$routes->post('files/folder-download-manifest', 'Files::folderDownloadManifest');
$routes->post('files/folder-download-complete', 'Files::folderDownloadComplete');
$routes->get('files/(:num)/preview', 'Files::preview/$1');
$routes->get('files/(:num)/download', 'Files::download/$1');
$routes->get('files/(:num)/shares', 'Files::shares/$1');
$routes->post('files/(:num)/shares', 'Files::createShare/$1');
$routes->get('files/folder-shares', 'Files::folderShares');
$routes->post('files/folder-shares', 'Files::createFolderShare');
$routes->post('files/shares/(:num)/revoke', 'Files::revokeShare/$1');
$routes->post('files/(:num)/update', 'Files::update/$1');
$routes->post('files/(:num)/favorite', 'Files::toggleFavorite/$1');
$routes->post('files/(:num)/delete', 'Files::destroy/$1');
$routes->post('files/(:num)/restore', 'Files::restore/$1');
$routes->post('files/(:num)/purge', 'Files::purge/$1');

// Others hub + its placeholder sections
$routes->get('others', 'Others::index');
$routes->get('notes', 'Notes::index');

// Assignments
$routes->get('assignments', 'Assignments::index');
$routes->get('assignments/export', 'Assignments::export');
$routes->post('assignments', 'Assignments::store');
$routes->post('assignments/import', 'Assignments::import');
$routes->post('assignments/clear-completed', 'Assignments::clearCompleted');
$routes->post('assignments/mark-all-done', 'Assignments::markAllDone');
$routes->post('assignments/bulk-undo', 'Assignments::bulkUndo');
$routes->post('assignments/reorder', 'Assignments::reorder');
$routes->post('assignments/(:num)/update', 'Assignments::update/$1');
$routes->post('assignments/(:num)/toggle', 'Assignments::toggle/$1');
$routes->post('assignments/(:num)/delete', 'Assignments::destroy/$1');
$routes->post('assignments/(:num)/restore', 'Assignments::restore/$1');
$routes->post('assignments/(:num)/snooze', 'Assignments::snooze/$1');
$routes->post('assignments/(:num)/notes', 'Assignments::addNote/$1');

// Deadline reminder cron (see vercel.json "crons")
$routes->get('cron/check-deadlines', 'Cron::checkDeadlines');
$routes->get('cron/check-deadlines/test', 'Cron::test');
$routes->get('cron/weekly-digest', 'Cron::weeklyDigest');
$routes->get('cron/weekly-digest/test', 'Cron::weeklyDigestTest');
$routes->get('cron/files-maintenance', 'FileVaultCron::maintenance');
$routes->get('cron/files-maintenance/test', 'FileVaultCron::maintenance');
