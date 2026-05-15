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

        if (! $user->inGroup('client_payroll_staff')) {
            return redirect()->back()->withInput()->with('error', 'Only payroll staff can publish announcements.');
        }

        $title = trim((string) $this->request->getPost('title'));
        $category = trim((string) $this->request->getPost('category'));
        $audience = trim((string) $this->request->getPost('audience'));
        $department = trim((string) $this->request->getPost('department'));
        $priority = trim((string) $this->request->getPost('priority'));
        $publishAtRaw = trim((string) $this->request->getPost('publish_at'));
        $expiresAtRaw = trim((string) $this->request->getPost('expires_at'));
        $summary = trim((string) $this->request->getPost('summary'));
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

        if ($summary === '' || mb_strlen($summary) > 180) {
            return redirect()->back()->withInput()->with('error', 'Short summary is required and must not exceed 180 characters.');
        }

        if ($details === '') {
            return redirect()->back()->withInput()->with('error', 'Announcement details are required.');
        }

        $saved = $this->announcementService->createAnnouncement((int) $user->id, [
            'title' => $title,
            'category' => $category,
            'audience' => $audience,
            'department' => $department,
            'priority' => $priority,
            'publish_at' => $publishAtDate->format('Y-m-d H:i:s'),
            'expires_at' => $expiresAt,
            'summary' => $summary,
            'details' => $details,
            'require_acknowledgement' => $requireAcknowledgement,
        ]);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', 'Failed to publish announcement. Please try again.');
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
