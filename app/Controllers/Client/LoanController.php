<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;

use App\Services\Client\LoanService;

use App\Models\Client\LoanModel;
use App\Models\Client\LoanScheduleModel;
use App\Models\Client\LoanSettingModel;

class LoanController extends BaseController
{
    public function loanView()
    {
        return redirect()->to(site_url('client_portal/employees'));
    }

    protected LoanService $loanService;
    protected LoanModel $loanModel;
    protected LoanScheduleModel $scheduleModel;
    protected LoanSettingModel $loanSettingModel;

    public function __construct()
    {
        $this->loanService = new LoanService();
        $this->loanModel = new LoanModel();
        $this->scheduleModel = new LoanScheduleModel();
        $this->loanSettingModel = new LoanSettingModel();
        
    }

    public function getLoanEmployees()
    {
        $db = \Config\Database::connect();

        $rows = $db->table('employees')

            ->select("
                employees.id,

                employees.first_name,
                employees.middle_name,
                employees.last_name,

                employee_positions.monthly_salary,
                employee_positions.working_days
            ")

            ->join(
                'users',
                'users.id = employees.user_id',
                'left'
            )

            ->join(
                'employee_positions',
                'employee_positions.employee_id = employees.id',
                'left'
            )

            ->where('users.active', 1)

            ->orderBy('employees.first_name', 'ASC')

            ->get()

            ->getResultArray();

        $employees = array_map(function ($row) {

            $first = trim($row['first_name'] ?? '');
            $last  = trim($row['last_name'] ?? '');
            $mid   = trim($row['middle_name'] ?? '');

            $mi = $mid !== ''
                ? strtoupper(substr($mid, 0, 1)) . '.'
                : '';

            $name = trim(
                $first . ' ' .
                ($mi !== '' ? $mi . ' ' : '') .
                $last
            );

            /*
            |--------------------------------------------------------------------------
            | WORKING DAYS
            |--------------------------------------------------------------------------
            */

            $workingDays = json_decode(
                $row['working_days'] ?? '[]',
                true
            );

            $workingDaysCount =
                is_array($workingDays)
                ? count($workingDays)
                : 5;

            /*
            |--------------------------------------------------------------------------
            | MONTHLY ESTIMATION
            |--------------------------------------------------------------------------
            */

            $monthlySalary =
                (float) ($row['monthly_salary'] ?? 0);

            $estimatedMonthlyWorkDays =
                round($workingDaysCount * 4.33);

            $dailyRate =
                $estimatedMonthlyWorkDays > 0
                ? round(
                    $monthlySalary / $estimatedMonthlyWorkDays,
                    2
                )
                : 0;

            return [

                'id' => (string) $row['id'],

                'name' => $name,

                'label' => $name,

                'monthly_salary' => $monthlySalary,

                'daily_rate' => $dailyRate,

                'working_days' => $workingDays,

                'working_days_count' => $workingDaysCount,
            ];

        }, $rows);

        return $this->response->setJSON($employees);
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE NEW LOAN
    |--------------------------------------------------------------------------
    */

    public function newLoan()
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $rules = [
            'loan_employee_id' => 'required|integer',
            'loan_type' => 'required',
            'principal_amount' => 'required|numeric',
            'installment_count' => 'required|integer',
            'start_date' => 'required',
        ];

        if (!$this->validate($rules)) {

            return redirect()->back()
                ->withInput()
                ->with(
                    'error',
                    implode(
                        '<br>',
                        $this->validator->getErrors()
                    )
                );
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE LOAN
        |--------------------------------------------------------------------------
        */
        try {
            $loanId = $this->loanService->createLoan([

            'loan_employee_id' =>
                $this->request->getPost('loan_employee_id'),

            'loan_category' =>
                $this->request->getPost('loan_category'),

            'loan_type' =>
                $this->request->getPost('loan_type'),

            'principal_amount' =>
                $this->request->getPost('principal_amount'),

            'interest_rate' =>
                $this->request->getPost('interest_rate'),

            'total_interest' =>
                $this->request->getPost('total_interest'),

            'total_payable' =>
                $this->request->getPost('total_payable'),

            'deduction_per_payroll' =>
                $this->request->getPost('deduction_per_payroll'),

            'remaining_balance' =>
                $this->request->getPost('remaining_balance'),

            'deduction_frequency' =>
                $this->request->getPost('deduction_frequency'),

            'installment_count' =>
                $this->request->getPost('installment_count'),

            'start_date' =>
                $this->request->getPost('start_date'),

            'estimated_end_date' =>
                $this->request->getPost('estimated_end_date'),

            'loan_status' =>
                $this->request->getPost('loan_status'),
        ]);

            return redirect()->back()->with('success','Employee loan created successfully.');

            } catch (\Exception $e) {
                log_message('error', static::class . ': ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
                return redirect()->back()->withInput()->with('error', $this->safeErrorMessage($e, 'Unable to create employee loan. Please check the inputs and try again.'));
            }
    }

    /*
    |--------------------------------------------------------------------------
    | GET ACTIVE LOANS
    |--------------------------------------------------------------------------
    */

    public function getLoans()
    {
        $loans = $this->loanModel
            ->select("
                employee_loans.*,

                employees.first_name,
                employees.middle_name,
                employees.last_name
            ")
            ->join(
                'employees',
                'employees.id = employee_loans.employee_id',
                'left'
            )
            ->orderBy(
                'employee_loans.created_at',
                'DESC'
            )
            ->findAll();

        $formatted = array_map(function ($loan) {
            $first = trim($loan['first_name'] ?? '');
            $middle = trim($loan['middle_name'] ?? '');
            $last = trim($loan['last_name'] ?? '');
            $mi = $middle !== ''
                ? strtoupper(substr($middle, 0, 1)) . '.'
                : '';
            $employeeName = trim(
                $first . ' ' .
                ($mi !== '' ? $mi . ' ' : '') .
                $last
            );

            return [
                'id' => $loan['id'],
                'employee_name' => $employeeName,

                'loan_category' => $loan['loan_category'],
                'loan_type' => $loan['loan_type'],

                'principal_amount' => $loan['principal_amount'],
                'interest_rate' => $loan['interest_rate'],
                'total_interest' => $loan['total_interest'],
                'total_payable' => $loan['total_payable'],

                'deduction_per_payroll' => $loan['deduction_per_payroll'],
                'deduction_frequency' => $loan['deduction_frequency'],

                'installment_count' => $loan['installment_count'],

                'remaining_balance' => $loan['remaining_balance'],

                'start_date' => $loan['start_date'],
                'estimated_end_date' => $loan['estimated_end_date'],

                'loan_status' => $loan['loan_status'],
            ];
        }, $loans);

        return $this->response->setJSON($formatted);
    }

    /*
    |--------------------------------------------------------------------------
    | VIEW LOAN SCHEDULE
    |--------------------------------------------------------------------------
    */

    public function viewSchedule($loanId)
    {
        $loan = $this->loanModel->find($loanId);
        if (!$loan) {

            throw new \CodeIgniter\Exceptions\PageNotFoundException(
                'Loan not found.'
            );
        }

        $schedule = $this->scheduleModel
            ->where('loan_id', $loanId)
            ->orderBy('installment_no', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'loan' => $loan,
            'schedule' => $schedule,
        ]);
    }

    public function getLoanPayments($loanId)
    {
        $payments = $this->loanService
            ->getLoanPayments((int) $loanId);

        return $this->response->setJSON($payments);
    }

    public function updateLoan($loanId)
    {
        $loan = $this->loanModel->find($loanId);

        if (!$loan) {

            return redirect()->back()
                ->with(
                    'error',
                    'Loan not found.'
                );
        }

        try {

            $this->loanService->updateLoan(
                (int) $loanId,
                [
                    'start_date' =>
                        $this->request->getPost('start_date'),

                    'remarks' =>
                        $this->request->getPost('remarks'),
                ]
            );

            return redirect()->back()
                ->with(
                    'success',
                    'Loan updated successfully.'
                );

        } catch (\Exception $e) {

            return $this->flashError($e, 'Unable to update loan. Please try again.');
        }
    }

    public function completeLoan($loanId)
    {
        $loan = $this->loanModel->find($loanId);

        if (!$loan) {

            return redirect()->back()
                ->with(
                    'error',
                    'Loan not found.'
                );
        }

        $this->loanService->completeLoan(
            (int) $loanId
        );

        return redirect()->back()
            ->with(
                'success',
                'Loan marked as completed.'
            );
    }

    public function cancelLoan($loanId)
    {
        $loan = $this->loanModel->find($loanId);

        if (!$loan) {

            return redirect()->back()
                ->with(
                    'error',
                    'Loan not found.'
                );
        }

        $this->loanService->cancelLoan(
            (int) $loanId
        );

        return redirect()->back()
            ->with(
                'success',
                'Loan cancelled successfully.'
            );
    }

    public function getLoanSettings()
    {
        $settings = $this->loanSettingModel
            ->orderBy('loan_type', 'ASC')
            ->findAll();

        return $this->response->setJSON($settings);
    }

    public function saveLoanSettings()
    {
        $loanTypes = $this->request->getPost('loan_type');
        $loanCategories = $this->request->getPost('loan_category');

        if (!$loanTypes || !$loanCategories) {
            return redirect()->back()
                ->with(
                    'error',
                    'No settings submitted.'
                );
        }

        foreach ($loanTypes as $index => $loanType) {
            $loanCategory = trim($loanCategories[$index] ?? '');
            $loanType = trim($loanType);

            if (
                empty($loanCategory) ||
                empty($loanType)
            ) {
                continue;
            }

            $existing =
                $this->loanSettingModel
                    ->where(
                        'loan_category',
                        $loanCategory
                    )
                    ->where(
                        'loan_type',
                        $loanType
                    )
                    ->first();

            $data = [

                'loan_category' =>
                    $loanCategory,
                'loan_type' =>
                    $loanType,
                'interest_rate' =>
                    $this->request
                        ->getPost('interest_rate')[$index] ?? 0,
                'max_salary_multiplier' =>
                    $this->request
                        ->getPost('max_salary_multiplier')[$index] ?? 0,
                'max_installments' =>
                    $this->request
                        ->getPost('max_installments')[$index] ?? 0,
                'deduction_frequency' =>
                    $this->request
                        ->getPost('deduction_frequency')[$index] ?? 'monthly',
                'allow_multiple_active_loans' =>
                    isset(
                        (
                            $this->request
                                ->getPost('allow_multiple_loans') ?? []
                        )[$index]
                    ) ? 1 : 0,
                'grace_period_days' =>
                    $this->request
                        ->getPost('grace_period_days')[$index] ?? 0,
                'penalty_rate' =>
                    $this->request
                        ->getPost('penalty_rate')[$index] ?? 0,
                'is_active' => 1,
            ];

            if ($existing) {
                $this->loanSettingModel
                    ->update(
                        $existing['id'],
                        $data
                    );
            } else {
                $this->loanSettingModel
                    ->insert($data);
            }
        }

        return redirect()->back()
            ->with(
                'success',
                'Loan settings updated successfully.'
            );
    }

    public function getEmployeeLoanInfo($employeeId)
    {
        $db = \Config\Database::connect();

        $employee = $db->table('employee_positions')
            ->select('monthly_salary, working_days')
            ->where('employee_id', $employeeId)
            ->get()
            ->getRowArray();

        if (!$employee) {

            return $this->response->setJSON([
                'monthly_salary' => 0,
                'daily_rate' => 0,
            ]);
        }

        $monthlySalary =
            (float) ($employee['monthly_salary'] ?? 0);

        /*
        |----------------------------------------------------------------------
        | WORKING DAYS
        |----------------------------------------------------------------------
        */

        $workingDays = json_decode(
            $employee['working_days'] ?? '[]',
            true
        );

        $workingDaysCount =
            is_array($workingDays)
            ? count($workingDays)
            : 5;

        /*
        |----------------------------------------------------------------------
        | DAILY RATE
        |----------------------------------------------------------------------
        */

        $estimatedMonthlyWorkDays =
            round($workingDaysCount * 4.33);

        $dailyRate =
            $estimatedMonthlyWorkDays > 0
            ? round(
                $monthlySalary / $estimatedMonthlyWorkDays,
                2
            )
            : 0;

        return $this->response->setJSON([

            'monthly_salary' => $monthlySalary,

            'daily_rate' => $dailyRate,

        ]);
    }
}
