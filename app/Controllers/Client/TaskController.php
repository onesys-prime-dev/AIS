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

    public function submitTask()
    {
        $user   = auth()->user();
        $userId = $user->id;

        $attendance = $this->attendanceModel
            ->where('user_id', $userId)
            ->where('time_out', null)
            ->first();

        if (!$attendance) {
            return redirect()->back()->with('error', 'You must be timed in to log tasks!');
        }

        $post          = $this->request->getPost();
        if (! is_string($post['task_performed'] ?? null) || ! is_string($post['start_time'] ?? null) || ! is_string($post['end_time'] ?? null)) {
            return redirect()->back()->with('error', 'Please fill in the task, start time and end time.');
        }
        $taskPerformed = trim($post['task_performed']);
        $startTime     = $post['start_time'];
        $endTime       = $post['end_time'];
        $saveTask      = isset($post['save_task']);

        // Task time validation
        if ($startTime < $attendance['time_in']) {
            return redirect()->back()->with('error', 'Task start time cannot be before your clock-in time!');
        }

        if ($endTime < $startTime) {
            return redirect()->back()->with('error', 'Task end time cannot be before start time!');
        }

        $now = date('Y-m-d H:i:s');

        $this->tasksModel->insert([
            'attendance_id'  => $attendance['id'],
            'user_id'        => $userId,
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'task_performed' => $taskPerformed,
            'saved_at'       => $now,
        ]);

        service('activityLogger')->taskLogged(
            $this->tasksModel->getInsertID(),
            $attendance['id'],
            [
                'start_time'     => $startTime,
                'end_time'       => $endTime,
                'task_performed' => $taskPerformed,
            ]
        );

        if ($saveTask) {
            // Avoid duplicates
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

                service('activityLogger')->savedTaskCreated(
                    $this->savedTasksModel->getInsertID(),
                    $taskPerformed
                );
            }
        }

        return redirect()->back()->with('success', 'Task logged successfully!');
    }

    public function getSavedTasks()
    {
        $user   = auth()->user();
        $userId = $user->id;

        $tasks = $this->savedTasksModel
            ->where('user_id', $userId)
            ->orderBy('saved_at', 'DESC')
            ->findAll();

        return $this->response->setJSON($tasks);
    }

    public function getSavedClients()
    {
        $clients = (new \App\Models\Client\SavedClientsModel())
            ->where('user_id', auth()->user()->id)
            ->orderBy('client', 'ASC')
            ->findAll();

        return $this->response->setJSON($clients);
    }
}
