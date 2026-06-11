<?php

namespace App\Controllers\Client;

use App\Models\Client\AttendanceModel;
use App\Controllers\BaseController;

class AttendanceController extends BaseController
{
    protected $attendanceModel;
    protected $db;

    public function __construct()
    {
        $this->attendanceModel = new AttendanceModel();
        $this->db              = db_connect();
    }

    public function timeIn()
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->to('/login');
        }

        $userId = $user->id;

        $current = $this->attendanceModel
            ->where('user_id', $userId)
            ->where('time_out', null)
            ->first();

        if ($current) {
            return redirect()->back()->with('error', 'Already timed in!');
        }

        $timeIn = date('Y-m-d H:i:s');

        $this->attendanceModel->insert([
            'user_id' => $userId,
            'time_in' => $timeIn,
        ]);

        service('activityLogger')->attendanceClockedIn($this->attendanceModel->getInsertID(), [
            'time_in' => $timeIn,
        ]);

        return redirect()->back()->with('success', 'Time in recorded!');
    }

    public function timeOut($id)
    {
        $user   = auth()->user();
        $userId = $user->id;

        $attendance = $this->attendanceModel
            ->where('id', $id)
            ->where('user_id', $userId)
            ->where('time_out', null)
            ->first();

        if (! $attendance) {
            return redirect()->back()->with('error', 'No active time in found!');
        }

        $start         = strtotime($attendance['time_in']);
        $end           = time();
        $clockOutDate  = date('Y-m-d', $end);

        // --- Resolve end_time cap from employee_positions ---
        $cappedEnd = $this->resolveEndTimeCap($userId, $clockOutDate, $end);

        $timeOut       = date('Y-m-d H:i:s', $cappedEnd);
        $totalHours    = ($cappedEnd - $start) / 3600;
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

        $this->attendanceModel->update($attendance['id'], [
            'time_out'         => $timeOut,
            'total_hours'      => round($totalHours, 2),
            'break_duration'   => $breakDuration,
            'night_diff_hours' => round($nightDiffHours, 2),
        ]);

        service('activityLogger')->attendanceClockedOut($attendance['id'], $before, $after);

        return redirect()->back()->with('success', 'Time out recorded!');
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

        if (! empty($attendance['break_start']) && ! empty($attendance['break_end'])) {
            return redirect()->back()->with('error', "You've already used your 1 hour break!");
        }

        if (empty($attendance['break_start']) && empty($attendance['break_end'])) {
            if ($attendance['break_duration'] >= 1) {
                return redirect()->back()->with('error', "You've used up your 1 hour break!");
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
            $breakStart = strtotime($attendance['break_start']);
            $breakEnd   = time();
            $breakHours = ($breakEnd - $breakStart) / 3600;
            $totalBreak = min($attendance['break_duration'] + $breakHours, 1.0);

            $breakEndTime = date('Y-m-d H:i:s');

            $this->attendanceModel->update($id, [
                'break_end'      => $breakEndTime,
                'break_duration' => $totalBreak,
            ]);

            service('activityLogger')->attendanceBreakRecorded($id,
                ['break_start' => $attendance['break_start'], 'break_end' => null, 'break_duration' => $attendance['break_duration']],
                ['break_start' => null, 'break_end' => $breakEndTime, 'break_duration' => round($totalBreak, 2)]
            );

            return redirect()->back()
                ->with('success', 'Break ended!')
                ->with('break_hours', round($totalBreak, 2));
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Returns the effective clock-out timestamp, capped to the employee's
     * scheduled end_time unless an approved overtime request covers that date.
     */
    private function resolveEndTimeCap(int $userId, string $clockOutDate, int $actualEnd): int
    {
        $employee = $this->db->table('employees')
            ->where('user_id', $userId)
            ->get()->getRowArray();

        if (! $employee) {
            return $actualEnd;
        }

        $position = $this->db->table('employee_positions')
            ->where('employee_id', $employee['id'])
            ->get()->getRowArray();

        if (! $position || empty($position['end_time'])) {
            return $actualEnd;
        }

        $capTimestamp = strtotime($clockOutDate . ' ' . $position['end_time']);

        // No cap needed if they're clocking out before or at the scheduled end
        if ($actualEnd <= $capTimestamp) {
            return $actualEnd;
        }

        // Check for an approved overtime request covering this date
        $hasOvertime = $this->db->table('requests')
            ->where('requested_by', $userId)
            ->where('request_type', 'overtime')
            ->where('status', 'approved')
            ->where('start_date <=', $clockOutDate)
            ->where('end_date >=', $clockOutDate)
            ->countAllResults() > 0;

        return $hasOvertime ? $actualEnd : $capTimestamp;
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