<?php

namespace App\Services\Client;

use App\Models\Client\LoanModel;
use App\Models\Client\LoanScheduleModel;
use App\Models\Client\LoanPaymentModel;
use App\Models\Client\EmployeePositionModel;
use App\Models\Client\LoanSettingModel;

class LoanService
{
    protected LoanModel $loanModel;

    protected LoanScheduleModel $scheduleModel;

    protected LoanPaymentModel $paymentModel;

    protected LoanSettingModel $loanSettingModel;

    public function __construct()
    {
        $this->loanModel     = new LoanModel();
        $this->scheduleModel = new LoanScheduleModel();
        $this->paymentModel  = new LoanPaymentModel();
        $this->loanSettingModel = new LoanSettingModel();
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE LOAN
    |--------------------------------------------------------------------------
    */

    public function createLoan(array $data): int
    {
        /*
        |--------------------------------------------------------------------------
        | GET EMPLOYEE SALARY
        |--------------------------------------------------------------------------
        */
        $employeePositionModel = new EmployeePositionModel();

        $employeePosition = $employeePositionModel
            ->where(
                'employee_id',
                $data['loan_employee_id']
            )
            ->first();
        if (!$employeePosition) {
            throw new \Exception(
                'Employee salary information not found.'
            );
        }
        $monthlySalary =
            (float) ($employeePosition['monthly_salary'] ?? 0);

        /*
        |--------------------------------------------------------------------------
        | DETERMINE MAX LOAN
        |--------------------------------------------------------------------------
        */

        $loanType = $data['loan_type'];
        $maxLoan = 0;

        /*
        |--------------------------------------------------------------------------
        | GET LOAN SETTINGS
        |--------------------------------------------------------------------------
        */

        $loanSetting = $this->loanSettingModel
            ->where('loan_type', $loanType)
            ->where('is_active', 1)
            ->first();

        if (!$loanSetting) {

            throw new \Exception(
                'Loan settings not configured for ' .
                $loanType
            );
        }

        /*
        |--------------------------------------------------------------------------
        | MAX LOAN
        |--------------------------------------------------------------------------
        */

        $maxLoan =
            $monthlySalary *
            (float) $loanSetting['max_salary_multiplier'];

        /*
        |--------------------------------------------------------------------------
        | VALIDATE MAX LOAN
        |--------------------------------------------------------------------------
        */

        $principal =
            (float) $data['principal_amount'];
        
        $installments =
            (int) $data['installment_count'];

        if ($installments <= 0) {

            throw new \Exception(
                'Invalid installment count.'
            );
        }

        if (
            $installments >
            (int) $loanSetting['max_installments']
        ) {

            throw new \Exception(
                'Installment count exceeds maximum allowed.'
            );
        }

        if ($principal <= 0) {
            throw new \Exception(
                'Principal amount must be greater than 0.'
            );
        }

        if ($principal > $maxLoan) {

            throw new \Exception(

                'Loan amount exceeds maximum allowed amount of ₱' .

                number_format($maxLoan, 2)

            );
        }

        /*
        |--------------------------------------------------------------------------
        | CONTINUE SAVING LOAN
        |--------------------------------------------------------------------------
        */

        if (!(bool) $loanSetting['allow_multiple_active_loans']) {

            $existingLoan = $this->loanModel 
                ->where(
                    'employee_id',
                    $data['loan_employee_id']
                )
                ->where(
                    'loan_type',
                    $data['loan_type']
                )
                ->where(
                    'loan_status',
                    'active'
                )
                ->first();

            if ($existingLoan) {

                throw new \Exception(
                    'Employee already has an active ' .
                    $data['loan_type']
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | AUTO APPLY SETTINGS
        |--------------------------------------------------------------------------
        */

        $data['interest_rate'] =
            $loanSetting['interest_rate'];

        $data['deduction_frequency'] =
            $loanSetting['deduction_frequency'];

        /*
        |--------------------------------------------------------------------------
        | CALCULATE FINANCIALS
        |--------------------------------------------------------------------------
        */

        $interestRate =
            (float) $data['interest_rate'];

        $principal =
            (float) $data['principal_amount'];

        $totalInterest =
            $principal * ($interestRate / 100);

        $totalPayable =
            $principal + $totalInterest;

        $deductionPerPayroll =
            $totalPayable / $installments;

        $data['total_interest'] =
            round($totalInterest, 2);

        $data['total_payable'] =
            round($totalPayable, 2);

        $data['deduction_per_payroll'] =
            round($deductionPerPayroll, 2);

        $data['remaining_balance'] =
            round($totalPayable, 2);

        /*
        |--------------------------------------------------------------------------
        | ESTIMATED END DATE
        |--------------------------------------------------------------------------
        */

        $endDate = new \DateTime(
            $data['start_date']
        );

        if (
            $data['deduction_frequency']
            === 'Monthly'
        ) {

            $months =
                max($installments - 1, 0);

            $endDate->modify("+{$months} month");

        } else {

            $days =
                max(($installments - 1) * 15, 0);

            $endDate->modify("+{$days} days");
        }

        $data['estimated_end_date'] =
            $endDate->format('Y-m-d');

        $db = \Config\Database::connect();
        $db->transBegin();

        $loanId = $this->loanModel->insert([
            'employee_id'            => $data['loan_employee_id'],
            'loan_type'              => $data['loan_type'],
            'loan_category'          => $data['loan_category'],
            'principal_amount'       => $data['principal_amount'],
            'interest_rate'          => $data['interest_rate'],
            'total_interest'         => $data['total_interest'],
            'total_payable'          => $data['total_payable'],
            'deduction_per_payroll'  => $data['deduction_per_payroll'],
            'deduction_frequency'    => $data['deduction_frequency'],
            'installment_count'      => $data['installment_count'],
            'remaining_balance'      => $data['remaining_balance'],
            'start_date'             => $data['start_date'],
            'estimated_end_date'     => $data['estimated_end_date'],
            'loan_status'            => $data['loan_status'] ?? 'active',
            'approved_by'            => auth()->id(),
            'approved_at'            => date('Y-m-d H:i:s'),
            'created_by'             => auth()->id(),
        ], true);

        if (!$loanId) {
            dd($this->loanModel->errors());
        }

        /*
        |--------------------------------------------------------------------------
        | Generate Amortization Schedule
        |--------------------------------------------------------------------------
        */

        try {
            $this->generateLoanSchedule(
                $loanId,
                $data
            );
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        if ($db->transStatus() === false) {
            $db->transRollback();
            throw new \Exception(
                'Failed to create loan.'
            );
        }

        $db->transCommit();

        return (int) $loanId;
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE LOAN SCHEDULE
    |--------------------------------------------------------------------------
    */

    public function generateLoanSchedule(
        int $loanId,
        array $loan
    ): void
    {
        $balance = (float) $loan['total_payable'];

        $amount = (float) $loan['deduction_per_payroll'];

        $date = new \DateTime(
            $loan['start_date']
        );

        for (
            $i = 1;
            $i <= (int) $loan['installment_count'];
            $i++
        ) {
            $balance -= $amount;
            $this->scheduleModel->insert([
                'loan_id' => $loanId,

                'installment_no' => $i,

                'due_date' => $date->format('Y-m-d'),

                'amount_due' => $amount,

                'remaining_balance' => max(
                    round($balance, 2),
                    0
                ),

                'schedule_status' => 'pending',

            ]);

            /*
            |--------------------------------------------------------------------------
            | Next Due Date
            |--------------------------------------------------------------------------
            */

            if (
                $loan['deduction_frequency']
                === 'Monthly'
            ) {

                $date->modify('+1 month');

            } else {

                $date->modify('+15 days');
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET EMPLOYEE LOAN DEDUCTION
    |--------------------------------------------------------------------------
    */

    /* public function getEmployeeLoanDeduction( int $employeeId, string $periodStart, string $periodEnd): float
    {
        $schedule = $this->scheduleModel
            ->select('employee_loan_schedule.*')
            ->join(
                'employee_loans',
                'employee_loans.id = employee_loan_schedule.loan_id'
            )
            ->where('employee_loans.employee_id', $employeeId)
            ->where('employee_loans.loan_status', 'active')
            ->where('employee_loan_schedule.schedule_status', 'pending')
            ->where(
                'employee_loan_schedule.due_date >=',
                $periodStart
            )
            ->where(
                'employee_loan_schedule.due_date <=',
                $periodEnd
            )
            ->orderBy(
                'employee_loan_schedule.installment_no',
                'ASC'
            )

            ->first();

        if (!$schedule) {
            return 0;
        }

        return (float) $schedule['amount_due'];
    } */

    /*
    |--------------------------------------------------------------------------
    | MARK INSTALLMENT AS PAID
    |--------------------------------------------------------------------------
    */
    public function markInstallmentPaid(int $employeeId, string $periodStart, string $periodEnd, ?int $payrollId = null): void
    {

        $schedules = $this->scheduleModel

            ->select('employee_loan_schedule.*')

            ->join(
                'employee_loans',
                'employee_loans.id = employee_loan_schedule.loan_id'
            )

            ->where('employee_loans.employee_id', $employeeId)

            ->where('employee_loans.loan_status', 'active')

            ->where('employee_loan_schedule.schedule_status', 'pending')

            ->where(
                'employee_loan_schedule.due_date >=',
                $periodStart
            )

            ->where(
                'employee_loan_schedule.due_date <=',
                $periodEnd
            )

            ->orderBy(
                'employee_loan_schedule.installment_no',
                'ASC'
            )

            ->findAll();

        if (empty($schedules)) {
            return;
        }

        foreach ($schedules as $schedule) {

            /*
            |--------------------------------------------------------------------------
            | Mark Schedule Paid
            |--------------------------------------------------------------------------
            */

            $this->scheduleModel->update(
                $schedule['id'],
                [

                    'schedule_status' => 'paid',

                    'paid_at' => date('Y-m-d H:i:s'),

                    'payroll_id' => $payrollId,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Save Payment Record
            |--------------------------------------------------------------------------
            */

            $loan = $this->loanModel->find(
                $schedule['loan_id']
            );

            if (!$loan) {
                continue;
            }

            $this->paymentModel->insert([

                'loan_id' => $schedule['loan_id'],

                'schedule_id' => $schedule['id'],

                'employee_id' => $employeeId,

                'payroll_id' => $payrollId,

                'payment_amount' =>
                    $schedule['amount_due'],

                'payment_method' =>
                    'Payroll Deduction',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Update Remaining Balance
            |--------------------------------------------------------------------------
            */

            $newBalance = max(
                (
                    (float) $loan['remaining_balance']
                    - (float) $schedule['amount_due']
                ),
                0
            );

            /*
            |--------------------------------------------------------------------------
            | Close Loan If Fully Paid
            |--------------------------------------------------------------------------
            */

            $status =
                $newBalance <= 0
                ? 'completed'
                : 'active';

            $this->loanModel->update(
                $loan['id'],
                [

                    'remaining_balance' =>
                        round($newBalance, 2),

                    'loan_status' => $status,
                ]
            );
        }
    }

    public function getLoanPayments(int $loanId): array
    {
        return $this->paymentModel
            ->where('loan_id', $loanId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function updateLoan(int $loanId, array $data): bool
    {
        $loan = $this->loanModel->find($loanId);

        if (!$loan) {
            throw new \Exception('Loan not found.');
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent editing locked loans
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $loan['loan_status'],
                ['completed', 'cancelled']
            )
        ) {
            throw new \Exception(
                'Cannot edit completed/cancelled loan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Update Loan
        |--------------------------------------------------------------------------
        */

        return $this->loanModel->update(
            $loanId,
            [
                'start_date' =>
                    $data['start_date'],

                'remarks' =>
                    $data['remarks'] ?? null,

                'updated_by' =>
                    auth()->id(),
            ]
        );
    }

    public function cancelLoan(int $loanId): bool
    {
        $this->scheduleModel
            ->where('loan_id', $loanId)
            ->where('schedule_status', 'pending')
            ->set([
                'schedule_status' => 'cancelled'
            ])
            ->update();

       return $this->loanModel->update(
            $loanId,
            [
                'loan_status' => 'cancelled',

                'cancelled_at' => date('Y-m-d H:i:s'),

                'cancelled_by' => auth()->id(),
            ]
        );
    }

    public function completeLoan(int $loanId): bool
    {
        $this->scheduleModel
            ->where('loan_id', $loanId)
            ->where('schedule_status', 'pending')
            ->set([
                'schedule_status' => 'completed'
            ])
            ->update();

        return $this->loanModel->update(
            $loanId,
            [
                'loan_status' => 'completed',

                'remaining_balance' => 0,

                'completed_at' => date('Y-m-d H:i:s'),

                'completed_by' => auth()->id(),
            ]
        );
    }

    public function getEmployeeLoanBreakdown(int $employeeId, string $periodStart, string $periodEnd): array 
    {
        $schedules = $this->scheduleModel
            ->select('
                employee_loan_schedule.amount_due,
                employee_loans.loan_type
            ')
            ->join(
                'employee_loans',
                'employee_loans.id = employee_loan_schedule.loan_id'
            )
            ->where('employee_loans.employee_id', $employeeId)
            ->where('employee_loans.loan_status', 'active')
            ->where('employee_loan_schedule.schedule_status', 'pending')
            ->where('employee_loan_schedule.due_date >=', $periodStart)
            ->where('employee_loan_schedule.due_date <=', $periodEnd)
            ->findAll();

        $breakdown = [];

        foreach ($schedules as $row) {
            $loanName = trim($row['loan_type'] ?? 'Loan');
            $amount   = (float)($row['amount_due'] ?? 0);
            if ($amount <= 0) {
                continue;
            }
            
            $existingIndex = null;

            foreach ($breakdown as $index => $loan) {
                if (
                    strtolower($loan['loan_name']) === strtolower($loanName)
                ) {
                    $existingIndex = $index;
                    break;
                }
            }

            if ($existingIndex !== null) {
                $breakdown[$existingIndex]['amount'] += $amount;
            } else {
                $breakdown[] = [
                    'loan_name' => $loanName,
                    'amount' => $amount,
                ];
            }
        }

        return $breakdown;
    }
}