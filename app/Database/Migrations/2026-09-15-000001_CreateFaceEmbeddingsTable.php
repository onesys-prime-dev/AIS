<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Stores face embeddings for the in-house face recognition service
 * (replaces the external CompreFace integration — see app/Services/FaceRecognitionService.php).
 * Up to FaceRecognition::$maxEmbeddingsPerUser rows per user, oldest evicted on new enrollment.
 */
class CreateFaceEmbeddingsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
            ],
            'embedding' => [
                'type' => 'MEDIUMTEXT',
                'null' => false,
            ],
            'model_version' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'quality_score' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('face_embeddings');
    }

    public function down()
    {
        $this->forge->dropTable('face_embeddings');
    }
}
