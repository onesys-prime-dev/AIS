<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDepartmentTeamHeads extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'department_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'team_head_user_id'=> ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'assigned_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey(['department_id', 'team_head_user_id']);
        $this->forge->addForeignKey('department_id', 'departments', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('team_head_user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('department_team_heads', true);

        // team_access_assignments (the old per-employee picker) is fully replaced
        // by this department-scoped table and is no longer written to anywhere
        // in the app. It's left in place, unused, rather than dropped here.
    }

    public function down()
    {
        $this->forge->dropTable('department_team_heads', true);
    }
}
