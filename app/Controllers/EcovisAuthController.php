<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\Shield\Controllers\LoginController;

class EcovisAuthController extends LoginController
{
    public function ecovisLogin()
    {
        // If already logged in, redirect
        if (auth()->loggedIn()) {
            return redirect()->to('/');
        }

        return view('ecovis_login');
    }

    public function attemptEcovisLogin()
    {
        // For now, use Shield's default login logic
        return $this->loginAction();
    }
}


?>