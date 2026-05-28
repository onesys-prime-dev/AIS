<?php

namespace App\Controllers\Client;

use App\Models\Client\AttendanceModel;
use App\Models\Client\RequestModel;
use App\Models\Client\TasksModel;
use App\Models\Client\SavedTasksModel;
use App\Services\Client\AnnouncementService;
use App\Services\Client\RequestNotificationService;
use App\Services\Client\NotificationService;
use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function index()
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->to('/login');
        }

        $userId = $user->id;

        $attendanceModel = new AttendanceModel();
        $requestModel = new RequestModel();
        $savedTasksModel = new SavedTasksModel();
        $announcementService = new AnnouncementService();
        $requestNotificationService = new RequestNotificationService();

        // check if user currently timed in
        $currentAttendance = $attendanceModel
            ->where('user_id', $userId)
            ->where('time_out', null)
            ->first();

        $requests = [];

        // only payroll staff see requests
        if ($user->inGroup('client_payroll_staff')) {

            $requests = $requestModel
                ->select('requests.created_at, users.username, requests.request_type, requests.start_date, requests.end_date, requests.duration, requests.approval_code, requests.reason, requests.status')
                ->join('users', 'users.id = requests.requested_by')
                ->orderBy('requests.created_at', 'DESC')
                ->findAll();
        }

        // fetch saved tasks for the dropdown (visible only to this user)
        $savedTasks = $savedTasksModel
            ->where('user_id', $userId)
            ->orderBy('saved_at', 'DESC')
            ->findAll();

        $notificationService = new NotificationService();

        // combined, normalized notifications for header/dashboard
        $publishedNotifications = $notificationService->getVisibleNotificationsForUser($user, 10);

        $unpublishedNotifications = [];
        if ($user->inGroup('client_payroll_staff')) {
            $unpublishedNotifications = $notificationService->getUnpublishedAnnouncementsForPayroll((int) $user->id);
        }

        return view('client_portal/dashboard', [
            'currentAttendance' => $currentAttendance,
            'requests' => $requests,
            'savedTasks' => $savedTasks,
            'publishedNotifications' => $publishedNotifications,
            'unpublishedNotifications' => $unpublishedNotifications,
        ]);
    }
}