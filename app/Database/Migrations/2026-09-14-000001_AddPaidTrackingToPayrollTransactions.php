<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds paid_by/paid_at audit columns to payroll_transactions so the existing
 * 'paid' status enum value (present in the schema but never written to) can
 * be set traceably via PayrollController::markTransactionPaid().
 */
class AddPaidTrackingToPayrollTransactions extends Migration
{
    public function up()
    {
        $this->forge->addColumn('payroll_transactions', [
            'paid_by' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'computed_at',
            ],
            'paid_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'after'      => 'paid_by',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('payroll_transactions', ['paid_by', 'paid_at']);
    }
}
