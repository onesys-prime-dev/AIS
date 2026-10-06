<?php

namespace App\Controllers\Auth;

use App\Models\Client\CompanyBrandingModel;
use App\Models\PresenceModel;
use Config\FaceRecognition;
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
        // Only FAILED attempts count toward the limit. A successful login never uses one up, so a
        // whole office signing in at 8am from one shared IP can't lock itself out. Per account:
        // 5 failures / 3 min. Per IP: 20 failures / 3 min (higher because many people share one
        // office address, but still caps guessing across many accounts from one place).
        $window = 180;
        $ipKey  = 'login-fail-ip-' . hash('sha256', $this->request->getIPAddress());
        $identifier = strtolower(trim((string) $this->request->getPost('email')));
        $emailKey   = $identifier !== '' ? 'login-fail-email-' . hash('sha256', $identifier) : null;

        $tooMany = ((int) cache($ipKey) >= 20) || ($emailKey !== null && (int) cache($emailKey) >= 5);
        if ($tooMany) {
            return redirect()->route('login')
                ->withInput()
                ->with('error', 'Too many login attempts. Please try again in a few minutes.');
        }

        $response = parent::loginAction();

        if (! auth()->loggedIn()) {
            cache()->save($ipKey, (int) cache($ipKey) + 1, $window);
            if ($emailKey !== null) {
                cache()->save($emailKey, (int) cache($emailKey) + 1, $window);
            }
            return $response;
        }

        // Success: forget this account's failures (the IP's count simply ages out).
        if ($emailKey !== null) {
            cache()->delete($emailKey);
        }

        $user = auth()->user();

        if (! $user || ! (bool) $user->active) {
            $this->clearFaceVerificationSession();
            auth()->logout();

            return redirect()->route('login')
                ->withInput()
                ->with('error', 'Your account has been deactivated. Please contact your administrator.');
        }

        if (! $this->isFaceRequiredFor((int) $user->id)) {
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

    /**
     * Per-account override (Face Recognition admin page) wins over the
     * global switch either way — "Always Require" still gates this account
     * even with the feature globally off, and "Exempt" skips it even with
     * the feature globally on. No override (every account, by default)
     * falls back to the global switch. Shared with FaceAuthController and
     * the FaceVerified filter so all three agree on the same answer.
     */
    private function isFaceRequiredFor(int $userId): bool
    {
        $override = \App\Libraries\AccountSettings::getFaceRequirement($userId);
        return $override ?? config(FaceRecognition::class)->enabled;
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
