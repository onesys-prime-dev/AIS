<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $user = auth()->user(); ?>

<style>
body{
    background:#f3f4f6;
    font-family:Segoe UI, Arial, sans-serif;
    margin:0;
}

/* DASHBOARD */
.dashboard{
    height:calc(100vh - 60px);
    display:flex;
    flex-direction:column;
    overflow:hidden;
    padding-left:2rem;
    padding-right:2rem;
    padding-bottom:2rem;
}

/* HEADER */
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:1rem;
    margin-bottom:1rem;
}

    .header h1{
        font-size:28px;
        font-weight:700;
    }

    .request-btn{
        background:#ef4444;
        color:white;
        padding:12px 20px;
        border-radius:10px;
        border:none;
        cursor:pointer;
        font-weight:600;
    }

    .main{
        display:flex;
        gap:28px;
        height: auto;
    }

/* LEFT CARD */
.clock-card{
    flex:2;
    background:white;
    display:flex;
    flex-direction:column;
    border-radius:16px;
    padding:20px 30px;
    box-shadow:0 6px 18px rgba(0,0,0,0.1);
    border:1px solid #e5e7eb;
}

/* TITLE */
.clock-title{
    text-align:center;
    color:#ef4444;
    font-weight:700;
    margin-bottom:10px;
    font-size:22px;
}

/* INNER BOX */
.clock-inner{
    flex:1;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    background:#e5e7eb;
    border-radius:12px;
    padding:20px;
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
    width:240px;
    height:240px;
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
    font-size:40px;
    font-weight:700;
}

/* DATE */
.date{
    font-size:14px;
    color:#4b5563;
    font-weight:500;
    margin-top:5px;
}

/* BUTTONS */
.buttons{
    display:flex;
    gap:12px;
    margin-top:15px;
    align-items:center;
}

.buttons form{
    flex:1;
    display:flex;
}

.clockout{
    width:100%;
    height:46px;
    background:#ef4444;
    border:none;
    color:white;
    border-radius:10px;
    font-weight:600;
    cursor:pointer;
    box-shadow:0 2px 6px rgba(0,0,0,0.15);
}

/* ICON BUTTONS */
.icon-btn{
    width:46px;
    height:46px;
    border:none;
    border-radius:10px;
    background:#f3f4f6;
    background-repeat:no-repeat;
    background-position:center;
    background-size:22px;
    cursor:pointer;
}

.coffee{
    background-image:url("<?= base_url('icons/coffee.png') ?>");
}

.idcard{
    background-image:url("<?= base_url('icons/add_notes.png') ?>");
}

/* BREAK ACTIVE */
.on-break{
    background-color:#fb923c !important;
}

/* RIGHT CARD */
.notifications{
    flex:1;
    background:white;
    border-radius:16px;
    padding:18px;
    box-shadow:0 6px 18px rgba(0,0,0,0.1);
    border:1px solid #e5e7eb;
    overflow-y:auto;
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

/* MODAL OVERLAY */
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

/* MODAL BOX */
.modal{
    background:white;
    width:420px;
    border-radius:14px;
    padding:20px 20px 15px 20px;
    box-shadow:0 25px 50px rgba(0,0,0,0.25);
    animation:modalPop 0.2s ease;
}

/* MODAL HEADER */
.modal-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
    font-weight:600;
    font-size:16px;
}

/* CLOSE BUTTON */
.modal-header button{
    border:none;
    background:none;
    font-size:18px;
    cursor:pointer;
}

/* INPUTS */
.modal input,
.modal select,
.modal textarea{
    width:100%;
    padding:10px;
    border:1px solid #d1d5db;
    border-radius:8px;
    margin-bottom:12px;
    font-size:14px;
}

/* SELECT ARROW */
.modal select{
    appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat:no-repeat;
    background-position:right 10px center;
    padding-right:30px;
}

/* TEXTAREA */
.modal textarea{
    height:80px;
    resize:none;
}

/* FOOTER BUTTONS */
.modal-footer{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-top:5px;
}

.cancel-btn{
    border:none;
    background:#e5e7eb;
    padding:8px 14px;
    border-radius:6px;
    cursor:pointer;
    color:#374151;
}

.submit-btn{
    background:#8b5cf6;
    color:white;
    border:none;
    padding:8px 16px;
    border-radius:6px;
    cursor:pointer;
}

/* POP ANIMATION */
@keyframes modalPop{
    from{
        transform:scale(0.95);
        opacity:0;
    }
    to{
        transform:scale(1);
        opacity:1;
    }
}

</style>

<div class="dashboard">

        <div class="header">
            <div class="p-1">
                <h1>Employee Dashboard</h1>
            </div>
            <button onclick="openRequestForm()" class="request-btn">+ Request Form</button>
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

                <label>Select Form *</label>
                <select name="request_type" required>
                    <option value="leave">Leave Request</option>
                    <option value="overtime">Overtime Request</option>
                    <option value="undertime">Undertime Request</option>
                </select>

                <label>Date *</label>
                <input type="date" name="date" required>

                <label>Message</label>
                <textarea name="reason"></textarea>

                <div class="modal-footer">
                    <button type="button" class="cancel-btn" onclick="closeRequestForm()">Cancel</button>
                    <button type="submit" class="submit-btn">Submit</button>
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