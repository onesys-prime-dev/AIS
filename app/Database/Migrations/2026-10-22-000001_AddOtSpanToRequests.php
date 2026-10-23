<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Overtime requests can now carry the time span they cover (e.g. 18:00 to 20:00).
 * Only time worked inside an approved span counts as approved OT; the rest of the
 * extra time stays unapproved. Both columns are optional: every request filed before
 * this, and any OT request without a span, keeps covering the whole day exactly as before.
 */
class AddOtSpanToRequests extends Migration
{
    public function up()
    {
        // Safe to re-run: skip columns that were already added by hand on a server
        // whose schema pre-dates migration tracking.
        $fields = [];
        if (! $this->db->fieldExists('ot_start_time', 'requests')) {
            $fields['ot_start_time'] = ['type' => 'TIME', 'null' => true, 'after' => 'end_date'];
        }
        if (! $this->db->fieldExists('ot_end_time', 'requests')) {
            $fields['ot_end_time'] = ['type' => 'TIME', 'null' => true, 'after' => 'ot_start_time'];
        }
        if ($fields) {
            $this->forge->addColumn('requests', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('requests', ['ot_start_time', 'ot_end_time']);
    }
}
