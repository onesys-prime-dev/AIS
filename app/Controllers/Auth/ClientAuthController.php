<?php

namespace App\Controllers\Auth;

use App\Models\Client\CompanyBrandingModel;
use App\Models\PresenceModel;
use Config\CompreFace;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Controllers\LoginController;
use Throwable;

class ClientAuthController extends LoginController
{
    public function loginView()
    {
        if (auth()->loggedIn()) {
            return redirect()->to(config('Auth')->loginRedirect());
        }

        /** @var \CodeIgniter\Shield\Authentication\Authenticators\Session $authenticator */
        $authenticator = auth('session')->getAuthenticator();

        if ($authenticator->hasAction()) {
            return redirect()->route('auth-action-show');
        }

        return $this->view(setting('Auth.views')['login'], [
            'companyLogoUrl' => $this->getCompanyLogoUrl(),
        ]);
    }

    /**
     * Override Shield's loginAction to enforce the `active` flag.
     * Shield's Session authenticator only checks isBanned(), not active,
     * so we intercept after a successful attempt and block inactive accounts.
     */
    public function loginAction(): RedirectResponse
    {
        $throttler = service('throttler');

        $ipKey = 'login-ip-' . hash('sha256', $this->request->getIPAddress());
        if (! $throttler->check($ipKey, 5, 900)) { // 5 attempts per 15mins
            return redirect()->route('login')
                ->withInput()
                ->with('error', 'Too many login attempts. Please try again in a few minutes.');
        }

        $identifier = strtolower(trim((string) $this->request->getPost('email')));

        if ($identifier !== '') {
            $emailKey = 'login-email-' . hash('sha256', $identifier);

            if (! $throttler->check($emailKey, 5, 900)) { // 5 attempts per 15min.
                return redirect()->route('login')
                    ->withInput()
                    ->with('error', 'Too many login attempts. Please try again in a few minutes.');
            }
        }

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
            (new PresenceModel())->setOffline(
                (int) $user->id
            );
        }

        $this->clearFaceVerificationSession();

        auth()->logout();

        return redirect()
            ->to('/login')
            ->with(
                'info',
                'You have been logged out successfully.'
            );
    }

    private function clearFaceVerificationSession(): void
    {
        session()->remove([
            'face_verified',
            'temp_user_id',
        ]);
    }

    private function getCompanyLogoUrl(): ?string
    {
        try {
            $companyId = (int) (session('company_id') ?? 1);
            $branding  = (new CompanyBrandingModel())
                ->where('company_id', $companyId)
                ->first();

            if (empty($branding['logo_path'])) {
                return null;
            }

            return base_url((string) $branding['logo_path']);
        } catch (Throwable $e) {
            log_message('debug', 'Unable to load company logo for login page: {message}', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
