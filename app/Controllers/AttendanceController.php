<?php

namespace App\Controllers;

use App\Models\AttendanceModel;

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

        if (! $attendance) {
            return redirect()->back()->with('error', 'No active time in found!');
        }

        $timeOut = date('H:i:s');
        $timeIn  = $attendance['time_in'];

        // calculates total hours
        $start = strtotime($attendance['date'] . ' ' . $timeIn);
        $end = strtotime($attendance['date'] . ' ' . $timeOut); 
        $totalHours = ($end - $start) / 3600 - $attendance['break_duration'];

        $this->attendanceModel->update($attendance['id'], [
            'time_out'    => $timeOut,
            'total_hours' => round($totalHours, 2),
        ]);

        return redirect()->back()
            ->with('success', 'Time out recorded!')
            ->with('timeout_time', $timeOut)
            ->with('total_hours', round($totalHours, 2));
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
            return redirect()->back()->with('error', 'Maximum break time reached!');
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
            'break_end' => date('Y-m-d H:i:s'),
            'break_duration' => $totalBreak,
        ]);

        return redirect()->back()
            ->with('success', 'Break ended!')
            ->with('break_hours', round($totalBreak, 2));
    }
}
}