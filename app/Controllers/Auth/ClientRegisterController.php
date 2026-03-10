<?php

namespace App\Controllers\Auth;

use CodeIgniter\Shield\Controllers\RegisterController as ShieldRegister;
use CodeIgniter\HTTP\RedirectResponse;

class ClientRegisterController extends ShieldRegister
{
    public function registerAction(): RedirectResponse
{
    $response = parent::registerAction();

    if (session('errors') || session('error')) {
        return $response;
    }

    $role = $this->request->getPost('role');

    $user = auth()->user();

    if ($user && $role) {
        $user->addGroup($role);
    }

    auth()->logout();

    return redirect()->to('/login');
}

    public function registerView()
    {
        return parent::registerView();
    }
}