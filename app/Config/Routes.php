<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/logout', 'Auth\ClientAuthController::logout');
$routes->post('login', 'Auth\ClientAuthController::loginAction');

service('auth')->routes($routes, ['except' => ['register']]);

$routes->get('register', 'Auth\ClientRegisterController::registerView');
$routes->post('register', 'Auth\ClientRegisterController::registerAction');   

$routes->group('client_portal', ['filter' => 'auth'], function($routes){
    $routes->get('dashboard', 'Client\DashboardController::index');
    $routes->get('employees', 'Client\EmployeeController::employeeView');
    $routes->post('employees/create', 'Client\EmployeeController::createEmployee');
    $routes->get('employees/get/(:num)', 'Client\EmployeeController::getEmployee/$1');
    $routes->post('employees/update/(:num)', 'Client\EmployeeController::updateEmployee/$1');
    $routes->post('employees/delete/(:num)', 'Client\EmployeeController::deleteEmployee/$1');
    $routes->post('timesheets/update/(:num)', 'Client\TimesheetController::update/$1');
    $routes->get('timesheets/delete/(:num)', 'Client\TimesheetController::delete/$1');
    $routes->get('profile', 'Client\EmployeeAccountController::index');
    $routes->post('profile/photo', 'Client\EmployeeAccountController::updatePhoto');
    $routes->post('profile/identity', 'Client\EmployeeAccountController::updateIdentity');
    $routes->post('profile/contact', 'Client\EmployeeAccountController::updateContact');
    $routes->post('profile/username', 'Client\EmployeeAccountController::updateUsername');
    $routes->post('profile/emergency', 'Client\EmployeeAccountController::updateEmergency');
    $routes->post('profile/password', 'Client\EmployeeAccountController::updatePassword');
    $routes->post('presence/heartbeat', 'Client\PresenceController::heartbeat');
    $routes->get('presence/statuses',   'Client\PresenceController::statuses');
});

$routes->get('client_portal/time-in', 'Client\AttendanceController::timeIn');
$routes->get('client_portal/time-out/(:num)', 'Client\AttendanceController::timeOut/$1');
$routes->get('client_portal/toggle-break/(:num)', 'Client\AttendanceController::toggleBreak/$1');
$routes->get('client_portal/requests/new', 'Client\RequestController::create'); 
$routes->post('client_portal/requests/store', 'Client\RequestController::store');
$routes->get('client_portal/requests', 'Client\RequestController::index');
$routes->post('client_portal/requests/update/(:num)', 'Client\RequestController::updateStatus/$1');
$routes->post('client_portal/announcements/store', 'Client\AnnouncementController::store');
$routes->post('client_portal/tasks/submit', 'Client\TaskController::submitTask');
$routes->get('client_portal/tasks/saved', 'Client\TaskController::getSavedTasks');
$routes->get('client_portal/timesheet', 'Client\TimesheetController::index');
$routes->get('settings/customization', 'SettingsController::customization');

$routes->group('client_portal', ['namespace' => 'App\Controllers\Client'], function($routes) {
    $routes->get('payroll', 'PayrollController::payrollView');
    $routes->get('payroll/employees_json', 'PayrollController::getEmployees');
    $routes->post('payroll/create', 'PayrollController::createPayroll');
    $routes->post('payroll/update/(:num)', 'PayrollController::updatePayroll/$1');
    $routes->get('payroll/delete/(:num)', 'PayrollController::deletePayroll/$1');
    $routes->get('payroll/get/(:num)', 'PayrollController::getPayroll/$1');
});