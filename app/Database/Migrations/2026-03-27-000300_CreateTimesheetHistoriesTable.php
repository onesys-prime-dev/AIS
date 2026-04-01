<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTimesheetHistoriesTable extends Migration
{
    public function up()
    {
        $tableExists = $this->db->query("SHOW TABLES LIKE 'timesheet_histories'")->getResultArray();

        if (! empty($tableExists)) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'period_start' => [
                'type' => 'DATE',
            ],
            'period_end' => [
                'type' => 'DATE',
            ],
            'total_minutes' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'total_hours' => [
                'type' => 'DECIMAL',
                'constraint' => '8,2',
                'default' => 0,
            ],
            'entries_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'days_covered' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'compiled_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey(['user_id', 'period_start', 'period_end']);
        $this->forge->createTable('timesheet_histories');
    }

    public function down()
    {
        $tableExists = $this->db->query("SHOW TABLES LIKE 'timesheet_histories'")->getResultArray();

        if (empty($tableExists)) {
            return;
        }

        $this->forge->dropTable('timesheet_histories', true);
    }
}
