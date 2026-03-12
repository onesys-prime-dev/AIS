<?php

namespace App\Services\Client;

use App\Models\Client\EmployeeGovernmentIdModel;
use App\Models\Client\EmployeeModel;
use App\Models\Client\EmployeePositionModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

class EmployeeService
{
    protected $employeeModel;
    protected $govModel;
    protected $posModel;
    protected $userModel;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->govModel = new EmployeeGovernmentIdModel();
        $this->posModel = new EmployeePositionModel();
        $this->userModel = new UserModel();
    }

    public function createEmployee(array $data)
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $email = $data['email'];
            $password = $data['password'];

            if (empty($email) || empty($password)) {
                throw new \Exception('Email and password are required.');
            }

            $existingUser = $this->userModel
                ->where('username', $email)
                ->first();

            if ($existingUser) {
                throw new \Exception('Email already exists.');
            }

            $user = new User([
                'username' => $email,
                'email' => $email,
                'password' => $password,
                'active' => 1,
                'status' => 'active',
            ]);

            if (! $this->userModel->save($user)) {
                throw new \Exception(json_encode($this->userModel->errors()));
            }

            $userId = (int) ($user->id ?? $this->userModel->getInsertID());

            if ($userId <= 0) {
                throw new \Exception('Unable to determine created user.');
            }

            $user->addGroup('employee');

            $employeeNumber = $this->generateEmployeeNumber();

            $employeeId = $this->employeeModel->insert([
                'user_id' => $userId,
                'employee_number' => $employeeNumber,
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'],
                'last_name' => $data['last_name'],
                'birthdate' => $data['dob'],
            ]);

            if (! $employeeId) {
                throw new \Exception(json_encode($this->employeeModel->errors()));
            }

            $this->govModel->insert([
                'employee_id' => $employeeId,
                'sss' => $data['sss'],
                'tin' => $data['tin'],
                'pagibig' => $data['pagibig'],
                'philhealth' => $data['philhealth'],
            ]);

            $this->posModel->insert([
                'employee_id' => $employeeId,
                'department' => $data['department'],
                'position' => $data['position'],
                'employment_type' => $data['employment_type'],
            ]);

            $db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function generateEmployeeNumber()
    {
        $year = date('Y');
        $count = $this->employeeModel->countAll() + 1;

        return 'EMP-' . $year . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    public function updateEmployee($employeeId, array $data)
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $employeeModel = new EmployeeModel();
            $positionModel = new EmployeePositionModel();
            $status = isset($data['status']) ? (int) $data['status'] : 1;

            if (! in_array($status, [0, 1], true)) {
                throw new \InvalidArgumentException('Invalid account status.');
            }

            $employee = $employeeModel->find($employeeId);

            if (! $employee) {
                throw new \RuntimeException('Employee not found.');
            }

            $currentUser = auth()->user();
            $currentUserId = (int) ($currentUser->id ?? 0);

            // Safety guard: prevent currently logged-in user from disabling their own account.
            if (
                $status === 0
                && ! empty($employee['user_id'])
                && (int) $employee['user_id'] === $currentUserId
            ) {
                throw new \RuntimeException('You cannot deactivate your own account while logged in.');
            }

            $employeeModel->update($employeeId, [
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'],
                'last_name' => $data['last_name'],
                'birthdate' => $data['dob'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if (! empty($employee['user_id'])) {
                $this->userModel->update($employee['user_id'], [
                    'active' => $status,
                    'status' => $status === 1 ? 'active' : 'inactive',
                ]);
            }

            $positionModel
                ->where('employee_id', $employeeId)
                ->set([
                    'department' => $data['department'],
                    'position' => $data['position'],
                    'employment_type' => $data['employment_type'],
                ])
                ->update();

            $db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function deleteEmployee($employeeId)
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $employeeModel = new EmployeeModel();
            $govModel = new EmployeeGovernmentIdModel();
            $posModel = new EmployeePositionModel();

            $govModel->where('employee_id', $employeeId)->delete();
            $posModel->where('employee_id', $employeeId)->delete();

            $employee = $employeeModel->find($employeeId);

            if ($employee) {
                $employeeModel->delete($employeeId);

                $userModel = new UserModel();
                $userModel->delete($employee['user_id']);
            }

            $db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}
