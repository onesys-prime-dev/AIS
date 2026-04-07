<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompanyBrandingTable extends Migration
{
    public function up()
    {
        $exists = $this->db->query("SHOW TABLES LIKE 'company_branding'")->getRowArray();
        if ($exists) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'company_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'logo_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'logo_original_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'logo_mime_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'logo_size' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'sidebar_color' => [
                'type' => 'VARCHAR',
                'constraint' => 7,
                'default' => '#1e1e1e',
            ],
            'header_color' => [
                'type' => 'VARCHAR',
                'constraint' => 7,
                'default' => '#ffffff',
            ],
            'background_color' => [
                'type' => 'VARCHAR',
                'constraint' => 7,
                'default' => '#f5f6fa',
            ],
            'sidebar_icon_mode' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'auto',
            ],
            'updated_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('company_id', 'uq_company_branding_company_id');
        $this->forge->createTable('company_branding', true);
    }

    public function down()
    {
        $this->forge->dropTable('company_branding', true);
    }
}
