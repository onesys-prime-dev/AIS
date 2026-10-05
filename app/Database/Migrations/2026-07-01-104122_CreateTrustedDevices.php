<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTrustedDevices extends Migration
{
    public function up()
    {
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
            'token' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'device_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'last_used_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
            ],
            'created_at DATETIME NULL',
            'updated_at DATETIME NULL',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('token');

        $this->forge->createTable('trusted_devices');
    }

    public function down()
    {
        $this->forge->dropTable('trusted_devices');
    }
}