<?php

namespace App\Controllers\Auth;

use App\Services\Client\EmployeeService;
use CodeIgniter\Shield\Controllers\RegisterController as ShieldRegister;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Validation\Exceptions\ValidationException;
use Throwable;

class ClientRegisterController extends ShieldRegister
{
    public function registerAction(): RedirectResponse
    {
        // Keep track of current session state to choose correct redirect target.
        $wasLoggedIn = auth()->loggedIn();

        if (! setting('Auth.allowRegistration')) {
            return redirect()->back()->withInput()
                ->with('error', lang('Auth.registerDisabled'));
        }

        $users = $this->getUserProvider();
        $rules = $this->getValidationRules();

        if (! $this->validateData($this->request->getPost(), $rules, [], config('Auth')->DBGroup)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $employeeRules = $this->getEmployeeRegistrationRules();
        if (! $this->validateData($this->request->getPost(), $employeeRules, [], config('Auth')->DBGroup)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $allowedPostFields = array_keys($rules);
        $user = $users->createNewUser($this->request->getPost($allowedPostFields));

        // Workaround for email-only registration/login setup.
        if ($user->username === null) {
            $user->username = null;
        }

        try {
            $users->save($user);
        } catch (ValidationException) {
            return redirect()->back()->withInput()->with('errors', $users->errors());
        }

        $user = $users->findById($users->getInsertID());

        if (! $user) {
            return redirect()->back()->withInput()->with('error', 'Unable to create user account.');
        }

        $users->addToDefaultGroup($user);

        $role = (string) $this->request->getPost('role');
        $username = (string) $this->request->getPost('username');
        $email = (string) $this->request->getPost('email');

        if ($user && $role) {
            $user->removeGroup('user');
            $user->addGroup($role);
        }

        if ($user && in_array($role, ['employee', 'client_payroll_staff', 'hr_admin', 'team_head', 'admin', 'superadmin'], true)) {
            try {
                (new EmployeeService())->createEmployeeProfileForExistingUser((int) $user->id, $role, [
                    'username' => $username,
                    'email' => $email,
                    'first_name' => (string) $this->request->getPost('first_name'),
                    'middle_name' => (string) $this->request->getPost('middle_name'),
                    'last_name' => (string) $this->request->getPost('last_name'),
                    'birthdate' => (string) $this->request->getPost('birthdate'),
                    'sss' => (string) $this->request->getPost('sss'),
                    'tin' => (string) $this->request->getPost('tin'),
                    'pagibig' => (string) $this->request->getPost('pagibig'),
                    'philhealth' => (string) $this->request->getPost('philhealth'),
                    'department' => (string) $this->request->getPost('department'),
                    'position' => (string) $this->request->getPost('position'),
                    'employment_type' => (string) $this->request->getPost('employment_type'),
                ]);
            } catch (Throwable $e) {
                log_message('error', 'Registration employee sync failed for user ID {id}: {message}', [
                    'id' => (int) $user->id,
                    'message' => $e->getMessage(),
                ]);

                // Keep data consistent: remove just-created auth user when employee profile sync fails.
                $users->delete((int) $user->id, true);

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'User account created, but employee profile setup failed. Please contact support.');
            }
        }

        // Mirror Shield's default behavior: newly registered users are active.
        $user->activate();
        $users->save($user);

        if ($wasLoggedIn) {
            return redirect()->to('/register')
                ->with('message', 'User registered successfully.');
        }

        return redirect()->to('/login')
            ->with('message', lang('Auth.registerSuccess'));
    }

    public function registerView()
    {
        return view(setting('Auth.views')['register']);
    }

    private function getEmployeeRegistrationRules(): array
    {
        return [
            'role' => [
                'label' => 'Role',
                // Same rule as Access Control: only a Super Admin can create (or change) a Super Admin account.
                'rules' => \App\Libraries\PortalAccess::can('roles')
                    ? (auth()->user() && auth()->user()->inGroup('superadmin')
                        ? 'required|in_list[employee,client_payroll_staff,hr_admin,team_head,admin,superadmin]'
                        : 'required|in_list[employee,client_payroll_staff,hr_admin,team_head,admin]')
                    : 'required|in_list[employee]',
            ],
            'first_name' => [
                'label' => 'First Name',
                'rules' => 'required|min_length[2]|max_length[100]|regex_match[/^[A-Za-z][A-Za-z\s\.\'\-]*$/]',
            ],
            'middle_name' => [
                'label' => 'Middle Name',
                'rules' => 'permit_empty|max_length[100]|regex_match[/^[A-Za-z\s\.\'\-]*$/]',
            ],
            'last_name' => [
                'label' => 'Last Name',
                'rules' => 'required|min_length[2]|max_length[100]|regex_match[/^[A-Za-z][A-Za-z\s\.\'\-]*$/]',
            ],
            'birthdate' => [
                'label' => 'Birthdate',
                'rules' => 'required|valid_date[Y-m-d]',
            ],
            'sss' => [
                'label' => 'SSS',
                'rules' => 'required|regex_match[/^(?:\d{10}|\d{2}-\d{7}-\d)$/]',
            ],
            'tin' => [
                'label' => 'TIN',
                'rules' => 'required|regex_match[/^(?:\d{9}|\d{12}|\d{3}-\d{3}-\d{3}|\d{3}-\d{3}-\d{3}-\d{3})$/]',
            ],
            'pagibig' => [
                'label' => 'Pag-IBIG',
                'rules' => 'required|regex_match[/^(?:\d{12}|\d{4}-\d{4}-\d{4})$/]',
            ],
            'philhealth' => [
                'label' => 'PhilHealth',
                'rules' => 'required|regex_match[/^(?:\d{12}|\d{2}-\d{9}-\d)$/]',
            ],
            'department' => [
                'label' => 'Department',
                'rules' => 'required|min_length[2]|max_length[100]',
            ],
            'position' => [
                'label' => 'Position',
                'rules' => 'required|min_length[2]|max_length[100]',
            ],
            'employment_type' => [
                'label' => 'Employment Type',
                'rules' => 'required|in_list[Regular,Probationary,Contractual,Part-time]',
            ],
        ];
    }
}