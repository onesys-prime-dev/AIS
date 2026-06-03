<?php
// app/Database/Migrations/2026-06-03-000001_AddEffectiveYearToBrackets.php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEffectiveYearToBrackets extends Migration
{
    public function up(): void
    {
        // ── sss_brackets ────────────────────────────────────────────────────
        // 1. Add the two new columns
        $this->forge->addColumn('sss_brackets', [
            'effective_year' => [
                'type'       => 'SMALLINT',
                'unsigned'   => true,
                'null'       => false,
                'default'    => 2025,
                'after'      => 'id',
            ],
            'is_active' => [
                'type'    => 'TINYINT',
                'null'    => false,
                'default' => 0,
                'after'   => 'effective_year',
            ],
        ]);

        // 2. The existing rows are the "2025" table — mark them active
        $this->db->query("UPDATE sss_brackets SET effective_year = 2025, is_active = 1");

        // 3. Add an index
        $this->forge->addKey('effective_year', false, false, 'sss_brackets');
        // (CI4's forge doesn't do ALTER ADD INDEX in addColumn — use raw SQL)
        $this->db->query("ALTER TABLE sss_brackets ADD INDEX idx_sss_year (effective_year)");

        // ── wtax_brackets_monthly ────────────────────────────────────────────
        $this->forge->addColumn('wtax_brackets_monthly', [
            'effective_year' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'null'     => false,
                'default'  => 2025,
                'after'    => 'id',
            ],
            'is_active' => [
                'type'    => 'TINYINT',
                'null'    => false,
                'default' => 0,
                'after'   => 'effective_year',
            ],
        ]);

        // Deduplicate: the current table has rows 1-6 and 7-12 (identical).
        // Keep only the lower-id set and assign year 2025.
        $this->db->query("DELETE FROM wtax_brackets_monthly WHERE id > 6");
        $this->db->query("UPDATE wtax_brackets_monthly SET effective_year = 2025, is_active = 1");
        $this->db->query("ALTER TABLE wtax_brackets_monthly ADD INDEX idx_wtax_year (effective_year)");
    }

    public function down(): void
    {
        $this->forge->dropColumn('sss_brackets', ['effective_year', 'is_active']);
        $this->forge->dropColumn('wtax_brackets_monthly', ['effective_year', 'is_active']);
    }
}
