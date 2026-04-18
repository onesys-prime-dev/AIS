<?php

namespace App\Controllers\Auth;

use App\Models\PresenceModel;
use Config\CompreFace;
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

        if (! auth()->loggedIn()) {
            return $response;
        }

        $user = auth()->user();

        if (! $user || ! (bool) $user->active) {
            $this->clearFaceVerificationSession();
            auth()->logout();

            return redirect()->route('login')
                ->withInput()
                ->with('error', 'Your account has been deactivated. Please contact your administrator.');
        }

        $faceConfig = config(CompreFace::class);
        if (! $faceConfig->enabled) {
            (new PresenceModel())->updatePresence((int) $user->id, 'Online');
            $this->clearFaceVerificationSession();

            return $response;
        }

        session()->set([
            'face_verified' => false,
            'temp_user_id' => (int) $user->id,
        ]);

        return redirect()->to('/face-verify');
    }

    public function logout(): RedirectResponse
    {
        $user = auth()->user();

        if ($user) {
            (new PresenceModel())->setOffline((int) $user->id);
        }

        $this->clearFaceVerificationSession();
        auth()->logout();

        return redirect()->to('/login')->with('info', 'You have been logged out successfully.');
    }

    private function clearFaceVerificationSession(): void
    {
        session()->remove([
            'face_verified',
            'temp_user_id',
        ]);
    }
}


?>
