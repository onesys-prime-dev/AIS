<?php

if (! function_exists('notification_data')) {
    function notification_data(array $notification): array
    {
        return [
            'id' => (int) ($notification['id'] ?? 0),
            'title' => (string) ($notification['title'] ?? 'Notification'),
            'details' => (string) (($notification['details'] ?? '') !== '' ? $notification['details'] : ($notification['message'] ?? '')),
            'published_at' => (string) ($notification['publish_at'] ?? ''),
            'category' => (string) ($notification['category'] ?? 'General'),
            'priority' => (string) ($notification['priority'] ?? 'Normal'),
            'audience' => (string) ($notification['audience'] ?? 'All Employees'),
            'status' => (string) ($notification['status'] ?? ''),
            'source' => (string) ($notification['source'] ?? 'announcement'),
            'created_by' => (int) ($notification['created_by'] ?? 0),
            'request_id' => (int) ($notification['request_id'] ?? 0),
            'actor_name' => (string) ($notification['actor_name'] ?? $notification['author_name'] ?? 'System'),
            'actor_position' => (string) ($notification['actor_position'] ?? ''),
            'author_name' => (string) ($notification['author_name'] ?? 'System'),
            'author_photo' => (string) ($notification['author_photo'] ?? ''),
            'attachments' => $notification['attachments'] ?? [],
            'requester_name' => (string) ($notification['requester_name'] ?? ''),
            'requester_department' => (string) ($notification['requester_department'] ?? ''),
            'requester_photo' => (string) ($notification['requester_photo'] ?? ''),
            'request_type' => (string) ($notification['request_type'] ?? $notification['category'] ?? ''),
        ];
    }
}
