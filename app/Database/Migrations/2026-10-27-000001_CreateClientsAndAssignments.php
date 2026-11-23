<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Clients become an admin-managed list instead of free text on every task.
 *
 * - clients:            the master list (deactivated, never deleted, once used).
 * - client_departments: a client is available to everyone in these departments.
 * - client_employees:   ...and/or to these individual employees (users.id), e.g. a
 *                       client that only two people from different departments work on.
 *   An employee can pick a client when their department OR they personally are assigned.
 * - tasks.client_id:    the chosen client. The old tasks.client text stays as a snapshot so
 *                       past exports and timesheets keep working unchanged.
 *
 * Every distinct client name already typed on a task is imported as a client with nobody
 * assigned yet (an admin assigns them). "Internal / no client" is created and opened to every
 * active department so logging a task is never blocked on rollout day.
 *
 * Safe to re-run: each step skips what already exists (these servers had hand-built schemas).
 */
class CreateClientsAndAssignments extends Migration
{
    public const INTERNAL = 'Internal / no client';

    public function up()
    {
        if (! $this->db->tableExists('clients')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'name'       => ['type' => 'VARCHAR', 'constraint' => 190],
                'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('name');
            $this->forge->createTable('clients', true);
        }

        if (! $this->db->tableExists('client_departments')) {
            $this->forge->addField([
                'client_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'department_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            ]);
            $this->forge->addKey(['client_id', 'department_id'], true);
            $this->forge->addKey('department_id');
            $this->forge->createTable('client_departments', true);
        }

        if (! $this->db->tableExists('client_employees')) {
            $this->forge->addField([
                'client_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'user_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            ]);
            $this->forge->addKey(['client_id', 'user_id'], true);
            $this->forge->addKey('user_id');
            $this->forge->createTable('client_employees', true);
        }

        if (! $this->db->fieldExists('client_id', 'tasks')) {
            $this->forge->addColumn('tasks', [
                'client_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'client'],
            ]);
            $this->db->query('ALTER TABLE `tasks` ADD INDEX `idx_tasks_client_user` (`client_id`, `user_id`)');
        }

        $now = date('Y-m-d H:i:s');

        // Import the client names people already typed (case/whitespace-insensitive: first spelling wins).
        $seen = [];
        foreach ($this->db->query("SELECT client FROM tasks WHERE client IS NOT NULL AND TRIM(client) <> '' GROUP BY client ORDER BY MIN(id)")->getResultArray() as $row) {
            $name = trim($row['client']);
            $key  = mb_strtolower($name);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            if (! $this->db->table('clients')->where('name', $name)->countAllResults()) {
                $this->db->table('clients')->insert(['name' => $name, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        if (! $this->db->table('clients')->where('name', self::INTERNAL)->countAllResults()) {
            $this->db->table('clients')->insert(['name' => self::INTERNAL, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
        }
        $internalId = (int) $this->db->table('clients')->where('name', self::INTERNAL)->get()->getRow()->id;
        foreach ($this->db->table('departments')->select('id')->where('is_active', 1)->where('deleted_at', null)->get()->getResultArray() as $d) {
            $this->db->query('INSERT IGNORE INTO client_departments (client_id, department_id) VALUES (?, ?)', [$internalId, (int) $d['id']]);
        }

        // Link existing tasks to the imported clients (same case-insensitive match). The collation is stated
        // on both sides because these servers mix utf8mb4_general_ci and utf8mb4_unicode_ci tables, and
        // MySQL refuses to compare columns of different collations ("Illegal mix of collations").
        $this->db->query("UPDATE tasks t JOIN clients c ON LOWER(TRIM(t.client)) COLLATE utf8mb4_general_ci = LOWER(c.name) COLLATE utf8mb4_general_ci SET t.client_id = c.id WHERE t.client_id IS NULL AND t.client IS NOT NULL AND TRIM(t.client) <> ''");
    }

    public function down()
    {
        if ($this->db->fieldExists('client_id', 'tasks')) {
            $this->forge->dropColumn('tasks', 'client_id');
        }
        $this->forge->dropTable('client_employees', true);
        $this->forge->dropTable('client_departments', true);
        $this->forge->dropTable('clients', true);
    }
}
