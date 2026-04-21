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
            'title' => 'About OTIS',
            'desc' => 'Learn how OTIS supports your daily operations.',
            'target' => '#about-system',
        ],
        [
            'title' => 'Support Basics',
            'desc' => 'Know where to ask and what information to provide.',
            'target' => '#contact-section',
        ],
        [
            'title' => 'System Features',
            'desc' => 'Discover tools built to streamline your workflow.',
            'target' => '#contact-section',
        ],
        [
            'title' => 'Contact Team',
            'desc' => 'Send concerns directly to our support staff.',
            'target' => '#contact-section',
        ],
        [
            'title' => 'Request History',
            'desc' => 'Track your submitted concerns and updates.',
            'target' => '#request-history',
        ],
    ];
?>

<style>
    .help-shell {
        background: #e8e5e7;
        height: 100%;
        min-height: 0;
        overflow-y: auto;
    }

    .help-container {
        max-width: 880px;
        margin: 0 auto;
        padding: 0 18px;
    }

    .hero-wrap {
        padding-top: 26px;
        padding-bottom: 18px;
        text-align: center;
    }

    .hero-title {
        font-size: 52px;
        line-height: 1;
        font-weight: 800;
        color: #111;
        margin-bottom: 14px;
    }

    .hero-search {
        max-width: 430px;
        margin: 0 auto;
        position: relative;
    }

    .hero-search input {
        width: 100%;
        height: 42px;
        border-radius: 999px;
        border: 1px solid #c6c6c6;
        background: #efefef;
        padding: 0 44px 0 16px;
        font-size: 14px;
        color: #4b4b4b;
        outline: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.12);
    }

    .hero-search-dot {
        position: absolute;
        right: 5px;
        top: 50%;
        transform: translateY(-50%);
        width: 24px;
        height: 24px;
        border-radius: 999px;
        border: 1px solid #c9c9c9;
        background: #ddd;
    }

    .hero-sub {
        font-size: 12px;
        color: #7e7e7e;
        margin: 14px 0 12px;
    }

    .top-cards {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .top-card {
        background: #f4f4f4;
        border: 1px solid #b6b6b6;
        border-radius: 10px;
        text-decoration: none;
        color: #111;
        min-height: 150px;
        padding: 16px 14px;
        box-shadow: 0 3px 7px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.18s ease;
    }

    .top-card:hover {
        transform: translateY(-2px);
    }

    .top-card-title {
        font-size: 18px;
        font-weight: 700;
    }

    .top-card-desc {
        font-size: 12px;
        color: #777;
        line-height: 1.35;
    }

    .top-card-arrow {
        margin-left: auto;
        width: 16px;
        height: 16px;
        color: #7094e0;
    }

    .icon-s {
        width: 42px;
        height: 42px;
        color: #7094e0;
    }

    .section-gray {
        background: #dcdcdc;
    }

    .about-wrap {
        padding-top: 20px;
        padding-bottom: 20px;
    }

    .about-grid {
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 14px;
        align-items: center;
    }

    .about-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 42px;
        font-weight: 700;
        color: #1f1f1f;
        margin-bottom: 8px;
    }

    .about-copy {
        border-left: 2px solid #8d8d8d;
        padding-left: 12px;
        font-size: 16px;
        color: #404040;
        line-height: 1.7;
    }

    .about-img {
        width: 100%;
        height: 300px;
        object-fit: cover;
        display: block;
    }

    .feature-wrap {
        padding-top: 18px;
        padding-bottom: 18px;
    }

    .feature-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .feature-head h3 {
        font-size: 34px;
        font-weight: 700;
        color: #2a2a2a;
    }

    .feature-head small {
        display: block;
        font-size: 12px;
        color: #8a8a8a;
        font-weight: 400;
        margin-top: 2px;
    }

    .feature-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 34px;
        font-weight: 600;
        color: #444;
    }

    .feature-badge svg {
        width: 34px;
        height: 34px;
        color: #6f95de;
    }

    .feature-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .feature-grid-2 {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .feature-card {
        min-height: 132px;
        border: 1px solid #b5b5b5;
        border-radius: 9px;
        background: #ececec;
        padding: 14px;
        text-decoration: none;
        color: #1f1f1f;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    }

    .feature-card .name {
        font-size: 20px;
        font-weight: 700;
        margin-top: 62px;
    }

    .feature-card .desc {
        font-size: 12px;
        color: #808080;
        margin-top: 2px;
    }

    .faq-wrap {
        padding-top: 18px;
        padding-bottom: 18px;
    }

    .faq-head {
        text-align: center;
        margin-bottom: 10px;
    }

    .faq-badge {
        width: 28px;
        height: 28px;
        border-radius: 999px;
        margin: 0 auto 8px;
        display: grid;
        place-items: center;
        background: #7094e0;
        color: #fff;
        font-size: 16px;
        font-weight: 700;
    }

    .faq-head h2 {
        font-size: 44px;
        font-weight: 800;
        color: #1c1c1c;
    }

    .faq-head p {
        font-size: 13px;
        color: #8b8b8b;
        margin-top: 2px;
    }

    .faq-list {
        max-width: 620px;
        margin: 0 auto;
    }

    .faq-item {
        border: 1px solid #ddd;
        border-radius: 5px;
        background: #fff;
        overflow: hidden;
        margin-bottom: 6px;
    }

    .faq-item summary {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 14px;
        font-size: 13px;
        font-weight: 700;
        color: #383838;
        cursor: pointer;
        list-style: none;
    }

    .faq-item summary::-webkit-details-marker {
        display: none;
    }

    .faq-item .answer {
        padding: 0 14px 12px;
        font-size: 12px;
        line-height: 1.55;
        color: #6f6f6f;
    }

    .contact-wrap {
        padding-top: 18px;
        padding-bottom: 20px;
    }

    .contact-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .map-box,
    .touch-box {
        background: #fff;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
    }

    .map-box {
        min-height: 360px;
        position: relative;
        background-image: linear-gradient(rgba(255,255,255,0.18), rgba(255,255,255,0.18)), url('<?= base_url('images/background/building.png') ?>');
        background-size: cover;
        background-position: center;
    }

    .map-pin {
        position: absolute;
        left: 48%;
        top: 56%;
        width: 20px;
        height: 20px;
        border-radius: 50% 50% 50% 0;
        transform: translate(-50%, -50%) rotate(-45deg);
        background: #ef4444;
        box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
    }

    .map-pin::after {
        content: '';
        position: absolute;
        inset: 5px;
        border-radius: 50%;
        background: #fff;
    }

    .touch-box {
        padding: 16px;
    }

    .touch-box h2 {
        font-size: 64px;
        font-weight: 700;
        color: #313131;
    }

    .touch-box p {
        font-size: 13px;
        color: #7f7f7f;
        margin-top: 4px;
        margin-bottom: 10px;
    }

    .contact-form {
        display: grid;
        gap: 8px;
    }

    .contact-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .contact-form input:not([type="checkbox"]),
    .contact-form select,
    .contact-form textarea {
        width: 100%;
        border: 1px solid #bcbcbc;
        border-radius: 3px;
        background: #f7f7f7;
        padding: 10px 10px;
        font-size: 12px;
        outline: none;
    }

    .contact-form textarea {
        min-height: 130px;
        resize: vertical;
    }

    .contact-form input:focus,
    .contact-form select:focus,
    .contact-form textarea:focus {
        border-color: #7094e0;
        box-shadow: 0 0 0 2px rgba(112, 148, 224, 0.15);
    }

    .contact-check {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 10.5px;
        color: #6b6b6b;
        line-height: 1.3;
        margin: 2px 0 4px;
    }

    .contact-check input[type="checkbox"] {
        width: 14px;
        height: 14px;
        min-width: 14px;
        margin: 0;
        padding: 0;
        border: 1px solid #9b9b9b;
        border-radius: 2px;
        accent-color: #7b7b7b;
        background: #fff;
        box-shadow: none;
    }

    .contact-check span {
        display: block;
    }

    .contact-btn {
        border: 0;
        border-radius: 4px;
        background: #ff4a4a;
        color: #fff;
        font-size: 14px;
        font-weight: 700;
        padding: 12px 14px;
        cursor: pointer;
    }

    .history-wrap {
        padding-top: 12px;
        padding-bottom: 30px;
    }

    .history-panel {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
        padding: 14px;
    }

    .footer-bar {
        background: #2f2f2f;
        color: #eee;
        font-size: 9px;
    }

    .footer-inner {
        max-width: 900px;
        margin: 0 auto;
        padding: 10px 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
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

    @media (max-width: 900px) {
        .top-cards,
        .feature-grid-3,
        .feature-grid-2,
        .contact-grid,
        .contact-row,
        .about-grid {
            grid-template-columns: 1fr;
        }

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
            margin-top: 16px;
        }
    }
</style>

<div class="help-shell">
    <section class="hero-wrap">
        <div class="help-container">
            <h1 class="hero-title">Help Centre</h1>
            <div class="hero-search">
                <input type="text" placeholder="Start typing your search" aria-label="Search help" />
                <span class="hero-search-dot" aria-hidden="true"></span>
            </div>
            <p class="hero-sub">Or choose and option below to find out more</p>

            <div class="top-cards">
                <?php foreach ($topCards as $item): ?>
                    <a href="<?= esc($item['target']) ?>" class="top-card">
                        <div class="icon-s" aria-hidden="true">
                            <?php if ($item['icon'] === 'about'): ?>
                                <svg viewBox="0 0 24 24" width="30" height="30" fill="none"><path d="M4 21V7l8-4 8 4v14H4z" fill="currentColor"/><path d="M11 9h2v2h-2zM11 13h2v2h-2zM11 17h2v4h-2z" fill="#fff"/></svg>
                            <?php elseif ($item['icon'] === 'faq'): ?>
                                <svg viewBox="0 0 24 24" width="30" height="30" fill="none"><circle cx="12" cy="12" r="9" fill="currentColor"/><path d="M10.9 9.5c0-1 .8-1.8 2-1.8 1.1 0 1.9.7 1.9 1.7 0 1.4-1.3 1.7-1.9 2.6-.2.3-.3.6-.3 1h-1.4c0-.6.1-1 .3-1.4.5-.9 1.9-1.4 1.9-2.2 0-.4-.3-.7-.8-.7-.6 0-.9.4-.9.9h-1.8zm.8 6.8c0-.6.4-.9.9-.9.6 0 1 .3 1 .9s-.4.9-1 .9c-.5 0-.9-.3-.9-.9z" fill="#fff"/></svg>
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" width="30" height="30" fill="none"><circle cx="6" cy="7" r="1.4" fill="currentColor"/><circle cx="6" cy="12" r="1.4" fill="currentColor"/><circle cx="6" cy="17" r="1.4" fill="currentColor"/><rect x="9.2" y="6.2" width="9.6" height="1.4" rx=".7" fill="currentColor"/><rect x="9.2" y="11.2" width="9.6" height="1.4" rx=".7" fill="currentColor"/><rect x="9.2" y="16.2" width="9.6" height="1.4" rx=".7" fill="currentColor"/></svg>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="top-card-title"><?= esc($item['title']) ?></div>
                            <div class="top-card-desc"><?= esc($item['desc']) ?></div>
                        </div>
                        <div class="top-card-arrow">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none"><path d="M5 12h13M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
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
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none"><path d="M4 21V7l8-4 8 4v14H4z" fill="#7094e0"/><path d="M11 9h2v2h-2zM11 13h2v2h-2zM11 17h2v4h-2z" fill="#fff"/></svg>
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
                <h3>Lorem Ipsum<small>Can’t find what you’re looking for?</small></h3>
                <div class="feature-badge">System Features
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 7h4M4 12h8M4 17h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="17" cy="7" r="1.4" fill="currentColor"/><circle cx="20" cy="12" r="1.4" fill="currentColor"/><circle cx="17" cy="17" r="1.4" fill="currentColor"/></svg>
                </div>
            </div>

            <div class="feature-grid-3">
                <?php foreach (array_slice($featureCards, 0, 3) as $item): ?>
                    <a href="<?= esc($item['target']) ?>" class="feature-card">
                        <div class="name"><?= esc($item['title']) ?></div>
                        <div class="desc"><?= esc($item['desc']) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="feature-grid-2">
                <?php foreach (array_slice($featureCards, 3, 2) as $item): ?>
                    <a href="<?= esc($item['target']) ?>" class="feature-card">
                        <div class="name"><?= esc($item['title']) ?></div>
                        <div class="desc"><?= esc($item['desc']) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="faq-list" class="section-gray faq-wrap">
        <div class="help-container">
            <div class="faq-head">
                <div class="faq-badge">?</div>
                <h2>Frequently Asked Questions <span style="font-size: 11px; color:#777; font-weight:500;">(FAQs)</span></h2>
                <p>Can’t find what you’re looking for?</p>
            </div>

            <div class="faq-list">
                <?php foreach ($faqs as $index => $faq): ?>
                    <details class="faq-item" <?= $index === 1 ? 'open' : '' ?>>
                        <summary>
                            <span><?= esc($faq['question']) ?></span>
                            <span><?= $index === 1 ? '-' : '+' ?></span>
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
                <div class="map-pin" aria-hidden="true"></div>
            </div>

            <div class="touch-box">
                <h2>Get in Touch</h2>
                <p>You can reach us anytime</p>

                <?php if (session()->getFlashdata('success')): ?>
                    <div class="mb-2 rounded border border-emerald-200 bg-emerald-50 px-2 py-2 text-[10px] text-emerald-700">
                        <?= esc(session()->getFlashdata('success')) ?>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="mb-2 rounded border border-red-200 bg-red-50 px-2 py-2 text-[10px] text-red-700">
                        <?= esc(session()->getFlashdata('error')) ?>
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

    <section id="request-history" class="history-wrap">
        <div class="help-container history-panel">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-900">Request History</h3>
                <span class="text-[10px] text-gray-500"><?= count($helpRequests) ?> total</span>
            </div>

            <div class="space-y-2 max-h-[240px] overflow-y-auto pr-1">
                <?php if (empty($helpRequests)): ?>
                    <div class="rounded border border-dashed border-gray-300 bg-gray-50 p-3 text-[10px] text-gray-500 text-center">No support request submitted yet.</div>
                <?php else: ?>
                    <?php foreach ($helpRequests as $request): ?>
                        <?php
                            $requestStatus = (string) ($request['status'] ?? 'open');
                            $requestPriority = (string) ($request['priority'] ?? 'normal');
                            $statusClass = $statusClasses[$requestStatus] ?? 'bg-slate-100 text-slate-700';
                            $priorityClass = $priorityClasses[$requestPriority] ?? 'bg-slate-100 text-slate-700';
                        ?>
                        <div class="rounded border border-gray-200 bg-gray-50 p-2">
                            <div class="flex items-center gap-1.5 mb-1">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-semibold <?= esc($statusClass) ?>"><?= esc($statusLabels[$requestStatus] ?? ucwords(str_replace('_', ' ', $requestStatus))) ?></span>
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-semibold <?= esc($priorityClass) ?>"><?= esc(ucfirst($requestPriority)) ?></span>
                            </div>
                            <div class="text-[10px] font-semibold text-gray-900">#<?= esc((string) ($request['id'] ?? '')) ?> - <?= esc((string) ($request['subject'] ?? 'No subject')) ?></div>
                            <div class="text-[9px] text-gray-500 mt-0.5"><?= esc(date('M d, Y h:i A', strtotime((string) ($request['created_at'] ?? 'now')))) ?></div>
                            <?php if (! empty($request['response'])): ?>
                                <div class="mt-1 rounded bg-emerald-50 border border-emerald-200 p-1.5 text-[9px] text-emerald-800"><?= nl2br(esc((string) $request['response'])) ?></div>
                            <?php endif; ?>

                            <?php if ($isPayrollStaff): ?>
                                <form action="<?= site_url('client_portal/help/update/' . (int) ($request['id'] ?? 0)) ?>" method="post" class="grid gap-1 mt-1.5">
                                    <?= csrf_field() ?>
                                    <select name="status" class="rounded border border-gray-300 px-2 py-1 text-[9px] outline-none focus:border-blue-400">
                                        <?php foreach ($statusLabels as $value => $label): ?>
                                            <option value="<?= esc($value) ?>" <?= $requestStatus === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <textarea name="response" rows="2" maxlength="2000" placeholder="Staff response" class="rounded border border-gray-300 px-2 py-1 text-[9px] outline-none focus:border-blue-400"><?= esc((string) ($request['response'] ?? '')) ?></textarea>
                                    <button type="submit" class="rounded bg-[#7094e0] px-2 py-1 text-[9px] font-semibold text-white hover:bg-[#5f85d6] transition">Update</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
