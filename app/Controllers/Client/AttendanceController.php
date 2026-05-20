<?php

namespace App\Controllers\Client;

use App\Models\Client\AttendanceModel;
use App\Controllers\BaseController;

class AttendanceController extends BaseController
{
    protected $attendanceModel;

    public function __construct()
    {
        $this->attendanceModel = new AttendanceModel();
    }

    public function timeIn()
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->to('/login');
        }

        $userId = $user->id;

        // check if already timed in
        $current = $this->attendanceModel
            ->where('user_id', $userId)
            ->where('time_out', null)
            ->first();

        if ($current) {
            return redirect()->back()->with('error', 'Already timed in!');
        }

        $timeIn = date('H:i:s');
        $date   = date('Y-m-d');

        $this->attendanceModel->insert([
            'user_id' => $userId,
            'date'    => $date,
            'time_in' => $timeIn,
        ]);

        service('activityLogger')->attendanceClockedIn($this->attendanceModel->getInsertID(), [
            'date'    => $date,
            'time_in' => $timeIn,
        ]);

        return redirect()->back()->with('success', 'Time in recorded!');
    }

    public function timeOut($id)
    {
        $user = auth()->user();
        $userId = $user->id;

        $attendance = $this->attendanceModel
            ->where('id', $id)
            ->where('user_id', $userId)
            ->where('time_out', null)
            ->first();

        if (!$attendance) {
            return redirect()->back()->with('error', 'No active time in found!');
        }

        $timeIn = $attendance['time_in'];
        $date   = $attendance['date'];

        $start = strtotime($date . ' ' . $timeIn);
        $end   = time();

        $totalHours = ($end - $start) / 3600;
        $breakDuration = (float)($attendance['break_duration'] ?? 0);
        $workedHours = $totalHours - $breakDuration;

        // Compute night differential hours (between 22:00 and 06:00)
        $nightDiffHours = $this->computeNightDiffHours($attendance['date'], $timeIn, date('H:i:s', $end), $breakDuration);

        $this->attendanceModel->update($attendance['id'], [
            'time_out'         => date('H:i:s'),
            'total_hours'      => round($totalHours, 2),
            'break_duration'   => $breakDuration,
            'night_diff_hours' => round($nightDiffHours, 2),
        ]);

        service('activityLogger')->attendanceClockedOut($attendance['id'], [
            'time_out'    => date('H:i:s'),
            'total_hours' => round($totalHours, 2),
        ]);

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

        if (!empty($attendance['break_start']) && !empty($attendance['break_end'])) {
            return redirect()->back()->with('error', "You've already used your 1 hour break!");
        }

        if (empty($attendance['break_start']) && empty($attendance['break_end'])) {
            // Start break
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
            // End break
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

    private function computeNightDiffHours($date, $timeIn, $timeOut, $breakDuration)
    {
        $start = new \DateTime($date . ' ' . $timeIn);
        $end   = new \DateTime($date . ' ' . $timeOut);
        if ($end < $start) {
            $end->modify('+1 day');
        }

        $ndStart = new \DateTime($date . ' 22:00:00');
        $ndEnd   = new \DateTime($date . ' 06:00:00');
        $ndEnd->modify('+1 day');

        $overlapStart = max($start, $ndStart);
        $overlapEnd   = min($end, $ndEnd);
        $nightMinutes = 0;
        if ($overlapStart < $overlapEnd) {
            $diff = $overlapStart->diff($overlapEnd);
            $nightMinutes = $diff->h * 60 + $diff->i;
        }
        // Simple deduction: assume break does not overlap night diff; refine if needed
        return $nightMinutes / 60;
    }
}