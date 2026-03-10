<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\Client\EmployeeModel;
use App\Models\Client\EmployeeGovernmentIdModel;
use App\Models\Client\EmployeePositionModel;

use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Entities\User;

class EmployeeController extends BaseController 
{

    public function employeeView()
    {
        $employeeModel = new EmployeeModel();
        $data['employees'] = $employeeModel->findAll();
        return view('client_portal/employee_management', $data);
    }

    public function createEmployee()
    {
        log_message('info', '=== Starting employee creation ===');
        
        $db = \Config\Database::connect();
        $db->transBegin();

        try {

            $userModel     = new UserModel();
            $employeeModel = new EmployeeModel();
            $govModel      = new EmployeeGovernmentIdModel();
            $posModel      = new EmployeePositionModel();

            $email = $this->request->getPost('email');
            $password = $this->request->getPost('password');
            
            log_message('info', 'Email: ' . $email);

            $first_name = $this->request->getPost('first_name');
            $middle_name = $this->request->getPost('middle_name');
            $last_name = $this->request->getPost('last_name');
            $dob = $this->request->getPost('dob');
            
            $sss = $this->request->getPost('sss');
            $tin = $this->request->getPost('tin');
            $pagibig = $this->request->getPost('pagibig');
            $philhealth = $this->request->getPost('philhealth');
            $department = $this->request->getPost('department');
            $position = $this->request->getPost('position');

            //Create User Account
            $user = new User([
                'username' => $email,
                'email'    => $email,
                'password' => $password, 
                'active'   => 1
            ]);

            log_message('info', 'Attempting to save user...');
            
            if (! $userModel->save($user)) {
                log_message('error', 'User save failed: ' . json_encode($userModel->errors()));
                throw new \Exception(json_encode($userModel->errors()));
            }

            $userId = $userModel->getInsertID();
            log_message('info', 'User created with ID: ' . $userId);

            //Assign Employee Role
            $user = $userModel->find($userId);
            $user->addGroup('employee');
          
            //Generate Employee Number
            $employeeNumber = $this->generateEmployeeNumber();
            log_message('info', 'Generated employee number: ' . $employeeNumber);

            //Insert Employee Record
            $employeeId = $employeeModel->insert([
                'user_id'         => $userId,
                'employee_number' => $employeeNumber,
                'first_name'      => $first_name,
                'middle_name'     => $middle_name,
                'last_name'       => $last_name,
                'birthdate'       => $dob,
            ]);

            log_message('info', 'Employee insert result: ' . ($employeeId ? $employeeId : 'false'));
            
            if (! $employeeId) {
                log_message('error', 'Employee insert failed: ' . json_encode($employeeModel->errors()));
                throw new \Exception(json_encode($employeeModel->errors()));
            }

            //Government IDs
            $govInserted = $govModel->insert([
                'employee_id' => $employeeId,
                'sss'         => $sss,
                'tin'         => $tin,
                'pagibig'     => $pagibig,
                'philhealth'  => $philhealth
            ]);

            if (! $govInserted) {
                throw new \Exception(json_encode($govModel->errors()));
            }

           // Employee Position
            $posInserted = $posModel->insert([
                'employee_id' => $employeeId,
                'department'  => $department,
                'position'    => $position
            ]);

            if (! $posInserted) {
                throw new \Exception(json_encode($posModel->errors()));
            }

            //COMMIT TRANSACTION
            $db->transCommit();
            log_message('info', 'Transaction committed successfully');

            return redirect()->back()->with('success', 'Employee created successfully');

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Exception caught: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    //GENERATE EMPLOYEE NUMBER
    private function generateEmployeeNumber()
    {
        $year = date('Y');
        $employeeModel = new EmployeeModel();
        $count = $employeeModel->countAll() + 1;
        return 'EMP-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

}