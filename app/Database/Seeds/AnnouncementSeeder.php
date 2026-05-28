<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run()
    {
        $db = db_connect();

        // create a sample announcement
        $now = date('Y-m-d H:i:s');
        $data = [
            'title' => 'Sample Announcement (Seed)',
            'category' => 'general',
            'audience' => 'all',
            'department' => null,
            'priority' => 'normal',
            'publish_at' => $now,
            'expires_at' => null,
            'details' => 'Details: seeded announcement. Replace or remove as needed.',
            'require_acknowledgement' => 0,
            'created_by' => 1,
            'created_at' => $now,
        ];

        $db->table('announcements')->insert($data);
        $id = (int) $db->insertID();

        if ($id > 0) {
            // add a placeholder attachment record (ensure the path exists if you want to click it)
            $att = [
                'announcement_id' => $id,
                'file_path' => 'uploads/announcement_attachments/placeholder.txt',
                'original_name' => 'placeholder.txt',
                'mime_type' => 'text/plain',
                'file_size' => 0,
                'uploaded_by' => 1,
                'created_at' => $now,
            ];

            $db->table('announcement_attachments')->insert($att);
        }
    }
}
