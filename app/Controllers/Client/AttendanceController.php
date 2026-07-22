<?php

namespace App\Controllers\Client;

use App\Models\Client\AttendanceModel;
use App\Controllers\BaseController;
use App\Services\Client\AttendanceComputationService;

class AttendanceController extends BaseController
{
    protected $attendanceModel;
    protected $attendanceComputationService;
    protected $db;

    public function __construct()
    {
        $this->attendanceModel = new AttendanceModel();
        $this->attendanceComputationService = new AttendanceComputationService();
        $this->db              = db_connect();
    }

    public function timeIn()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->to('/login');
        }

        $userId = $user->id;

        /*
        |--------------------------------------------------------------------------
        | Check if employee is currently timed in
        |--------------------------------------------------------------------------
        */

        $current = $this->attendanceModel
            ->where('user_id', $userId)
            ->where('time_out', null)
            ->first();

        if ($current) {
            return redirect()
                ->back()
                ->with('error', 'Already timed in!');
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate attendance for today
        |--------------------------------------------------------------------------
        */

        $todayAttendance = $this->attendanceModel
            ->where('user_id', $userId)
            ->where('time_in >=', date('Y-m-d 00:00:00'))
            ->where('time_in <=', date('Y-m-d 23:59:59'))
            ->first();

        if ($todayAttendance) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'You have already completed your attendance for today.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Prepare Time In
        |--------------------------------------------------------------------------
        */

        $timeIn = date('Y-m-d H:i:s');

        $schedule =
            $this->attendanceComputationService
                ->getEmployeeSchedule($userId);

        $lateMinutes = 0;

        if (
            $schedule &&
            ! empty($schedule['start_time'])
        ) {
            $scheduledStart =
                date('Y-m-d')
                . ' '
                . $schedule['start_time'];

            if (
                strtotime($timeIn)
                >
                strtotime($scheduledStart)
            ) {
                $lateMinutes = floor(
                    (
                        strtotime($timeIn)
                        -
                        strtotime($scheduledStart)
                    ) / 60
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Create Attendance Record
        |--------------------------------------------------------------------------
        */

        $this->attendanceModel->insert([
            'user_id'          => $userId,
            'time_in'          => $timeIn,
            'late_minutes'     => $lateMinutes,
            'scheduled_start'  => $schedule['start_time'] ?? null,
            'scheduled_end'    => $schedule['end_time'] ?? null,
            'attendance_status'=> 'present',
        ]);

        service('activityLogger')->attendanceClockedIn(
            $this->attendanceModel->getInsertID(),
            [
                'time_in' => $timeIn,
            ]
        );

        return redirect()
            ->back()
            ->with(
                'success',
                'Time in recorded!'
            );
    }

    public function timeOut($id)
    {
        $user   = auth()->user();
        $userId = $user->id;

        $reason = trim(
            $this->request->getPost(
                'unapproved_ut_reason'
            ) ?? ''
        );

        $missedClockoutReason = trim(
            $this->request->getPost(
                'missed_clockout_reason'
            ) ?? ''
        );

        $attendance = $this->attendanceModel
            ->where('id', $id)
            ->where('user_id', $userId)
            ->where('time_out', null)
            ->first();

        if (! $attendance) {
            return redirect()->back()->with('error', 'No active time in found!');
        }

        // --- Resolve end_time cap from employee_positions ---
        $timeOut = date('Y-m-d H:i:s');

        $analysis =
            $this->attendanceComputationService
                ->analyzeClockOut(
                    $userId,
                    $timeOut
                );

        $ot = $analysis['overtime'];
        $ut = $analysis['undertime'];

        $undertimeReason = null;

        if (
            $ut['unapproved_ut'] > 0
        )
        {
            $undertimeReason = $reason;
        }

        // Clocking out on a later calendar day than the shift's date means this is
        // an overdue/missed clock-out (e.g. forgot to clock out yesterday), not an
        // early clock-out — keep its reason separate from unapproved_ut_reason.
        $isMissedClockout = date('Y-m-d', strtotime($attendance['time_in'])) !== date('Y-m-d', strtotime($timeOut));
        $missedClockoutReasonToSave = $isMissedClockout ? $missedClockoutReason : null;

        $totalHours =
        (strtotime($timeOut) - strtotime($attendance['time_in'])) / 3600;
        $totalHours -= (float) ($attendance['break_duration'] ?? 0);

        $totalHours =
        max(0, $totalHours);

        $breakDuration = (float) ($attendance['break_duration'] ?? 0);

        $nightDiffHours = $this->computeNightDiffHours(
            $attendance['time_in'],
            $timeOut,
            $breakDuration
        );

        $before = [
            'time_out'    => $attendance['time_out'] ?? null,
            'total_hours' => $attendance['total_hours'] ?? null,
        ];

        $after = [
            'time_out'    => $timeOut,
            'total_hours' => round($totalHours, 2),
        ];

        $status = 'present';

        if ($ut['approved_ut'] > 0)
        {
            $status = 'approved_undertime';
        }
        elseif ($ut['unapproved_ut'] > 0)
        {
            $status = 'unapproved_undertime';
        }
        elseif ($ot['approved_ot'] > 0)
        {
            $status = 'approved_overtime';
        }
        elseif ($ot['unapproved_ot'] > 0)
        {
            $status = 'unapproved_overtime';
        }

        $this->attendanceModel->update(
            $attendance['id'],
            [
                'time_out' => $timeOut,
                'total_hours' => round($totalHours, 2),
                'break_duration' => $breakDuration,
                'night_diff_hours' => round($nightDiffHours, 2),

                'approved_ot_hours' =>
                    $ot['approved_ot'],

                'unapproved_ot_hours' =>
                    $ot['unapproved_ot'],

                'approved_ut_hours' =>
                    $ut['approved_ut'],

                'unapproved_ut_hours' =>
                    $ut['unapproved_ut'],

                'unapproved_ut_reason' =>
                    $undertimeReason,

                'missed_clockout_reason' =>
                    $missedClockoutReasonToSave,

                'attendance_status' => $status,
            ]
        );

        service('activityLogger')->attendanceClockedOut($attendance['id'], $before, $after);

        $response = redirect()->back()->with('success', 'Time out recorded!');

        // Prompt the employee to file a late overtime request right away — the
        // extra hours from a missed clock-out aren't paid unless one gets
        // approved, and there's nothing else on the dashboard that would tell
        // a first-time user this step exists.
        if ($isMissedClockout) {
            $response = $response->with('prompt_ot_request', [
                'date'   => date('Y-m-d', strtotime($attendance['time_in'])),
                'reason' => $missedClockoutReasonToSave,
            ]);
        }

        return $response;
    }

    /**
     * Each employee's configured break list (see EmployeePositionModel::break_schedule,
     * a JSON array of per-break minutes, e.g. [15,60,15] for a 15-min break, an hour
     * lunch, then another 15-min break). Falls back to a single 60-minute break for
     * anyone who hasn't been configured yet, matching the previous flat-1-hour rule.
     */
    private function getBreakScheduleMinutes(int $userId): array
    {
        $schedule = $this->getEmployeeSchedule($userId);
        $raw      = $schedule['break_schedule'] ?? null;
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && count($decoded) > 0) {
                return array_values(array_map('intval', $decoded));
            }
        }
        return [60];
    }

    public function toggleBreak($id)
    {
        $user   = auth()->user();
        $userId = $user->id;

        $attendance = $this->attendanceModel
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $attendance || $attendance['time_out'] !== null) {
            return redirect()->back()->with('error', 'No active attendance found!');
        }

        $breakSchedule = $this->getBreakScheduleMinutes($userId);
        $breaksTaken   = (int) ($attendance['breaks_taken'] ?? 0);
        $totalBreaks   = count($breakSchedule);

        if (empty($attendance['break_start'])) {
            // Starting a break
            if ($breaksTaken >= $totalBreaks) {
                return redirect()->back()->with('error', "You've used all your breaks for today ({$breaksTaken}/{$totalBreaks}).");
            }

            $breakStartTime = date('Y-m-d H:i:s');

            $this->attendanceModel->update($id, [
                'break_start' => $breakStartTime,
            ]);

            service('activityLogger')->attendanceBreakRecorded($id,
                ['break_start' => null, 'break_end' => $attendance['break_end'], 'break_duration' => $attendance['break_duration']],
                ['break_start' => $breakStartTime, 'break_end' => $attendance['break_end'], 'break_duration' => $attendance['break_duration']]
            );

            return redirect()->back()->with('success', 'Break started!');
        } else {
            // Ending a break — credit the preset duration configured for this break
            // slot, not the actual elapsed time, so it always matches what was set
            // up in Employee Management regardless of how long the employee is away.
            $presetMinutes = $breakSchedule[$breaksTaken] ?? end($breakSchedule);
            $newBreaksTaken = $breaksTaken + 1;
            $totalBreak     = (float) ($attendance['break_duration'] ?? 0) + ($presetMinutes / 60);

            $breakEndTime = date('Y-m-d H:i:s');

            $this->attendanceModel->update($id, [
                'break_start'    => null,
                'break_end'      => $breakEndTime,
                'break_duration' => $totalBreak,
                'breaks_taken'   => $newBreaksTaken,
            ]);

            service('activityLogger')->attendanceBreakRecorded($id,
                ['break_start' => $attendance['break_start'], 'break_end' => null, 'break_duration' => $attendance['break_duration']],
                ['break_start' => null, 'break_end' => $breakEndTime, 'break_duration' => round($totalBreak, 2)]
            );

            return redirect()->back()
                ->with('success', "Break ended! Credited {$presetMinutes} min ({$newBreaksTaken}/{$totalBreaks} breaks used).")
                ->with('break_hours', round($totalBreak, 2));
        }
    }

    private function getEmployeeSchedule(int $userId): ?array
    {
        $employee = $this->db->table('employees')
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();

        if (!$employee) {
            return null;
        }

        return $this->db->table('employee_positions')
            ->where('employee_id', $employee['id'])
            ->get()
            ->getRowArray();
    }

    private function computeNightDiffHours($timeIn, $timeOut, $breakDuration)
    {
        $start = new \DateTime($timeIn);
        $end   = new \DateTime($timeOut);

        $date = $start->format('Y-m-d');

        $ndStart = new \DateTime($date . ' 22:00:00');
        $ndEnd   = new \DateTime($date . ' 06:00:00');
        $ndEnd->modify('+1 day');

        $overlapStart = max($start, $ndStart);
        $overlapEnd   = min($end, $ndEnd);
        $nightMinutes = 0;

        if ($overlapStart < $overlapEnd) {
            $diff         = $overlapStart->diff($overlapEnd);
            $nightMinutes = $diff->h * 60 + $diff->i;
        }

        return $nightMinutes / 60;
    }
}