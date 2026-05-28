<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveSummaryFromNotifications extends Migration
{
    public function up()
    {
        // Drop `summary` column from announcements if it exists
        try {
            $fields = db_connect()->getFieldNames('announcements');
            if (in_array('summary', $fields, true)) {
                $this->forge->dropColumn('announcements', ['summary']);
            }
        } catch (\Exception $e) {
            // ignore if table missing or other DB error
        }

        // Drop `summary` column from request_notifications if it exists
        try {
            $fields = db_connect()->getFieldNames('request_notifications');
            if (in_array('summary', $fields, true)) {
                $this->forge->dropColumn('request_notifications', ['summary']);
            }
        } catch (\Exception $e) {
            // ignore if table missing or other DB error
        }
    }

    public function down()
    {
        // Re-create columns (best-effort) so this migration is reversible for fresh installs
        try {
            $fields = db_connect()->getFieldNames('announcements');
            if (! in_array('summary', $fields, true)) {
                $this->forge->addColumn('announcements', [
                    'summary' => [
                        'type' => 'VARCHAR',
                        'constraint' => 180,
                        'null' => true,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            // ignore
        }

        try {
            $fields = db_connect()->getFieldNames('request_notifications');
            if (! in_array('summary', $fields, true)) {
                $this->forge->addColumn('request_notifications', [
                    'summary' => [
                        'type' => 'VARCHAR',
                        'constraint' => 180,
                        'null' => true,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            // ignore
        }
    }
}
