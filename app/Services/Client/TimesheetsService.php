<?php

namespace App\Services\Client;

use App\Models\Client\AttendanceModel;
use App\Models\Client\TasksModel;
use App\Models\Client\RequestModel;
use DateTime;

class TimesheetsService
{
    protected TasksModel $tasksModel;
    protected AttendanceModel $attendanceModel;
    protected RequestModel $requestModel;

    public function __construct()
    {
        $this->tasksModel      = new TasksModel();
        $this->attendanceModel = new AttendanceModel();
        $this->requestModel    = new RequestModel();
    }

    // ---------- Existing methods ----------
    public function listByUserId(int $userId): array
    {
        return $this->tasksModel
            ->select('attendance.date, tasks.start_time, tasks.end_time, employee_positions.department, tasks.task_performed, tasks.id, tasks.user_id, tasks.attendance_id')
            ->join('attendance', 'attendance.id = tasks.attendance_id')
            ->join('employees', 'employees.user_id = tasks.user_id')
            ->join('employee_positions', 'employee_positions.employee_id = employees.id')
            ->where('tasks.user_id', $userId)
            ->orderBy('attendance.date', 'DESC')
            ->orderBy('tasks.start_time', 'ASC')
            ->findAll();
    }

    public function listDailyTimeLogByUserId(int $userId): array
    {
        return $this->attendanceModel
            ->select('id, user_id, date, time_in, break_start, break_end, break_duration, time_out, total_hours')
            ->where('user_id', $userId)
            ->orderBy('date', 'DESC')
            ->findAll();
    }

    public function listCurrentCutoffByUserId(int $userId): array
    {
        $now = date('Y-m-d');
        $period = $this->resolveCutoffByDate($now);

        return $this->baseListByUserIdBuilder($userId)
            ->where('attendance.date >=', $period['start'])
            ->where('attendance.date <=', $period['end'])
            ->orderBy('attendance.date', 'DESC')
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

    public function updateByUserId(int $taskId, int $userId, array $payload): bool
    {
        $task = $this->tasksModel->where('id', $taskId)->where('user_id', $userId)->first();
        if (!$task) return false;

        $taskUpdateData = [];
        if (isset($payload['start_time'])) $taskUpdateData['start_time'] = $payload['start_time'];
        if (isset($payload['end_time']))   $taskUpdateData['end_time']   = $payload['end_time'];
        if (isset($payload['task_performed'])) $taskUpdateData['task_performed'] = $payload['task_performed'];

        $ok = true;
        if (!empty($taskUpdateData)) {
            $old = [
                'start_time'     => $task['start_time'],
                'end_time'       => $task['end_time'],
                'task_performed' => $task['task_performed'],
            ];
            $ok = $this->tasksModel->update($taskId, $taskUpdateData);
            if ($ok) service('activityLogger')->taskUpdated($taskId, $old, $taskUpdateData);
        }

        if (isset($payload['date']) && $payload['date'] !== '') {
            $ok = $ok && $this->attendanceModel
                ->where('id', (int) $task['attendance_id'])
                ->where('user_id', $userId)
                ->set('date', $payload['date'])
                ->update();
        }
        return $ok;
    }

    public function deleteByUserId(int $taskId, int $userId): bool
    {
        $task = $this->tasksModel->where('id', $taskId)->where('user_id', $userId)->first();
        if (!$task) return false;

        $deleted = $this->tasksModel->delete($taskId);
        if ($deleted) service('activityLogger')->taskDeleted($taskId, $task);
        return $deleted;
    }

    // ---------- NEW: Detailed daily timesheet ----------
    public function getDetailedTimesheet(int $userId, string $startDate, string $endDate): array
    {
        $attendances = $this->attendanceModel
            ->where('user_id', $userId)
            ->where('date >=', $startDate)
            ->where('date <=', $endDate)
            ->orderBy('date', 'ASC')
            ->findAll();

        $dailyRecords = [];
        $totals = [
            'regular_hours' => 0,
            'ot_hours'      => 0,
            'rdot_hours'    => 0,
            'tardy_minutes' => 0,
            'night_diff'    => 0,
        ];

        // Company schedule – you may move these to settings
        $scheduledStart = '08:00:00';
        $scheduledEnd   = '17:00:00';
        $nightStart     = '22:00:00';
        $nightEnd       = '06:00:00';

        foreach ($attendances as $att) {
            $date     = $att['date'];
            $timeIn   = $att['time_in'] ?? null;
            $timeOut  = $att['time_out'] ?? null;

            // Regular Hours (worked minus breaks)
            $regularHours = 0;
            if ($timeIn && $timeOut) {
                $start = new DateTime($date . ' ' . $timeIn);
                $end   = new DateTime($date . ' ' . $timeOut);
                if ($end < $start) $end->modify('+1 day');
                $diff = $end->diff($start);
                $workedMinutes = $diff->h * 60 + $diff->i;
                $breakMinutes = (float)($att['break_duration'] ?? 0) * 60;
                $regularMinutes = max(0, $workedMinutes - $breakMinutes);
                $regularHours = round($regularMinutes / 60, 2);
            }

            // OT (after scheduled end)
            $otHours = 0;
            if ($timeOut && $timeOut > $scheduledEnd) {
                $endObj = new DateTime($date . ' ' . $timeOut);
                $scheduledEndObj = new DateTime($date . ' ' . $scheduledEnd);
                $otDiff = $endObj->diff($scheduledEndObj);
                $otHours = round($otDiff->h + $otDiff->i / 60, 2);
            }

            // RDOT (Rest Day OT – example: Sunday)
            $isRestDay = (date('N', strtotime($date)) == 7);
            $rdotHours = 0;
            if ($isRestDay && $timeIn && $timeOut) {
                $rdotHours = $regularHours;
            }

            // Tardy minutes
            $tardyMinutes = 0;
            if ($timeIn && $timeIn > $scheduledStart) {
                $inObj = new DateTime($date . ' ' . $timeIn);
                $startObj = new DateTime($date . ' ' . $scheduledStart);
                $diff = $inObj->diff($startObj);
                $tardyMinutes = $diff->h * 60 + $diff->i;
            }

            // Night Differential
            $nightDiff = (float)($att['night_diff_hours'] ?? 0);
            if ($nightDiff == 0 && $timeIn && $timeOut) {
                $nightDiff = $this->computeNightDiff($date, $timeIn, $timeOut, $nightStart, $nightEnd);
            }

            // Remarks (leave or task)
            $remarks = $this->getRemarksForDay($userId, $date);

            $dailyRecords[] = [
                'attendance_id'   => $att['id'],
                'date'            => $date,
                'in'              => $timeIn ? date('h:i A', strtotime($timeIn)) : '-',
                'out'             => $timeOut ? date('h:i A', strtotime($timeOut)) : '-',
                'regular_hours'   => $regularHours,
                'ot_hours'        => $otHours,
                'rdot_hours'      => $rdotHours,
                'tardy_minutes'   => $tardyMinutes,
                'night_diff'      => $nightDiff,
                'remarks'         => $remarks,
            ];

            $totals['regular_hours'] += $regularHours;
            $totals['ot_hours']      += $otHours;
            $totals['rdot_hours']    += $rdotHours;
            $totals['tardy_minutes'] += $tardyMinutes;
            $totals['night_diff']    += $nightDiff;
        }

        return [
            'daily'  => $dailyRecords,
            'totals' => $totals,
        ];
    }

    private function computeNightDiff(string $date, string $timeIn, string $timeOut, string $nightStart, string $nightEnd): float
    {
        $startTs = strtotime("$date $timeIn");
        $endTs   = strtotime("$date $timeOut");
        if ($endTs < $startTs) $endTs += 86400;

        $nightStartTs = strtotime("$date $nightStart");
        $nightEndTs   = strtotime("$date $nightEnd");
        if ($nightEndTs < $nightStartTs) $nightEndTs += 86400;

        $nightMinutes = 0;
        for ($t = $startTs; $t < $endTs; $t++) {
            if ($t >= $nightStartTs && $t < $nightEndTs) $nightMinutes++;
        }
        return round($nightMinutes / 60, 2);
    }

    private function getRemarksForDay(int $userId, string $date): string
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
        if ($request) {
            return ucfirst($request['request_type']) . ' (' . $request['reason'] . ')';
        }

        // Auto-mark undertime when user clocked out before scheduled end
        $scheduledEnd = '17:00:00';
        $attendance = $this->attendanceModel
            ->where('user_id', $userId)
            ->where('date', $date)
            ->first();
        if ($attendance && !empty($attendance['time_out']) && $attendance['time_out'] < $scheduledEnd) {
            return 'Undertime';
        }

        // First task of the day
        $task = $this->tasksModel
            ->select('task_performed')
            ->join('attendance', 'attendance.id = tasks.attendance_id')
            ->where('tasks.user_id', $userId)
            ->where('attendance.date', $date)
            ->first();
        return $task ? $task['task_performed'] : '';
    }

    // ---------- Private helpers ----------
    private function baseListByUserIdBuilder(int $userId): TasksModel
    {
        return $this->tasksModel
            ->select('attendance.date, tasks.start_time, tasks.end_time, employee_positions.department, tasks.task_performed, tasks.id, tasks.user_id, tasks.attendance_id')
            ->join('attendance', 'attendance.id = tasks.attendance_id')
            ->join('employees', 'employees.user_id = tasks.user_id')
            ->join('employee_positions', 'employee_positions.employee_id = employees.id')
            ->where('tasks.user_id', $userId);
    }

    private function resolveCutoffByDate(string $date): array
    {
        $base = new DateTime($date);
        $day = (int) $base->format('d');
        if ($day <= 15) {
            return ['start' => $base->format('Y-m-01'), 'end' => $base->format('Y-m-15')];
        }
        return ['start' => $base->format('Y-m-16'), 'end' => $base->format('Y-m-t')];
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