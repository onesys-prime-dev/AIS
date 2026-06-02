<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$user = auth()->user();
$currentAttendance = $currentAttendance ?? null;
?>

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
    display:flex;
    flex-direction:column;
    overflow:hidden;
    max-height:none;
    height:100%;
    min-height:0;
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
    width:25px;
    height:25px;
}

.notifications-clear:hover{
    color:#1f2937;
    background:#f3f4f6;
}

.notifications-tabs{
    display:flex;
    align-items:center;
    gap:10px;
    margin: 12px 20px 15px;
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
    margin:10px 0 10px;
    text-transform:uppercase;
    letter-spacing:0.06em;
}

.notifications-section{
    display:flex;
    flex-direction:column;
    gap:10px;
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
    min-height: 100%;
    display:flex;
    align-items:center;
    justify-content:center;
}

.notif-meta{
    margin-top:6px;
    font-size:11px;
    color:#6b7280;
}

.notifications-group{
    display:none;
    padding: 0 18px 16px 20px;
    overflow-y:auto;
    flex:1;
    margin-bottom:14px; /* keep list content away from card bottom edge */
    min-height:0;
    box-sizing:border-box;
    scrollbar-gutter:stable;
}

.notifications-group.active{
    display: block;
}

/* .notifications-group::after{
    content:'';
    display:block;
    height:18px;
} */

/* NOTIFICATION ITEM */
.notif{
    position:relative;
    display:flex;
    align-items:flex-start;
    gap:12px;
    padding:16px 18px 12px 18px;
    height: 110px;
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
    font-size:12px;
    color:#6b7280;
    font-weight:500;
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
    font-size:16px;
    color:#262626;
    line-height:1.35;
}

.notif-desc{
    font-size:14px;
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

/* Attachments list for announcement modal */
.announcement-attachments-list{
    margin-top:8px;
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.attachment-item{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:6px;
    width:110px;
}
.attachment-preview{
    width:110px;
    height:80px;
    border-radius:8px;
    background:#f8fafc;
    border:1px solid #e5e7eb;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    text-align:center;
    padding:6px;
}
.attachment-preview img{
    max-width:100%;
    max-height:100%;
    object-fit:cover;
}

/* file input + attach button */
.file-input-wrapper{
    display:flex;
    align-items:center;
    gap:8px;
}
.file-upload-button{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:36px;
    height:36px;
    border-radius:8px;
    background:#eef2ff;
    border:1px solid #e5e7eb;
    cursor:pointer;
}
.file-upload-button:hover{ background:#e0e7ff; }
.file-input-hidden{ /* keep native input but hide default UI */
    opacity:0;
    position:relative;
    width:1px;
    height:1px;
    overflow:hidden;
}
.announcement-attachments-name{
    font-size:13px;
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
/* ── Request form: approval-code toggle ── */
.req-toggle-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    margin-bottom: 14px;
}

.req-toggle-label {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
}

.req-toggle-switch {
    position: relative;
    width: 38px;
    height: 22px;
    flex-shrink: 0;
}
.req-toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.req-toggle-track {
    position: absolute;
    inset: 0;
    background: #d1d5db;
    border-radius: 999px;
    cursor: pointer;
    transition: background 0.2s;
}
.req-toggle-track::before {
    content: '';
    position: absolute;
    height: 16px;
    width: 16px;
    left: 3px;
    top: 3px;
    background: #fff;
    border-radius: 50%;
    transition: transform 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.req-toggle-switch input:checked + .req-toggle-track {
    background: #ef4444;
}
.req-toggle-switch input:checked + .req-toggle-track::before {
    transform: translateX(16px);
}

/* greyed-out normal fields while in code mode */
#normalModePanel.req-disabled {
    opacity: 0.4;
    pointer-events: none;
    user-select: none;
}

.req-code-hint {
    font-size: 12px;
    color: #6b7280;
    background: #fef9c3;
    border: 1px solid #fde68a;
    border-radius: 8px;
    padding: 8px 12px;
    margin-bottom: 10px;
    line-height: 1.5;
}

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

<script>
document.addEventListener('DOMContentLoaded', function () {
    ['toast-success', 'toast-error'].forEach(function (toastId) {
        const toast = document.getElementById(toastId);
        if (!toast) {
            return;
        }

        setTimeout(function () {
            toast.classList.remove('opacity-0', 'scale-95');
        }, 100);

        setTimeout(function () {
            toast.classList.add('opacity-0', 'scale-95');
        }, 3000);

        setTimeout(function () {
            toast.remove();
        }, 3500);
    });
});
</script>

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
                    <div id="toast-success" class="opacity-0 scale-95 transition-all duration-300" style="background:#dcfce7;padding:10px;border-radius:6px;margin-bottom:15px;">
                        <?= session()->getFlashdata('success') ?>
                    </div>
                <?php endif; ?>

                <?php if(session()->getFlashdata('error')): ?>
                    <div id="toast-error" class="opacity-0 scale-95 transition-all duration-300" style="background:#fecaca;padding:10px;border-radius:6px;margin-bottom:15px;">
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
                    class="icon-btn coffee <?= ($currentAttendance['break_start'] && !$currentAttendance['break_end']) ? 'on-break' : '' ?>"
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
                if ($diff < 604800) {
                    return floor($diff / 86400) . 'd ago';
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
                $notification = notification_data($n);
                ?>
                <div
                    class="notif <?= ! empty($n['is_viewed']) ? 'notif-viewed' : '' ?>"
                    role="button"
                    tabindex="0"
                    data-id="<?= esc((string) $notification['id'], 'attr') ?>"
                    data-title="<?= esc($notification['title'], 'attr') ?>"
                    
                    data-details="<?= esc($notification['details'], 'attr') ?>"
                    data-publish="<?= esc($notification['published_at'] !== '' ? date('M d, Y h:i A', strtotime($notification['published_at'])) : 'N/A', 'attr') ?>"
                    data-category="<?= esc($notification['category'], 'attr') ?>"
                    data-priority="<?= esc(ucfirst($notification['priority']), 'attr') ?>"
                    data-audience="<?= esc(ucfirst($notification['audience']), 'attr') ?>"
                    data-status="<?= esc($notification['status'], 'attr') ?>"
                    data-source="<?= esc($notification['source'], 'attr') ?>"
                    data-request-id="<?= esc((string) $notification['request_id'], 'attr') ?>"
                    data-actor-name="<?= esc($notification['actor_name'], 'attr') ?>"
                    data-actor-position="<?= esc($notification['actor_position'], 'attr') ?>"
                    data-author-name="<?= esc($notification['author_name'], 'attr') ?>"
                    data-author-photo="<?= esc($notification['author_photo'], 'attr') ?>"
                    data-requester-name="<?= esc($notification['requester_name'], 'attr') ?>"
                    data-requester-department="<?= esc($notification['requester_department'], 'attr') ?>"
                    data-requester-photo="<?= esc($notification['requester_photo'], 'attr') ?>"
                    data-request-type="<?= esc($notification['request_type'], 'attr') ?>"
                    data-attachments="<?= esc(json_encode($notification['attachments'] ?? []), 'attr') ?>"
                    onclick="openNotificationModal(this)"
                    onkeydown="handleNotificationKey(event, this)">
                    <div class="notif-avatar"><?= esc((string) $initials((string) ($n['requester_name'] ?? $notification['title'] ?? ''))) ?></div>
                    <div class="notif-content">
                        <div class="notif-title-row">
                            <div class="notif-title"><?= esc($notification['title']) ?></div>
                            <div class="notif-time"><?= esc((string) $timeAgo((string) ($notification['published_at'] ?? ''))) ?></div>
                        </div>
                        <div class="notif-desc"><?= esc($notification['details']) ?></div>
                        <div class="notif-tags">
                            <span class="notif-pill priority-<?= strtolower($notification['priority']) ?>"><?= esc(ucfirst($notification['priority'])) ?></span>
                            <span class="notif-pill muted"><?= esc(ucfirst($notification['category'])) ?></span>
                        </div>
                        <?php if ($showMeta): ?>
                        <div class="notif-meta">Publish on <?= esc(date('M d, Y h:i A', strtotime((string) ($notification['published_at'] ?? 'now')))) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            };

            $publishedBuckets = $groupNotificationsByPeriod($publishedItems);
            $unpublishedBuckets = $groupNotificationsByPeriod($unpublishedItems);

            $hasPublishedTodayItems = ! empty($publishedBuckets['today']['items']);
            $hasPublishedOlderItems = ! empty($publishedBuckets['older']['items']);
            $hasPublishedItems = $hasPublishedTodayItems || $hasPublishedOlderItems;

            $hasUnpublishedTodayItems = ! empty($unpublishedBuckets['today']['items']);
            $hasUnpublishedOlderItems = ! empty($unpublishedBuckets['older']['items']);
            $hasUnpublishedItems = $hasUnpublishedTodayItems || $hasUnpublishedOlderItems;

            $renderNotificationSection = static function (string $sectionClass, string $title, array $items, callable $renderItem, callable $initials, callable $timeAgo, bool $showMeta = false): void {
                if (empty($items)) {
                    return;
                }
                ?>
                <div class="notifications-section <?= esc($sectionClass, 'attr') ?>">
                    <div class="notifications-divider-title"><?= esc($title) ?></div>
                    <?php foreach ($items as $n): ?>
                        <?php $renderItem($n, $initials, $timeAgo, $showMeta); ?>
                    <?php endforeach; ?>
                </div>
                <?php
            };
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

            <div id="publishedGroup" class="notifications-group hide-scroll-bar active">
                <?php if ($hasPublishedItems): ?>
                    <?php
                        if ($hasPublishedTodayItems) {
                            $renderNotificationSection('today', $publishedBuckets['today']['title'], $publishedBuckets['today']['items'], $renderNotificationItem, $initials, $timeAgo);
                        }

                        if ($hasPublishedOlderItems) {
                            $renderNotificationSection('older', $publishedBuckets['older']['title'], $publishedBuckets['older']['items'], $renderNotificationItem, $initials, $timeAgo);
                        }
                    ?>
                <?php else: ?>
                    <div class="notifications-empty">No notifications</div>
                <?php endif; ?>
            </div>

            <?php if ($user->inGroup('client_payroll_staff')): ?>
            <div id="unpublishedGroup" class="notifications-group hide-scroll-bar">
                <?php if ($hasUnpublishedItems): ?>
                    <?php
                        if ($hasUnpublishedTodayItems) {
                            $renderNotificationSection('today', $unpublishedBuckets['today']['title'], $unpublishedBuckets['today']['items'], $renderNotificationItem, $initials, $timeAgo, true);
                        }

                        if ($hasUnpublishedOlderItems) {
                            $renderNotificationSection('older', $unpublishedBuckets['older']['title'], $unpublishedBuckets['older']['items'], $renderNotificationItem, $initials, $timeAgo, true);
                        }
                    ?>
                <?php else: ?>
                    <div class="notifications-empty">No notifications</div>
                <?php endif; ?>
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
                    <textarea id="taskPerformed" name="task_performed" placeholder="e.g. Coding Session" required></textarea>

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
            <form id="requestForm" action="<?= site_url('client_portal/requests/store') ?>" method="post">
                <!-- tells the controller which mode we're in -->
                <input type="hidden" name="code_mode" id="codeModeInput" value="0">

                <div class="modal-header">
                    <span>Request Form</span>
                    <button type="button" onclick="closeRequestForm()">✕</button>
                </div>

                <div class="modal-body">

                    <!-- ── Code-mode toggle ── -->
                    <div class="req-toggle-row">
                        <label class="req-toggle-switch" for="codeModeToggle">
                            <input type="checkbox" id="codeModeToggle" onchange="toggleRequestCodeMode(this.checked)">
                            <span class="req-toggle-track"></span>
                        </label>
                        <span class="req-toggle-label">I have an approval code</span>
                    </div>

                    <!-- ── Code-mode panel (shown when toggle is ON) ── -->
                    <div id="codeModePanel" style="display:none;">
                        <div class="req-code-hint" id="codeHintText">
                            Enter your approval code to continue.
                        </div>
                        <label>Approval Code</label>
                        <input type="text" id="codeModeCodeInput" name="approval_code"
                               placeholder="e.g. A3KM-PQ72-XYRS-84NV"
                               style="text-transform:uppercase;letter-spacing:2px;font-family:monospace;font-size:15px;"
                               oninput="this.value=this.value.toUpperCase()"
                               onblur="applyApprovalCode(this.value)">
                    </div>

                    <!-- ── Normal-mode fields (always visible, locked only when preset data exists) ── -->
                    <div id="normalModePanel">
                        <label>Select Form</label>
                        <select name="request_type" id="reqType" required>
                            <option value="leave">Leave Request</option>
                            <option value="overtime">Overtime Request</option>
                            <option value="undertime">Undertime Request</option>
                        </select>

                        <label>Start Date</label>
                        <input type="date" name="start_date" id="reqStartDate" required>

                        <label>End Date</label>
                        <input type="date" name="end_date" id="reqEndDate">

                        <label>Message</label>
                        <textarea rows="3" name="reason" id="reqReason" placeholder="Enter your reason..." required></textarea>
                    </div>

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
            <form id="announcementForm" action="<?= site_url('client_portal/announcements/store') ?>" method="post" enctype="multipart/form-data" class="announcement-form">
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
                            <label>Attachments</label>
                            <div class="file-input-wrapper">
                                <button type="button" id="file-upload-button" class="file-upload-button" aria-label="Attach files">
                                    <!-- paperclip / attach SVG -->
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M21.44 11.05l-8.49 8.49a5 5 0 01-7.07 0 5 5 0 010-7.07l8.49-8.49a3.5 3.5 0 014.95 4.95L11.83 18.97a2 2 0 01-2.83 0 2 2 0 010-2.83l6.36-6.36" stroke="#374151" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <input class="file-input-hidden" type="file" id="announcementAttachments" name="attachments[]" multiple accept="image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                                <span id="announcementAttachmentsName" class="announcement-attachments-name">No files chosen</span>
                            </div>
                            <div id="announcementAttachmentsList" class="announcement-attachments-list"></div>
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
        // Reset toggle back to normal mode when closing
        const toggle = document.getElementById("codeModeToggle");
        if (toggle && toggle.checked) {
            toggle.checked = false;
            toggleRequestCodeMode(false);
        }
        resetPresetState();
        document.getElementById("requestForm").reset();
    }

    function toggleRequestCodeMode(on) {
        const codePanel   = document.getElementById("codeModePanel");
        const hiddenInput = document.getElementById("codeModeInput");

        if (on) {
            codePanel.style.display = "block";
            hiddenInput.value       = "1";
            // Normal fields stay visible + required; they only get locked if
            // a looked-up code carries preset data (see applyApprovalCode).
            setNormalFieldsLocked(false);
            document.getElementById("codeModeCodeInput").focus();
        } else {
            codePanel.style.display = "none";
            hiddenInput.value       = "0";
            // Clear code input and unlock all normal fields
            document.getElementById("codeModeCodeInput").value = "";
            resetPresetState();
        }
    }

    // Tracks active preset duration (days) when a code provides one
    let _currentPresetDuration = null;

    // Helper: format Date -> YYYY-MM-DD
    function _formatYMD(d) {
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    }

    // Set end-date `max` based on start date + (duration - 1)
    function setEndDateMaxForPreset(duration) {
        const startEl = document.getElementById('reqStartDate');
        const endEl   = document.getElementById('reqEndDate');
        if (!endEl) return;

        _currentPresetDuration = duration !== null ? Number(duration) : null;

        if (_currentPresetDuration === null) {
            endEl.removeAttribute('max');
            return;
        }

        // If start date exists, compute max; otherwise do not set max yet
        const startVal = startEl ? startEl.value : '';
        if (!startVal) {
            endEl.removeAttribute('max');
            return;
        }

        const startDate = new Date(startVal + 'T00:00:00');
        if (isNaN(startDate.getTime())) {
            endEl.removeAttribute('max');
            return;
        }

        const maxDate = new Date(startDate);
        maxDate.setDate(maxDate.getDate() + (_currentPresetDuration - 1));
        const maxYmd = _formatYMD(maxDate);
        endEl.setAttribute('max', maxYmd);

        // If end empty, default it to the computed end date
        if (!endEl.value) {
            endEl.value = maxYmd;
        }

        // Enforce current value not past max
        if (endEl.value > maxYmd) {
            endEl.value = maxYmd;
            const hint = document.getElementById('codeHintText');
            if (hint) {
                hint.style.background  = '#fef3c7';
                hint.style.borderColor = '#facc15';
                hint.innerHTML = `⚠️ End date limited to ${_currentPresetDuration} day${_currentPresetDuration === 1 ? '' : 's'} by preset.`;
            }
        }
    }

    // Enforce end date does not exceed max when user edits it
    function enforceEndDateLimitOnChange() {
        const endEl = document.getElementById('reqEndDate');
        if (!endEl) return;
        const max = endEl.getAttribute('max');
        if (!max) return;
        if (endEl.value && endEl.value > max) {
            endEl.value = max;
            const hint = document.getElementById('codeHintText');
            if (hint) {
                hint.style.background  = '#fef3c7';
                hint.style.borderColor = '#facc15';
                hint.innerHTML = `⚠️ End date cannot exceed the preset duration (${_currentPresetDuration} day${_currentPresetDuration === 1 ? '' : 's'}).`;
            }
        }
    }

    // Attach listeners so start/end changes update enforcement
    (function attachPresetDateListeners() {
        const startEl = document.getElementById('reqStartDate');
        const endEl   = document.getElementById('reqEndDate');
        if (startEl) {
            startEl.addEventListener('change', () => {
                if (_currentPresetDuration !== null) setEndDateMaxForPreset(_currentPresetDuration);
            });
        }
        if (endEl) {
            endEl.addEventListener('change', enforceEndDateLimitOnChange);
            endEl.addEventListener('input', enforceEndDateLimitOnChange);
        }
    })();

    // Lock/unlock individual preset-capable fields
    function setNormalFieldsLocked(locked) {
        const fields = ["reqType", "reqStartDate", "reqEndDate", "reqReason"];
        fields.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.disabled = locked;
            el.style.opacity = locked ? "0.5" : "";
        });
    }

    function resetPresetState() {
        _currentPresetDuration = null;
        setNormalFieldsLocked(false);
        // Remove any end-date max applied by a preset
        const endEl = document.getElementById('reqEndDate');
        if (endEl) endEl.removeAttribute('max');
        // Restore field values to empty
        document.getElementById("reqType").value      = "leave";
        document.getElementById("reqStartDate").value = "";
        document.getElementById("reqEndDate").value   = "";
        document.getElementById("reqReason").value    = "";
        // Restore required on text fields
        document.getElementById("reqType").required      = true;
        document.getElementById("reqStartDate").required = true;
        document.getElementById("reqReason").required    = true;
        // Reset hint
        const hint = document.getElementById("codeHintText");
        if (hint) {
            hint.style.background = "";
            hint.style.borderColor = "";
            hint.innerHTML = "Enter your approval code to continue.";
        }
    }

    async function applyApprovalCode(rawCode) {
        const code = rawCode.trim().toUpperCase();
        if (!code) return;

        const hint = document.getElementById("codeHintText");

        try {
            const res  = await fetch("<?= site_url('client_portal/requests/lookup-code') ?>?code=" + encodeURIComponent(code));
            const data = await res.json();

            if (!data.success) {
                // Invalid/used code — show warning but don't block the fields
                hint.style.background  = "#fee2e2";
                hint.style.borderColor = "#fca5a5";
                hint.innerHTML = "⚠️ " + (data.message || "Invalid or already-used code.");
                setNormalFieldsLocked(false);
                return;
            }

            const preset = data.preset_data || {};
            const presetDuration = preset.duration && Number(preset.duration) > 0 ? Number(preset.duration) : null;

            setNormalFieldsLocked(false);
            hint.style.background  = "#dcfce7";
            hint.style.borderColor = "#86efac";
            hint.innerHTML = presetDuration !== null
                ? `✅ Code accepted. Duration preset: ${presetDuration} day${presetDuration === 1 ? "" : "s"}.`
                : "✅ Code accepted. You can fill in the request details below.";

            // Activate client-side enforcement for end date when preset duration exists
            if (presetDuration !== null) {
                setEndDateMaxForPreset(presetDuration);
            } else {
                setEndDateMaxForPreset(null);
            }

        } catch (e) {
            hint.style.background  = "#fee2e2";
            hint.style.borderColor = "#fca5a5";
            hint.innerHTML = "⚠️ Could not verify the code. Please check your connection and try again.";
        }
    }

    function openAnnouncementForm() {
        document.getElementById("announcementModal").style.display="flex";
    }

    function closeAnnouncementForm() {
        document.getElementById("announcementModal").style.display="none";
        document.getElementById("announcementForm").reset();
        toggleAnnouncementAudience();
        
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

    async function clearNotificationsView() {
        if (!window.notificationDismissedUrl || !window.notificationCsrfTokenName || !window.notificationCsrfTokenHash) {
            return;
        }

        if (!window.confirm('Clear all notifications from your account?')) {
            return;
        }

        const payload = new URLSearchParams();
        payload.append('dismiss_all', '1');
        payload.append(window.notificationCsrfTokenName, window.notificationCsrfTokenHash);

        try {
            const response = await fetch(window.notificationDismissedUrl, {
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
                return;
            }

            window.location.reload();
        } catch (error) {
            console.error('Unable to clear notifications for current user.', error);
        }
    }

    document.getElementById('viewNotificationsModal')?.addEventListener('click', function (e) {
        if (e.target === this) {
            closeNotificationModal();
        }
    });

    
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

        // time_in is now a full datetime string e.g. "2026-06-02 09:00:00"
        // Replace space with T so all browsers parse it correctly
        const timeIn = new Date(attendance.time_in.replace(' ', 'T'));

        const now = new Date();
        let workedMinutes = Math.floor((now - timeIn) / 60000);

        // total break already taken (in minutes)
        let breakUsed = attendance.break_duration ? attendance.break_duration * 60 : 0;

        // Remaining shift
        shiftRemaining = SHIFT_MINUTES - (workedMinutes - breakUsed);

        // if currently on break (break_start set but break_end not yet set)
        if (attendance.break_start && !attendance.break_end) {
            onBreak = true;

            // break_start is also a full datetime string now
            const breakStart = new Date(attendance.break_start.replace(' ', 'T'));

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

<script>
    // Preview and removal helpers for announcement attachments
    let _announcementSelectedFiles = [];

    function handleAnnouncementAttachments(filesArray) {
        const list = document.getElementById('announcementAttachmentsList');
        if (!list) return;
        list.innerHTML = '';
        if (!filesArray || filesArray.length === 0) return;
        const files = Array.from(filesArray);
        files.forEach((file, i) => {
            const item = document.createElement('div');
            item.className = 'attachment-item';

            const preview = document.createElement('div');
            preview.className = 'attachment-preview';

            if (file.type && file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.onload = () => URL.revokeObjectURL(img.src);
                preview.appendChild(img);
            } else {
                const span = document.createElement('span');
                span.textContent = file.name;
                preview.appendChild(span);
            }

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'cancel-btn';
            removeBtn.textContent = 'Remove';
            removeBtn.addEventListener('click', function () {
                removeAnnouncementAttachment(i);
            });

            item.appendChild(preview);
            item.appendChild(removeBtn);
            list.appendChild(item);
        });
    }

    function removeAnnouncementAttachment(index) {
        const input = document.getElementById('announcementAttachments');
        if (!input) return;
        // remove from our array
        if (index >= 0 && index < _announcementSelectedFiles.length) {
            _announcementSelectedFiles.splice(index, 1);
        }
        // sync back to input.files
        const dt = new DataTransfer();
        _announcementSelectedFiles.forEach(f => dt.items.add(f));
        input.files = dt.files;
        handleAnnouncementAttachments(_announcementSelectedFiles);
        updateNameFromArray(_announcementSelectedFiles);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('announcementAttachments');
        const btn = document.getElementById('file-upload-button');
        const nameEl = document.getElementById('announcementAttachmentsName');

        function updateNameFromArray(arr){
            if (!nameEl) return;
            if (!arr || arr.length === 0) {
                nameEl.textContent = 'No files chosen';
                return;
            }
            if (arr.length === 1) {
                nameEl.textContent = arr[0].name;
            } else {
                nameEl.textContent = arr.length + ' files chosen';
            }
        }

        if (el) {
            el.addEventListener('change', function (e) {
                const added = Array.from(e.target.files || []);
                // merge new files into our array (avoid duplicates by name+size+type)
                added.forEach(f => {
                    const exists = _announcementSelectedFiles.some(existing =>
                        existing.name === f.name && existing.size === f.size && existing.type === f.type
                    );
                    if (!exists) _announcementSelectedFiles.push(f);
                });

                // sync back to the hidden input
                const dt = new DataTransfer();
                _announcementSelectedFiles.forEach(f => dt.items.add(f));
                el.files = dt.files;

                handleAnnouncementAttachments(_announcementSelectedFiles);
                updateNameFromArray(_announcementSelectedFiles);
            });
        }

        if (btn && el) {
            btn.addEventListener('click', function () { el.click(); });
        }
    });
</script>

<?= $this->endSection() ?>