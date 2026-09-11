<?php

namespace App\Services\Client;

use App\Services\Client\AnnouncementService;
use App\Services\Client\RequestNotificationService;

class NotificationService
{
    public function getVisibleNotificationsForUser($user, int $limit = 10): array
    {
        $announcementService = new AnnouncementService();
        $requestNotificationService = new RequestNotificationService();

        $announcementRows = $announcementService->getVisibleAnnouncementsForUser($user, $limit);
        $announcementAttachments = $announcementService->getAttachmentsForAnnouncementIds(array_column($announcementRows, 'id'));
        $requestNotificationRows = $requestNotificationService->getVisibleNotificationsForUser($user, $limit);

        $mapNotificationRow = static function (array $row, string $source, array $announcementAttachments = []): array {
            $authorPhoto = (string) ($row['author_photo'] ?? $row['author_photo_path'] ?? '');
            $requesterPhoto = (string) ($row['requester_photo_path'] ?? $row['recipient_photo_path'] ?? $row['requester_photo'] ?? '');

            if ($authorPhoto !== '' && ! preg_match('~^(?:[a-z][a-z0-9+.-]*:)?//~i', $authorPhoto)) {
                $authorPhoto = base_url($authorPhoto);
            }

            if ($requesterPhoto !== '' && ! preg_match('~^(?:[a-z][a-z0-9+.-]*:)?//~i', $requesterPhoto)) {
                $requesterPhoto = base_url($requesterPhoto);
            }

            $createdAt = (string) ($row['created_at'] ?? $row['publish_at'] ?? '');
            $publishedAt = (string) ($row['publish_at'] ?? $createdAt);

            $attachments = [];
            if (($row['id'] ?? 0) > 0 && isset($announcementAttachments[$row['id']])) {
                foreach ($announcementAttachments[$row['id']] as $att) {
                    // Attachments are stored outside the public webroot and
                    // served only through the authenticated download action,
                    // which re-checks this announcement's audience scope.
                    $att['file_path'] = base_url('client_portal/announcements/download/' . (int) ($att['id'] ?? 0));
                    $attachments[] = $att;
                }
            }

            return [
                'id' => (int) ($row['id'] ?? 0),
                'source' => $source,
                'title' => (string) ($row['title'] ?? 'Notification'),
                'details' => (string) ($row['details'] ?? ''),
                'publish_at' => $publishedAt,
                'created_at' => $createdAt,
                'category' => (string) ($row['category'] ?? ($source === 'request' ? ($row['request_type'] ?? 'request') : 'General')),
                'request_type' => (string) ($row['request_type'] ?? ''),
                'priority' => (string) ($row['priority'] ?? 'Normal'),
                'audience' => (string) ($row['audience'] ?? 'All Employees'),
                'time_ago' => (string) ($row['time_ago'] ?? ''),
                'is_viewed' => (int) ($row['is_viewed'] ?? 0),
                'author_name' => (string) ($row['author_name'] ?? $row['author_full_name'] ?? $row['author_username'] ?? 'System'),
                'author_photo' => $authorPhoto,
                'attachments' => $attachments,
                'status' => (string) ($row['status'] ?? ''),
                'request_id' => (int) ($row['request_id'] ?? ($row['id'] ?? 0)),
                'actor_name' => (string) ($row['author_name'] ?? $row['author_full_name'] ?? $row['author_username'] ?? 'System'),
                'actor_position' => (string) ($row['actor_position'] ?? ''),
                'review_message' => (string) ($row['review_message'] ?? ''),
                'requester_name' => (string) ($row['recipient_full_name'] ?? $row['requester_full_name'] ?? ''),
                'requester_department' => (string) ($row['recipient_department'] ?? $row['requester_department'] ?? ''),
                'requester_photo' => $requesterPhoto,
            ];
        };

        $notifications = array_merge(
            array_map(static fn (array $row) => $mapNotificationRow($row, 'announcement', $announcementAttachments), $announcementRows),
            array_map(static fn (array $row) => $mapNotificationRow($row, 'request', $announcementAttachments), $requestNotificationRows)
        );

        usort($notifications, static function (array $left, array $right): int {
            $leftTime = strtotime((string) ($left['created_at'] ?? $left['publish_at'] ?? '')) ?: 0;
            $rightTime = strtotime((string) ($right['created_at'] ?? $right['publish_at'] ?? '')) ?: 0;

            return $rightTime <=> $leftTime;
        });

        if ($limit > 0) {
            $notifications = array_slice($notifications, 0, $limit);
        }

        return $notifications;
    }

    public function getUnpublishedAnnouncementsForPayroll(int $userId): array
    {
        $announcementService = new AnnouncementService();
        $rows = $announcementService->getUnpublishedAnnouncementsForPayroll($userId);

        return array_map(static function (array $announcement): array {
            $isUrgent = in_array(($announcement['priority'] ?? 'normal'), ['high', 'urgent'], true);
            $authorName = trim((string) ($announcement['author_full_name'] ?? $announcement['author_username'] ?? ''));
            $authorPhotoPath = trim((string) ($announcement['author_photo_path'] ?? ''));

            return [
                'id' => (int) ($announcement['id'] ?? 0),
                'type' => $isUrgent ? 'error' : 'success',
                'title' => (string) ($announcement['title'] ?? 'Announcement'),
                'message' => (string) ($announcement['details'] ?? ''),
                'details' => (string) ($announcement['details'] ?? ''),
                'publish_at' => (string) ($announcement['publish_at'] ?? ''),
                'category' => (string) ($announcement['category'] ?? 'general'),
                'priority' => (string) ($announcement['priority'] ?? 'normal'),
                'audience' => (string) ($announcement['audience'] ?? 'all'),
                'is_viewed' => (int) ($announcement['is_viewed'] ?? 0),
                'author_name' => $authorName !== '' ? $authorName : 'System',
                'author_photo' => $authorPhotoPath !== '' ? base_url($authorPhotoPath) : '',
                'source' => 'announcement',
            ];
        }, $rows);
    }
}
