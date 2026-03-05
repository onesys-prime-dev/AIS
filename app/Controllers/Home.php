<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        return view('welcome_message');
    }

    public function dashboard(): string|object
    {
        // Check if user is logged in
        if (!auth()->loggedIn()) {
            return redirect()->to('/login');
        }
        return view('pages/dashboard');
    }

    public function logout()
    {
        // Logout the user
        auth()->logout();

        // Redirect to login page with success message
        return redirect()->to('/login')->with('info', 'You have been logged out successfully.');
    }
}

