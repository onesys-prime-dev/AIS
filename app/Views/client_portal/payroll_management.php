<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payroll System</title>
<script src="https://cdn.tailwindcss.com"></script>

<style>
body{ font-family: Arial, sans-serif; }

/* Tabs */
.sub-tab{
    padding:12px 0;
    font-weight:600;
    color:#777;
}
.sub-tab.active{
    color:#000;
    border-bottom:3px solid #000;
}

/* Tab content */
.tab-content{ display:none; }
.tab-content.active{ display:block; }

/* Scroll */
.scroll-area{
    overflow-y:auto;
    height:100%;
    padding-right:20px;
    margin-bottom: 60px; /* extra space at bottom for button visibility */
}

/* Sections */
.section{ margin-bottom:35px; }

.subtitle{
    font-size:12px;
    color:#888;
    margin-bottom:10px;
}

/* Grid */
.grid-2{ display:grid; grid-template-columns:1fr 1fr; gap:25px; }
.grid-3{ display:grid; grid-template-columns:1fr 1fr 1fr; gap:25px; }
.grid-4{ display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:25px; }
.grid-5{ display:grid; grid-template-columns:1fr 1fr 1fr 1fr 1fr; gap:25px; }

/* Inputs */
label{
    font-size:12px;
    color:#555;
    margin-bottom:5px;
    display:block;
}
input, select{
    background:#e9e9e9;
    border:1px solid #cfcfcf;
    height:40px;
    border-radius:8px;
    padding:8px;
    width:100%;
}

/* Mode Switch */
.mode-switch{
    background:#e5e5e5;
    width:200px;
    border-radius:20px;
    padding:3px;
    display:flex;
    margin-bottom:20px;
}
.mode-btn{
    flex:1;
    border:none;
    padding:6px;
    border-radius:20px;
    font-size:12px;
}
.mode-btn.active{
    background:white;
}

/* Button */
.button-container{
    display:flex;
    justify-content:flex-end;
    margin-top:30px;
    margin-bottom: 10px; /* extra spacing */
}
.next-btn{
    background:#7b5cff;
    color:white;
    padding:10px 30px;
    border-radius:10px;
    border:none;
    cursor: pointer;
}
.done-btn{
    background:#7b5cff;
    color:white;
    padding:8px 20px;
    border-radius:6px;
    border:none;
    margin-left:10px;
    cursor: pointer;
}

/* Payslip */
.payslip-table{
    width:100%;
    border-collapse:collapse;
    margin-top:10px;
}
.payslip-table td,.payslip-table th{
    border:1px solid #000;
    padding:6px;
    font-size:12px;
}
.table-title th{ background:#f0f0f0; }
.gross-row{ text-align:right; font-weight:bold; }
.net-row{ text-align:right; font-weight:bold; background:#f8d7da; }
.signature{ margin-top:20px; }
</style>
</head>

<body class="bg-gray-100 p-6">

    <!-- ADDED: mt-4 class on the main card to create a small margin on top -->
    <div class="bg-white rounded-2xl shadow-lg p-8 h-[90vh] border border-gray-300 flex flex-col mt-4">

        <!-- TABS -->
        <div class="flex gap-10 border-b border-gray-300 mb-6">
            <button class="sub-tab active" data-tab="raw">Raw Sheet</button>
            <button class="sub-tab" data-tab="register">Payroll Register</button>
            <button class="sub-tab" data-tab="payslip">Payslip</button>
        </div>

        <!-- BODY -->
        <div class="card-body">

            <!-- PERSONAL DETAILS -->
            <div class="section">
                <h3>Personal Details</h3>
                <p class="subtitle">Please complete the employee data</p>
                <input type="text" placeholder="Search Employee" class="full-input">
            </div>

            <!-- GROSS SALARY -->
            <div class="section">
                <h3>Gross Salary</h3>
                <p class="subtitle">Please complete the employee data</p>
                <div class="grid-4">
                    <input type="text" placeholder="Monthly">
                    <input type="text" placeholder="Semi-Monthly">
                    <input type="text" placeholder="Daily">
                    <input type="text" placeholder="Hourly">
                </div>
            </div>

            <!-- DE MINIMIS -->
            <div class="section">
                <h3>De Minimis Benefit</h3>
                <p class="subtitle">Please complete the employee data</p>
                <div class="grid-3">
                    <input type="text" placeholder="Rice Subsidy">
                    <input type="text" placeholder="Uniform Clothing Allowance">
                    <input type="text" placeholder="Laundry Allowance">
                </div>
            </div>

                <!-- DE MINIMIS BENEFIT (all fields from image) -->
                <div class="section">
                    <h3 class="font-semibold">De Minimis Benefit</h3>
                    <p class="subtitle">Please complete the employee data</p>
                    <div class="grid-2">
                        <input type="text" placeholder="Salary">
                        <input type="text" placeholder="Deductibility">
                    </div>
                </div>

                <!-- OTHER NON-SOCIAL COMPENSATION (from image) -->
                <div class="section">
                    <h3>Advances to Employees</h3>
                    <p class="subtitle">Please complete the employee data</p>
                    <div class="grid-2">
                        <input type="text" placeholder="Salary">
                        <input type="text" placeholder="Deductibility">
                    </div>
                </div>

                <!-- UNDERTIME / ABSENT (original) -->
                <div class="grid-2 section">
                    <div>
                        <h3 class="font-semibold">Undertime</h3>
                        <div class="grid-2">
                            <div><label>Hours</label><input type="number"></div>
                            <div><label>PHP Equivalent</label><input type="number"></div>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-semibold">Absent</h3>
                        <div class="grid-2">
                            <div><label>Day(s)</label><input type="number"></div>
                            <div><label>PHP Equivalent</label><input type="number"></div>
                        </div>
                    </div>
                </div>

                <!-- WORKDAYS OT (original) -->
                <div class="section">
                    <h3 class="font-semibold">Workdays Overtime</h3>
                    <div class="grid-2">
                        <input type="text" placeholder="Hours">
                        <input type="text" placeholder="PHP Equivalent">
                    </div>
                </div>

                <!-- NIGHT DIFF (original) -->
                <div class="section">
                    <h3 class="font-semibold">Night Differential 10PM - 6AM</h3>
                    <div class="grid-2">
                        <input type="text" placeholder="Day(s)">
                        <input type="text" placeholder="PHP Equivalent">
                    </div>
                </div>

                <div class="button-container">
                    <button type="button" class="next-btn" onclick="nextTab('register')">Next</button>
                </div>

            </div>

            <!-- ================= PAYROLL REGISTER ================= -->
            <div class="tab-content scroll-area" id="register">
                <div class="section">
                    <h3 class="font-semibold">Details</h3>
                    <input type="text" placeholder="Search Employee">
                    <div class="grid-2">
                        <input type="text" placeholder="Hours">
                        <input type="text" placeholder="PHP Equivalent">
                    </div>
                </div>

                <div class="section">
                    <h4>Night Differential 10PM - 6AM</h4>
                    <p class="subtitle">Please complete the employee data</p>
                    <div class="grid-2">
                        <input type="text" placeholder="Day(s)">
                        <input type="text" placeholder="PHP Equivalent">
                    </div>
                </div>

                <!-- ADDED: Withholding Tax Compensation section -->
                <div class="section">
                    <h3 class="font-semibold">Withholding Tax Compensation</h3>
                    <div class="grid-4">
                        <div><label>Indirect</label><input type="number"></div>
                        <div><label>Ideal</label><input type="text"></div>
                        <div><label>Personage</label><input type="text"></div>
                        <div><label>Basic</label><input type="number"></div>
                    </div>
                    <div class="grid-3 mt-3">
                        <div><label>Excess</label><input type="number"></div>
                        <div><label>Withholding Tax Paid Last Cut-off</label><input type="number"></div>
                        <div><label>Withholding Tax Payable</label><input type="number"></div>
                    </div>
                </div>

                <!-- ADDED: SSS Loan & Advance to Employees (detailed) -->
                <div class="grid-2 section">
                    <div>
                        <h3 class="font-semibold">SSS Loan</h3>
                        <div><label>Salary</label><input type="number"></div>
                        <div class="mt-2"><label>Ordinary</label><input type="number"></div>
                    </div>
                    <div>
                        <h3 class="font-semibold">Advance to Employees</h3>
                        <div><label>Working Leave</label><input type="number"></div>
                        <div class="mt-2"><label>Non Taxable Compensation</label><input type="number"></div>
                        <div class="mt-2"><label>Not Pay</label><input type="number"></div>
                    </div>
                </div>

            <div class="section">
                <h4>Overtime</h4>
                <p class="subtitle">Please complete the employee data</p>
                <div class="grid-3">
                    <input type="text" placeholder="Hours">
                    <input type="text" placeholder="Excess 8 Hours">
                    <input type="text" placeholder="PHP Equivalent">
                </div>
            </div>

            <div class="section">
                <h4>Night Differential 10PM - 6AM</h4>
                <p class="subtitle">Please complete the employee data</p>
                <div class="grid-2">
                    <input type="text" placeholder="Day(s)">
                    <input type="text" placeholder="PHP Equivalent">
                </div>
            </div>

        </form>
    </div>

<script>
function nextTab(tab){
    document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
    document.getElementById(tab).classList.add('active');

    document.querySelectorAll('.sub-tab').forEach(t=>t.classList.remove('active'));
    document.querySelector('[data-tab="'+tab+'"]').classList.add('active');
}

/* SECTIONS */
.section{
    margin-bottom: 25px;
}

.section h3{
    font-size: 16px;
    color: #333;
    margin-bottom: 3px;
}

.section h4{
    font-size: 14px;
    color: #444;
    margin-bottom: 3px;
}

.subtitle{
    font-size: 12px;
    color: #888;
    margin-bottom: 10px;
}

/* GRIDS */
.grid-2{
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.grid-3{
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 15px;
}

.grid-4{
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr;
    gap: 15px;
}

.full-input{
    width: 100%;
}

/* INPUT */
input{
    height: 38px;
    border-radius: 6px;
    border: 1px solid #cfcfcf;
    background: #eeeeee;
    padding: 8px 10px;
    font-size: 13px;
    outline: none;
}

input:focus{
    background: #ffffff;
    border: 1px solid #7b5cff;
}

/* BUTTON */
.button-container{
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
}

.next-btn{
    background: linear-gradient(135deg, #7b5cff, #9b6bff);
    color: white;
    border: none;
    padding: 10px 28px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    box-shadow: 0 4px 10px rgba(123,92,255,0.3);
}
</style>