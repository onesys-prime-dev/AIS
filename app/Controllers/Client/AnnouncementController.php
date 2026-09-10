<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Services\Client\AnnouncementService;
use DateTime;

class AnnouncementController extends BaseController
{
    protected AnnouncementService $announcementService;

    public function __construct()
    {
        $this->announcementService = new AnnouncementService();
    }

    public function store()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->to('/login');
        }

        if (! \App\Libraries\PortalAccess::can('announcements', $user)) {
            return redirect()->back()->withInput()->with('error', 'Only payroll staff can publish announcements.');
        }

        $title = trim((string) $this->request->getPost('title'));
        $category = trim((string) $this->request->getPost('category'));
        $audience = trim((string) $this->request->getPost('audience'));
        $department = trim((string) $this->request->getPost('department'));
        $priority = trim((string) $this->request->getPost('priority'));
        $publishAtRaw = trim((string) $this->request->getPost('publish_at'));
        $expiresAtRaw = trim((string) $this->request->getPost('expires_at'));
        $details = trim((string) $this->request->getPost('details'));
        $requireAcknowledgement = (string) $this->request->getPost('require_acknowledgement') === '1';

        $allowedCategories = ['general', 'policy', 'event', 'maintenance', 'emergency'];
        $allowedAudiences = ['all', 'payroll', 'department'];
        $allowedPriorities = ['normal', 'high', 'urgent'];

        if ($title === '' || mb_strlen($title) > 120) {
            return redirect()->back()->withInput()->with('error', 'Title is required and must not exceed 120 characters.');
        }

        if (! in_array($category, $allowedCategories, true)) {
            return redirect()->back()->withInput()->with('error', 'Invalid announcement category.');
        }

        if (! in_array($audience, $allowedAudiences, true)) {
            return redirect()->back()->withInput()->with('error', 'Invalid announcement audience.');
        }

        if ($audience === 'department' && $department === '') {
            return redirect()->back()->withInput()->with('error', 'Department is required for department-only announcements.');
        }

        if (! in_array($priority, $allowedPriorities, true)) {
            return redirect()->back()->withInput()->with('error', 'Invalid announcement priority.');
        }

        $publishAtDate = DateTime::createFromFormat('Y-m-d\TH:i', $publishAtRaw);

        if (! $publishAtDate) {
            return redirect()->back()->withInput()->with('error', 'Publish date/time is required and must be valid.');
        }

        $expiresAt = '';
        if ($expiresAtRaw !== '') {
            $expiresAtDate = DateTime::createFromFormat('Y-m-d\TH:i', $expiresAtRaw);

            if (! $expiresAtDate) {
                return redirect()->back()->withInput()->with('error', 'Visible until date/time must be valid.');
            }

            if ($expiresAtDate < $publishAtDate) {
                return redirect()->back()->withInput()->with('error', 'Visible until date/time cannot be earlier than publish date/time.');
            }

            $expiresAt = $expiresAtDate->format('Y-m-d H:i:s');
        }


        if ($details === '') {
            return redirect()->back()->withInput()->with('error', 'Announcement details are required.');
        }

        $announcementId = $this->announcementService->createAnnouncement((int) $user->id, [
            'title' => $title,
            'category' => $category,
            'audience' => $audience,
            'department' => $department,
            'priority' => $priority,
            'publish_at' => $publishAtDate->format('Y-m-d H:i:s'),
            'expires_at' => $expiresAt,
            'details' => $details,
            'require_acknowledgement' => $requireAcknowledgement,
        ]);

        if (! $announcementId) {
            return redirect()->back()->withInput()->with('error', 'Failed to publish announcement. Please try again.');
        }

        // Handle attachments: validate, save files and collect metadata for DB
        $savedAttachments = [];
        $maxFiles = 6;
        $maxSizeBytes = 5 * 1024 * 1024; // 5MB per file
        $allowedMimePrefixes = ['image/', 'application/pdf'];
        $allowedExt = ['jpg','jpeg','png','gif','webp','pdf','doc','docx'];

        try {
            $files = $this->request->getFileMultiple('attachments');
            if (! empty($files)) {
                if (count($files) > $maxFiles) {
                    return redirect()->back()->withInput()->with('error', 'You may not upload more than ' . $maxFiles . ' files.');
                }

                // Pre-validate all files
                foreach ($files as $file) {
                    if (! $file) continue;
                    if (! $file->isValid()) {
                        return redirect()->back()->withInput()->with('error', 'One of the uploaded files is invalid.');
                    }

                    $size = (int) $file->getSize();
                    if ($size > $maxSizeBytes) {
                        return redirect()->back()->withInput()->with('error', 'Each file must be smaller than 5 MB.');
                    }

                    $mime = strtolower((string) $file->getClientMimeType());
                    $ext = strtolower((string) $file->getClientExtension());

                    $ok = false;
                    foreach ($allowedMimePrefixes as $p) {
                        if (strpos($mime, $p) === 0) { $ok = true; break; }
                    }
                    if (! $ok && in_array($ext, $allowedExt, true)) {
                        $ok = true;
                    }

                    if (! $ok) {
                        return redirect()->back()->withInput()->with('error', 'One or more files have unsupported type. Allowed: images, PDF, DOC/DOCX.');
                    }
                }

                // Save files
                $uploadPath = FCPATH . 'uploads/announcement_attachments/';
                if (! is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                foreach ($files as $file) {
                    if (! $file) continue;
                    if ($file->isValid() && ! $file->hasMoved()) {
                        $newName = $file->getRandomName();
                        $file->move($uploadPath, $newName);
                        $savedAttachments[] = [
                            'file_path' => 'uploads/announcement_attachments/' . $newName,
                            'original_name' => $file->getClientName(),
                            'mime_type' => $file->getClientMimeType(),
                            'file_size' => $file->getSize(),
                            'uploaded_by' => (int) $user->id,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Failed to process attachments.');
        }

        if (! empty($savedAttachments)) {
            $this->announcementService->saveAnnouncementAttachments($announcementId, $savedAttachments);
        }

        return redirect()->to(site_url('client_portal/dashboard'))->with('success', 'Announcement published successfully.');
    }

    public function viewed()
    {
        $user = auth()->user();

        if (! $user) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Unauthenticated.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $announcementId = (int) $this->request->getPost('announcement_id');

        if ($announcementId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Invalid announcement id.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $saved = $this->announcementService->markAnnouncementViewed($announcementId, (int) $user->id);

        if (! $saved) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Announcement not found or unable to mark as viewed.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function delete(int $id)
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->to('/login');
        }

        if (! \App\Libraries\PortalAccess::can('announcements', $user)) {
            return redirect()->back()->with('error', 'Only payroll staff can delete announcements.');
        }

        $id = (int) $id;
        if ($id <= 0) {
            return redirect()->back()->with('error', 'Invalid announcement id.');
        }

        $ok = $this->announcementService->deleteAnnouncement($id);

        if (! $ok) {
            return redirect()->back()->with('error', 'Failed to delete announcement.');
        }

        return redirect()->to(site_url('client_portal/dashboard'))->with('success', 'Announcement deleted.');
    }

    public function dismiss()
    {
        $user = auth()->user();

        if (! $user) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Unauthenticated.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $announcementId = (int) $this->request->getPost('announcement_id');
        $dismissAll = (string) $this->request->getPost('dismiss_all') === '1';

        if ($dismissAll) {
            $dismissed = $this->announcementService->dismissAllVisibleAnnouncementsForUser($user);

            return $this->response->setJSON([
                'success' => true,
                'dismissed' => $dismissed,
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($announcementId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Invalid announcement id.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $saved = $this->announcementService->dismissAnnouncementsForUser((int) $user->id, [$announcementId]);

        if ($saved <= 0) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Announcement not found or unable to dismiss.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'dismissed' => $saved,
            'csrfHash' => csrf_hash(),
        ]);
    }
}
