<!-- FONT AWESOME -->
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<header class="app-header">
    <!-- LEFT SIDE -->
    <div class="header-left">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input
            type="text"
            placeholder="What are you looking for?"
            class="search-input"
            >
        </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="header-right">
        <!-- BELL -->
        <div class="header-notif-wrapper">
            <button class="header-notif-btn" onclick="toggleNotif()">
                <img src="<?=base_url('icons/notifications-bell.png')?>">
                <?php if (!empty($notifications) && count($notifications) > 0): ?>
                    <span class="header-notif-badge">
                <?= count($notifications) ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- BELL DROPDOWN -->
            <div id="notifDropdown" class="header-notif-dropdown">
                <div class="header-notif-header">
                    <span class="header-notif-heading">Notifications</span>
                    <button type="button" class="header-notif-mark-read">
                        <img src="<?= base_url('icons/double-check.png') ?>" class="double-check-icon">
                        Mark all as read
                    </button>
                </div>

                <div class="header-notif-section-label">Today</div>

                <div class="header-notif-list notif-scroll-hide">
                        <?php if (!empty($notifications)): ?>
                            <?php foreach($notifications as $n): ?>
                                <?php
                                    $notif = notification_data($n);
                                    $notifTime = (string) ($n['time_ago'] ?? '');
                                    $notifTitle = (string) $notif['title'];
                                    $notifSummary = (string) $notif['details'];
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
                                    <div class="header-notif-icon-wrap">
                                        <i class="fa-regular fa-bell"></i>
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
            ?>
            <div class="user-profile" onclick="toggleUserMenu()">
                <?php if ($photoUrl !== ''): ?>
                    <div class="avatar avatar-photo" style="background-image:url('<?= esc($photoUrl) ?>');" aria-label="Profile photo"></div>
                <?php else: ?>
                    <div class="avatar">
                        <?= esc($avatarInitials) ?>
                    </div>
                <?php endif; ?>
                <div class="user-info">
                    <div class="user-name"><?= esc($displayName !== '' ? $displayName : 'User') ?></div>
                    <div class="user-email"><?= esc($displayEmail) ?></div>
                </div>
                <i class="fa-solid fa-chevron-down dropdown-icon"></i>
            </div>

            <!-- USER DROPDOWN -->
            <div id="userDropdown" class="user-dropdown">
                <a href="<?= site_url('client_portal/profile') ?>">Profile</a>
                <a href="<?= site_url('settings/customization') ?>">Settings</a>
                <a href="<?= site_url('logout') ?>">Logout</a>
            </div>
        </div>
    </div>
</header>

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

    .search-box{
        position:relative;
        width:420px;
    }

    .search-input{
        width:100%;
        height:42px;

        border:none;
        outline:none;

        border-radius:999px;

        background: var(--search-bg);

        padding-left:45px;

        font-size:14px;
        color: var(--search-text);
    }

    .search-input::placeholder{
        color: var(--search-placeholder);
        opacity:1;
    }

    .search-icon{
        position:absolute;

        left:16px;
        top:50%;

        transform:translateY(-50%);

        color: var(--search-placeholder);
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

        font-size:20px;

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

        font-size:10px;
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
        font-size:20px;
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

        font-size:12px;
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

        font-size:13px;
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
        font-size:16px;
        font-weight:700;

        color:var(--header-text);

        line-height:1.25;
    }

    .header-notif-desc{
        font-size:14px;
        color:#475569;
        line-height:1.45;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .header-notif-time{
        font-size:13px;

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

        font-size:14px;
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

    /* =========================================================
    USER INFO
    ========================================================= */

    .user-info{
        display:flex;
        flex-direction:column;
    }

    .user-name{
        font-size:14px;
        font-weight:600;

        color:var(--header-text);
    }

    .user-email{
        font-size:12px;

        color:#9ca3af;
    }

    .dropdown-icon{
        font-size:12px;

        color:var(--header-text);
    }

    /* =========================================================
    USER DROPDOWN
    ========================================================= */

    .user-dropdown{
        position:absolute;

        right:0;
        top:55px;

        width:160px;

        background:var(--dropdown-bg);

        border-radius:10px;

        box-shadow:0 10px 30px rgba(0,0,0,0.15);

        display:none;

        overflow:hidden;

        z-index:9999;
    }

    .user-dropdown a{
        display:block;

        padding:12px 15px;

        text-decoration:none;

        color:var(--dropdown-text);

        font-size:14px;

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

</style>
