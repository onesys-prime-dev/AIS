<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClientHolidays extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'client_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'holiday_date' => [
                'type' => 'DATE',
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['regular', 'special'],
                'default'    => 'regular',
            ],
            'is_recurring' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['client_id', 'holiday_date']);
        $this->forge->createTable('client_holidays', true);
    }

    public function down()
    {
        $this->forge->dropTable('client_holidays', true);
    }
}
