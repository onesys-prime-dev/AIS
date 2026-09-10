<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

final class CreateTeamAccessAssignments extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('team_access_assignments')) return;
        $this->forge->addField([
            'team_head_user_id'=>['type'=>'INT','unsigned'=>true],
            'employee_id'=>['type'=>'INT','unsigned'=>true],
            'assigned_by'=>['type'=>'INT','unsigned'=>true,'null'=>true],
            'created_at'=>['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey(['team_head_user_id','employee_id'], true);
        $this->forge->addForeignKey('team_head_user_id','users','id','CASCADE','CASCADE');
        $this->forge->addForeignKey('employee_id','employees','id','CASCADE','CASCADE');
        $this->forge->createTable('team_access_assignments', true, ['ENGINE'=>'InnoDB']);
    }
    public function down()
    {
        throw new \RuntimeException('Restore the access-control backup to roll back assignments without losing access history.');
    }
}
