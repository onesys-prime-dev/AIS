<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmployeeGovernmentIds extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'employee_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'sss' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'tin' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'pagibig' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'philhealth' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('employee_government_ids');
    }

    public function down()
    {
        $this->forge->dropTable('employee_government_ids');
    }
}
