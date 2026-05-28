<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRequestNotificationsTable extends Migration
{
    public function up()
    {
        $tableExists = $this->db->query("SHOW TABLES LIKE 'request_notifications'")->getResultArray();

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
            'request_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'recipient_user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'actor_user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
            ],
            'details' => [
                'type' => 'TEXT',
            ],
            'viewed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'dismissed_at' => [
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
        $this->forge->addKey('request_id');
        $this->forge->addKey('recipient_user_id');
        $this->forge->addKey('actor_user_id');
        $this->forge->addKey('status');
        $this->forge->addKey('viewed_at');
        $this->forge->addKey('dismissed_at');
        $this->forge->createTable('request_notifications');
    }

    public function down()
    {
        $tableExists = $this->db->query("SHOW TABLES LIKE 'request_notifications'")->getResultArray();

        if (empty($tableExists)) {
            return;
        }

        $this->forge->dropTable('request_notifications', true);
    }
}
