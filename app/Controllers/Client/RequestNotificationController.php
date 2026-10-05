<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Services\Client\RequestNotificationService;

class RequestNotificationController extends BaseController
{
    protected RequestNotificationService $notificationService;

    public function __construct()
    {
        $this->notificationService = new RequestNotificationService();
    }

    public function viewed()
    {
        $user = auth()->user();

        if (! $user) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Unauthenticated.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $notificationId = (int) $this->request->getPost('notification_id');

        if ($notificationId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Invalid notification id.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $saved = $this->notificationService->markNotificationViewed($notificationId, (int) $user->id);

        if (! $saved) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Notification not found or unable to mark as viewed.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function dismiss()
    {
        $user = auth()->user();

        if (! $user) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Unauthenticated.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $notificationId = (int) $this->request->getPost('notification_id');

        if ($notificationId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Invalid notification id.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $saved = $this->notificationService->dismissNotification($notificationId, (int) $user->id);

        if (! $saved) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Notification not found or unable to dismiss.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'csrfHash' => csrf_hash(),
        ]);
    }
}
