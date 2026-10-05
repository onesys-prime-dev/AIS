<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDepartments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('departments', true);

        // Backfill: one department row per distinct free-text value already on
        // employee_positions, then point every employee at the matching row.
        $this->forge->addColumn('employee_positions', [
            'department_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'department'],
        ]);

        $names = $this->db->query(
            "SELECT DISTINCT TRIM(department) AS name FROM employee_positions WHERE TRIM(COALESCE(department, '')) <> ''"
        )->getResultArray();

        $now = date('Y-m-d H:i:s');
        foreach ($names as $row) {
            $this->db->table('departments')->insert(['name' => $row['name'], 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
            $id = $this->db->insertID();
            $this->db->query('UPDATE employee_positions SET department_id = ? WHERE TRIM(department) = ?', [$id, $row['name']]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('employee_positions', 'department_id');
        $this->forge->dropTable('departments', true);
    }
}
