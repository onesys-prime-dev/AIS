<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Models\Client\PayrollCutoffModel;
use App\Services\Client\PayrollAggregationService;

class PayrollCutoffController extends BaseController
{
    protected $cutoffModel;
    protected $aggService;

    public function __construct()
    {
        $this->cutoffModel = new PayrollCutoffModel();
        $this->aggService = new PayrollAggregationService();
    }

    public function index()
    {
        $data['cutoffs'] = $this->cutoffModel->orderBy('start_date', 'DESC')->findAll();
        return view('client_portal/payroll_cutoffs', $data);
    }

    public function create()
    {
        if ($this->request->getMethod() === 'post') {
            $this->cutoffModel->save([
                'name'       => $this->request->getPost('name'),
                'start_date' => $this->request->getPost('start_date'),
                'end_date'   => $this->request->getPost('end_date'),
            ]);
            return redirect()->to('/client_portal/payroll-cutoffs')->with('success', 'Cutoff created');
        }
        return view('client_portal/payroll_cutoff_form');
    }

    public function setActive($id)
    {
        $this->cutoffModel->setActiveCutoff($id);
        return redirect()->back()->with('success', 'Active cutoff changed');
    }

    public function aggregate($id)
    {
        $this->aggService->aggregateCutoff($id);
        return redirect()->back()->with('success', 'Attendance aggregated for cutoff');
    }
}