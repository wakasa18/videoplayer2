<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Video::index');

// Video library
$routes->get('videos', 'Video::index');
$routes->post('videos', 'Video::store');
$routes->post('videos/(:num)/delete', 'Video::destroy/$1');
