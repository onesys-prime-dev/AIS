<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// =============================== AUTH ===============================
$routes->get ('/logout', 'Auth\ClientAuthController::logout');
$routes->get ('login'  , 'Auth\ClientAuthController::loginView', ['as' => 'login']);
$routes->post('login' , 'Auth\ClientAuthController::loginAction');

$routes->get ('register', 'Auth\ClientRegisterController::registerView');
$routes->post('register', 'Auth\ClientRegisterController::registerAction');   

$routes->get ('face-verify', 'Auth\FaceAuthController::index', ['filter' => 'auth']);
$routes->post('face-verify', 'Auth\FaceAuthController::verify', ['filter' => 'auth']);

$routes->get ('forgot-password', 'Auth\PasswordResetController::forgotPassword', ['as' => 'forgot-password']);
$routes->post('forgot-password', 'Auth\PasswordResetController::sendResetLink' , ['as' => 'forgot-password']);

$routes->get ('reset-password/(:any)', 'Auth\PasswordResetController::resetPassword/$1', ['as' => 'reset-password']);
$routes->post('reset-password/(:any)', 'Auth\PasswordResetController::updatePassword/$1');    


service('auth')->routes($routes, ['except' => ['register', 'login', 'logout']]);

$routes->get('/', function() {
    return redirect()->to('client_portal/dashboard');
});

// ======================== CLIENT PORTAL (authenticated) ========================
$routes->group('client_portal', ['filter' => 'auth'], function($routes){

    $routes->get('dashboard', 'Client\DashboardController::index');

    // -- Employees --
    $routes->get ('employees'               , 'Client\EmployeeController::employeeView');
    $routes->post('employees/create'        , 'Client\EmployeeController::createEmployee');
    $routes->get ('employees/get/(:num)'    , 'Client\EmployeeController::getEmployee/$1');
    $routes->post('employees/update/(:num)' , 'Client\EmployeeController::updateEmployee/$1');
    $routes->post('employees/import'        ,'Client\EmployeeController::importEmployees');
    $routes->get ('employees/export'        ,'Client\EmployeeController::exportEmployees');
    $routes->get ('employees/import-history','Client\EmployeeController::importHistory');

    // -- Access Control (role assignment — a tab inside Employee Management) --
    $routes->post('access-control/update-roles'              , 'Client\AccessControlController::updateRoles');
    $routes->post('access-control/update-role-permissions'    , 'Client\AccessControlController::updateRolePermissions');
    $routes->post('access-control/departments/create'         , 'Client\AccessControlController::createDepartment');
    $routes->post('access-control/departments/rename'         , 'Client\AccessControlController::renameDepartment');
    $routes->post('access-control/departments/deactivate'     , 'Client\AccessControlController::deactivateDepartment');
    $routes->post('access-control/departments/team-heads'     , 'Client\AccessControlController::updateDepartmentTeamHeads');

    // -- Loans --
    // Note: no standalone 'loans' index route — the Loans tab is rendered
    // directly via employee_main.php -> employee_loan.php; a route here
    // previously pointed at LoanController::loanView(), a method that was
    // never implemented (nothing in the UI links to this URL).
    $routes->post('loans/addLoan'                   , 'Client\LoanController::newLoan');
    $routes->get ('loans/loanEmployees'             , 'Client\LoanController::getLoanEmployees');
    $routes->get ('loans/getLoans'                  , 'Client\LoanController::getLoans');
    $routes->get ('loans/viewSchedule/(:num)'       , 'Client\LoanController::viewSchedule/$1');
    $routes->post('loans/schedule/(:num)/(:num)'    , 'Client\LoanController::updateInstallment/$1/$2');
    $routes->post ('loans/cancel/(:num)'             , 'Client\LoanController::cancelLoan/$1');
    $routes->post ('loans/complete/(:num)'           , 'Client\LoanController::completeLoan/$1');
    $routes->post('loans/update/(:num)'             , 'Client\LoanController::updateLoan/$1');
    $routes->get ('loans/settings'                  , 'Client\LoanController::getLoanSettings');
    $routes->post('loans/settings/save'             , 'Client\LoanController::saveLoanSettings');
    $routes->get ('loans/payments/(:num)'           , 'Client\LoanController::getLoanPayments/$1');
    $routes->get ('loans/getEmployeeLoanInfo/(:num)', 'Client\LoanController::getEmployeeLoanInfo/$1');
    $routes->get ('loans/resolveCutoffDate'         , 'Client\LoanController::resolveCutoffDate');

    // -- Face Recognition (admin-managed enrollment; portal.face_recognition) --
    $routes->get ('face-recognition/health'            , 'Client\FaceRecognitionController::health');
    $routes->get ('face-recognition/status/(:num)'      , 'Client\FaceRecognitionController::status/$1');
    $routes->post('face-recognition/enroll/(:num)'      , 'Client\FaceRecognitionController::enroll/$1');
    $routes->post('face-recognition/clear/(:num)'       , 'Client\FaceRecognitionController::clear/$1');
    $routes->post('face-recognition/requirement/(:num)' , 'Client\FaceRecognitionController::setRequirement/$1');

    $routes->get('timesheets/daily-log'     , 'Client\TimesheetController::dailyTimeLog');
    $routes->get('timesheet/flagged'        , 'Client\TimesheetController::flaggedAttendance');
    $routes->get('timesheet/history-details', 'Client\TimesheetController::historyDetails');
    $routes->get('timesheet/export-history' , 'Client\TimesheetController::exportHistory');

    $routes->get('timesheet/tasks/(:num)'        , 'Client\TimesheetController::listTasks/$1');
    $routes->post('timesheet/tasks/(:num)/create', 'Client\TimesheetController::createTask/$1');
    $routes->post('timesheet/tasks/edit/(:num)'  , 'Client\TimesheetController::updateTask/$1');
    $routes->post('timesheet/tasks/delete/(:num)', 'Client\TimesheetController::deleteTask/$1');

    // -- Profile / Account --
    $routes->get ('profile'                       , 'Client\EmployeeAccountController::index');
    $routes->post('profile/photo'                 , 'Client\EmployeeAccountController::updatePhoto');
    $routes->post('profile/identity'              , 'Client\EmployeeAccountController::updateIdentity');
    $routes->post('profile/contact'               , 'Client\EmployeeAccountController::updateContact');
    $routes->post('profile/username'              , 'Client\EmployeeAccountController::updateUsername');
    $routes->post('profile/emergency'             , 'Client\EmployeeAccountController::updateEmergency');
    $routes->post('profile/password'              , 'Client\EmployeeAccountController::updatePassword');
    $routes->post('profile/payslip-password'      , 'Client\EmployeeAccountController::updatePayslipPassword');
    $routes->post('profile/payslip-password/reset', 'Client\EmployeeAccountController::resetPayslipPassword');

    // -- Presence -- 
    $routes->post('presence/heartbeat', 'Client\PresenceController::heartbeat');
    $routes->get ('presence/statuses' , 'Client\PresenceController::statuses');

    // -- Help / support tickets -- 
    $routes->get ('help'              , 'Client\HelpController::index');
    $routes->get ('help/search-index' , 'Client\HelpController::searchIndex');
    $routes->post('help/store'        , 'Client\HelpController::store');
    $routes->post('help/update/(:num)', 'Client\HelpController::update/$1');
});

$routes->group('', ['filter' => 'auth'], function($routes) {
    $routes->post('client_portal/time-in'            , 'Client\AttendanceController::timeIn');
    $routes->post('client_portal/time-out/(:num)'    , 'Client\AttendanceController::timeOut/$1');
    $routes->post('client_portal/toggle-break/(:num)', 'Client\AttendanceController::toggleBreak/$1');

    $routes->get ('client_portal/requests/new'                 , 'Client\RequestController::create');
    $routes->post('client_portal/requests/store'               , 'Client\RequestController::store');
    $routes->get ('client_portal/requests'                     , 'Client\RequestController::index');
    $routes->post('client_portal/requests/update/(:num)'       , 'Client\RequestController::updateStatus/$1');
    $routes->post('client_portal/request-notifications/viewed' , 'Client\RequestNotificationController::viewed');
    $routes->post('client_portal/request-notifications/dismiss', 'Client\RequestNotificationController::dismiss');

    $routes->post('client_portal/announcements/store'                    , 'Client\AnnouncementController::store');
    $routes->get ('client_portal/announcements/download/(:num)'          , 'Client\AnnouncementController::download/$1');
    $routes->post('client_portal/announcements/viewed'                   , 'Client\AnnouncementController::viewed');
    $routes->post('client_portal/announcements/dismiss'                  , 'Client\AnnouncementController::dismiss');
    $routes->post('client_portal/announcements/delete/(:num)'            , 'Client\AnnouncementController::delete/$1');
    $routes->get ('client_portal/announcements/edit/(:num)'              , 'Client\AnnouncementController::edit/$1');
    $routes->post('client_portal/announcements/attachments/delete/(:num)', 'Client\AnnouncementController::deleteAttachment/$1');
    $routes->post('client_portal/announcements/update/(:num)'            , 'Client\AnnouncementController::update/$1');

    $routes->post('client_portal/tasks/submit', 'Client\TaskController::submitTask');
    $routes->get('client_portal/tasks/saved', 'Client\TaskController::getSavedTasks');
    $routes->get('client_portal/tasks/saved-clients', 'Client\TaskController::getSavedClients');
    $routes->get('client_portal/timesheet', 'Client\TimesheetController::index');
    $routes->get('settings/customization', 'SettingsController::customization');
    $routes->post('settings/customization/save', 'SettingsController::saveCustomization');
    $routes->post('client_portal/requests/generate-code', 'Client\RequestController::generateApprovalCode');
    $routes->get ('client_portal/requests/lookup-code'  , 'Client\RequestController::lookupCode');
});

// ================= PAYROLL ROUTES =================
$routes->group('client_portal', ['namespace' => 'App\Controllers\Client', 'filter' => 'auth'], function($routes) {
    $routes->get('payroll', 'PayrollController::payrollView');
    // -- CRUD
    $routes->get ('payroll'                        , 'PayrollController::payrollView');
    $routes->get ('payroll/employees_json'         , 'PayrollController::getEmployees');
    $routes->get ('payroll/employee_details/(:num)', 'PayrollController::getEmployeeDetails/$1');
    $routes->post('payroll/create'                 , 'PayrollController::createPayroll');
    $routes->post('payroll/update/(:num)'          , 'PayrollController::updatePayroll/$1');
    $routes->get ('payroll/delete/(:num)'          , 'PayrollController::deletePayroll/$1');
    $routes->get ('payroll/get/(:num)'             , 'PayrollController::getPayroll/$1');
    $routes->post('payroll/markStatus'             , 'PayrollController::markPayrollStatus');
    $routes->post('payroll/markPaid/(:num)'        , 'PayrollController::markTransactionPaid/$1');

    $routes->post('payroll/compute'        , 'PayrollController::computeAndSave');
    $routes->get ('payroll/generate/(:num)', 'PayrollController::generatePayslip/$1');
    $routes->post('payroll/send/(:num)'    , 'PayrollController::sendPayslip/$1');
    
    $routes->get('payroll/getMyPayslips'         , 'PayrollController::getMyPayslips');
    $routes->get('performance/my-summary'        , 'PerformanceController::mySummary');
    $routes->get('payroll/downloadPayslip/(:num)', 'PayrollController::downloadPayslip/$1');
    $routes->get('payroll/payslipHistory', 'PayrollController::payslipHistory');
    $routes->get('payroll/getPayslipData/(:num)', 'PayrollController::getPayslipData/$1');
    $routes->get('payroll/getPayslipActivity/(:num)', 'PayrollController::getPayslipActivity/$1');
    
    $routes->get ('payroll/getRegisterRows'       , 'PayrollController::getRegisterRows');
    $routes->get ('payroll/getRegisterRawSheet'   , 'PayrollController::getRegisterRawSheet');
    $routes->get ('payroll/exportRegister'        , 'PayrollController::exportRegister');
    $routes->get ('payroll/getPayslipEditData'    , 'PayrollController::getPayslipEditData');
    $routes->post('payroll/savePayslipEditData'   , 'PayrollController::savePayslipEditData');
    $routes->get ('payroll/exportTimesheet'       , 'PayrollController::exportTimesheet');
    $routes->post('payroll/previewTimesheetImport', 'PayrollController::previewTimesheetImport');
    $routes->post('payroll/importTimesheet'       , 'PayrollController::importTimesheet');

    $routes->get('payroll/loanBreakdown/(:num)/(:any)/(:any)','PayrollController::getLoanBreakdown/$1/$2/$3');
});

$routes->group('client_portal/payroll', ['namespace' => 'App\Controllers\Client', 'filter' => 'auth'], function($routes) {
    $routes->get ('getTimesheet/(:num)/(:any)/(:any)', 'PayrollController::getTimesheet/$1/$2/$3');
    $routes->post('importCSV'                        , 'PayrollController::importCSV');
    $routes->get ('exportCSV/(:any)'                 , 'PayrollController::exportCSV/$1');
    $routes->post('saveSettings'                     , 'PayrollController::saveSettings');
    $routes->get ('getSettings'                      , 'PayrollController::getSettings');

    // ========== CRITICAL: Attendance summary endpoint ==========
    $routes->get('attendance-summary/(:num)/(:any)/(:any)', 'PayrollController::getAttendanceSummary/$1/$2/$3');

    // ========== Additional compute & export endpoints ==========
    $routes->post('compute'               , 'PayrollController::computeAndSave');
    $routes->post('savePayslipRecord'     , 'PayrollController::savePayslipRecord');
    $routes->post('sendPdf'               , 'PayrollController::sendPdf');
    $routes->get ('exportRegister'        , 'PayrollController::exportRegister');
    $routes->get ('exportTimesheet'       , 'PayrollController::exportTimesheet');
    $routes->post('previewTimesheetImport', 'PayrollController::previewTimesheetImport');
    $routes->post('importTimesheet'       , 'PayrollController::importTimesheet');

    $routes->get('getPriorHalfWT/(:num)/(:any)/(:any)', 'PayrollController::getPriorHalfWT/$1/$2/$3');
    $routes->get('getPriorHalfWT/(:num)/(:any)'       , 'PayrollController::getPriorHalfWT/$1/$2');

    // ========== SSS Bracket Management ==========
    $routes->get ('sss/years'       , 'PayrollController::sssYears');
    $routes->get ('sss/brackets'    , 'PayrollController::sssBrackets');
    $routes->post('sss/saveBrackets', 'PayrollController::sssSaveBrackets');
    $routes->post('sss/setActive'   , 'PayrollController::sssSetActive');
    $routes->post('sss/createYear'  , 'PayrollController::sssCreateYear');

    // ========== Withholding Tax Bracket Management ==========
    $routes->get ('wtax/years'       , 'PayrollController::wtaxYears');
    $routes->get ('wtax/brackets'    , 'PayrollController::wtaxBrackets');
    $routes->post('wtax/saveBrackets',   'PayrollController::wtaxSaveBrackets');
    $routes->post('wtax/setActive'   , 'PayrollController::wtaxSetActive');
    $routes->post('wtax/createYear'  , 'PayrollController::wtaxCreateYear');

    // ========== Additional De Minimis Categories (BIR) ==========
    $routes->get ('deminimis/categories'      , 'PayrollController::getDeMinimisCategories');
    $routes->post('deminimis/saveCategories'  , 'PayrollController::saveDeMinimisCategories');
    $routes->post('deminimis/restoreDefaults' , 'PayrollController::restoreDeMinimisDefaults');

    // ========== 13th Month Pay & Annualization ==========
    $routes->post('computeYearEnd'   , 'PayrollController::computeYearEnd');
    $routes->get ('getYearEndRecords', 'PayrollController::getYearEndRecords');

    // ========== Holiday Calendar ==========
    $routes->get ('holidays/list'  , 'PayrollController::holidayList');
    $routes->post('holidays/save'  , 'PayrollController::holidaySave');
    $routes->post('holidays/delete', 'PayrollController::holidayDelete');

    // ========== Audit Log ==========
    $routes->get('getAuditLog'     , 'PayrollController::getAuditLog');
    $routes->get('getImportHistory', 'PayrollController::getImportHistory');
});

$routes->group('client_portal', ['namespace' => 'App\Controllers\Client', 'filter' => 'auth'], function($routes) {
    $routes->post('payroll/compute'                , 'PayrollController::computeAndSave');
    $routes->get ('payroll/employees_json'         , 'PayrollController::getEmployees');
    $routes->get ('payroll/employee_details/(:num)', 'PayrollController::getEmployeeDetails/$1');
    $routes->post('payroll/create'                 , 'PayrollController::createPayroll');
    $routes->post('payroll/update/(:num)'          , 'PayrollController::updatePayroll/$1');
    $routes->get ('payroll/delete/(:num)'          , 'PayrollController::deletePayroll/$1');
    $routes->get ('payroll/get/(:num)'             , 'PayrollController::getPayroll/$1');
    $routes->get ('payroll/generate/(:num)'        , 'PayrollController::generatePayslip/$1');
    $routes->post('payroll/send/(:num)'            , 'PayrollController::sendPayslip/$1');

    $routes->get ('payroll'                           , 'PayrollController::payrollView');
    $routes->post('payroll/sendPdf'                   , 'PayrollController::sendPdf');
    $routes->post('payroll/savePayslipRecord'         , 'PayrollController::savePayslipRecord');
    $routes->get ('payroll/getAllPayslips'            , 'PayrollController::getAllPayslips');
    $routes->post('timesheet/update-attendance/(:num)', 'TimesheetController::updateAttendance/$1');
    $routes->get ('timesheet/review-employees'        , 'TimesheetController::reviewEmployees');
    $routes->get ('timesheet/employee/history'        , 'TimesheetController::adminEmployeeTimesheetHistory');
    $routes->get ('timesheet/employee'                , 'TimesheetController::adminEmployeeTimesheet');
    $routes->get ('timesheet/employee/export'         , 'TimesheetController::adminExportEmployeeTimesheet');
    $routes->get ('company-logo'                      , 'PayrollController::getCompanyLogo');
});