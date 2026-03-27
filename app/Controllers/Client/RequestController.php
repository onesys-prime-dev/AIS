<?php

namespace App\Controllers\Client;

use App\Models\Client\RequestModel;
use App\Controllers\BaseController;

class RequestController extends BaseController
{
    protected $requestModel;

    public function __construct()
    {
        $this->requestModel = new RequestModel();
    }

    public function index()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->to('/login');
        }

        // Payroll staff see all requests
        if ($user->inGroup('client_payroll_staff')) {

            $data['requests'] = $this->requestModel
                ->select("requests.*, employees.employee_number, ea.photo_path,
                    COALESCE(NULLIF(CONCAT(employees.last_name, ', ', employees.first_name, CASE WHEN employees.middle_name IS NOT NULL AND TRIM(employees.middle_name) != '' THEN CONCAT(' ', UPPER(LEFT(TRIM(employees.middle_name), 1)), '.') ELSE '' END), ''), users.username) as requested_by_name")
                ->join('users', 'users.id = requests.requested_by', 'left')
                ->join('employees', 'employees.user_id = requests.requested_by', 'left')
                ->join('employee_accounts as ea', 'ea.employee_id = employees.id', 'left')
                ->orderBy('created_at', 'DESC')
                ->findAll();

        } else {

            // Employees only see their own requests
            $data['requests'] = $this->requestModel
                ->select("requests.*, employees.employee_number,
                    COALESCE(NULLIF(CONCAT(employees.last_name, ', ', employees.first_name, CASE WHEN employees.middle_name IS NOT NULL AND TRIM(employees.middle_name) != '' THEN CONCAT(' ', UPPER(LEFT(TRIM(employees.middle_name), 1)), '.') ELSE '' END), ''), users.username) as requested_by_name")
                ->join('users', 'users.id = requests.requested_by', 'left')
                ->join('employees', 'employees.user_id = requests.requested_by', 'left')
                ->join('employee_accounts as ea', 'ea.employee_id = employees.id', 'left')
                ->where('requested_by', $user->id)
                ->orderBy('created_at', 'DESC')
                ->findAll();
        }

        foreach ($data['requests'] as &$request) {
            if (! empty($request['photo_path'])) {
                $request['photo_path'] = base_url((string) $request['photo_path']);
            }
        }
        unset($request);

        return view('client_portal/requests', $data);
    }

    public function updateStatus($id)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->to('/login');
        }

        // Only payroll staff can approve/reject
        if (!$user->inGroup('client_payroll_staff')) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $status = $this->request->getPost('status');

        // Fetch old state before updating
        $old = $this->requestModel->find($id);

        $this->requestModel->update($id, [
            'status'       => $status,
            'processed_by' => $user->id,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        service('activityLogger')->requestStatusChanged($id, $old['status'], $status, $user->id);

        return redirect()->back()->with('success', "Request $status successfully.");
    }

    public function create()
    {
        return view('client_portal/dashboard');
    }

    public function store()
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->to('/login');
        }

        $userId       = $user->id;
        $requestType  = trim((string) $this->request->getPost('request_type'));
        $startDate    = trim((string) $this->request->getPost('start_date'));
        $endDate      = trim((string) $this->request->getPost('end_date'));
        $approvalCode = trim((string) $this->request->getPost('approval_code'));
        $reason       = trim((string) $this->request->getPost('reason'));

        $allowedTypes = ['leave', 'overtime', 'undertime'];
        if (! in_array($requestType, $allowedTypes, true)) {
            return redirect()->back()->withInput()->with('error', 'Invalid request type.');
        }

        if ($startDate === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            return redirect()->back()->withInput()->with('error', 'Start date is required and must use YYYY-MM-DD format.');
        }

        $startDateObj = date_create($startDate);
        if (! $startDateObj) {
            return redirect()->back()->withInput()->with('error', 'Invalid start date.');
        }

        $endDateObj = null;
        if ($endDate !== '') {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                return redirect()->back()->withInput()->with('error', 'End date must use YYYY-MM-DD format.');
            }

            $endDateObj = date_create($endDate);
            if (! $endDateObj) {
                return redirect()->back()->withInput()->with('error', 'Invalid end date.');
            }

            if ($endDateObj < $startDateObj) {
                return redirect()->back()->withInput()->with('error', 'End date cannot be earlier than start date.');
            }
        }

        $effectiveEndDate = $endDateObj ?? $startDateObj;
        $duration         = (int) $startDateObj->diff($effectiveEndDate)->days + 1;

        if ($reason === '') {
            return redirect()->back()->withInput()->with('error', 'Message is required.');
        }

        $insertData = [
            'requested_by'  => $userId,
            'request_type'  => $requestType,
            'start_date'    => $startDate,
            'end_date'      => $endDate !== '' ? $endDate : null,
            'duration'      => $duration,
            'approval_code' => $approvalCode !== '' ? $approvalCode : null,
            'reason'        => $reason,
            'status'        => 'pending',
        ];

        $this->requestModel->insert($insertData);

        service('activityLogger')->requestFiled($this->requestModel->getInsertID(), $insertData);

        return redirect()->back()->with('success', 'Request submitted successfully.');
    }
}
