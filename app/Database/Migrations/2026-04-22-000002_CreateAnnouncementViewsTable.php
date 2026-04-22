<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAnnouncementViewsTable extends Migration
{
    public function up()
    {
        $tableExists = $this->db->query("SHOW TABLES LIKE 'announcement_views'")->getResultArray();

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
            'announcement_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'viewed_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey('announcement_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey(['announcement_id', 'user_id'], false, true);
        $this->forge->createTable('announcement_views');
    }

    public function down()
    {
        $tableExists = $this->db->query("SHOW TABLES LIKE 'announcement_views'")->getResultArray();

        if (empty($tableExists)) {
            return;
        }

        $this->forge->dropTable('announcement_views', true);
    }
}
