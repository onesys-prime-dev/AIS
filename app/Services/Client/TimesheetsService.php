<?php

namespace App\Services\Client;

use App\Models\Client\AttendanceModel;
use App\Models\Client\TasksModel;
use App\Models\Client\RequestModel;
use App\Models\Client\EmployeePositionModel;
use App\Services\Client\ClientService;
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
        $rows = $this->attendanceModel
            ->select('id, user_id, time_in, break_start, break_end, break_duration, break_log, time_out, total_hours')
            ->where('user_id', $userId)
            ->orderBy('time_in', 'DESC')
            ->findAll();

        // break_start/break_end double as a live "currently on break?" flag:
        // endBreak() (AttendanceSessionService) clears break_start back to
        // null the moment a break ends, while break_end keeps that break's
        // end time — so once a break is over, break_start always reads blank
        // here even though one was taken (BL-042). The actual start/end pair
        // for each break is in break_log; backfill break_start for display
        // from there whenever the live flag is empty but a break happened.
        foreach ($rows as &$row) {
            if (!empty($row['break_start'])) continue; // currently on break — already correct as-is
            $log = json_decode((string) ($row['break_log'] ?? '[]'), true);
            if (is_array($log) && $log !== []) {
                $row['break_start'] = end($log)['start'] ?? null;
            }
        }
        unset($row);

        return $rows;
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

    /**
     * Previously grouped by the TASKS a user logged (joining through
     * baseListByUserIdBuilder(), a tasks-table query) and summed task
     * start/end durations as "Total Hours". That's a completely different
     * number from hours actually worked — a day with attendance but no
     * logged task didn't even appear, and a day with a task logged for only
     * part of the shift showed a fraction of the real hours. Now sourced
     * directly from attendance.total_hours (the same per-day worked-hours
     * figure shown elsewhere, e.g. Attendance Corrections), so every past
     * cutoff with attendance shows up, with its actual worked hours.
     */
    public function listCutoffHistoryByUserId(int $userId): array
    {
        $rows = $this->attendanceModel
            ->select('COALESCE(shift_date, DATE(time_in)) AS date, total_hours')
            ->where('user_id', $userId)
            ->findAll();

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
                    'total_hours'  => 0.0,
                    'entries_count' => 0,
                    'days' => [],
                ];
            }

            $groups[$key]['total_hours'] += (float) ($row['total_hours'] ?? 0);
            $groups[$key]['entries_count']++;
            $groups[$key]['days'][$date] = true;
        }

        krsort($groups);
        $result = [];
        foreach ($groups as $group) {
            $result[] = [
                'period_start'  => $group['period_start'],
                'period_end'    => $group['period_end'],
                'total_hours'   => number_format($group['total_hours'], 2, '.', ''),
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

        if ($taskPerformed === '') {
            return ['success' => false, 'message' => 'Task description is required.'];
        }

        // Clients come from the admin-managed list. Keeping the task's current client is always allowed
        // (it may have been deactivated or unassigned since); switching needs one available to this employee.
        $clientId   = (int) ($task['client_id'] ?? 0);
        $clientName = $task['client'] ?? null;
        if (array_key_exists('client_id', $payload) && (int) $payload['client_id'] !== $clientId) {
            $picked = (int) $payload['client_id'] ? (new ClientService())->resolveForUser($userId, (int) $payload['client_id']) : null;
            if (! $picked) {
                return ['success' => false, 'message' => 'Please select a client from the list.'];
            }
            $clientId   = $picked['id'];
            $clientName = $picked['name'];
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
            'client'         => $clientName,
            'client_id'      => $clientId ?: null,
        ];

        $ok = $this->tasksModel->update($taskId, $newData);
        if ($ok) service('activityLogger')->taskUpdated($taskId, $old, $newData);

        $timesChanged = $start !== $task['start_time'] || $end !== $task['end_time'];

        return [
            'success' => (bool) $ok,
            'message' => $ok ? null : 'Unable to update task.',
            'warning' => ($ok && $timesChanged) ? $this->breakOverlapWarning($attendance, $start, $end) : null,
        ];
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
                'time_in'     => $attendance['time_in'],
                'time_out'    => $attendance['time_out'],
                // Purely informational — lets the Task Log timeline mark the
                // break period so it's obvious that stretch isn't "free" to
                // log a task in, without changing what's actually allowed
                // (validateTaskWindow() doesn't check this; unchanged here).
                'break_start' => $attendance['break_start'] ?? null,
                'break_end'   => $attendance['break_end'] ?? null,
                // Every break taken (and one in progress), so the timeline can show them all.
                'breaks'      => $this->breakIntervals($attendance),
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

        if ($taskPerformed === '') {
            return ['success' => false, 'message' => 'Task description is required.'];
        }

        // A client is required and must be one the admin made available to this employee.
        $requestedClient = (int) ($payload['client_id'] ?? 0);
        if (! $requestedClient) {
            return ['success' => false, 'message' => 'Please select a client.'];
        }
        $client = (new ClientService())->resolveForUser($userId, $requestedClient);
        if (! $client) {
            return ['success' => false, 'message' => 'That client is not available to you. Please pick one from the list.'];
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
            'client'         => $client['name'],
            'client_id'      => $client['id'],
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

        return ['success' => true, 'task' => $this->tasksModel->find($id), 'warning' => $this->breakOverlapWarning($attendance, $start, $end)];
    }

    /**
     * Saves a task description as a reusable preset (the "Select Preset Task"
     * dropdown), skipping the insert if this user already has an identical one.
     */
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
     * The clocked-in span a task has to fit in, plus a closure that anchors a bare "HH:MM" start/end
     * onto real dates for it. Shared by the window validation and the break-overlap warning.
     *
     * @return array{0: DateTime, 1: DateTime, 2: \Closure}
     */
    private function taskWindowContext(array $attendance): array
    {
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

        return [$winStart, $winEnd, $anchor];
    }

    /**
     * Hours of logged tasks per client, with the detail behind every figure.
     *
     * A task's hours are its length minus any break actually taken inside it (break time isn't client
     * work, and older tasks may already overlap one). Tasks belong to the day of the shift they were
     * logged on. Also reports clocked (worked) hours for the same days, so the gap — time worked but
     * never logged to a client — is visible rather than silently missing.
     *
     * @param int[] $userIds
     * @return array<string, mixed>
     */
    /** Most tasks one Client Hours request will load; beyond this the caller is asked to narrow the range. */
    public const CLIENT_HOURS_MAX_TASKS = 30000;

    public function clientHours(array $userIds, string $start, string $end, ?int $clientId = null, ?int $maxTasks = null): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        $empty = ['range' => ['start' => $start, 'end' => $end], 'totals' => ['logged_hours' => 0.0, 'worked_hours' => 0.0, 'unlogged_hours' => 0.0, 'tasks' => 0, 'employees' => 0, 'clients' => 0], 'clients' => [], 'client_options' => []];
        if (! $userIds) return $empty;

        $db = \Config\Database::connect();

        // Bounded work however many people and days are asked for.
        $taskCount = $db->table('tasks t')
            ->join('attendance a', 'a.id = t.attendance_id')
            ->whereIn('t.user_id', $userIds)
            ->where('COALESCE(a.shift_date, DATE(a.time_in)) >=', $start)
            ->where('COALESCE(a.shift_date, DATE(a.time_in)) <=', $end)
            ->countAllResults();
        if ($taskCount > ($maxTasks ?? self::CLIENT_HOURS_MAX_TASKS)) {
            throw new \App\Exceptions\ValidationException('That is a lot of tasks (' . number_format($taskCount) . '). Pick an employee or a shorter date range.');
        }

        $rows = $db->table('tasks t')
            ->select("t.id, t.user_id, t.start_time, t.end_time, t.task_performed, t.client, t.client_id, a.id AS attendance_id, a.time_in, a.time_out, a.shift_date, a.break_log, a.break_start, CONCAT(e.first_name, ' ', e.last_name) AS employee_name", false)
            ->join('attendance a', 'a.id = t.attendance_id')
            ->join('employees e', 'e.user_id = t.user_id', 'left')
            ->whereIn('t.user_id', $userIds)
            ->where('COALESCE(a.shift_date, DATE(a.time_in)) >=', $start)
            ->where('COALESCE(a.shift_date, DATE(a.time_in)) <=', $end)
            ->orderBy('COALESCE(a.shift_date, DATE(a.time_in))', 'ASC', false)
            ->orderBy('t.start_time', 'ASC')
            ->get()->getResultArray();

        $contexts = [];
        $clients = [];
        $options = [];
        $loggedAll = 0.0;
        foreach ($rows as $r) {
            $attId = (int) $r['attendance_id'];
            $att   = ['time_in' => $r['time_in'], 'time_out' => $r['time_out'], 'break_log' => $r['break_log'], 'break_start' => $r['break_start']];
            [, , $anchor] = $contexts[$attId] ??= $this->taskWindowContext($att);
            [$ts, $te] = $anchor((string) $r['start_time'], (string) $r['end_time']);

            $seconds = max(0, $te->getTimestamp() - $ts->getTimestamp());
            $breakSeconds = 0;
            foreach ($this->breakIntervals($att) as [$bs, $be]) {
                $overlap = min($te->getTimestamp(), strtotime($be)) - max($ts->getTimestamp(), strtotime($bs));
                if ($overlap > 0) $breakSeconds += $overlap;
            }
            $hours = round(max(0, $seconds - $breakSeconds) / 3600, 2);

            $cid  = $r['client_id'] !== null ? (int) $r['client_id'] : 0;
            $name = $r['client'] !== null && trim((string) $r['client']) !== '' ? (string) $r['client'] : 'No client';
            $options[$cid] = ['id' => $cid, 'name' => $name];
            $loggedAll += $hours;
            if ($clientId !== null && $cid !== $clientId) continue;

            $date = $r['shift_date'] ?: substr((string) $r['time_in'], 0, 10);
            $uid  = (int) $r['user_id'];
            $clients[$cid]['client_id'] = $cid;
            $clients[$cid]['name']      = $name;
            $emp = &$clients[$cid]['employees'][$uid];
            $emp['user_id'] = $uid;
            $emp['name']    = trim((string) $r['employee_name']) ?: 'Employee #' . $uid;
            $emp['hours']   = round(($emp['hours'] ?? 0) + $hours, 2);
            $emp['days'][$date]['date']  = $date;
            $emp['days'][$date]['hours'] = round(($emp['days'][$date]['hours'] ?? 0) + $hours, 2);
            $emp['days'][$date]['tasks'][] = [
                'start'       => substr((string) $r['start_time'], 0, 5),
                'end'         => substr((string) $r['end_time'], 0, 5),
                'description' => (string) $r['task_performed'],
                'hours'       => $hours,
                'break_excluded_minutes' => (int) round($breakSeconds / 60),
            ];
            unset($emp);
        }

        // Shape for JSON and add totals / shares.
        $out = [];
        $loggedShown = 0.0;
        $taskCount = 0;
        $employeeSet = [];
        foreach ($clients as $c) {
            $empList = [];
            $cHours = 0.0; $cTasks = 0;
            foreach ($c['employees'] as $e) {
                $days = array_values($e['days']);
                $tasks = array_sum(array_map(static fn ($d) => count($d['tasks']), $days));
                $empList[] = ['user_id' => $e['user_id'], 'name' => $e['name'], 'hours' => $e['hours'], 'tasks' => $tasks, 'days' => $days];
                $cHours += $e['hours']; $cTasks += $tasks;
                $employeeSet[$e['user_id']] = true;
            }
            usort($empList, static fn ($a, $b) => $b['hours'] <=> $a['hours']);
            $out[] = ['client_id' => $c['client_id'], 'name' => $c['name'], 'hours' => round($cHours, 2), 'tasks' => $cTasks, 'employees' => $empList];
            $loggedShown += $cHours;
            $taskCount += $cTasks;
        }
        usort($out, static fn ($a, $b) => $b['hours'] <=> $a['hours']);
        foreach ($out as &$o) {
            $o['share'] = $loggedShown > 0 ? round($o['hours'] / $loggedShown * 100, 1) : 0.0;
        }
        unset($o);

        $worked = (float) ($db->table('attendance')
            ->selectSum('total_hours', 'h')
            ->whereIn('user_id', $userIds)
            ->where('time_out IS NOT NULL', null, false)
            ->where('COALESCE(shift_date, DATE(time_in)) >=', $start)
            ->where('COALESCE(shift_date, DATE(time_in)) <=', $end)
            ->get()->getRow()->h ?? 0);

        usort($options, static fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return [
            'range'   => ['start' => $start, 'end' => $end],
            'totals'  => [
                'logged_hours'   => round($loggedShown, 2),
                'worked_hours'   => round($worked, 2),
                // Across every client (not just the filtered one): time clocked in but never logged to any.
                'unlogged_hours' => round(max(0, $worked - $loggedAll), 2),
                'tasks'          => $taskCount,
                'employees'      => count($employeeSet),
                'clients'        => count($out),
            ],
            'clients'        => $out,
            'client_options' => array_values($options),
        ];
    }
    /**
     * Every break actually taken on an attendance, as [start, end] datetime strings: the logged breaks
     * plus one still running (which ends now, or at clock-out). Real times, not the fixed slot length
     * that payroll deducts.
     *
     * @return list<array{0: string, 1: string}>
     */
    public function breakIntervals(array $attendance): array
    {
        $out = [];
        foreach ((json_decode((string) ($attendance['break_log'] ?? ''), true) ?: []) as $b) {
            if (! empty($b['start']) && ! empty($b['end'])) $out[] = [(string) $b['start'], (string) $b['end']];
        }
        if (! empty($attendance['break_start'])) {
            $out[] = [(string) $attendance['break_start'], (string) ($attendance['time_out'] ?: date('Y-m-d H:i:s'))];
        }
        return $out;
    }

    /**
     * A friendly warning when a task's time runs over a break the employee took, or null. Warn-only:
     * the task is still saved (break time isn't paid work, so the report also subtracts it).
     */
    private function breakOverlapWarning(array $attendance, string $start, string $end): ?string
    {
        $breaks = $this->breakIntervals($attendance);
        if (! $breaks) return null;

        [, , $anchor] = $this->taskWindowContext($attendance);
        [$taskStart, $taskEnd] = $anchor($start, $end);

        $hits = [];
        foreach ($breaks as [$bs, $be]) {
            $bStart = new DateTime($bs);
            $bEnd   = new DateTime($be);
            if ($taskStart < $bEnd && $taskEnd > $bStart) {
                $hits[] = $bStart->format('h:i A') . ' – ' . $bEnd->format('h:i A');
            }
        }
        if (! $hits) return null;

        return 'Saved, but this overlaps your break (' . implode(', ', $hits) . '). Break time is not counted as client work.';
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

        [$winStart, $winEnd, $anchor] = $this->taskWindowContext($attendance);

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
            'unapproved_undertime_hours' => 0,
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

        // Batch-fetch approved requests for the whole range. OT/UT requests drive
        // approval calculations, while every request contributes a dated remark.
        $otRequestsByDate = [];   // approved OT requests per date (rows, so each request's time span is available)
        $approvedUtDates = [];
        $requestRemarksByDate = [];
        $leaveRemarksByDate = [];
        $approvalRequests = $this->requestModel
            ->where('requested_by', $userId)
            ->where('status', 'approved')
            ->where('start_date <=', $endDate)
            ->groupStart()
                ->where('end_date >=', $startDate)
                ->orWhere('end_date', null)
            ->groupEnd()
            ->orderBy('start_date', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
        foreach ($approvalRequests as $req) {
            $requestStart = strtotime((string) ($req['start_date'] ?? ''));
            $requestEnd = strtotime((string) (($req['end_date'] ?? '') ?: ($req['start_date'] ?? '')));
            if ($requestStart === false || $requestEnd === false) continue;

            $rangeStart = max($requestStart, strtotime($startDate));
            $rangeEnd   = min($requestEnd, strtotime($endDate));
            if ($rangeStart > $rangeEnd) continue;

            $remark = $this->formatApprovedRequestRemark($req);
            for ($t = $rangeStart; $t <= $rangeEnd; $t += 86400) {
                $d = date('Y-m-d', $t);
                if ($req['request_type'] === 'overtime') {
                    $otRequestsByDate[$d][] = $req;
                } elseif ($req['request_type'] === 'undertime') {
                    $approvedUtDates[$d] = true;
                }

                $requestRemarksByDate[$d][] = $remark;
                if ($req['request_type'] === 'leave') {
                    $leaveRemarksByDate[$d][] = $remark;
                }
            }
        }

        $attendanceDates = [];
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
            $unapprovedUndertimeHours = 0;
            $tardyMinutes      = 0;
            $nightDiff         = 0;
            $hasSchedule       = (bool) ($scheduledStart && $scheduledEnd);
            $otApproval        = AttendanceComputationService::otApprovalForDay($otRequestsByDate[$date] ?? [], $date);
            // Same split as OT above (approved-request lookup, not the
            // attendance row's own attendance_status), so "Undertime" here
            // means the same thing "OT Hrs" does: only the approved portion,
            // with unapproved shown/totaled separately — previously this
            // lookup was built ($approvedUtDates) but never actually used,
            // so every undertime minute was shown as one blended number
            // regardless of approval, unlike OT which always split.
            $hasApprovedUt     = isset($approvedUtDates[$date]);

            // Tardiness only depends on the clock-in time, not the clock-out —
            // computed here, unconditionally, so a still-open shift (clocked in
            // late, not yet clocked out) still shows it instead of silently
            // showing nothing until the employee eventually clocks out.
            if ($timeIn) {
                $tardyMinutes = AttendanceComputationService::lateMinutes(new DateTime($att['time_in']), $windowStart, (int) (($recordSchedule ?? [])['tardiness_grace_minutes'] ?? 0));
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
                    $date,
                    (int) (($recordSchedule ?? [])['ot_grace_minutes'] ?? 0)
                );
                $start = $split['span_start'];
                $end   = $split['span_end'];
                $regularHours = $split['regular_hours'];
                // Arriving late but within the tardiness grace is not a shortfall: credit those minutes so the day reads as complete.
                if ($hasSchedule && $requiredHours > 0) {
                    $forgiven    = AttendanceComputationService::graceForgivenHours(new DateTime($att['time_in']), $windowStart, (int) (($recordSchedule ?? [])['tardiness_grace_minutes'] ?? 0));
                    $regularHours = round($regularHours + min($forgiven, max(0, $requiredHours - $regularHours)), 2);
                }
                $excessHours  = $split['excess_hours'];
                // A request with a time span only approves the OT worked inside that span; a span-less request approves the whole day.
                $otSplit           = AttendanceComputationService::applyOtApproval($split, $otApproval);
                $otHours           = $otSplit['approved'];
                $unapprovedOtHours = $otSplit['unapproved'];

                // Undertime = shortfall against the scheduled window itself — time
                // during the shift the employee simply wasn't there (late arrival,
                // early leave, etc.), independent of any OT worked outside it.
                if ($hasSchedule) {
                    $shortfall = max(0, round($requiredHours - $regularHours, 2));
                    if ($hasApprovedUt) {
                        $undertimeHours = $shortfall;
                    } else {
                        $unapprovedUndertimeHours = $shortfall;
                    }
                }


                // Night Differential — only counted for the portion of the shift
                // that's actually paid (the scheduled window, plus any OT hours
                // that were approved). Unapproved excess worked at night isn't
                // recognized as OT, so it isn't paid a night differential either.
                $nightDiff = 0;
                foreach ($otSplit['paid'] as [$paidStart, $paidEnd]) {
                    $nightDiff += $this->computeNightDiffRange($paidStart, $paidEnd, $date, $nightStart, $nightEnd);
                }
            }

            // RDOT (Rest Day OT – example: Sunday)
            $isRestDay = (date('N', strtotime($date)) == 7);
            $rdotHours = 0;
            if ($isRestDay && $timeIn && $timeOut) {
                $rdotHours = $regularHours;
            }

            $attendanceDates[$date] = true;
            $remarks = $this->getRemarksForAttendance(
                $att,
                $requestRemarksByDate[$date] ?? [],
                $unapprovedOtHours,
                $unapprovedUndertimeHours
            );

            // WFH label — from this row's own resolved schedule (snapshot-first,
            // same as scheduledStart/scheduledEnd above), so a later change to
            // the employee's WFH days doesn't retroactively relabel a past day.
            $wfhDays = json_decode((string) ($recordSchedule['wfh_days'] ?? ''), true);
            $isWfhDay = is_array($wfhDays) && in_array(strtolower(date('D', strtotime($date))), $wfhDays, true);
            if ($isWfhDay) {
                $remarks = trim('(WFH) ' . $remarks);
            }

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
                'unapproved_undertime_hours' => $unapprovedUndertimeHours,
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
            $totals['unapproved_undertime_hours'] += $unapprovedUndertimeHours;
        }

        // Approved leave normally has no attendance row. Keep it visible as a
        // read-only zero-hour entry instead of silently dropping the day.
        foreach ($leaveRemarksByDate as $date => $leaveRemarks) {
            if (isset($attendanceDates[$date])) continue;

            $dailyRecords[] = [
                'attendance_id'       => null,
                'time_in_datetime'    => null,
                'time_out_datetime'   => null,
                'break_hours'         => 0.0,
                'out_date'            => null,
                'state_version'       => 0,
                'date'                => $date,
                'in'                  => '-',
                'out'                 => '-',
                'regular_hours'       => 0.0,
                'ot_hours'            => 0.0,
                'rdot_hours'          => 0.0,
                'tardy_minutes'       => 0,
                'night_diff'          => 0.0,
                'unapproved_ot_hours' => 0.0,
                'undertime_hours'     => 0.0,
                'unapproved_undertime_hours' => 0.0,
                'remarks'             => implode('; ', array_values(array_unique($leaveRemarks))),
                'task_performed'      => '',
                'client'              => '',
                'tasks'               => [],
                'is_leave_day'        => true,
            ];
        }

        usort($dailyRecords, static function (array $a, array $b): int {
            $dateCompare = strcmp((string) $a['date'], (string) $b['date']);
            if ($dateCompare !== 0) return $dateCompare;
            return strcmp((string) ($a['time_in_datetime'] ?? ''), (string) ($b['time_in_datetime'] ?? ''));
        });

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

    private function formatApprovedRequestRemark(array $request): string
    {
        $type = (string) ($request['request_type'] ?? 'request');
        if ($type === 'leave') {
            $category = (string) ($request['leave_category'] ?? '');
            $label = $category !== '' && $category !== 'leave'
                ? ucfirst($category) . ' Leave'
                : 'Leave';
        } else {
            $label = 'Approved ' . ucfirst($type);
            if ($type === 'overtime' && !empty($request['ot_start_time']) && !empty($request['ot_end_time'])) {
                $label .= ' ' . date('g:i A', strtotime($request['ot_start_time'])) . ' - ' . date('g:i A', strtotime($request['ot_end_time']));
            }
        }

        $reason = trim((string) ($request['reason'] ?? ''));
        return $reason === '' ? $label : $label . ' (' . $reason . ')';
    }

    private function getRemarksForAttendance(
        array $attendance,
        array $approvedRequestRemarks,
        float $unapprovedOtHours,
        float $unapprovedUndertimeHours
    ): string {
        $remarks = $approvedRequestRemarks;

        if ($unapprovedOtHours > 0) {
            $remarks[] = 'Unapproved Overtime';
        }

        if ($unapprovedUndertimeHours > 0) {
            $reason = trim((string) ($attendance['unapproved_ut_reason'] ?? ''));
            $remarks[] = $reason === ''
                ? 'Unapproved Undertime'
                : 'Unapproved Undertime (' . $reason . ')';
        }

        $missedClockoutReason = trim((string) ($attendance['missed_clockout_reason'] ?? ''));
        if ($missedClockoutReason !== '') {
            $remarks[] = 'Missed Clock-out (' . $missedClockoutReason . ')';
        }

        return implode('; ', array_values(array_unique(array_filter($remarks))));
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
