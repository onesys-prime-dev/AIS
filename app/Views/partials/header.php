<!-- FONT AWESOME -->
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<header class="app-header">
    <!-- LEFT SIDE -->
    <div class="header-left">
        <button type="button" id="sidebarToggleBtn" class="sidebar-toggle-btn" aria-label="Open menu" aria-controls="appSidebar" aria-expanded="false" onclick="openMobileSidebar()">
            <i class="fa-solid fa-bars"></i>
        </button>
        <form class="search-box" action="<?= site_url('client_portal/help') ?>" method="get" id="headerSearchForm" data-search-index-url="<?= site_url('client_portal/help/search-index') ?>" autocomplete="off">
            <button type="submit" class="search-icon-btn" aria-label="Search Help Centre">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
            </button>
            <input
            type="text"
            name="q"
            id="headerSearchInput"
            placeholder="What are you looking for?"
            class="search-input"
            autocomplete="off"
            >
            <div id="headerSearchResults" class="header-search-results" role="listbox" hidden></div>
        </form>
        <button type="button" id="aiHelperOpenBtn" class="ai-helper-open-btn" aria-label="Ask the OTIS Help Assistant">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
            <span>Ask</span>
        </button>
    </div>

    <!-- RIGHT SIDE -->
    <div class="header-right">
        <!-- BELL -->
        <div class="header-notif-wrapper">
            <button class="header-notif-btn" onclick="toggleNotif()">
                <img src="<?=base_url('icons/notifications-bell.png')?>">
                <?php
                    $unreadNotifCount = 0;
                    foreach ($notifications ?? [] as $n) {
                        if (empty($n['is_viewed'])) {
                            $unreadNotifCount++;
                        }
                    }
                ?>
                <span id="headerNotifBadgeSlot">
                <?php if ($unreadNotifCount > 0): ?>
                    <span class="header-notif-badge">
                <?= $unreadNotifCount ?>
                    </span>
                <?php endif; ?>
                </span>
            </button>

            <!-- BELL DROPDOWN -->
            <div id="notifDropdown" class="header-notif-dropdown">
                <div class="header-notif-header">
                    <span class="header-notif-heading">Notifications</span>
                    <button type="button" class="header-notif-mark-read" onclick="markAllNotificationsAsRead()">
                        <img src="<?= base_url('icons/double-check.png') ?>" class="double-check-icon">
                        Mark all as read
                    </button>
                </div>

                <div class="header-notif-section-label">Today</div>

                <div id="headerNotifList" class="header-notif-list notif-scroll-hide">
                        <?php if (!empty($notifications)): ?>
                            <?php foreach($notifications as $n): ?>
                                <?php
                                    $notif = notification_data($n);
                                    $notifTime = (string) ($n['time_ago'] ?? '');
                                    $notifTitle = (string) $notif['title'];
                                    $notifSummary = (string) $notif['details'];

                                    $notifAttachments = $notif['attachments'] ?? [];
                                    $notifFirstAttachment = $notifAttachments[0] ?? null;
                                    $notifPreviewIsImage = $notifFirstAttachment
                                        && strpos(strtolower((string) ($notifFirstAttachment['mime_type'] ?? '')), 'image/') === 0;
                                ?>
                                <div
                                    class="header-notif-item <?= ! empty($n['is_viewed']) ? 'header-notif-viewed' : '' ?>"
                                    role="button"
                                    tabindex="0"
                                    data-id="<?= esc((string) $notif['id'], 'attr') ?>"
                                    data-title="<?= esc($notif['title'], 'attr') ?>"
                                    
                                    data-details="<?= esc($notif['details'], 'attr') ?>"
                                    data-publish="<?= esc($notif['published_at'] !== '' ? date('M d, Y h:i A', strtotime($notif['published_at'])) : 'N/A', 'attr') ?>"
                                    data-category="<?= esc($notif['category'], 'attr') ?>"
                                    data-priority="<?= esc(ucfirst($notif['priority']), 'attr') ?>"
                                    data-audience="<?= esc(ucfirst($notif['audience']), 'attr') ?>"
                                    data-status="<?= esc($notif['status'], 'attr') ?>"
                                    data-source="<?= esc($notif['source'], 'attr') ?>"
                                    data-created-by="<?= esc((string) $notif['created_by'], 'attr') ?>"
                                    data-request-id="<?= esc((string) $notif['request_id'], 'attr') ?>"
                                    data-actor-name="<?= esc($notif['actor_name'], 'attr') ?>"
                                    data-actor-position="<?= esc($notif['actor_position'], 'attr') ?>"
                                    data-author-name="<?= esc($notif['author_name'], 'attr') ?>"
                                    data-author-photo="<?= esc($notif['author_photo'], 'attr') ?>"
                                    data-requester-name="<?= esc($notif['requester_name'], 'attr') ?>"
                                    data-requester-department="<?= esc($notif['requester_department'], 'attr') ?>"
                                    data-requester-photo="<?= esc($notif['requester_photo'], 'attr') ?>"
                                    data-request-type="<?= esc($notif['request_type'], 'attr') ?>"
                                    data-attachments="<?= esc(json_encode($notif['attachments'] ?? []), 'attr') ?>"
                                    onclick="openNotificationModal(this)"
                                    onkeydown="handleNotificationKey(event, this)"
                                >
                                    <div class="header-notif-icon-wrap <?= $notifPreviewIsImage ? 'header-notif-icon-thumb' : '' ?>">
                                        <?php if ($notifPreviewIsImage): ?>
                                            <img src="<?= esc($notifFirstAttachment['file_path'], 'attr') ?>" alt="" class="header-notif-thumb-img" loading="lazy">
                                        <?php else: ?>
                                            <i class="fa-regular fa-bell"></i>
                                        <?php endif; ?>
                                        <?php if ($notifFirstAttachment): ?>
                                            <span class="header-notif-attach-badge" title="Has attachment">
                                                <i class="fa-solid fa-paperclip"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="header-notif-content">
                                        <div class="header-notif-topline">
                                            <div class="header-notif-title-wrap">
                                                <div class="header-notif-title"><?= esc($notifTitle) ?></div>
                                                <span class="header-notif-dot"></span>
                                            </div>
                                            <?php if ($notifTime !== ''): ?>
                                                <span class="header-notif-time"><?= esc($notifTime) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="header-notif-desc"><?= esc($notifSummary) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="header-notif-empty">No notifications</div>
                        <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- USER -->
        <div class="user-wrapper">
            <?php $user = auth()->user(); ?>
            <?php
                $headerIdentity = $headerIdentity ?? [];
                $photoUrl = (string) ($headerIdentity['photo_url'] ?? '');
                $displayName = (string) ($headerIdentity['display_name'] ?? ($user->username ?? 'User'));
                $displayEmail = (string) ($headerIdentity['display_email'] ?? ($user->email ?? ''));
                $avatarSource = trim((string) ($displayName !== '' ? $displayName : $displayEmail));
                $avatarInitials = strtoupper(substr($avatarSource !== '' ? $avatarSource : 'U', 0, 2));

                $headerAccountActive  = ($headerIdentity['status'] ?? '') === 'Active';
                $headerOnlineStatus   = $headerIdentity['online_status'] ?? 'Offline';
                $headerPresenceBadges = ['Online' => 'bg-green-200 text-green-700', 'Away' => 'bg-yellow-200 text-yellow-700', 'Offline' => 'bg-gray-200 text-gray-500'];
                $headerBadgeClass     = $headerAccountActive ? ($headerPresenceBadges[$headerOnlineStatus] ?? $headerPresenceBadges['Offline']) : 'bg-red-200 text-red-600';
                $headerBadgeText      = $headerAccountActive ? $headerOnlineStatus : 'Inactive';
                $headerEmployeeId     = $headerIdentity['employee_id'] ?? '';
                $headerPresenceAttrs  = $headerAccountActive ? "data-presence-badge=\"{$headerEmployeeId}\" data-current-status=\"{$headerOnlineStatus}\"" : '';
            ?>
            <div class="user-profile" onclick="toggleUserMenu()">
                <div class="header-avatar-wrap">
                    <?php if ($photoUrl !== ''): ?>
                        <div class="avatar avatar-photo" style="background-image:url('<?= esc($photoUrl) ?>');" aria-label="Profile photo"></div>
                    <?php else: ?>
                        <div class="avatar">
                            <?= esc($avatarInitials) ?>
                        </div>
                    <?php endif; ?>
                    <span class="header-avatar-status-dot <?= $headerBadgeClass ?>" <?= $headerPresenceAttrs ?> title="<?= esc($headerBadgeText) ?>"></span>
                </div>
                <div class="user-info">
                    <div class="user-name"><?= esc($displayName !== '' ? $displayName : 'User') ?></div>
                    <div class="user-email"><?= esc($displayEmail) ?></div>
                </div>
                <i class="fa-solid fa-chevron-down dropdown-icon"></i>
            </div>

            <!-- USER DROPDOWN -->
            <div id="userDropdown" class="user-dropdown">
                <div class="user-dropdown-identity">
                    <div class="user-dropdown-name"><?= esc($displayName !== '' ? $displayName : 'User') ?></div>
                    <div class="user-dropdown-email"><?= esc($displayEmail) ?></div>
                    <span class="user-dropdown-status <?= $headerBadgeClass ?>" <?= $headerPresenceAttrs ?>>
                        <span class="status-dot"></span><span data-presence-label><?= esc($headerBadgeText) ?></span>
                    </span>
                </div>
                <a href="<?= site_url('client_portal/profile') ?>">Account &amp; settings</a>
                <a href="<?= site_url('logout') ?>">Logout</a>
            </div>
        </div>
    </div>
</header>

<!-- AI HELPER CHAT PANEL — local doc/FAQ matcher only, no LLM, no external calls -->
<div id="aiHelperOverlay" class="ai-helper-overlay" hidden>
    <div class="ai-helper-panel" role="dialog" aria-modal="true" aria-labelledby="aiHelperTitle">
        <div class="ai-helper-header">
            <div class="ai-helper-header-text">
                <div class="ai-helper-header-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                <div>
                    <div id="aiHelperTitle" class="ai-helper-title">OTIS Help Assistant</div>
                    <div class="ai-helper-subtitle">Answers from the Help Centre — ask anything about the system</div>
                </div>
            </div>
            <div class="ai-helper-header-actions">
                <button type="button" id="aiHelperResetBtn" class="ai-helper-close-btn" aria-label="Start a new conversation" title="Start a new conversation">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
                <button type="button" id="aiHelperCloseBtn" class="ai-helper-close-btn" aria-label="Close">✕</button>
            </div>
        </div>

        <div id="aiHelperThread" class="ai-helper-thread"></div>

        <div class="ai-helper-input-row">
            <input type="text" id="aiHelperInput" class="ai-helper-input" placeholder="Ask a question about OTIS..." autocomplete="off">
            <button type="button" id="aiHelperSendBtn" class="ai-helper-send-btn" aria-label="Send">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<style>
    :root {
        --header-bg: #ffffff;
        --header-text: #000000;
        --header-shadow: 0 4px 12px rgba(0,0,0,0.1);

        --accent-violet: #7c3aed;
        --accent-violet-deep: #5b21b6;
        --accent-violet-soft: #f3e8ff;

        --search-bg: #DAD7D7;
        --search-text: #374151;
        --search-placeholder: #6b7280;

        --dropdown-bg: #ffffff;
        --dropdown-text: #374151;
    }

    /* =========================================================
    HEADER
    ========================================================= */

    .app-header{
        height:100px;
        background: var(--header-bg);

        display:flex;
        align-items:center;
        justify-content:space-between;

        padding:0 35px;

        font-family:Segoe UI, Arial;

        box-shadow: var(--header-shadow);

        position:relative;
        z-index:1000;
    }

    /* =========================================================
    SEARCH
    ========================================================= */

    .header-left{
        display:flex;
        align-items:center;
        gap:10px;
    }

    .search-box{
        position:relative;
        width:420px;
    }

    .ai-helper-open-btn{
        display:flex;
        align-items:center;
        gap:8px;

        height:42px;
        padding:0 16px;

        border:none;
        border-radius:999px;

        background:linear-gradient(135deg, #ef4444, #dc2626);
        color:#ffffff;

        font-size: calc(13px * var(--font-scale, 1));
        font-weight:700;

        cursor:pointer;

        box-shadow:0 6px 16px rgba(239,68,68,0.28);

        transition: transform 0.15s ease, box-shadow 0.15s ease;

        flex-shrink:0;
    }

    .ai-helper-open-btn:hover{
        transform:translateY(-1px);
        box-shadow:0 8px 20px rgba(239,68,68,0.35);
    }

    .ai-helper-open-btn i{
        font-size: calc(13px * var(--font-scale, 1));
    }

    .search-input{
        width:100%;
        height:42px;

        border:none;
        outline:none;

        border-radius:999px;

        background: var(--search-bg);

        padding-left:45px;

        font-size: calc(14px * var(--font-scale, 1));
        color: var(--search-text);
    }

    .search-input::placeholder{
        color: var(--search-placeholder);
        opacity:1;
    }

    .search-icon-btn{
        position:absolute;

        left:16px;
        top:50%;

        transform:translateY(-50%);

        border:none;
        background:none;
        padding:0;

        display:flex;
        align-items:center;
        justify-content:center;

        cursor:pointer;

        color: var(--search-placeholder);

        transition: color 0.15s ease;
    }

    .search-icon-btn:hover{
        color: var(--header-text);
    }

    /* =========================================================
    HEADER SEARCH RESULTS (local doc/FAQ matcher — no LLM, no
    external calls; see HelpController::searchIndex())
    ========================================================= */

    .header-search-results{
        position:absolute;

        left:0;
        right:0;
        top:calc(100% + 8px);

        background:#ffffff;
        border:1px solid #e5e7eb;
        border-radius:14px;

        box-shadow:0 18px 40px rgba(15,23,42,0.16);

        overflow:hidden;

        z-index:1001;
    }

    .header-search-result{
        display:block;

        padding:10px 16px;

        text-decoration:none;
        color:inherit;

        border-bottom:1px solid #f1f5f9;

        cursor:pointer;
    }

    .header-search-result:last-child{
        border-bottom:none;
    }

    .header-search-result:hover,
    .header-search-result.is-active{
        background:#f8fafc;
    }

    .header-search-result-title{
        display:flex;
        align-items:center;
        gap:8px;

        font-size: calc(13px * var(--font-scale, 1));
        font-weight:700;

        color:#111827;
    }

    .header-search-result-tag{
        font-size: calc(10px * var(--font-scale, 1));
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:0.04em;

        color:#ef4444;

        background:rgba(239,68,68,0.1);
        border-radius:999px;
        padding:2px 8px;

        flex-shrink:0;
    }

    .header-search-result-snippet{
        font-size: calc(12px * var(--font-scale, 1));
        color:#6b7280;

        margin-top:2px;

        overflow:hidden;
        text-overflow:ellipsis;
        display:-webkit-box;
        -webkit-line-clamp:2;
        -webkit-box-orient:vertical;
    }

    .header-search-empty{
        padding:14px 16px;

        font-size: calc(12px * var(--font-scale, 1));
        color:#6b7280;
        text-align:center;
    }

    .header-search-answer{
        display:block;

        padding:14px 16px;

        background:rgba(239,68,68,0.04);
        border-bottom:1px solid #e5e7eb !important;
    }

    .header-search-answer:hover,
    .header-search-answer.is-active{
        background:rgba(239,68,68,0.08);
    }

    .header-search-answer-label{
        font-size: calc(10px * var(--font-scale, 1));
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:0.06em;

        color:#ef4444;

        margin-bottom:4px;
    }

    .header-search-answer-text{
        font-size: calc(13px * var(--font-scale, 1));
        line-height:1.55;
        color:#374151;

        margin-top:6px;

        /* Full text, but capped so one very long article can't take over
           the whole dropdown — still much more than the old 2-line preview. */
        max-height:8.6em;
        overflow-y:auto;
    }

    .header-search-answer-link{
        font-size: calc(12px * var(--font-scale, 1));
        font-weight:700;
        color:#dc2626;

        margin-top:8px;
    }

    .header-search-related-label{
        padding:8px 16px 4px;

        font-size: calc(10px * var(--font-scale, 1));
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:0.06em;

        color:#9ca3af;
    }

    /* =========================================================
    RIGHT SIDE
    ========================================================= */

    .header-right{
        display:flex;
        align-items:center;

        gap:32px;

        height:100%;

        padding:20px;
    }

    /* =========================================================
    NOTIFICATION WRAPPER
    ========================================================= */

    .header-notif-wrapper{
        position:relative;

        padding:10px;

        border-radius:10px;

        transition: background-color 0.2s ease;
    }

    /* =========================================================
    NOTIFICATION BUTTON
    ========================================================= */

    .header-notif-btn{
        position:relative;

        background:none;
        border:none;

        font-size: calc(20px * var(--font-scale, 1));

        cursor:pointer;

        color: var(--header-text);
    }

    .header-notif-btn img{
        transition: transform 0.2s ease;
    }

    .header-notif-btn img:hover{
        transform: scale(1.08);
    }

    /* =========================================================
    NOTIFICATION BADGE
    ========================================================= */

    .header-notif-badge{
        position:absolute;

        top:-4px;
        right:-6px;

        width:16px;
        height:16px;

        border-radius:50%;

        background:#ef4444;
        color:white;

        font-size: calc(10px * var(--font-scale, 1));
        font-weight:600;

        display:flex;
        align-items:center;
        justify-content:center;
    }

    /* =========================================================
    NOTIFICATION DROPDOWN
    ========================================================= */

    .header-notif-dropdown{
        position:absolute;

        right:0;
        top:56px;

        width:400px;
        height:500px;

        background: var(--dropdown-bg);

        border-radius:14px;

        border:1px solid #e5e7eb;

        box-shadow:0 18px 40px rgba(15,23,42,0.16);

        display:none;

        overflow:hidden;

        padding:0 0 5px 0;

        z-index:99999;
    }

    /* =========================================================
    DROPDOWN HEADER
    ========================================================= */

    .header-notif-header{
        display:flex;
        align-items:center;
        justify-content:space-between;

        gap:10px;

        height:55px;

        padding:0 18px;

        background:#ffffff;

        border-bottom:1px solid #e5e7eb;

        box-sizing:border-box;
    }

    .header-notif-heading{
        font-size: calc(20px * var(--font-scale, 1));
        font-weight:700;

        color:var(--header-text);

        line-height:1;

        flex:0 0 auto;
    }

    /* =========================================================
    MARK ALL READ
    ========================================================= */

    .header-notif-mark-read{
        border:none;
        background:transparent;

        color:var(--accent-violet-deep);

        font-size: calc(12px * var(--font-scale, 1));
        font-weight:700;

        display:flex;
        align-items:center;

        gap:6px;

        padding:0 4px;

        height:26px;

        border-radius:5px;

        cursor:pointer;

        transition: color 0.2s ease;

        line-height:1;

        flex:0 0 auto;

        white-space:nowrap;
    }

    .header-notif-mark-read:hover{
        color:var(--accent-violet);
        background:#f3f4f6;
    }

    /* =========================================================
    SECTION LABEL
    ========================================================= */

    .header-notif-section-label{
        padding:10px 18px;

        background:#f3f4f6;

        border-bottom:1px solid #e5e7eb;

        color:#6b7280;

        font-size: calc(13px * var(--font-scale, 1));
        font-weight:700;

        box-shadow:0 10px 12px -12px rgba(31, 30, 9, 0.17);
    }

    /* =========================================================
    NOTIFICATION LIST
    ========================================================= */

    .header-notif-list{
        display:flex;
        flex-direction:column;

        align-items:stretch;
        justify-content:flex-start;

        height:calc(100% - 88px);

        overflow-y:auto;

        padding:0;
    }

    /* =========================================================
    HIDE SCROLLBAR
    ========================================================= */

    .notif-scroll-hide{
        scrollbar-width:none;
        -ms-overflow-style:none;
    }

    .notif-scroll-hide::-webkit-scrollbar{
        width:0;
        height:0;
    }

    /* =========================================================
    NOTIFICATION ITEM
    ========================================================= */

    .header-notif-item{
        display:flex;
        align-items:flex-start;

        gap:14px;

        padding:16px 18px;

        border-bottom:1px solid #eef2f7;

        transition: background-color 0.2s ease;
    }

    .header-notif-item:hover{
        background:var(--accent-violet-soft);
    }

    /* =========================================================
    NOTIFICATION ICON
    ========================================================= */

    .header-notif-icon-wrap{
        position:relative;

        width:42px;
        height:42px;

        border-radius:999px;

        background:#f8fafc;

        border:1px solid #e5e7eb;

        color:#64748b;

        display:flex;
        align-items:center;
        justify-content:center;

        flex-shrink:0;
    }

    /* =========================================================
    NOTIFICATION FILE PREVIEW THUMBNAIL
    ========================================================= */

    .header-notif-icon-thumb{
        padding:0;
        overflow:hidden;
    }

    .header-notif-thumb-img{
        width:100%;
        height:100%;

        object-fit:cover;
    }

    .header-notif-attach-badge{
        position:absolute;

        right:-3px;
        bottom:-3px;

        width:18px;
        height:18px;

        border-radius:50%;

        background:#ffffff;
        border:1px solid #e5e7eb;

        color:#7c3aed;
        font-size: calc(9px * var(--font-scale, 1));

        display:flex;
        align-items:center;
        justify-content:center;

        box-shadow:0 2px 4px rgba(15,23,42,0.12);
    }

    /* =========================================================
    NOTIFICATION CONTENT
    ========================================================= */

    .header-notif-content{
        flex:1;
        min-width:0;
    }

    .header-notif-topline{
        display:flex;
        align-items:center;
        justify-content:space-between;

        gap:10px;

        margin-bottom:4px;
    }

    .header-notif-title-wrap{
        display:flex;
        align-items:center;

        gap:8px;

        min-width:0;
    }

    .header-notif-dot{
        width:7px;
        height:7px;

        border-radius:50%;

        background:var(--accent-violet);

        flex-shrink:0;
    }

    .header-notif-item.header-notif-viewed .header-notif-dot{
        display:none;
    }

    .header-notif-title{
        font-size: calc(16px * var(--font-scale, 1));
        font-weight:700;

        color:var(--header-text);

        line-height:1.25;
    }

    .header-notif-desc{
        font-size: calc(14px * var(--font-scale, 1));
        color:#475569;
        line-height:1.45;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .header-notif-time{
        font-size: calc(13px * var(--font-scale, 1));

        color:#6b7280;

        white-space:nowrap;
    }

    /* =========================================================
    EMPTY STATE
    ========================================================= */

    .header-notif-empty{
        width:100%;

        margin:auto;

        padding:20px;

        text-align:center;

        color:#6b7280;

        font-size: calc(14px * var(--font-scale, 1));
    }

    /* =========================================================
    DOUBLE CHECK ICON
    ========================================================= */

    .double-check-icon{
        width:16px;
        height:16px;
    }

    /* =========================================================
    USER
    ========================================================= */

    .user-wrapper{
        position:relative;
    }

    .user-profile{
        display:flex;
        align-items:center;

        gap:12px;

        height:100%;

        padding:8px;

        border-radius:10px;

        cursor:pointer;

        transition:
            background-color 0.2s ease,
            transform 0.2s ease;
    }

    .user-profile:hover{
        background-color:#dad7d7;

        transform:translateY(-1px);
    }

    /* =========================================================
    AVATAR
    ========================================================= */

    .avatar{
        width:60px;
        height:60px;

        border-radius:50%;

        background:#e5e7eb;

        display:flex;
        align-items:center;
        justify-content:center;

        font-weight:600;
    }

    .avatar-photo{
        background-size:cover;
        background-position:center;
        background-repeat:no-repeat;
    }

    .header-avatar-wrap{
        position:relative;
        flex:0 0 auto;
    }

    .header-avatar-status-dot{
        position:absolute;
        right:0;
        bottom:0;
        width:14px;
        height:14px;
        border:2px solid #fff;
        border-radius:50%;
        background:currentColor;
    }

    .user-dropdown-status{
        display:inline-flex;
        align-items:center;
        margin-top:6px;
        padding:5px;
        border-radius:999px;
        width:fit-content;
    }

    /* The label text stays in the DOM (presence.js writes to it on live status
       changes) but is hidden — the dropdown just shows the color dot now. */
    .user-dropdown-status [data-presence-label]{
        display:none;
    }

    .user-dropdown-status .status-dot{
        width:7px;
        height:7px;
        flex:0 0 7px;
        border-radius:50%;
        background:currentColor;
    }

    /* =========================================================
    USER INFO
    ========================================================= */

    .user-info{
        display:flex;
        flex-direction:column;
    }

    .user-name{
        font-size: calc(14px * var(--font-scale, 1));
        font-weight:600;

        color:var(--header-text);
    }

    .user-email{
        font-size: calc(12px * var(--font-scale, 1));

        color:#9ca3af;
    }

    .dropdown-icon{
        font-size: calc(12px * var(--font-scale, 1));

        color:var(--header-text);
    }

    /* =========================================================
    USER DROPDOWN
    ========================================================= */

    .user-dropdown{
        position:absolute;

        right:0;
        top:100%;
        margin-top:8px;

        width:200px;

        background:var(--dropdown-bg);

        border-radius:10px;

        box-shadow:0 10px 30px rgba(0,0,0,0.15);

        display:none;

        overflow:hidden;

        z-index:9999;
    }

    /* Name/email — always here (not just on mobile), since the header's own
       .user-info text is now hidden below 768px (see the mobile section
       further down) and the avatar alone doesn't say whose account it is. */
    .user-dropdown-identity{
        padding:12px 15px;
        border-bottom:1px solid #f0f0f0;
    }

    .user-dropdown-name{
        font-weight:600;
        font-size: calc(13px * var(--font-scale, 1));
        color:var(--dropdown-text);
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }

    .user-dropdown-email{
        font-size: calc(11px * var(--font-scale, 1));
        color:#9ca3af;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
        margin-top:2px;
    }

    .user-dropdown a{
        display:block;

        padding:12px 15px;

        text-decoration:none;

        color:var(--dropdown-text);

        font-size: calc(14px * var(--font-scale, 1));

        transition:
            background 0.2s ease,
            color 0.2s ease;
    }

    .user-dropdown a:hover{
        background:#f3f4f6;
    }

    .user-dropdown a:first-child:hover{
        border-top-left-radius:10px;
        border-top-right-radius:10px;
    }

    .user-dropdown a:last-child:hover{
        border-bottom-left-radius:10px;
        border-bottom-right-radius:10px;
    }

    /* =========================================================
    AI HELPER CHAT PANEL
    ========================================================= */

    .ai-helper-overlay{
        position:fixed;
        inset:0;

        background:rgba(15,23,42,0.45);

        display:flex;
        align-items:flex-start;
        justify-content:flex-end;

        padding:24px;

        z-index:999999;
    }

    .ai-helper-overlay[hidden]{
        display:none;
    }

    .ai-helper-panel{
        width:400px;
        max-width:100%;
        height:min(640px, calc(100vh - 48px));

        background:#ffffff;
        border-radius:18px;

        box-shadow:0 30px 70px rgba(15,23,42,0.28);

        display:flex;
        flex-direction:column;
        overflow:hidden;
    }

    .ai-helper-header{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;

        padding:16px 18px;

        background:linear-gradient(135deg, #ef4444, #dc2626);
        color:#ffffff;
    }

    .ai-helper-header-text{
        display:flex;
        align-items:center;
        gap:10px;
    }

    .ai-helper-header-icon{
        width:34px;
        height:34px;

        border-radius:10px;

        background:rgba(255,255,255,0.18);

        display:flex;
        align-items:center;
        justify-content:center;

        flex-shrink:0;
    }

    .ai-helper-title{
        font-size: calc(14px * var(--font-scale, 1));
        font-weight:800;
    }

    .ai-helper-subtitle{
        font-size: calc(11px * var(--font-scale, 1));
        color:rgba(255,255,255,0.85);
        margin-top:1px;
    }

    .ai-helper-header-actions{
        display:flex;
        align-items:center;
        gap:14px;
        flex-shrink:0;
    }

    .ai-helper-close-btn{
        background:none;
        border:none;

        color:#ffffff;
        font-size: calc(15px * var(--font-scale, 1));

        cursor:pointer;

        opacity:0.85;
    }

    .ai-helper-close-btn:hover{
        opacity:1;
    }

    .ai-helper-thread{
        flex:1;
        min-height:0;

        overflow-y:auto;

        padding:16px;

        display:flex;
        flex-direction:column;
        gap:12px;

        background:#f9fafb;
    }

    .ai-helper-bubble{
        max-width:88%;

        padding:10px 13px;

        border-radius:14px;

        font-size: calc(13px * var(--font-scale, 1));
        line-height:1.55;

        white-space:pre-wrap;
    }

    .ai-helper-bubble-user{
        align-self:flex-end;

        background:#111827;
        color:#ffffff;

        border-bottom-right-radius:4px;
    }

    .ai-helper-bubble-assistant{
        align-self:flex-start;

        background:#ffffff;
        border:1px solid #e5e7eb;
        color:#111827;

        border-bottom-left-radius:4px;

        box-shadow:0 2px 6px rgba(15,23,42,0.04);
    }

    .ai-helper-bubble-assistant.is-thinking{
        color:#9ca3af;
        font-style:italic;
    }

    .ai-helper-source-tag{
        display:inline-block;

        font-size: calc(10px * var(--font-scale, 1));
        font-weight:700;
        text-transform:uppercase;
        letter-spacing:0.05em;

        color:#ef4444;
        background:rgba(239,68,68,0.1);

        border-radius:999px;
        padding:2px 8px;

        margin-bottom:6px;
    }

    .ai-helper-source-link{
        display:inline-block;

        margin-top:8px;

        font-size: calc(12px * var(--font-scale, 1));
        font-weight:700;
        color:#dc2626;
        text-decoration:none;
    }

    .ai-helper-source-link:hover{
        text-decoration:underline;
    }

    .ai-helper-chips{
        display:flex;
        flex-wrap:wrap;
        gap:6px;

        margin-top:8px;
    }

    .ai-helper-chip{
        border:1px solid #e5e7eb;
        background:#ffffff;

        color:#374151;
        font-size: calc(11px * var(--font-scale, 1));
        font-weight:600;

        padding:5px 11px;
        border-radius:999px;

        cursor:pointer;

        transition: background 0.15s ease, border-color 0.15s ease;
    }

    .ai-helper-chip:hover{
        background:rgba(239,68,68,0.08);
        border-color:rgba(239,68,68,0.3);
        color:#dc2626;
    }

    .ai-helper-input-row{
        display:flex;
        align-items:center;
        gap:8px;

        padding:12px;

        border-top:1px solid #e5e7eb;
        background:#ffffff;
    }

    .ai-helper-input{
        flex:1;

        height:38px;

        border:1px solid #e5e7eb;
        border-radius:999px;

        padding:0 16px;

        font-size: calc(13px * var(--font-scale, 1));
        color:#111827;

        outline:none;
    }

    .ai-helper-input:focus{
        border-color:rgba(239,68,68,0.4);
        box-shadow:0 0 0 3px rgba(239,68,68,0.1);
    }

    .ai-helper-send-btn{
        width:38px;
        height:38px;

        border:none;
        border-radius:999px;

        background:linear-gradient(135deg, #ef4444, #dc2626);
        color:#ffffff;

        display:flex;
        align-items:center;
        justify-content:center;

        cursor:pointer;

        flex-shrink:0;
    }

    .ai-helper-send-btn:hover{
        filter:brightness(1.05);
    }

    @media (max-width: 480px){
        .ai-helper-overlay{
            padding:0;
            align-items:stretch;
        }

        .ai-helper-panel{
            width:100%;
            height:100%;
            border-radius:0;
        }
    }

    /* MOBILE HEADER — .search-box alone was a fixed 420px (wider than most
       phones on its own), and the sidebar had no way to be hidden at all
       (see partials/sidebar.php), so the whole header just overflowed
       sideways on a small screen rather than adapting. */
    .sidebar-toggle-btn{
        display:none;
        background:none;
        border:none;
        font-size:20px;
        color:var(--header-text);
        cursor:pointer;
        padding:8px;
        flex-shrink:0;
        line-height:1;
    }

    @media (max-width: 900px){
        .sidebar-toggle-btn{
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .app-header{
            height:auto;
            min-height:64px;
            padding:10px 12px;
            gap:8px;
            display:grid;
            grid-template-columns:minmax(0, 1fr) auto;
        }

        .header-left{
            flex:1;
            min-width:0;
        }

        .header-right{
            gap:8px;
            height:auto;
            min-width:0;
            padding:0;
        }

        .header-notif-wrapper,
        .user-profile{
            padding:6px;
        }

        .avatar{
            width:40px;
            height:40px;
            font-size:13px;
        }

        .user-profile{
            gap:5px;
        }

        .search-box{
            width:auto;
            flex:1;
            min-width:0;
        }

        /* Icon + "Ask" label together no longer fit next to a flexible
           search box on a phone — keep the icon, drop the label. */
        .ai-helper-open-btn span{
            display:none;
        }

        /* Name/email text next to the avatar is the next thing to overflow
           once the search box and hamburger are already sharing the row —
           the avatar (still tappable, still opens the same dropdown with
           the name/email visible inside it) is enough on a phone. */
        .user-info{
            display:none;
        }

        .header-notif-dropdown{
            position:fixed;
            top:72px;
            left:8px;
            right:8px;
            width:auto;
            height:min(500px, calc(100dvh - 84px));
            max-height:calc(100dvh - 84px);
        }

        .user-dropdown{
            position:fixed;
            top:72px;
            right:8px;
            width:min(240px, calc(100vw - 16px));
        }
    }

    @media (max-width: 480px){
        .app-header{
            display:flex;
            flex-wrap:wrap;
            align-content:center;
            padding:8px 10px 10px;
            min-height:112px;
        }

        .header-left{
            order:2;
            width:100%;
            flex:0 0 100%;
            display:grid;
            grid-template-columns:44px minmax(0, 1fr) 44px;
            grid-template-areas:
                "menu spacer assistant"
                "search search search";
            gap:6px 8px;
            padding-right:96px;
        }

        .sidebar-toggle-btn{
            grid-area:menu;
            width:44px;
            height:44px;
            padding:0;
        }

        .search-box{
            grid-area:search;
            width:auto;
            margin-right:-96px;
        }

        .ai-helper-open-btn{
            grid-area:assistant;
            width:44px;
            height:44px;
            padding:0;
            justify-content:center;
        }

        .search-input{
            height:40px;
            padding-left:40px;
            padding-right:12px;
            font-size:13px;
        }

        .header-right{
            position:absolute;
            top:8px;
            right:8px;
            gap:2px;
        }

        .header-notif-wrapper,
        .user-profile{
            min-width:44px;
            min-height:44px;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:2px;
        }

        .dropdown-icon{
            display:none;
        }

        .header-notif-dropdown{
            top:120px;
            height:calc(100dvh - 128px);
            max-height:calc(100dvh - 128px);
            border-radius:12px;
        }

        .header-notif-header{
            height:auto;
            min-height:52px;
            padding:10px 12px;
        }

        .header-notif-mark-read{
            font-size:11px;
        }

        .user-dropdown{
            top:60px;
        }
    }

</style>

<script>
(function () {
    // Local doc/FAQ search widget for the header search box. No LLM, no
    // external calls — scores against HelpController::searchIndex()'s JSON
    // (FAQs + plain-text Help Centre articles), fetched once and cached
    // for the rest of the session.
    const form = document.getElementById('headerSearchForm');
    const input = document.getElementById('headerSearchInput');
    const resultsBox = document.getElementById('headerSearchResults');

    if (!form || !input || !resultsBox) {
        return;
    }

    let indexPromise = null;
    let activeIndex = -1;
    let currentMatches = [];

    function loadIndex() {
        if (indexPromise) {
            return indexPromise;
        }

        indexPromise = fetch(form.dataset.searchIndexUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (res) { return res.ok ? res.json() : { success: false }; })
            .then(function (data) { return (data && data.success) ? (data.entries || []) : []; })
            .catch(function () { return []; });

        return indexPromise;
    }

    // Filtered out before scoring — otherwise a question like "How do I
    // request leave?" scores mostly on "how"/"do"/"i", which appear in
    // nearly every entry and drown out what the question is actually about.
    const STOPWORDS = new Set([
        'a', 'an', 'the', 'is', 'are', 'was', 'were', 'be', 'been', 'am',
        'do', 'does', 'did', 'doing', 'i', 'me', 'my', 'you', 'your',
        'it', 'its', 'to', 'of', 'in', 'on', 'at', 'for', 'and', 'or',
        'how', 'what', 'why', 'when', 'where', 'who', 'can', 'will',
        'not', 'no', 'so', 'if', 'this', 'that', 'with', 'about',
    ]);

    function tokenize(query) {
        const words = query.toLowerCase().split(/\s+/).filter(Boolean);
        const meaningful = words.filter(function (w) { return !STOPWORDS.has(w); });
        // Fall back to the unfiltered words if stripping stopwords leaves
        // nothing (e.g. someone just types "how") rather than matching zero
        // entries.
        return meaningful.length > 0 ? meaningful : words;
    }

    // Whole-word match instead of a raw substring test — otherwise short
    // terms false-positive inside unrelated words (e.g. "ot" inside "not"/
    // "photo", "pay" inside "display"), which was a real source of the
    // assistant confidently answering with an unrelated FAQ/doc section.
    function escapeRegExp(s) {
        return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Optional trailing "s" so a singular query term still matches a plural
    // in the content and vice versa (e.g. "request" needs to match "Leave
    // Requests") — the fix for substring false-positives above would
    // otherwise have quietly broken this simple, very common case.
    function containsWord(haystack, term) {
        if (new RegExp('\\b' + escapeRegExp(term) + 's?\\b', 'i').test(haystack)) {
            return true;
        }
        // Typo tolerance: a real assistant doesn't lose "annoucement" or
        // "atendance" to zero matches just because a letter is missing or
        // swapped. Only for words long enough that a 1-edit fuzzy match is
        // still a meaningful signal (short words like "ot"/"ut" would false-
        // positive against half the dictionary at that tolerance).
        if (term.length < 5) {
            return false;
        }
        const words = haystack.toLowerCase().match(/[a-z]+/g) || [];
        return words.some(function (w) {
            return Math.abs(w.length - term.length) <= 1 && levenshtein(w, term) <= 1;
        });
    }

    // Classic edit-distance DP, capped at maxDist so a couple of retyped
    // words per query never becomes an expensive full scan over the index.
    function levenshtein(a, b) {
        if (Math.abs(a.length - b.length) > 1) return 2; // short-circuit: can't be within 1
        const dp = [];
        for (let i = 0; i <= a.length; i++) dp.push([i]);
        for (let j = 1; j <= b.length; j++) dp[0][j] = j;
        for (let i = 1; i <= a.length; i++) {
            for (let j = 1; j <= b.length; j++) {
                dp[i][j] = a[i - 1] === b[j - 1]
                    ? dp[i - 1][j - 1]
                    : 1 + Math.min(dp[i - 1][j - 1], dp[i - 1][j], dp[i][j - 1]);
            }
        }
        return dp[a.length][b.length];
    }

    // Real transcript that exposed this: asking "how to view payslip" lost
    // to the payslip-PASSWORD FAQ instead of "Why can't I see my payslip?",
    // purely because the query said "view" and that FAQ's title says "see" —
    // same concept, different word, so word-boundary matching alone still
    // picked the wrong answer. Each group below is treated as one concept:
    // if the query uses any word in a group, it's checked against all of
    // them in the title/snippet, not just the one the user happened to type.
    const SYNONYM_GROUPS = [
        ['view', 'see', 'check'],
        ['submit', 'apply', 'request', 'file', 'send'],
        ['login', 'signin', 'log', 'sign'],
        ['password', 'pw', 'pass'],
        ['ot', 'overtime'],
        ['ut', 'undertime'],
        ['approve', 'approval', 'authorize'],
        ['edit', 'update', 'change', 'modify'],
        ['add', 'create', 'new'],
        ['delete', 'remove', 'cancel'],
    ];
    const SYNONYM_LOOKUP = {};
    SYNONYM_GROUPS.forEach(function (group) {
        group.forEach(function (word) { SYNONYM_LOOKUP[word] = group; });
    });

    function expandTerm(term) {
        return SYNONYM_LOOKUP[term] || [term];
    }

    function scoreEntry(entry, terms, rawQuery) {
        const title = (entry.title || '').toLowerCase();
        const snippet = (entry.snippet || '').toLowerCase();
        let score = 0;

        terms.forEach(function (term) {
            const variants = expandTerm(term);
            if (variants.some(function (v) { return containsWord(title, v); })) score += 3;
            if (variants.some(function (v) { return containsWord(snippet, v); })) score += 1;
        });

        // Exact-phrase bonus: the whole query appearing verbatim is a much
        // stronger signal than its words matching separately. A phrase can
        // still use a plain substring check since it's long/specific enough
        // that word-boundary false positives aren't a real risk.
        if (rawQuery && rawQuery.trim().length >= 3) {
            const phrase = rawQuery.trim().toLowerCase();
            if (title.includes(phrase)) score += 6;
            else if (snippet.includes(phrase)) score += 3;
        }

        // Small, deliberate tie-breaker: a direct FAQ ("How do I view my
        // payslip?") is a more useful answer than an equally-scored
        // troubleshooting doc section ("Why can't I see my payslip?") when
        // the question was a plain how-to, not a "why isn't this working"
        // complaint — e.g. both matched "payslip" + "view/see" in their
        // title and would otherwise tie. Small enough to only ever affect
        // near-ties, never override a genuinely stronger doc match.
        if (entry.type === 'faq') score += 1;

        return score;
    }

    // Below this, the "best" match is really just one incidental snippet hit
    // — not something the assistant should present as a confident, direct
    // answer. (One term found only in a snippet scores 1; this requires more
    // than that, e.g. a title hit, an exact-phrase hit, or 3+ snippet hits.)
    const CONFIDENT_ANSWER_THRESHOLD = 3;

    // Small talk — a real assistant doesn't run "thanks" or "hi" through the
    // FAQ scorer (best case a weak/irrelevant match, worst case none at all,
    // either way a poor first impression). Checked before scoring, on the
    // raw text so punctuation ("Hi!", "thanks!!") doesn't need stripping by
    // the caller. Order matters: bye/thanks are checked before the very
    // loose greeting pattern so e.g. "thanks a lot" can't be mistaken for it.
    const SMALL_TALK = [
        { pattern: /^\s*(thanks|thank you|thx|ty|appreciate it)\b/i,
          replies: ['You\'re welcome! Anything else I can help with?', 'Anytime! Let me know if something else comes up.'] },
        { pattern: /^\s*(bye|goodbye|see ya|see you|later|that'?s all|that is all)\b/i,
          replies: ['Take care! Come back anytime you have a question.', 'Bye for now — I\'ll be here if you need anything else.'] },
        { pattern: /^\s*(hi|hello|hey|yo|good\s?(morning|afternoon|evening))\b[\s!.]*$/i,
          replies: ['Hi! What can I help you with in OTIS?', 'Hello! Ask me anything about attendance, requests, payroll, loans, or announcements.'] },
        { pattern: /how are you|how'?s it going/i,
          replies: ['I\'m just a Help Centre script, not a person, but I\'m ready to help! What do you need?'] },
    ];

    // Picks deterministically off the question text itself (not random) so
    // the same input always renders the same reply — real conversation
    // still varies because the input varies, but a test asking the same
    // question twice gets a stable, reproducible answer.
    function detectSmallTalk(text) {
        for (let i = 0; i < SMALL_TALK.length; i++) {
            if (SMALL_TALK[i].pattern.test(text)) {
                const replies = SMALL_TALK[i].replies;
                const idx = text.length % replies.length;
                return replies[idx];
            }
        }
        return null;
    }

    function buildResultLink(entry, extraClass) {
        const link = document.createElement('a');
        link.href = entry.url;
        link.className = 'header-search-result' + (extraClass ? ' ' + extraClass : '');
        link.setAttribute('role', 'option');

        const titleRow = document.createElement('div');
        titleRow.className = 'header-search-result-title';

        const titleText = document.createElement('span');
        titleText.textContent = entry.title;
        titleRow.appendChild(titleText);

        if (entry.tag) {
            const tag = document.createElement('span');
            tag.className = 'header-search-result-tag';
            tag.textContent = entry.tag;
            titleRow.appendChild(tag);
        }

        link.appendChild(titleRow);
        return link;
    }

    function render(matches) {
        currentMatches = matches;
        activeIndex = -1;
        resultsBox.innerHTML = '';

        if (matches.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'header-search-empty';
            empty.textContent = 'No matches — press Enter to search the Help Centre.';
            resultsBox.appendChild(empty);
            resultsBox.hidden = false;
            return;
        }

        // Best match is shown as a direct answer (full text, not a
        // truncated preview) instead of just another link in a list.
        const [best, ...rest] = matches;

        const answerLink = buildResultLink(best, 'header-search-answer');
        const answerLabel = document.createElement('div');
        answerLabel.className = 'header-search-answer-label';
        answerLabel.textContent = 'Best match';
        answerLink.insertBefore(answerLabel, answerLink.firstChild);

        const answerText = document.createElement('div');
        answerText.className = 'header-search-answer-text';
        answerText.textContent = best.snippet || '';
        answerLink.appendChild(answerText);

        const answerCta = document.createElement('div');
        answerCta.className = 'header-search-answer-link';
        answerCta.textContent = 'View full article →';
        answerLink.appendChild(answerCta);

        resultsBox.appendChild(answerLink);

        if (rest.length > 0) {
            const relatedLabel = document.createElement('div');
            relatedLabel.className = 'header-search-related-label';
            relatedLabel.textContent = 'Related';
            resultsBox.appendChild(relatedLabel);

            rest.forEach(function (entry) {
                const link = buildResultLink(entry);
                const snippet = document.createElement('div');
                snippet.className = 'header-search-result-snippet';
                snippet.textContent = entry.snippet || '';
                link.appendChild(snippet);
                resultsBox.appendChild(link);
            });
        }

        resultsBox.hidden = false;
    }

    function runSearch(rawQuery) {
        const query = rawQuery.trim().toLowerCase();

        if (query.length < 2) {
            resultsBox.hidden = true;
            resultsBox.innerHTML = '';
            return;
        }

        const terms = tokenize(query);

        loadIndex().then(function (entries) {
            const matches = entries
                .map(function (entry) { return { entry: entry, score: scoreEntry(entry, terms, query) }; })
                .filter(function (r) { return r.score > 0; })
                .sort(function (a, b) { return b.score - a.score; })
                .slice(0, 5)
                .map(function (r) { return r.entry; });

            render(matches);
        });
    }

    let debounceTimer = null;
    input.addEventListener('input', function () {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(function () {
            runSearch(input.value);
        }, 150);
    });

    input.addEventListener('focus', function () {
        loadIndex(); // warm the cache as soon as the user shows intent
        if (input.value.trim().length >= 2) {
            runSearch(input.value);
        }
    });

    input.addEventListener('keydown', function (event) {
        const items = Array.from(resultsBox.querySelectorAll('.header-search-result'));
        if (items.length === 0) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
        } else if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();
            window.location.href = items[activeIndex].href;
            return;
        } else {
            return;
        }

        items.forEach(function (item, i) {
            item.classList.toggle('is-active', i === activeIndex);
        });
        items[activeIndex].scrollIntoView({ block: 'nearest' });
    });

    document.addEventListener('click', function (event) {
        if (!form.contains(event.target)) {
            resultsBox.hidden = true;
        }
    });

    // ---------------------------------------------------------------
    // AI Helper chat panel — same local index/scoring as above (loadIndex,
    // scoreEntry), presented as a conversation instead of a dropdown.
    // Still no LLM, no external calls — it's local keyword matching
    // dressed up as a chat.
    // ---------------------------------------------------------------
    const overlay = document.getElementById('aiHelperOverlay');
    const openBtn = document.getElementById('aiHelperOpenBtn');
    const closeBtn = document.getElementById('aiHelperCloseBtn');
    const resetBtn = document.getElementById('aiHelperResetBtn');
    const thread = document.getElementById('aiHelperThread');
    const chatInput = document.getElementById('aiHelperInput');
    const sendBtn = document.getElementById('aiHelperSendBtn');

    if (!overlay || !openBtn || !thread || !chatInput || !sendBtn) {
        return;
    }

    const STORAGE_KEY = 'aiHelperThreadV1';
    const SUGGESTED_QUESTIONS = [
        'How do I request leave?',
        "Why can't I see my payslip?",
        'How do I edit an announcement?',
        'What can Payroll Staff do?',
    ];

    function loadHistory() {
        try {
            const raw = sessionStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            return [];
        }
    }

    function saveHistory(history) {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(history));
        } catch (e) {
            // sessionStorage unavailable (private mode, quota) — fine, just
            // means the thread won't survive a refresh this tab.
        }
    }

    let history = loadHistory();

    function appendChips(container, questions) {
        if (!questions || questions.length === 0) {
            return;
        }
        const chips = document.createElement('div');
        chips.className = 'ai-helper-chips';
        questions.forEach(function (q) {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'ai-helper-chip';
            chip.textContent = q;
            chip.addEventListener('click', function () { askQuestion(q); });
            chips.appendChild(chip);
        });
        container.appendChild(chips);
    }

    function renderBubble(entry) {
        const bubble = document.createElement('div');
        bubble.className = 'ai-helper-bubble ai-helper-bubble-' + entry.role;

        if (entry.thinking) {
            bubble.classList.add('is-thinking');
            bubble.textContent = entry.text;
            return bubble;
        }

        if (entry.role === 'user') {
            bubble.textContent = entry.text;
            return bubble;
        }

        if (entry.tag) {
            const tag = document.createElement('span');
            tag.className = 'ai-helper-source-tag';
            tag.textContent = entry.tag;
            bubble.appendChild(tag);
            bubble.appendChild(document.createElement('br'));
        }

        bubble.appendChild(document.createTextNode(entry.text));

        if (entry.url) {
            const link = document.createElement('a');
            link.href = entry.url;
            link.target = '_blank';
            link.rel = 'noopener';
            link.className = 'ai-helper-source-link';
            link.textContent = 'View full article →';
            bubble.appendChild(document.createElement('br'));
            bubble.appendChild(link);
        }

        if (entry.related && entry.related.length > 0) {
            appendChips(bubble, entry.related.map(function (r) { return r.title; }));
        }

        return bubble;
    }

    function renderThread() {
        thread.innerHTML = '';

        if (history.length === 0) {
            const welcome = document.createElement('div');
            welcome.className = 'ai-helper-bubble ai-helper-bubble-assistant';
            welcome.appendChild(document.createTextNode(
                "Hi! I can answer questions about OTIS — attendance, requests, payroll, loans, announcements, and roles. What do you need?"
            ));
            appendChips(welcome, SUGGESTED_QUESTIONS);
            thread.appendChild(welcome);
            return;
        }

        history.forEach(function (entry) {
            thread.appendChild(renderBubble(entry));
        });
        thread.scrollTop = thread.scrollHeight;
    }

    function openPanel(seedQuestion) {
        overlay.hidden = false;
        renderThread();
        chatInput.focus();
        if (seedQuestion) {
            askQuestion(seedQuestion);
        }
    }

    function closePanel() {
        overlay.hidden = true;
    }

    function resetConversation() {
        history = [];
        saveHistory(history);
        renderThread();
        chatInput.value = '';
        chatInput.focus();
    }

    function askQuestion(rawText) {
        const text = rawText.trim();
        if (text === '') {
            return;
        }

        chatInput.value = '';

        // The previous question, if any, is still the last 'user' entry in
        // history at this point — grabbed before pushing the new one below,
        // for the follow-up retry further down.
        const previousQuestion = (function () {
            for (let i = history.length - 1; i >= 0; i--) {
                if (history[i].role === 'user') return history[i].text;
            }
            return null;
        })();

        history.push({ role: 'user', text: text });
        renderThread();

        const smallTalk = detectSmallTalk(text);
        if (smallTalk) {
            history.push({ role: 'assistant', text: smallTalk, related: [] });
            saveHistory(history);
            renderThread();
            return;
        }

        const thinkingBubble = document.createElement('div');
        thinkingBubble.className = 'ai-helper-bubble ai-helper-bubble-assistant is-thinking';
        thinkingBubble.textContent = 'Thinking…';
        thread.appendChild(thinkingBubble);
        thread.scrollTop = thread.scrollHeight;

        const terms = tokenize(text);

        loadIndex().then(function (entries) {
            const scoreAgainst = function (theTerms, theRawText) {
                return entries
                    .map(function (entry) { return { entry: entry, score: scoreEntry(entry, theTerms, theRawText) }; })
                    .filter(function (r) { return r.score > 0; })
                    .sort(function (a, b) { return b.score - a.score; });
            };

            let scored = scoreAgainst(terms, text);
            let isFollowUp = false;

            // Follow-up context: a short reply like "what about undertime?"
            // or just "undertime" doesn't stand on its own — a real assistant
            // reads it against what was just discussed, not in isolation. Only
            // kicks in when the question alone wasn't already confident, and
            // only combines with the PREVIOUS question (not the growing whole
            // conversation) so one bad early message can't keep steering
            // every answer after it.
            if ((scored.length === 0 || scored[0].score < CONFIDENT_ANSWER_THRESHOLD)
                && previousQuestion && terms.length <= 4) {
                const combinedText = previousQuestion + ' ' + text;
                const combinedScored = scoreAgainst(tokenize(combinedText), combinedText);
                if (combinedScored.length > 0 && combinedScored[0].score >= CONFIDENT_ANSWER_THRESHOLD
                    && (scored.length === 0 || combinedScored[0].score > scored[0].score)) {
                    scored = combinedScored;
                    isFollowUp = true;
                }
            }

            thinkingBubble.remove();

            if (scored.length === 0) {
                history.push({
                    role: 'assistant',
                    text: "I couldn't find anything about that in the Help Centre. Try rephrasing, or submit a help request and the support team will follow up.",
                    related: [],
                });
            } else if (scored[0].score < CONFIDENT_ANSWER_THRESHOLD) {
                // The best match is too weak to state as fact — offer it (and
                // any other weak matches) as things to check instead of
                // answering with low-confidence content as if it were certain.
                const maybe = scored.slice(0, 4).map(function (r) { return r.entry; });
                history.push({
                    role: 'assistant',
                    text: "I'm not fully sure, but this might be related — take a look and see if it helps:",
                    related: maybe,
                });
            } else {
                const best = scored[0].entry;
                const related = scored.slice(1, 4).map(function (r) { return r.entry; });
                history.push({
                    role: 'assistant',
                    text: (isFollowUp ? 'Following up on that — ' : '') + best.snippet,
                    tag: best.tag || (best.type === 'faq' ? 'FAQ' : ''),
                    url: best.url,
                    related: related,
                });
            }

            saveHistory(history);
            renderThread();
        });
    }

    openBtn.addEventListener('click', function () { openPanel(input.value.trim() || null); });
    closeBtn.addEventListener('click', closePanel);
    if (resetBtn) {
        resetBtn.addEventListener('click', resetConversation);
    }
    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
            closePanel();
        }
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !overlay.hidden) {
            closePanel();
        }
    });

    sendBtn.addEventListener('click', function () { askQuestion(chatInput.value); });
    chatInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            askQuestion(chatInput.value);
        }
    });
})();
</script>
