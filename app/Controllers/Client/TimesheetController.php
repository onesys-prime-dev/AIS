<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\Client\TasksModel;

class TimesheetController extends BaseController
{
    public function index()
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->to('/login');
        }

        $tasksModel = new TasksModel();

        $timesheet = $tasksModel
            ->select('
                attendance.date,
                tasks.start_time,
                tasks.end_time,
                employee_positions.department,
                tasks.task_performed,
                tasks.id
            ')
            ->join('attendance', 'attendance.id = tasks.attendance_id')
            ->join('employees', 'employees.user_id = tasks.user_id')
            ->join('employee_positions', 'employee_positions.employee_id = employees.id')
            ->where('tasks.user_id', $user->id) // show only their tasks
            ->orderBy('attendance.date', 'DESC')
            ->orderBy('tasks.start_time', 'ASC')
            ->findAll();

        return view('client_portal/timesheet', [
            'timesheet' => $timesheet
        ]);
    }
}