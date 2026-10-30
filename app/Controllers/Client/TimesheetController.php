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
                throw new \DomainException('This correction overlaps with another attendance record for this employee. If that record is also never clocked out, correct the older one first.');
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
                // Closing the shift also closes any break left running; the
                // corrected break_duration above is the authoritative total.
                'break_start' => null, 'break_start_dt' => null,
                'total_hours' => \App\Libraries\AttendanceWindow::elapsedHours($newTimeIn, $newTimeOut, $breakHours),
                'night_diff_hours' => $analysis['night_diff_hours'],
                'late_minutes' => \App\Services\Client\AttendanceComputationService::lateMinutes(new \DateTime($newTimeIn), $windowStart, (int) ($schedule['tardiness_grace_minutes'] ?? 0)),
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
        return \App\Libraries\PayrollCutoff::forDate(date('Y-m-d', strtotime($date)));
    }

    public function historyList()
    {
        $user = auth()->user();

        return $this->response->setJSON(
            $this->timesheetsService->listCutoffHistoryByUserId((int) $user->id)
        );
    }

    public function historyDetails()
    {
        $user = auth()->user();

        $start = $this->request->getGet('start');
        $end = $this->request->getGet('end');

        if (!is_string($start) || !is_string($end) || $start === '' || $end === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Start and end dates are required.']);
        }

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

        $start = (string) $this->request->getGet('start');
        $end = (string) $this->request->getGet('end');

        $employee = (new EmployeeModel())->where('user_id', $user->id)->first();
        if (!$employee) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Employee profile not found']);
        }

        // Same XLSX as Payroll's "Export Timesheet" — see
        // PayrollController::buildTimesheetWorkbook()'s docblock for why this
        // is shared rather than a second, easily-drifting CSV builder.
        return $this->payrollController()->buildTimesheetWorkbook($employee, $start, $end);
    }

    /**
     * A PayrollController instance, fully initialized the same way the
     * framework would for a routed request (request/response/logger), so its
     * methods that touch $this->request / $this->response work correctly
     * even though we're calling it directly instead of through its own route.
     */
    private function payrollController(): \App\Controllers\Client\PayrollController
    {
        $controller = new \App\Controllers\Client\PayrollController();
        $controller->initController($this->request, $this->response, service('logger'));

        return $controller;
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

        // Same scoping as the correction itself (canReviewEmployee): a Team Head
        // only sees, and can only fix, their own team, not every employee.
        $teamOnly = !\App\Libraries\PortalAccess::can('attendance');
        $teamIds  = $teamOnly ? (\App\Libraries\PortalAccess::teamUserIds() ?: [0]) : [];
        $limit    = 200;

        $stuckQuery = $db->table('attendance a')
            ->select("a.id, a.user_id, e.employee_number, {$nameExpr}, a.time_in, a.break_duration, a.break_start, a.scheduled_start, a.scheduled_end, a.shift_date", false)
            ->join('employees e', 'e.user_id = a.user_id', 'left')
            ->where('a.time_out', null)
            ->where('a.time_in <', date('Y-m-d H:i:s', strtotime('-16 hours')));
        if ($teamOnly) $stuckQuery->whereIn('a.user_id', $teamIds);
        $stuckOpen = $stuckQuery->orderBy('a.time_in', 'ASC')->limit($limit)->get()->getResultArray();

        // Pre-fill Time Out with the end of the scheduled shift so the reviewer
        // only confirms or adjusts it instead of typing it from scratch.
        foreach ($stuckOpen as &$row) {
            $row['suggested_time_out'] = null;
            if (!empty($row['scheduled_start']) && !empty($row['scheduled_end'])) {
                $date  = $row['shift_date'] ?: substr($row['time_in'], 0, 10);
                $start = strtotime($date . ' ' . $row['scheduled_start']);
                $end   = strtotime($date . ' ' . $row['scheduled_end']);
                if ($end <= $start) $end += 86400;
                if ($end > strtotime($row['time_in'])) $row['suggested_time_out'] = date('Y-m-d H:i:s', $end);
            }
        }
        unset($row);

        $badQuery = $db->table('attendance a')
            ->select("a.id, a.user_id, e.employee_number, {$nameExpr}, a.time_in, a.time_out, a.total_hours, a.break_duration", false)
            ->join('employees e', 'e.user_id = a.user_id', 'left')
            ->groupStart()->where('a.total_hours <', 0)->orWhere('a.total_hours >', 24)->groupEnd();
        if ($teamOnly) $badQuery->whereIn('a.user_id', $teamIds);
        $badHours = $badQuery->orderBy('a.time_in', 'DESC')->limit($limit)->get()->getResultArray();

        return $this->response->setJSON([
            'stuck_open' => $this->annotateAttendanceRows($stuckOpen),
            'bad_hours'  => $this->annotateAttendanceRows($badHours),
        ]);
    }

    /**
     * Every attendance record for one employee in a date range, flagged or not,
     * so a reviewer can correct a wrong-but-plausible time too (the flagged list
     * only catches stuck-open and impossible-total rows). Same permission rule
     * as the correction itself: canReviewEmployee(), so a Team Head only ever
     * reaches their own team. Paged so a long range stays cheap.
     */
    public function attendanceRecords()
    {
        $employeeId = (int) $this->request->getGet('employee_id');
        if (!$employeeId || !\App\Libraries\PortalAccess::canReviewEmployee($employeeId)) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $employee = (new EmployeeModel())->find($employeeId);
        if (!$employee) {
            return $this->response->setJSON(['error' => 'Employee not found']);
        }

        $isDate = static fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) !== false;
        $to   = $this->request->getGet('to');
        $from = $this->request->getGet('from');
        $to   = $isDate($to) ? $to : date('Y-m-d');
        $from = $isDate($from) ? $from : date('Y-m-d', strtotime($to . ' -30 days'));
        if ($from > $to) [$from, $to] = [$to, $from];
        if (strtotime($to) - strtotime($from) > 366 * 86400) {
            return $this->response->setJSON(['error' => 'Please choose a range of a year or less.']);
        }

        $perPage = 25;
        $page    = max(1, (int) $this->request->getGet('page'));

        $db = db_connect();
        $base = static function () use ($db, $employee, $from, $to) {
            return $db->table('attendance a')
                ->where('a.user_id', (int) $employee['user_id'])
                ->where('a.time_in >=', $from . ' 00:00:00')
                ->where('a.time_in <=', $to . ' 23:59:59');
        };

        $total = $base()->countAllResults();
        $rows  = $base()
            ->select('a.id, a.user_id, a.time_in, a.time_out, a.total_hours, a.break_duration, a.break_start, a.late_minutes, a.attendance_status, a.scheduled_start, a.scheduled_end, a.shift_date')
            ->orderBy('a.time_in', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()->getResultArray();

        return $this->response->setJSON([
            'employee_name' => trim(($employee['last_name'] ?? '') . ', ' . ($employee['first_name'] ?? ''), ', '),
            'from'      => $from,
            'to'        => $to,
            'page'      => $page,
            'per_page'  => $perPage,
            'total'     => $total,
            'records'   => $this->annotateAttendanceRows($rows),
        ]);
    }

    /**
     * Adds what a reviewer needs to know before changing a row:
     *  - payslip_issued: a payslip already covers that day, so editing the
     *    attendance will NOT change what was already generated/sent;
     *  - last_correction: who last corrected it and when (from the activity log).
     * Both are looked up in one query each for the whole page, not per row.
     */
    private function annotateAttendanceRows(array $rows): array
    {
        if (!$rows) return $rows;
        $db = db_connect();

        $userIds = array_values(array_unique(array_map('intval', array_column($rows, 'user_id'))));
        $slips = $db->table('employee_payslips p')
            ->select('e.user_id, p.period_start, p.period_end, p.sent_at')
            ->join('employees e', 'e.id = p.employee_id')
            ->whereIn('e.user_id', $userIds)
            ->get()->getResultArray();

        $ids = array_map('intval', array_column($rows, 'id'));
        $logs = $db->table('activity_logs')
            ->select('record_id, username, created_at')
            ->where('module', 'attendance')
            ->where('action', 'updated')
            ->like('description', 'Attendance corrected', 'after')
            ->whereIn('record_id', $ids)
            ->orderBy('created_at', 'DESC')
            ->get()->getResultArray();
        $lastCorrection = [];
        foreach ($logs as $log) {
            $lastCorrection[(int) $log['record_id']] ??= ['by' => $log['username'], 'at' => $log['created_at']];
        }

        foreach ($rows as &$row) {
            $day = $row['shift_date'] ?? null ?: substr((string) $row['time_in'], 0, 10);
            // payslip_issued: a SENT payslip covers the day (the employee's copy
            // won't change). payslip_unsent: only a draft/scheduled one does —
            // it's held at send time until re-saved (see deliverPayslip()).
            $row['payslip_issued'] = false;
            $row['payslip_unsent'] = false;
            foreach ($slips as $slip) {
                if ((int) $slip['user_id'] === (int) $row['user_id'] && $slip['period_start'] <= $day && $day <= $slip['period_end']) {
                    if (!empty($slip['sent_at'])) {
                        $row['payslip_issued'] = true;
                        break;
                    }
                    $row['payslip_unsent'] = true;
                }
            }
            $row['last_correction'] = $lastCorrection[(int) $row['id']] ?? null;
        }
        unset($row);
        return $rows;
    }

    /** Task hours per client for the signed-in employee's own tasks. */
    public function clientHours()
    {
        $user = auth()->user();
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthenticated']);
        }
        return $this->clientHoursResponse([(int) $user->id]);
    }

    /**
     * The same report for reviewers: one employee, or everyone they may review when none is chosen
     * (everyone for attendance reviewers, their department's people for a Team Head).
     */
    public function adminClientHours()
    {
        if (!\App\Libraries\PortalAccess::reviewsAttendance()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $employeeParam = $this->request->getGet('employee_id');
        if ($employeeParam !== null && $employeeParam !== '' && !is_string($employeeParam)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid employee.']);
        }

        $db = db_connect();
        if ((int) $employeeParam > 0) {
            if (!\App\Libraries\PortalAccess::canReviewEmployee((int) $employeeParam)) {
                return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
            }
            $row = $db->table('employees')->select('user_id')->where('id', (int) $employeeParam)->get()->getRowArray();
            $userIds = $row && $row['user_id'] ? [(int) $row['user_id']] : [];
        } else {
            $query = $db->table('employees')->select('user_id')->where('user_id IS NOT NULL', null, false);
            if (!\App\Libraries\PortalAccess::can('attendance')) {
                $query->whereIn('user_id', \App\Libraries\PortalAccess::teamUserIds() ?: [0]);
            }
            $userIds = array_map('intval', array_column($query->get()->getResultArray(), 'user_id'));
        }

        return $this->clientHoursResponse($userIds);
    }

    private function clientHoursResponse(array $userIds)
    {
        $start = $this->request->getGet('start');
        $end   = $this->request->getGet('end');
        $client = $this->request->getGet('client_id');
        $valid = static fn ($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;

        if (!$valid($start) || !$valid($end) || $start > $end) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Choose a valid start and end date.']);
        }
        // Keeps one request bounded however many people and tasks are in range.
        if ((strtotime($end) - strtotime($start)) / 86400 > 366) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Choose a range of one year or less.']);
        }
        if ($client !== null && $client !== '' && !is_string($client)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid client.']);
        }

        try {
            return $this->response->setJSON($this->timesheetsService->clientHours(
                $userIds, $start, $end, is_string($client) && $client !== '' ? (int) $client : null
            ));
        } catch (\Throwable $e) {
            // A ValidationException's message (e.g. "too many tasks, narrow the range") is shown as is, as a 422.
            return $this->jsonError($e, 'Unable to load client hours.');
        }
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

        // Same XLSX as Payroll's "Export Timesheet" — see
        // PayrollController::buildTimesheetWorkbook()'s docblock.
        return $this->payrollController()->buildTimesheetWorkbook($employee, (string) $start, (string) $end);
    }
}
