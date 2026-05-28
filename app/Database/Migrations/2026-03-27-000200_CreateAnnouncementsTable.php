<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAnnouncementsTable extends Migration
{
    public function up()
    {
        $tableExists = $this->db->query("SHOW TABLES LIKE 'announcements'")->getResultArray();

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
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
            ],
            'category' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
            ],
            'audience' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
            ],
            'department' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'priority' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'normal',
            ],
            'publish_at' => [
                'type' => 'DATETIME',
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'details' => [
                'type' => 'TEXT',
            ],
            'require_acknowledgement' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
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
        $this->forge->addKey('audience');
        $this->forge->addKey('publish_at');
        $this->forge->addKey('expires_at');
        $this->forge->addKey('created_by');
        $this->forge->createTable('announcements');
    }

    public function down()
    {
        $tableExists = $this->db->query("SHOW TABLES LIKE 'announcements'")->getResultArray();

        if (empty($tableExists)) {
            return;
        }

        $this->forge->dropTable('announcements', true);
    }
}
