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
            $files = $this->request->getFileMultiple('attachments') ?? [];
            // A file input with no file actually chosen still submits an
            // "empty" UploadedFile part (error === UPLOAD_ERR_NO_FILE) rather
            // than an empty array — drop those before validating, otherwise
            // saving without adding new attachments is rejected as invalid.
            $files = array_filter($files, static fn ($file) => $file && $file->getError() !== UPLOAD_ERR_NO_FILE);

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

                // Save files outside the public webroot — attachments are only
                // reachable through the authenticated download() action below,
                // which re-checks the announcement's audience/department scope.
                $uploadPath = WRITEPATH . 'uploads/announcement_attachments/';
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

    /**
     * Return an announcement's editable fields + attachments as JSON, for
     * prefilling the edit form (used when "Edit announcement" is triggered
     * from a page that doesn't already have the announcement data inline).
     */
    public function edit(int $id)
    {
        $user = auth()->user();

        if (! $user) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated.']);
        }

        if (! \App\Libraries\PortalAccess::can('announcements', $user)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Only payroll staff can edit announcements.']);
        }

        $announcement = $this->announcementService->getAnnouncementById($id);
        if (! $announcement) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Announcement not found.']);
        }

        $attachments = $this->announcementService->getAttachmentsForAnnouncementIds([$id])[$id] ?? [];
        $attachmentsOut = array_map(static function (array $att): array {
            return [
                'id' => (int) ($att['id'] ?? 0),
                'name' => (string) ($att['original_name'] ?? basename((string) ($att['file_path'] ?? ''))),
                'url' => base_url('client_portal/announcements/download/' . (int) ($att['id'] ?? 0)),
                'size' => (int) ($att['file_size'] ?? 0),
                'mime' => (string) ($att['mime_type'] ?? ''),
            ];
        }, $attachments);

        $publishAt = (string) ($announcement['publish_at'] ?? '');
        $expiresAt = (string) ($announcement['expires_at'] ?? '');

        return $this->response->setJSON([
            'success' => true,
            'announcement' => [
                'id' => (int) $announcement['id'],
                'title' => (string) $announcement['title'],
                'category' => (string) $announcement['category'],
                'audience' => (string) $announcement['audience'],
                'department' => (string) ($announcement['department'] ?? ''),
                'priority' => (string) $announcement['priority'],
                'publish_at' => $publishAt !== '' ? date('Y-m-d\TH:i', strtotime($publishAt)) : '',
                'expires_at' => $expiresAt !== '' ? date('Y-m-d\TH:i', strtotime($expiresAt)) : '',
                'details' => (string) $announcement['details'],
                'require_acknowledgement' => (bool) ($announcement['require_acknowledgement'] ?? false),
            ],
            'attachments' => $attachmentsOut,
        ]);
    }

    public function update(int $id)
    {
        $isAjax = $this->request->isAJAX();

        // Returns an AJAX-aware failure response: JSON for fetch() callers,
        // a redirect-back-with-flash for a plain form submission.
        $fail = function (string $message, int $statusCode = 422) use ($isAjax) {
            if ($isAjax) {
                return $this->response->setStatusCode($statusCode)->setJSON(['success' => false, 'message' => $message]);
            }
            return redirect()->back()->withInput()->with('error', $message);
        };

        $user = auth()->user();

        if (! $user) {
            return $isAjax
                ? $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated.'])
                : redirect()->to('/login');
        }

        if (! \App\Libraries\PortalAccess::can('announcements', $user)) {
            return $fail('Only payroll staff can edit announcements.', 403);
        }

        $existing = $this->announcementService->getAnnouncementById($id);
        if (! $existing) {
            return $fail('Announcement not found.', 404);
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
            return $fail('Title is required and must not exceed 120 characters.');
        }

        if (! in_array($category, $allowedCategories, true)) {
            return $fail('Invalid announcement category.');
        }

        if (! in_array($audience, $allowedAudiences, true)) {
            return $fail('Invalid announcement audience.');
        }

        if ($audience === 'department' && $department === '') {
            return $fail('Department is required for department-only announcements.');
        }

        if (! in_array($priority, $allowedPriorities, true)) {
            return $fail('Invalid announcement priority.');
        }

        $publishAtDate = DateTime::createFromFormat('Y-m-d\TH:i', $publishAtRaw);

        if (! $publishAtDate) {
            return $fail('Publish date/time is required and must be valid.');
        }

        $expiresAt = '';
        if ($expiresAtRaw !== '') {
            $expiresAtDate = DateTime::createFromFormat('Y-m-d\TH:i', $expiresAtRaw);

            if (! $expiresAtDate) {
                return $fail('Visible until date/time must be valid.');
            }

            if ($expiresAtDate < $publishAtDate) {
                return $fail('Visible until date/time cannot be earlier than publish date/time.');
            }

            $expiresAt = $expiresAtDate->format('Y-m-d H:i:s');
        }

        if ($details === '') {
            return $fail('Announcement details are required.');
        }

        $ok = $this->announcementService->updateAnnouncement($id, [
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

        if (! $ok) {
            return $fail('Failed to update announcement. Please try again.', 500);
        }

        // New attachments are appended; existing ones are left untouched.
        $savedAttachments = [];
        $maxFiles = 6;
        $maxSizeBytes = 5 * 1024 * 1024;
        $allowedMimePrefixes = ['image/', 'application/pdf'];
        $allowedExt = ['jpg','jpeg','png','gif','webp','pdf','doc','docx'];

        try {
            $files = $this->request->getFileMultiple('attachments') ?? [];
            // A file input with no file actually chosen still submits an
            // "empty" UploadedFile part (error === UPLOAD_ERR_NO_FILE) rather
            // than an empty array — drop those before validating, otherwise
            // saving without adding new attachments is rejected as invalid.
            $files = array_filter($files, static fn ($file) => $file && $file->getError() !== UPLOAD_ERR_NO_FILE);

            if (! empty($files)) {
                if (count($files) > $maxFiles) {
                    return $fail('You may not upload more than ' . $maxFiles . ' files.');
                }

                foreach ($files as $file) {
                    if (! $file) continue;
                    if (! $file->isValid()) {
                        return $fail('One of the uploaded files is invalid.');
                    }

                    $size = (int) $file->getSize();
                    if ($size > $maxSizeBytes) {
                        return $fail('Each file must be smaller than 5 MB.');
                    }

                    $mime = strtolower((string) $file->getClientMimeType());
                    $ext = strtolower((string) $file->getClientExtension());

                    $ok2 = false;
                    foreach ($allowedMimePrefixes as $p) {
                        if (strpos($mime, $p) === 0) { $ok2 = true; break; }
                    }
                    if (! $ok2 && in_array($ext, $allowedExt, true)) {
                        $ok2 = true;
                    }

                    if (! $ok2) {
                        return $fail('One or more files have unsupported type. Allowed: images, PDF, DOC/DOCX.');
                    }
                }

                $uploadPath = WRITEPATH . 'uploads/announcement_attachments/';
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
            return $fail('Failed to process attachments.', 500);
        }

        if (! empty($savedAttachments)) {
            $this->announcementService->saveAnnouncementAttachments($id, $savedAttachments);
        }

        if ($isAjax) {
            // The bell dropdown's notification data lives in HTML attributes
            // rendered once at page load, and this save doesn't reload the
            // page — hand back the fresh row so the caller can patch that
            // notification's dataset in place (attachments included).
            $freshAttachments = $this->announcementService->getAttachmentsForAnnouncementIds([$id])[$id] ?? [];
            $attachmentsForClient = array_map(static function (array $att): array {
                return [
                    'file_path' => base_url('client_portal/announcements/download/' . (int) ($att['id'] ?? 0)),
                    'original_name' => (string) ($att['original_name'] ?? ''),
                    'mime_type' => (string) ($att['mime_type'] ?? ''),
                    'file_size' => (int) ($att['file_size'] ?? 0),
                ];
            }, $freshAttachments);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Announcement updated successfully.',
                'notification' => [
                    'id' => $id,
                    'title' => $title,
                    'details' => $details,
                    'category' => $category,
                    'priority' => ucfirst($priority),
                    'audience' => ucfirst($audience),
                    'publish' => date('M d, Y h:i A', $publishAtDate->getTimestamp()),
                    'attachments' => $attachmentsForClient,
                ],
            ]);
        }

        return redirect()->to(site_url('client_portal/dashboard'))->with('success', 'Announcement updated successfully.');
    }

    /**
     * Remove a single existing attachment (file on disk + DB row) from an
     * announcement, independent of the main edit-and-save flow — used by
     * the "remove" button on an existing attachment in the edit form.
     */
    public function deleteAttachment(int $attachmentId)
    {
        $user = auth()->user();

        if (! $user) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Unauthenticated.']);
        }

        if (! \App\Libraries\PortalAccess::can('announcements', $user)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Only payroll staff can edit announcements.']);
        }

        $attachment = $this->announcementService->getAttachmentById($attachmentId);
        if (! $attachment) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Attachment not found.']);
        }

        $announcementId = (int) ($attachment['announcement_id'] ?? 0);

        if (! $this->announcementService->deleteAttachment($attachmentId)) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Failed to remove attachment.']);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Attachment removed.',
            'announcement_id' => $announcementId,
            'attachment_id' => $attachmentId,
        ]);
    }

    /**
     * Serve an announcement attachment. Requires login (route-level 'auth'
     * filter) and re-checks the parent announcement's audience/department
     * scope here — attachments are stored outside the public webroot
     * specifically so this check can't be bypassed by requesting the file
     * directly.
     */
    public function download(int $attachmentId)
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->to('/login');
        }

        $attachment = $this->announcementService->getAttachmentById($attachmentId);
        if (! $attachment) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (! $this->announcementService->canUserAccessAnnouncement((int) $attachment['announcement_id'], $user)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $relativePath = ltrim((string) $attachment['file_path'], '/');
        $fullPath = WRITEPATH . $relativePath;

        if (! is_file($fullPath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $downloadName = (string) ($attachment['original_name'] ?? basename($fullPath));

        $response = $this->response->download($fullPath, null, true)->setFileName($downloadName);

        // The attachment preview modal loads this same URL in an <iframe>/<img>;
        // the default attachment disposition makes browsers refuse to render it
        // inline and the preview just shows blank, so let the caller ask for
        // an inline disposition instead of forcing a save-as download.
        if ($this->request->getGet('view') === '1') {
            $response->inline();
        }

        return $response;
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
