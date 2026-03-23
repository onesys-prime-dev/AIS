<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Services\Client\TimesheetsService;

class TimesheetController extends BaseController
{
    protected TimesheetsService $timesheetsService;

    public function __construct()
    {
        $this->timesheetsService = new TimesheetsService();
    }

    public function index()
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->to('/login');
        }

        $timesheet = $this->timesheetsService->listByUserId((int) $user->id);

        return view('client_portal/timesheet', [
            'timesheet' => $timesheet,
            'currentUserId' => (int) $user->id,
        ]);
    }

    public function update(int $id)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->to('/login');
        }

        $date = trim((string) $this->request->getPost('date'));
        $startTime = trim((string) $this->request->getPost('start_time'));
        $endTime = trim((string) $this->request->getPost('end_time'));
        $taskPerformed = trim((string) $this->request->getPost('task_performed'));

        if ($taskPerformed === '') {
            return redirect()->back()->with('error', 'Task Performed is required.');
        }

        if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return redirect()->back()->with('error', 'Invalid date format. Use YYYY-MM-DD.');
        }

        if ($startTime !== '' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $startTime)) {
            return redirect()->back()->with('error', 'Invalid start time format. Use HH:MM or HH:MM:SS.');
        }

        if ($endTime !== '' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $endTime)) {
            return redirect()->back()->with('error', 'Invalid end time format. Use HH:MM or HH:MM:SS.');
        }

        if ($startTime !== '' && $endTime !== '' && $endTime < $startTime) {
            return redirect()->back()->with('error', 'End time cannot be earlier than start time.');
        }

        $updated = $this->timesheetsService->updateByUserId($id, (int) $user->id, [
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'task_performed' => $taskPerformed,
        ]);

        if (!$updated) {
            return redirect()->back()->with('error', 'Unable to update timesheet.');
        }

        return redirect()->back()->with('success', 'Timesheet updated successfully.');
    }

    public function delete(int $id)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->to('/login');
        }

        $deleted = $this->timesheetsService->deleteByUserId($id, (int) $user->id);

        if (!$deleted) {
            return redirect()->back()->with('error', 'Unable to delete timesheet.');
        }

        return redirect()->back()->with('success', 'Timesheet deleted successfully.');
    }
}