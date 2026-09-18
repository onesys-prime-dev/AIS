<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Additional, admin-editable BIR de minimis categories — separate from the
 * existing 4 fixed fields (rice/uniform/laundry/medical) on employee_positions,
 * which stay untouched. Caps are stored here (not hardcoded) since BIR
 * revises these ceilings periodically; an amount entered above a category's
 * cap becomes taxable instead of silently staying non-taxable.
 */
class CreateDeMinimisCategories extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'category_key' => ['type' => 'VARCHAR', 'constraint' => 100],
            'label' => ['type' => 'VARCHAR', 'constraint' => 150],
            'cap_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'cap_period' => ['type' => 'ENUM', 'constraint' => ['monthly', 'annual', 'per_payroll'], 'default' => 'annual'],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('category_key');
        $this->forge->createTable('de_minimis_categories');
    }

    public function down()
    {
        $this->forge->dropTable('de_minimis_categories');
    }
}
