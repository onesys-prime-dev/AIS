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
        $user   = auth()->user();
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

        $totalHours    = ($end - $start) / 3600;
        $breakDuration = $attendance['break_duration'] ?? 0;
        $workedHours   = $totalHours - $breakDuration;
        $cappedHours   = min($workedHours, 8);

        $timeOutTimestamp = $start + ($cappedHours + $breakDuration) * 3600;
        $timeOut          = date('H:i:s', $timeOutTimestamp);

        // Capture state before the update
        $before = [
            'time_out'    => $attendance['time_out'],
            'total_hours' => $attendance['total_hours'],
        ];

        $this->attendanceModel->update($attendance['id'], [
            'time_out'       => $timeOut,
            'total_hours'    => round($cappedHours, 2),
            'break_duration' => $breakDuration,
            'break_end'      => (!empty($attendance['break_start']) && empty($attendance['break_end']))
                ? date('Y-m-d H:i:s')
                : ($attendance['break_end'] ?? null),
        ]);

        service('activityLogger')->attendanceClockedOut($attendance['id'], $before, [
            'time_out'    => $timeOut,
            'total_hours' => round($cappedHours, 2),
        ]);

        return redirect()->back()
            ->with('success', 'Time out recorded!')
            ->with('timeout_time', $timeOut)
            ->with('total_hours', round($cappedHours, 2))
            ->with('break_hours', $breakDuration);
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
                [
                    'break_start'    => null,
                    'break_end'      => $attendance['break_end'],
                    'break_duration' => $attendance['break_duration'],
                ],
                [
                    'break_start'    => $breakStartTime,
                    'break_end'      => $attendance['break_end'],
                    'break_duration' => $attendance['break_duration'],
                ]
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
                [
                    'break_start'    => $attendance['break_start'],
                    'break_end'      => null,
                    'break_duration' => $attendance['break_duration'],
                ],
                [
                    'break_start'    => null,
                    'break_end'      => $breakEndTime,
                    'break_duration' => round($totalBreak, 2),
                ]
            );

            return redirect()->back()
                ->with('success', 'Break ended!')
                ->with('break_hours', round($totalBreak, 2));
        }
    }
}
