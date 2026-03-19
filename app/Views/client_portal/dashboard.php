<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $user = auth()->user(); ?>

<style>

    body{
        background:#f3f4f6;
        font-family:Segoe UI, Arial, sans-serif;
    }

    .dashboard{
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        min-height: 0;
        padding-left: 2rem;
        padding-right: 2rem;
        padding-bottom: 2rem;
    }

    .header{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-top: 1rem;
        margin-bottom: 1rem;
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

    .clock-card{
        flex:2;
        background:white;
        height: 100%;
        display:flex;
        flex-direction:column;
        border-radius:16px;
        padding:15px 30px 25px 30px;
        box-shadow:0 6px 18px rgba(0,0,0,0.1);
        border:1px solid #e5e7eb;
    }

    .clock-title{
        text-align:center;
        color:#ef4444;
        font-weight:700;
        margin-bottom:10px;
        font-size: x-large;
    }

    .clock-inner{
        flex:1;
        display:flex;
        flex-direction:column;
        justify-content:center;
        align-items:center;
        gap:8px;
        background:#e5e7eb;
        border-radius:12px;
        padding:35px;
        text-align:center;
    }

    .status{
        font-size:clamp(14px, 1vw, 18px);
        color:#6b7280;
        margin-bottom:0;
        font-weight: 500;
    }

    .status span{
        background:#bbf7d0;
        color:#065f46;
        padding:4px 10px;
        border-radius:20px;
        font-size:clamp(13px, 0.85vw, 15px);
    }

    .clock{
        width:clamp(220px, 28vw, 320px);
        aspect-ratio:1/1;
        margin:0;
        position:relative;
    }

    .clock svg{
        width:100%;
        height:100%;
        transform:rotate(-90deg);
    }

    .shift-time{
        position:absolute;
        top:50%;
        left:50%;
        transform:translate(-50%,-50%);
        font-size:clamp(32px, 3vw, 42px);
        font-weight:700;
    }

    .date{
        margin-top:0;
        font-size:clamp(14px, 0.95vw, 16px);
        color:#4b5563;
        font-weight:500;
    }

    .buttons{
        display:flex;
        gap:10px;
        margin-top:20px;
    }

    .icon-btn{
        width:44px;
        height:44px;
        border:none;
        border-radius:10px;
        background:#f3f4f6;
        position:relative;
        cursor:pointer;
    }

    .coffee::before{
        content:'';
        width:16px;
        height:10px;
        border:2px solid #374151;
        border-bottom-left-radius:4px;
        border-bottom-right-radius:4px;
        border-top:none;
        position:absolute;
        bottom:14px;
        left:13px;
    }

    .coffee::after{
        content:'';
        width:6px;
        height:6px;
        border:2px solid #374151;
        border-radius:50%;
        position:absolute;
        right:10px;
        bottom:16px;
    }

    .coffee span{
        position:absolute;
        top:10px;
        width:2px;
        height:8px;
        background:#374151;
    }

    .coffee span:nth-child(1){left:16px;}
    .coffee span:nth-child(2){left:20px;}
    .coffee span:nth-child(3){left:24px;}

    .idcard::before{
        content:'';
        width:18px;
        height:12px;
        border:2px solid #374151;
        border-radius:2px;
        position:absolute;
        top:14px;
        left:12px;
    }

    .idcard::after{
        content:'';
        width:5px;
        height:5px;
        background:#374151;
        border-radius:50%;
        position:absolute;
        left:15px;
        top:18px;
    }

    .idcard span{
        position:absolute;
        width:6px;
        height:2px;
        background:#374151;
        right:10px;
    }

    .idcard span:nth-child(1){top:18px;}
    .idcard span:nth-child(2){top:22px;}

    .clockout{
        flex:1;
        background:#ef4444;
        border:none;
        color:white;
        padding:12px;
        border-radius:10px;
        font-weight:600;
        cursor:pointer;
    }

    .notifications{
        flex:1;
        background:white;
        border-radius:16px;
        padding:18px;
        box-shadow:0 6px 18px rgba(0,0,0,0.1);
        border:1px solid #e5e7eb;
    }

    .notifications-title{
        color:#ef4444;
        font-weight:700;
        margin-bottom:10px;
    }

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
        background:rgba(0,0,0,0.4);
        display:none;
        align-items:center;
        justify-content:center;
        z-index:999;
    }

    .modal{
        background:white;
        width:420px;
        border-radius:12px;
        padding:20px;
        box-shadow:0 20px 40px rgba(0,0,0,0.2);
        overflow:visible;
        z-index:1000;
    }

    .modal-header{
        display:flex;
        justify-content:space-between;
        margin-bottom:15px;
        font-weight:600;
    }

    .modal input,
    .modal select,
    .modal textarea{
        width:100%;
        padding:10px;
        border:1px solid #d1d5db;
        border-radius:6px;
        margin-bottom:12px;
    }

    .modal select{
        position:relative;
        z-index:1000;
        appearance:none;
        background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23374151' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat:no-repeat;
        background-position:right 10px center;
        padding-right:30px;
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
        background:none;
        cursor:pointer;
        color:#6b7280;
    }

    .submit-btn{
        background:#8b5cf6;
        color:white;
        border:none;
        padding:8px 16px;
        border-radius:6px;
        cursor:pointer;
    }

    .buttons form{
        flex:1;
    }

    .buttons form button{
        width:100%;
    }

    .on-break{
    background:#f97316;
    }

    .checkbox-container {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
        font-size: 14px;
        cursor: pointer;
    }

    .checkbox-container input[type="checkbox"] {
        width: 16px;
        height: 16px;
        margin-top: 12px;
        cursor: pointer;
    }

</style>

<div class="dashboard">

        <div class="header">
            <h1>Employee Dashboard</h1>
            <button onclick="openRequestForm()" class="request-btn">+ Request Form</button>
        </div>

    <div class=" h-full relative flex flex-row min-h-0 gap-8">

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