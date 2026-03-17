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

        $this->attendanceModel->insert([
            'user_id' => $userId,
            'date' => date('Y-m-d'),
            'time_in' => date('H:i:s'),
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
        $end   = time(); // default current time

        // compute total hours worked so far
        $totalHours = ($end - $start) / 3600;

        // check break duration
        $breakDuration = $attendance['break_duration'] ?? 0;

        // if break is less than 1 hour, automatically add up to 1 hour
        /*  if ($breakDuration < 1) {
            $breakDuration = 1.0;
        } */

        // subtract break from total hours
        $workedHours = $totalHours - $breakDuration;

        // cap worked hours to 8
        $cappedHours = min($workedHours, 8);

        // adjust time_out if they "forgot" to clock out
        $timeOutTimestamp = $start + ($cappedHours + $breakDuration) * 3600;
        $timeOut = date('H:i:s', $timeOutTimestamp);

        // update attendance record
        $this->attendanceModel->update($attendance['id'], [
            'time_out'       => $timeOut,
            'total_hours'    => round($cappedHours, 2),
            'break_duration' => $breakDuration, // ensure 1 hour minimum
            'break_end'      => $attendance['break_start'] ? date('Y-m-d H:i:s') : $attendance['break_end'] ?? null,
            'break_start'    => null, // clear break_start
        ]);

        return redirect()->back()
            ->with('success', 'Time out recorded!')
            ->with('timeout_time', $timeOut)
            ->with('total_hours', round($cappedHours, 2))
            ->with('break_hours', $breakDuration);
    }

    public function toggleBreak($id)
    {
        $user = auth()->user();
        $userId = $user->id;

        $attendance = $this->attendanceModel
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $attendance || $attendance['time_out'] !== null) {
            return redirect()->back()->with('error', 'No active attendance found!');
        }

        if (empty($attendance['break_start'])) {
            // start break
            if ($attendance['break_duration'] >= 1) {
                return redirect()->back()->with('error', "You've used up your 1 hour break!");
            }

            $this->attendanceModel->update($id, [
                'break_start' => date('Y-m-d H:i:s')
            ]);

            return redirect()->back()->with('success', 'Break started!');
        } else {
            // end break
            $breakStart = strtotime($attendance['break_start']);
            $breakEnd = time();
            $breakHours = ($breakEnd - $breakStart) / 3600;

            // break duration caps at 1 hour
            $totalBreak = min($attendance['break_duration'] + $breakHours, 1.0);

            $this->attendanceModel->update($id, [
                'break_start' => null,
                'break_end' => date('Y-m-d H:i:s'),
                'break_duration' => $totalBreak,
            ]);

            return redirect()->back()
                ->with('success', 'Break ended!')
                ->with('break_hours', round($totalBreak, 2));
        }
    }
}
