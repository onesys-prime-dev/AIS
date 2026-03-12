<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\Client\TasksModel;
use App\Models\Client\SavedTasksModel;
use App\Models\Client\AttendanceModel;

class TaskController extends BaseController
{
    protected $tasksModel;
    protected $savedTasksModel;
    protected $attendanceModel;

    public function __construct()
    {
        $this->tasksModel      = new TasksModel();
        $this->savedTasksModel = new SavedTasksModel();
        $this->attendanceModel = new AttendanceModel();
    }

    
    // Show the task logging form (or dashboard snippet)
    
    public function showLogTaskForm()
    {
        $user = auth()->user();
        $userId = $user->id;

        // get current active attendance
        $currentAttendance = $this->attendanceModel
            ->where('user_id', $userId)
            ->where('time_out', null)
            ->first();

        // if not timed in, do not show form
        if (!$currentAttendance) {
            return redirect()->back()->with('error', 'You must be timed in to log tasks!');
        }

        // fetch saved tasks for dropdown
        $savedTasks = $this->savedTasksModel
            ->where('user_id', $userId)
            ->orderBy('saved_at', 'DESC')
            ->findAll();

        return view('client/log_task', [
            'currentAttendance' => $currentAttendance,
            'savedTasks' => $savedTasks,
        ]);
    }

    
    // handle submission of a task
    
    public function submitTask()
    {
        $user = auth()->user();
        $userId = $user->id;

        $attendance = $this->attendanceModel
            ->where('user_id', $userId)
            ->where('time_out', null)
            ->first();

        if (!$attendance) {
            return redirect()->back()->with('error', 'You must be timed in to log tasks!');
        }

        $post = $this->request->getPost();
        $taskPerformed = trim($post['task_performed']);
        $startTime     = $post['start_time'];
        $endTime       = $post['end_time'];
        $saveTask      = isset($post['save_task']); // checkbox toggle

        $timeIn  = $attendance['time_in'];
        $now     = date('Y-m-d H:i:s');

        // task time validation
        if ($startTime < $attendance['time_in']) {
            return redirect()->back()->with('error', 'Task start time cannot be before your clock-in time!');
        }

        if ($endTime > $now) {
            return redirect()->back()->with('error', 'Task end time cannot be in the future!');
        }

        if ($endTime < $startTime) {
            return redirect()->back()->with('error', 'Task end time cannot be before start time!');
        }

        // insert task
        $this->tasksModel->insert([
            'attendance_id'  => $attendance['id'],
            'user_id'        => $userId,
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'task_performed' => $taskPerformed,
            'saved_at'       => $now,
        ]);

        // optionally save to saved_tasks
        if ($saveTask) {
            // avoid duplicates
            $exists = $this->savedTasksModel
                ->where('user_id', $userId)
                ->where('task_performed', $taskPerformed)
                ->first();

            if (!$exists) {
                $this->savedTasksModel->insert([
                    'user_id'        => $userId,
                    'task_performed' => $taskPerformed,
                    'saved_at'       => $now,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Task logged successfully!');
    }


    public function getSavedTasks()
    {
        $user = auth()->user();
        $userId = $user->id;

        $tasks = $this->savedTasksModel
            ->where('user_id', $userId)
            ->orderBy('saved_at', 'DESC')
            ->findAll();

        return $this->response->setJSON($tasks);
    }
}