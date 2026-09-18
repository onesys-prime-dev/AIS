<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
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

    public function index()
    {
        $faceConfig = config(FaceRecognition::class);
        if (! $faceConfig->enabled) {
            return redirect()->to('/client_portal/dashboard');
        }

        if (! auth()->loggedIn()) {
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

        return view('auth/login', [
            'title' => 'Face Verification',
            'showFaceVerification' => true,
            'verificationUrl' => site_url('/face-verify'),
            'postLoginRedirect' => site_url('/client_portal/dashboard'),
        ]);
    }

    public function verify()
    {
        $faceConfig = config(FaceRecognition::class);
        if (! $faceConfig->enabled) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Face verification is currently disabled.',
                'redirect' => site_url('/client_portal/dashboard'),
            ]);
        }

        if (! auth()->loggedIn()) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 'error',
                'message' => 'Your session has expired. Please sign in again.',
                'redirect' => route_to('login'),
            ]);
        }

        $tempUserId = (int) session('temp_user_id');
        $authUserId = (int) auth()->id();

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
