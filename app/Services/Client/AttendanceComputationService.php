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
     *
     * $graceMinutes (per-employee, from Work Schedule) is a gate, not a
     * discount: arriving within it counts as exactly 0 minutes late, but
     * crossing it does NOT subtract the grace from the total — the full
     * lateness from the scheduled start still counts. Defaults to 0 (no
     * grace, today's exact-minute behavior) for any caller that doesn't
     * pass one.
     *
     * Uses floor(), not round() — seconds are never held against the
     * employee. 7:30:59 against a 7:30:00 start is 0 minutes late, not
     * "rounded up" to 1 just for landing past the 30-second mark. Only
     * whole elapsed minutes ever count. This is purely a reported/HR
     * figure (confirmed not used in any pay/deduction calculation), so it
     * can't affect payroll math.
     */
    public static function lateMinutes(\DateTimeInterface $actualStart, ?\DateTimeInterface $windowStart, int $graceMinutes = 0): int
    {
        if (!$windowStart || $actualStart <= $windowStart) return 0;
        $minutes = (int) floor(($actualStart->getTimestamp() - $windowStart->getTimestamp()) / 60);
        return $minutes <= $graceMinutes ? 0 : $minutes;
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
     *
     * $otGraceMinutes (per-employee, from Work Schedule) is applied
     * independently to EACH edge — up to that many minutes early (before
     * scheduled start) or late (after scheduled end) isn't counted as
     * excess, but it's a gate per edge, not a shared pool: 5 minutes early
     * AND 5 minutes late in the same shift are each separately forgiven,
     * not netted against one 5-minute budget. Crossing an edge's grace
     * counts that edge's full minutes, same non-discount rule as
     * lateMinutes(). Defaults to 0 (no grace) for any caller that doesn't
     * pass one.
     */
    public function splitShiftWindow(string $timeIn, string $timeOut, float $breakHours, ?string $scheduledStart, ?string $scheduledEnd, string $windowDate, int $otGraceMinutes = 0): array
    {
        $start = new \DateTime($timeIn);
        $end = new \DateTime($timeOut);
        if ($end < $start) $end->modify('+1 day');

        $hasSchedule = (bool) ($scheduledStart && $scheduledEnd);
        $windowStart = $windowEnd = null;
        $excessHours = 0.0;
        if ($hasSchedule) {
            $windowStart = new \DateTime($windowDate . ' ' . $scheduledStart);
            $windowEnd = new \DateTime($windowDate . ' ' . $scheduledEnd);
            if ($windowEnd < $windowStart) $windowEnd->modify('+1 day');
            // Clock-in seconds are floored to the minute here, matching
            // lateMinutes(): 7:30:30 is 0 minutes late, so it must not also
            // lose a minute of regular hours (which showed up as a phantom
            // 0.02 undertime on a shift that ran past the scheduled end).
            $startFloored = (clone $start)->setTime((int) $start->format('G'), (int) $start->format('i'), 0);
            $overlapStart = max($startFloored, $windowStart);
            $overlapEnd = min($end, $windowEnd);
            $windowMinutes = 0;
            if ($overlapStart < $overlapEnd) {
                $diff = $overlapStart->diff($overlapEnd);
                $windowMinutes = $diff->h * 60 + $diff->i + $diff->days * 1440;
            }

            // Each edge graced on its own — equivalent to the old combined
            // "total worked outside the window" figure when grace is 0.
            // Minutes counted the same way regularHours/windowMinutes above
            // already do (DateInterval decomposition, seconds truncated) —
            // NOT round() or floor() on a raw seconds/60 division, which
            // would change actual OT pay for sub-minute edge cases. This
            // keeps OT math byte-for-byte identical to before grace/floor
            // existed whenever otGraceMinutes is 0.
            $leadingMinutes = 0;
            if ($start < $windowStart) {
                $diff = $start->diff($windowStart);
                $leadingMinutes = $diff->h * 60 + $diff->i + $diff->days * 1440;
            }
            $trailingMinutes = 0;
            if ($end > $windowEnd) {
                $diff = $windowEnd->diff($end);
                $trailingMinutes = $diff->h * 60 + $diff->i + $diff->days * 1440;
            }
            $gracedLeading = $leadingMinutes > $otGraceMinutes ? $leadingMinutes : 0;
            $gracedTrailing = $trailingMinutes > $otGraceMinutes ? $trailingMinutes : 0;
            $excessHours = round(($gracedLeading + $gracedTrailing) / 60, 2);
        } else {
            // No schedule on file — fall back to the full worked span.
            $diff = $end->diff($start);
            $windowMinutes = $diff->h * 60 + $diff->i + $diff->days * 1440;
        }

        $breakMinutes = $breakHours * 60;
        $regularHours = round(max(0, $windowMinutes - $breakMinutes) / 60, 2);

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
            $date,
            (int) ($schedule['ot_grace_minutes'] ?? 0)
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
            $requiredHours = \App\Libraries\AttendanceWindow::requiredHours(($split['window_end']->getTimestamp() - $split['window_start']->getTimestamp()) / 3600, $schedule);
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