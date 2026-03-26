<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateRequestsDateColumns extends Migration
{
    public function up()
    {
        if (! $this->requestsTableExists()) {
            return;
        }

        if ($this->requestsFieldExists('date') && ! $this->requestsFieldExists('start_date')) {
            $this->forge->modifyColumn('requests', [
                'date' => [
                    'name' => 'start_date',
                    'type' => 'DATE',
                    'null' => false,
                ],
            ]);
        }

        if (! $this->requestsFieldExists('end_date')) {
            $this->forge->addColumn('requests', [
                'end_date' => [
                    'type' => 'DATE',
                    'null' => true,
                    'after' => 'start_date',
                ],
            ]);
        }

        if ($this->requestsFieldExists('days') && ! $this->requestsFieldExists('duration')) {
            $this->forge->modifyColumn('requests', [
                'days' => [
                    'name' => 'duration',
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'default' => null,
                ],
            ]);
        }

        if (! $this->requestsFieldExists('duration')) {
            $this->forge->addColumn('requests', [
                'duration' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'default' => null,
                    'after' => 'end_date',
                ],
            ]);
        }

        if (! $this->requestsFieldExists('approval_code')) {
            $this->forge->addColumn('requests', [
                'approval_code' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                    'after' => 'duration',
                ],
            ]);
        }

        if ($this->requestsFieldExists('duration')) {
            $this->db->query("UPDATE requests\n SET duration = CASE\n WHEN start_date IS NULL THEN 1\n WHEN end_date IS NULL OR end_date < start_date THEN 1\n ELSE DATEDIFF(end_date, start_date) + 1\n END\n WHERE duration IS NULL OR duration < 1");

            $this->forge->modifyColumn('requests', [
                'duration' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                    'default' => 1,
                ],
            ]);
        }
    }

    public function down()
    {
        if (! $this->requestsTableExists()) {
            return;
        }

        if ($this->requestsFieldExists('approval_code')) {
            $this->forge->dropColumn('requests', 'approval_code');
        }

        if ($this->requestsFieldExists('duration')) {
            $this->forge->dropColumn('requests', 'duration');
        }

        if ($this->requestsFieldExists('end_date')) {
            $this->forge->dropColumn('requests', 'end_date');
        }

        if ($this->requestsFieldExists('start_date') && ! $this->requestsFieldExists('date')) {
            $this->forge->modifyColumn('requests', [
                'start_date' => [
                    'name' => 'date',
                    'type' => 'DATE',
                    'null' => false,
                ],
            ]);
        }
    }

    private function requestsTableExists(): bool
    {
        $result = $this->db->query("SHOW TABLES LIKE 'requests'")->getResultArray();

        return ! empty($result);
    }

    private function requestsFieldExists(string $fieldName): bool
    {
        $result = $this->db->query('SHOW COLUMNS FROM requests LIKE ?', [$fieldName])->getResultArray();

        return ! empty($result);
    }
}
