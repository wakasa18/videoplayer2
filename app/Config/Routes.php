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
$routes->post('assignments', 'Assignments::store');
$routes->post('assignments/(:num)/toggle', 'Assignments::toggle/$1');
$routes->post('assignments/(:num)/delete', 'Assignments::destroy/$1');
