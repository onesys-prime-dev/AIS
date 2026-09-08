<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
    $helpRequests = $helpRequests ?? [];
    $categories = $categories ?? [];
    $priorities = $priorities ?? [];
    $isPayrollStaff = (bool) ($isPayrollStaff ?? false);

    $faqs = (! empty($faqs) && is_array($faqs)) ? $faqs : [
        [
            'question' => 'How do I submit a help request?',
            'answer' => 'Use the contact form below and send your concern to the support team.',
        ],
        [
            'question' => 'How quickly will my request be reviewed?',
            'answer' => 'Urgent and high-priority concerns are handled first by the support team.',
        ],
        [
            'question' => 'Can I check request status updates?',
            'answer' => 'Yes. Use the request history list below to track status and responses.',
        ],
        [
            'question' => 'What kinds of issues should I send here?',
            'answer' => 'Anything related to OTIS features, access problems, workflow concerns, or system questions.',
        ],
    ];

    $statusLabels = [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];

    $statusClasses = [
        'open' => 'bg-slate-100 text-slate-700',
        'in_progress' => 'bg-blue-100 text-blue-700',
        'resolved' => 'bg-emerald-100 text-emerald-700',
        'closed' => 'bg-zinc-100 text-zinc-700',
    ];

    $priorityClasses = [
        'low' => 'bg-slate-100 text-slate-700',
        'normal' => 'bg-blue-100 text-blue-700',
        'high' => 'bg-amber-100 text-amber-700',
        'urgent' => 'bg-red-100 text-red-700',
    ];

    $topCards = [
        [
            'title' => 'Browse Documentation',
            'desc' => 'System overview, role guides, workflows, and more.',
            'target' => '#documentation',
            'icon' => 'about',
        ],
        [
            'title' => 'FAQs',
            'desc' => 'Quick answers to your most common questions.',
            'target' => '#faq-list',
            'icon' => 'faq',
        ],
        [
            'title' => 'Contact Support',
            'desc' => 'Reach the support team or send a help request.',
            'target' => '#contact-section',
            'icon' => 'features',
        ],
    ];

    // Popular searches shown as chips under the search bar, mirroring how
    // established help centers (Zendesk, Intercom) surface trending topics.
    $popularSearches = [
        'Payroll not generated',
        'Payslip access',
        'Bookkeeping upload',
        'Overtime approval',
        'Tax compliance',
    ];

    // One card per documentation article below. Each maps directly to a
    // real section id in the docs, so there are no dead links.
    $docCategories = [
        [
            'title' => 'System Overview',
            'desc' => 'What OTIS is, its purpose, and the three portals it provides.',
            'tag' => 'System',
            'target' => '#doc-system-overview',
            'icon' => 'overview',
        ],
        [
            'title' => 'User Role Guides',
            'desc' => 'What each role can see and do, from Employee to Administrator.',
            'tag' => 'Access',
            'target' => '#doc-role-guides',
            'icon' => 'roles',
        ],
        [
            'title' => 'Operational Workflows',
            'desc' => 'Step-by-step flow for payroll, bookkeeping, reconciliation, and tax.',
            'tag' => 'Workflows',
            'target' => '#doc-operational-workflows',
            'icon' => 'workflows',
        ],
        [
            'title' => 'Knowledge Base',
            'desc' => 'Common issues, their causes, and how to resolve them.',
            'tag' => 'Troubleshooting',
            'target' => '#doc-knowledge-base',
            'icon' => 'knowledge',
        ],
        [
            'title' => 'Compliance & Audit',
            'desc' => 'Audit logging, security controls, and government compliance.',
            'tag' => 'Compliance',
            'target' => '#doc-compliance-audit',
            'icon' => 'compliance',
        ],
        [
            'title' => 'Support Center',
            'desc' => 'Ticket lifecycle, priority levels, and what to include when reporting.',
            'tag' => 'Support',
            'target' => '#doc-support-center',
            'icon' => 'support',
        ],
        [
            'title' => 'Release Notes',
            'desc' => 'What shipped, what is coming, and current known limitations.',
            'tag' => 'Updates',
            'target' => '#doc-release-notes',
            'icon' => 'release',
        ],
    ];

    $docIcons = [
        'overview' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
        'roles' => '<path d="M17 21v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>',
        'workflows' => '<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/><path d="M9 12h6M9 16h6"/>',
        'knowledge' => '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 015.82 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'compliance' => '<path d="M12 2l8 4v6c0 5-3.5 9-8 10-4.5-1-8-5-8-10V6z"/><path d="M9 12l2 2 4-4"/>',
        'support' => '<path d="M18 21H4a2 2 0 01-2-2V7a2 2 0 012-2h5l2-3h2l2 3h5a2 2 0 012 2v12a2 2 0 01-2 2z"/><circle cx="12" cy="13" r="3"/>',
        'release' => '<path d="M13 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V9z"/><polyline points="13 2 13 9 20 9"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/>',
    ];

    $ecovisAddress = 'Unit 1802, The Peak Tower, Salcedo Village, 107 L.P. Leviste Street, Makati City, 1227 Metro Manila';
    $ecovisMapsQuery = rawurlencode($ecovisAddress);
    $ecovisMapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . $ecovisMapsQuery;
    $ecovisEmbedUrl = 'https://www.google.com/maps?q=' . $ecovisMapsQuery . '&output=embed';
?>

<style>
    :root {
        --help-bg: #eef1f5;
        --help-surface: rgba(255, 255, 255, 0.92);
        --help-surface-strong: #ffffff;
        --help-border: #d7dee8;
        --help-text: #182230;
        --help-muted: #5f6b7a;
        --help-accent: #4f7fe0;
        --help-accent-strong: #3e67c7;
        --help-shadow: 0 18px 40px rgba(17, 24, 39, 0.08);
    }

    .help-shell {
        background:
            radial-gradient(circle at top left, rgba(79, 127, 224, 0.12), transparent 34%),
            radial-gradient(circle at top right, rgba(112, 148, 224, 0.12), transparent 28%),
            var(--help-bg);
        height: 100%;
        min-height: 0;
        overflow-y: auto;
    }

    .help-container {
        max-width: 1160px;
        margin: 0 auto;
        padding: 0 clamp(16px, 3vw, 28px);
    }

    .hero-wrap {
        padding-top: clamp(24px, 4vw, 48px);
        padding-bottom: clamp(20px, 3vw, 34px);
        text-align: center;
    }

    .hero-title {
        font-size: clamp(2.4rem, 5vw, 4.25rem);
        line-height: 0.98;
        font-weight: 800;
        color: var(--help-text);
        margin: 0 0 25px;
        letter-spacing: -0.03em;
    }

    .hero-search {
        max-width: 560px;
        margin: 0 auto;
        position: relative;
    }

    .hero-search input {
        width: 100%;
        height: 50px;
        border-radius: 999px;
        border: 1px solid var(--help-border);
        background: rgba(255, 255, 255, 0.9);
        padding: 0 52px 0 18px;
        font-size: 15px;
        color: var(--help-text);
        outline: none;
        box-shadow: 0 10px 22px rgba(17, 24, 39, 0.08);
    }

    .hero-search-dot {
        position: absolute;
        right: 7px;
        top: 50%;
        transform: translateY(-50%);
        width: 28px;
        height: 28px;
        border-radius: 999px;
        display: grid;
        place-items: center;
        color: var(--help-accent);
        background: linear-gradient(180deg, #edf3ff, #dfe8f7);
        border: 1px solid #c9d5ea;
        padding: 0;
        cursor: pointer;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
    }

    .hero-sub {
        font-size: 14px;
        color: var(--help-muted);
        margin: 25px 0 25px;
    }

    .top-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 14px;
    }

    .top-card {
        background: var(--help-surface);
        backdrop-filter: blur(10px);
        border: 1px solid var(--help-border);
        border-radius: 18px;
        text-decoration: none;
        color: var(--help-text);
        min-height: 184px;
        padding: 30px 25px 25px 25px;
        box-shadow: var(--help-shadow);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 18px;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }

    .top-card:hover {
        transform: translateY(-3px);
        border-color: rgba(79, 127, 224, 0.35);
        box-shadow: 0 22px 46px rgba(17, 24, 39, 0.11);
    }

    .top-card-title {
        font-size: 1.1rem;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 6px;
    }

    .top-card-desc {
        font-size: 0.94rem;
        color: var(--help-muted);
        line-height: 1.55;
    }

    .top-card-arrow {
        margin-left: auto;
        width: 28px;
        height: 28px;
        color: var(--help-accent);
    }

    .icon-s {
        display: flex;
        justify-content: center;
        align-items: center;
        width: auto;
        height: 48px;
        color: var(--help-accent);
    }

    .icon-s .icon{
        width: 65px;
        height: 65px;
    }

    .faq-head .icon {
        width: 55px;
        height: 55px;
    }

    .section-gray {
        background: rgba(255, 255, 255, 0.5);
    }

    .search-chips {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 8px;
        margin: 0 0 25px;
    }

    .search-chip {
        border: 1px solid var(--help-border);
        background: rgba(255, 255, 255, 0.75);
        color: var(--help-muted);
        font-size: 0.82rem;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 999px;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }

    .search-chip:hover {
        background: rgba(79, 127, 224, 0.1);
        color: var(--help-accent-strong);
        border-color: rgba(79, 127, 224, 0.35);
    }

    .docs-empty-state {
        display: none;
        text-align: center;
        padding: 40px 20px;
        color: var(--help-muted);
        font-size: 0.95rem;
        background: var(--help-surface-strong);
        border: 1px dashed var(--help-border);
        border-radius: 16px;
    }

    .docs-empty-state.show {
        display: block;
    }

    .faq-wrap {

        padding-top: clamp(18px, 3vw, 32px);
        padding-bottom: clamp(18px, 3vw, 32px);
    }

    .faq-head {
        display: flex;
        flex-direction: column;
        justify-self: center;
        align-items: center;
        text-align: center;
        margin-bottom: 18px;
    }

    .faq-badge {
        width: 34px;
        height: 34px;
        border-radius: 999px;
        margin: 0 auto 10px;
        display: grid;
        place-items: center;
        background: var(--help-accent);
        color: #fff;
        font-size: 18px;
        font-weight: 700;
    }

    .faq-head h2 {
        font-size: clamp(1.9rem, 4vw, 3rem);
        font-weight: 800;
        color: var(--help-text);
        letter-spacing: -0.03em;
        line-height: 1.1;
    }

    .faq-head p {
        font-size: 0.95rem;
        color: var(--help-muted);
        margin-top: 6px;
    }

    .faq-list {
        max-width: 760px;
        margin: 0 auto;
    }

    .faq-item {
        border: 1px solid var(--help-border);
        border-radius: 14px;
        background: var(--help-surface-strong);
        overflow: hidden;
        margin-bottom: 10px;
        box-shadow: 0 10px 24px rgba(17, 24, 39, 0.05);
    }

    .faq-item summary {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding: 16px 18px;
        font-size: 0.98rem;
        font-weight: 700;
        color: var(--help-text);
        cursor: pointer;
        list-style: none;
    }

    .faq-toggle {
        flex: 0 0 auto;
        width: 24px;
        height: 24px;
        display: grid;
        place-items: center;
        color: var(--help-accent);
    }

    .faq-toggle svg {
        width: 18px;
        height: 18px;
        display: block;
    }

    .faq-toggle .icon-minus {
        display: none;
    }

    .faq-item[open] .faq-toggle {
        color: var(--help-text);
    }

    .faq-item[open] .faq-toggle .icon-plus {
        display: none;
    }

    .faq-item[open] .faq-toggle .icon-minus {
        display: block;
    }

    .faq-item summary::-webkit-details-marker {
        display: none;
    }

    .faq-item .answer {
        padding: 0 18px 16px;
        font-size: 0.95rem;
        line-height: 1.65;
        color: var(--help-muted);
    }

    .contact-wrap {
        padding-top: clamp(18px, 3vw, 32px);
        padding-bottom: clamp(20px, 3vw, 36px);
    }

    .contact-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 16px;
        align-items: stretch;
    }

    .map-box,
    .touch-box {
        background: var(--help-surface-strong);
        box-shadow: var(--help-shadow);
        border: 1px solid var(--help-border);
        border-radius: 22px;
        overflow: hidden;
    }

    .map-box {
        min-height: 360px;
        height: 100%;
        position: relative;
        background: #e9eef7;
    }

    .map-box iframe {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        border: 0;
    }

    .map-link-btn {
        position: absolute;
        left: 14px;
        bottom: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.55);
        background: rgba(24, 34, 48, 0.76);
        color: #fff;
        text-decoration: none;
        font-size: 0.84rem;
        font-weight: 600;
        padding: 8px 12px;
        backdrop-filter: blur(4px);
    }

    .map-link-btn:hover {
        background: rgba(24, 34, 48, 0.88);
    }

    .touch-box {
        padding: clamp(18px, 3vw, 28px);
    }

    .touch-box h2 {
        font-size: clamp(2.3rem, 5vw, 4rem);
        font-weight: 700;
        color: var(--help-text);
        line-height: 0.98;
        letter-spacing: -0.03em;
    }

    .touch-box p {
        font-size: 0.95rem;
        color: var(--help-muted);
        margin-top: 8px;
        margin-bottom: 18px;
    }

    .contact-form {
        display: grid;
        gap: 12px;
    }

    .contact-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .contact-form input:not([type="checkbox"]),
    .contact-form select,
    .contact-form textarea {
        width: 100%;
        border: 1px solid var(--help-border);
        border-radius: 12px;
        background: #fbfcfe;
        padding: 12px 14px;
        font-size: 0.95rem;
        color: var(--help-text);
        outline: none;
    }

    .contact-form textarea {
        min-height: 150px;
        resize: vertical;
    }

    .contact-form input:focus,
    .contact-form select:focus,
    .contact-form textarea:focus {
        border-color: #dd1414;
        box-shadow: 0 0 0 3px rgba(224, 128, 128, 0.141);
    }

    .contact-check {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.88rem;
        color: var(--help-muted);
        line-height: 1.45;
        margin: 2px 0 2px;
    }

    .contact-check input[type="checkbox"] {
        width: 16px;
        height: 16px;
        min-width: 16px;
        margin: 0;
        padding: 0;
        border: 1px solid #9aa7b8;
        border-radius: 3px;
        accent-color: var(--help-accent);
        background: #fff;
        box-shadow: none;
    }

    .contact-check span {
        display: block;
    }

    .contact-btn {
        border: 0;
        border-radius: 12px;
        background: linear-gradient(135deg, #cf5252, #dd1414) /* linear-gradient(135deg, var(--help-accent), var(--help-accent-strong)) */;
        color: #fff;
        font-size: 0.98rem;
        font-weight: 700;
        padding: 13px 16px;
        cursor: pointer;
        box-shadow: 0 14px 24px rgba(106, 69, 69, 0.161);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .contact-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 28px rgba(79, 127, 224, 0.3);
    }

    .history-wrap {
        padding-top: 12px;
        padding-bottom: clamp(24px, 4vw, 42px);
    }

    .history-panel {
        background: var(--help-surface-strong);
        border-radius: 22px;
        box-shadow: var(--help-shadow);
        border: 1px solid var(--help-border);
        padding: clamp(16px, 2vw, 24px);
    }

    .footer-bar {
        background: #2E2E2E /* 1d2633 */;
        color: #d7dee8;
        font-size: 0.84rem;
        padding: 15px 0px;
    }

    .footer-inner {
        width: auto;
        margin: 0 20px;
        padding: 14px clamp(16px, 3vw, 28px);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }

    .footer-links {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .footer-links a {
        color: inherit;
        text-decoration: none;
    }

    .footer-links a:hover {
        color: #ffffff;
    }

    /* Documentation layout */
    .docs-layout {
        display: grid;
        grid-template-columns: 240px minmax(0, 1fr);
        gap: 32px;
        align-items: start;
    }

    .docs-sidebar {
        position: sticky;
        top: 24px;
        background: var(--help-surface-strong);
        border: 1px solid var(--help-border);
        border-radius: 16px;
        padding: 20px 14px;
        box-shadow: var(--help-shadow);
        display: flex;
        flex-direction: column;
        gap: 2px;
        max-height: calc(100vh - 48px);
        overflow-y: auto;
    }

    .docs-sidebar h4 {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--help-muted);
        padding: 0 12px;
        margin-bottom: 10px;
    }

    .docs-sidebar a {
        display: block;
        padding: 9px 12px;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--help-muted);
        text-decoration: none;
        border-left: 2px solid transparent;
        transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }

    .docs-sidebar a:hover {
        background: rgba(79, 127, 224, 0.08);
        color: var(--help-text);
    }

    .docs-sidebar a.active {
        background: rgba(79, 127, 224, 0.12);
        color: var(--help-accent-strong);
        border-left-color: var(--help-accent);
    }

    .docs-content {
        display: flex;
        flex-direction: column;
        gap: 18px;
        min-width: 0;
    }

    /* Doc article typography */
    .doc-article {
        border-radius: 16px;
        padding: 30px clamp(20px, 3vw, 36px);
        background: var(--help-surface-strong);
        border: 1px solid var(--help-border);
        box-shadow: var(--help-shadow);
        scroll-margin-top: 20px;
    }

    .doc-article h2 {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--help-text);
        letter-spacing: -0.02em;
        padding-bottom: 14px;
        margin-bottom: 18px;
        border-bottom: 1px solid var(--help-border);
    }

    .doc-article h3 {
        font-size: 1.02rem;
        font-weight: 700;
        color: var(--help-text);
        margin: 24px 0 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .doc-article h3:first-of-type {
        margin-top: 2px;
    }

    .doc-article h3::before {
        content: '';
        width: 4px;
        height: 15px;
        border-radius: 999px;
        background: var(--help-accent);
        flex: 0 0 auto;
    }

    .doc-article p {
        font-size: 0.94rem;
        line-height: 1.7;
        color: var(--help-muted);
        margin-bottom: 6px;
    }

    .doc-article p strong {
        color: var(--help-text);
    }

    .doc-article ul,
    .doc-article ol {
        margin: 4px 0 14px;
        padding-left: 0;
        list-style: none;
    }

    .doc-article ul li,
    .doc-article ol li {
        position: relative;
        padding: 7px 0 7px 26px;
        font-size: 0.91rem;
        line-height: 1.55;
        color: var(--help-muted);
        border-bottom: 1px dashed var(--help-border);
    }

    .doc-article ul li:last-child,
    .doc-article ol li:last-child {
        border-bottom: 0;
    }

    .doc-article ul li::before {
        content: '';
        position: absolute;
        left: 4px;
        top: 16px;
        width: 6px;
        height: 6px;
        border-radius: 999px;
        background: var(--help-accent);
    }

    .doc-article ol {
        counter-reset: doc-step;
    }

    .doc-article ol li {
        counter-increment: doc-step;
        padding-left: 32px;
    }

    .doc-article ol li::before {
        content: counter(doc-step);
        position: absolute;
        left: 0;
        top: 6px;
        width: 20px;
        height: 20px;
        border-radius: 999px;
        background: rgba(79, 127, 224, 0.12);
        color: var(--help-accent-strong);
        font-size: 0.7rem;
        font-weight: 700;
        display: grid;
        place-items: center;
    }

    .doc-article table {
        width: 100%;
        border-collapse: collapse;
        margin: 6px 0 14px;
        font-size: 0.88rem;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--help-border);
    }

    .doc-article thead th {
        background: rgba(79, 127, 224, 0.08);
        color: var(--help-text);
        text-align: left;
        font-weight: 700;
        padding: 11px 14px;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .doc-article tbody td {
        padding: 11px 14px;
        color: var(--help-muted);
        border-top: 1px solid var(--help-border);
    }

    .doc-article tbody tr:nth-child(even) {
        background: rgba(79, 127, 224, 0.03);
    }

    @media (max-width: 900px) {
        .docs-layout {
            grid-template-columns: 1fr;
        }

        .docs-sidebar {
            position: static;
            max-height: none;
            flex-direction: row;
            flex-wrap: wrap;
            gap: 6px;
        }

        .docs-sidebar h4 {
            width: 100%;
        }
    }

    /* Documentation category grid */
    .featured-guides-wrap {
        padding-top: clamp(28px, 4vw, 48px);
        padding-bottom: clamp(28px, 4vw, 48px);
        background: rgba(255, 255, 255, 0.5);
    }

    .docs-section-title {
        font-size: clamp(1.9rem, 4vw, 2.6rem);
        font-weight: 700;
        color: var(--help-text);
        letter-spacing: -0.03em;
        margin-bottom: 8px;
    }

    .docs-section-sub {
        font-size: 0.95rem;
        color: var(--help-muted);
        margin-bottom: 28px;
    }

    .guides-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
    }

    .guide-card {
        display: block;
        background: var(--help-surface-strong);
        border: 1px solid var(--help-border);
        border-radius: 16px;
        padding: 28px 24px;
        text-decoration: none;
        box-shadow: var(--help-shadow);
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }

    .guide-card:hover {
        transform: translateY(-4px);
        border-color: rgba(79, 127, 224, 0.35);
        box-shadow: 0 24px 48px rgba(17, 24, 39, 0.12);
    }

    .guide-card-icon {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(79, 127, 224, 0.1);
        border-radius: 12px;
        color: var(--help-accent);
        margin-bottom: 16px;
    }

    .guide-card-icon svg {
        width: 28px;
        height: 28px;
    }

    .guide-card-meta {
        font-size: 0.78rem;
        color: var(--help-muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
    }

    .guide-card h3 {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--help-text);
        margin-bottom: 12px;
        line-height: 1.35;
    }

    .guide-card p {
        font-size: 0.95rem;
        color: var(--help-muted);
        line-height: 1.6;
        margin: 0;
    }

    @media (max-width: 900px) {
        .top-card {
            min-height: auto;
        }

        .map-box {
            min-height: 280px;
        }

        .map-link-btn {
            left: 12px;
            bottom: 12px;
        }
    }

    @media (max-width: 640px) {
        .hero-wrap {
            padding-top: 20px;
        }

        .hero-search input {
            height: 46px;
            font-size: 0.95rem;
        }

        .top-card,
        .touch-box,
        .history-panel {
            border-radius: 16px;
        }

        .faq-item summary {
            padding: 14px 16px;
        }

        .faq-item .answer {
            padding: 0 16px 14px;
        }

        .contact-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="help-shell hide-scroll-bar">
    <section class="hero-wrap">
        <div class="help-container">
            <h1 class="hero-title">Help Centre</h1>
            <div class="hero-search">
                <input type="text" id="helpSearchInput" placeholder="Search articles, e.g. &quot;payroll&quot; or &quot;payslip&quot;" aria-label="Search help" autocomplete="off" />
                <button type="button" class="hero-search-dot" id="helpSearchClear" aria-label="Clear search">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="2"></circle>
                        <path d="M16.2 16.2L20 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                </button>
            </div>
            <p class="hero-sub">Choose a topic below to learn more about the system</p>

            <div class="search-chips">
                <?php foreach ($popularSearches as $term): ?>
                    <button type="button" class="search-chip" data-search-term="<?= esc($term) ?>"><?= esc($term) ?></button>
                <?php endforeach; ?>
            </div>

            <div class="top-cards">
                <?php foreach ($topCards as $item): ?>
                    <a href="<?= esc($item['target']) ?>" class="top-card">
                        <div class="icon-s" aria-hidden="true">
                            <?php if ($item['icon'] === 'about'): ?>
                                <img src="<?= base_url('icons/help-about.png') ?>" class="icon">
                            <?php elseif ($item['icon'] === 'faq'): ?>
                                <img src="<?= base_url('icons/help-questions.png') ?>" class="icon">
                            <?php else: ?>
                                <img src="<?= base_url('icons/help-features.png') ?>" class="icon">
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="top-card-title"><?= esc($item['title']) ?></div>
                            <div class="top-card-desc"><?= esc($item['desc']) ?></div>
                        </div>
                        <div class="top-card-arrow">
                            <img src="<?= base_url('icons/help-arrow.png') ?>" class="icon">
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="documentation" class="featured-guides-wrap">
        <div class="help-container">
            <h2 class="docs-section-title">Documentation</h2>
            <p class="docs-section-sub">Browse by topic below, or jump straight to a section using the sidebar.</p>

            <div class="guides-grid" style="margin-bottom: 40px;">
                <?php foreach ($docCategories as $item): ?>
                    <a href="<?= esc($item['target']) ?>" class="guide-card">
                        <div class="guide-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <?= $docIcons[$item['icon']] ?? '' ?>
                            </svg>
                        </div>
                        <div class="guide-card-meta"><?= esc($item['tag']) ?></div>
                        <h3><?= esc($item['title']) ?></h3>
                        <p><?= esc($item['desc']) ?></p>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="docs-layout">
                <aside class="docs-sidebar">
                    <h4>On this page</h4>
                    <a href="#doc-system-overview">System Overview</a>
                    <a href="#doc-role-guides">User Role Guides</a>
                    <a href="#doc-operational-workflows">Operational Workflows</a>
                    <a href="#doc-knowledge-base">Knowledge Base</a>
                    <a href="#doc-compliance-audit">Compliance & Audit</a>
                    <a href="#doc-support-center">Support Center</a>
                    <a href="#doc-release-notes">Release Notes</a>
                </aside>
                <div class="docs-content">
                    <?php include __DIR__ . '/docs/system_overview.php'; ?>
                    <?php include __DIR__ . '/docs/role_guides.php'; ?>
                    <?php include __DIR__ . '/docs/operational_workflows.php'; ?>
                    <?php include __DIR__ . '/docs/knowledge_base.php'; ?>
                    <?php include __DIR__ . '/docs/compliance_audit.php'; ?>
                    <?php include __DIR__ . '/docs/support_center.php'; ?>
                    <?php include __DIR__ . '/docs/release_notes.php'; ?>
                    <div class="docs-empty-state" id="docsEmptyState">No articles match your search. Try a different term or <a href="#contact-section" style="color: var(--help-accent-strong); font-weight:600;">contact support</a>.</div>
                </div>
            </div>
        </div>
    </section>

    <section id="faq-list" class="section-gray faq-wrap">
        <div class="help-container">
            <div class="faq-head">
                <img src="<?= base_url('icons/help-questions.png') ?>" class="icon">
                <h2>Frequently Asked Questions <span style="font-size: 15px; color:#777; font-weight:500;">(FAQs)</span></h2>
                <p>Can’t find what you’re looking for?</p>
            </div>

            <div class="faq-list">
                <?php foreach ($faqs as $index => $faq): ?>
                    <details class="faq-item" <?= $index === 1 ? 'open' : '' ?>>
                        <summary>
                            <span><?= esc($faq['question']) ?></span>
                            <span class="faq-toggle" aria-hidden="true">
                                <svg class="icon-plus" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                                <svg class="icon-minus" viewBox="0 0 24 24" fill="none"><path d="M6 12h12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
                            </span>
                        </summary>
                        <div class="answer"><?= esc($faq['answer']) ?></div>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="contact-section" class="contact-wrap">
        <div class="help-container contact-grid">
            <div class="map-box">
                <iframe
                    src="<?= esc($ecovisEmbedUrl) ?>"
                    title="Ecovis location map"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen
                ></iframe>
                <a href="<?= esc($ecovisMapsUrl) ?>" class="map-link-btn" target="_blank" rel="noopener noreferrer">
                    Open in Google Maps
                </a>
            </div>

            <div class="touch-box">
                <h2>Get in Touch</h2>
                <p>You can reach us anytime</p>

                <?php $successMessage = session()->getFlashdata('success'); ?>
                <?php if (is_string($successMessage) && $successMessage !== ''): ?>
                    <div class="mb-2 rounded border border-emerald-200 bg-emerald-50 px-2 py-2 text-[10px] text-emerald-700">
                        <?= esc($successMessage) ?>
                    </div>
                <?php endif; ?>

                <?php $errorMessage = session()->getFlashdata('error'); ?>
                <?php if (is_string($errorMessage) && $errorMessage !== ''): ?>
                    <div class="mb-2 rounded border border-red-200 bg-red-50 px-2 py-2 text-[10px] text-red-700">
                        <?= esc($errorMessage) ?>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('client_portal/help/store') ?>" method="post" class="contact-form" id="helpContactForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="category" id="helpCategoryField" value="general" />
                    <input type="hidden" name="priority" value="normal" />
                    <input type="hidden" name="subject" id="helpSubjectField" value="Help request" />
                    <input type="hidden" name="message" id="helpMessageField" value="" />

                    <div class="contact-row">
                        <input type="text" id="helpFirstName" placeholder="First Name" required />
                        <input type="text" id="helpLastName" placeholder="Last Name" required />
                    </div>

                    <input type="email" id="helpEmail" placeholder="Email Address" required />

                    <div class="contact-row">
                        <input type="tel" id="helpPhone" placeholder="Phone Number" required />
                        <select id="helpCategoryVisual" aria-label="Help category">
                            <?php foreach ($categories as $value => $label): ?>
                                <option value="<?= esc($value) ?>"><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <textarea id="helpMessageVisual" placeholder="How can we help?" required></textarea>

                    <label class="contact-check">
                        <input type="checkbox" id="helpAgree" required />
                        <span>I have reviewed and accepted our communication and privacy terms.</span>
                    </label>

                    <button type="submit" class="contact-btn">Send Message</button>
                </form>
            </div>
        </div>
    </section>

    <footer class="footer-bar">
        <div class="footer-inner">
            <div>&copy; 2026 OTIS. All rights reserved.</div>
            <div class="footer-links">
                <a href="#doc-compliance-audit">Privacy Policy</a>
                <a href="#faq-list">Terms of Service</a>
                <a href="#contact-section">Cookie Settings</a>
            </div>
        </div>
    </footer>
</div>

<script>
    (function () {
        const form = document.getElementById('helpContactForm');
        const firstName = document.getElementById('helpFirstName');
        const lastName = document.getElementById('helpLastName');
        const email = document.getElementById('helpEmail');
        const phone = document.getElementById('helpPhone');
        const categoryVisual = document.getElementById('helpCategoryVisual');
        const messageVisual = document.getElementById('helpMessageVisual');
        const agree = document.getElementById('helpAgree');
        const categoryField = document.getElementById('helpCategoryField');
        const subjectField = document.getElementById('helpSubjectField');
        const messageField = document.getElementById('helpMessageField');

        if (categoryVisual && categoryField) {
            categoryField.value = categoryVisual.value || 'general';
            categoryVisual.addEventListener('change', function () {
                categoryField.value = categoryVisual.value || 'general';
            });
        }

        if (form) {
            form.addEventListener('submit', function (event) {
                if (!agree.checked) {
                    event.preventDefault();
                    return;
                }

                subjectField.value = `${firstName.value.trim()} ${lastName.value.trim()} - Help Request`.trim();
                messageField.value = [
                    `Name: ${firstName.value.trim()} ${lastName.value.trim()}`,
                    `Email: ${email.value.trim()}`,
                    `Phone: ${phone.value.trim()}`,
                    `Category: ${categoryVisual.value}`,
                    '',
                    messageVisual.value.trim(),
                ].join('\n');
            });
        }
    })();
</script>

<script>
    (function () {
        const shell = document.querySelector('.help-shell');
        const sidebarLinks = Array.from(document.querySelectorAll('.docs-sidebar a[href^="#doc-"]'));
        const sections = sidebarLinks
            .map(function (link) { return document.querySelector(link.getAttribute('href')); })
            .filter(Boolean);

        if (!shell || !sections.length) {
            return;
        }

        function setActive() {
            const shellTop = shell.getBoundingClientRect().top;
            let activeId = sections[0].id;

            sections.forEach(function (section) {
                const offset = section.getBoundingClientRect().top - shellTop;
                if (offset <= 120) {
                    activeId = section.id;
                }
            });

            sidebarLinks.forEach(function (link) {
                link.classList.toggle('active', link.getAttribute('href') === '#' + activeId);
            });
        }

        shell.addEventListener('scroll', setActive, { passive: true });
        window.addEventListener('resize', setActive);
        setActive();
    })();
</script>

<script>
    (function () {
        const input = document.getElementById('helpSearchInput');
        const clearBtn = document.getElementById('helpSearchClear');
        const emptyState = document.getElementById('docsEmptyState');
        const docArticles = Array.from(document.querySelectorAll('.doc-article'));
        const faqItems = Array.from(document.querySelectorAll('.faq-item'));
        const shell = document.querySelector('.help-shell');
        const docsSection = document.getElementById('documentation');

        if (!input) {
            return;
        }

        let hasScrolledToResults = false;

        function applyFilter(rawQuery) {
            const query = rawQuery.trim().toLowerCase();
            const isFiltering = query.length >= 2;

            let visibleDocs = 0;
            docArticles.forEach(function (article) {
                const matches = !isFiltering || article.textContent.toLowerCase().includes(query);
                article.style.display = matches ? '' : 'none';
                if (matches) {
                    visibleDocs += 1;
                }
            });

            let visibleFaqs = 0;
            faqItems.forEach(function (item) {
                const matches = !isFiltering || item.textContent.toLowerCase().includes(query);
                item.style.display = matches ? '' : 'none';
                if (matches) {
                    visibleFaqs += 1;
                }
            });

            if (emptyState) {
                emptyState.classList.toggle('show', isFiltering && visibleDocs === 0);
            }

            clearBtn.classList.toggle('has-value', query.length > 0);

            if (isFiltering && !hasScrolledToResults && shell && docsSection) {
                hasScrolledToResults = true;
                docsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            if (!isFiltering) {
                hasScrolledToResults = false;
            }
        }

        let debounceTimer = null;
        input.addEventListener('input', function () {
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(function () {
                applyFilter(input.value);
            }, 150);
        });

        clearBtn.addEventListener('click', function () {
            input.value = '';
            input.focus();
            applyFilter('');
        });

        document.querySelectorAll('.search-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                const term = chip.getAttribute('data-search-term') || '';
                input.value = term;
                input.focus();
                applyFilter(term);
            });
        });
    })();
</script>

<?= $this->endSection() ?>