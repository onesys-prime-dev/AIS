<?php

namespace App\Services\Client;

use App\Models\Client\RequestNotificationModel;
use App\Models\Client\RequestModel;
use App\Models\Client\EmployeeModel;
use App\Models\Client\EmployeePositionModel;

class RequestNotificationService
{
    protected RequestNotificationModel $notificationModel;
    protected RequestModel $requestModel;
    protected EmployeeModel $employeeModel;
    protected EmployeePositionModel $employeePositionModel;

    public function __construct()
    {
        $this->notificationModel = new RequestNotificationModel();
        $this->requestModel = new RequestModel();
        $this->employeeModel = new EmployeeModel();
        $this->employeePositionModel = new EmployeePositionModel();
    }

    public function createStatusNotification(int $requestId, int $recipientUserId, int $actorUserId, string $status, string $requestType, ?string $reviewMessage = null): bool
    {
        if ($requestId <= 0 || $recipientUserId <= 0 || $actorUserId <= 0) {
            return false;
        }

        $existing = $this->notificationModel
            ->select('id')
            ->where('request_id', $requestId)
            ->where('recipient_user_id', $recipientUserId)
            ->where('status', $status)
            ->first();

        if ($existing) {
            return true;
        }

        $actor = $this->getActorProfile($actorUserId);
        $actorName = $actor['name'] ?? 'System';
        $actorPhoto = $actor['photo_url'] ?? '';
        $statusLabel = ucfirst($status);
        $requestLabel = trim($requestType) !== '' ? ucfirst($requestType) : 'request';
        $details = sprintf('%s reviewed your %s request and marked it as %s.', $actorName, strtolower($requestLabel), strtolower($statusLabel));

        if ($reviewMessage !== null && trim($reviewMessage) !== '') {
            $details .= ' Note: ' . trim($reviewMessage);
        }

        return (bool) $this->notificationModel->insert([
            'request_id' => $requestId,
            'recipient_user_id' => $recipientUserId,
            'actor_user_id' => $actorUserId,
            'status' => $status,
            'title' => $statusLabel . ' Request',
            'details' => $details,
            'viewed_at' => null,
            'dismissed_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getVisibleNotificationsForUser(object $user, int $limit = 10): array
    {
        if (! isset($user->id)) {
            return [];
        }

        $rows = $this->notificationModel
            ->select(
                'request_notifications.*, users.username as author_username, '
                . 'CONCAT_WS(" ", employees.first_name, employees.middle_name, employees.last_name) as author_full_name, '
                . 'ea.photo_path as author_photo_path, '
                . 'actor_pos.position as actor_position, '
                    // requester info: prefer the notification recipient as the requester (recipient_user_id)
                    . 'requests.requested_by as requester_user_id, '
                    . 'requests.request_type as request_type, '
                    . 'req_emp.employee_number as requester_employee_number, '
                    . 'CONCAT_WS(" ", req_emp.first_name, req_emp.middle_name, req_emp.last_name) as requester_full_name, '
                    . 'req_pos.department as requester_department, '
                    . 'req_ea.photo_path as requester_photo_path, '
                    . 'recipient_emp.id AS recipient_employee_id, '
                    . 'CONCAT_WS(" ", recipient_emp.first_name, recipient_emp.middle_name, recipient_emp.last_name) as recipient_full_name, '
                    . 'recipient_pos.department as recipient_department',
                false
            )
            ->select('CASE WHEN request_notifications.viewed_at IS NULL THEN 0 ELSE 1 END as is_viewed', false)
            ->join('users', 'users.id = request_notifications.actor_user_id', 'left')
            ->join('employees', 'employees.user_id = users.id', 'left')
            ->join('employee_accounts ea', 'ea.employee_id = employees.id', 'left')
            ->join('employee_positions as actor_pos', 'actor_pos.employee_id = employees.id', 'left')
                ->join('requests', 'requests.id = request_notifications.request_id', 'left')
                // requests.requested_by stores user id
                ->join('employees as req_emp', 'req_emp.user_id = requests.requested_by', 'left')
                ->join('employee_positions as req_pos', 'req_pos.employee_id = req_emp.id', 'left')
                ->join('employee_accounts as req_ea', 'req_ea.employee_id = req_emp.id', 'left')
                // recipient_emp: resolve requester by notification.recipient_user_id (recipient is the requestor per system behavior)
                ->join('employees as recipient_emp', 'recipient_emp.user_id = request_notifications.recipient_user_id', 'left')
                ->join('employee_positions as recipient_pos', 'recipient_pos.employee_id = recipient_emp.id', 'left')
            ->where('request_notifications.recipient_user_id', (int) $user->id)
            ->where('request_notifications.dismissed_at', null)
            ->orderBy('request_notifications.created_at', 'DESC')
            ->limit($limit)
            ->findAll();

        if ($rows === []) {
            return [];
        }

        // Defensive dedupe in case any left-join cardinality multiplies rows.
        $unique = [];
        foreach ($rows as $row) {
            $key = (int) ($row['id'] ?? 0);
            if ($key <= 0) {
                continue;
            }

            if (! isset($unique[$key])) {
                $unique[$key] = $row;
            }
        }

        return array_values($unique);
    }

    public function markNotificationViewed(int $notificationId, int $userId): bool
    {
        if ($notificationId <= 0 || $userId <= 0) {
            return false;
        }

        $notification = $this->notificationModel
            ->where('id', $notificationId)
            ->where('recipient_user_id', $userId)
            ->first();

        if (! $notification) {
            return false;
        }

        if (! empty($notification['viewed_at'])) {
            return true;
        }

        return (bool) $this->notificationModel->update($notificationId, [
            'viewed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function dismissNotification(int $notificationId, int $userId): bool
    {
        if ($notificationId <= 0 || $userId <= 0) {
            return false;
        }

        $notification = $this->notificationModel
            ->where('id', $notificationId)
            ->where('recipient_user_id', $userId)
            ->first();

        if (! $notification) {
            return false;
        }

        return (bool) $this->notificationModel->update($notificationId, [
            'dismissed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function getActorProfile(int $userId): array
    {
        $row = db_connect()->table('users AS u')
            ->select('u.username, CONCAT_WS(" ", e.first_name, e.middle_name, e.last_name) AS full_name, ea.photo_path AS photo_path', false)
            ->join('employees AS e', 'e.user_id = u.id', 'left')
            ->join('employee_accounts AS ea', 'ea.employee_id = e.id', 'left')
            ->where('u.id', $userId)
            ->get()
            ->getRowArray();

        $name = trim((string) ($row['full_name'] ?? ''));
        if ($name === '') {
            $name = (string) ($row['username'] ?? 'System');
        }

        $photo = trim((string) ($row['photo_path'] ?? ''));
        if ($photo !== '' && ! preg_match('~^(?:[a-z][a-z0-9+.-]*:)?//~i', $photo)) {
            $photo = base_url($photo);
        }

        return [
            'name' => $name,
            'photo_url' => $photo,
        ];
    }
}
