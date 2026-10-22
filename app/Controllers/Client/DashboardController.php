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
            ->orderBy('id', 'ASC')
            ->first();

        // Safety net for anyone who left a break running past its scheduled
        // length without the dashboard open to auto-submit End Break itself
        // (see the client-side check in the view) — e.g. they closed the tab,
        // or their laptop slept. Never changes what gets deducted from pay
        // (still the fixed scheduled slot length either way), only keeps "on
        // break" from getting stuck indefinitely.
        $breakAutoEnded = false;
        if ($currentAttendance && !empty($currentAttendance['break_start'])) {
            try {
                $ended = (new \App\Services\Client\AttendanceSessionService())->autoEndBreakIfOverdue(
                    (int) $userId,
                    (int) $currentAttendance['id'],
                    (int) $currentAttendance['state_version']
                );
                if ($ended) {
                    $currentAttendance = $ended;
                    $breakAutoEnded = true;
                }
            } catch (\Throwable $e) {
                // Non-fatal — worst case the break just stays open until the
                // next load or the employee clicks End Break themselves.
                log_message('warning', 'Auto-end break check failed for user {uid}: {msg}', ['uid' => $userId, 'msg' => $e->getMessage()]);
            }
        }

        // Total break budget (minutes) from this employee's configured break
        // schedule (see AttendanceController::getBreakScheduleMinutes) — falls
        // back to 60 (one legacy break) if nothing's been configured, so the
        // dashboard's break countdown ring isn't stuck assuming a flat hour for
        // employees whose admin set up a different total.
        $breakMinutesTotal = 60;
        $db = \Config\Database::connect();
        $employeeRow = $db->table('employees')->where('user_id', $userId)->get()->getRowArray();
        if ($employeeRow) {
            $positionRow = $db->table('employee_positions')->where('employee_id', $employeeRow['id'])->get()->getRowArray();
            $rawSchedule = $positionRow['break_schedule'] ?? null;
            if ($rawSchedule) {
                $decoded = json_decode($rawSchedule, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $breakMinutesTotal = array_sum(array_map('intval', $decoded));
                }
            }
        }

        $requests = [];

        // only payroll staff see requests
        if (\App\Libraries\PortalAccess::reviewsRequests($user)) {

            $requests = \App\Libraries\PortalAccess::scopeRequests($requestModel)
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
        if (\App\Libraries\PortalAccess::can('announcements', $user)) {
            $unpublishedNotifications = $notificationService->getUnpublishedAnnouncementsForPayroll((int) $user->id);
        }

        if (!session()->get('attendance_csrf')) session()->set('attendance_csrf', bin2hex(random_bytes(32)));
        $nextBreakMinutes = 60;
        $shiftMinutes = 480;
        $breakMinutesTotal = 0;
        $totalBreakSlots = 0;
        $breakWindow = null;
        $breakWindowAllowed = true;
        if ($currentAttendance) {
            try {
                // Break count/duration reads the employee's CURRENT configured
                // schedule, not the one frozen at clock-in — so an admin's
                // mid-shift edit in Employee Management shows up immediately,
                // and matches what AttendanceSessionService will actually
                // allow when Start/End Break is clicked (same shared method).
                $breakSlots = \App\Libraries\AttendanceWindow::liveBreakSlots($currentAttendance, $positionRow ?? []);
                $totalBreakSlots = count($breakSlots);
                $breakMinutesTotal = array_sum($breakSlots);
                $taken = (int) ($currentAttendance['breaks_taken'] ?? 0);
                $nextBreakMinutes = $breakSlots[$taken] ?? 0;

                // The current (not-yet-taken) slot's time-of-day policy, if
                // any — drives whether Start Break is disabled right now.
                if (empty($currentAttendance['break_start']) && $taken < $totalBreakSlots) {
                    $breakWindows = \App\Libraries\AttendanceWindow::liveBreakWindows($positionRow ?? []);
                    $breakWindow = $breakWindows[$taken] ?? null;
                    $breakWindowAllowed = \App\Libraries\AttendanceWindow::withinBreakWindow($breakWindow, $currentAttendance, (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s'));
                }

                [$shiftStart, $shiftEnd] = \App\Libraries\AttendanceWindow::bounds($currentAttendance, $positionRow ?? []);
                if ($shiftStart && $shiftEnd) $shiftMinutes = max(1, (int) (($shiftEnd->getTimestamp() - $shiftStart->getTimestamp()) / 60) - $breakMinutesTotal);
                $currentAttendance['scheduled_end_datetime'] = $shiftEnd?->format('Y-m-d H:i:s');
            } catch (\InvalidArgumentException $e) {
                // Legacy/malformed schedule data (e.g. an out-of-range break_schedule
                // value saved before validation existed) must not crash the dashboard
                // for an employee who is currently clocked in — fall back to defaults
                // and let them still see the page and clock out.
                log_message('warning', 'Dashboard schedule fallback for user {uid}: {msg}', [
                    'uid' => $userId,
                    'msg' => $e->getMessage(),
                ]);
            }
        }
        return view('client_portal/dashboard', [
            'attendanceToken' => session()->get('attendance_csrf'),
            'nextBreakMinutes' => $nextBreakMinutes,
            'shiftMinutes' => $shiftMinutes,
            // Used by the Overtime request form: OT starts when the shift ends, on the employee's working days.
            'otShiftEnd' => !empty($positionRow['end_time']) ? substr((string) $positionRow['end_time'], 0, 5) : null,
            'otWorkingDays' => (static function ($raw) { $d = json_decode((string) $raw, true); return is_array($d) ? array_values($d) : []; })($positionRow['working_days'] ?? null),
            'currentAttendance' => $currentAttendance,
            'requests' => $requests,
            'savedTasks' => $savedTasks,
            'publishedNotifications' => $publishedNotifications,
            'unpublishedNotifications' => $unpublishedNotifications,
            'breakMinutesTotal' => $breakMinutesTotal,
            'totalBreakSlots' => $totalBreakSlots,
            'breakAutoEnded' => $breakAutoEnded,
            'breakWindow' => $breakWindow,
            'breakWindowAllowed' => $breakWindowAllowed,
        ]);
    }
}
