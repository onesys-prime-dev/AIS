<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddClientToTasks extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tasks', [
            'client' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'task_performed',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tasks', 'client');
    }
}
