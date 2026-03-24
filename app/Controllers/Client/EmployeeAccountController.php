<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
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

        return view('client_portal/employee_account', $this->accountService->getAccountViewData((int) $user->id));
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
}