<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Update v2
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('mdarc', 'Home::index');
$routes->post('mdarc-post', 'Home::mdarcPost');
