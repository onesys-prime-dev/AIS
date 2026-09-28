<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Libraries\AccountSettings;
use App\Models\PresenceModel;
use App\Services\FaceRecognitionService;
use App\Libraries\TrustedDevicesService;
use Config\FaceRecognition;
use Throwable;

class FaceAuthController extends BaseController
{
    public function __construct(
        private readonly FaceRecognitionService $faceRecognitionService = new FaceRecognitionService(),
        private readonly PresenceModel $presenceModel = new PresenceModel(),
    ) {
    }

    /** Same rule as ClientAuthController::isFaceRequiredFor() — kept in sync manually since the two controllers don't share a base. */
    private function isFaceRequiredFor(int $userId): bool
    {
        $override = AccountSettings::getFaceRequirement($userId);
        return $override ?? config(FaceRecognition::class)->enabled;
    }

    public function index()
    {
        if (! auth()->loggedIn()) {
            if (! config(FaceRecognition::class)->enabled) {
                return redirect()->to('/client_portal/dashboard');
            }
            return redirect()->route('login');
        }

        if ((bool) session('face_verified')) {
            return redirect()->to('/client_portal/dashboard');
        }

        $tempUserId = (int) session('temp_user_id');
        if ($tempUserId < 1 || $tempUserId !== (int) auth()->id()) {
            auth()->logout();
            session()->remove(['face_verified', 'temp_user_id']);

            return redirect()->route('login')
                ->with('error', 'Your verification session expired. Please sign in again.');
        }

        if (! $this->isFaceRequiredFor($tempUserId)) {
            return redirect()->to('/client_portal/dashboard');
        }

        return view('auth/login', [
            'title' => 'Face Verification',
            'showFaceVerification' => true,
            'verificationUrl' => site_url('/face-verify'),
            'postLoginRedirect' => site_url('/client_portal/dashboard'),
        ]);
    }

    public function verify()
    {
        if (! auth()->loggedIn()) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'Your session has expired. Please sign in again.',
                'redirect' => route_to('login'),
            ]);
        }

        $authUserId = (int) auth()->id();

        // Same shape as the login/password-reset throttles: cap match
        // attempts per account and per IP so this can't be hammered to brute
        // -force a match (repeated probes/frames against the recognition
        // service) the way the login form itself already can't be.
        $throttler = service('throttler');

        $userKey = 'face-verify-user-' . $authUserId;
        if (! $throttler->check($userKey, 20, 300)) { // 20 attempts per 5min per account
            return $this->response->setStatusCode(429)->setJSON([
                'status' => 'error',
                'message' => 'Too many verification attempts. Please wait a few minutes and try again.',
            ]);
        }

        $ipKey = 'face-verify-ip-' . hash('sha256', $this->request->getIPAddress());
        if (! $throttler->check($ipKey, 40, 300)) { // 40 attempts per 5min per IP
            return $this->response->setStatusCode(429)->setJSON([
                'status' => 'error',
                'message' => 'Too many verification attempts. Please wait a few minutes and try again.',
            ]);
        }

        $tempUserId = (int) session('temp_user_id');

        if (! $this->isFaceRequiredFor($authUserId)) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Face verification is not required for this account.',
                'redirect' => site_url('/client_portal/dashboard'),
            ]);
        }

        // Already verified by a previous request (the login page's background
        // "already matched?" probe can have more than one check in flight at
        // once — see face-scan.js's earlyMatchProbe). temp_user_id is cleared
        // the moment the first one succeeds, so a still-in-flight duplicate
        // must not treat that as an expired/hijacked session and log the user
        // back out right after showing them a success screen.
        if ((bool) session('face_verified')) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Face verification successful.',
                'redirect' => site_url('/client_portal/dashboard'),
            ]);
        }

        if ($tempUserId < 1 || $tempUserId !== $authUserId) {
            auth()->logout();
            session()->remove(['face_verified', 'temp_user_id']);

            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Your verification session is no longer valid. Please sign in again.',
                'redirect' => route_to('login'),
            ]);
        }

        $image = (string) $this->request->getPost('image');
        if ($image === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'Please capture a face image before verifying.',
            ]);
        }

        try {
            $match = $this->faceRecognitionService->recognize($image);
        } catch (Throwable $e) {
            log_message('error', 'Face verification failed for user {userId}: {message}', [
                'userId' => $authUserId,
                'message' => $e->getMessage(),
            ]);

            return $this->response->setStatusCode(502)->setJSON([
                'status' => 'error',
                'message' => $this->safeErrorMessage($e, 'Face verification is unavailable right now. Please try again later.'),
            ]);
        }

        if ($match === null || (string) ($match['subject'] ?? '') !== (string) $authUserId) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'fail',
                'message' => 'Face not recognized for this account. Please try again.',
            ]);
        }

        session()->set('face_verified', true);

        $trusted = new TrustedDevicesService();
        $trusted->trustCurrentDevice($authUserId);

        session()->remove('temp_user_id');

        $this->presenceModel->updatePresence(
            $authUserId,
            'Online'
        );

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Face verification successful.',
            'redirect' => site_url('/client_portal/dashboard'),
        ]);
    }
}
