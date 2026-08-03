<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// Video library
$routes->get('videos', 'Video::index');
$routes->post('videos/sign-upload', 'Video::signUpload');
$routes->post('videos', 'Video::store');
$routes->post('videos/(:num)/delete', 'Video::destroy/$1');

// Pictures (placeholder)
$routes->get('pictures', 'Pictures::index');

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
$routes->post('assignments/(:num)/update', 'Assignments::update/$1');
$routes->post('assignments/(:num)/toggle', 'Assignments::toggle/$1');
$routes->post('assignments/(:num)/delete', 'Assignments::destroy/$1');
$routes->post('assignments/(:num)/restore', 'Assignments::restore/$1');
$routes->post('assignments/(:num)/snooze', 'Assignments::snooze/$1');

// Deadline reminder cron (see vercel.json "crons")
$routes->get('cron/check-deadlines', 'Cron::checkDeadlines');
$routes->get('cron/check-deadlines/test', 'Cron::test');
