<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHelpRequestsTable extends Migration
{
    public function up()
    {
        $tableExists = $this->db->query("SHOW TABLES LIKE 'help_requests'")->getResultArray();

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
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
            ],
            'priority' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'normal',
            ],
            'subject' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
            ],
            'message' => [
                'type' => 'TEXT',
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'open',
            ],
            'response' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'responded_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'responded_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('status');
        $this->forge->addKey('created_at');
        $this->forge->createTable('help_requests');
    }

    public function down()
    {
        $tableExists = $this->db->query("SHOW TABLES LIKE 'help_requests'")->getResultArray();

        if (empty($tableExists)) {
            return;
        }

        $this->forge->dropTable('help_requests', true);
    }
}