<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

final class AddAttendanceSessionState extends Migration
{
    public function up()
    {
        $fields = [
            'shift_date' => ['type' => 'DATE', 'null' => true],
            'schedule_snapshot' => ['type' => 'TEXT', 'null' => true],
            'break_log' => ['type' => 'TEXT', 'null' => true],
            'state_version' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ];
        foreach ($fields as $name => $definition) {
            if (!$this->db->fieldExists($name, 'attendance')) $this->forge->addColumn('attendance', [$name => $definition]);
        }
    }
    public function down()
    {
        throw new \RuntimeException('Preserve attendance snapshots and audit history; use the matched backup for rollback.');
    }
}
