<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Services\Client\TimesheetsService;
use App\Models\Client\AttendanceModel;
use App\Models\Client\EmployeeModel;

class TimesheetController extends BaseController
{
    protected TimesheetsService $timesheetsService;

    public function __construct()
    {
        $this->timesheetsService = new TimesheetsService();
    }

    public function index()
    {
        $user = auth()->user();
        if (!$user) return redirect()->to('/login');

        $userId = (int) $user->id;
        $now = date('Y-m-d');
        $period = $this->resolveCutoffByDate($now);

        // Detailed timesheet for the current cutoff
        $detailed = $this->timesheetsService->getDetailedTimesheet($userId, $period['start'], $period['end']);

        // Existing data for other parts of the view
        $timesheet = $this->timesheetsService->listCurrentCutoffByUserId($userId);
        $timesheetHistory = $this->timesheetsService->listCutoffHistoryByUserId($userId);
        $dailyTimeLog = $this->timesheetsService->listDailyTimeLogByUserId($userId);

        return view('client_portal/timesheet', [
            'timesheet'         => $timesheet,
            'timesheetHistory'  => $timesheetHistory,
            'dailyTimeLog'      => $dailyTimeLog,
            'currentUserId'     => $userId,
            // New data for Excel‑style table
            'dailyTimesheet'    => $detailed['daily'],
            'totals'            => $detailed['totals'],
            'period_start'      => $period['start'],
            'period_end'        => $period['end'],
            'isPayrollAdmin'    => $this->isPayrollAdmin(),
        ]);
    }

    public function dailyTimeLog()
    {
        $user = auth()->user();
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated.']);
        }
        $dailyTimeLog = $this->timesheetsService->listDailyTimeLogByUserId((int) $user->id);
        return $this->response->setJSON($dailyTimeLog);
    }

    // AJAX endpoint: list every task logged against one attendance session,
    // plus the clock-in/out window they must fit within.
    public function listTasks(int $attendanceId)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        return $this->response->setJSON(
            $this->timesheetsService->listTasksForAttendance($attendanceId, (int) $user->id)
        );
    }

    // AJAX endpoint: log a new task against an attendance session.
    public function createTask(int $attendanceId)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        $data = $this->request->getJSON(true) ?? [];

        return $this->response->setJSON(
            $this->timesheetsService->createTask($attendanceId, (int) $user->id, $data)
        );
    }

    // AJAX endpoint: edit an existing task (looked up by its own id).
    public function updateTask(int $taskId)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        $data = $this->request->getJSON(true) ?? [];

        return $this->response->setJSON(
            $this->timesheetsService->updateByUserId($taskId, (int) $user->id, $data)
        );
    }

    // AJAX endpoint: delete a task (looked up by its own id).
    public function deleteTask(int $taskId)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        $deleted = $this->timesheetsService->deleteByUserId($taskId, (int) $user->id);

        return $this->response->setJSON([
            'success' => $deleted,
            'message' => $deleted ? null : 'Task not found',
        ]);
    }

    /**
     * Admin/payroll correction for a broken or stuck-open attendance record —
     * e.g. a session never closed (time_out NULL), or a bad time_in/time_out
     * pair. This previously let ANY employee silently rewrite their OWN
     * clocked times (only `where('user_id', $user->id)`, no permission check
     * beyond being logged in) with no audit trail, and recomputed totals with
     * a fourth, independent, schedule-unaware formula that didn't touch OT,
     * undertime, or attendance_status at all. Now: gated the same way every
     * other cross-employee attendance action is (canReviewEmployee()), full
     * datetimes required (not a bare time glued onto an unknown date — how a
     * NULL time_out could ever have been "corrected" here before), recomputed
     * through the same AttendanceComputationService/AttendanceWindow path
     * live clock-out, import, Timesheet, and Payroll all share, and logged.
     */
    public function updateAttendance(int $attendanceId)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated']);
        }

        $attModel = new AttendanceModel();
        $attendance = $attModel->find($attendanceId);
        if (!$attendance) {
            return $this->response->setJSON(['success' => false, 'message' => 'Attendance record not found']);
        }

        $employeeRow = db_connect()->table('employees')->where('user_id', (int) $attendance['user_id'])->get()->getRowArray();
        $employeeId  = (int) ($employeeRow['id'] ?? 0);
        if (!$employeeId || !\App\Libraries\PortalAccess::canReviewEmployee($employeeId, $user)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $data = $this->request->getJSON(true) ?? [];
        $pattern = '/^\d{4}-\d{2}-\d{2} ([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/';
        $newTimeIn  = trim((string) ($data['time_in']  ?? ''));
        $newTimeOut = trim((string) ($data['time_out'] ?? ''));
        $breakHours = isset($data['break_hours']) ? max(0.0, (float) $data['break_hours']) : (float) ($attendance['break_duration'] ?? 0);

        // This tool exists to fix or close a complete shift — including the
        // stuck-open-session case, which is exactly why time_out can't be
        // optional here the way it was before (a bare "HH:MM:SS" applied to a
        // previously-NULL time_out had no date to attach to at all).
        if (!preg_match($pattern, $newTimeIn) || !preg_match($pattern, $newTimeOut)) {
            return $this->response->setJSON(['success' => false, 'message' => 'time_in and time_out are both required, as full "YYYY-MM-DD HH:MM:SS" datetimes.']);
        }
        if (strtotime($newTimeOut) <= strtotime($newTimeIn)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Time out must be after time in.']);
        }

        $db = db_connect();
        $db->transBegin();
        try {
            // Overlap guard: this correction must not create a second clash
            // with another one of this employee's own records.
            $overlap = $attModel->where('user_id', (int) $attendance['user_id'])
                ->where('id !=', $attendanceId)
                ->where('time_in <', $newTimeOut)
                ->groupStart()->where('time_out >', $newTimeIn)->orWhere('time_out', null)->groupEnd()
                ->countAllResults();
            if ($overlap > 0) {
                throw new \DomainException('This correction overlaps with another attendance record for this employee.');
            }

            $computationService = new \App\Services\Client\AttendanceComputationService();
            // Prefer this row's own saved schedule (the policy in effect when the
            // shift happened); the employee's current schedule is only a
            // fallback for a legacy row that never got one — same rule every
            // other computation path uses.
            $currentSchedule = $computationService->getEmployeeSchedule((int) $attendance['user_id']) ?? [];
            $schedule  = \App\Libraries\AttendanceWindow::schedule($attendance, $currentSchedule);
            $shiftDate = \App\Libraries\AttendanceWindow::shiftDate($newTimeIn, $schedule);

            $effective = $attendance;
            $effective['time_in'] = $newTimeIn;
            $effective['break_duration'] = $breakHours;
            $effective['shift_date'] = $shiftDate;
            if (empty($effective['schedule_snapshot'])) {
                $effective['schedule_snapshot'] = json_encode($schedule, JSON_THROW_ON_ERROR);
            }

            $analysis = $computationService->analyzeClockOut((int) $attendance['user_id'], $newTimeOut, $effective);
            $ot = $analysis['overtime'];
            $ut = $analysis['undertime'];
            $status = $ut['approved_ut'] > 0 ? 'approved_undertime' : ($ut['unapproved_ut'] > 0 ? 'unapproved_undertime' : ($ot['approved_ot'] > 0 ? 'approved_overtime' : ($ot['unapproved_ot'] > 0 ? 'unapproved_overtime' : 'present')));
            [$windowStart] = \App\Libraries\AttendanceWindow::bounds($effective);

            $changes = [
                'time_in' => $newTimeIn, 'time_in_dt' => $newTimeIn,
                'time_out' => $newTimeOut, 'time_out_dt' => $newTimeOut,
                'shift_date' => $shiftDate,
                'schedule_snapshot' => $effective['schedule_snapshot'],
                'scheduled_start' => $schedule['start_time'] ?? null,
                'scheduled_end' => $schedule['end_time'] ?? null,
                'break_duration' => $breakHours,
                'total_hours' => \App\Libraries\AttendanceWindow::elapsedHours($newTimeIn, $newTimeOut, $breakHours),
                'night_diff_hours' => $analysis['night_diff_hours'],
                'late_minutes' => \App\Services\Client\AttendanceComputationService::lateMinutes(new \DateTime($newTimeIn), $windowStart),
                'approved_ot_hours' => $ot['approved_ot'], 'unapproved_ot_hours' => $ot['unapproved_ot'],
                'approved_ut_hours' => $ut['approved_ut'], 'unapproved_ut_hours' => $ut['unapproved_ut'],
                'attendance_status' => $status,
                'state_version' => (int) ($attendance['state_version'] ?? 0) + 1,
            ];

            if (!$attModel->update($attendanceId, $changes)) throw new \RuntimeException('Attendance update failed.');
            if (!service('activityLogger')->log('updated', 'attendance', 'Attendance corrected by ' . ($user->username ?? $user->id), $attendanceId, $attendance, $changes)) {
                throw new \RuntimeException('Attendance correction could not be logged.');
            }

            if (!$db->transStatus()) throw new \RuntimeException('Correction could not be saved.');
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            $message = ($e instanceof \DomainException) ? $e->getMessage() : 'Could not save this correction. Please try again.';
            if (!($e instanceof \DomainException)) log_message('error', 'Attendance correction failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setJSON(['success' => false, 'message' => $message]);
        }

        return $this->response->setJSON(['success' => true]);
    }

    private function resolveCutoffByDate(string $date): array
    {
        $day = (int)date('d', strtotime($date));
        if ($day <= 15) {
            return ['start' => date('Y-m-01', strtotime($date)), 'end' => date('Y-m-15', strtotime($date))];
        } else {
            return ['start' => date('Y-m-16', strtotime($date)), 'end' => date('Y-m-t', strtotime($date))];
        }
    }

    public function historyDetails()
    {
        $user = auth()->user();

        $start = $this->request->getGet('start');
        $end = $this->request->getGet('end');

        $data =
            $this->timesheetsService
                ->getDetailedTimesheet(
                    $user->id,
                    $start,
                    $end
                );

        return $this->response->setJSON($data);
    }

    public function exportHistory()
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->to('/login');
        }

        $start = $this->request->getGet('start');
        $end = $this->request->getGet('end');

        $data = $this->timesheetsService
            ->getDetailedTimesheet(
                $user->id,
                $start,
                $end
            );

        $this->streamTimesheetCsv($data['daily'], "Timesheet_{$start}_to_{$end}.csv");
    }

    private function streamTimesheetCsv(array $rows, string $filename): void
    {
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'Date',
            'Time In',
            'Time Out',
            'Regular Hours',
            'OT Hours',
            'Unapproved OT Hours',
            'RDOT Hours',
            'Tardy Minutes',
            'Undertime Hours',
            'Night Diff',
            'Remarks',
            'Client',
            'Task Performed'
        ]);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['date'] ?? '',
                $row['in'] ?? '',
                $row['out'] ?? '',
                $row['regular_hours'] ?? '',
                $row['ot_hours'] ?? '',
                $row['unapproved_ot_hours'] ?? '',
                $row['rdot_hours'] ?? '',
                $row['tardy_minutes'] ?? '',
                $row['undertime_hours'] ?? '',
                $row['night_diff'] ?? '',
                $row['remarks'] ?? '',
                $row['client'] ?? '',
                $row['task_performed'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }

    // ================= ADMIN: REVIEW AN EMPLOYEE'S TIMESHEET =================

    private function isPayrollAdmin(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return \App\Libraries\PortalAccess::reviewsAttendance($user);
    }

    /**
     * Attendance rows that need a human's attention before payroll trusts
     * them: sessions clocked in but never closed (excluding anyone genuinely
     * still on a shift right now — flagged only once a session has been open
     * long enough that it almost certainly wasn't), and rows with an
     * impossible total_hours (negative, or over 24h in one session). These
     * are exactly the kind of legacy/stuck records that had no discovery path
     * before this — an admin had to already know an attendanceId to fix one.
     */
    public function flaggedAttendance()
    {
        if (!\App\Libraries\PortalAccess::reviewsAttendance()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $db = db_connect();
        $nameExpr = "COALESCE(NULLIF(CONCAT(e.last_name, ', ', e.first_name), ', '), CONCAT('user #', a.user_id)) AS employee_name";

        $stuckOpen = $db->table('attendance a')
            ->select("a.id, a.user_id, e.employee_number, {$nameExpr}, a.time_in", false)
            ->join('employees e', 'e.user_id = a.user_id', 'left')
            ->where('a.time_out', null)
            ->where('a.time_in <', date('Y-m-d H:i:s', strtotime('-16 hours')))
            ->orderBy('a.time_in', 'ASC')
            ->get()->getResultArray();

        $badHours = $db->table('attendance a')
            ->select("a.id, a.user_id, e.employee_number, {$nameExpr}, a.time_in, a.time_out, a.total_hours", false)
            ->join('employees e', 'e.user_id = a.user_id', 'left')
            ->groupStart()->where('a.total_hours <', 0)->orWhere('a.total_hours >', 24)->groupEnd()
            ->orderBy('a.time_in', 'DESC')
            ->get()->getResultArray();

        return $this->response->setJSON(['stuck_open' => $stuckOpen, 'bad_hours' => $badHours]);
    }

    public function reviewEmployees()
    {
        if (!\App\Libraries\PortalAccess::reviewsAttendance()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }
        $query = db_connect()->table('employees')->select('id, employee_number, first_name, middle_name, last_name');
        if (!\App\Libraries\PortalAccess::can('attendance')) {
            $query->whereIn('user_id', \App\Libraries\PortalAccess::teamUserIds() ?: [0]);
        }
        $rows = $query->orderBy('last_name')->get()->getResultArray();
        foreach ($rows as &$row) {
            $row['label'] = trim($row['last_name'] . ', ' . $row['first_name'] . ' ' . ($row['middle_name'] ?? ''));
        }
        unset($row);
        return $this->response->setJSON($rows);
    }

    public function adminEmployeeTimesheetHistory()
    {
        if (!\App\Libraries\PortalAccess::canReviewEmployee((int) $this->request->getGet('employee_id'))) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $employeeId = (int) $this->request->getGet('employee_id');
        if (!$employeeId) {
            return $this->response->setJSON(['error' => 'Missing employee_id']);
        }

        try {
            $employee = (new EmployeeModel())->find($employeeId);
            if (!$employee) {
                return $this->response->setJSON(['error' => 'Employee not found']);
            }

            $userId = (int) $employee['user_id'];

            return $this->response->setJSON([
                'employee_name'  => trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')),
                'current_period' => $this->resolveCutoffByDate(date('Y-m-d')),
                'history'        => $this->timesheetsService->listCutoffHistoryByUserId($userId),
            ]);
        } catch (\Throwable $e) {
            return $this->jsonError($e, 'Unable to load employee timesheet history.');
        }
    }

    public function adminEmployeeTimesheet()
    {
        if (!\App\Libraries\PortalAccess::canReviewEmployee((int) $this->request->getGet('employee_id'))) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $employeeId = (int) $this->request->getGet('employee_id');
        $start      = $this->request->getGet('start');
        $end        = $this->request->getGet('end');

        if (!$employeeId || !$start || !$end) {
            return $this->response->setJSON(['error' => 'Missing employee_id, start, or end']);
        }

        try {
            $employee = (new EmployeeModel())->find($employeeId);
            if (!$employee) {
                return $this->response->setJSON(['error' => 'Employee not found']);
            }

            $data = $this->timesheetsService->getDetailedTimesheet((int) $employee['user_id'], $start, $end);
            $data['employee_name'] = trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));

            return $this->response->setJSON($data);
        } catch (\Throwable $e) {
            return $this->jsonError($e, 'Unable to load employee timesheet.');
        }
    }

    public function adminExportEmployeeTimesheet()
    {
        if (!\App\Libraries\PortalAccess::canReviewEmployee((int) $this->request->getGet('employee_id'))) {
            return $this->response->setStatusCode(403)->setBody('Unauthorized');
        }

        $employeeId = (int) $this->request->getGet('employee_id');
        $start      = $this->request->getGet('start');
        $end        = $this->request->getGet('end');

        if (!$employeeId || !$start || !$end) {
            return $this->response->setStatusCode(400)->setBody('Missing employee_id, start, or end');
        }

        $employee = (new EmployeeModel())->find($employeeId);
        if (!$employee) {
            return $this->response->setStatusCode(404)->setBody('Employee not found');
        }

        $data = $this->timesheetsService->getDetailedTimesheet((int) $employee['user_id'], $start, $end);
        $slug = preg_replace('/[^A-Za-z0-9_-]+/', '_', trim(($employee['first_name'] ?? '') . '_' . ($employee['last_name'] ?? '')));

        $this->streamTimesheetCsv($data['daily'], "Timesheet_{$slug}_{$start}_to_{$end}.csv");
    }
}
