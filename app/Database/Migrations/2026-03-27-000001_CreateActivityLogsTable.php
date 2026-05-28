<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateActivityLogsTable extends Migration
{
    public function up(): void
    {
        $tableExists = $this->db->query("SHOW TABLES LIKE 'activity_logs'")->getResultArray();

        if (! empty($tableExists)) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            // Denormalized so logs remain readable even after username changes
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'action' => [
                'type'       => 'ENUM',
                'constraint' => ['created', 'updated', 'deleted'],
            ],
            // Corresponds to the affected table name (e.g. 'attendance', 'requests')
            'module' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            // The primary key of the affected row in the module's table
            'record_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'default'    => null,
            ],
            // Human-readable summary of what happened
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            // Populated for 'updated' and 'deleted' actions
            'old_values' => [
                'type'    => 'JSON',
                'null'    => true,
                'default' => null,
            ],
            // Populated for 'created' and 'updated' actions
            'new_values' => [
                'type'    => 'JSON',
                'null'    => true,
                'default' => null,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45, // supports both IPv4 and IPv6
                'null'       => true,
                'default'    => null,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => false,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('module');
        // Composite index for looking up all logs for a specific record in a module
        $this->forge->addKey(['module', 'record_id']);
        $this->forge->addKey('created_at');

        $this->forge->createTable('activity_logs');
    }

    public function down(): void
    {
        $tableExists = $this->db->query("SHOW TABLES LIKE 'activity_logs'")->getResultArray();

        if (empty($tableExists)) {
            return;
        }

        $this->forge->dropTable('activity_logs', true);
    }
}
