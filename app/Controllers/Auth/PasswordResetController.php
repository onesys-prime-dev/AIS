<?php

namespace App\Controllers\Auth;

use App\Models\Client\CompanyBrandingModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Models\UserIdentityModel;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use Throwable;

class PasswordResetController extends Controller
{
    private const RESET_TYPE = 'password-reset';
    private const RESET_LIFETIME = 900; // 15 minutes

    public function forgotPassword()
    {
        if (auth()->loggedIn()) {
            return redirect()->to(config('Auth')->loginRedirect());
        }

        return view(setting('Auth.views')['forgotPassword'] ?? 'auth/forgot_password', [
            'companyLogoUrl' => $this->getCompanyLogoUrl(),
        ]);
    }

    // send password reset email
    public function sendResetLink(): RedirectResponse
    {
        $email = trim((string) $this->request->getPost('email'));
        
        // Always return the same response to avoid account enumeration.
        $genericMessage = 'If an account exists with that email address, a password reset link has been sent.';

        $throttler = service('throttler');

        // rate limit by IP.
        // 5 requests per 15 minutes
        $ipKey = 'password-reset-ip-' . hash(
            'sha256',
             $this->request->getIPAddress()
        );

        if (! $throttler->check($ipKey, 5, 900)) {
            return redirect()
                ->to('/forgot-password')
                ->with(
                    'message',
                    $genericMessage
                );
        }

        // validate email format
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()
                ->withInput()
                ->with('message', $genericMessage);
        }

        // rate limit by email
        // 3 requests per 15 minutes
        $emailKey = 'password-reset-email-' . hash(
            'sha256',
            strtolower($email)
        );

        if (! $throttler->check($emailKey, 3, 900)) {
            return redirect()
                ->to('/forgot-password')
                ->with('message', $genericMessage);
        }

        /** @var UserModel $users */
        $users = auth()->getProvider();

        $user = $users->findByCredentials([
            'email' => $email,
        ]);

        if (! $user) {
            return redirect()->back()
                ->with('message', $genericMessage);
        }

        $identityModel = model(UserIdentityModel::class);

        // Remove previous reset tokens for this user.
        $identityModel->deleteIdentitiesByType(
            $user,
            self::RESET_TYPE
        );

        // Raw token goes into the email.
        $rawToken = bin2hex(random_bytes(32));

        // Only the hash is stored in the database.
        $hashedToken = hash('sha256', $rawToken);

        $expires = date(
            'Y-m-d H:i:s',
            time() + self::RESET_LIFETIME
        );

        $identityModel->create([
            'user_id' => $user->id,
            'type' => self::RESET_TYPE,
            'name' => null,
            'secret' => $hashedToken,
            'secret2' => null,
            'expires' => $expires,
            'extra' => null,
            'force_reset' => 0,
            'last_used_at' => null,
        ]);

        $resetUrl = site_url('reset-password/' . $rawToken);

        $emailService = service('email');

        $emailService->setFrom(
            config('Email')->fromEmail,
            config('Email')->fromName
        );

        $emailService->setTo($email);
        $emailService->setSubject('Reset Your AIS Password');

        $emailBody = view('emails/password_reset', [
            'resetUrl' => $resetUrl,
            'expiresInMinutes' => self::RESET_LIFETIME / 60,
        ]);

        $emailService->setMessage($emailBody);

        if (! $emailService->send()) {
            log_message('error', 'Password reset email failed for user ID {id}.', [
                'id' => (int) $user->id,
            ]);

            // remove the token because the email wasn't successfully sent
            $identityModel->deleteIdentitiesByType(
                $user,
                self::RESET_TYPE
            );
        }

        return redirect()
            ->to('/forgot-password')
            ->with('message', $genericMessage);
    }

    /**
     * Show reset-password form.
     */
    public function resetPassword(string $token)
    {
        $identity = $this->getValidResetIdentity($token);

        if (! $identity) {
            return redirect()
                ->to('/forgot-password')
                ->with('error', 'This password reset link is invalid or has expired.');
        }

        return view('auth/reset_password', [
            'token' => $token,
            'companyLogoUrl' => $this->getCompanyLogoUrl(),
        ]);
    }

    // process new password
    public function updatePassword(string $token): RedirectResponse
    {
        $identity = $this->getValidResetIdentity($token);

        if (! $identity) {
            return redirect()
                ->to('/forgot-password')
                ->with('error', 'This password reset link is invalid or has expired.');
        }

        $rules = [
            'password' => [
                'label' => 'New Password',
                'rules' => 'required|min_length[8]|max_length[72]',
            ],
            'password_confirm' => [
                'label' => 'Confirm Password',
                'rules' => 'required|matches[password]',
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        /** @var UserModel $users */
        $users = auth()->getProvider();

        $user = $users->findById((int) $identity->user_id);

        if (! $user) {
            return redirect()
                ->to('/forgot-password')
                ->with('error', 'Unable to reset the password.');
        }

        $newPassword = (string) $this->request->getPost('password');

        /*
         * Shield handles password hashing through the User entity
         * and its configured password service.
         */
        $user->setPassword($newPassword);

        $users->save($user);

        /*
         * Delete this reset token and any other outstanding
         * reset tokens for this user.
         */
        $identityModel = model(UserIdentityModel::class);

        $identityModel->deleteIdentitiesByType(
            $user,
            self::RESET_TYPE
        );

        /*
         * Invalidate the user's remember-me authentication.
         */
        auth('session')->forget($user);

        return redirect()
            ->to('/login')
            ->with(
                'message',
                'Your password has been reset successfully. You can now log in with your new password.'
            );
    }

    /**
     * Retrieve a valid reset identity.
     */
    private function getValidResetIdentity(string $token)
    {
        if ($token === '') {
            return null;
        }

        $hashedToken = hash('sha256', $token);

        /** @var UserIdentityModel $identityModel */
        $identityModel = model(UserIdentityModel::class);

        $identity = $identityModel->getIdentityBySecret(
            self::RESET_TYPE,
            $hashedToken
        );

        if (! $identity) {
            return null;
        }

        if (
            $identity->expires === null ||
            strtotime((string) $identity->expires) < time()
        ) {
            // Clean up expired token.
            $identityModel->delete($identity->id);

            return null;
        }

        return $identity;
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
            log_message('debug', 'Unable to load company logo for password reset pages: {message}', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}