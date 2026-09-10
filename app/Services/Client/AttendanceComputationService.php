<?php

namespace App\Services\Client;

use App\Models\Client\AttendanceModel;

class AttendanceComputationService{

    protected $db;
    protected AttendanceModel $attendanceModel;

    public function __construct()
    {
        $this->db = db_connect();
        $this->attendanceModel = new AttendanceModel();
    }

    public function getEmployeeSchedule(
        int $userId
    ): ?array
    {
        $db = db_connect();

        $employee = $db->table('employees')
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();

        if (!$employee) {
            return null;
        }

        return $db->table('employee_positions')
            ->where('employee_id', $employee['id'])
            ->get()
            ->getRowArray();
    }

    public function getApprovedRequest(
        int $userId,
        string $date,
        string $type
    ): ?array
    {
        $db = db_connect();

       $query = $db->table('requests')
        ->where('requested_by', $userId)
        ->where('request_type', $type)
        ->where('status', 'approved')
        ->where('start_date <=', $date)
        ->groupStart()
            ->where('end_date >=', $date)
            ->orWhere('end_date', null)
        ->groupEnd();

        return $query->get()->getRowArray();
    }

    public function calculateLate(
        string $timeIn,
        string $scheduledStart
    ): int
    {
        $actual = strtotime($timeIn);
        $start = strtotime($scheduledStart);

        if ($actual <= $start) {
            return 0;
        }

        return floor(
            ($actual - $start) / 60
        );
    }

    public function calculateOvertime(
        int $userId,
        string $date,
        string $actualOut,
        string $scheduledEnd
    ): array
    {
        $result = [
            'approved_ot'   => 0,
            'unapproved_ot' => 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | No OT if employee did not exceed scheduled end
        |--------------------------------------------------------------------------
        */

        if (
            strtotime($actualOut)
            <=
            strtotime($scheduledEnd)
        ) {
            return $result;
        }

        /*
        |--------------------------------------------------------------------------
        | Get today's attendance
        |--------------------------------------------------------------------------
        */

        $attendance = $this->db
            ->table('attendance')
            ->where('user_id', $userId)
            ->where('DATE(time_in)', $date)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        if (! $attendance) {
            return $result;
        }

        /*
        |--------------------------------------------------------------------------
        | Compute total worked hours
        |--------------------------------------------------------------------------
        */

        $breakDuration =
            (float) ($attendance['break_duration'] ?? 0);

        $totalHoursWorked =
            (
                strtotime($actualOut)
                -
                strtotime($attendance['time_in'])
            )
            / 3600;

        $totalHoursWorked -= $breakDuration;

        $totalHoursWorked =
            max(0, $totalHoursWorked);

        /*
        |--------------------------------------------------------------------------
        | Compute required hours dynamically
        |--------------------------------------------------------------------------
        */

        $schedule =
            $this->getEmployeeSchedule(
                $userId
            );

        if (! $schedule) {
            return $result;
        }

        $scheduledStart =
            $date
            . ' '
            . $schedule['start_time'];

        $requiredHours =
            (
                strtotime($scheduledEnd)
                -
                strtotime($scheduledStart)
            )
            / 3600;

        /*
        |--------------------------------------------------------------------------
        | Employee must complete required hours first
        |--------------------------------------------------------------------------
        */

        if (
            $totalHoursWorked
            <
            $requiredHours
        ) {
            return $result;
        }

        /*
        |--------------------------------------------------------------------------
        | Compute OT hours
        |--------------------------------------------------------------------------
        */

        $hours =
            (
                strtotime($actualOut)
                -
                strtotime($scheduledEnd)
            )
            / 3600;

        $request =
            $this->getApprovedRequest(
                $userId,
                $date,
                'overtime'
            );

        if ($request) {
            $result['approved_ot'] =
                round($hours, 2);
        } else {
            $result['unapproved_ot'] =
                round($hours, 2);
        }

        return $result;
    }

    public function calculateUndertime(
        int $userId,
        string $date,
        string $actualOut,
        string $scheduledEnd
    ): array
    {
        $result = [
            'approved_ut' => 0,
            'unapproved_ut' => 0
        ];

        if (
            strtotime($actualOut)
            >=
            strtotime($scheduledEnd)
        ) {
            return $result;
        }

        $hours =
            (
                strtotime($scheduledEnd)
                -
                strtotime($actualOut)
            )
            /
            3600;

        $request =
            $this->getApprovedRequest(
                $userId,
                $date,
                'undertime'
            );

        if ($request) {
            $result['approved_ut'] = round($hours, 2);
        } else {
            $result['unapproved_ut'] = round($hours, 2);
        }

        return $result;
    }
    
    /**
     * Minutes late against a shift's scheduled start — shared so live clock-in,
     * import, Timesheet, and Payroll all report the same figure for the same
     * arrival. Previously clock-in/import floor()ed the difference while
     * Timesheet/Payroll round()ed it, so the same arrival could be reported as
     * (for example) 5 minutes late in one place and 6 in another.
     */
    public static function lateMinutes(\DateTimeInterface $actualStart, ?\DateTimeInterface $windowStart): int
    {
        if (!$windowStart || $actualStart <= $windowStart) return 0;
        return (int) round(($actualStart->getTimestamp() - $windowStart->getTimestamp()) / 60);
    }

    /**
     * Canonical shift math shared by live clock-out/import (via analyzeClockOut()
     * below), the Timesheet page, and Payroll aggregation — previously each of
     * those computed OT/UT independently: clock-out/import used a single net
     * "worked vs required" difference (a late arrival offset by a late departure
     * netted to exactly zero, hiding both), while Timesheet/Payroll computed
     * regular hours as only the portion of the shift actually inside the
     * scheduled window, with excess and shortfall tracked independently and
     * simultaneously. The three could — and did — disagree on the same shift.
     *
     * Regular = time worked INSIDE the scheduled window (clocking in/out early
     * or late doesn't shift the window). Excess = time worked outside it, on
     * either side. Both are computed independently, so a shift can show both
     * excess (late departure) and a shortfall (late arrival) at once.
     */
    public function splitShiftWindow(string $timeIn, string $timeOut, float $breakHours, ?string $scheduledStart, ?string $scheduledEnd, string $windowDate): array
    {
        $start = new \DateTime($timeIn);
        $end = new \DateTime($timeOut);
        if ($end < $start) $end->modify('+1 day');

        $hasSchedule = (bool) ($scheduledStart && $scheduledEnd);
        $windowStart = $windowEnd = null;
        if ($hasSchedule) {
            $windowStart = new \DateTime($windowDate . ' ' . $scheduledStart);
            $windowEnd = new \DateTime($windowDate . ' ' . $scheduledEnd);
            if ($windowEnd < $windowStart) $windowEnd->modify('+1 day');
            $overlapStart = max($start, $windowStart);
            $overlapEnd = min($end, $windowEnd);
            $windowMinutes = 0;
            if ($overlapStart < $overlapEnd) {
                $diff = $overlapStart->diff($overlapEnd);
                $windowMinutes = $diff->h * 60 + $diff->i + $diff->days * 1440;
            }
        } else {
            // No schedule on file — fall back to the full worked span.
            $diff = $end->diff($start);
            $windowMinutes = $diff->h * 60 + $diff->i + $diff->days * 1440;
        }

        $breakMinutes = $breakHours * 60;
        $regularHours = round(max(0, $windowMinutes - $breakMinutes) / 60, 2);

        $totalDiff = $end->diff($start);
        $totalWorkedMinutes = $totalDiff->h * 60 + $totalDiff->i + $totalDiff->days * 1440;
        $totalWorkedHours = max(0, ($totalWorkedMinutes - $breakMinutes) / 60);
        $excessHours = max(0, round($totalWorkedHours - $regularHours, 2));

        return [
            'span_start'    => $start,
            'span_end'      => $end,
            'window_start'  => $windowStart,
            'window_end'    => $windowEnd,
            'has_schedule'  => $hasSchedule,
            'regular_hours' => $regularHours,
            'excess_hours'  => $excessHours,
        ];
    }

    public function analyzeClockOut(int $userId, string $actualOut, ?array $attendance = null): array
    {
        $result = ['overtime' => ['approved_ot' => 0, 'unapproved_ot' => 0], 'undertime' => ['approved_ut' => 0, 'unapproved_ut' => 0], 'night_diff_hours' => 0.0];
        $attendance ??= $this->attendanceModel->where('user_id', $userId)->where('time_out', null)->orderBy('id', 'ASC')->first();
        if (!$attendance || (int) $attendance['user_id'] !== $userId) return $result;
        $schedule = \App\Libraries\AttendanceWindow::schedule($attendance, $this->getEmployeeSchedule($userId));
        $date = $attendance['shift_date'] ?? substr($attendance['time_in'], 0, 10);
        $split = $this->splitShiftWindow(
            $attendance['time_in'],
            $actualOut,
            (float) ($attendance['break_duration'] ?? 0),
            $schedule['start_time'] ?? null,
            $schedule['end_time'] ?? null,
            $date
        );

        // Checked once, shared by the OT figure below and by night differential —
        // like Timesheet/Payroll, the full worked span is only paid at night when
        // overtime is actually approved; otherwise night hours outside the
        // scheduled window aren't paid either, so they don't count here.
        $hasApprovedOt = (bool) $this->getApprovedRequest($userId, $date, 'overtime');

        if ($split['excess_hours'] > 0) {
            $result['overtime'][$hasApprovedOt ? 'approved_ot' : 'unapproved_ot'] = $split['excess_hours'];
        }

        if ($split['has_schedule']) {
            $requiredHours = ($split['window_end']->getTimestamp() - $split['window_start']->getTimestamp()) / 3600;
            $undertime = max(0, round($requiredHours - $split['regular_hours'], 2));
            if ($undertime > 0) {
                $approvedUt = $this->getApprovedRequest($userId, $date, 'undertime');
                $result['undertime'][$approvedUt ? 'approved_ut' : 'unapproved_ut'] = $undertime;
            }
        }

        $paidStart = ($hasApprovedOt || !$split['has_schedule']) ? $split['span_start'] : max($split['span_start'], $split['window_start']);
        $paidEnd   = ($hasApprovedOt || !$split['has_schedule']) ? $split['span_end']   : min($split['span_end'],   $split['window_end']);
        if ($paidStart < $paidEnd) {
            $result['night_diff_hours'] = \App\Libraries\AttendanceWindow::nightHours($paidStart->format('Y-m-d H:i:s'), $paidEnd->format('Y-m-d H:i:s'));
        }

        return $result;
    }
}