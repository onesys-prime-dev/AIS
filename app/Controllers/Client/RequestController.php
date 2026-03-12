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

        // get all requests
        $data['requests'] = $this->requestModel
                                ->orderBy('created_at', 'DESC')
                                ->findAll();

        return view('client_portal/requests', $data);
    }

    public function updateStatus($id)
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->to('/login');
        }

        $status = $this->request->getPost('status'); // 'approved' or 'rejected'

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