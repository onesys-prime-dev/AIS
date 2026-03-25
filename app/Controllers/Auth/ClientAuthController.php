<?php

namespace App\Controllers\Auth;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Controllers\LoginController;

class ClientAuthController extends LoginController
{
    /**
     * Override Shield's loginAction to enforce the `active` flag.
     * Shield's Session authenticator only checks isBanned(), not active,
     * so we intercept after a successful attempt and block inactive accounts.
     */
    public function loginAction(): RedirectResponse
    {
        $response = parent::loginAction();

        if (auth()->loggedIn() && ! (bool) auth()->user()->active) {
            auth()->logout();

            return redirect()->route('login')
                ->withInput()
                ->with('error', 'Your account has been deactivated. Please contact your administrator.');
        }

        // set online immediately on successful login
        if (auth()->loggedIn()) {
            (new \App\Models\PresenceModel())->updatePresence((int) auth()->user()->id, 'Online');
        }

        return $response;
    }

    public function logout(): RedirectResponse
    {
        $user = auth()->user();

        if ($user) {
            (new \App\Models\PresenceModel())->setOffline((int) $user->id);
        }

        auth()->logout();

        return redirect()->to('/login')->with('info', 'You have been logged out successfully.');
    }
}


?>