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

        <form class="flex flex-col flex-1 overflow-hidden">

            <!-- ================= RAW SHEET ================= -->
            <div class="tab-content active scroll-area" id="raw">

                <div class="mode-switch">
                    <button type="button" class="mode-btn active" onclick="switchMode('individual', event)">Individual</button>
                    <button type="button" class="mode-btn" onclick="switchMode('bulk', event)">Bulk Send</button>
                </div>

                <!-- INDIVIDUAL -->
                <div id="individual-mode">
                    <div class="section">
                        <h3 class="font-semibold">Personal Details</h3>
                        <p class="subtitle">Please complete the employee data</p>
                        <input type="text" placeholder="Search Employee">
                    </div>

                    <div class="section">
                        <h3 class="font-semibold">Gross Salary</h3>
                        <div class="grid-4">
                            <div><label>Monthly</label><input type="number"></div>
                            <div><label>Semi-Monthly</label><input type="number"></div>
                            <div><label>Daily</label><input type="number"></div>
                            <div><label>Hourly</label><input type="number"></div>
                        </div>
                    </div>
                </div>

                <!-- BULK -->
                <div id="bulk-mode" style="display:none;">
                    <div class="section">
                        <h3 class="font-semibold">Select Recipient Group</h3>
                        <p class="subtitle">Please complete the employee data</p>
                        <label>Send to *</label>
                        <select>
                            <option>Create custom list</option>
                        </select>
                        <div class="flex mt-2">
                            <input type="text" placeholder="Search employee">
                            <button type="button" class="done-btn">Done</button>
                        </div>
                        <p class="subtitle">Select the employees for your custom list</p>
                    </div>
                </div>

                <!-- DE MINIMIS BENEFIT (all fields from image) -->
                <div class="section">
                    <h3 class="font-semibold">De Minimis Benefit</h3>
                    <p class="subtitle">Please complete the employee data</p>
                    <div class="grid-3">
                        <div><label>Year End Gifts</label><input type="number"></div>
                        <div><label>Rice Subsidy</label><input type="number"></div>
                        <div><label>Uniform/Cleaning Allowance</label><input type="number"></div>
                    </div>
                    <div class="grid-3 mt-3">
                        <div><label>Laundry Allowance</label><input type="number"></div>
                        <div><label>Luxury Allowance</label><input type="number"></div>
                        <div><label>Medical Allowance</label><input type="number"></div>
                    </div>
                    <div class="grid-2 mt-3">
                        <div><label>Medical Allowance (Additional)</label><input type="number"></div>
                    </div>
                </div>

                <!-- OTHER NON-SOCIAL COMPENSATION (from image) -->
                <div class="section">
                    <h3 class="font-semibold">Other Non-Social Compensation</h3>
                    <div class="grid-3">
                        <div><label>Salary Adjustment</label><input type="number"></div>
                        <div><label>Override</label><input type="number"></div>
                        <div><label>NED</label><input type="number"></div>
                    </div>
                    <div class="grid-3 mt-3">
                        <div><label>Absentee/Tardiness</label><input type="number"></div>
                        <div><label>Holiday Pay</label><input type="number"></div>
                        <div><label>Gross Compensation</label><input type="number"></div>
                    </div>
                    <div class="grid-2 mt-3">
                        <div><label>Gross Tender Compensation</label><input type="number"></div>
                        <div><label>Non-Tender Compensation</label><input type="number"></div>
                    </div>
                </div>

                <!-- LOANS (original) -->
                <div class="grid-2 section">
                    <div>
                        <h3 class="font-semibold">SSS Loan</h3>
                        <div class="grid-2">
                            <div><label>Salary</label><input type="number"></div>
                            <div><label>Calamity</label><input type="number"></div>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-semibold">Advances to Employees</h3>
                        <div class="grid-2">
                            <div><label>Salary</label><input type="number"></div>
                            <div><label>Calamity</label><input type="number"></div>
                        </div>
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
                        <div>
                            <label>Hours</label>
                            <input type="number">
                            <label class="mt-2">PHP Equivalent</label>
                            <input type="number">
                        </div>
                        <div>
                            <label>Night Differential 10PM - 6AM (Days)</label>
                            <input type="number">
                            <label class="mt-2">PHP Equivalent</label>
                            <input type="number">
                        </div>
                    </div>
                </div>

                <!-- SATURDAY OT (original) -->
                <div class="section">
                    <h3 class="font-semibold">Saturday Overtime</h3>
                    <div class="grid-3">
                        <div><label>Hours</label><input type="number"></div>
                        <div><label>Excess 8 Hours</label><input type="number"></div>
                        <div><label>PHP Equivalent</label><input type="number"></div>
                    </div>
                </div>

                <!-- NIGHT DIFF (original) -->
                <div class="section">
                    <h3 class="font-semibold">Night Differential 10PM - 6AM</h3>
                    <div class="grid-2">
                        <div><label>Day(s)</label><input type="number"></div>
                        <div><label>PHP Equivalent</label><input type="number"></div>
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
                        <div><label>Monthly Basic Salary</label><input type="number"></div>
                        <div><label>Semi-Monthly Basic Salary</label><input type="number"></div>
                    </div>
                </div>

                <div class="section">
                    <h3 class="font-semibold">Employee's Share</h3>
                    <div class="grid-5">
                        <input type="number" placeholder="SSS">
                        <input type="number" placeholder="SSS WISP">
                        <input type="number" placeholder="SSS TOTAL">
                        <input type="number" placeholder="PHIC">
                        <input type="number" placeholder="HDMF">
                    </div>
                    <!-- ADDED: fields from image -->
                    <div class="grid-3 mt-3">
                        <div><label>Regular Net Taxable Compensation</label><input type="number"></div>
                        <div><label>Net Taxable Compensation Prov. Cut-off</label><input type="number"></div>
                        <div><label>Monthly Taxable Compensation</label><input type="number"></div>
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

                <!-- ADDED: Monthly Gross Compensation & Contributions -->
                <div class="section">
                    <div class="grid-3">
                        <div><label>Monthly Gross Compensation</label><input type="number"></div>
                        <div><label>SSS Contribution</label><input type="number"></div>
                        <div><label>PHC Contribution</label><input type="number"></div>
                        <div><label>HEAP Contribution</label><input type="number"></div>
                    </div>
                    <div class="grid-2 mt-3">
                        <div><label>Nil Nill</label><input type="number"></div>
                        <div><label>2nd null</label><input type="number"></div>
                    </div>
                </div>

                <div class="button-container">
                    <button type="button" class="next-btn" onclick="nextTab('payslip')">Next</button>
                </div>
            </div>

            <!-- ================= PAYSLIP ================= -->
            <div class="tab-content scroll-area" id="payslip">
                <h2 class="text-center text-xl font-bold mb-4">PAY SLIP</h2>

                <table class="payslip-table">
                    <tr><td>Employee:</td><td>Payroll period:</td></tr>
                    <tr><td>Position:</td><td></td></tr>
                </table>

                <table class="payslip-table">
                    <tr class="table-title">
                        <th>REGULAR EARNINGS</th>
                        <th>Days/Hours</th>
                        <th>Amount</th>
                        <th>TAXABLE OTHER EARNINGS</th>
                        <th>NON-TAXABLE OTHER EARNINGS</th>
                    </tr>
                    <tr><td>Basic Pay</td><td></td><td></td><td></td><td>De Minimis</td></tr>
                    <tr><td>Rate per hour</td><td></td><td></td><td></td><td></td></tr>
                    <tr><td>No. of hours Worked</td><td></td><td></td><td></td><td></td></tr>
                    <tr><td>Night premium</td><td></td><td></td><td></td><td></td></tr>
                    <tr><td>Less: Absences</td><td></td><td></td><td></td><td></td></tr>
                    <tr><td>Late/Undertime</td><td></td><td></td><td></td><td></td></tr>
                    <tr><td><b>Net Basic Pay</b></td><td></td><td></td><td></td><td></td></tr>
                    <tr><td colspan="5" class="gross-row">GROSS PAY</td></tr>
                    <tr><td colspan="5" class="gross-row">TOTAL DEDUCTIONS</td></tr>
                    <tr><td colspan="5" class="net-row">NET PAY</td></tr>
                </table>

                <div class="signature">
                    Employee signature: ___________________________
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

document.querySelectorAll('.sub-tab').forEach(btn=>{
    btn.onclick = () => nextTab(btn.dataset.tab);
});

function switchMode(mode, e){
    document.getElementById('individual-mode').style.display = mode === 'individual' ? 'block' : 'none';
    document.getElementById('bulk-mode').style.display = mode === 'bulk' ? 'block' : 'none';

    document.querySelectorAll('.mode-btn').forEach(b=>b.classList.remove('active'));
    e.target.classList.add('active');
}
</script>

</body>
</html>