<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddClientToSavedTasks extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('client', 'saved_tasks')) {
            return;
        }

        $this->forge->addColumn('saved_tasks', [
            'client' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'task_performed',
            ],
        ]);
    }

    public function down()
    {
        if ($this->db->fieldExists('client', 'saved_tasks')) {
            $this->forge->dropColumn('saved_tasks', 'client');
        }
    }
}
