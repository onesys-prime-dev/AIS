<?php

namespace App\Controllers\Client;

use App\Models\Client\AttendanceModel;
use App\Models\Client\RequestModel;
use App\Models\Client\TasksModel;
use App\Models\Client\SavedTasksModel;
use App\Services\Client\AnnouncementService;
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

        $announcements = $announcementService->getVisibleAnnouncementsForUser($user);
        $unpublishedAnnouncements = [];

        if ($user->inGroup('client_payroll_staff')) {
            $unpublishedAnnouncements = $announcementService->getUnpublishedAnnouncementsForPayroll((int) $user->id);
        }

        $publishedNotifications = array_map(static function (array $announcement): array {
            $isUrgent = in_array(($announcement['priority'] ?? 'normal'), ['high', 'urgent'], true);
               $authorName = trim((string) ($announcement['author_full_name'] ?? $announcement['author_username'] ?? ''));
               $authorPhotoPath = trim((string) ($announcement['author_photo_path'] ?? ''));

            return [
                'id' => (int) ($announcement['id'] ?? 0),
                'type' => $isUrgent ? 'error' : 'success',
                'title' => (string) ($announcement['title'] ?? 'Announcement'),
                'summary' => (string) ($announcement['summary'] ?? ''),
                'message' => (string) ($announcement['summary'] ?? ''),
                'details' => (string) ($announcement['details'] ?? ($announcement['summary'] ?? '')),
                'publish_at' => (string) ($announcement['publish_at'] ?? ''),
                'category' => (string) ($announcement['category'] ?? 'general'),
                'priority' => (string) ($announcement['priority'] ?? 'normal'),
                'audience' => (string) ($announcement['audience'] ?? 'all'),
                'is_viewed' => (int) ($announcement['is_viewed'] ?? 0),
                   'author_name' => $authorName !== '' ? $authorName : 'System',
                   'author_photo' => $authorPhotoPath !== '' ? base_url($authorPhotoPath) : '',
            ];
        }, $announcements);

        $unpublishedNotifications = array_map(static function (array $announcement): array {
            $isUrgent = in_array(($announcement['priority'] ?? 'normal'), ['high', 'urgent'], true);
               $authorName = trim((string) ($announcement['author_full_name'] ?? $announcement['author_username'] ?? ''));
               $authorPhotoPath = trim((string) ($announcement['author_photo_path'] ?? ''));

            return [
                'id' => (int) ($announcement['id'] ?? 0),
                'type' => $isUrgent ? 'error' : 'success',
                'title' => (string) ($announcement['title'] ?? 'Announcement'),
                'summary' => (string) ($announcement['summary'] ?? ''),
                'message' => (string) ($announcement['summary'] ?? ''),
                'details' => (string) ($announcement['details'] ?? ($announcement['summary'] ?? '')),
                'publish_at' => (string) ($announcement['publish_at'] ?? ''),
                'category' => (string) ($announcement['category'] ?? 'general'),
                'priority' => (string) ($announcement['priority'] ?? 'normal'),
                'audience' => (string) ($announcement['audience'] ?? 'all'),
                'is_viewed' => (int) ($announcement['is_viewed'] ?? 0),
                   'author_name' => $authorName !== '' ? $authorName : 'System',
                   'author_photo' => $authorPhotoPath !== '' ? base_url($authorPhotoPath) : '',
            ];
        }, $unpublishedAnnouncements);

        return view('client_portal/dashboard', [
            'currentAttendance' => $currentAttendance,
            'requests' => $requests,
            'savedTasks' => $savedTasks,
            'announcements' => $announcements,
            'publishedNotifications' => $publishedNotifications,
            'unpublishedNotifications' => $unpublishedNotifications,
        ]);
    }
}