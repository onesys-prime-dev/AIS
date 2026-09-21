<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BackfillSavedTaskClients extends Migration
{
    public function up()
    {
        // Existing presets have no client; borrow it from the user's most recent
        // logged task with the same description.
        $this->db->query("
            UPDATE saved_tasks st
            SET st.client = (
                SELECT t.client FROM tasks t
                WHERE t.user_id = st.user_id
                  AND t.task_performed = st.task_performed
                  AND t.client IS NOT NULL AND t.client <> ''
                ORDER BY t.id DESC LIMIT 1
            )
            WHERE st.client IS NULL
        ");
    }

    public function down()
    {
    }
}
