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
                                    ->orderBy('created_at', 'DESC')
                                    ->findAll();

        } else {

            // Employees only see their own requests
            $data['requests'] = $this->requestModel
                                    ->where('requested_by', $user->id)
                                    ->orderBy('created_at', 'DESC')
                                    ->findAll();
        }

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

        $this->requestModel->update($id, [
            'status'       => $status,
            'processed_by' => $user->id,
            'updated_at'   => date('Y-m-d H:i:s')
        ]);

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

        $userId = $user->id;

        $this->requestModel->insert([
            'requested_by' => $userId,
            'request_type' => $this->request->getPost('request_type'),
            'date'         => $this->request->getPost('date'),
            'reason'       => $this->request->getPost('reason'),
            'status'       => 'pending'
        ]);

        return redirect()->back()->with('success', 'Request submitted successfully.');
    }
}