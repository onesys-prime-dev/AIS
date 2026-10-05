<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusToRequestNotifications extends Migration
{
    public function up()
    {
        $this->forge->addColumn('request_notifications', [
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
                'after'      => 'type',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('request_notifications', 'status');
    }
}
