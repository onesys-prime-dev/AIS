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
        <div class="notif-wrapper">
            <button class="notif-btn" onclick="toggleNotif()">
                <img src="<?=base_url('icons/notifications-bell.png')?>">
                <?php if (!empty($notifications) && count($notifications) > 0): ?>
                    <span class="notif-badge">
                <?= count($notifications) ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- BELL DROPDOWN -->
            <div id="notifDropdown" class="notif-dropdown">
                <div class="notif-header">
                    <span class="notif-heading">Notifications</span>
                    <button type="button" class="notif-mark-read">
                        <img src="<?= base_url('icons/double-check.png') ?>" class="double-check-icon">
                        Mark all as read
                    </button>
                </div>

                <div class="notif-section-label">Today</div>

                <div class="notif-list">
                    <?php if (!empty($notifications)): ?>
                        <?php foreach($notifications as $n): ?>
                            <?php
                                $notifId = (int) ($n['id'] ?? 0);
                                $notifTime = (string) ($n['time_ago'] ?? '');
                                $notifTitle = (string) ($n['title'] ?? 'Notification');
                                $notifSummary = (string) ($n['summary'] ?? $n['message'] ?? '');
                                $notifDetails = (string) ($n['details'] ?? $notifSummary);
                                $notifPublished = (string) ($n['publish_at'] ?? '');
                                $notifCategory = (string) ($n['category'] ?? 'General');
                                $notifPriority = (string) ($n['priority'] ?? 'Normal');
                                $notifAudience = (string) ($n['audience'] ?? 'All Employees');
                            ?>
                            <div
                                class="notif-item <?= ! empty($n['is_viewed']) ? 'notif-viewed' : '' ?>"
                                role="button"
                                tabindex="0"
                                data-id="<?= esc((string) $notifId, 'attr') ?>"
                                data-title="<?= esc($notifTitle, 'attr') ?>"
                                data-summary="<?= esc($notifSummary, 'attr') ?>"
                                data-details="<?= esc($notifDetails, 'attr') ?>"
                                data-publish="<?= esc($notifPublished !== '' ? date('M d, Y h:i A', strtotime($notifPublished)) : 'N/A', 'attr') ?>"
                                data-category="<?= esc($notifCategory, 'attr') ?>"
                                data-priority="<?= esc(ucfirst($notifPriority), 'attr') ?>"
                                data-audience="<?= esc(ucfirst($notifAudience), 'attr') ?>"
                                onclick="openNotificationModal(this)"
                                onkeydown="handleNotificationKey(event, this)"
                            >
                                <div class="notif-icon-wrap">
                                    <i class="fa-regular fa-bell"></i>
                                </div>
                                <div class="notif-content">
                                    <div class="notif-topline">
                                        <div class="notif-title-wrap">
                                            <div class="notif-title"><?= esc($notifTitle) ?></div>
                                            <span class="notif-dot"></span>
                                        </div>
                                        <?php if ($notifTime !== ''): ?>
                                            <span class="notif-time"><?= esc($notifTime) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="notif-desc"><?= esc($notifSummary) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="notif-empty">No notifications</div>
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

/* HEADER */
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

/* SEARCH */
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

.search-input::placeholder {
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

/* RIGHT SIDE */
.header-right{
    display:flex;
    align-items:center;
    gap:32px;
    height: 100%;
    padding: 20px
}

/* BELL */
.notif-wrapper{
    position:relative;
    padding: 10px;
    border-radius: 10px;
    transition: background-color 0.2s ease;
}

.notif-btn{
    background:none;
    border:none;
    font-size:20px;
    cursor:pointer;
    color: var(--header-text);
    position:relative;
}

.notif-btn img{
    transition: transform 0.2s ease;
}

.notif-btn img:hover{
    transform: scale(1.08);
}

.notif-badge{
    position:absolute;
    top:-4px;
    right:-6px;
    background:#ef4444;
    color:white;
    width:16px;
    height:16px;
    border-radius:50%;
    font-size:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:600;
}

/* NOTIF DROPDOWN */
.notif-dropdown{
    position:absolute;
    right:0;
    top:56px;
    width:460px;
    background: var(--dropdown-bg);
    border-radius:14px;
    box-shadow:0 18px 40px rgba(15,23,42,0.16);
    border:1px solid #e5e7eb;
    display:none;
    padding:0;
    overflow:hidden;

    z-index:99999;
}

.notif-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    padding:14px 18px;
    border-bottom:1px solid #e5e7eb;
    background:#ffffff;
}

.notif-heading{
    font-size: 20px;
    font-weight: 700;
    color:var(--header-text);
    line-height: 1;
}

.notif-mark-read{
    border:none;
    background:transparent;
    color:var(--accent-violet-deep);
    font-size:13px;
    font-weight:700;
    display:flex;
    align-items:center;
    gap: 6px;
    padding: 3px 5px;
    border-radius: 5px;
    cursor:pointer;
    transition: color 0.2s ease;
}

.notif-mark-read:hover{
    color:var(--accent-violet);
    background:#f3f4f6;
}

.notif-section-label{
    padding:10px 18px;
    background:#f3f4f6;
    border-bottom:1px solid #e5e7eb;
    color:#6b7280;
    font-size:13px;
    font-weight:700;
}

.notif-list{
    max-height:420px;
    overflow:auto;
}

.notif-item{
    display:flex;
    align-items:flex-start;
    gap:14px;
    padding:16px 18px;
    border-bottom:1px solid #eef2f7;
    transition: background-color 0.2s ease;
}

.notif-item:hover{
    background:var(--accent-violet-soft);
}

.notif-icon-wrap{
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

.notif-content{
    flex:1;
    min-width:0;
}

.notif-topline{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:4px;
}

.notif-title-wrap{
    display:flex;
    align-items:center;
    gap:8px;
    min-width:0;
}

.notif-dot{
    width:7px;
    height:7px;
    border-radius:50%;
    background:var(--accent-violet);
    flex-shrink:0;
}

.notif-item.notif-viewed .notif-dot{
    display:none;
}

.notif-title{
    font-size:17px;
    font-weight:700;
    color: var(--header-text);
    line-height:1.25;
}

.notif-desc{
    font-size:15px;
    color:#475569;
    line-height:1.45;
}

.notif-time{
    font-size:13px;
    color:#6b7280;
    white-space:nowrap;
}

.notif-empty{
    padding:20px;
    text-align:center;
    color:#6b7280;
    font-size:14px;
}

.double-check-icon{
    width: 20px;
    height: 20px;
}

.modal-overlay{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.45);
    display:none !important;
    align-items:center;
    justify-content:center;
    z-index:999999;
    pointer-events:none;
}

.modal-overlay[style*="display: flex"] {
    display:flex !important;
    pointer-events:auto;
}

/* Force modal visibility on payroll and other pages */
body .modal-overlay[style*="display: flex"] {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.notification-modal{
    background:white;
    width:100%;
    max-width:460px;
    max-height:auto;
    border-radius:14px;
    box-shadow:0 25px 50px rgba(0,0,0,0.25);
    animation:modalPop 0.2s ease;
}

#viewNotificationsModal .notification-modal {
    max-width: 640px;
}

.view-notification-body {
    display:flex;
    flex-direction:column;
    gap:14px;
}

.notification-modal-body{
    flex:1;
    min-height:0;
    padding:16px 20px;
    overflow-y:auto;
}

.view-notification-meta {
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:8px 14px;
    padding:10px 12px;
    border-radius:10px;
    background:#f8fafc;
    border:1px solid #e2e8f0;
}

.view-notification-meta p {
    margin:0;
    font-size:12px;
    color:#475569;
}

.view-notification-meta strong {
    color:#0f172a;
}

.view-notification-content {
    margin:0;
    font-size:14px;
    line-height:1.6;
    color:#334155;
    white-space:pre-wrap;
    word-break:break-word;
}

.notification-modal-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
    font-size:20px;
    font-weight:700;
    padding:16px 20px;
    margin-bottom:0;
    border-bottom:1px solid #e5e7eb;
}

.notification-modal-footer{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    flex-shrink:0;
    padding:14px 20px;
    border-top:1px solid #e5e7eb;
}

.notification-cancel-btn{
    border:none;
    background:#e5e7eb;
    padding:8px 14px;
    border-radius:6px;
    cursor:pointer;
    transition:all 0.2s ease;
}

.notification-cancel-btn:hover{
    background:#d1d5db;
    transform:translateY(-2px);
}

@keyframes modalPop{
    from{ transform:scale(0.95); opacity:0; }
    to{ transform:scale(1); opacity:1; }
}

/* USER */
.user-wrapper{
    position:relative;
}

.user-profile{
    display:flex;
    align-items:center;
    gap:12px;
    cursor:pointer;
    height: 100%;
    padding: 8px;
    border-radius: 10px;
    transition: background-color 0.2s ease, transform 0.2s ease;
}

.user-profile:hover{
    background-color: #dad7d7;
    transform: translateY(-1px);
}

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

.avatar-photo {
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.user-info{
    display:flex;
    flex-direction:column;
}

.user-name{
    font-size:14px;
    font-weight:600;
    color: var(--header-text);
}

.user-email{
    font-size:12px;
    color:#9ca3af;
}

.dropdown-icon{
    font-size:12px;
    color: var(--header-text);
}

/* USER DROPDOWN */
.user-dropdown{
    position:absolute;
    right:0;
    top:55px;
    background: var(--dropdown-bg);
    border-radius:10px;
    box-shadow:0 10px 30px rgba(0,0,0,0.15);
    display:none;
    width:160px;

    z-index:9999;
}

.user-dropdown a{
    display:block;
    padding:12px 15px;
    text-decoration:none;
    color: var(--dropdown-text);
    font-size:14px;
}

.user-dropdown a:hover{
    background:#f3f4f6;
}

</style>


<script>

    /* TOGGLE NOTIFICATIONS MODAL */
    function toggleNotif(){
        let menu = document.getElementById("notifDropdown");
        if(menu.style.display === "block"){
            menu.style.display = "none";
        } else {
            menu.style.display = "block";
            document.getElementById("userDropdown").style.display = "none"; // Close user menu
        }
    }

    /* TOGGLE USER MENU */
    function toggleUserMenu(){
        let menu = document.getElementById("userDropdown");
        if(menu.style.display === "block"){
            menu.style.display = "none";
        } else {
            menu.style.display = "block";
            document.getElementById("notifDropdown").style.display = "none"; // Close notif menu
        }
    }

    /* CLOSE MODALS IF CLICK OUTSIDE */
    document.addEventListener('click', function(e){
        let notifWrapper = document.querySelector('.notif-wrapper');
        let userWrapper = document.querySelector('.user-wrapper');
        
        if(notifWrapper && !notifWrapper.contains(e.target)){
            document.getElementById("notifDropdown").style.display="none";
        }
        if(userWrapper && !userWrapper.contains(e.target)){
            document.getElementById("userDropdown").style.display="none";
        }
    });

    /* CLOSE MODALS WITH ESC KEY */
    document.addEventListener('keydown', function(e){
        if(e.key === 'Escape'){
            document.getElementById("notifDropdown").style.display="none";
            document.getElementById("userDropdown").style.display="none";
        }
    });

    function handleNotificationKey(event, el) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openNotificationModal(el);
        }
    }

    function openNotificationModal(el) {
        if (!el) return;

        const modal = document.getElementById('viewNotificationsModal');
        if (!modal) {
            console.error('Notification modal not found');
            return;
        }

        // Close the notification dropdown
        const notifDropdown = document.getElementById('notifDropdown');
        if (notifDropdown) {
            notifDropdown.style.display = 'none';
        }

        const title = el.dataset.title || 'Notification';
        const summary = el.dataset.summary || '';
        const details = el.dataset.details || summary;

        const titleNode = document.getElementById('viewNotificationTitle');
        const publishedNode = document.getElementById('viewNotificationPublished');
        const categoryNode = document.getElementById('viewNotificationCategory');
        const priorityNode = document.getElementById('viewNotificationPriority');
        const audienceNode = document.getElementById('viewNotificationAudience');
        const summaryNode = document.getElementById('viewNotificationSummary');
        const detailsNode = document.getElementById('viewNotificationDetails');

        if (titleNode) titleNode.innerText = title;
        if (publishedNode) publishedNode.innerText = el.dataset.publish || 'N/A';
        if (categoryNode) categoryNode.innerText = el.dataset.category || 'General';
        if (priorityNode) priorityNode.innerText = el.dataset.priority || 'Normal';
        if (audienceNode) audienceNode.innerText = el.dataset.audience || 'All Employees';
        if (summaryNode) summaryNode.innerText = summary;
        if (detailsNode) detailsNode.innerText = details;

        const notificationId = Number(el.dataset.id || 0);

        if (notificationId && !el.classList.contains('notif-viewed')) {
            syncNotificationViewedState(notificationId, true);
            markNotificationAsViewed(notificationId);
        }

        modal.style.display = 'flex';
        console.log('Modal displayed');
    }

    function syncNotificationViewedState(notificationId, isViewed) {
        if (!notificationId) return;

        document.querySelectorAll(`.notif-item[data-id="${notificationId}"], .notif[data-id="${notificationId}"]`).forEach(function (item) {
            item.classList.toggle('notif-viewed', isViewed);
        });
    }

    async function markNotificationAsViewed(notificationId) {
        if (!notificationId || !window.notificationViewedUrl || !window.notificationCsrfTokenName || !window.notificationCsrfTokenHash) {
            return;
        }

        const payload = new URLSearchParams();
        payload.append('announcement_id', String(notificationId));
        payload.append(window.notificationCsrfTokenName, window.notificationCsrfTokenHash);

        try {
            const response = await fetch(window.notificationViewedUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: payload.toString(),
            });

            const result = await response.json();

            if (result && result.csrfHash) {
                window.notificationCsrfTokenHash = result.csrfHash;
            }

            if (!response.ok || !result || result.success !== true) {
                syncNotificationViewedState(notificationId, false);
            }
        } catch (error) {
            syncNotificationViewedState(notificationId, false);
            console.error('Unable to mark notification as viewed.', error);
        }
    }

    function closeNotificationModal() {
        const modal = document.getElementById('viewNotificationsModal');
        if (!modal) return;
        modal.style.display = 'none';
    }

</script>

