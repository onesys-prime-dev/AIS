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
    height:600px;
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
.next-btn, .auto-fill-btn, .generate-payslip-btn{
    background:#7b5cff;
    color:white;
    padding:10px 28px;
    border-radius:10px;
    border:none;
    cursor: pointer;
    font-weight:500;
    transition:0.2s;
}
.next-btn:hover, .auto-fill-btn:hover, .generate-payslip-btn:hover{ background:#6249e0; }

/* Payslip card layout (editable preview) */
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
.btn-purple { background: #7b5cff; }
.btn-red { background: #ff3b3b; }
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

/* Employee search dropdown */
.employee-search-container {
    position: relative;
}
.employee-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #cfcfcf;
    border-radius: 8px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 20;
    display: none;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.employee-suggestion-item {
    padding: 8px 12px;
    cursor: pointer;
    font-size: 13px;
    border-bottom: 1px solid #eee;
}
.employee-suggestion-item:last-child {
    border-bottom: none;
}
.employee-suggestion-item:hover {
    background: #f0f0f0;
}
.bulk-employee-list {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #cfcfcf;
    border-radius: 8px;
    padding: 8px;
    background: white;
}
.bulk-employee-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 0;
    font-size: 13px;
}

/* Modal for static payslip */
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.6);
}
.modal-content {
    background-color: #fefefe;
    margin: 2% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 950px;
    max-width: 95%;
    border-radius: 10px;
    position: relative;
}
.close-modal {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    position: absolute;
    right: 15px;
    top: 5px;
}
.close-modal:hover {
    color: black;
}
.send-pdf-btn {
    background: #28a745;
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
}
.send-pdf-btn:hover {
    background: #218838;
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

        <!-- ================= RAW SHEET ================= -->
        <div class="tab-content active scroll-area" id="raw">
            <!-- Mode Switch -->
            <div class="mode-switch">
                <button type="button" class="mode-btn active" onclick="switchMode('individual', event)">Individual</button>
                <button type="button" class="mode-btn" onclick="switchMode('bulk', event)">Bulk Send</button>
            </div>

            <!-- Employee Selection Area -->
            <div class="section">
                <h3 class="font-semibold">Employee Selection</h3>
                <div id="employee-search-area">
                    <div id="individual-employee-select" class="employee-search-container">
                        <label>Employee Name</label>
                        <input type="text" id="empSearchInput" placeholder="Type to search..." autocomplete="off">
                        <div id="employeeSuggestions" class="employee-suggestions"></div>
                        <input type="hidden" id="selectedEmployeeId" value="">
                    </div>
                    <div id="bulk-employee-select" style="display:none;">
                        <label>Select Employees</label>
                        <div id="bulkEmployeeList" class="bulk-employee-list"></div>
                        <div class="mt-2 text-sm text-gray-600">Selected employees will appear in the name field.</div>
                    </div>
                </div>
                <div class="mt-3">
                    <label>Selected Employee(s)</label>
                    <input type="text" id="empName" placeholder="e.g. 51 T" readonly class="bg-gray-200">
                </div>
            </div>

            <div class="flex justify-end mb-4">
                <button type="button" class="auto-fill-btn" onclick="autoFillRawSheet()">Auto-fill from Employee</button>
            </div>

            <div id="individual-mode">
                <div class="section">
                    <h3 class="font-semibold">Personal Details</h3>
                    <p class="subtitle">Employee & payroll period</p>
                    <div class="grid-2">
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
                <div class="section">
                    <h3 class="font-semibold">Bulk Processing Options</h3>
                    <p class="subtitle">The selected employees above will be processed together.</p>
                </div>
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
                <div>
                    <h3 class="font-semibold">SSS Loan</h3>
                    <div><label>Salary Loan</label><input type="number" id="sssSalaryLoan" value="0"></div>
                    <div class="mt-2"><label>Calamity</label><input type="number" id="sssCalamityLoan" value="0"></div>
                </div>
                <div>
                    <h3 class="font-semibold">Advances to Employees</h3>
                    <div><label>Salary Advance</label><input type="number" id="advSalary" value="0"></div>
                    <div class="mt-2"><label>Calamity Advance</label><input type="number" id="advCalamity" value="0"></div>
                </div>
            </div>

            <!-- UNDERTIME / ABSENT -->
            <div class="grid-2 section">
                <div>
                    <h3 class="font-semibold">Undertime</h3>
                    <div><label>Hours</label><input type="number" id="utHours" value="0"></div>
                    <div><label>PHP Equivalent</label><input type="number" id="utPhp" value="0"></div>
                </div>
                <div>
                    <h3 class="font-semibold">Absent</h3>
                    <div><label>Day(s)</label><input type="number" id="absentDays" value="0"></div>
                    <div><label>PHP Equivalent</label><input type="number" id="absentPhp" value="0"></div>
                </div>
            </div>

            <!-- WORKDAYS OVERTIME -->
            <div class="section">
                <h3 class="font-semibold">Workdays Overtime</h3>
                <div class="grid-2">
                    <div>
                        <label>Hours (Regular OT)</label>
                        <input type="number" id="workdayOtHours" value="0">
                        <label class="mt-2">PHP Equivalent</label>
                        <input type="number" id="workdayOtPhp" value="0">
                    </div>
                    <div>
                        <label>Night Differential 10PM-6AM (Days)</label>
                        <input type="number" id="workdayNightDiffDays" value="17">
                        <label class="mt-2">PHP Equivalent</label>
                        <input type="number" id="workdayNightDiffPhp" value="214.94">
                    </div>
                </div>
            </div>
            <div class="section">
                <h3 class="font-semibold">Saturday Overtime</h3>
                <div class="grid-3">
                    <div><label>Hours (<=10hrs)</label><input type="number" id="satOtHours" value="0"></div>
                    <div><label>Excess 8 Hours</label><input type="number" id="satOtExcess" value="0"></div>
                    <div><label>PHP Equivalent</label><input type="number" id="satOtPhp" value="0"></div>
                </div>
            </div>
            <div class="section">
                <h3 class="font-semibold">Sunday / Holiday Overtime</h3>
                <div class="grid-3">
                    <div><label>Sun OT Hours</label><input type="number" id="sunOtHours" value="0"></div>
                    <div><label>Sun OT PHP</label><input type="number" id="sunOtPhp" value="0"></div>
                    <div><label>Hol OT PHP</label><input type="number" id="holOtPhp" value="0"></div>
                </div>
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

        <!-- ================= PAYSLIP TAB (Editable Preview + Generate Button) ================= -->
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
                <button class="generate-payslip-btn" id="generateStaticBtn" style="border:none; padding:8px 18px; border-radius:6px; color:#fff; cursor:pointer;">Generate Payslip</button>
            </div>

            <div class="card-container">
                <div class="payslip-card">
                    <h4>Regular Earnings</h4>
                    <table class="payslip-table">
                        <thead><tr><th>Type</th><th>Days/Hours</th><th>Amount</th></tr></thead>
                        <tbody id="regularEarningsBody"></tbody>
                    </table>
                </div>
                <div class="payslip-card">
                    <h4>Taxable Other Earnings</h4>
                    <table class="payslip-table">
                        <thead><tr><th>Type</th><th>Amount</th></tr></thead>
                        <tbody id="taxableLeftBody"></tbody>
                    </table>
                </div>
                <div class="payslip-card">
                    <h4>Non-Taxable Other Earnings</h4>
                    <table class="payslip-table">
                        <thead><tr><th>Type</th><th>Amount</th></tr></thead>
                        <tbody id="taxableRightBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="bottom-cards">
                <div class="payslip-card">
                    <h4>Regular Deductions</h4>
                    <table class="payslip-table">
                        <thead><tr><th>Type</th><th>Amount</th></tr></thead>
                        <tbody id="deductionsBody"></tbody>
                    </table>
                </div>
                <div class="payslip-card">
                    <h4>Loans/Advances</h4>
                    <table class="payslip-table">
                        <thead><tr><th>Type</th><th>Amount</th></tr></thead>
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

<!-- ================= MODAL FOR STATIC PAYSLIP ================= -->
<div id="staticPayslipModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <div id="staticPayslipContent"></div>
        <div style="text-align: center; margin-top: 20px;">
            <button class="send-pdf-btn" id="sendPdfBtn">Send Payslip to Employee</button>
        </div>
    </div>
</div>

<script>
// ================= GLOBAL VARIABLES =================
let employeesList = [];
let selectedEmployeeIds = new Set();

// ================= TAB NAVIGATION =================
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

// ================= MODE SWITCH =================
function switchMode(mode, e){
    document.getElementById('individual-mode').style.display = mode === 'individual' ? 'block' : 'none';
    document.getElementById('bulk-mode').style.display = mode === 'bulk' ? 'block' : 'none';
    document.getElementById('individual-employee-select').style.display = mode === 'individual' ? 'block' : 'none';
    document.getElementById('bulk-employee-select').style.display = mode === 'bulk' ? 'block' : 'none';
    document.querySelectorAll('.mode-btn').forEach(b=>b.classList.remove('active'));
    e.target.classList.add('active');
    
    if (mode === 'individual') {
        selectedEmployeeIds.clear();
        updateSelectedEmployeeDisplay();
        document.getElementById('selectedEmployeeId').value = '';
        document.getElementById('empSearchInput').value = '';
    } else {
        renderBulkEmployeeList();
    }
}

// ================= FETCH EMPLOYEES =================
async function loadEmployees() {
    try {
        const url = '<?= site_url('client_portal/payroll/employees_json') ?>';
        const response = await fetch(url);
        if (!response.ok) throw new Error('Failed to load employees');
        const data = await response.json();
        employeesList = data;
        renderBulkEmployeeList();
        initSearchSuggestions();
    } catch (error) {
        console.error('Failed to load employees:', error);
        employeesList = [
            { id: '1', name: 'Test Employee 1', label: 'Test Employee 1' },
            { id: '2', name: 'Test Employee 2', label: 'Test Employee 2' }
        ];
        renderBulkEmployeeList();
        initSearchSuggestions();
        alert('Using dummy employee data. Check console for server error.');
    }
}

function renderBulkEmployeeList() {
    const container = document.getElementById('bulkEmployeeList');
    if (!container) return;
    let html = '';
    employeesList.forEach(emp => {
        html += `<div class="bulk-employee-item">
            <input type="checkbox" id="bulk_emp_${emp.id}" value="${emp.id}" onchange="toggleBulkEmployee(${emp.id}, this.checked)">
            <label for="bulk_emp_${emp.id}">${emp.name}</label>
        </div>`;
    });
    container.innerHTML = html;
}

function toggleBulkEmployee(id, checked) {
    if (checked) selectedEmployeeIds.add(id);
    else selectedEmployeeIds.delete(id);
    updateSelectedEmployeeDisplay();
}

function updateSelectedEmployeeDisplay() {
    const nameField = document.getElementById('empName');
    if (selectedEmployeeIds.size === 0) {
        nameField.value = '';
        return;
    }
    const names = [];
    selectedEmployeeIds.forEach(id => {
        const emp = employeesList.find(e => e.id == id);
        if (emp) names.push(emp.name);
    });
    nameField.value = names.join(', ');
}

function initSearchSuggestions() {
    const searchInput = document.getElementById('empSearchInput');
    const suggestionsDiv = document.getElementById('employeeSuggestions');
    const selectedIdHidden = document.getElementById('selectedEmployeeId');
    if (!searchInput || !suggestionsDiv) return;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        if (query.length === 0) {
            suggestionsDiv.style.display = 'none';
            return;
        }
        const matches = employeesList.filter(emp => emp.name.toLowerCase().includes(query));
        if (matches.length === 0) {
            suggestionsDiv.style.display = 'none';
            return;
        }
        let html = '';
        matches.forEach(emp => {
            html += `<div class="employee-suggestion-item" data-id="${emp.id}" data-name="${emp.name}">${emp.name}</div>`;
        });
        suggestionsDiv.innerHTML = html;
        suggestionsDiv.style.display = 'block';
    });

    suggestionsDiv.addEventListener('click', function(e) {
        const item = e.target.closest('.employee-suggestion-item');
        if (!item) return;
        const id = item.dataset.id;
        const name = item.dataset.name;
        searchInput.value = name;
        selectedIdHidden.value = id;
        document.getElementById('empName').value = name;
        selectedEmployeeIds.clear();
        selectedEmployeeIds.add(id);
        suggestionsDiv.style.display = 'none';
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
            suggestionsDiv.style.display = 'none';
        }
    });
}

// ================= AUTO-FILL RAW SHEET =================
async function autoFillRawSheet() {
    let employeeId = null;
    const mode = document.querySelector('.mode-btn.active').textContent.includes('Individual') ? 'individual' : 'bulk';
    
    if (mode === 'individual') {
        employeeId = document.getElementById('selectedEmployeeId').value;
        if (!employeeId) {
            alert('Please select an employee first.');
            return;
        }
    } else {
        if (selectedEmployeeIds.size === 0) {
            alert('Please select at least one employee.');
            return;
        }
        employeeId = Array.from(selectedEmployeeIds)[0];
    }

    const periodStart = document.getElementById('periodStart').value;
    if (!periodStart) {
        alert('Please set a valid Pay Period Start date.');
        return;
    }
    const month = periodStart.substring(0, 7);

    try {
        const url = `<?= site_url('client_portal/payroll/employee_details') ?>/${employeeId}?month=${month}`;
        const response = await fetch(url);
        if (!response.ok) {
            let errorMsg = `HTTP ${response.status}`;
            try {
                const errorData = await response.json();
                errorMsg = errorData.error || errorMsg;
            } catch(e) {}
            throw new Error(errorMsg);
        }
        const data = await response.json();

        document.getElementById('empPosition').value = data.position || 'Service Desk Analyst L-1';
        document.getElementById('monthlySalary').value = data.monthly_salary || '22000.00';
        const monthly = parseFloat(data.monthly_salary) || 22000;
        document.getElementById('semiMonthlySalary').value = (monthly / 2).toFixed(2);
        document.getElementById('dailyRate').value = data.daily_rate || (monthly / 22).toFixed(2);
        document.getElementById('hourlyRate').value = data.hourly_rate || (monthly / 160).toFixed(2);
        
        if (mode === 'bulk') {
            const emp = employeesList.find(e => e.id == employeeId);
            if (emp) document.getElementById('empName').value = emp.name;
        }
        alert('Raw sheet populated with employee data from Payroll Status.');
    } catch (error) {
        console.error('Auto-fill failed:', error);
        alert(error.message);
    }
}

// ================= SAFE NUMBER PARSING =================
function safeParseFloat(value, defaultValue = 0) {
    let num = parseFloat(value);
    return isNaN(num) ? defaultValue : num;
}

// ================= COMPUTE PAYSLIP DATA FROM RAW SHEET =================
function computePayslipData() {
    let monthly = safeParseFloat(document.getElementById('monthlySalary')?.value, 0);
    let semiMonthly = safeParseFloat(document.getElementById('semiMonthlySalary')?.value, 0);
    let basicPay = semiMonthly > 0 ? semiMonthly : (monthly / 2);
    if(basicPay <= 0 && monthly > 0) basicPay = monthly/2;
    let monthlyBase = monthly || (basicPay*2);
    let ratePerHour = monthlyBase > 0 ? monthlyBase / 160 : 126.44;
    let hoursWorked = ratePerHour > 0 ? basicPay / ratePerHour : 84;
    hoursWorked = Math.round(hoursWorked * 100)/100;
    
    let nightPremium = safeParseFloat(document.getElementById('workdayNightDiffPhp')?.value);
    let absentAmt = safeParseFloat(document.getElementById('absentPhp')?.value);
    let undertimeAmt = safeParseFloat(document.getElementById('utPhp')?.value);
    
    let regOt = safeParseFloat(document.getElementById('workdayOtPhp')?.value);
    let satOt = safeParseFloat(document.getElementById('satOtPhp')?.value);
    let sunOt = safeParseFloat(document.getElementById('sunOtPhp')?.value);
    let holOt = safeParseFloat(document.getElementById('holOtPhp')?.value);
    
    let taxableOther = safeParseFloat(document.getElementById('salaryAdjustment')?.value) +
                       safeParseFloat(document.getElementById('overrideAmount')?.value) +
                       safeParseFloat(document.getElementById('nedAmount')?.value) +
                       safeParseFloat(document.getElementById('holidayPay')?.value) +
                       safeParseFloat(document.getElementById('grossComp')?.value);
    
    let nonTaxable = safeParseFloat(document.getElementById('yearEndGifts')?.value) +
                     safeParseFloat(document.getElementById('riceSubsidy')?.value) +
                     safeParseFloat(document.getElementById('uniformAllowance')?.value) +
                     safeParseFloat(document.getElementById('laundryAllowance')?.value) +
                     safeParseFloat(document.getElementById('luxuryAllowance')?.value) +
                     safeParseFloat(document.getElementById('medicalAllowance')?.value) +
                     safeParseFloat(document.getElementById('medicalAllowanceAdd')?.value);
    
    let netBasicPay = basicPay - (absentAmt + undertimeAmt) + nightPremium;
    if(netBasicPay < 0) netBasicPay = basicPay;
    
    let totalOvertime = regOt + satOt + sunOt + holOt;
    let grossPay = netBasicPay + totalOvertime + taxableOther;
    
    let sssDed = safeParseFloat(document.getElementById('sssEE')?.value);
    let phicDed = safeParseFloat(document.getElementById('phicEE')?.value);
    let hdmfDed = safeParseFloat(document.getElementById('hdmfEE')?.value);
    let withholdingTax = safeParseFloat(document.getElementById('wtPayable')?.value);
    let totalDeductions = sssDed + phicDed + hdmfDed + withholdingTax;
    let netPay = grossPay - totalDeductions;
    
    let empName = document.getElementById('empName')?.value || '';
    if (!empName || empName.trim() === '') empName = 'Not Selected';
    let position = document.getElementById('empPosition')?.value || 'N/A';
    let pStart = document.getElementById('periodStart')?.value || '';
    let pEnd = document.getElementById('periodEnd')?.value || '';
    
    const formatDate = (dateStr) => {
        if (!dateStr) return '';
        let d = new Date(dateStr);
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    };
    
    return {
        empName, position, periodStart: pStart, periodEnd: pEnd,
        basicPay, ratePerHour, hoursWorked, nightPremium, absentAmt, undertimeAmt,
        netBasicPay, regOt, satOt, sunOt, holOt, taxableOther, nonTaxable,
        grossPay, sssDed, phicDed, hdmfDed, withholdingTax, totalDeductions, netPay,
        formatStart: formatDate(pStart), formatEnd: formatDate(pEnd)
    };
}

// ================= GENERATE STATIC PAYSLIP (POPUP MODAL) =================
function generateStaticPayslip() {
    const d = computePayslipData();
    if (!d.empName || d.empName === 'Not Selected') {
        alert('Please select an employee and fill in the Raw Sheet first.');
        return;
    }
    
    const html = `
        <div style="font-family: Arial, sans-serif; background: #fff; border: 1px solid #000; padding: 20px; width: 100%; box-sizing: border-box;">
            <h2 style="text-align: center; margin: 5px 0 10px;">PAY SLIP</h2>
            <table style="width:100%; border-collapse: collapse; font-size:13px;">
                <tr>
                    <td style="border:1px solid #000; padding:5px;"><strong>Employee:</strong></td>
                    <td style="border:1px solid #000; padding:5px;">${escapeHtml(d.empName)}</td>
                    <td style="border:1px solid #000; padding:5px;"></td>
                    <td style="border:1px solid #000; padding:5px;"><strong>Payroll period:</strong></td>
                    <td style="border:1px solid #000; padding:5px;">${d.formatStart}</td>
                    <td style="border:1px solid #000; padding:5px;">${d.formatEnd}</td>
                </tr>
                <tr>
                    <td style="border:1px solid #000; padding:5px;"><strong>Position:</strong></td>
                    <td colspan="5" style="border:1px solid #000; padding:5px;">${escapeHtml(d.position)}</td>
                </tr>
            </table>
            
            <table style="width:100%; border-collapse: collapse; font-size:13px; margin-top:10px;">
                <tr>
                    <th style="border:1px solid #000; padding:5px; background:#eee; text-align:center;">REGULAR EARNINGS</th>
                    <th style="border:1px solid #000; padding:5px; background:#eee; text-align:center;">TAXABLE OTHER EARNINGS</th>
                    <th style="border:1px solid #000; padding:5px; background:#eee; text-align:center;">NON-TAXABLE OTHER EARNINGS</th>
                </tr>
                <tr>
                    <td style="border:1px solid #000; padding:5px; vertical-align:top;">
                        <table style="width:100%;">
                            <tr><td>Basic pay:</td><td style="text-align:right;">${d.basicPay.toFixed(2)}</td></tr>
                            <tr><td>Rate per hour:</td><td style="text-align:right;">${d.ratePerHour.toFixed(2)}</td></tr>
                            <tr><td>No. of hours Worked:</td><td style="text-align:right;">${d.hoursWorked.toFixed(2)}</td></tr>
                            <tr><td>Night premium:</td><td style="text-align:right;">${d.nightPremium.toFixed(2)}</td></tr>
                            <tr><td>Less: Absences</td><td style="text-align:right;">${d.absentAmt.toFixed(2)}</td></tr>
                            <tr><td>Late/Undertime</td><td style="text-align:right;">${d.undertimeAmt.toFixed(2)}</td></tr>
                            <tr class="bold"><td><strong>Net basic pay</strong></td><td style="text-align:right;"><strong>${d.netBasicPay.toFixed(2)}</strong></td></tr>
                            <tr><td>Reg. OT</td><td style="text-align:right;">${d.regOt.toFixed(2)}</td></tr>
                            <tr><td>Sat OT</td><td style="text-align:right;">${d.satOt.toFixed(2)}</td></tr>
                            <tr><td>Sun OT</td><td style="text-align:right;">${d.sunOt.toFixed(2)}</td></tr>
                            <tr><td>Holiday OT</td><td style="text-align:right;">${d.holOt.toFixed(2)}</td></tr>
                        </table>
                    </td>
                    <td style="border:1px solid #000; padding:5px; vertical-align:top;">
                        <table style="width:100%;">
                            <tr><td>Salary Adjustment</td><td style="text-align:right;">${safeParseFloat(document.getElementById('salaryAdjustment')?.value).toFixed(2)}</td></tr>
                            <tr><td>Override</td><td style="text-align:right;">${safeParseFloat(document.getElementById('overrideAmount')?.value).toFixed(2)}</td></tr>
                            <tr><td>NED</td><td style="text-align:right;">${safeParseFloat(document.getElementById('nedAmount')?.value).toFixed(2)}</td></tr>
                            <tr><td>Holiday Pay</td><td style="text-align:right;">${safeParseFloat(document.getElementById('holidayPay')?.value).toFixed(2)}</td></tr>
                            <tr><td>Gross Compensation</td><td style="text-align:right;">${safeParseFloat(document.getElementById('grossComp')?.value).toFixed(2)}</td></tr>
                        </table>
                    </td>
                    <td style="border:1px solid #000; padding:5px; vertical-align:top;">
                        <table style="width:100%;">
                            <tr><td>De Minimis Benefit</td><td style="text-align:right;">${d.nonTaxable.toFixed(2)}</td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="border:1px solid #000; padding:5px; text-align:right;"><strong>GROSS PAY</strong></td>
                    <td style="border:1px solid #000; padding:5px; text-align:right;"><strong>${d.grossPay.toFixed(2)}</strong></td>
                </tr>
            </table>
            
            <table style="width:100%; border-collapse: collapse; font-size:13px; margin-top:10px;">
                <tr>
                    <th style="border:1px solid #000; padding:5px; background:#eee; text-align:center;">REGULAR DEDUCTIONS</th>
                    <th style="border:1px solid #000; padding:5px; background:#eee; text-align:center;">LOANS/ADVANCES</th>
                </tr>
                <tr>
                    <td style="border:1px solid #000; padding:5px; vertical-align:top;">
                        <table style="width:100%;">
                            <tr><td>SSS</td><td style="text-align:right;">${d.sssDed.toFixed(2)}</td></tr>
                            <tr><td>Philhealth</td><td style="text-align:right;">${d.phicDed.toFixed(2)}</td></tr>
                            <tr><td>Pag-IBIG</td><td style="text-align:right;">${d.hdmfDed.toFixed(2)}</td></tr>
                            <tr><td>Withholding tax</td><td style="text-align:right;">${d.withholdingTax.toFixed(2)}</td></tr>
                        </table>
                    </td>
                    <td style="border:1px solid #000; padding:5px; vertical-align:top;">
                        <table style="width:100%;">
                            <tr><td>Salary Loan</td><td style="text-align:right;">${safeParseFloat(document.getElementById('sssSalaryLoan')?.value).toFixed(2)}</td></tr>
                            <tr><td>Calamity Loan</td><td style="text-align:right;">${safeParseFloat(document.getElementById('sssCalamityLoan')?.value).toFixed(2)}</td></tr>
                            <tr><td>Salary Advance</td><td style="text-align:right;">${safeParseFloat(document.getElementById('advSalary')?.value).toFixed(2)}</td></tr>
                            <tr><td>Calamity Advance</td><td style="text-align:right;">${safeParseFloat(document.getElementById('advCalamity')?.value).toFixed(2)}</td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="border:1px solid #000; padding:5px; text-align:right;"><strong>TOTAL DEDUCTIONS</strong></td>
                    <td style="border:1px solid #000; padding:5px; text-align:right;"><strong>${d.totalDeductions.toFixed(2)}</strong></td>
                </tr>
            </table>
            
            <table style="width:100%; border-collapse: collapse; font-size:13px; margin-top:10px;">
                <tr>
                    <td style="border:1px solid #000; padding:5px;">Employee signature:</td>
                    <td style="border:1px solid #000; padding:5px; height:40px;"></td>
                    <td style="border:1px solid #000; padding:5px; text-align:right;"><strong>NET PAY:</strong></td>
                    <td style="border:1px solid #000; padding:5px; text-align:right; background:#f8d7da;"><strong>${d.netPay.toFixed(2)}</strong></td>
                </tr>
            </table>
        </div>
    `;
    
    document.getElementById('staticPayslipContent').innerHTML = html;
    document.getElementById('staticPayslipModal').style.display = 'block';
    window.currentPayslipData = d;
}

// Close modal
document.querySelector('.close-modal')?.addEventListener('click', function() {
    document.getElementById('staticPayslipModal').style.display = 'none';
});
window.onclick = function(event) {
    const modal = document.getElementById('staticPayslipModal');
    if (event.target == modal) modal.style.display = 'none';
}

// ================= SEND PDF TO EMPLOYEE =================
async function sendPdfToEmployee() {
    if (!window.currentPayslipData) {
        alert('Please generate the payslip first (click "Generate Payslip").');
        return;
    }
    const employeeId = document.getElementById('selectedEmployeeId').value;
    if (!employeeId) {
        alert('Please select an employee first.');
        return;
    }
    
    const payload = {
        employee_id: employeeId,
        period_start: window.currentPayslipData.periodStart,
        period_end: window.currentPayslipData.periodEnd,
        basic_pay: window.currentPayslipData.basicPay,
        overtime_pay: window.currentPayslipData.regOt + window.currentPayslipData.satOt + window.currentPayslipData.sunOt + window.currentPayslipData.holOt,
        night_diff_pay: window.currentPayslipData.nightPremium,
        holiday_pay: safeParseFloat(document.getElementById('holidayPay')?.value),
        de_minimis: window.currentPayslipData.nonTaxable,
        other_earnings_taxable: window.currentPayslipData.taxableOther,
        other_earnings_non_taxable: 0,
        sss_ee: window.currentPayslipData.sssDed,
        phic_ee: window.currentPayslipData.phicDed,
        hdmf_ee: window.currentPayslipData.hdmfDed,
        withholding_tax: window.currentPayslipData.withholdingTax,
        sss_loan: safeParseFloat(document.getElementById('sssSalaryLoan')?.value),
        calamity_loan: safeParseFloat(document.getElementById('sssCalamityLoan')?.value),
        salary_advance: safeParseFloat(document.getElementById('advSalary')?.value),
        other_deductions: safeParseFloat(document.getElementById('advCalamity')?.value),
        net_pay: window.currentPayslipData.netPay,
        employee_name: window.currentPayslipData.empName,
        position: window.currentPayslipData.position
    };
    
    try {
        const response = await fetch('<?= site_url('client_portal/payroll/sendPdf') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (result.success) {
            alert('PDF payslip sent successfully to employee email.');
        } else {
            alert('Error sending PDF: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Send PDF error:', error);
        alert('Could not send email. Please check server logs.');
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// ================= EDITABLE PAYSLIP PREVIEW FUNCTIONS =================
function getComputedPayslipValues(){
    let monthly = safeParseFloat(document.getElementById('monthlySalary')?.value);
    let semiMonthly = safeParseFloat(document.getElementById('semiMonthlySalary')?.value);
    let basicPay = semiMonthly > 0 ? semiMonthly : (monthly / 2);
    if(basicPay <= 0 && monthly > 0) basicPay = monthly/2;
    let monthlyBase = monthly || (basicPay*2);
    let ratePerHour = monthlyBase > 0 ? monthlyBase / 160 : 126.44;
    let hoursWorked = ratePerHour > 0 ? basicPay / ratePerHour : 84;
    hoursWorked = Math.round(hoursWorked * 100)/100;
    
    let nightPremium = safeParseFloat(document.getElementById('workdayNightDiffPhp')?.value);
    let absentAmt = safeParseFloat(document.getElementById('absentPhp')?.value);
    let undertimeAmt = safeParseFloat(document.getElementById('utPhp')?.value);
    let regOtLe10 = safeParseFloat(document.getElementById('workdayOtPhp')?.value);
    let satOtLe10 = safeParseFloat(document.getElementById('satOtPhp')?.value);
    let sunOtLe10 = safeParseFloat(document.getElementById('sunOtPhp')?.value);
    let holOtLe10 = safeParseFloat(document.getElementById('holOtPhp')?.value);
    
    let taxableOther = safeParseFloat(document.getElementById('salaryAdjustment')?.value) +
                       safeParseFloat(document.getElementById('overrideAmount')?.value) +
                       safeParseFloat(document.getElementById('nedAmount')?.value) +
                       safeParseFloat(document.getElementById('holidayPay')?.value) +
                       safeParseFloat(document.getElementById('grossComp')?.value);
    
    let nonTaxable = safeParseFloat(document.getElementById('yearEndGifts')?.value) +
                     safeParseFloat(document.getElementById('riceSubsidy')?.value) +
                     safeParseFloat(document.getElementById('uniformAllowance')?.value) +
                     safeParseFloat(document.getElementById('laundryAllowance')?.value) +
                     safeParseFloat(document.getElementById('luxuryAllowance')?.value) +
                     safeParseFloat(document.getElementById('medicalAllowance')?.value) +
                     safeParseFloat(document.getElementById('medicalAllowanceAdd')?.value);
    
    let netBasicPay = basicPay - (absentAmt + undertimeAmt) + nightPremium;
    if(netBasicPay < 0) netBasicPay = basicPay;
    let totalOvertime = regOtLe10 + satOtLe10 + sunOtLe10 + holOtLe10;
    let grossPay = netBasicPay + totalOvertime + taxableOther;
    let sssDed = safeParseFloat(document.getElementById('sssEE')?.value);
    let phicDed = safeParseFloat(document.getElementById('phicEE')?.value);
    let hdmfDed = safeParseFloat(document.getElementById('hdmfEE')?.value);
    let withholdingTax = safeParseFloat(document.getElementById('wtPayable')?.value);
    let totalDeductions = sssDed + phicDed + hdmfDed + withholdingTax;
    let netPay = grossPay - totalDeductions;
    let empName = document.getElementById('empName')?.value || 'Not Selected';
    let position = document.getElementById('empPosition')?.value || 'N/A';
    let pStart = document.getElementById('periodStart')?.value || '';
    let pEnd = document.getElementById('periodEnd')?.value || '';
    return { empName, position, pStart, pEnd, basicPay, ratePerHour, hoursWorked, nightPremium, absentAmt, undertimeAmt, netBasicPay,
             regOtLe10, satOtLe10, sunOtLe10, holOtLe10, taxableOther, nonTaxable, grossPay, sssDed, phicDed, hdmfDed, withholdingTax, totalDeductions, netPay };
}

function buildPayslipTable() {
    let d = getComputedPayslipValues();
    document.getElementById('payslipEmpName').value = d.empName;
    document.getElementById('payslipPosition').value = d.position;
    document.getElementById('payslipPeriodStart').value = d.pStart;
    document.getElementById('payslipPeriodEnd').value = d.pEnd;

    const regRows = [
        { label: 'Basic Pay', id: 'edit_basicPay', days: '-', val: d.basicPay },
        { label: 'Rate per hour', id: 'edit_ratePerHour', days: '-', val: d.ratePerHour },
        { label: 'No. of Hours Worked', id: 'edit_hoursWorked', days: '-', val: d.hoursWorked },
        { label: 'Night Premium', id: 'edit_nightPremium', days: '-', val: d.nightPremium },
        { label: 'Less Absences', id: 'edit_absentAmt', days: '-', val: d.absentAmt },
        { label: 'Late/Undertime', id: 'edit_undertimeAmt', days: '-', val: d.undertimeAmt },
        { label: 'Reg OT <= 10 Hrs', id: 'edit_regOtLe10', days: '-', val: d.regOtLe10 },
        { label: 'Sat OT <= 10 Hrs', id: 'edit_satOtLe10', days: '-', val: d.satOtLe10 },
        { label: 'Sun OT <= 10 Hrs', id: 'edit_sunOtLe10', days: '-', val: d.sunOtLe10 },
        { label: 'Hol OT <= 10 Hrs', id: 'edit_holOtLe10', days: '-', val: d.holOtLe10 }
    ];
    let regHtml = '';
    regRows.forEach(row => {
        regHtml += `<tr><td>${row.label}</td><td>${row.days}</td><td><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any"></td></tr>`;
    });
    document.getElementById('regularEarningsBody').innerHTML = regHtml;

    const taxableRows = [
        { desc: 'Salary Adjustment', id: 'edit_taxAdj', val: safeParseFloat(document.getElementById('salaryAdjustment')?.value) },
        { desc: 'Override', id: 'edit_taxOverride', val: safeParseFloat(document.getElementById('overrideAmount')?.value) },
        { desc: 'NED', id: 'edit_taxNed', val: safeParseFloat(document.getElementById('nedAmount')?.value) },
        { desc: 'Holiday Pay', id: 'edit_taxHol', val: safeParseFloat(document.getElementById('holidayPay')?.value) },
        { desc: 'Gross Compensation', id: 'edit_taxGrossComp', val: safeParseFloat(document.getElementById('grossComp')?.value) }
    ];
    let taxableLeftHtml = '';
    taxableRows.forEach(row => {
        taxableLeftHtml += `<tr><td><input type="text" value="${row.desc}" style="width:100%; background:#e5e5e5; border:none;"></td><td><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any"></td></tr>`;
    });
    document.getElementById('taxableLeftBody').innerHTML = taxableLeftHtml;

    const nonTaxRows = [
        { desc: 'Year End Gifts', id: 'edit_nonYearEnd', val: safeParseFloat(document.getElementById('yearEndGifts')?.value) },
        { desc: 'Rice Subsidy', id: 'edit_riceSubsidy', val: safeParseFloat(document.getElementById('riceSubsidy')?.value) },
        { desc: 'Uniform Allowance', id: 'edit_uniformAllow', val: safeParseFloat(document.getElementById('uniformAllowance')?.value) },
        { desc: 'Laundry Allowance', id: 'edit_laundryAllow', val: safeParseFloat(document.getElementById('laundryAllowance')?.value) },
        { desc: 'Medical Allowance', id: 'edit_medicalAllow', val: safeParseFloat(document.getElementById('medicalAllowance')?.value) + safeParseFloat(document.getElementById('medicalAllowanceAdd')?.value) }
    ];
    let nonTaxHtml = '';
    nonTaxRows.forEach(row => {
        nonTaxHtml += `<tr><td><input type="text" value="${row.desc}" style="width:100%; background:#e5e5e5; border:none;"></td><td><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any"></td></tr>`;
    });
    document.getElementById('taxableRightBody').innerHTML = nonTaxHtml;

    const dedRows = [
        { label: 'SSS', id: 'edit_sss', val: d.sssDed },
        { label: 'Philhealth', id: 'edit_phic', val: d.phicDed },
        { label: 'Pagibig', id: 'edit_hdmf', val: d.hdmfDed },
        { label: 'SSS Loan', id: 'edit_sssLoan', val: safeParseFloat(document.getElementById('sssSalaryLoan')?.value) }
    ];
    let dedHtml = '';
    dedRows.forEach(row => {
        dedHtml += `<tr><td>${row.label}</td><td><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any"></td></tr>`;
    });
    document.getElementById('deductionsBody').innerHTML = dedHtml;

    const loanRows = [
        { desc: 'Salary Advance', id: 'edit_advSalary', val: safeParseFloat(document.getElementById('advSalary')?.value) },
        { desc: 'Calamity Advance', id: 'edit_advCalam', val: safeParseFloat(document.getElementById('advCalamity')?.value) },
        { desc: 'Other Loan', id: 'edit_otherLoan', val: 0 },
        { desc: 'Other Advance', id: 'edit_otherAdv', val: 0 }
    ];
    let loanHtml = '';
    loanRows.forEach(row => {
        loanHtml += `<tr><td><input type="text" value="${row.desc}" style="width:100%; background:#e5e5e5; border:none;"></td><td><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any"></td></tr>`;
    });
    document.getElementById('loansBody').innerHTML = loanHtml;

    const allEditIds = ['edit_basicPay','edit_ratePerHour','edit_hoursWorked','edit_nightPremium','edit_absentAmt','edit_undertimeAmt',
        'edit_regOtLe10','edit_satOtLe10','edit_sunOtLe10','edit_holOtLe10',
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
    let regularSum = 0;
    const regIds = ['edit_basicPay','edit_nightPremium','edit_regOtLe10','edit_satOtLe10','edit_sunOtLe10','edit_holOtLe10'];
    regIds.forEach(id => { regularSum += safeParseFloat(document.getElementById(id)?.value); });
    let absent = safeParseFloat(document.getElementById('edit_absentAmt')?.value);
    let undertime = safeParseFloat(document.getElementById('edit_undertimeAmt')?.value);
    let netRegular = regularSum - (absent + undertime);
    
    let taxableSum = 0;
    ['edit_taxAdj','edit_taxOverride','edit_taxNed','edit_taxHol','edit_taxGrossComp'].forEach(id => {
        taxableSum += safeParseFloat(document.getElementById(id)?.value);
    });
    let nonTaxableSum = 0;
    ['edit_nonYearEnd','edit_riceSubsidy','edit_uniformAllow','edit_laundryAllow','edit_medicalAllow'].forEach(id => {
        nonTaxableSum += safeParseFloat(document.getElementById(id)?.value);
    });
    let gross = netRegular + taxableSum + nonTaxableSum;
    
    let dedSum = 0;
    ['edit_sss','edit_phic','edit_hdmf','edit_sssLoan','edit_advSalary','edit_advCalam','edit_otherLoan','edit_otherAdv'].forEach(id => {
        dedSum += safeParseFloat(document.getElementById(id)?.value);
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
    setNum('edit_taxAdj', safeParseFloat(document.getElementById('salaryAdjustment')?.value));
    setNum('edit_taxOverride', safeParseFloat(document.getElementById('overrideAmount')?.value));
    setNum('edit_taxNed', safeParseFloat(document.getElementById('nedAmount')?.value));
    setNum('edit_taxHol', safeParseFloat(document.getElementById('holidayPay')?.value));
    setNum('edit_taxGrossComp', safeParseFloat(document.getElementById('grossComp')?.value));
    setNum('edit_nonYearEnd', safeParseFloat(document.getElementById('yearEndGifts')?.value));
    setNum('edit_riceSubsidy', safeParseFloat(document.getElementById('riceSubsidy')?.value));
    setNum('edit_uniformAllow', safeParseFloat(document.getElementById('uniformAllowance')?.value));
    setNum('edit_laundryAllow', safeParseFloat(document.getElementById('laundryAllowance')?.value));
    let med = safeParseFloat(document.getElementById('medicalAllowance')?.value) + safeParseFloat(document.getElementById('medicalAllowanceAdd')?.value);
    setNum('edit_medicalAllow', med);
    setNum('edit_sss', safeParseFloat(document.getElementById('sssEE')?.value));
    setNum('edit_phic', safeParseFloat(document.getElementById('phicEE')?.value));
    setNum('edit_hdmf', safeParseFloat(document.getElementById('hdmfEE')?.value));
    setNum('edit_sssLoan', safeParseFloat(document.getElementById('sssSalaryLoan')?.value));
    setNum('edit_advSalary', safeParseFloat(document.getElementById('advSalary')?.value));
    setNum('edit_advCalam', safeParseFloat(document.getElementById('advCalamity')?.value));
    setNum('edit_otherLoan', 0);
    setNum('edit_otherAdv', 0);
    recalcTotals();
}
function setNum(id, val) { let el = document.getElementById(id); if(el) el.value = val.toFixed(2); }

// ================= INITIALIZATION =================
document.addEventListener('DOMContentLoaded', function() {
    loadEmployees();
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    document.getElementById('periodStart').value = `${year}-${month}-01`;
    const lastDay = new Date(year, today.getMonth() + 1, 0).getDate();
    document.getElementById('periodEnd').value = `${year}-${month}-${String(lastDay).padStart(2, '0')}`;
    
    document.getElementById('autoFillBtn')?.addEventListener('click', autoFillPayslip);
    document.getElementById('generateStaticBtn')?.addEventListener('click', generateStaticPayslip);
    document.getElementById('sendPdfBtn')?.addEventListener('click', sendPdfToEmployee);
});
</script>
</body>
</html>