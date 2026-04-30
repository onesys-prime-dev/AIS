<?php

namespace App\Services\Client;

use App\Models\Client\AnnouncementModel;
use App\Models\Client\AnnouncementViewModel;
use App\Models\Client\EmployeeModel;
use App\Models\Client\EmployeePositionModel;
use App\Services\ActivityLogger;

class AnnouncementService
{
    protected AnnouncementModel $announcementModel;
    protected AnnouncementViewModel $announcementViewModel;
    protected EmployeeModel $employeeModel;
    protected EmployeePositionModel $employeePositionModel;
    protected ActivityLogger $logger;

    public function __construct()
    {
        $this->announcementModel = new AnnouncementModel();
        $this->announcementViewModel = new AnnouncementViewModel();
        $this->employeeModel = new EmployeeModel();
        $this->employeePositionModel = new EmployeePositionModel();
        $this->logger = service('activityLogger');
    }

    public function createAnnouncement(int $createdBy, array $data): bool
    {
        $this->announcementModel->insert([
            'title' => $data['title'],
            'category' => $data['category'],
            'audience' => $data['audience'],
            'department' => $data['department'] !== '' ? $data['department'] : null,
            'priority' => $data['priority'],
            'publish_at' => $data['publish_at'],
            'expires_at' => $data['expires_at'] !== '' ? $data['expires_at'] : null,
            'summary' => $data['summary'],
            'details' => $data['details'],
            'require_acknowledgement' => $data['require_acknowledgement'] ? 1 : 0,
            'created_by' => $createdBy,
        ]);

        $id = $this->announcementModel->getInsertID();
        if (! $id) {
            return false;
        }

        $this->logger->announcementPublished($id, $data);

        return true;
    }

    public function getVisibleAnnouncementsForUser(object $user, int $limit = 15): array
    {
        $now = date('Y-m-d H:i:s');
        $isPayrollStaff = $user->inGroup('client_payroll_staff');
        $userDepartment = $this->getDepartmentByUserId((int) $user->id);
        $userId = (int) $user->id;

        $builder = $this->announcementModel
            ->select('announcements.*, users.username as author_username')
            ->select('CASE WHEN announcement_views.id IS NULL THEN 0 ELSE 1 END as is_viewed', false)
            ->join('users', 'users.id = announcements.created_by', 'left')
            ->join('announcement_views', 'announcement_views.announcement_id = announcements.id AND announcement_views.user_id = ' . $userId, 'left')
            ->where('publish_at <=', $now)
            ->groupStart()
                ->where('expires_at', null)
                ->orWhere('expires_at >=', $now)
            ->groupEnd()
            ->groupStart()
                ->where('audience', 'all');

        if ($isPayrollStaff) {
            $builder->orWhere('audience', 'payroll');
        }

        if ($userDepartment !== null && $userDepartment !== '') {
            $builder->orGroupStart()
                ->where('audience', 'department')
                ->where('department', $userDepartment)
            ->groupEnd();
        }

        $builder->groupEnd();

        return $builder
            ->orderBy('publish_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    public function getUnpublishedAnnouncementsForPayroll(int $userId, int $limit = 15): array
    {
        $now = date('Y-m-d H:i:s');

        return $this->announcementModel
            ->select('announcements.*, users.username as author_username')
            ->select('CASE WHEN announcement_views.id IS NULL THEN 0 ELSE 1 END as is_viewed', false)
            ->join('users', 'users.id = announcements.created_by', 'left')
            ->join('announcement_views', 'announcement_views.announcement_id = announcements.id AND announcement_views.user_id = ' . $userId, 'left')
            ->where('publish_at >', $now)
            ->groupStart()
                ->where('expires_at', null)
                ->orWhere('expires_at >=', $now)
            ->groupEnd()
            ->orderBy('publish_at', 'ASC')
            ->limit($limit)
            ->findAll();
    }

    public function markAnnouncementViewed(int $announcementId, int $userId): bool
    {
        if ($announcementId <= 0 || $userId <= 0) {
            return false;
        }

        $announcement = $this->announcementModel
            ->select('id')
            ->where('id', $announcementId)
            ->first();

        if (! $announcement) {
            return false;
        }

        $alreadyViewed = $this->announcementViewModel
            ->select('id')
            ->where('announcement_id', $announcementId)
            ->where('user_id', $userId)
            ->first();

        if ($alreadyViewed) {
            return true;
        }

        return (bool) $this->announcementViewModel->insert([
            'announcement_id' => $announcementId,
            'user_id' => $userId,
            'viewed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function getDepartmentByUserId(int $userId): ?string
    {
        $employee = $this->employeeModel
            ->select('id')
            ->where('user_id', $userId)
            ->first();

        if (! $employee || empty($employee['id'])) {
            return null;
        }

        $position = $this->employeePositionModel
            ->select('department')
            ->where('employee_id', (int) $employee['id'])
            ->first();

        if (! $position) {
            return null;
        }

        $department = trim((string) ($position['department'] ?? ''));

        return $department !== '' ? $department : null;
    }
}
