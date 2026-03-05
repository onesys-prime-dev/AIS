<?php

use CodeIgniter\Router\RouteCollection;
use App\Controllers\EcovisAuthController;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('employee/dashboard', 'AttendanceController::index', ['filter' => 'session']);
$routes->get('employee/time-in', 'AttendanceController::timeIn', ['filter' => 'session']);
$routes->get('employee/time-out/(:num)', 'AttendanceController::timeOut/$1', ['filter' => 'session']);
$routes->get('ecovis-login', [EcovisAuthController::class, 'ecovisLogin'], ['as' => 'ecovis-login']);
$routes->post('ecovis-login', [EcovisAuthController::class, 'attemptEcovisLogin']);

service('auth')->routes($routes);