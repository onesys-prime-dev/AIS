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
    min-height:100vh;
    display:flex;
    flex-direction:column;
    align-items:center; /* center everything */
    padding:20px;
    box-sizing:border-box;
}

/* LIMIT CONTENT WIDTH */
.header,
.dashboard-main{
    width:100%;
    max-width:1300px; /* same width for header and content */
}

/* HEADER */
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
}

/* TITLE */
.header h1{
    font-size:clamp(20px, 2.5vw, 28px);
    font-weight:700;
    margin:0;
}

/* BUTTON */
.request-btn{
    background:#ef4444;
    color:white;
    padding:10px 18px;
    border-radius:10px;
    border:none;
    cursor:pointer;
    font-weight:600;
    box-shadow:0 2px 6px rgba(0,0,0,0.15);
    display:inline-flex;
    align-items:center;
    gap:8px;
}

.request-btn:hover{
    background:#dc2626;
}

/* MAIN LAYOUT */
.dashboard-main{
    display:flex;
    justify-content:center;
    gap:20px;
    flex-wrap:wrap;
}

/* CLOCK CARD */
.clock-card{
    flex:1 1 600px;
    max-width:900px;
    background:white;
    display:flex;
    flex-direction:column;
    border-radius:16px;
    padding:20px;
    box-shadow:0 6px 18px rgba(0,0,0,0.1);
    border:1px solid #e5e7eb;
    justify-content:flex-start;
}

/* TITLE */
.clock-title{
    text-align:center;
    color:#ef4444;
    font-weight:700;
    margin-bottom:10px;
    font-size:clamp(18px, 2vw, 22px);
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
    flex:0; /* IMPORTANT - removes big gap */
}

/* STATUS */
.status{
    font-size:14px;
    color:#6b7280;
    font-weight:500;
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
    font-size:14px;
    color:#4b5563;
    font-weight:500;
    margin-top:8px;
}

/* BUTTONS */
.buttons{
    display:flex;
    gap:12px;
    align-items:center;
    width:100%;
    margin-top:5px;
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
}

.clockout:hover{
    background:#dc2626;
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
}

/* .icon-btn:hover{
    filter: var(--sidebar-icon-filter);
} */

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
    flex:1 1 280px;
    max-width:380px;
    background:white;
    border-radius:16px;
    padding:18px;
    box-shadow:0 6px 18px rgba(0,0,0,0.1);
    border:1px solid #e5e7eb;
    overflow-y:auto;
    max-height:520px;
}

/* NOTIFICATION TITLE */
.notifications-title{
    color:#ef4444;
    font-weight:700;
    margin-bottom:10px;
}

/* NOTIFICATION ITEM */
.notif{
    display:flex;
    gap:10px;
    padding:10px;
    border-radius:10px;
    background:#f3f4f6;
    margin-bottom:10px;
}

.notif.success{
    background:#ede9fe;
}

.notif-icon{
    width:26px;
    height:26px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-weight:bold;
}

.success-icon{background:#8b5cf6;}
.success-icon::before{content:'✓';}

.error-icon{background:#ef4444;}
.error-icon::before{content:'!';}

.notif-title{
    font-weight:700;
    font-size:13px;
}

.notif-desc{
    font-size:12px;
    color:#6b7280;
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
    padding:20px;
    box-shadow:0 25px 50px rgba(0,0,0,0.25);
    animation:modalPop 0.2s ease;
}

.modal-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
    font-weight:600;
}

.modal input,
.modal select,
.modal textarea{
    width:100%;
    padding:10px;
    border:1px solid #d1d5db;
    border-radius:8px;
    margin-top: 5px;
    margin-bottom:12px;
}

.modal textarea{
    height:80px;
    resize:none;
}

.modal-footer{
    display:flex;
    justify-content:flex-end;
    gap:10px;
}

.cancel-btn{
    border:none;
    background:#e5e7eb;
    padding:8px 14px;
    border-radius:6px;
    cursor:pointer;
}

.submit-btn{
    background:#8b5cf6;
    color:white;
    border:none;
    padding:8px 16px;
    border-radius:6px;
    cursor:pointer;
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

.announcement-form .modal-header{
    padding:16px 20px;
    margin-bottom:0;
    border-bottom:1px solid #e5e7eb;
}

.announcement-body{
    flex:1;
    min-height:0;
    padding:14px 18px;
    overflow-y:auto;
}

.announcement-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px 12px;
}

.announcement-field{
    display:flex;
    flex-direction:column;
}

.announcement-field.full-span{
    grid-column:1 / -1;
}

.announcement-field label{
    font-size:13px;
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
    min-height:84px;
}

.announcement-counter{
    text-align:right;
    font-size:12px;
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

.announcement-form .modal-footer{
    flex-shrink:0;
    padding:14px 20px;
    border-top:1px solid #e5e7eb;
    background:#f9fafb;
}

@keyframes modalPop{
    from{ transform:scale(0.95); opacity:0; }
    to{ transform:scale(1); opacity:1; }
}

/* MOBILE */
@media (max-width: 900px){
    .dashboard-main{
        flex-direction:column;
        align-items:center;
    }

    .notifications{
        max-width:900px;
        width:100%;
    }

    .clock-card{
        width:100%;
    }

    .announcement-grid{
        grid-template-columns:1fr;
    }
}
</style>

<div class="dashboard">

        <div class="header">
            <div class="p-1">
                <h1>Employee Dashboard</h1>
            </div>
            <div class="button-holder">
                <button onclick="openAnnouncementForm()" class="request-btn">
                    <img src="<?= base_url('icons/announcement.png') ?>" alt="Announcement" class="btn-icon">
                    <span>Announcement</span>
                </button>
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

        <div class="notifications">
            <div class="notifications-title">
                Notifications ●
            </div>
            <?php foreach($notifications ?? [] as $n): ?>
            <div class="notif <?= $n['type']=='success'?'success':'' ?>">
                <div class="notif-icon <?= $n['type']=='success'?'success-icon':'error-icon' ?>"></div>
                <div>
                    <div class="notif-title">
                        <?= esc($n['title']) ?>
                    </div>

                    <div class="notif-desc">
                        <?= esc($n['message']) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
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
                <input type="text" name="approval_code">

                <label>Message</label>
                <textarea name="reason" required></textarea>

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
            <form id="announcementForm" action="#" method="post" class="announcement-form">
                <div class="modal-header">
                    <span>Create Announcement</span>
                    <button type="button" onclick="closeAnnouncementForm()">✕</button>
                </div>

                <div class="announcement-body scroll-bar">
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

    document.getElementById("announcementSummary")?.addEventListener("input", updateAnnouncementSummaryCount);
    updateAnnouncementSummaryCount();

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