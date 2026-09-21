<?php

namespace App\Services\Client;

use App\Models\Client\AttendanceModel;
use App\Models\Client\TasksModel;
use App\Models\Client\RequestModel;
use App\Models\Client\EmployeePositionModel;
use App\Models\Client\SavedClientsModel;
use App\Models\Client\SavedTasksModel;
use DateTime;

class TimesheetsService
{
    protected TasksModel $tasksModel;
    protected AttendanceModel $attendanceModel;
    protected RequestModel $requestModel;
    protected EmployeePositionModel $employeePositionModel;
    protected AttendanceComputationService $computationService;

    public function __construct()
    {
        $this->tasksModel      = new TasksModel();
        $this->attendanceModel = new AttendanceModel();
        $this->requestModel    = new RequestModel();
        $this->employeePositionModel = new EmployeePositionModel();
        $this->computationService = new AttendanceComputationService();
    }

    // ---------- Existing methods ----------
    public function listByUserId(int $userId): array
    {
        return $this->baseListByUserIdBuilder($userId)
            ->orderBy('attendance.time_in', 'DESC')
            ->orderBy('tasks.start_time', 'ASC')
            ->findAll();
    }

    public function listDailyTimeLogByUserId(int $userId): array
    {
        return $this->attendanceModel
            ->select('id, user_id, time_in, break_start, break_end, break_duration, time_out, total_hours')
            ->where('user_id', $userId)
            ->orderBy('time_in', 'DESC')
            ->findAll();
    }

    public function listCurrentCutoffByUserId(int $userId): array
    {
        $now = date('Y-m-d');
        $period = $this->resolveCutoffByDate($now);

        return $this->baseListByUserIdBuilder($userId)
            ->where('COALESCE(attendance.shift_date, DATE(attendance.time_in)) >=', $period['start'])
            ->where('COALESCE(attendance.shift_date, DATE(attendance.time_in)) <=', $period['end'])
            ->orderBy('attendance.time_in', 'DESC')
            ->orderBy('tasks.start_time', 'ASC')
            ->findAll();
    }

    public function listCutoffHistoryByUserId(int $userId): array
    {
        $rows = $this->listByUserId($userId);
        $currentKey = $this->cutoffKeyByDate(date('Y-m-d'));
        $groups = [];

        foreach ($rows as $row) {
            $date = (string) ($row['date'] ?? '');
            if ($date === '') continue;

            $period = $this->resolveCutoffByDate($date);
            $key = $period['start'] . '|' . $period['end'];
            if ($key === $currentKey) continue;

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'period_start' => $period['start'],
                    'period_end'   => $period['end'],
                    'total_minutes' => 0,
                    'entries_count' => 0,
                    'days' => [],
                ];
            }

            $groups[$key]['total_minutes'] += $this->durationMinutes(
                (string) ($row['start_time'] ?? ''),
                (string) ($row['end_time'] ?? '')
            );
            $groups[$key]['entries_count']++;
            $groups[$key]['days'][$date] = true;
        }

        krsort($groups);
        $result = [];
        foreach ($groups as $group) {
            $totalMinutes = (int) $group['total_minutes'];
            $result[] = [
                'period_start'  => $group['period_start'],
                'period_end'    => $group['period_end'],
                'total_minutes' => $totalMinutes,
                'total_hours'   => number_format($totalMinutes / 60, 2, '.', ''),
                'entries_count' => (int) $group['entries_count'],
                'days_covered'  => count($group['days']),
            ];
        }
        return $result;
    }

    public function updateByUserId(int $taskId, int $userId, array $payload): array
    {
        $task = $this->tasksModel->where('id', $taskId)->where('user_id', $userId)->first();
        if (!$task) return ['success' => false, 'message' => 'Task not found'];

        $start         = array_key_exists('start_time', $payload) ? trim((string) $payload['start_time']) : $task['start_time'];
        $end           = array_key_exists('end_time', $payload)   ? trim((string) $payload['end_time'])   : $task['end_time'];
        $taskPerformed = array_key_exists('task_performed', $payload) ? trim((string) $payload['task_performed']) : $task['task_performed'];
        $client        = array_key_exists('client', $payload) ? trim((string) $payload['client']) : (string) ($task['client'] ?? '');

        if ($taskPerformed === '') {
            return ['success' => false, 'message' => 'Task description is required.'];
        }

        $attendance = $this->attendanceModel->find((int) $task['attendance_id']);
        if (!$attendance) {
            return ['success' => false, 'message' => 'Attendance record not found'];
        }

        $error = $this->validateTaskWindow($attendance, $start, $end, $taskId);
        if ($error) {
            return ['success' => false, 'message' => $error];
        }

        $old = [
            'start_time'     => $task['start_time'],
            'end_time'       => $task['end_time'],
            'task_performed' => $task['task_performed'],
        ];
        $newData = [
            'start_time'     => $start,
            'end_time'       => $end,
            'task_performed' => $taskPerformed,
            'client'         => $client !== '' ? $client : null,
        ];

        $ok = $this->tasksModel->update($taskId, $newData);
        if ($ok) service('activityLogger')->taskUpdated($taskId, $old, $newData);

        return ['success' => (bool) $ok, 'message' => $ok ? null : 'Unable to update task.'];
    }

    public function deleteByUserId(int $taskId, int $userId): bool
    {
        $task = $this->tasksModel->where('id', $taskId)->where('user_id', $userId)->first();
        if (!$task) return false;

        $deleted = $this->tasksModel->delete($taskId);
        if ($deleted) service('activityLogger')->taskDeleted($taskId, $task);
        return $deleted;
    }

    public function listTasksForAttendance(int $attendanceId, int $userId): array
    {
        $attendance = $this->attendanceModel->where('id', $attendanceId)->where('user_id', $userId)->first();
        if (!$attendance) {
            return ['success' => false, 'message' => 'Attendance record not found'];
        }

        $tasks = $this->tasksModel
            ->where('attendance_id', $attendanceId)
            ->where('user_id', $userId)
            ->orderBy('start_time', 'ASC')
            ->findAll();

        return [
            'success' => true,
            'tasks'   => $tasks,
            'window'  => [
                'time_in'  => $attendance['time_in'],
                'time_out' => $attendance['time_out'],
            ],
        ];
    }

    public function createTask(int $attendanceId, int $userId, array $payload): array
    {
        $attendance = $this->attendanceModel->where('id', $attendanceId)->where('user_id', $userId)->first();
        if (!$attendance) {
            return ['success' => false, 'message' => 'Attendance record not found'];
        }

        $start         = trim((string) ($payload['start_time'] ?? ''));
        $end           = trim((string) ($payload['end_time']   ?? ''));
        $taskPerformed = trim((string) ($payload['task_performed'] ?? ''));
        $client        = trim((string) ($payload['client'] ?? ''));

        if ($taskPerformed === '') {
            return ['success' => false, 'message' => 'Task description is required.'];
        }

        $error = $this->validateTaskWindow($attendance, $start, $end);
        if ($error) {
            return ['success' => false, 'message' => $error];
        }

        $id = $this->tasksModel->insert([
            'attendance_id'  => $attendanceId,
            'user_id'        => $userId,
            'start_time'     => $start,
            'end_time'       => $end,
            'task_performed' => $taskPerformed,
            'client'         => $client !== '' ? $client : null,
            'saved_at'       => date('Y-m-d H:i:s'),
        ]);

        if (!$id) {
            return ['success' => false, 'message' => 'Unable to save task.'];
        }

        service('activityLogger')->taskLogged((int) $id, $attendanceId, [
            'start_time'     => $start,
            'end_time'       => $end,
            'task_performed' => $taskPerformed,
        ]);

        if (!empty($payload['save_task'])) {
            $this->saveAsPreset($userId, $taskPerformed);
        }

        if (!empty($payload['save_client']) && $client !== '') {
            $this->saveClientPreset($userId, $client);
        }

        return ['success' => true, 'task' => $this->tasksModel->find($id)];
    }

    /**
     * Saves a task description as a reusable preset (the "Select Preset Task"
     * dropdown), skipping the insert if this user already has an identical one.
     */
    private function saveClientPreset(int $userId, string $client): void
    {
        $model = new SavedClientsModel();
        $exists = $model->where('user_id', $userId)->where('client', $client)->first();
        if ($exists) return;

        $model->insert([
            'user_id'  => $userId,
            'client'   => $client,
            'saved_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function saveAsPreset(int $userId, string $taskPerformed): void
    {
        $savedTasksModel = new SavedTasksModel();
        $exists = $savedTasksModel
            ->where('user_id', $userId)
            ->where('task_performed', $taskPerformed)
            ->first();
        if ($exists) return;

        $savedTasksModel->insert([
            'user_id'        => $userId,
            'task_performed' => $taskPerformed,
            'saved_at'       => date('Y-m-d H:i:s'),
        ]);

        service('activityLogger')->savedTaskCreated((int) $savedTasksModel->getInsertID(), $taskPerformed);
    }

    /**
     * Validates a task's [start,end) window against its attendance's clock-in/out
     * span (capped at "now" if still clocked in) and against every other task
     * already logged on that same attendance record. Times are anchored to the
     * attendance's clock-in date, with a same-pattern +1 day roll for spans that
     * cross midnight (mirrors the regular/OT hour math in getDetailedTimesheet()).
     * Returns an error message, or null if the window is valid.
     */
    private function validateTaskWindow(array $attendance, string $start, string $end, ?int $excludeTaskId = null): ?string
    {
        if ($start === '' || $end === '') {
            return 'Start and end time are required.';
        }
        if (strtotime($start) === false || strtotime($end) === false) {
            return 'Invalid time format.';
        }
        if ($start === $end) {
            return 'Start and end time cannot be the same.';
        }

        $baseDate = date('Y-m-d', strtotime($attendance['time_in']));
        $winStart = new DateTime($attendance['time_in']);
        // Floor to the minute — the real clock-in timestamp almost always has
        // non-zero seconds (e.g. 10:31:38), but <input type="time"> can only
        // submit whole minutes (10:31:00) and that's also all the UI displays.
        // Without this, typing the exact minute you see on screen gets
        // rejected as "before clock-in" by whatever seconds you can't enter.
        $winStart->setTime((int) $winStart->format('H'), (int) $winStart->format('i'), 0);
        $winEnd   = $attendance['time_out'] ? new DateTime($attendance['time_out']) : new DateTime();
        // Whether the session itself spans past midnight (e.g. a 10pm-6am
        // shift) — only then does a bare time in the small hours (like 05:00)
        // need to be understood as "the day after clock-in" rather than
        // "before clock-in on the same day."
        $windowCrossesMidnight = $winEnd->format('Y-m-d') !== $baseDate;

        $anchor = function (string $s, string $e) use ($baseDate, $winStart, $windowCrossesMidnight): array {
            $start = new DateTime($baseDate . ' ' . $s);
            $end   = new DateTime($baseDate . ' ' . $e);
            if ($end <= $start) $end->modify('+1 day');
            if ($windowCrossesMidnight && $end <= $winStart) {
                $start->modify('+1 day');
                $end->modify('+1 day');
            }
            return [$start, $end];
        };

        [$taskStart, $taskEnd] = $anchor($start, $end);

        if ($taskStart < $winStart) {
            return 'Task cannot start before your clock-in time (' . $winStart->format('h:i A') . ').';
        }
        if ($taskEnd > $winEnd) {
            $label = $attendance['time_out'] ? $winEnd->format('h:i A') : 'now';
            return "Task cannot end after your clock-out time ($label).";
        }

        $builder = $this->tasksModel->where('attendance_id', (int) $attendance['id']);
        if ($excludeTaskId) $builder->where('id !=', $excludeTaskId);
        $existing = $builder->findAll();

        foreach ($existing as $t) {
            [$tStart, $tEnd] = $anchor($t['start_time'], $t['end_time']);

            if ($taskStart < $tEnd && $taskEnd > $tStart) {
                return 'This time overlaps with an already-logged task ('
                    . $tStart->format('h:i A') . '–' . $tEnd->format('h:i A') . ').';
            }
        }

        return null;
    }

    // ---------- NEW: Detailed daily timesheet ----------
    public function getDetailedTimesheet(int $userId, string $startDate, string $endDate): array
    {
        $attendances = $this->attendanceModel
            ->where('user_id', $userId)
            ->where('COALESCE(shift_date, DATE(time_in)) >=', $startDate)
            ->where('COALESCE(shift_date, DATE(time_in)) <=', $endDate)
            ->orderBy('time_in', 'ASC')
            ->findAll();

        $dailyRecords = [];
        $totals = [
            'regular_hours'       => 0,
            'ot_hours'            => 0,
            'rdot_hours'          => 0,
            'tardy_minutes'       => 0,
            'night_diff'          => 0,
            'unapproved_ot_hours' => 0,
            'undertime_hours'     => 0,
        ];

        $db = db_connect();

        $employee = $db->table('employees')
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();

        $schedule = null;

        if ($employee) {
            $schedule = $this->employeePositionModel
                ->where('employee_id', $employee['id'])
                ->first();
        }

        $scheduledStart =
            $schedule['start_time']
            ?? null;

        $scheduledEnd =
            $schedule['end_time']
            ?? null;
        $nightStart     = '22:00:00';
        $nightEnd       = '06:00:00';

        // Legacy fallback only. Each attendance row below resolves its own saved schedule.
        $requiredHours = 0;
        if ($scheduledStart && $scheduledEnd) {
            $reqStart = strtotime($scheduledStart);
            $reqEnd   = strtotime($scheduledEnd);
            if ($reqEnd < $reqStart) $reqEnd += 86400;
            $requiredHours = ($reqEnd - $reqStart) / 3600;
        }

        // Batch-fetch approved overtime/undertime requests for the whole range up
        // front (one query instead of one per day) — a date only counts as "OT
        // approved" or "UT approved" if an actual approved request covers it.
        $approvedOtDates = [];
        $approvedUtDates = [];
        $approvalRequests = $this->requestModel
            ->where('requested_by', $userId)
            ->where('status', 'approved')
            ->whereIn('request_type', ['overtime', 'undertime'])
            ->where('start_date <=', $endDate)
            ->groupStart()
                ->where('end_date >=', $startDate)
                ->orWhere('end_date', null)
            ->groupEnd()
            ->findAll();
        foreach ($approvalRequests as $req) {
            $rangeStart = max(strtotime($req['start_date']), strtotime($startDate));
            $rangeEnd   = min(strtotime($req['end_date'] ?? $req['start_date']), strtotime($endDate));
            for ($t = $rangeStart; $t <= $rangeEnd; $t += 86400) {
                $d = date('Y-m-d', $t);
                if ($req['request_type'] === 'overtime') {
                    $approvedOtDates[$d] = true;
                } else {
                    $approvedUtDates[$d] = true;
                }
            }
        }

        foreach ($attendances as $att) {
            // Derive date and time components from the datetime columns
            $date    = $att['shift_date'] ?? ($att['time_in'] ? date('Y-m-d', strtotime($att['time_in'])) : null);
            try {
                $recordSchedule = \App\Libraries\AttendanceWindow::schedule($att, $schedule);
                $scheduledStart = $recordSchedule['start_time'] ?? null;
                $scheduledEnd = $recordSchedule['end_time'] ?? null;
                [$windowStart, $windowEnd] = \App\Libraries\AttendanceWindow::bounds($att, $schedule);
            } catch (\InvalidArgumentException $e) {
                // A legacy/malformed schedule (e.g. an out-of-range break_schedule
                // value saved before validation existed) on one row must not abort
                // the whole timesheet — fall back to "no schedule" for this row,
                // same as an attendance record that never had a schedule on file.
                $scheduledStart = null;
                $scheduledEnd = null;
                $windowStart = null;
                $windowEnd = null;
            }
            $requiredHours = $windowStart && $windowEnd
                ? \App\Libraries\AttendanceWindow::requiredHours(($windowEnd->getTimestamp() - $windowStart->getTimestamp()) / 3600, $recordSchedule ?? [])
                : 0;
            $timeIn  = $att['time_in']  ? date('H:i:s', strtotime($att['time_in']))  : null;
            $timeOut = $att['time_out'] ? date('H:i:s', strtotime($att['time_out'])) : null;

            if (!$date) continue;

            // Recompute the display from this attendance's saved schedule and current
            // approved requests. New schedule edits apply to future clock-ins.
            $regularHours      = 0;
            $otHours           = 0;
            $unapprovedOtHours = 0;
            $undertimeHours    = 0;
            $tardyMinutes      = 0;
            $nightDiff         = 0;
            $hasSchedule       = (bool) ($scheduledStart && $scheduledEnd);
            $hasApprovedOt     = isset($approvedOtDates[$date]);

            // Tardiness only depends on the clock-in time, not the clock-out —
            // computed here, unconditionally, so a still-open shift (clocked in
            // late, not yet clocked out) still shows it instead of silently
            // showing nothing until the employee eventually clocks out.
            if ($timeIn) {
                $tardyMinutes = AttendanceComputationService::lateMinutes(new DateTime($att['time_in']), $windowStart);
            }

            if ($timeIn && $timeOut) {
                // Use the full stored datetimes (not the date + time-of-day
                // reconstruction) so a clock-out recorded on a later calendar day
                // — e.g. a missed clock-out resolved a day or more afterward —
                // yields the true elapsed span. Reconstructing from $date (always
                // the clock-in's day) plus the time-out's time-of-day silently
                // dropped that date whenever the time-out's clock time happened to
                // fall later in the day than the clock-in's, undercounting a
                // multi-day gap as a same-day few-minute session.
                //
                // The actual regular/excess-hours math is shared with Payroll
                // aggregation (and, via analyzeClockOut(), live clock-out and
                // import) through AttendanceComputationService::splitShiftWindow(),
                // so all of those produce matching numbers for the same shift.
                $split = $this->computationService->splitShiftWindow(
                    $att['time_in'],
                    $att['time_out'],
                    (float) ($att['break_duration'] ?? 0),
                    $scheduledStart,
                    $scheduledEnd,
                    $date
                );
                $start = $split['span_start'];
                $end   = $split['span_end'];
                $regularHours = $split['regular_hours'];
                $excessHours  = $split['excess_hours'];
                if ($hasApprovedOt) {
                    $otHours = $excessHours;
                } else {
                    $unapprovedOtHours = $excessHours;
                }

                // Undertime = shortfall against the scheduled window itself — time
                // during the shift the employee simply wasn't there (late arrival,
                // early leave, etc.), independent of any OT worked outside it.
                if ($hasSchedule) {
                    $undertimeHours = max(0, round($requiredHours - $regularHours, 2));
                }


                // Night Differential — only counted for the portion of the shift
                // that's actually paid (the scheduled window, plus any OT hours
                // that were approved). Unapproved excess worked at night isn't
                // recognized as OT, so it isn't paid a night differential either.
                if ($hasApprovedOt || !$hasSchedule) {
                    $paidStart = $start;
                    $paidEnd   = $end;
                } else {
                    $paidStart = max($start, $split['window_start']);
                    $paidEnd   = min($end, $split['window_end']);
                }
                $nightDiff = $this->computeNightDiffRange($paidStart, $paidEnd, $date, $nightStart, $nightEnd);
            }

            // RDOT (Rest Day OT – example: Sunday)
            $isRestDay = (date('N', strtotime($date)) == 7);
            $rdotHours = 0;
            if ($isRestDay && $timeIn && $timeOut) {
                $rdotHours = $regularHours;
            }

            // Remarks (leave or task)
            $remarks = $this->getRemarksForDay($userId, $date);

            // All tasks logged against this exact attendance session (scoped by
            // attendance_id, not by calendar date — an employee with two clock
            // sessions on the same day gets two independent task lists).
            $taskRows = $this->tasksModel
                ->where('attendance_id', $att['id'])
                ->orderBy('start_time', 'ASC')
                ->findAll();

            $tasks = array_map(fn($t) => [
                'id'             => (int) $t['id'],
                'start_time'     => $t['start_time'],
                'end_time'       => $t['end_time'],
                'task_performed' => $t['task_performed'],
                'client'         => $t['client'],
            ], $taskRows);

            // Backward-compatible joined-string summary — still what CSV export
            // and the read-only admin review tables render.
            $taskPerformed = implode('; ', array_column($tasks, 'task_performed'));
            $taskClient    = implode(', ', array_unique(array_filter(array_column($tasks, 'client'))));

            $dailyRecords[] = [
                'attendance_id'       => $att['id'],
                'time_in_datetime'    => $att['time_in'],
                'time_out_datetime'   => $att['time_out'],
                'break_hours'         => (float) ($att['break_duration'] ?? 0),
                'out_date'            => $att['time_out'] ? substr($att['time_out'], 0, 10) : null,
                'state_version'       => (int) ($att['state_version'] ?? 0),
                'date'                => $date,
                'in'                  => $timeIn ? date('h:i A', strtotime($timeIn)) : '-',
                'out'                 => $timeOut ? date('h:i A', strtotime($timeOut)) : '-',
                'regular_hours'       => $regularHours,
                'ot_hours'            => $otHours,
                'rdot_hours'          => $rdotHours,
                'tardy_minutes'       => $tardyMinutes,
                'night_diff'          => $nightDiff,
                'unapproved_ot_hours' => $unapprovedOtHours,
                'undertime_hours'     => $undertimeHours,
                'remarks'             => $remarks,
                'task_performed'      => $taskPerformed,
                'client'              => $taskClient,
                'tasks'               => $tasks,
            ];

            $totals['regular_hours']       += $regularHours;
            $totals['ot_hours']            += $otHours;
            $totals['rdot_hours']          += $rdotHours;
            $totals['tardy_minutes']       += $tardyMinutes;
            $totals['night_diff']          += $nightDiff;
            $totals['unapproved_ot_hours'] += $unapprovedOtHours;
            $totals['undertime_hours']     += $undertimeHours;
        }

        return [
            'daily'  => $dailyRecords,
            'totals' => $totals,
        ];
    }

    /**
     * Night differential hours within [$start, $end] — restricted to whatever
     * portion of the shift is actually paid (the caller passes the paid range,
     * e.g. the schedule window when overtime wasn't approved), not necessarily
     * the full clock-in/clock-out span.
     */
    private function computeNightDiffRange(DateTime $start, DateTime $end, string $date, string $nightStart, string $nightEnd): float
    {
        if ($start >= $end) return 0.0;

        return \App\Libraries\AttendanceWindow::nightHours($start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'));
    }

    private function getRemarksForDay(
        int $userId,
        string $date
    ): string
    {
        // Approved leave request
        $request = $this->requestModel
            ->where('requested_by', $userId)
            ->where('status', 'approved')
            ->groupStart()
                ->where('start_date <=', $date)
                ->where('end_date >=', $date)
            ->groupEnd()
            ->first();

        if ($request)
        {
            return ucfirst($request['request_type'])
                . ' (' . $request['reason'] . ')';
        }

        $attendance = $this->attendanceModel
            ->where('user_id', $userId)
            ->where('COALESCE(shift_date, DATE(time_in))', $date)
            ->first();

        if ($attendance)
        {
            switch (
                $attendance['attendance_status']
                ?? 'present'
            )
            {
                case 'approved_undertime':
                    return 'Approved Undertime';

                case 'unapproved_undertime':
                    return 'Unapproved Undertime';

                case 'approved_overtime':
                    return 'Approved Overtime';

                case 'unapproved_overtime':
                    return 'Unapproved Overtime';

                case 'present':
                default:
                    break;
            }
        }

        // Task descriptions have their own column now (see getDetailedTimesheet's
        // 'task_performed' field) — no fallback needed here.
        return '';
    }
    // ---------- Private helpers ----------
    private function baseListByUserIdBuilder(int $userId): TasksModel
    {
        return $this->tasksModel
            ->select('COALESCE(attendance.shift_date, DATE(attendance.time_in)) AS date, tasks.start_time, tasks.end_time, employee_positions.department, tasks.task_performed, tasks.id, tasks.user_id, tasks.attendance_id')
            ->join('attendance', 'attendance.id = tasks.attendance_id')
            ->join('employees', 'employees.user_id = tasks.user_id')
            ->join('employee_positions', 'employee_positions.employee_id = employees.id')
            ->where('tasks.user_id', $userId);
    }

    private function resolveCutoffByDate(string $date): array
    {
        return \App\Libraries\PayrollCutoff::forDate((new DateTime($date))->format('Y-m-d'));
    }

    private function cutoffKeyByDate(string $date): string
    {
        $period = $this->resolveCutoffByDate($date);
        return $period['start'] . '|' . $period['end'];
    }

    private function durationMinutes(string $startTime, string $endTime): int
    {
        if ($startTime === '' || $endTime === '') return 0;
        $startStamp = strtotime($startTime);
        $endStamp = strtotime($endTime);
        if ($startStamp === false || $endStamp === false || $endStamp <= $startStamp) return 0;
        return (int) floor(($endStamp - $startStamp) / 60);
    }
}
