<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/logout', 'Auth\ClientAuthController::logout');

// Override Shield's login POST before Shield registers its own, so first-match wins.
// ClientAuthController::loginAction adds the `active` flag check that Shield skips.
$routes->post('login', 'Auth\ClientAuthController::loginAction');

service('auth')->routes($routes, ['except' => ['register']]);

$routes->get('register', 'Auth\ClientRegisterController::registerView');
$routes->post('register', 'Auth\ClientRegisterController::registerAction');   

$routes->group('client_portal', ['filter' => 'auth'], function($routes){
    $routes->get('dashboard', 'Client\DashboardController::index');
    $routes->get('employees', 'Client\EmployeeController::employeeView');
    $routes->post('employees/create', 'Client\EmployeeController::createEmployee');
<<<<<<< HEAD
    $routes->get('employees/get/(:num)', 'Client\EmployeeController::getEmployee/$1');
    $routes->post('employees/update/(:num)', 'Client\EmployeeController::updateEmployee/$1');
    $routes->post('employees/delete/(:num)', 'Client\EmployeeController::deleteEmployee/$1');
});

$routes->get('time-in', 'Client\AttendanceController::timeIn');
$routes->get('time-out/(:num)', 'Client\AttendanceController::timeOut/$1');
$routes->get('toggle-break/(:num)', 'Client\AttendanceController::toggleBreak/$1');
$routes->get('requests/new', 'Client\RequestController::create'); 
$routes->post('requests/store', 'Client\RequestController::store');
$routes->get('requests', 'Client\RequestController::index');
$routes->post('requests/update/(:num)', 'Client\RequestController::updateStatus/$1');
$routes->post('tasks/submit', 'Client\TaskController::submitTask');
$routes->get('tasks/saved', 'Client\TaskController::getSavedTasks');

=======
    $routes->get('time-in', 'Client\AttendanceController::timeIn');
    $routes->get('time-out/(:num)', 'Client\AttendanceController::timeOut/$1');
    $routes->get('toggle-break/(:num)', 'Client\AttendanceController::toggleBreak/$1');
    $routes->get('requests/new', 'Client\RequestController::create'); 
    $routes->post('requests/store', 'Client\RequestController::store');
    $routes->get('requests', 'Client\RequestController::index');
    $routes->post('requests/update/(:num)', 'Client\RequestController::updateStatus/$1');
    $routes->post('tasks/submit', 'Client\TaskController::submitTask');
    $routes->get('tasks/saved', 'Client\TaskController::getSavedTasks');
    $routes->get('timesheets', 'Client\TimesheetController::index');
    });

$routes->group('client_portal', ['namespace' => 'App\Controllers\Client'], function($routes) {
    $routes->get('payroll', 'PayrollController::payrollView');
    $routes->post('payroll/create', 'PayrollController::createPayroll');
    $routes->post('payroll/update/(:num)', 'PayrollController::updatePayroll/$1');
    $routes->get('payroll/delete/(:num)', 'PayrollController::deletePayroll/$1');
    $routes->get('payroll/get/(:num)', 'PayrollController::getPayroll/$1');
});
>>>>>>> 033b898d01dd995a7eb8706bf2b3512e051f0234
