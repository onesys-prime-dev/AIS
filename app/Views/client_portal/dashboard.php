<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $user = auth()->user(); ?>

<style>

body{
background:#f3f4f6;
font-family:Segoe UI, Arial, sans-serif;
}

.dashboard{
max-width:1200px;
margin:auto;
padding:30px;
}

.header{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:25px;
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
}

.clock-card{
flex:2;
background:white;
border-radius:16px;
padding:20px;
box-shadow:0 6px 18px rgba(0,0,0,0.1);
border:1px solid #e5e7eb;
}

.clock-title{
text-align:center;
color:#ef4444;
font-weight:600;
margin-bottom:10px;
}

.clock-inner{
background:#e5e7eb;
border-radius:12px;
padding:35px;
text-align:center;
}

.status{
font-size:13px;
color:#6b7280;
margin-bottom:10px;
}

.status span{
background:#bbf7d0;
color:#065f46;
padding:4px 10px;
border-radius:20px;
font-size:12px;
}

.clock{
width:220px;
height:220px;
margin:auto;
position:relative;
}

.clock svg{
transform:rotate(-90deg);
}

.shift-time{
position:absolute;
top:50%;
left:50%;
transform:translate(-50%,-50%);
font-size:34px;
font-weight:700;
}

.date{
margin-top:10px;
font-size:13px;
color:#6b7280;
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

</style>

<div class="dashboard">

<div class="header">
<h1>Employee Dashboard</h1>
<button onclick="openRequestForm()" class="request-btn">+ Request Form</button>
</div>

<div class="main">

<div class="clock-card">

<div class="clock-title">
Welcome back, <?= esc($user->username) ?>!
</div>

<div class="clock-inner">

<div class="status">
Status : <span id="statusBadge">Clocked Out</span>
</div>

<div class="clock">

<svg viewBox="0 0 200 200" width="220" height="220">

<circle cx="100" cy="100" r="90" stroke="#d1d5db" stroke-width="12" fill="none"/>

<circle id="progressRing" cx="100" cy="100" r="90" stroke="#84cc16" stroke-width="12" fill="none" stroke-linecap="round"/>

<circle id="progressDot" cx="100" cy="10" r="6" fill="white" stroke="#FFFEFE" stroke-width="4"/>

</svg>

<div id="shiftTime" class="shift-time">8:00</div>

</div>

<div id="clockDate" class="date"></div>

</div>

<div class="buttons">

<button onclick="startBreak()" class="icon-btn coffee" id="breakBtn">
<span></span><span></span><span></span>
</button>

<button onclick="openTaskLog()" class="icon-btn idcard">
<span></span><span></span>
</button>

<button onclick="toggleClock()" class="clockout" id="clockBtn">
Clock In
</button>

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

<label>Start Time *</label>
<input type="time">

<label>End Time *</label>
<input type="time">

<label>Assigned Task *</label>
<input type="text">

<div class="modal-footer">
<button class="cancel-btn" onclick="closeTaskLog()">Cancel</button>
<button class="submit-btn">Log Task</button>
</div>

</div>

</div>

<!-- REQUEST MODAL -->

<div class="modal-overlay" id="requestModal">

<div class="modal">

<div class="modal-header">
<span>Request Form</span>
<button onclick="closeRequestForm()">✕</button>
</div>

<label>Select Form *</label>
<select>
<option>Leave Request</option>
<option>Overtime Request</option>
<option>Undertime Request</option>
</select>

<label>Date *</label>
<input type="date">

<label>Message</label>
<textarea></textarea>

<div class="modal-footer">
<button class="cancel-btn" onclick="closeRequestForm()">Cancel</button>
<button class="submit-btn">Submit</button>
</div>

</div>

</div>

<script>

/* MODALS */

function openTaskLog(){
document.getElementById("taskLogModal").style.display="flex";
}

function closeTaskLog(){
document.getElementById("taskLogModal").style.display="none";
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

const ring = document.getElementById("progressRing");
const dot = document.getElementById("progressDot");
const shiftTimeEl = document.getElementById("shiftTime");
const statusBadge = document.getElementById("statusBadge");
const clockBtn = document.getElementById("clockBtn");
const breakBtn = document.getElementById("breakBtn");

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

function toggleClock(){

if(!isClockedIn){

isClockedIn=true;
shiftRemaining=SHIFT_MINUTES;
onBreak=false;

statusBadge.innerText="Clocked In";
clockBtn.innerText="Clock Out";

breakBtn.disabled=false;

updateDisplay();

}else{

isClockedIn=false;
onBreak=false;

statusBadge.innerText="Clocked Out";
clockBtn.innerText="Clock In";

breakBtn.disabled=true;

shiftRemaining=SHIFT_MINUTES;

updateDisplay();

}

}

function startBreak(){

if(!isClockedIn){
alert("Please clock in first");
return;
}

if(onBreak){
alert("Break already active");
return;
}

if(shiftRemaining<=0){
alert("Shift finished");
return;
}

onBreak=true;
breakRemaining=BREAK_MINUTES;

updateDisplay();

}

const now = new Date();

const days=["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
const months=["January","February","March","April","May","June","July","August","September","October","November","December"];

document.getElementById("clockDate").innerText =
days[now.getDay()] + ", " +
months[now.getMonth()] + " " +
now.getDate() + ", " +
now.getFullYear();

updateDisplay();
breakBtn.disabled=true;

</script>

<?= $this->endSection() ?>