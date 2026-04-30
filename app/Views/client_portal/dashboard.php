<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $user = auth()->user(); ?>

<style>
body{
    background:#f3f4f6;
    font-family:Segoe UI, Arial, sans-serif;
    margin:0;
}

/* CENTER CONTENT WIDTH */
.dashboard{
    display: flex;
    flex-direction: column;
    align-items: stretch;
    padding: 0 2rem 2rem 2rem;
    width: 100%;
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    box-sizing: border-box;
}

/* LIMIT CONTENT WIDTH */
.header,
.dashboard-main{
    width:100%;
    max-width:none;
    height:auto;
}

/* HEADER */
.header{
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    margin-bottom: 1rem;
}

/* TITLE */
.header h1{
    font-size:clamp(20px, 2.5vw, 28px);
    font-weight:700;
    margin:0;
}

/* BUTTON */
.request-btn{
    background: #ef4444;
    color: white;
    padding: 10px 18px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.request-btn:hover{
    background: #dc2626;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(239, 68, 68, 0.3);
}

/* MAIN LAYOUT */
.dashboard-main{
    display: grid;
    grid-template-columns: minmax(0, 1fr) clamp(400px, 34vw, 500px);
    align-items: stretch;
    gap: 25px;
    width: 100%;
    flex: 1;
    min-height: 0;
}

/* CLOCK CARD */
.clock-card{
    min-width: 0;
    max-width: none;
    background: white;
    display: flex;
    flex-direction: column;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
    border: 1px solid #e5e7eb;
    justify-content: flex-start;
    height:100%;
}

/* TITLE */
.clock-title{
    text-align: center;
    color: #ef4444;
    font-weight: 700;
    margin-bottom: 10px;
    font-size: clamp(20px, 2vw, 28px);
}

/* INNER BOX */
.clock-inner{
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    background:#e5e7eb;
    border-radius:12px;
    padding:25px;
    margin-bottom:10px;
    text-align:center;
    flex:1;
    min-height:320px;
}

/* STATUS */
.status{
    font-size: 18px;
    color:#6b7280;
    font-weight:500;
    padding: 18px;
}

.status span{
    background:#bbf7d0;
    color:#065f46;
    padding:4px 12px;
    border-radius:20px;
    font-size:13px;
}

/* CLOCK */
.clock{
    width:clamp(180px, 25vw, 240px);
    height:clamp(180px, 25vw, 240px);
    position:relative;
    display:flex;
    align-items:center;
    justify-content:center;
    padding: 20px;
}

.clock svg{
    position:absolute;
    width:100%;
    height:100%;
    transform:rotate(-90deg);
}

.shift-time{
    position:absolute;
    font-size:clamp(28px, 4vw, 40px);
    font-weight:700;
}

/* DATE */
.date{
    font-size: 18px;
    color: #4b5563;
    font-weight: 500;
    margin-top: 18px;
}

/* BUTTONS */
.buttons{
    display:flex;
    gap:12px;
    align-items:center;
    width:100%;
    margin-top:12px;
}

.buttons form{
    flex:1;
    margin:0;
}

.clockout{
    width:100%;
    height:48px;
    background:#ef4444;
    border:none;
    color:white;
    border-radius:12px;
    font-weight:600;
    cursor:pointer;
    box-shadow:0 2px 6px rgba(0,0,0,0.15);
    display:block;
    transition:all 0.2s ease;
}

.clockout:hover{
    background:#dc2626;
    transform:translateY(-2px);
    box-shadow:0 6px 12px rgba(239, 68, 68, 0.3);
}

/* ICON BUTTON */
.icon-btn{
    width:48px;
    height:48px;
    min-width:48px;
    border:none;
    border-radius:12px;
    background:#f3f4f6;
    background-repeat:no-repeat;
    background-position:center;
    background-size:22px;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    transition:all 0.2s ease;
}

.icon-btn:hover{
    background-color:#e5e7eb;
    transform:scale(1.08);
}

/* ICONS */
.coffee{
    background-image:url("<?= base_url('icons/coffee.png') ?>");
}

.idcard{
    background-image:url("<?= base_url('icons/add_notes.png') ?>");
}

.on-break{
    background-color:#fb923c !important;
}


/* NOTIFICATIONS */
.notifications{
    width:100%;
    max-width:none;
    background: linear-gradient(180deg, #ffffff 0%, #fcfcfe 100%);
    border-radius:18px;
    box-shadow:0 14px 30px rgba(17, 24, 39, 0.10);
    border:1px solid #ececec;
    overflow-y:auto;
    max-height:none;
    height:100%;
}

.notifications-divider{
    border:0;
    border-top:1px solid #e5e7eb;
}

/* NOTIFICATION TITLE */
.notifications-title{
    color:#232323;
    font-size:24px;
    font-weight:700;
    letter-spacing:-0.015em;
}

.notifications-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin: 20px 20px 14px;
}

.notifications-clear{
    border:none;
    background:transparent;
    color:#4b5563;
    font-size:12px;
    font-weight:600;
    cursor:pointer;
    padding:6px 8px;
    border-radius:8px;
    display:flex;
    align-items:center;
    gap:4px;
}

.notifications-clear .dot{
    width:23px;
    height:23px;
}

.notifications-clear:hover{
    color:#1f2937;
    background:#f3f4f6;
}

.notifications-tabs{
    display:flex;
    align-items:center;
    gap:10px;
    margin: 12px 20px 8px;
}

.notifications-tab{
    border:1px solid transparent;
    background:#f3f4f6;
    font-size:12px;
    font-weight:600;
    color:#4b5563;
    border-radius:999px;
    padding:8px 13px;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:7px;
    transition:all 0.2s ease;
}

.notifications-tab:hover{
    background:#ede9fe;
    color:#1f2937;
}

.notifications-tab.active{
    background:#EBE5FF;
    color:#1f2937;
    border-color:rgba(126, 35, 253, 0.25);
    box-shadow:0 2px 8px rgba(126, 35, 253, 0.14);
}

.notifications-badge{
    background:#7E23FD;
    color:#fff;
    font-size:11px;
    font-weight:700;
    border-radius:999px;
    padding:2px 8px;
    line-height:1.4;
}

.notifications-divider-title{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:11px;
    font-weight:700;
    color:#6b7280;
    margin:14px 0 10px;
    text-transform:uppercase;
    letter-spacing:0.06em;
}

.notifications-divider-title::after{
    content:'';
    flex:1;
    height:1px;
    background:#dedede;
}

.notifications-empty{
    font-size:12px;
    color:#7d7d7d;
    margin:10px 0 14px;
    text-align: center;
}

.notif-meta{
    margin-top:6px;
    font-size:11px;
    color:#6b7280;
}

.notifications-group{
    display:none;
    padding: 0 20px 16px;
}

.notifications-group.active{
    display:block;
}

/* NOTIFICATION ITEM */
.notif{
    position:relative;
    display:flex;
    align-items:flex-start;
    gap:12px;
    padding:14px 18px 14px 18px;
    margin:10px 0;
    background:#ffffff;
    border-radius:14px;
    cursor:pointer;
    border:1px solid transparent;
    box-shadow:0 6px 14px rgba(15, 23, 42, 0.09);
    transition:background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.notif:hover{
    background:#f6f2ff;
    transform:translateY(-2px);
    border-color:rgba(126, 35, 253, 0.2);
    box-shadow:0 12px 22px rgba(126, 35, 253, 0.20);
}

.notif::before{
    content:'';
    position:absolute;
    left:8px;
    top:18px;
    width:8px;
    height:8px;
    border-radius:50%;
    background:#7E23FD;
    transition:transform 0.2s ease, box-shadow 0.2s ease;
}

.notif:hover::before{
    transform:scale(1.2);
    box-shadow:0 0 0 4px rgba(126, 35, 253, 0.15);
}

/* .notif::after{
    content:'>';
    position:absolute;
    right:14px;
    top:50%;
    transform:translateY(-50%);
    color:#8b5cf6;
    font-size:14px;
    font-weight:700;
    opacity:0.75;
    transition:transform 0.2s ease, opacity 0.2s ease;
} */

.notif:hover::after{
    transform:translateY(-50%) translateX(2px);
    opacity:1;
}

.notif.notif-viewed::before{
    display:none;
}

.notif-avatar{
    width:40px;
    height:40px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(135deg, #7E23FD, #a855f7);
    color:#fff;
    font-weight:700;
    font-size:12px;
    letter-spacing:0.02em;
    flex-shrink:0;
}

.notif-content{
    flex:1;
    min-width:0;
    padding-top:1px;
}

.notif-title-row{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
}

.notif-time{
    font-size:11px;
    color:#6b7280;
    font-weight:600;
    white-space: nowrap;
    flex-shrink:0;
    margin-top:1px;
}

.notif-tags{
    display:flex;
    align-items:center;
    gap:6px;
    margin-top:8px;
}

.notif-pill{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    padding:2px 8px;
    font-size:10px;
    font-weight:700;
    letter-spacing:0.03em;
    text-transform:uppercase;
    background:#efe7ff;
    color:#5b21b6;
}

.notif-pill.priority-normal{
    background:#dbeafe;
    color:#1e40af;
}

.notif-pill.priority-high{
    background:#fed7aa;
    color:#b45309;
}

.notif-pill.priority-urgent{
    background:#fecaca;
    color:#991b1b;
}

.notif-pill.muted{
    background:#f3f4f6;
    color:#4b5563;
}

.notif-thumb{
    width:58px;
    height:58px;
    border-radius:14px;
    background:linear-gradient(140deg, #f4d03f, #16a085);
    position:relative;
    overflow:hidden;
    flex-shrink:0;
}

.notif-thumb::after{
    content:'+';
    position:absolute;
    right:4px;
    bottom:4px;
    width:18px;
    height:18px;
    border-radius:50%;
    background:#ef4444;
    color:#fff;
    font-size:12px;
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
}

.notif-title{
    font-weight:700;
    font-size:13px;
    color:#262626;
    line-height:1.35;
}

.notif-desc{
    font-size:12px;
    color:#6b7280;
    margin-top:6px;
    line-height:1.5;
}

/* MODAL */
.modal-overlay{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.45);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999;
}

.modal{
    background:white;
    width:100%;
    max-width:460px;
    max-height: auto;
    border-radius:14px;
    box-shadow:0 25px 50px rgba(0,0,0,0.25);
    animation:modalPop 0.2s ease;
}

#viewNotificationsModal .modal {
    max-width: 640px;
}

.view-notification-body {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.view-notification-meta {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px 14px;
    padding: 10px 12px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.view-notification-meta p {
    margin: 0;
    font-size: 12px;
    color: #475569;
}

.view-notification-meta strong {
    color: #0f172a;
}

.view-notification-content {
    margin: 0;
    font-size: 14px;
    line-height: 1.6;
    color: #334155;
    white-space: pre-wrap;
    word-break: break-word;
}

.modal-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
    font-size: 20px;
    font-weight: 700;
    padding:16px 20px;
    margin-bottom:0;
    border-bottom:1px solid #e5e7eb;
}

.modal input,
.modal select,
.modal textarea{
    width:100%;
    padding:10px;
    border:1px solid #d1d5db;
    border-radius:8px;
    margin-top: 5px;
    margin-bottom:10px;
}

.modal textarea{
    height:80px;
    resize:none;
}

.modal-footer{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    flex-shrink:0;
    padding:14px 20px;
    border-top:1px solid #e5e7eb;
}

.cancel-btn{
    border:none;
    background:#e5e7eb;
    padding:8px 14px;
    border-radius:6px;
    cursor:pointer;
    transition:all 0.2s ease;
}

.cancel-btn:hover{
    background:#d1d5db;
    transform:translateY(-2px);
}

.submit-btn{
    background:#8b5cf6;
    color:white;
    border:none;
    padding:8px 16px;
    border-radius:6px;
    cursor:pointer;
    transition:all 0.2s ease;
}

.submit-btn:hover{
    background:#7c3aed;
    transform:translateY(-2px);
    box-shadow:0 4px 12px rgba(139, 92, 246, 0.3);
}

.button-holder{
    display:flex;
    align-items:center;
    gap: 10px;
}

.btn-icon{
    width:26px;
    height:26px;
    object-fit:contain;
}

/* ANNOUNCEMENT MODAL (OWN STYLES) */
.announcement-modal{
    max-width:920px;
    width:min(92vw, 920px);
    max-height:90vh;
    padding:0;
    overflow:hidden;
    border-radius:16px;
}

.announcement-form{
    display:flex;
    flex-direction:column;
    max-height:90vh;
}

.modal-body{
    flex:1;
    min-height:0;
    padding: 16px 20px;
    overflow-y:auto;
}

.announcement-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap: 0px 15px;
}

.announcement-field{
    display:flex;
    flex-direction:column;
}

.announcement-field.full-span{
    grid-column:1 / -1;
}

.modal-body label{
    font-size:15px;
    color:#374151;
    font-weight:600;
}

.announcement-field input,
.announcement-field select,
.announcement-field textarea{
    width:100%;
    border:1px solid #d1d5db;
    border-radius:8px;
}

.announcement-field textarea{
    resize:vertical;
    min-height: 82px;
}

.announcement-counter{
    text-align:right;
    font-size:12px;
    padding: 0;
    color:#6b7280;
}

.announcement-check{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:13px;
    color:#4b5563;
}

.announcement-check input{
    width:auto;
    margin:0;
    padding:0;
    border:none;
    border-radius:0;
}

.checkbox-container{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:13px;
    color:#4b5563;
    padding: 10px 0px;
}

.checkbox-container input[type="checkbox"]{
    width:15px;
    height:15px;
    cursor:pointer;
    margin:0;
    padding:0;
    accent-color:#8b5cf6;
}

@keyframes modalPop{
    from{ transform:scale(0.95); opacity:0; }
    to{ transform:scale(1); opacity:1; }
}

/* MOBILE */
@media (max-width: 900px){
    .dashboard-main{
        grid-template-columns:1fr;
        flex:none;
    }

    .notifications{
        width:100%;
        height:auto;
    }

    .clock-card{
        width:100%;
        height:auto;
    }

    .announcement-grid{
        grid-template-columns:1fr;
    }

    .notif{
        padding:13px 36px 13px 16px;
    }

    .notif-time{
        font-size:10px;
    }

    .view-notification-meta {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="dashboard">

        <div class="header">
            <div class="p-2">
                <h1>Employee Dashboard</h1>
            </div>
            <div class="button-holder">
                <?php if ($user->inGroup('client_payroll_staff')): ?>
                <button onclick="openAnnouncementForm()" class="request-btn">
                    <img src="<?= base_url('icons/announcement.png') ?>" alt="Announcement" class="btn-icon">
                    <span>Announcement</span>
                </button>
                <?php endif; ?>
                <button onclick="openRequestForm()" class="request-btn">
                    <img src="<?= base_url('icons/Requests.png') ?>" alt="Request" class="btn-icon">
                    <span>Request Form</span>
                </button>
            </div>
        </div>
    <div class="dashboard-main">

        <div class="clock-card">

            <div class="clock-title">
                Welcome back, <?= esc($user->username) ?>!
            </div>

            <div class="clock-inner">

                <!-- success/error messages for taking break -->
                <?php if(session()->getFlashdata('success')): ?>
                    <div style="background:#dcfce7;padding:10px;border-radius:6px;margin-bottom:15px;">
                        <?= session()->getFlashdata('success') ?>
                    </div>
                <?php endif; ?>

                <?php if(session()->getFlashdata('error')): ?>
                    <div style="background:#fecaca;padding:10px;border-radius:6px;margin-bottom:15px;">
                        <?= session()->getFlashdata('error') ?>
                    </div>
                <?php endif; ?> 

                <div class="status">
                    Status :
                        <span id="statusBadge">
                            <?= $currentAttendance ? 'Clocked In' : 'Clocked Out' ?>
                        </span>
                </div>

                <div class="clock">
                    <svg viewBox="0 0 200 200" width="100%" height="100%">
                        <circle cx="100" cy="100" r="90" stroke="#d1d5db" stroke-width="12" fill="none"/>
                        <circle id="progressRing" cx="100" cy="100" r="90" stroke="#84cc16" stroke-width="12" fill="none" stroke-linecap="round"/>
                        <circle id="progressDot" cx="100" cy="10" r="6" fill="white" stroke="#FFFEFE" stroke-width="4"/>
                    </svg>
                    <div id="shiftTime" class="shift-time">8:00</div>
                </div>

                <div id="clockDate" class="date"></div>
            </div>

            <div class="buttons">
                <!-- functioning break button -->
                <?php if ($currentAttendance): ?>
                <a href="<?= site_url('client_portal/toggle-break/'.$currentAttendance['id']) ?>"
                    class="icon-btn coffee <?= $currentAttendance['break_start'] ? 'on-break' : '' ?>">
                        <span></span><span></span><span></span>
                </a>

                    <?php endif; ?>

                <button onclick="openTaskLog()" class="icon-btn idcard">
                <span></span><span></span>
                </button>

                <!--functioning toggle clock -->
                <?php if (!$currentAttendance): ?>
                <form action="<?= site_url('client_portal/time-in') ?>" method="get">
                    <button type="submit" class="clockout">
                        Clock In
                    </button>
                </form>
                <?php else: ?>
                <form action="<?= site_url('client_portal/time-out/'.$currentAttendance['id']) ?>" method="get">
                    <button type="submit" class="clockout">
                        Clock Out
                    </button>
                </form>
                <?php endif; ?>
            </div>

        </div>

        <?php
            $publishedItems = $publishedNotifications ?? [];
            $unpublishedItems = $unpublishedNotifications ?? [];

            $groupNotificationsByPeriod = static function (array $items): array {
                $today = new DateTimeImmutable('today');

                $buckets = [
                    'today' => [],
                    'older' => [],
                ];

                foreach ($items as $item) {
                    $stamp = strtotime((string) ($item['publish_at'] ?? ''));
                    $date = $stamp ? (new DateTimeImmutable())->setTimestamp($stamp) : $today;

                    if ($date->format('Y-m-d') === $today->format('Y-m-d')) {
                        $buckets['today'][] = $item;
                        continue;
                    }

                    $buckets['older'][] = $item;
                }

                return [
                    'today' => [
                        'title' => 'Today',
                        'items' => $buckets['today'],
                    ],
                    'older' => [
                        'title' => 'Older',
                        'items' => $buckets['older'],
                    ],
                ];
            };

            $timeAgo = static function (string $dateTime): string {
                $stamp = strtotime($dateTime);
                if (! $stamp) {
                    return 'just now';
                }

                $diff = time() - $stamp;
                if ($diff < 60) {
                    return $diff . 'sec ago';
                }
                if ($diff < 3600) {
                    return floor($diff / 60) . 'min ago';
                }
                if ($diff < 86400) {
                    return floor($diff / 3600) . 'hr ago';
                }

                return floor($diff / 604800) . 'w ago';
            };

            $initials = static function (string $title): string {
                $clean = trim($title);
                if ($clean === '') {
                    return 'AN';
                }

                $parts = preg_split('/\s+/', $clean) ?: [];
                $letters = '';

                foreach ($parts as $part) {
                    if ($part !== '') {
                        $letters .= strtoupper(substr($part, 0, 1));
                    }
                    if (strlen($letters) >= 2) {
                        break;
                    }
                }

                return str_pad(substr($letters, 0, 2), 2, 'N');
            };

            $renderNotificationItem = static function (array $n, callable $initials, callable $timeAgo, bool $showMeta = false): void {
                $title = (string) ($n['title'] ?? 'Untitled Notification');
                $summary = (string) ($n['summary'] ?? $n['message'] ?? '');
                $details = (string) ($n['details'] ?? $summary);
                $publishedAt = (string) ($n['publish_at'] ?? '');
                $publishedOn = $publishedAt !== '' ? date('M d, Y h:i A', strtotime($publishedAt)) : 'N/A';
                $category = (string) ($n['category'] ?? 'General');
                $priority = (string) ($n['priority'] ?? 'Normal');
                $audience = (string) ($n['audience'] ?? 'All Employees');
                ?>
                <div
                    class="notif <?= ! empty($n['is_viewed']) ? 'notif-viewed' : '' ?>"
                    role="button"
                    tabindex="0"
                    data-id="<?= esc((string) ($n['id'] ?? 0), 'attr') ?>"
                    data-title="<?= esc($title, 'attr') ?>"
                    data-summary="<?= esc($summary, 'attr') ?>"
                    data-details="<?= esc($details, 'attr') ?>"
                    data-publish="<?= esc($publishedOn, 'attr') ?>"
                    data-category="<?= esc($category, 'attr') ?>"
                    data-priority="<?= esc(ucfirst($priority), 'attr') ?>"
                    data-audience="<?= esc(ucfirst($audience), 'attr') ?>"
                    onclick="openNotificationModal(this)"
                    onkeydown="handleNotificationKey(event, this)">
                    <div class="notif-avatar"><?= esc($initials((string) ($n['title'] ?? ''))) ?></div>
                    <div class="notif-content">
                        <div class="notif-title-row">
                            <div class="notif-title"><?= esc($title) ?></div>
                            <div class="notif-time"><?= esc($timeAgo((string) ($n['publish_at'] ?? ''))) ?></div>
                        </div>
                        <div class="notif-desc"><?= esc($summary) ?></div>
                        <div class="notif-tags">
                            <span class="notif-pill priority-<?= strtolower($priority) ?>"><?= esc(ucfirst($priority)) ?></span>
                            <span class="notif-pill muted"><?= esc(ucfirst($category)) ?></span>
                        </div>
                        <?php if ($showMeta): ?>
                        <div class="notif-meta">Publish on <?= esc(date('M d, Y h:i A', strtotime((string) ($n['publish_at'] ?? 'now')))) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            };

            $renderNotificationSection = static function (string $title, array $items, callable $renderItem, callable $initials, callable $timeAgo, bool $showMeta = false): void {
                ?>
                <div class="notifications-divider-title"><?= esc($title) ?></div>
                <?php if (! empty($items)): ?>
                    <?php foreach ($items as $n): ?>
                        <?php $renderItem($n, $initials, $timeAgo, $showMeta); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="notifications-empty">No <?= strtolower($title) ?> notifications.</div>
                <?php endif; ?>
                <?php
            };

            $publishedBuckets = $groupNotificationsByPeriod($publishedItems);
            $unpublishedBuckets = $groupNotificationsByPeriod($unpublishedItems);
        ?>

        <div class="notifications">
            <div class="notifications-top">
                <div class="notifications-title">Notification</div>
                <button type="button" class="notifications-clear" onclick="clearNotificationsView()"><img class="dot" src="<?= base_url('icons/Clear.png') ?>">Clear all</button>
            </div>

            <div class="notifications-divider"></div>

            <div class="notifications-tabs">
                <button type="button" id="tabPublished" class="notifications-tab active" onclick="toggleNotificationsGroup('published')">
                    Published
                    <span class="notifications-badge"><?= count($publishedItems) ?></span>
                </button>

                <?php if ($user->inGroup('client_payroll_staff')): ?>
                <button type="button" id="tabUnpublished" class="notifications-tab" onclick="toggleNotificationsGroup('unpublished')">
                    Unpublished
                    <span class="notifications-badge"><?= count($unpublishedItems) ?></span>
                </button>
                <?php endif; ?>
            </div>

            <div id="publishedGroup" class="notifications-group active">
                <?php
                    $renderNotificationSection($publishedBuckets['today']['title'], $publishedBuckets['today']['items'], $renderNotificationItem, $initials, $timeAgo);
                    $renderNotificationSection($publishedBuckets['older']['title'], $publishedBuckets['older']['items'], $renderNotificationItem, $initials, $timeAgo);
                ?>
            </div>

            <?php if ($user->inGroup('client_payroll_staff')): ?>
            <div id="unpublishedGroup" class="notifications-group">
                <?php
                    $renderNotificationSection($unpublishedBuckets['today']['title'], $unpublishedBuckets['today']['items'], $renderNotificationItem, $initials, $timeAgo, true);
                    $renderNotificationSection($unpublishedBuckets['older']['title'], $unpublishedBuckets['older']['items'], $renderNotificationItem, $initials, $timeAgo, true);
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

    <!-- TASK LOG MODAL -->
    <div class="modal-overlay" id="taskLogModal">
        <div class="modal">
            <div class="modal-header">
                <span>Task Log</span>
                <button onclick="closeTaskLog()">✕</button>
            </div>

            <form id="taskLogForm" action="<?= site_url('client_portal/tasks/submit') ?>" method="post">
                <div class="modal-body">
                    <label>Select Preset Task</label>
                    <select id="savedTasksDropdown">
                        <option value="">-- Write your task manually --</option>
                    </select>

                    <label>Or write your task *</label>
                    <textarea id="taskPerformed" name="task_performed" required></textarea>

                    <label>Start Time *</label>
                    <input type="time" name="start_time" required>

                    <label>End Time *</label>
                    <input type="time" name="end_time" required>

                    <label class="checkbox-container">
                        <input type="checkbox" name="save_task" id="saveTaskToggle">
                        Save this task as a preset
                    </label>
                </div>  

                <div class="modal-footer">
                    <button type="button" class="cancel-btn" onclick="closeTaskLog()">Cancel</button>
                    <button type="submit" class="submit-btn">Log Task</button>
                </div>
            </form>
        </div>
    </div>

    <!-- REQUEST MODAL -->
    <div class="modal-overlay" id="requestModal">
        <div class="modal">
            <form action="<?= site_url('client_portal/requests/store') ?>" method="post">
                <div class="modal-header">
                    <span>Request Form</span>
                    <button type="button" onclick="closeRequestForm()">✕</button>
                </div>

                <div class="modal-body">
                    <label>Select Form</label>
                    <select name="request_type" required>
                        <option value="leave">Leave Request</option>
                        <option value="overtime">Overtime Request</option>
                        <option value="undertime">Undertime Request</option>
                    </select>

                    <label>Start Date</label>
                    <input type="date" name="start_date" required>

                    <label>End Date</label>
                    <input type="date" name="end_date">

                    <label>Approval Code</label>
                    <input type="text" name="approval_code" placeholder="Approval code entry...">

                    <label>Message</label>
                    <textarea row="3" name="reason" placeholder="Enter your reason..."></textarea>
                </div> 

                <div class="modal-footer">
                    <button type="button" class="cancel-btn" onclick="closeRequestForm()">Cancel</button>
                    <button type="submit" class="submit-btn">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ANNOUNCEMENT MODAL -->
    <div class="modal-overlay" id="announcementModal">
        <div class="modal announcement-modal">
            <form id="announcementForm" action="<?= site_url('client_portal/announcements/store') ?>" method="post" class="announcement-form">
                <div class="modal-header">
                    <span>Create Announcement</span>
                    <button type="button" onclick="closeAnnouncementForm()">✕</button>
                </div>

                <div class="modal-body scroll-bar">
                    <div class="announcement-grid">
                        <div class="announcement-field full-span">
                            <label>Announcement Title *</label>
                            <input type="text" name="title" maxlength="120" placeholder="e.g. Payroll Release Schedule - April 2026" required>
                        </div>

                        <div class="announcement-field">
                            <label>Category *</label>
                            <select name="category" required>
                                <option value="">Select category</option>
                                <option value="general">General Update</option>
                                <option value="policy">Policy Notice</option>
                                <option value="event">Event</option>
                                <option value="maintenance">System Maintenance</option>
                                <option value="emergency">Urgent Alert</option>
                            </select>
                        </div>

                        <div class="announcement-field">
                            <label>Audience *</label>
                            <select id="announcementAudience" name="audience" onchange="toggleAnnouncementAudience()" required>
                                <option value="">Select audience</option>
                                <option value="all">All Employees</option>
                                <option value="payroll">Payroll Staff</option>
                                <option value="department">Specific Department</option>
                            </select>
                        </div>

                        <div id="announcementDepartmentWrap" class="announcement-field" style="display:none;">
                            <label>Department *</label>
                            <input id="announcementDepartment" type="text" name="department" placeholder="e.g. Finance">
                        </div>

                        <div class="announcement-field">
                            <label>Priority *</label>
                            <select name="priority" required>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <div class="announcement-field">
                            <label>Publish On *</label>
                            <input type="datetime-local" name="publish_at" required>
                        </div>

                        <div class="announcement-field">
                            <label>Visible Until</label>
                            <input type="datetime-local" name="expires_at">
                        </div>

                        <div class="announcement-field full-span">
                            <label>Short Summary *</label>
                            <textarea id="announcementSummary" name="summary" maxlength="180" placeholder="One-line summary visible in dashboard cards" required></textarea>
                            <div class="announcement-counter"><span id="announcementSummaryCount">0</span>/180</div>
                        </div>

                        <div class="announcement-field full-span">
                            <label>Announcement Details *</label>
                            <textarea name="details" style="min-height:120px;" placeholder="Write complete announcement details here." required></textarea>
                        </div>

                        <div class="announcement-field full-span">
                            <label class="announcement-check">
                                <input type="checkbox" name="require_acknowledgement" value="1">
                                Require employee acknowledgement
                            </label>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="cancel-btn" onclick="closeAnnouncementForm()">Cancel</button>
                    <button type="submit" class="submit-btn">Publish</button>
                </div>
            </form>
        </div>
    </div>

<script>
    const attendance = <?= json_encode($currentAttendance ?? null) ?>;

    async function loadSavedTasks() {
        try {
            const response = await fetch("<?= site_url('client_portal/tasks/saved') ?>");
            const tasks = await response.json();

            const dropdown = document.getElementById("savedTasksDropdown");
            dropdown.innerHTML = '<option value="">-- Write your task manually --</option>';

            tasks.forEach(task => {
                const option = document.createElement("option");
                option.value = task.task_performed;
                option.text = task.task_performed;
                dropdown.appendChild(option);
            });

            // attach change listener here after options are loaded
            dropdown.onchange = function() {
                document.getElementById("taskPerformed").value = this.value;
            };

        } catch (err) {
            console.error("Failed to load saved tasks:", err);
        }
    }

    /* MODALS */
    function openTaskLog() {
        document.getElementById("taskLogModal").style.display = "flex";
        loadSavedTasks();
    }

    function closeTaskLog() {
        document.getElementById("taskLogModal").style.display = "none";
        document.getElementById("taskLogForm").reset();
    }

    function openRequestForm(){
        document.getElementById("requestModal").style.display="flex";
    }

    function closeRequestForm(){
        document.getElementById("requestModal").style.display="none";
    }

    function openAnnouncementForm() {
        document.getElementById("announcementModal").style.display="flex";
    }

    function closeAnnouncementForm() {
        document.getElementById("announcementModal").style.display="none";
        document.getElementById("announcementForm").reset();
        toggleAnnouncementAudience();
        updateAnnouncementSummaryCount();

    }

    function toggleAnnouncementAudience() {
        const audience = document.getElementById("announcementAudience").value;
        const wrap = document.getElementById("announcementDepartmentWrap");
        const input = document.getElementById("announcementDepartment");

        if (audience === "department") {
            wrap.style.display = "block";
            input.required = true;
        } else {
            wrap.style.display = "none";
            input.required = false;
            input.value = "";
        }
    }

    function updateAnnouncementSummaryCount() {
        const summary = document.getElementById("announcementSummary");
        const counter = document.getElementById("announcementSummaryCount");
        if (!summary || !counter) return;
        counter.innerText = summary.value.length;
    }

    function toggleNotificationsGroup(target = "published") {
        const tabPublished = document.getElementById("tabPublished");
        const tabUnpublished = document.getElementById("tabUnpublished");
        const publishedGroup = document.getElementById("publishedGroup");
        const unpublishedGroup = document.getElementById("unpublishedGroup");

        if (!publishedGroup) return;

        if (target === "unpublished" && unpublishedGroup) {
            publishedGroup.classList.remove("active");
            unpublishedGroup.classList.add("active");
            if (tabPublished) tabPublished.classList.remove("active");
            if (tabUnpublished) tabUnpublished.classList.add("active");
            return;
        }

        publishedGroup.classList.add("active");
        if (unpublishedGroup) {
            unpublishedGroup.classList.remove("active");
        }
        if (tabPublished) tabPublished.classList.add("active");
        if (tabUnpublished) tabUnpublished.classList.remove("active");
    }

    function clearNotificationsView() {
        const publishedGroup = document.getElementById("publishedGroup");
        const unpublishedGroup = document.getElementById("unpublishedGroup");

        if (publishedGroup) {
            publishedGroup.innerHTML = '<div class="notifications-empty">Notifications cleared from view.</div>';
        }

        if (unpublishedGroup) {
            unpublishedGroup.innerHTML = '<div class="notifications-empty">Notifications cleared from view.</div>';
        }
    }

    document.getElementById('viewNotificationsModal')?.addEventListener('click', function (e) {
        if (e.target === this) {
            closeNotificationModal();
        }
    });

    document.getElementById("announcementSummary")?.addEventListener("input", updateAnnouncementSummaryCount);
    updateAnnouncementSummaryCount();
    toggleNotificationsGroup("published");

    /* CLOCK SYSTEM */

    const SHIFT_MINUTES = 480;
    const BREAK_MINUTES = 60;

    let shiftRemaining = SHIFT_MINUTES;
    let breakRemaining = 0;
    let onBreak = false;
    let isClockedIn = false;

    // logic 
    if (attendance) {
        isClockedIn = true;

        // time in calculation
        const [h, m, s] = attendance.time_in.split(":").map(Number);
        const timeIn = new Date();
        timeIn.setHours(h, m, s ?? 0, 0);

        const now = new Date();
        let workedMinutes = Math.floor((now - timeIn) / 60000);

        // total break already taken (in minutes)
        let breakUsed = attendance.break_duration ? attendance.break_duration * 60 : 0;

        // Remaining shift
        shiftRemaining = SHIFT_MINUTES - (workedMinutes - breakUsed);

        // if currently on break
        if (attendance.break_start) {
            onBreak = true;

            const [bh, bm, bs] = attendance.break_start.split(":").map(Number);
            const breakStart = new Date();
            breakStart.setHours(bh, bm, bs ?? 0, 0);

            let breakElapsed = Math.floor((now - breakStart) / 60000);

            // subtract already taken portion of break
            breakRemaining = Math.max(BREAK_MINUTES - (attendance.break_duration * 60 + breakElapsed), 0);
        } else {
            // Not on break: show remaining break time
            breakRemaining = Math.max(BREAK_MINUTES - (attendance.break_duration * 60), 0);
        }
    }

    const ring = document.getElementById("progressRing");
    const dot = document.getElementById("progressDot");
    const shiftTimeEl = document.getElementById("shiftTime");
    const statusBadge = document.getElementById("statusBadge");
    const clockBtn = document.getElementById("clockBtn");
    const breakBtn = document.getElementById("breakBtn");

    if (isClockedIn) {
        statusBadge.innerText = onBreak ? "On Break" : "Clocked In";
    }

    const circumference = 565.48;

    function updateDisplay(){

        let progress;

        if(onBreak){
            progress = breakRemaining / BREAK_MINUTES;
            ring.setAttribute("stroke","#f97316");
        }else{
            progress = shiftRemaining / SHIFT_MINUTES;
            ring.setAttribute("stroke","#B7E824");
        }

        if(progress<0)progress=0;
        if(progress>1)progress=1;

        let dash = progress * circumference;

        ring.setAttribute("stroke-dasharray",dash+" "+circumference);

        let angle = progress * 360;
        let rad = angle * Math.PI / 180;

        let x = 100 + 90 * Math.cos(rad);
        let y = 100 + 90 * Math.sin(rad);

        dot.setAttribute("cx",x);
        dot.setAttribute("cy",y);

        if(onBreak){
            shiftTimeEl.innerText = "Break "+Math.ceil(breakRemaining)+"m";
        }else{
            let hours = Math.floor(shiftRemaining/60);
            let minutes = Math.floor(shiftRemaining%60);
            shiftTimeEl.innerText = hours+":"+minutes.toString().padStart(2,'0');
        }

    }

    setInterval(()=>{

        if(!isClockedIn) return;

        if(onBreak){

        if(breakRemaining>0){
            breakRemaining--;
            updateDisplay();
            if(breakRemaining<=0){
                onBreak=false;
                updateDisplay();
            }
        }

        }else{
            if(shiftRemaining>0){
            shiftRemaining--;
            updateDisplay();
            }
        }
    },60000);

    const now = new Date();

    const days=["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
    const months=["January","February","March","April","May","June","July","August","September","October","November","December"];

    document.getElementById("clockDate").innerText =
        days[now.getDay()] + ", " +
        months[now.getMonth()] + " " +
        now.getDate() + ", " +
        now.getFullYear();

    updateDisplay();
    if (breakBtn) breakBtn.disabled = true;

</script>

<?= $this->endSection() ?>