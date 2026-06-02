<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\Client\LeaveBalanceModel;
use App\Services\Client\AccountService;

class EmployeeAccountController extends BaseController
{
    protected AccountService $accountService;

    public function __construct()
    {
        $this->accountService = new AccountService();
    }

    public function index()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->to('/login');
        }

        $viewData = $this->accountService->getAccountViewData((int) $user->id);

        // Resolve employee_id from the employment data already fetched by AccountService,
        // falling back to a direct DB lookup if needed
        $employeeId = (int) ($viewData['employment']['employee_id'] ?? 0);

        if ($employeeId > 0) {
            $leaveBalanceModel        = new LeaveBalanceModel();
            $viewData['leaveBalance'] = $leaveBalanceModel->getBalance($employeeId, 'leave', (int) date('Y'));
        } else {
            $viewData['leaveBalance'] = null;
        }

        return view('client_portal/employee_account', $viewData);
    }

    public function updateContact()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->to('/login');
        }

        $rules = [
            'secondary_email' => 'permit_empty|valid_email|max_length[255]',
            'mobile_number' => 'permit_empty|max_length[30]',
            'address' => 'permit_empty|max_length[500]',
        ];

        if (! $this->validateData($this->request->getPost(), $rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $updated = $this->accountService->updateContact((int) $user->id, [
            'secondary_email' => trim((string) $this->request->getPost('secondary_email')),
            'mobile_number' => trim((string) $this->request->getPost('mobile_number')),
            'address' => trim((string) $this->request->getPost('address')),
        ]);

        if (! $updated) {
            return redirect()->back()->with('error', 'Unable to update contact information.');
        }

        return redirect()->back()->with('success', 'Contact information updated successfully.');
    }

    public function updateUsername()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->to('/login');
        }

        $rules = [
            'username' => 'required|min_length[3]|max_length[50]|regex_match[/^[A-Za-z0-9._-]+$/]',
        ];

        if (! $this->validateData($this->request->getPost(), $rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $result = $this->accountService->updateUsername(
            (int) $user->id,
            trim((string) $this->request->getPost('username'))
        );

        if (! $result['ok']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', 'Username updated successfully.');
    }

    public function updateIdentity()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->to('/login');
        }

        $rules = [
            'full_name' => 'required|max_length[255]',
            'username' => 'required|min_length[3]|max_length[50]|regex_match[/^[A-Za-z0-9._-]+$/]',
            'birthdate' => 'required|valid_date[Y-m-d]',
        ];

        if (! $this->validateData($this->request->getPost(), $rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $result = $this->accountService->updateIdentity((int) $user->id, [
            'full_name' => trim((string) $this->request->getPost('full_name')),
            'username' => trim((string) $this->request->getPost('username')),
            'birthdate' => trim((string) $this->request->getPost('birthdate')),
        ]);

        if (! $result['ok']) {
            return redirect()->back()->withInput()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', 'Personal identity updated successfully.');
    }

    public function updatePhoto()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->to('/login');
        }

        $file = $this->request->getFile('profile_photo');

        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('error', 'Please select a valid image file.');
        }

        $result = $this->accountService->updatePhoto((int) $user->id, $file);

        if (! $result['ok']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', 'Profile photo updated successfully.');
    }

    public function updateEmergency()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->to('/login');
        }

        $rules = [
            'emergency_name' => 'permit_empty|max_length[150]',
            'emergency_relationship' => 'permit_empty|max_length[100]',
            'emergency_phone_primary' => 'permit_empty|max_length[30]',
            'emergency_phone_alternate' => 'permit_empty|max_length[30]',
        ];

        if (! $this->validateData($this->request->getPost(), $rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $updated = $this->accountService->updateEmergency((int) $user->id, [
            'emergency_name' => trim((string) $this->request->getPost('emergency_name')),
            'emergency_relationship' => trim((string) $this->request->getPost('emergency_relationship')),
            'emergency_phone_primary' => trim((string) $this->request->getPost('emergency_phone_primary')),
            'emergency_phone_alternate' => trim((string) $this->request->getPost('emergency_phone_alternate')),
        ]);

        if (! $updated) {
            return redirect()->back()->with('error', 'Unable to update emergency contact.');
        }

        return redirect()->back()->with('success', 'Emergency contact updated successfully.');
    }

    public function updatePassword()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->to('/login');
        }

        $rules = [
            'current_password' => 'required',
            'new_password' => 'required|min_length[8]|max_length[72]',
            'confirm_password' => 'required|matches[new_password]',
        ];

        if (! $this->validateData($this->request->getPost(), $rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $result = $this->accountService->updatePassword(
            (int) $user->id,
            (string) $this->request->getPost('current_password'),
            (string) $this->request->getPost('new_password')
        );

        if (! $result['ok']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', 'Password updated successfully.');
    }

    public function updatePayslipPassword()
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->to('/login');
        }

        $rules = [
            'current_payslip_password' => 'required',
            'new_payslip_password'     => 'required|min_length[8]|max_length[72]',
            'confirm_payslip_password' => 'required|matches[new_payslip_password]',
        ];

        if (! $this->validateData($this->request->getPost(), $rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $currentInput = (string) $this->request->getPost('current_payslip_password');
        $newPassword  = (string) $this->request->getPost('new_payslip_password');

        // --- Load existing settings ---
        $db = \Config\Database::connect();
        $settingsRow = $db->table('user_settings')
            ->where('user_id', $user->id)
            ->get()
            ->getRowArray();

        $settings = $settingsRow ? json_decode($settingsRow['settings'], true) : [];

        // --- Verify current password ---
        // If a custom hash is stored, verify against it; otherwise verify against the preset.
        if (! empty($settings['payslip_password'])) {
            $valid = password_verify($currentInput, $settings['payslip_password']);
        } else {
            // Preset: employee_number + birthdate (YYYYMMDD)
            $employeeModel = new \App\Models\Client\EmployeeModel();
            $employee = $employeeModel->where('user_id', $user->id)->first();
            if (! $employee) {
                return redirect()->back()->with('error', 'Employee record not found.');
            }
            $preset = $employee['employee_number'] . date('Ymd', strtotime($employee['birthdate']));
            $valid = hash_equals($preset, $currentInput);
        }

        if (! $valid) {
            return redirect()->back()->with('error', 'Current payslip password is incorrect.');
        }

        // --- Hash and save the new password ---
        $encrypter = \Config\Services::encrypter();
        $settings['payslip_password']     = password_hash($newPassword, PASSWORD_BCRYPT);
        $settings['payslip_password_enc'] = base64_encode($encrypter->encrypt($newPassword));
        $newSettingsJson = json_encode($settings);
        $now = date('Y-m-d H:i:s');

        if ($settingsRow) {
            $db->table('user_settings')
                ->where('user_id', $user->id)
                ->update(['settings' => $newSettingsJson, 'updated_at' => $now]);
        } else {
            $db->table('user_settings')->insert([
                'user_id'    => $user->id,
                'settings'   => $newSettingsJson,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        service('activityLogger')->log(
            'updated',
            'user_settings',
            'Employee updated their payslip PDF password',
            (int) $user->id,
            null,
            ['user_id' => $user->id]
        );

        return redirect()->back()->with('success', 'Payslip password updated successfully.');
    }

    public function resetPayslipPassword()
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->to('/login');
        }

        $rules = [
            'account_password' => 'required',
        ];

        if (! $this->validateData($this->request->getPost(), $rules)) {
            return redirect()->back()->with('error', 'Account password is required.');
        }

        // Verify account password against Shield's auth_identities secret2
        $db = \Config\Database::connect();
        $identity = $db->table('auth_identities')
            ->where('user_id', $user->id)
            ->where('type', 'email_password')
            ->get()
            ->getRowArray();

        if (! $identity) {
            return redirect()->back()->with('error', 'Could not verify account credentials.');
        }

        $accountPassword = (string) $this->request->getPost('account_password');
        if (! password_verify($accountPassword, $identity['secret2'])) {
            return redirect()->back()->with('error', 'Account password is incorrect.');
        }

        // Strip payslip password keys from settings, preserve everything else
        $settingsRow = $db->table('user_settings')
            ->where('user_id', $user->id)
            ->get()
            ->getRowArray();

        if ($settingsRow) {
            $settings = json_decode($settingsRow['settings'], true) ?? [];
            unset($settings['payslip_password'], $settings['payslip_password_enc']);

            $db->table('user_settings')
                ->where('user_id', $user->id)
                ->update([
                    'settings'   => json_encode($settings),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }
        // If no settings row exists at all, there's nothing to reset — still a success

        service('activityLogger')->log(
            'deleted',
            'user_settings',
            'Employee reset their payslip PDF password to default',
            (int) $user->id,
            null,
            ['user_id' => $user->id]
        );

        return redirect()->back()->with('success', 'Payslip password reset to default successfully.');
    }
}