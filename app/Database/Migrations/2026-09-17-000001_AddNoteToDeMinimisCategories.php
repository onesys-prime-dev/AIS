<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNoteToDeMinimisCategories extends Migration
{
    public function up()
    {
        $this->forge->addColumn('de_minimis_categories', [
            'note' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'label',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('de_minimis_categories', 'note');
    }
}
