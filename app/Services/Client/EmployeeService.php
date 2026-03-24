<?php

namespace App\Services\Client;

use App\Models\Client\EmployeeGovernmentIdModel;
use App\Models\Client\EmployeeModel;
use App\Models\Client\EmployeePositionModel;
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
            $email = trim((string) ($data['email'] ?? ''));
            $password = trim((string) ($data['password'] ?? ''));

            // Validate email and password are provided and not empty
            if (empty($email)) {
                throw new \Exception('Email is required and cannot be empty.');
            }

            if (empty($password)) {
                throw new \Exception('Password is required and cannot be empty.');
            }

            // Validate email format
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Please provide a valid email address.');
            }

            // Check minimum password length
            if (strlen($password) < 8) {
                throw new \Exception('Password must be at least 8 characters long.');
            }

            $existingUser = $this->userModel
                ->where('username', $email)
                ->first();

            if ($existingUser) {
                throw new \Exception('This email is already registered. Please use a different email.');
            }

            // Use Shield's provider flow to guarantee proper email identity creation.
            $users = auth()->getProvider();
            $user = $users->createNewUser([
                'email' => $email,
                'password' => $password,
            ]);

            if (! $users->save($user)) {
                throw new \Exception(json_encode($users->errors()));
            }

            $userId = (int) ($user->id ?? $users->getInsertID());

            if ($userId <= 0) {
                throw new \Exception('Unable to determine created user.');
            }

            // Keep username aligned to email for existing app conventions.
            $this->userModel->update($userId, [
                'username' => $email,
                'active' => 1,
                'status' => 'active',
            ]);

            $savedUser = $this->userModel->find($userId);

            if (! $savedUser) {
                throw new \Exception('Unable to load created user for group assignment.');
            }

            // Ensure normal employees created by payroll staff are in employee group.
            $savedUser->removeGroup('user');
            $savedUser->addGroup('employee');

            if (! $savedUser->inGroup('employee')) {
                throw new \Exception('Failed to assign employee group.');
            }

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

            if ($this->govModel->errors()) {
                throw new \Exception(json_encode($this->govModel->errors()));
            }

            $this->posModel->insert([
                'employee_id' => $employeeId,
                'department' => $data['department'],
                'position' => $data['position'],
                'employment_type' => $data['employment_type'],
            ]);

            if ($this->posModel->errors()) {
                throw new \Exception(json_encode($this->posModel->errors()));
            }

            $db->transCommit();

            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    public function createEmployeeProfileForExistingUser(int $userId, string $role, array $details): void
    {
        $existingEmployee = $this->employeeModel->where('user_id', $userId)->first();
        if ($existingEmployee) {
            return;
        }

        [$firstNameFallback, $middleNameFallback, $lastNameFallback] = $this->extractNameParts(
            (string) ($details['username'] ?? ''),
            (string) ($details['email'] ?? '')
        );

        $firstName = trim((string) ($details['first_name'] ?? '')) ?: $firstNameFallback;
        $middleName = trim((string) ($details['middle_name'] ?? '')) ?: $middleNameFallback;
        $lastName = trim((string) ($details['last_name'] ?? '')) ?: $lastNameFallback;
        $birthdate = trim((string) ($details['birthdate'] ?? '')) ?: '1900-01-01';

        $sss = trim((string) ($details['sss'] ?? '')) ?: 'PENDING';
        $tin = trim((string) ($details['tin'] ?? '')) ?: 'PENDING';
        $pagibig = trim((string) ($details['pagibig'] ?? '')) ?: 'PENDING';
        $philhealth = trim((string) ($details['philhealth'] ?? '')) ?: 'PENDING';

        $department = trim((string) ($details['department'] ?? '')) ?: ($role === 'client_payroll_staff' ? 'Payroll' : 'General');
        $position = trim((string) ($details['position'] ?? '')) ?: ($role === 'client_payroll_staff' ? 'Client Payroll Staff' : 'Employee');
        $employmentType = trim((string) ($details['employment_type'] ?? '')) ?: 'Regular';

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $employeeId = $this->employeeModel->insert([
                'employee_number' => $this->generateEmployeeNumber(),
                'user_id' => $userId,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'birthdate' => $birthdate,
            ]);

            if (! $employeeId) {
                throw new \RuntimeException('Failed to create employee record: ' . json_encode($this->employeeModel->errors()));
            }

            $this->govModel->insert([
                'employee_id' => $employeeId,
                'sss' => $sss,
                'tin' => $tin,
                'pagibig' => $pagibig,
                'philhealth' => $philhealth,
            ]);

            if ($this->govModel->errors()) {
                throw new \RuntimeException('Failed to create employee government IDs: ' . json_encode($this->govModel->errors()));
            }

            $this->posModel->insert([
                'employee_id' => $employeeId,
                'department' => $department,
                'position' => $position,
                'employment_type' => $employmentType,
            ]);

            if ($this->posModel->errors()) {
                throw new \RuntimeException('Failed to create employee position: ' . json_encode($this->posModel->errors()));
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function generateEmployeeNumber()
    {
        $year = date('Y');
        $prefix = 'EMP-' . $year . '-';

        $latestEmployee = $this->employeeModel
            ->select('employee_number')
            ->like('employee_number', $prefix, 'after')
            ->orderBy('employee_number', 'DESC')
            ->first();

        $lastSequence = 0;
        if (! empty($latestEmployee['employee_number'])) {
            $parts = explode('-', (string) $latestEmployee['employee_number']);
            $lastChunk = end($parts);
            $lastSequence = (int) $lastChunk;
        }

        $nextSequence = $lastSequence + 1;

        return $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }

    private function extractNameParts(string $username, string $email): array
    {
        $base = trim($username);

        if ($base === '') {
            $base = trim(strtok($email, '@') ?: 'Employee');
        }

        $parts = preg_split('/\s+/', $base) ?: [];

        if (count($parts) === 0) {
            return ['Employee', null, 'User'];
        }

        if (count($parts) === 1) {
            return [$parts[0], null, 'User'];
        }

        if (count($parts) === 2) {
            return [$parts[0], null, $parts[1]];
        }

        $first = array_shift($parts);
        $last = array_pop($parts);
        $middle = implode(' ', $parts);

        return [$first ?: 'Employee', $middle ?: null, $last ?: 'User'];
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
            ]);

            if (! empty($employee['user_id'])) {
                $this->userModel->update($employee['user_id'], [
                    'active' => $status,
                    'status' => $status === 1 ? 'active' : 'inactive',
                ]);
            }

            // Check if position record exists, if not create it
            $existingPosition = $positionModel
                ->where('employee_id', $employeeId)
                ->first();

            if ($existingPosition) {
                // Update existing position record
                $positionModel
                    ->where('employee_id', $employeeId)
                    ->set([
                        'department' => $data['department'],
                        'position' => $data['position'],
                        'employment_type' => $data['employment_type'],
                    ])
                    ->update();
            } else {
                // Insert new position record if it doesn't exist
                $positionModel->insert([
                    'employee_id' => $employeeId,
                    'department' => $data['department'],
                    'position' => $data['position'],
                    'employment_type' => $data['employment_type'],
                ]);

                if ($positionModel->errors()) {
                    throw new \Exception(json_encode($positionModel->errors()));
                }
            }

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
