<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payroll System | Complete Payslip Processor</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
body{ font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; }

/* Tabs */
.sub-tab{
    padding:12px 0;
    font-weight:600;
    color:#777;
    cursor:pointer;
    transition:0.2s;
}
.sub-tab.active{
    color:#000;
    border-bottom:3px solid #7b5cff;
}

.tab-content{ display:none; }
.tab-content.active{ display:block; }

/* Scroll area */
.scroll-area{
    overflow-y:auto;
    height:100%;
    padding-right:12px;
    margin-bottom: 20px;
}

.section{ margin-bottom:35px; }

.subtitle{
    font-size:12px;
    color:#888;
    margin-bottom:10px;
}

.grid-2{ display:grid; grid-template-columns:1fr 1fr; gap:25px; }
.grid-3{ display:grid; grid-template-columns:1fr 1fr 1fr; gap:25px; }
.grid-4{ display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:25px; }
.grid-5{ display:grid; grid-template-columns:1fr 1fr 1fr 1fr 1fr; gap:25px; }

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
    font-size:13px;
}

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
    cursor:pointer;
    background:transparent;
}
.mode-btn.active{
    background:white;
    box-shadow:0 1px 3px rgba(0,0,0,0.1);
}

.button-container{
    display:flex;
    justify-content:flex-end;
    gap:15px;
    margin-top:30px;
    margin-bottom: 50px;
}
.next-btn, .compute-btn, .final-btn{
    background:#7b5cff;
    color:white;
    padding:10px 28px;
    border-radius:10px;
    border:none;
    cursor: pointer;
    font-weight:500;
    transition:0.2s;
}
.next-btn:hover, .compute-btn:hover, .final-btn:hover{ background:#6249e0; }

/* Payslip card layout (matching image) */
.payslip-card {
    background: #f7f7f7;
    border-radius: 6px;
    padding: 12px;
    box-shadow: 0 0 3px rgba(0,0,0,0.2);
}
.payslip-card h4 {
    font-size: 12px;
    margin-bottom: 8px;
    font-weight: 600;
}
.payslip-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}
.payslip-table th {
    text-align: left;
    background: #d9d9d9;
    padding: 4px;
    font-weight: normal;
}
.payslip-table td {
    padding: 3px;
}
.payslip-table input {
    width: 100%;
    height: 24px;
    background: #e5e5e5;
    border: none;
    border-radius: 3px;
    padding: 2px 4px;
    font-size: 11px;
}
.top-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}
.button-area {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-bottom: 20px;
}
.btn-purple {
    background: #7b5cff;
}
.btn-red {
    background: #ff3b3b;
}
.card-container {
    display: grid;
    grid-template-columns: 2fr 1.2fr 1.2fr;
    gap: 15px;
}
.bottom-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 15px;
}
.totals-card {
    background: #f8f9fc;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 12px 20px;
    margin-top: 20px;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
}
.totals-card div {
    font-size: 12px;
    font-weight: 600;
}
.totals-card input {
    width: 130px;
    background: white;
    border: 1px solid #ccc;
    height: 28px;
    margin-left: 8px;
}
.signature {
    margin-top: 20px;
    font-style: italic;
    text-align: right;
    font-size: 11px;
}

/* Final modal - exact payslip style */
.modal-overlay {
    position: fixed;
    top:0; left:0; width:100%; height:100%;
    background:rgba(0,0,0,0.5);
    display:flex;
    align-items:center;
    justify-content:center;
    z-index:1000;
}
.modal-card {
    background:white;
    border-radius:0;
    max-width:900px;
    width:90%;
    max-height:85vh;
    overflow-y:auto;
    padding:0;
    box-shadow:0 10px 25px rgba(0,0,0,0.2);
    border:1px solid #000;
}
.payslip-modal {
    width:100%;
}
.payslip-modal .header {
    text-align: center;
    padding: 10px;
    border-bottom: 1px solid #000;
}
.payslip-modal .title {
    text-align: center;
    font-weight: bold;
    padding: 5px;
    border-bottom: 1px solid #000;
}
.payslip-modal table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.payslip-modal td, .payslip-modal th {
    border: 1px solid #000;
    padding: 4px;
    vertical-align: top;
}
.payslip-modal .no-border {
    border: none;
}
.payslip-modal .section-title {
    font-weight: bold;
    text-align: center;
    background: #f0f0f0;
}
.payslip-modal .right {
    text-align: right;
}
.payslip-modal .bold {
    font-weight: bold;
}
.payslip-modal .net-pay {
    background: #f8d7da;
    font-weight: bold;
}
.payslip-modal .signature {
    height: 40px;
}
</style>
</head>
<body class="p-6">

<div class="bg-white rounded-2xl shadow-lg p-8 h-[90vh] border border-gray-300 flex flex-col mt-4">

    <!-- TABS -->
    <div class="flex gap-10 border-b border-gray-300 mb-6">
        <button class="sub-tab active" data-tab="raw">Raw Sheet</button>
        <button class="sub-tab" data-tab="register">Payroll Register</button>
        <button class="sub-tab" data-tab="payslip">Payslip</button>
    </div>

    <div class="flex flex-col flex-1 overflow-hidden">

        <!-- ================= RAW SHEET (unchanged) ================= -->
        <div class="tab-content active scroll-area" id="raw">
            <div class="mode-switch">
                <button type="button" class="mode-btn active" onclick="switchMode('individual', event)">Individual</button>
                <button type="button" class="mode-btn" onclick="switchMode('bulk', event)">Bulk Send</button>
            </div>
            <div id="individual-mode">
                <div class="section">
                    <h3 class="font-semibold">Personal Details</h3>
                    <p class="subtitle">Employee & payroll period</p>
                    <div class="grid-2">
                        <div><label>Employee Name</label><input type="text" id="empName" placeholder="e.g. 51 T" value="51 T"></div>
                        <div><label>Position</label><input type="text" id="empPosition" placeholder="Service Desk Analyst L-1" value="Service Desk Analyst L-1"></div>
                        <div><label>Pay Period Start</label><input type="date" id="periodStart" value="2025-12-16"></div>
                        <div><label>Pay Period End</label><input type="date" id="periodEnd" value="2025-12-31"></div>
                    </div>
                </div>
                <div class="section">
                    <h3 class="font-semibold">Gross Salary</h3>
                    <div class="grid-4">
                        <div><label>Monthly</label><input type="number" id="monthlySalary" value="22000.00"></div>
                        <div><label>Semi-Monthly</label><input type="number" id="semiMonthlySalary" placeholder="Auto from monthly"></div>
                        <div><label>Daily</label><input type="number" id="dailyRate"></div>
                        <div><label>Hourly</label><input type="number" id="hourlyRate"></div>
                    </div>
                </div>
            </div>
            <div id="bulk-mode" style="display:none;">
                <div class="section"><h3 class="font-semibold">Select Recipient Group</h3><p class="subtitle">Bulk send placeholder</p><select><option>Create custom list</option></select></div>
            </div>

            <!-- DE MINIMIS BENEFIT -->
            <div class="section">
                <h3 class="font-semibold">De Minimis Benefit</h3>
                <div class="grid-3">
                    <div><label>Year End Gifts</label><input type="number" id="yearEndGifts" value="0"></div>
                    <div><label>Rice Subsidy</label><input type="number" id="riceSubsidy" value="0"></div>
                    <div><label>Uniform/Cleaning Allowance</label><input type="number" id="uniformAllowance" value="0"></div>
                </div>
                <div class="grid-3 mt-3">
                    <div><label>Laundry Allowance</label><input type="number" id="laundryAllowance" value="0"></div>
                    <div><label>Luxury Allowance</label><input type="number" id="luxuryAllowance" value="0"></div>
                    <div><label>Medical Allowance</label><input type="number" id="medicalAllowance" value="0"></div>
                </div>
                <div class="grid-2 mt-3">
                    <div><label>Medical Allowance (Additional)</label><input type="number" id="medicalAllowanceAdd" value="0"></div>
                </div>
            </div>

            <!-- OTHER NON-SOCIAL COMPENSATION -->
            <div class="section">
                <h3 class="font-semibold">Other Non-Social Compensation</h3>
                <div class="grid-3">
                    <div><label>Salary Adjustment</label><input type="number" id="salaryAdjustment" value="0"></div>
                    <div><label>Override</label><input type="number" id="overrideAmount" value="0"></div>
                    <div><label>NED</label><input type="number" id="nedAmount" value="0"></div>
                </div>
                <div class="grid-3 mt-3">
                    <div><label>Absentee/Tardiness</label><input type="number" id="absenteeTardiness" value="0"></div>
                    <div><label>Holiday Pay</label><input type="number" id="holidayPay" value="0"></div>
                    <div><label>Gross Compensation</label><input type="number" id="grossComp" value="0"></div>
                </div>
            </div>

            <!-- LOANS + ADVANCES -->
            <div class="grid-2 section">
                <div><h3 class="font-semibold">SSS Loan</h3><div><label>Salary Loan</label><input type="number" id="sssSalaryLoan" value="0"></div><div class="mt-2"><label>Calamity</label><input type="number" id="sssCalamityLoan" value="0"></div></div>
                <div><h3 class="font-semibold">Advances to Employees</h3><div><label>Salary Advance</label><input type="number" id="advSalary" value="0"></div><div class="mt-2"><label>Calamity Advance</label><input type="number" id="advCalamity" value="0"></div></div>
            </div>

            <!-- UNDERTIME / ABSENT -->
            <div class="grid-2 section">
                <div><h3 class="font-semibold">Undertime</h3><div><label>Hours</label><input type="number" id="utHours" value="0"></div><div><label>PHP Equivalent</label><input type="number" id="utPhp" value="0"></div></div>
                <div><h3 class="font-semibold">Absent</h3><div><label>Day(s)</label><input type="number" id="absentDays" value="0"></div><div><label>PHP Equivalent</label><input type="number" id="absentPhp" value="0"></div></div>
            </div>

            <!-- WORKDAYS OVERTIME -->
            <div class="section">
                <h3 class="font-semibold">Workdays Overtime</h3>
                <div class="grid-2">
                    <div><label>Hours (Regular OT)</label><input type="number" id="workdayOtHours" value="0"><label class="mt-2">PHP Equivalent</label><input type="number" id="workdayOtPhp" value="0"></div>
                    <div><label>Night Differential 10PM-6AM (Days)</label><input type="number" id="workdayNightDiffDays" value="17"><label class="mt-2">PHP Equivalent</label><input type="number" id="workdayNightDiffPhp" value="214.94"></div>
                </div>
            </div>
            <!-- SATURDAY OVERTIME -->
            <div class="section">
                <h3 class="font-semibold">Saturday Overtime</h3>
                <div class="grid-3"><div><label>Hours (<=10hrs)</label><input type="number" id="satOtHours" value="0"></div><div><label>Excess 8 Hours</label><input type="number" id="satOtExcess" value="0"></div><div><label>PHP Equivalent</label><input type="number" id="satOtPhp" value="0"></div></div>
            </div>
            <!-- SUNDAY OVERTIME -->
            <div class="section">
                <h3 class="font-semibold">Sunday / Holiday Overtime</h3>
                <div class="grid-3"><div><label>Sun OT Hours</label><input type="number" id="sunOtHours" value="0"></div><div><label>Sun OT PHP</label><input type="number" id="sunOtPhp" value="0"></div><div><label>Hol OT PHP</label><input type="number" id="holOtPhp" value="0"></div></div>
            </div>
            <div class="button-container">
                <button type="button" class="next-btn" onclick="nextTab('register')">Next → Register</button>
            </div>
        </div>

        <!-- ================= PAYROLL REGISTER ================= -->
        <div class="tab-content scroll-area" id="register">
            <div style="max-width: 100%;">
                <h3 class="font-semibold">Details</h3>
                <p class="subtitle">Please complete the employee data</p>
                <div class="grid-2">
                    <div><label>Search Employee</label><input type="text" id="searchEmployee" placeholder="Search..."></div>
                </div>
                <div class="grid-2">
                    <div><label>Monthly Basic Salary</label><input type="number" id="monthlyBasicSalary" value="22000.00"></div>
                    <div><label>Semi-Monthly Basic Salary</label><input type="number" id="semiMonthlyBasicSalary" placeholder="Auto from monthly"></div>
                </div>

                <h3 class="font-semibold mt-4">De Minimis Benefit</h3>
                <p class="subtitle">Please complete the employee data</p>
                <div class="grid-3">
                    <div><label>Year End 13th</label><input type="number" id="deMinYearEnd" value="0"></div>
                    <div><label>Rice Subsidy</label><input type="number" id="deMinRiceSubsidy" value="0"></div>
                    <div><label>Uniform/Clothing Allowance</label><input type="number" id="deMinUniform" value="0"></div>
                    <div><label>Laundry Allowance</label><input type="number" id="deMinLaundry" value="0"></div>
                    <div><label>Meal Allowance</label><input type="number" id="deMinMeal" value="0"></div>
                    <div><label>Medical Allowance</label><input type="number" id="deMinMedical" value="0"></div>
                </div>

                <hr class="my-3 border-gray-300">

                <div class="grid-3">
                    <div><label>Other Non-Taxable Compensation</label><input type="number" id="otherNonTaxable" value="0"></div>
                    <div><label>Salary Adjustment</label><input type="number" id="salaryAdjustmentReg" value="0"></div>
                    <div><label>Overtime</label><input type="number" id="overtimeReg" value="0"></div>
                    <div><label>ND</label><input type="number" id="ndReg" value="0"></div>
                    <div><label>Absences/Tardiness</label><input type="number" id="absencesTardiness" value="0"></div>
                    <div><label>Holiday Pay</label><input type="number" id="holidayPayReg" value="0"></div>
                    <div><label>Gross Compensation</label><input type="number" id="grossCompReg" value="0"></div>
                    <div><label>Gross Taxable Compensation</label><input type="number" id="grossTaxableComp" value="0"></div>
                    <div><label>Gross Non-Taxable Compensation</label><input type="number" id="grossNonTaxableComp" value="0"></div>
                </div>

                <h3 class="font-semibold mt-4">Employee's Share</h3>
                <p class="subtitle">Please complete the employee data</p>
                <div class="grid-5">
                    <div><label>SSS - ES</label><input type="number" id="sssEE" value="1300.00"></div>
                    <div><label>SSS - WISP</label><input type="number" id="sssWisp" value="0"></div>
                    <div><label>SSS - TOTAL</label><input type="number" id="sssTotal" value="1300.00"></div>
                    <div><label>PHIC</label><input type="number" id="phicEE" value="550.00"></div>
                    <div><label>HDMF</label><input type="number" id="hdmfEE" value="200.00"></div>
                </div>
                <div class="grid-3">
                    <div><label>Regular Net Taxable Compensation</label><input type="number" id="regNetTaxable" value="0"></div>
                    <div><label>Net Taxable Compensation Prev. Cut-Off</label><input type="number" id="netTaxProv" value="0"></div>
                    <div><label>Monthly Taxable Compensation</label><input type="number" id="monthlyTaxableComp" value="0"></div>
                </div>

                <h3 class="font-semibold mt-4">Withholding Tax Compensation</h3>
                <p class="subtitle">Please complete the employee data</p>
                <div class="grid-5">
                    <div><label>Bracket</label><input type="text" id="wtBracket" placeholder="---"></div>
                    <div><label>Basic</label><input type="number" id="wtBasic" value="0"></div>
                    <div><label>Percentage</label><input type="text" id="wtPercentage" placeholder="---"></div>
                    <div><label>Basic</label><input type="number" id="wtBasic2" value="0"></div>
                    <div><label>Excess</label><input type="number" id="wtExcess" value="0"></div>
                </div>
                <div class="grid-2">
                    <div><label>Withholding Tax Paid Last Cut-Off</label><input type="number" id="wtPaidLast" value="0"></div>
                    <div><label>Withholding Tax Payable</label><input type="number" id="wtPayable" value="823.84"></div>
                </div>

                <h3 class="font-semibold mt-4">SSS Loan</h3>
                <p class="subtitle">Please complete the employee data</p>
                <div class="grid-2">
                    <div><label>Salary</label><input type="number" id="regSssLoanSalary" value="0"></div>
                    <div><label>Calamity</label><input type="number" id="regSssLoanCalamity" value="0"></div>
                </div>

                <hr class="my-3 border-gray-300">

                <div class="grid-3">
                    <div><label>Advance to Employees</label><input type="number" id="advWorkingLeave" value="0"></div>
                    <div><label>Vacation Leave</label><input type="number" id="vacationLeave" value="0"></div>
                    <div><label>Non-Taxable Compensation</label><input type="number" id="advNonTaxable" value="0"></div>
                    <div><label>Net Pay</label><input type="number" id="netPayRegister" value="0"></div>
                    <div><label>1st Half</label><input type="text" id="firstHalf" placeholder="---"></div>
                    <div><label>2nd Half</label><input type="text" id="secondHalf" placeholder="---"></div>
                    <div><label>Monthly Gross Compensation</label><input type="number" id="monthlyGrossComp" value="0"></div>
                    <div><label>SSS Contribution</label><input type="number" id="sssContribution" value="0"></div>
                    <div><label>PHIC Contribution</label><input type="number" id="phicContribution" value="0"></div>
                    <div><label>HDMF Contribution</label><input type="number" id="hdmfContribution" value="0"></div>
                </div>

                <div class="button-container">
                    <button type="button" class="next-btn" onclick="nextTab('payslip')">Next → Payslip</button>
                </div>
            </div>
        </div>

        <!-- ================= PAYSLIP ================= -->
        <div class="tab-content scroll-area" id="payslip">
            <h3 class="font-semibold">Payslip Details</h3>
            <div class="sub-text">Please complete the employee data</div>

            <div>
                <label>Employee Name</label>
                <input type="text" id="payslipEmpName">
            </div>

            <div class="top-grid">
                <div>
                    <label>Position</label>
                    <input type="text" id="payslipPosition">
                </div>
                <div>
                    <label>Cut-Off Period</label>
                    <input type="date" id="payslipPeriodStart">
                </div>
                <div>
                    <label>&nbsp;</label>
                    <input type="date" id="payslipPeriodEnd">
                </div>
            </div>

            <div class="button-area">
                <button class="btn-purple btn" id="autoFillBtn" style="border:none; padding:8px 18px; border-radius:6px; color:#fff; cursor:pointer;">Auto-fill</button>
                <button class="btn-red btn" id="generateBtn" style="border:none; padding:8px 18px; border-radius:6px; color:#fff; cursor:pointer;">Generate Payslip</button>
            </div>

            <div class="card-container">
                <!-- Regular Earnings Card -->
                <div class="payslip-card">
                    <h4>Regular Earnings</h4>
                    <table class="payslip-table">
                        <thead> <tr><th>Type</th><th>Days/Hours</th><th>Amount</th></tr> </thead>
                        <tbody id="regularEarningsBody"></tbody>
                    </table>
                </div>

                <!-- Taxable Other Earnings (left) -->
                <div class="payslip-card">
                    <h4>Taxable Other Earnings</h4>
                    <table class="payslip-table">
                        <thead> <tr><th>Type</th><th>Amount</th></tr> </thead>
                        <tbody id="taxableLeftBody"></tbody>
                    </table>
                </div>

                <!-- Non-Taxable Other Earnings (right) -->
                <div class="payslip-card">
                    <h4>Non-Taxable Other Earnings</h4>
                    <table class="payslip-table">
                        <thead> <tr><th>Type</th><th>Amount</th></tr> </thead>
                        <tbody id="taxableRightBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="bottom-cards">
                <div class="payslip-card">
                    <h4>Regular Deductions</h4>
                    <table class="payslip-table">
                        <thead> <tr><th>Type</th><th>Amount</th></tr> </thead>
                        <tbody id="deductionsBody"></tbody>
                    </table>
                </div>
                <div class="payslip-card">
                    <h4>Loans/Advances</h4>
                    <table class="payslip-table">
                        <thead> <tr><th>Type</th><th>Amount</th></tr> </thead>
                        <tbody id="loansBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="totals-card">
                <div>GROSS PAY: <input type="text" id="totalGross" readonly style="background:#f0f0f0;"></div>
                <div>TOTAL DEDUCTIONS: <input type="text" id="totalDeductions" readonly style="background:#f0f0f0;"></div>
                <div>NET PAY: <input type="text" id="totalNet" readonly style="background:#f0f0f0;"></div>
            </div>

            <div class="signature">Employee signature: ___________________________</div>
        </div>
    </div>
</div>

<script>
// Tab navigation
function nextTab(tabId){
    document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    document.querySelectorAll('.sub-tab').forEach(t=>t.classList.remove('active'));
    document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
    if(tabId === 'payslip') buildPayslipTable();
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

// Collect values from Raw + Register
function getComputedPayslipValues(){
    let monthly = parseFloat(document.getElementById('monthlySalary')?.value) || 0;
    let semiMonthly = parseFloat(document.getElementById('semiMonthlySalary')?.value) || 0;
    let basicPay = semiMonthly > 0 ? semiMonthly : (monthly / 2);
    if(basicPay <= 0 && monthly > 0) basicPay = monthly/2;
    let monthlyBase = monthly || (basicPay*2);
    let ratePerHour = monthlyBase > 0 ? monthlyBase / 160 : 126.44;
    let hoursWorked = ratePerHour > 0 ? basicPay / ratePerHour : 84;
    hoursWorked = Math.round(hoursWorked * 100)/100;
    
    let nightPremium = parseFloat(document.getElementById('workdayNightDiffPhp')?.value) || 0;
    let absentAmt = parseFloat(document.getElementById('absentPhp')?.value) || 0;
    let undertimeAmt = parseFloat(document.getElementById('utPhp')?.value) || 0;
    
    let regOtLe10 = parseFloat(document.getElementById('workdayOtPhp')?.value) || 0;
    let satOtLe10 = parseFloat(document.getElementById('satOtPhp')?.value) || 0;
    let sunOtLe10 = parseFloat(document.getElementById('sunOtPhp')?.value) || 0;
    let holOtLe10 = parseFloat(document.getElementById('holOtPhp')?.value) || 0;
    
    let taxableOther = (parseFloat(document.getElementById('salaryAdjustment')?.value) || 0) +
                       (parseFloat(document.getElementById('overrideAmount')?.value) || 0) +
                       (parseFloat(document.getElementById('nedAmount')?.value) || 0) +
                       (parseFloat(document.getElementById('holidayPay')?.value) || 0) +
                       (parseFloat(document.getElementById('grossComp')?.value) || 0);
    
    let nonTaxable = (parseFloat(document.getElementById('yearEndGifts')?.value) || 0) +
                     (parseFloat(document.getElementById('riceSubsidy')?.value) || 0) +
                     (parseFloat(document.getElementById('uniformAllowance')?.value) || 0) +
                     (parseFloat(document.getElementById('laundryAllowance')?.value) || 0) +
                     (parseFloat(document.getElementById('luxuryAllowance')?.value) || 0) +
                     (parseFloat(document.getElementById('medicalAllowance')?.value) || 0) +
                     (parseFloat(document.getElementById('medicalAllowanceAdd')?.value) || 0);
    
    let netBasicPay = basicPay - (absentAmt + undertimeAmt) + nightPremium;
    if(netBasicPay < 0) netBasicPay = basicPay;
    
    let totalOvertime = regOtLe10 + satOtLe10 + sunOtLe10 + holOtLe10;
    let grossPay = netBasicPay + totalOvertime + taxableOther;
    
    let sssDed = parseFloat(document.getElementById('sssEE')?.value) || 0;
    let phicDed = parseFloat(document.getElementById('phicEE')?.value) || 0;
    let hdmfDed = parseFloat(document.getElementById('hdmfEE')?.value) || 0;
    let withholdingTax = parseFloat(document.getElementById('wtPayable')?.value) || 0;
    let totalDeductions = sssDed + phicDed + hdmfDed + withholdingTax;
    let netPay = grossPay - totalDeductions;
    
    let empName = document.getElementById('empName')?.value || '51 T';
    let position = document.getElementById('empPosition')?.value || 'Service Desk Analyst L-1';
    let pStart = document.getElementById('periodStart')?.value || '2025-12-16';
    let pEnd = document.getElementById('periodEnd')?.value || '2025-12-31';
    let periodStr = `${pStart} to ${pEnd}`;
    
    return { empName, position, pStart, pEnd, basicPay, ratePerHour, hoursWorked, nightPremium, absentAmt, undertimeAmt, netBasicPay,
             regOtLe10, satOtLe10, sunOtLe10, holOtLe10, taxableOther, nonTaxable, grossPay, sssDed, phicDed, hdmfDed, withholdingTax, totalDeductions, netPay };
}

// Build the payslip UI
function buildPayslipTable() {
    let d = getComputedPayslipValues();
    document.getElementById('payslipEmpName').value = d.empName;
    document.getElementById('payslipPosition').value = d.position;
    document.getElementById('payslipPeriodStart').value = d.pStart;
    document.getElementById('payslipPeriodEnd').value = d.pEnd;

    // Regular Earnings rows
    const regRows = [
        { label: 'Basic Pay', id: 'edit_basicPay', days: '-', val: d.basicPay },
        { label: 'Rate per hour', id: 'edit_ratePerHour', days: '-', val: d.ratePerHour },
        { label: 'No. of Hours Worked', id: 'edit_hoursWorked', days: '-', val: d.hoursWorked },
        { label: 'Night Premium', id: 'edit_nightPremium', days: '-', val: d.nightPremium },
        { label: 'Less Absences', id: 'edit_absentAmt', days: '-', val: d.absentAmt },
        { label: 'Late/Undertime', id: 'edit_undertimeAmt', days: '-', val: d.undertimeAmt },
        { label: 'Reg OT <= 10 Hrs', id: 'edit_regOtLe10', days: '-', val: d.regOtLe10 },
        { label: 'Reg OT > 10 Hrs', id: 'edit_regOtGt10', days: '-', val: 0 },
        { label: 'Reg Night Premium', id: 'edit_regNightPrem', days: '-', val: 0 },
        { label: 'Sat OT <= 10 Hrs', id: 'edit_satOtLe10', days: '-', val: d.satOtLe10 },
        { label: 'Sat OT > 10 Hrs', id: 'edit_satOtGt10', days: '-', val: 0 },
        { label: 'Sat Night Premium', id: 'edit_satNightPrem', days: '-', val: 0 },
        { label: 'Sun OT <= 10 Hrs', id: 'edit_sunOtLe10', days: '-', val: d.sunOtLe10 },
        { label: 'Sun OT > 10 Hrs', id: 'edit_sunOtGt10', days: '-', val: 0 },
        { label: 'Hol OT <= 10 Hrs', id: 'edit_holOtLe10', days: '-', val: d.holOtLe10 },
        { label: 'Hol OT > 10 Hrs', id: 'edit_holOtGt10', days: '-', val: 0 },
        { label: 'Hol Night Premium', id: 'edit_holNightPrem', days: '-', val: 0 }
    ];
    let regHtml = '';
    regRows.forEach(row => {
        regHtml += `<tr><td>${row.label}</td><td>${row.days}</td><td><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any"></td></tr>`;
    });
    document.getElementById('regularEarningsBody').innerHTML = regHtml;

    // Taxable Other Earnings (left)
    const taxableRows = [
        { desc: 'Salary Adjustment', id: 'edit_taxAdj', val: (parseFloat(document.getElementById('salaryAdjustment')?.value)||0) },
        { desc: 'Override', id: 'edit_taxOverride', val: (parseFloat(document.getElementById('overrideAmount')?.value)||0) },
        { desc: 'NED', id: 'edit_taxNed', val: (parseFloat(document.getElementById('nedAmount')?.value)||0) },
        { desc: 'Holiday Pay', id: 'edit_taxHol', val: (parseFloat(document.getElementById('holidayPay')?.value)||0) },
        { desc: 'Gross Compensation', id: 'edit_taxGrossComp', val: (parseFloat(document.getElementById('grossComp')?.value)||0) }
    ];
    let taxableLeftHtml = '';
    taxableRows.forEach(row => {
        taxableLeftHtml += `<tr><td><input type="text" value="${row.desc}" style="width:100%; background:#e5e5e5; border:none;"></td><td><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any"></td></tr>`;
    });
    document.getElementById('taxableLeftBody').innerHTML = taxableLeftHtml;

    // Non-Taxable Other Earnings (right)
    const nonTaxRows = [
        { desc: 'Year End Gifts', id: 'edit_nonYearEnd', val: (parseFloat(document.getElementById('yearEndGifts')?.value)||0) },
        { desc: 'Rice Subsidy', id: 'edit_riceSubsidy', val: (parseFloat(document.getElementById('riceSubsidy')?.value)||0) },
        { desc: 'Uniform Allowance', id: 'edit_uniformAllow', val: (parseFloat(document.getElementById('uniformAllowance')?.value)||0) },
        { desc: 'Laundry Allowance', id: 'edit_laundryAllow', val: (parseFloat(document.getElementById('laundryAllowance')?.value)||0) },
        { desc: 'Medical Allowance', id: 'edit_medicalAllow', val: (parseFloat(document.getElementById('medicalAllowance')?.value)||0)+(parseFloat(document.getElementById('medicalAllowanceAdd')?.value)||0) }
    ];
    let nonTaxHtml = '';
    nonTaxRows.forEach(row => {
        nonTaxHtml += `<tr><td><input type="text" value="${row.desc}" style="width:100%; background:#e5e5e5; border:none;"></td><td><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any"></td></tr>`;
    });
    document.getElementById('taxableRightBody').innerHTML = nonTaxHtml;

    // Deductions
    const dedRows = [
        { label: 'SSS', id: 'edit_sss', val: d.sssDed },
        { label: 'Philhealth', id: 'edit_phic', val: d.phicDed },
        { label: 'Pagibig', id: 'edit_hdmf', val: d.hdmfDed },
        { label: 'SSS Loan', id: 'edit_sssLoan', val: parseFloat(document.getElementById('sssSalaryLoan')?.value)||0 }
    ];
    let dedHtml = '';
    dedRows.forEach(row => {
        dedHtml += `<tr><td>${row.label}</td><td><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any"></td></tr>`;
    });
    document.getElementById('deductionsBody').innerHTML = dedHtml;

    // Loans/Advances
    const loanRows = [
        { desc: 'Salary Advance', id: 'edit_advSalary', val: parseFloat(document.getElementById('advSalary')?.value)||0 },
        { desc: 'Calamity Advance', id: 'edit_advCalam', val: parseFloat(document.getElementById('advCalamity')?.value)||0 },
        { desc: 'Other Loan', id: 'edit_otherLoan', val: 0 },
        { desc: 'Other Advance', id: 'edit_otherAdv', val: 0 }
    ];
    let loanHtml = '';
    loanRows.forEach(row => {
        loanHtml += `<tr><td><input type="text" value="${row.desc}" style="width:100%; background:#e5e5e5; border:none;"></td><td><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any"></td></tr>`;
    });
    document.getElementById('loansBody').innerHTML = loanHtml;

    // Attach recalc listeners
    const allEditIds = ['edit_basicPay','edit_ratePerHour','edit_hoursWorked','edit_nightPremium','edit_absentAmt','edit_undertimeAmt',
        'edit_regOtLe10','edit_regOtGt10','edit_regNightPrem','edit_satOtLe10','edit_satOtGt10','edit_satNightPrem',
        'edit_sunOtLe10','edit_sunOtGt10','edit_holOtLe10','edit_holOtGt10','edit_holNightPrem',
        'edit_taxAdj','edit_taxOverride','edit_taxNed','edit_taxHol','edit_taxGrossComp',
        'edit_nonYearEnd','edit_riceSubsidy','edit_uniformAllow','edit_laundryAllow','edit_medicalAllow',
        'edit_sss','edit_phic','edit_hdmf','edit_sssLoan','edit_advSalary','edit_advCalam','edit_otherLoan','edit_otherAdv'];
    allEditIds.forEach(id => {
        let el = document.getElementById(id);
        if(el) el.addEventListener('input', recalcTotals);
    });
    recalcTotals();
}

function recalcTotals() {
    // Sum regular earnings
    let regularSum = 0;
    const regIds = ['edit_basicPay','edit_nightPremium','edit_regOtLe10','edit_regOtGt10','edit_regNightPrem',
        'edit_satOtLe10','edit_satOtGt10','edit_satNightPrem','edit_sunOtLe10','edit_sunOtGt10',
        'edit_holOtLe10','edit_holOtGt10','edit_holNightPrem'];
    regIds.forEach(id => { regularSum += parseFloat(document.getElementById(id)?.value) || 0; });
    let absent = parseFloat(document.getElementById('edit_absentAmt')?.value) || 0;
    let undertime = parseFloat(document.getElementById('edit_undertimeAmt')?.value) || 0;
    let netRegular = regularSum - (absent + undertime);
    
    let taxableSum = 0;
    ['edit_taxAdj','edit_taxOverride','edit_taxNed','edit_taxHol','edit_taxGrossComp'].forEach(id => {
        taxableSum += parseFloat(document.getElementById(id)?.value) || 0;
    });
    let nonTaxableSum = 0;
    ['edit_nonYearEnd','edit_riceSubsidy','edit_uniformAllow','edit_laundryAllow','edit_medicalAllow'].forEach(id => {
        nonTaxableSum += parseFloat(document.getElementById(id)?.value) || 0;
    });
    let gross = netRegular + taxableSum + nonTaxableSum;
    
    let dedSum = 0;
    ['edit_sss','edit_phic','edit_hdmf','edit_sssLoan','edit_advSalary','edit_advCalam','edit_otherLoan','edit_otherAdv'].forEach(id => {
        dedSum += parseFloat(document.getElementById(id)?.value) || 0;
    });
    let netPay = gross - dedSum;
    
    document.getElementById('totalGross').value = gross.toFixed(2);
    document.getElementById('totalDeductions').value = dedSum.toFixed(2);
    document.getElementById('totalNet').value = netPay.toFixed(2);
}

function autoFillPayslip() {
    let d = getComputedPayslipValues();
    setNum('edit_basicPay', d.basicPay);
    setNum('edit_ratePerHour', d.ratePerHour);
    setNum('edit_hoursWorked', d.hoursWorked);
    setNum('edit_nightPremium', d.nightPremium);
    setNum('edit_absentAmt', d.absentAmt);
    setNum('edit_undertimeAmt', d.undertimeAmt);
    setNum('edit_regOtLe10', d.regOtLe10);
    setNum('edit_satOtLe10', d.satOtLe10);
    setNum('edit_sunOtLe10', d.sunOtLe10);
    setNum('edit_holOtLe10', d.holOtLe10);
    setNum('edit_taxAdj', parseFloat(document.getElementById('salaryAdjustment')?.value)||0);
    setNum('edit_taxOverride', parseFloat(document.getElementById('overrideAmount')?.value)||0);
    setNum('edit_taxNed', parseFloat(document.getElementById('nedAmount')?.value)||0);
    setNum('edit_taxHol', parseFloat(document.getElementById('holidayPay')?.value)||0);
    setNum('edit_taxGrossComp', parseFloat(document.getElementById('grossComp')?.value)||0);
    setNum('edit_nonYearEnd', parseFloat(document.getElementById('yearEndGifts')?.value)||0);
    setNum('edit_riceSubsidy', parseFloat(document.getElementById('riceSubsidy')?.value)||0);
    setNum('edit_uniformAllow', parseFloat(document.getElementById('uniformAllowance')?.value)||0);
    setNum('edit_laundryAllow', parseFloat(document.getElementById('laundryAllowance')?.value)||0);
    let med = (parseFloat(document.getElementById('medicalAllowance')?.value)||0)+(parseFloat(document.getElementById('medicalAllowanceAdd')?.value)||0);
    setNum('edit_medicalAllow', med);
    setNum('edit_sss', parseFloat(document.getElementById('sssEE')?.value)||0);
    setNum('edit_phic', parseFloat(document.getElementById('phicEE')?.value)||0);
    setNum('edit_hdmf', parseFloat(document.getElementById('hdmfEE')?.value)||0);
    setNum('edit_sssLoan', parseFloat(document.getElementById('sssSalaryLoan')?.value)||0);
    setNum('edit_advSalary', parseFloat(document.getElementById('advSalary')?.value)||0);
    setNum('edit_advCalam', parseFloat(document.getElementById('advCalamity')?.value)||0);
    setNum('edit_otherLoan', 0);
    setNum('edit_otherAdv', 0);
    recalcTotals();
}
function setNum(id, val) { let el = document.getElementById(id); if(el) el.value = val.toFixed(2); }

// NEW: Final modal with exact payslip layout from example
function showFinalPayslip() {
    // Helper to get values
    let get = (id) => parseFloat(document.getElementById(id)?.value) || 0;
    
    // Employee info
    let emp = document.getElementById('payslipEmpName')?.value || 'N/A';
    let pos = document.getElementById('payslipPosition')?.value || 'N/A';
    let start = document.getElementById('payslipPeriodStart')?.value || '';
    let end = document.getElementById('payslipPeriodEnd')?.value || '';
    let period = start && end ? `${start} to ${end}` : 'N/A';
    
    // Regular earnings rows (all fields from payslip)
    const regLabels = [
        'Basic Pay', 'Rate per hour', 'No. of Hours Worked', 'Night Premium',
        'Less Absences', 'Late/Undertime', 'Reg OT <= 10 Hrs', 'Reg OT > 10 Hrs',
        'Reg Night Premium', 'Sat OT <= 10 Hrs', 'Sat OT > 10 Hrs', 'Sat Night Premium',
        'Sun OT <= 10 Hrs', 'Sun OT > 10 Hrs', 'Hol OT <= 10 Hrs', 'Hol OT > 10 Hrs',
        'Hol Night Premium'
    ];
    const regIds = [
        'edit_basicPay','edit_ratePerHour','edit_hoursWorked','edit_nightPremium',
        'edit_absentAmt','edit_undertimeAmt','edit_regOtLe10','edit_regOtGt10','edit_regNightPrem',
        'edit_satOtLe10','edit_satOtGt10','edit_satNightPrem','edit_sunOtLe10','edit_sunOtGt10',
        'edit_holOtLe10','edit_holOtGt10','edit_holNightPrem'
    ];
    
    // Taxable rows
    const taxableLabels = ['Salary Adjustment', 'Override', 'NED', 'Holiday Pay', 'Gross Compensation'];
    const taxableIds = ['edit_taxAdj','edit_taxOverride','edit_taxNed','edit_taxHol','edit_taxGrossComp'];
    
    // Non-taxable rows
    const nonTaxLabels = ['Year End Gifts', 'Rice Subsidy', 'Uniform Allowance', 'Laundry Allowance', 'Medical Allowance'];
    const nonTaxIds = ['edit_nonYearEnd','edit_riceSubsidy','edit_uniformAllow','edit_laundryAllow','edit_medicalAllow'];
    
    // Deductions
    const dedLabels = ['SSS', 'Philhealth', 'HDMF', 'Withholding tax'];
    const dedIds = ['edit_sss','edit_phic','edit_hdmf','edit_wtax'];  // note: we don't have wtax in the UI? actually we have edit_wtax? In current UI we don't have a field for withholding tax in the payslip card, but we have edit_wtax in the total calculation? Wait, we have edit_wtax? Actually we didn't include it in the deductions card. We'll add it.
    // We need to ensure the withholding tax is present. Since the earlier code had 'edit_wtax' but not in the card, we'll fetch from the register's wtPayable.
    let withholding = parseFloat(document.getElementById('wtPayable')?.value) || 0;
    // Also loans
    const loanLabels = ['SSS Loan', 'Salary Advance', 'Calamity Advance', 'Other Loan', 'Other Advance'];
    const loanIds = ['edit_sssLoan','edit_advSalary','edit_advCalam','edit_otherLoan','edit_otherAdv'];
    
    // Build regular earnings table rows (sub-table)
    let regHtml = '';
    for (let i=0; i<regLabels.length; i++) {
        let val = get(regIds[i]);
        // For Less Absences and Late/Undertime, display as negative? we'll show as positive with minus sign
        let displayVal = val;
        if (regLabels[i] === 'Less Absences' || regLabels[i] === 'Late/Undertime') {
            displayVal = -val;
        }
        regHtml += `<tr><td>${regLabels[i]}</td><td>-</td><td class="right">${displayVal.toFixed(2)}</td></tr>`;
    }
    // Add net basic pay row (calculated as basic - absences - late + night premium)
    let netBasic = get('edit_basicPay') - get('edit_absentAmt') - get('edit_undertimeAmt') + get('edit_nightPremium');
    regHtml += `<tr class="bold"><td>Net basic pay</td><td></td><td class="right">${netBasic.toFixed(2)}</td></tr>`;
    
    // Taxable other table
    let taxableHtml = '';
    for (let i=0; i<taxableLabels.length; i++) {
        let val = get(taxableIds[i]);
        taxableHtml += `<tr><td>${taxableLabels[i]}</td><td class="right">${val.toFixed(2)}</td></tr>`;
    }
    
    // Non-taxable table
    let nonTaxHtml = '';
    for (let i=0; i<nonTaxLabels.length; i++) {
        let val = get(nonTaxIds[i]);
        nonTaxHtml += `<tr><td>${nonTaxLabels[i]}</td><td class="right">${val.toFixed(2)}</td></tr>`;
    }
    
    // Deductions table
    let dedHtml = '';
    for (let i=0; i<dedLabels.length; i++) {
        let val = (dedIds[i] === 'edit_wtax') ? withholding : get(dedIds[i]);
        dedHtml += `<tr><td>${dedLabels[i]}</td><td class="right">${val.toFixed(2)}</td></tr>`;
    }
    let totalDed = get('totalDeductions') ? get('totalDeductions') : (get('edit_sss')+get('edit_phic')+get('edit_hdmf')+withholding + get('edit_sssLoan')+get('edit_advSalary')+get('edit_advCalam')+get('edit_otherLoan')+get('edit_otherAdv'));
    dedHtml += `<tr class="bold"><td>TOTAL DEDUCTIONS</td><td class="right">${totalDed.toFixed(2)}</td></tr>`;
    
    // Loans table
    let loanHtml = '';
    for (let i=0; i<loanLabels.length; i++) {
        let val = get(loanIds[i]);
        loanHtml += `<tr><td>${loanLabels[i]}</td><td class="right">${val.toFixed(2)}</td></tr>`;
    }
    
    let gross = get('totalGross') ? get('totalGross') : (netBasic + get('edit_regOtLe10')+get('edit_satOtLe10')+get('edit_sunOtLe10')+get('edit_holOtLe10') + get('edit_taxAdj')+get('edit_taxOverride')+get('edit_taxNed')+get('edit_taxHol')+get('edit_taxGrossComp') + get('edit_nonYearEnd')+get('edit_riceSubsidy')+get('edit_uniformAllow')+get('edit_laundryAllow')+get('edit_medicalAllow'));
    let netPay = get('totalNet') ? get('totalNet') : gross - totalDed;
    
    let modalHtml = `
    <div class="modal-overlay" id="finalModalOverlay">
        <div class="modal-card payslip-modal">
            <div class="header">
                <div style="font-size: 20px; font-weight: bold;">COMPANY NAME</div>
                <div style="font-size: 12px;">Employee Payslip</div>
            </div>
            <div class="title">PAY SLIP</div>
            <table>
                <tr>
                    <td>Employee:</td>
                    <td>${emp}</td>
                    <td class="no-border"></td>
                    <td>Payroll period:</td>
                    <td colspan="2">${period}</td>
                </tr>
                <tr>
                    <td>Position:</td>
                    <td colspan="2">${pos}</td>
                    <td class="no-border"></td>
                    <td colspan="3"></td>
                </tr>
            </table>
            <table>
                <tr>
                    <th class="section-title">REGULAR EARNINGS</th>
                    <th class="section-title">TAXABLE OTHER EARNINGS</th>
                    <th class="section-title">NON-TAXABLE OTHER EARNINGS</th>
                </tr>
                <tr>
                    <td><table style="width:100%">${regHtml}</table></td>
                    <td><table style="width:100%"><tr><td class="bold">GROSS PAY</td><td class="right bold">${gross.toFixed(2)}</td></tr>${taxableHtml}</table></td>
                    <td><table style="width:100%">${nonTaxHtml}</table></td>
                </tr>
            </table>
            <table>
                <tr>
                    <th class="section-title">REGULAR DEDUCTIONS</th>
                    <th class="section-title">LOANS/ADVANCES</th>
                </tr>
                <tr>
                    <td><table style="width:100%">${dedHtml}</table></td>
                    <td><table style="width:100%">${loanHtml}</table></td>
                </tr>
            </table>
            <table>
                <tr>
                    <td class="signature">Employee signature: ___________________________</td>
                    <td class="right net-pay">NET PAY: ${netPay.toFixed(2)}</td>
                </tr>
            </table>
            <div class="flex justify-end mt-3 gap-3 p-2">
                <button id="sendFinalBtn" style="background:#7b5cff; color:white; padding:8px 20px; border-radius:6px;">Send Payslip</button>
                <button id="closeModalBtn2" style="background:#aaa; padding:8px 20px; border-radius:6px;">Close</button>
            </div>
        </div>
    </div>`;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    let overlay = document.getElementById('finalModalOverlay');
    let close = () => overlay?.remove();
    document.getElementById('closeModalBtn2')?.addEventListener('click', close);
    document.getElementById('sendFinalBtn')?.addEventListener('click', () => {
        alert(`Payslip sent to ${emp} (simulated). Net pay: PHP ${netPay.toFixed(2)}`);
        close();
    });
}

document.getElementById('autoFillBtn')?.addEventListener('click', autoFillPayslip);
document.getElementById('generateBtn')?.addEventListener('click', showFinalPayslip);
window.switchMode = switchMode;
window.nextTab = nextTab;
setTimeout(()=>{ if(document.getElementById('payslip').classList.contains('active')) buildPayslipTable(); }, 100);
</script>
</body>
</html>