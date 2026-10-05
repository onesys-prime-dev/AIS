<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOnlineStatusToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'online_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'Offline',
                'after'      => 'active',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'online_status');
    }
}
