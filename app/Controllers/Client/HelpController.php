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

        $isPayrollStaff = \App\Libraries\PortalAccess::can('help', $user);

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
            'faqs' => $this->getFaqs(),
        ]);
    }

    /**
     * Single source of truth for the Help Centre FAQs — shared by the page
     * itself (index()) and the header search widget's index (searchIndex()),
     * so the two never drift out of sync.
     */
    private function getFaqs(): array
    {
        return [
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
            [
                'question' => 'How do I set up face recognition login?',
                'answer' => 'Go to Settings > Face Recognition and follow the enrollment steps. Once enrolled, you can use it as a faster sign-in option alongside your regular password.',
            ],
            [
                'question' => 'What is the payslip password, and is it the same as my login password?',
                'answer' => 'No — it\'s a separate password just for opening your payslip file, set and reset from your Profile page.',
            ],
            [
                'question' => 'How do I apply for a loan?',
                'answer' => 'Loans aren\'t self-service — there\'s no application form in the system. Reach out to Payroll Staff directly (a help request works too); they\'ll record it and set up the repayment schedule.',
            ],
            [
                'question' => 'How do request approval codes work?',
                'answer' => 'Payroll Staff or HR Admin can generate an approval code and share it with you, so your overtime/undertime/leave request is approved faster when you submit it.',
            ],
            [
                'question' => 'Why is my notification bell count wrong?',
                'answer' => 'The unread count only reflects notifications loaded when the page was opened. Refresh the page if you were just told about something new.',
            ],
        ];
    }

    /**
     * Lightweight local search index for the header's "AI helper" search
     * widget — no LLM, no external calls. Combines the FAQs above with the
     * plain-text content of the Help Centre's documentation articles, so
     * the widget can score matches against real content instead of just
     * page titles. Cached client-side after first fetch (see header.php).
     */
    public function searchIndex()
    {
        if (! auth()->loggedIn()) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false]);
        }

        $entries = [];

        foreach ($this->getFaqs() as $faq) {
            $entries[] = [
                'type' => 'faq',
                'title' => $faq['question'],
                'snippet' => $faq['answer'],
                'url' => site_url('client_portal/help') . '#faq-list',
            ];
        }

        $docFiles = [
            'system_overview' => ['tag' => 'System', 'target' => '#doc-system-overview'],
            'role_guides' => ['tag' => 'Access', 'target' => '#doc-role-guides'],
            'operational_workflows' => ['tag' => 'Workflows', 'target' => '#doc-operational-workflows'],
            'knowledge_base' => ['tag' => 'Troubleshooting', 'target' => '#doc-knowledge-base'],
            'compliance_audit' => ['tag' => 'Compliance', 'target' => '#doc-compliance-audit'],
            'support_center' => ['tag' => 'Support', 'target' => '#doc-support-center'],
            'release_notes' => ['tag' => 'Updates', 'target' => '#doc-release-notes'],
        ];

        foreach ($docFiles as $file => $meta) {
            $path = APPPATH . 'Views/client_portal/docs/' . $file . '.php';
            if (! is_file($path)) {
                continue;
            }

            $html = file_get_contents($path);
            $articleTitle = '';
            if (preg_match('~<h2>(.*?)</h2>~s', $html, $m)) {
                $articleTitle = $this->plainText($m[1]);
            }
            if ($articleTitle === '') {
                $articleTitle = ucwords(str_replace('_', ' ', $file));
            }

            // Split into per-<h3> sub-sections rather than indexing the
            // whole article as one blob — several of these docs (knowledge
            // base especially) are literally structured as one question per
            // <h3>, and matching the specific section gives a focused
            // answer instead of dumping the entire article.
            $sections = preg_split('~<h3>(.*?)</h3>~s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
            $hasSections = count($sections) > 1;

            if ($hasSections) {
                // $sections alternates: [intro, h3_1_title, h3_1_body, h3_2_title, h3_2_body, ...]
                $intro = $this->plainText($sections[0]);
                if ($intro !== '') {
                    $entries[] = [
                        'type' => 'doc',
                        'tag' => $meta['tag'],
                        'title' => $articleTitle,
                        'snippet' => $intro,
                        'url' => site_url('client_portal/help') . $meta['target'],
                    ];
                }

                for ($i = 1; $i < count($sections); $i += 2) {
                    $sectionTitle = $this->plainText($sections[$i]);
                    $sectionBody = $this->plainText($sections[$i + 1] ?? '');

                    if ($sectionTitle === '' || $sectionBody === '') {
                        continue;
                    }

                    $entries[] = [
                        'type' => 'doc',
                        'tag' => $meta['tag'],
                        'title' => $sectionTitle,
                        'snippet' => $sectionBody,
                        'url' => site_url('client_portal/help') . $meta['target'],
                    ];
                }
            } else {
                $entries[] = [
                    'type' => 'doc',
                    'tag' => $meta['tag'],
                    'title' => $articleTitle,
                    'snippet' => $this->plainText($html),
                    'url' => site_url('client_portal/help') . $meta['target'],
                ];
            }
        }

        return $this->response->setJSON(['success' => true, 'entries' => $entries]);
    }

    /** Strip HTML, decode entities (so "5&amp;nbsp;MB" renders as "5 MB"), and collapse whitespace. */
    private function plainText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);

        return trim(preg_replace('~\s+~', ' ', $text));
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

        if (! \App\Libraries\PortalAccess::can('help', $user)) {
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