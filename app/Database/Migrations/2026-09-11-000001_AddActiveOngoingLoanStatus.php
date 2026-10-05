<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds 'active_ongoing' to employee_loans.loan_status — set when a payslip
 * covering a loan's pending installment has been generated, locking the loan
 * from further edits until it's completed/cancelled.
 */
class AddActiveOngoingLoanStatus extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('employee_loans', [
            'loan_status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'active_ongoing', 'completed', 'cancelled'],
                'null'       => false,
                'default'    => 'active',
            ],
        ]);
    }

    public function down()
    {
        $this->db->query(
            "UPDATE employee_loans SET loan_status = 'active' WHERE loan_status = 'active_ongoing'"
        );

        $this->forge->modifyColumn('employee_loans', [
            'loan_status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'completed', 'cancelled'],
                'null'       => false,
                'default'    => 'active',
            ],
        ]);
    }
}
