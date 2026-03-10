<?php

namespace App\Controllers\Auth;

use CodeIgniter\Controller;
use CodeIgniter\Shield\Controllers\LoginController;

class ClientAuthController extends LoginController
{
    /* public function clientLogin()
    {
        // If already logged in, redirect
        if (auth()->loggedIn()) {
            return redirect()->to('/client_portal/dashboard');
        }

        return view('/auth/login');
    } */

    /* public function attemptClientLogin()
    {
        // For now, use Shield's default login logic
        return $this->loginAction();
    } */

    public function logout()
    {
        // Logout the user
        auth()->logout();

        // Redirect to login page with success message
        return redirect()->to('/login')->with('info', 'You have been logged out successfully.');
    }
}


?>