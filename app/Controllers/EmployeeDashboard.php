<?php

namespace App\Controllers;
use App\Models\AttendanceModel;

class EmployeeDashboard extends BaseController
{
public function index()
    {
        $attendance = new AttendanceModel();
        $session = session();
        $user = auth()->user();
        $userId = $user->id;

        $openAttendance = $attendance
            ->where('user_id', auth()>get('user_id'))
            ->where('time_out', null)
            ->first();

        return view('employee/dashboard', [
            'isTimedIn' => $openAttendance !== null,
        ]);
    }
}