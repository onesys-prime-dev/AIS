<?php

namespace App\Controllers;

use App\Models\RequestModel;

class RequestController extends BaseController
{
    protected $requestModel;

    public function __construct()
    {
        $this->requestModel = new RequestModel();
    }

    // 
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