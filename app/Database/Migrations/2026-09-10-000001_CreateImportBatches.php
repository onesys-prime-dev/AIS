<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

/**
 * A per-batch record of every file import — timesheet or employee — so
 * "what got imported, when, by whom, and with what result" is a real,
 * browsable answer instead of free-text search through the generic
 * activity log. Neither importer previously recorded the original
 * filename anywhere at all.
 */
final class CreateImportBatches extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('import_batches')) return;

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'type'          => ['type' => 'VARCHAR', 'constraint' => 20], // 'timesheet' | 'employees'
            'filename'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'file_hash'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'imported_by'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'total_rows'    => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'ready_count'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'duplicate_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'error_count'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'other_count'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0], // pending/out-of-period/etc.
            'summary'       => ['type' => 'TEXT', 'null' => true], // JSON: free-form per-type detail
            'created_at'    => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('type');
        $this->forge->addKey('created_at');
        $this->forge->createTable('import_batches');
    }

    public function down()
    {
        $this->forge->dropTable('import_batches', true);
    }
}
