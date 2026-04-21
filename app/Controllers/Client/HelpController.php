<?php

namespace App\Controllers\Client;

use App\Controllers\BaseController;
use App\Services\Client\HelpService;

class HelpController extends BaseController
{
    protected HelpService $helpService;

    public function __construct()
    {
        $this->helpService = new HelpService();
    }

    public function index()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->to('/login');
        }

        $isPayrollStaff = $user->inGroup('client_payroll_staff');

        return view('client_portal/help', [
            'helpRequests' => $isPayrollStaff
                ? $this->helpService->getAllHelpRequests()
                : $this->helpService->getHelpRequestsForUser((int) $user->id),
            'isPayrollStaff' => $isPayrollStaff,
            'categories' => [
                'general' => 'General Support',
                'account' => 'Account Access',
                'timekeeping' => 'Timekeeping',
                'payroll' => 'Payroll',
                'technical' => 'Technical Issue',
                'other' => 'Other',
            ],
            'priorities' => [
                'low' => 'Low',
                'normal' => 'Normal',
                'high' => 'High',
                'urgent' => 'Urgent',
            ],
            'statuses' => [
                'open' => 'Open',
                'in_progress' => 'In Progress',
                'resolved' => 'Resolved',
                'closed' => 'Closed',
            ],
            'faqs' => [
                [
                    'question' => 'How do I submit a help request?',
                    'answer' => 'Use the form on this page, choose a category, and describe the issue clearly so the team can respond faster.',
                ],
                [
                    'question' => 'How fast will I get a response?',
                    'answer' => 'Urgent issues are prioritized first. Normal requests are handled in the order they were received.',
                ],
                [
                    'question' => 'Can I track my request?',
                    'answer' => 'Yes. Your submitted requests and their current status appear in the request history section below.',
                ],
            ],
        ]);
    }

    public function store()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->to('/login');
        }

        $category = trim((string) $this->request->getPost('category'));
        $priority = trim((string) $this->request->getPost('priority'));
        $subject = trim((string) $this->request->getPost('subject'));
        $message = trim((string) $this->request->getPost('message'));

        $allowedCategories = ['general', 'account', 'timekeeping', 'payroll', 'technical', 'other'];
        $allowedPriorities = ['low', 'normal', 'high', 'urgent'];

        if (! in_array($category, $allowedCategories, true)) {
            return redirect()->back()->withInput()->with('error', 'Please choose a valid help category.');
        }

        if (! in_array($priority, $allowedPriorities, true)) {
            return redirect()->back()->withInput()->with('error', 'Please choose a valid priority.');
        }

        if ($subject === '' || mb_strlen($subject) > 120) {
            return redirect()->back()->withInput()->with('error', 'Subject is required and must not exceed 120 characters.');
        }

        if ($message === '') {
            return redirect()->back()->withInput()->with('error', 'Please describe your issue.');
        }

        if (mb_strlen($message) > 2000) {
            return redirect()->back()->withInput()->with('error', 'Message must not exceed 2000 characters.');
        }

        $saved = $this->helpService->createHelpRequest((int) $user->id, [
            'category' => $category,
            'priority' => $priority,
            'subject' => $subject,
            'message' => $message,
        ]);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', 'Failed to submit your help request. Please try again.');
        }

        return redirect()->to(site_url('client_portal/help'))->with('success', 'Your help request has been submitted.');
    }

    public function update(int $id)
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->to('/login');
        }

        if (! $user->inGroup('client_payroll_staff')) {
            return redirect()->back()->withInput()->with('error', 'Only payroll staff can update help requests.');
        }

        $status = trim((string) $this->request->getPost('status'));
        $response = trim((string) $this->request->getPost('response'));

        $allowedStatuses = ['open', 'in_progress', 'resolved', 'closed'];

        if (! in_array($status, $allowedStatuses, true)) {
            return redirect()->back()->withInput()->with('error', 'Please choose a valid request status.');
        }

        if ($response !== '' && mb_strlen($response) > 2000) {
            return redirect()->back()->withInput()->with('error', 'Response must not exceed 2000 characters.');
        }

        $updated = $this->helpService->updateHelpRequest($id, (int) $user->id, [
            'status' => $status,
            'response' => $response,
        ]);

        if (! $updated) {
            return redirect()->back()->withInput()->with('error', 'Failed to update the help request.');
        }

        return redirect()->back()->with('success', 'Help request updated successfully.');
    }
}

?>