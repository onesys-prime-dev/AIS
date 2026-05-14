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
            'title' => 'About the System',
            'desc' => 'Everything you need to know about the system.',
            'target' => '#about-system',
            'icon' => 'about',
        ],
        [
            'title' => 'FAQs',
            'desc' => 'Quick answers to your most common questions.',
            'target' => '#faq-list',
            'icon' => 'faq',
        ],
        [
            'title' => 'System Features',
            'desc' => 'Master the tools designed to streamline your workflow.',
            'target' => '#feature-grid',
            'icon' => 'features',
        ],
    ];

    $featureCards = [
        [
            'title' => 'Employee Requests',
            'desc' => 'Save hours of manual work with error-free automated payroll.',
            'target' => '#doc-core-workflows',
            'icon' => base_url('icons/request.png'),
            'image' => base_url('images/background/employee_request.png'),
            'tone' => 'rgba(62, 36, 10, 0.62)',
            'size' => 'card-standard',
        ],
        [
            'title' => 'Employee Management',
            'desc' => 'Build a stronger workforce by keeping all staff data organized.',
            'target' => '#about-system',
            'icon' => base_url('icons/employee.png'),
            'image' => base_url('images/background/employee_management.png'),
            'tone' => 'rgba(28, 36, 52, 0.58)',
            'size' => 'card-standard',
        ],
        [
            'title' => 'System Customization',
            'desc' => 'Personalize your workspace with custom branding and color themes.',
            'target' => '#doc-support',
            'icon' => base_url('icons/customization.png'),
            'image' => base_url('images/background/system_customization.png'),
            'tone' => 'rgba(18, 34, 70, 0.62)',
            'size' => 'card-standard',
        ],
        [
            'title' => 'Payroll Management',
            'desc' => 'Automate payroll processing with clear checks and reliable outputs.',
            'target' => '#doc-core-workflows',
            'icon' => base_url('icons/payroll.png'),
            'image' => base_url('images/background/payroll_management.png'),
            'tone' => 'rgba(20, 30, 44, 0.58)',
            'size' => 'card-wide',
        ],
        [
            'title' => 'Bookkeeping',
            'desc' => 'Simplified financial records and audit-ready reporting workflows.',
            'target' => '#doc-core-workflows',
            'icon' => base_url('icons/activity.png'),
            'image' => base_url('images/background/bookkeeping.png'),
            'tone' => 'rgba(40, 34, 24, 0.54)',
            'size' => 'card-medium',
        ],
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
        pointer-events: none;
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

    .about-title .icon, .feature-badge .icon, .faq-head .icon{
        width: 55px;
        height: 55px;
    }

    .section-gray {
        background: rgba(255, 255, 255, 0.5);
    }

    .about-wrap {
        padding-top: clamp(18px, 3vw, 34px);
        padding-bottom: clamp(18px, 3vw, 34px);
    }

    .about-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(280px, 0.8fr);
        gap: 24px;
        align-items: center;
    }

    .about-title {
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: clamp(2rem, 4vw, 3.25rem);
        font-weight: 700;
        color: var(--help-text);
        margin-bottom: 10px;
        letter-spacing: -0.03em;
    }

    .about-copy {
        border-left: 3px solid rgba(79, 127, 224, 0.4);
        padding-left: 16px;
        font-size: 1rem;
        color: var(--help-muted);
        line-height: 1.8;
    }

    .about-img {
        width: 100%;
        height: 100%;
        min-height: 280px;
        max-height: 360px;
        object-fit: cover;
        display: block;
        border-radius: 20px;
        box-shadow: var(--help-shadow);
    }

    .feature-wrap {
        padding-top: clamp(18px, 3vw, 32px);
        padding-bottom: clamp(18px, 3vw, 32px);
    }

    .feature-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 16px;
    }

    .feature-head h3 {
        font-size: clamp(1.6rem, 3vw, 2.45rem);
        font-weight: 700;
        color: var(--help-text);
        letter-spacing: -0.03em;
    }

    .feature-head small {
        display: block;
        font-size: 0.85rem;
        color: var(--help-muted);
        font-weight: 400;
        margin-top: 4px;
    }

    .feature-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: clamp(1.2rem, 2vw, 1.7rem);
        font-weight: 600;
        color: var(--help-text);
    }

    .feature-badge svg {
        width: 28px;
        height: 28px;
        color: var(--help-accent);
    }
    .feature-topic-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 18px;
        margin-top: 18px;
    }

    .feature-card {
        position: relative;
        min-height: 180px;
        border-radius: 18px;
        padding: 22px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        text-decoration: none;
        color: #fff;
        box-shadow: 0 20px 40px rgba(17,24,39,0.16);
        transition: transform 0.22s ease, box-shadow 0.22s ease;
        background: transparent;
    }

    .feature-card.card-standard { grid-column: span 2; }
    .feature-card.card-wide { grid-column: span 4; min-height: 220px; }
    .feature-card.card-medium { grid-column: span 2; min-height: 220px; }

    .feature-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image: linear-gradient(to top, rgba(6,10,18,0.78) 8%, rgba(6,10,18,0.28) 52%, var(--topic-tone, rgba(10,20,40,0.5)) 100%), var(--topic-image);
        background-size: cover;
        background-position: center;
        transform: scale(1.02);
        transition: transform 0.22s ease;
        z-index: 0;
    }

    .feature-card::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0));
        z-index: 0;
    }

    .feature-card:hover { transform: translateY(-6px); box-shadow: 0 34px 64px rgba(17,24,39,0.24); }
    .feature-card:hover::before { transform: scale(1.07); }

    .feature-card > * { position: relative; z-index: 1; }

    .feature-card .card-icon { width:46px; height:46px; border-radius:999px; background:#fff; display:grid; place-items:center; box-shadow:0 8px 18px rgba(0,0,0,0.18); }
    .feature-card .card-icon img { width:20px; height:20px; }

    .feature-card .name { font-size:1.05rem; font-weight:700; color:#fff; margin-top:8px; text-shadow:0 3px 18px rgba(0,0,0,0.5); }
    .feature-card .desc { font-size:0.92rem; color: rgba(255,255,255,0.92); margin-top:8px; line-height:1.45; text-shadow:0 2px 12px rgba(0,0,0,0.35); }

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

    .doc-article {
        border-radius: 10px;
        padding: 18px;
        background: var(--help-surface);
        border: 1px solid var(--help-border);
        box-shadow: 0 8px 20px rgba(15,23,36,0.04);
        margin-bottom: 14px;
    }

    .doc-article h2 { font-size: 1.25rem; margin-bottom: 6px; }
    .doc-article h3 { font-size: 1rem; margin-top: 10px; }

    /* Featured Guides & Resources Styles */
    .featured-guides-wrap {
        padding-top: clamp(28px, 4vw, 48px);
        padding-bottom: clamp(28px, 4vw, 48px);
        background: rgba(255, 255, 255, 0.5);
    }

    .guide-item {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 48px;
        align-items: center;
        margin-bottom: 64px;
    }

    .guide-item-reverse {
        direction: rtl;
    }

    .guide-item-reverse > * {
        direction: ltr;
    }

    .guide-content {
        padding: 12px 0;
    }

    .guide-image {
        min-height: 340px;
        background: var(--help-surface-strong);
        border: 1px solid var(--help-border);
        border-radius: 20px;
        padding: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: var(--help-shadow);
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
        .feature-head {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .top-card,
        .feature-card {
            min-height: auto;
        }

        .feature-card .name {
            margin-top: 32px;
        }

        .map-box {
            min-height: 280px;
        }

        .map-link-btn {
            left: 12px;
            bottom: 12px;
        }

        .guide-item {
            grid-template-columns: 1fr;
            gap: 24px;
            margin-bottom: 48px;
        }

        .guide-item-reverse {
            direction: ltr;
        }

        .guide-image {
            min-height: 280px;
            padding: 24px;
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
        .feature-card,
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

        .feature-card .name {
            margin-top: 24px;
        }
    }
</style>

<div class="help-shell hide-scroll-bar">
    <section class="hero-wrap">
        <div class="help-container">
            <h1 class="hero-title">Help Centre</h1>
            <div class="hero-search">
                <input type="text" placeholder="Start typing your search" aria-label="Search help" />
                <span class="hero-search-dot" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="2"></circle>
                        <path d="M16.2 16.2L20 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                </span>
            </div>
            <p class="hero-sub">Choose a topic below to learn more about the system</p>

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

    <section id="about-system" class="section-gray about-wrap">
        <div class="help-container about-grid">
            <div>
                <div class="about-title">
                    <img src="<?= base_url('icons/help-about.png') ?>" class="icon">
                    About OTIS
                </div>
                <div class="about-copy">
                    <strong>OTIS (One Transaction Integrated System)</strong> is a comprehensive accounting solution designed to centralize and automate core financial operations by integrating payroll, bookkeeping, and tax management into a single, unified environment. By eliminating the need for disparate systems, OTIS keeps every transaction synchronized across essential records.
                </div>
            </div>
            <div>
                <img src="<?= base_url('images/background/building.png') ?>" alt="About OTIS" class="about-img" />
            </div>
        </div>
    </section>

    <section id="feature-grid" class="feature-wrap">
        <div class="help-container">
            <div class="feature-head">
                <h3>Explore Support Topics<small>Can’t find what you’re looking for?</small></h3>
            </div>

            <div class="feature-topic-grid">
                <?php foreach ($featureCards as $item): ?>
                    <a
                        href="<?= esc($item['target']) ?>"
                        class="feature-card <?= esc($item['size'] ?? 'card-standard') ?>"
                        style="--topic-image: url('<?= esc($item['image'] ?? base_url('images/background/building.png')) ?>'); --topic-tone: <?= esc($item['tone'] ?? 'rgba(10,20,40,0.5)') ?>;"
                    >
                        <div class="card-icon">
                            <img src="<?= esc($item['icon'] ?? base_url('icons/help-features.png')) ?>" alt="">
                        </div>
                        <div class="name"><?= esc($item['title']) ?></div>
                        <div class="desc"><?= esc($item['desc']) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="documentation" class="featured-guides-wrap">
        <div class="help-container">
            <!-- Featured Guides Section -->
            <div style="margin-bottom: 48px;">
                <h2 style="font-size: clamp(1.9rem, 4vw, 2.8rem); font-weight: 700; color: var(--help-text); letter-spacing: -0.03em; margin-bottom: 32px;">Featured Guides</h2>

                <!-- Access & Login Guide -->
                <div class="guide-item">
                    <div class="guide-content">
                        <h3 style="font-size: 1.8rem; font-weight: 700; color: var(--help-text); margin-bottom: 16px; letter-spacing: -0.02em;">Access & Login</h3>
                        <p style="font-size: 1.05rem; color: var(--help-muted); line-height: 1.7; margin-bottom: 20px;">Get started with your account. Learn how to sign in securely, recover forgotten passwords, and set up multi-factor authentication for enhanced security.</p>
                        <a href="#doc-access" style="display: inline-block; padding: 12px 24px; background: var(--help-accent); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.95rem; transition: all 0.18s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 12px 24px rgba(79, 127, 224, 0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">Read Guide →</a>
                    </div>
                    <div class="guide-image">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, rgba(79, 127, 224, 0.15), rgba(79, 127, 224, 0.05)); border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                            <svg viewBox="0 0 200 200" style="width: 140px; height: 140px; opacity: 0.6;">
                                <circle cx="100" cy="70" r="35" fill="currentColor" color="rgba(79, 127, 224, 0.4)"/>
                                <rect x="50" y="120" width="100" height="50" rx="8" fill="currentColor" color="rgba(79, 127, 224, 0.3)"/>
                                <circle cx="80" cy="135" r="6" fill="currentColor" color="rgba(79, 127, 224, 0.6)"/>
                                <circle cx="120" cy="135" r="6" fill="currentColor" color="rgba(79, 127, 224, 0.6)"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Troubleshooting Guide -->
                <div class="guide-item guide-item-reverse">
                    <div class="guide-image">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, rgba(79, 127, 224, 0.15), rgba(79, 127, 224, 0.05)); border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                            <svg viewBox="0 0 200 200" style="width: 140px; height: 140px; opacity: 0.6;">
                                <path d="M 60 80 L 100 120 L 140 80" stroke="currentColor" stroke-width="8" fill="none" stroke-linecap="round" stroke-linejoin="round" color="rgba(79, 127, 224, 0.5)"/>
                                <circle cx="100" cy="150" r="8" fill="currentColor" color="rgba(79, 127, 224, 0.6)"/>
                            </svg>
                        </div>
                    </div>
                    <div class="guide-content">
                        <h3 style="font-size: 1.8rem; font-weight: 700; color: var(--help-text); margin-bottom: 16px; letter-spacing: -0.02em;">Troubleshooting</h3>
                        <p style="font-size: 1.05rem; color: var(--help-muted); line-height: 1.7; margin-bottom: 20px;">Encounter an issue? Find quick fixes for common problems, browser-specific issues, and learn when to escalate to our support team.</p>
                        <a href="#doc-troubleshooting" style="display: inline-block; padding: 12px 24px; background: var(--help-accent); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.95rem; transition: all 0.18s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 12px 24px rgba(79, 127, 224, 0.3)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">Read Guide →</a>
                    </div>
                </div>
            </div>

            <!-- More Resources Section -->
            <div>
                <h2 style="font-size: clamp(1.9rem, 4vw, 2.8rem); font-weight: 700; color: var(--help-text); letter-spacing: -0.03em; margin-bottom: 32px;">More Resources</h2>
                
                <div class="guides-grid">
                    <!-- Core Workflows Card -->
                    <a href="#doc-core-workflows" class="guide-card">
                        <div class="guide-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                <path d="M9 12h6M9 16h6"/>
                            </svg>
                        </div>
                        <div class="guide-card-meta">MAY 4, 2026</div>
                        <h3>Core Workflows</h3>
                        <p>Top user transactions and step-by-step guides for completing common tasks in the system.</p>
                    </a>

                    <!-- Support & SLA Card -->
                    <a href="#doc-support" class="guide-card">
                        <div class="guide-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 21H4a2 2 0 01-2-2V7a2 2 0 012-2h5l2-3h2l2 3h5a2 2 0 012 2v12a2 2 0 01-2 2z"/>
                                <circle cx="12" cy="13" r="3"/>
                            </svg>
                        </div>
                        <div class="guide-card-meta">MAY 4, 2026</div>
                        <h3>Support & SLA</h3>
                        <p>Contact channels, expected response times, and our service level agreements for various priority levels.</p>
                    </a>

                    <!-- Release Notes Card -->
                    <a href="#doc-release-notes" class="guide-card">
                        <div class="guide-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M13 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V9z"/>
                                <polyline points="13 2 13 9 20 9"/>
                                <line x1="8" y1="13" x2="16" y2="13"/>
                                <line x1="8" y1="17" x2="16" y2="17"/>
                            </svg>
                        </div>
                        <div class="guide-card-meta">MAY 4, 2026</div>
                        <h3>Release Notes</h3>
                        <p>Recent changes, new features, bug fixes, and known limitations of the current system version.</p>
                    </a>
                </div>
            </div>

            <!-- Documentation Content (Hidden initially, shown on click) -->
            <div style="margin-top: 48px; display: none;" id="doc-content">
                <?php include __DIR__ . '/docs/access.php'; ?>
                <?php include __DIR__ . '/docs/core_workflows.php'; ?>
                <?php include __DIR__ . '/docs/troubleshooting.php'; ?>
                <?php include __DIR__ . '/docs/support.php'; ?>
                <?php include __DIR__ . '/docs/release_notes.php'; ?>
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
                <a href="#about-system">Privacy Policy</a>
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

<?= $this->endSection() ?>