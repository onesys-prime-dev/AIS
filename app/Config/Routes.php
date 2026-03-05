<?php

use CodeIgniter\Router\RouteCollection;
use App\Controllers\EcovisAuthController;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('client_portal/dashboard', 'AttendanceController::index', ['filter' => 'session']);
$routes->get('client_portal/time-in', 'AttendanceController::timeIn', ['filter' => 'session']);
$routes->get('client_portal/time-out/(:num)', 'AttendanceController::timeOut/$1', ['filter' => 'session']);

$routes->post('client_portal/request/submit', 'RequestController::store', ['filter' => 'session']);

$routes->get('ecovis-login', [EcovisAuthController::class, 'ecovisLogin'], ['as' => 'ecovis-login']);
$routes->post('ecovis-login', [EcovisAuthController::class, 'attemptEcovisLogin']);

service('auth')->routes($routes);