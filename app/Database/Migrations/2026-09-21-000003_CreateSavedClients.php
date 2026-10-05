<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSavedClients extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'client'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'saved_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'client']);
        $this->forge->createTable('saved_clients', true);

        // Seed each user's clients from what they've already logged.
        $this->db->query("
            INSERT INTO saved_clients (user_id, client, saved_at)
            SELECT t.user_id, t.client, NOW() FROM tasks t
            WHERE t.client IS NOT NULL AND t.client <> ''
            GROUP BY t.user_id, t.client
        ");

        // Clients are their own preset list now, not bundled onto task presets.
        if ($this->db->fieldExists('client', 'saved_tasks')) {
            $this->forge->dropColumn('saved_tasks', 'client');
        }
    }

    public function down()
    {
        $this->forge->dropTable('saved_clients', true);
    }
}
