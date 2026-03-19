<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\Client\TasksModel;

class EmployeeAccountController extends BaseController
{
    public function index()
    {
        return view('client_portal/employee_account');
    }
}