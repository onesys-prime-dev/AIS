<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * employee_loan_payments.payroll_id now references employee_payslips.id —
 * the actual payroll record this payment was deducted against (sendPdf()
 * populates it at send time). payroll_transactions was never a reliable
 * reference here: it's written on every "Generate Payslip" preview click,
 * not tied to whether a payslip was ever sent.
 */
class AddPayrollIdForeignKeyToLoanPayments extends Migration
{
    public function up()
    {
        // Match employee_payslips.id's type (int unsigned) — InnoDB requires
        // matching column types for a foreign key.
        $this->forge->modifyColumn('employee_loan_payments', [
            'payroll_id' => [
                'type'     => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null'     => true,
            ],
        ]);

        $this->forge->addForeignKey(
            'payroll_id',
            'employee_payslips',
            'id',
            'CASCADE',
            'SET NULL',
            'fk_loan_payments_payroll'
        );

        $this->forge->processIndexes('employee_loan_payments');
    }

    public function down()
    {
        $this->db->query(
            'ALTER TABLE employee_loan_payments DROP FOREIGN KEY fk_loan_payments_payroll'
        );

        $this->forge->modifyColumn('employee_loan_payments', [
            'payroll_id' => [
                'type'     => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null'     => true,
            ],
        ]);
    }
}
