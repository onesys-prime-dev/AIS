<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\Client\EmployeeModel;
use App\Services\FaceRecognitionService;
use CodeIgniter\Exceptions\PageNotFoundException;
use Throwable;

/**
 * Admin-managed face enrollment — superadmin/admin enroll/remove a face on
 * behalf of an employee (not self-service; see employee_main.php's
 * Face Recognition tab). Routes already sit behind the 'auth' filter chain
 * (ChainAuth + PortalAccessFilter), which resolves these paths to the
 * 'face_recognition' requirement (app/Libraries/PortalAccess.php) — this
 * guard is a second, explicit check, matching EmployeeController's pattern.
 */
class FaceRecognitionController extends BaseController
{
    protected FaceRecognitionService $faceRecognitionService;
    protected EmployeeModel $employeeModel;

    public function __construct()
    {
        $this->faceRecognitionService = new FaceRecognitionService();
        $this->employeeModel = new EmployeeModel();
    }

    private function ensureCanManage(): void
    {
        $user = auth()->user();

        if (! $user || ! \App\Libraries\PortalAccess::can('face_recognition', $user)) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    /**
     * Resolves a users.id from the route param, confirming it actually
     * corresponds to a real user account (avoids enrolling/reading/clearing
     * an embedding row against an arbitrary, unrelated user id).
     */
    private function resolveUserId(int $userId): int
    {
        $db = \Config\Database::connect();
        $exists = $db->table('users')->where('id', $userId)->countAllResults() > 0;

        if (! $exists) {
            throw new \App\Exceptions\ValidationException('Employee account not found.');
        }

        return $userId;
    }

    public function health()
    {
        $this->ensureCanManage();

        return $this->response->setJSON($this->faceRecognitionService->getHealthStatus());
    }

    public function status(int $userId)
    {
        $this->ensureCanManage();

        try {
            $userId = $this->resolveUserId($userId);
        } catch (\App\Exceptions\ValidationException $e) {
            return $this->response->setStatusCode(404)->setJSON([
                'enrolled' => false,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->response->setJSON($this->faceRecognitionService->getEnrollmentStatus($userId));
    }

    public function enroll(int $userId)
    {
        $this->ensureCanManage();

        try {
            $userId = $this->resolveUserId($userId);
        } catch (\App\Exceptions\ValidationException $e) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        $image = (string) $this->request->getPost('image');
        if ($image === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'Please capture an image before saving this face.',
            ]);
        }

        try {
            $result = $this->faceRecognitionService->enrollFace($image, (string) $userId);
        } catch (Throwable $e) {
            log_message('error', 'Face enrollment failed for user {userId} (admin-managed): {message}', [
                'userId' => $userId,
                'message' => $e->getMessage(),
            ]);

            return $this->response->setStatusCode(502)->setJSON([
                'status' => 'error',
                'message' => $this->safeErrorMessage($e, 'Face enrollment is unavailable right now. Please try again later.'),
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Face enrolled successfully.',
            'result' => $result,
            'enrollment' => $this->faceRecognitionService->getEnrollmentStatus($userId),
        ]);
    }

    public function clear(int $userId)
    {
        $this->ensureCanManage();

        try {
            $userId = $this->resolveUserId($userId);
        } catch (\App\Exceptions\ValidationException $e) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        try {
            $this->faceRecognitionService->clearEnrollment($userId);
        } catch (Throwable $e) {
            log_message('error', 'Clearing face enrollment failed for user {userId} (admin-managed): {message}', [
                'userId' => $userId,
                'message' => $e->getMessage(),
            ]);

            return $this->response->setStatusCode(502)->setJSON([
                'status' => 'error',
                'message' => $this->safeErrorMessage($e, 'Unable to remove this enrolled face right now.'),
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Enrolled face removed.',
            'enrollment' => $this->faceRecognitionService->getEnrollmentStatus($userId),
        ]);
    }
}
