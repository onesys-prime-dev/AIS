<?php

namespace App\Controllers;

use App\Models\AttendanceModel;
use App\Models\RequestModel;

class EmployeeDashboardController extends BaseController
{
    public function index()
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->to('/login');
        }

        $userId = $user->id;

        $attendanceModel = new AttendanceModel();
        $requestModel = new RequestModel();

        // check if user currently timed in
        $currentAttendance = $attendanceModel
            ->where('user_id', $userId)
            ->where('time_out', null)
            ->first();

        $requests = [];

        // only payroll staff see requests
        if ($user->inGroup('client_payroll_staff')) {

            $requests = $requestModel
                ->select('requests.created_at, users.username, requests.request_type, requests.date, requests.reason, requests.status')
                ->join('users', 'users.id = requests.requested_by')
                ->orderBy('requests.created_at', 'DESC')
                ->findAll();
        }

        return view('client_portal/dashboard', [
            'currentAttendance' => $currentAttendance,
            'requests' => $requests
        ]);
    }
}