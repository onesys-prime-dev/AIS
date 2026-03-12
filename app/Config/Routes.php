<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/logout', 'Auth\ClientAuthController::logout');

service('auth')->routes($routes, ['except' => ['register']]);

$routes->get('register', 'Auth\ClientRegisterController::registerView');
$routes->post('register', 'Auth\ClientRegisterController::registerAction');   

$routes->group('client_portal', ['filter' => 'auth'], function($routes){
    $routes->get('dashboard', 'Client\DashboardController::clientDashboard');
    $routes->get('employees', 'Client\EmployeeController::employeeView');
    $routes->post('employees/create', 'Client\EmployeeController::createEmployee');
    $routes->get('employees/get/(:num)', 'Client\EmployeeController::getEmployee/$1');
    $routes->post('employees/update/(:num)', 'Client\EmployeeController::updateEmployee/$1');
    $routes->post('employees/delete/(:num)', 'Client\EmployeeController::deleteEmployee/$1');
});

