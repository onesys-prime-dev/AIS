<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReviewMessageToRequests extends Migration
{
    public function up()
    {
        if (! $this->requestsTableExists()) {
            return;
        }

        if (! $this->requestsFieldExists('review_message')) {
            $this->forge->addColumn('requests', [
                'review_message' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'approval_code',
                ],
            ]);
        }
    }

    public function down()
    {
        if (! $this->requestsTableExists()) {
            return;
        }

        if ($this->requestsFieldExists('review_message')) {
            $this->forge->dropColumn('requests', 'review_message');
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