<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDetailsToRequestNotifications extends Migration
{
    public function up()
    {
        $this->forge->addColumn('request_notifications', [
            'details' => [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
                'after'   => 'title',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('request_notifications', 'details');
    }
}
