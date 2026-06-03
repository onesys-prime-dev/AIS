<?php
// Fetch company logo from database
$companyBranding = model(\App\Models\Client\CompanyBrandingModel::class)
    ->where('company_id', 1)
    ->first();
$companyLogoUrl = $companyBranding && !empty($companyBranding['logo_path']) 
    ? base_url($companyBranding['logo_path']) 
    : '';
?>
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
    .payroll-scroll-area{
        overflow-y:auto;
        height:600px;
    }

    .payroll-scroll-hide{
        scrollbar-width:none;
        -ms-overflow-style:none;
    }

    .payroll-scroll-hide::-webkit-scrollbar{
        width:0;
        height:0;
    }

    .section{ margin-bottom:35px; }

    .subtitle{
        font-size:12px;
        color:#888;
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
        margin-bottom: 10px;
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
        margin-top: 10px;
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
    #staticPayslipModal {
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
    #staticPayslipModal .modal-content {
        background-color: #fefefe;
        margin: 2% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 950px;
        max-width: 95%;
        border-radius: 10px;
        position: relative;
        max-height: 92vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    #staticPayslipContent {
        flex: 1 1 auto;
        overflow-y: auto;
        padding-right: 8px;
    }
    #staticPayslipModal .modal-footer {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #e5e7eb;
        text-align: center;
        flex: 0 0 auto;
    }
    #staticPayslipModal .close-modal {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        position: absolute;
        right: 15px;
        top: 5px;
    }
    #staticPayslipModal .close-modal:hover {
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

/* ADDED: style for import modal */
.import-modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}
.import-modal-content {
    background-color: #fefefe;
    margin: 10% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 500px;
    border-radius: 10px;
}
</style>
</head>
<body class="p-6">

<div class="bg-white rounded-2xl shadow-lg p-8 h-full overflow-hidden border border-gray-300 flex flex-col">

    <!-- TABS -->
    <div class="flex gap-10 border-b border-gray-300 mb-6">
        <button class="sub-tab active" data-tab="raw">Raw Sheet</button>
        <button class="sub-tab" data-tab="register">Payroll Register</button>
        <button class="sub-tab" data-tab="payslip">Payslip</button>
    </div>

    <div class="flex flex-col flex-1 overflow-hidden">

        <!-- ================= RAW SHEET ================= -->
        <div class="tab-content active payroll-scroll-area payroll-scroll-hide" id="raw">
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
                        <div id="employeeSuggestions" class="employee-suggestions payroll-scroll-hide"></div>
                        <input type="hidden" id="selectedEmployeeId" value="">
                    </div>
                    <div id="bulk-employee-select" style="display:none;">
                        <label>Select Employees (Bulk)</label>
                        <div class="mb-2">
                            <input type="text" id="bulkSearchInput" placeholder="Filter employees..." class="w-full p-2 border rounded">
                        </div>
                        <div id="bulkEmployeeList" class="bulk-employee-list"></div>
                        <div class="mt-2 text-sm text-gray-600">Selected employees will appear in the name field.</div>
                    </div>
                </div>
                <div class="mt-3">
                    <label>Selected Employee(s)</label>
                    <input type="text" id="empName" placeholder="e.g. 51 T" readonly class="bg-gray-200">
                </div>
            </div>

            <div class="flex justify-end mb-4 gap-2">
                <button type="button" class="auto-fill-btn" onclick="autoFillRawSheet()">Auto-fill from Employee</button>
                <button type="button" class="next-btn" id="exportRawSheetBtn" style="background:#28a745;">Export Raw Sheet (CSV)</button>
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
                    <p class="subtitle">The selected employees above will be processed together. Click "Auto-fill" to populate each employee's data (you will be prompted for each).</p>
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
                        <label>Regular OT Hours</label>
                        <input type="number" id="workdayOtHours" value="0">
                        <label class="mt-2">Regular OT Pay</label>
                        <input type="number" id="workdayOtPhp" value="0">
                    </div>
                    <div>
                        <label>Night Differential Hours</label>
                        <input type="number" id="workdayNightDiffDays" value="0">
                        <label class="mt-2">Night Diff Pay</label>
                        <input type="number" id="workdayNightDiffPhp" value="0">
                    </div>
                </div>
            </div>
            <div class="section">
                <h3 class="font-semibold">Saturday Overtime</h3>
                <div class="grid-3">
                    <div><label>Hours</label><input type="number" id="satOtHours" value="0"></div>
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
                <button type="button" class="next-btn" id="exportTimesheetBtn" style="background:#28a745;">Export Timesheet</button>
                <button type="button" class="next-btn" id="importTimesheetBtn" style="background:#17a2b8;">Import Timesheet</button>
                <button type="button" class="next-btn" onclick="syncToRegister()">Next → Register</button>
            </div>
        </div>

        <!-- ================= PAYROLL REGISTER ================= -->
        <div class="tab-content payroll-scroll-area payroll-scroll-hide" id="register">
            <div style="max-width: 100%;">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold">Details</h3>
                    <button type="button" class="next-btn" id="exportRegisterBtn" style="background:#28a745;">Export Register (CSV)</button>
                </div>
                <p class="subtitle">Please complete the employee data</p>
                <div class="grid-2">
                    <div><label>Search Employee</label><input type="text" id="searchEmployee" placeholder="Search..."></div>
                </div>
                <div class="grid-2">
                    <div><label>Monthly Basic Salary</label><input type="number" id="monthlyBasicSalary" value="0"></div>
                    <div><label>Semi-Monthly Basic Salary</label><input type="number" id="semiMonthlyBasicSalary" value="0"></div>
                </div>

                <h3 class="font-semibold mt-4">De Minimis Benefit</h3>
                <p class="subtitle">Please complete the employee data</p>
                <div class="grid-3">
                    <div><label>Year End 13th</label><input type="number" id="deMinYearEnd" value="0"></div>
                    <div><label>Rice Subsidy</label><input type="number" id="deMinRiceSubsidy" value="0"></div>
                    <div><label>Uniform/Clothing Allowance</label><input type="number" id="deMinUniform" value="0"></div>
                    <div><label>Laundry Allowance</label><input type="number" id="deMinLaundry" value="0"></div>
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
                    <div><label>SSS - ES</label><input type="number" id="sssEE" value="0"></div>
                    <div><label>SSS - WISP</label><input type="number" id="sssWisp" value="0"></div>
                    <div><label>SSS - TOTAL</label><input type="number" id="sssTotal" value="0"></div>
                    <div><label>PHIC</label><input type="number" id="phicEE" value="0"></div>
                    <div><label>HDMF</label><input type="number" id="hdmfEE" value="0"></div>
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
                    <div><label>Withholding Tax Payable</label><input type="number" id="wtPayable" value="0"></div>
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
                    <div><label>Non-Taxable Compensation</label><input type="number" id="advNonTaxable" value="0"></div>
                    <div><label>Net Pay</label><input type="number" id="netPayRegister" value="0"></div>
                    <div><label>Monthly Gross Compensation</label><input type="number" id="monthlyGrossComp" value="0"></div>
                    <div><label>SSS Contribution</label><input type="number" id="sssContribution" value="0"></div>
                    <div><label>PHIC Contribution</label><input type="number" id="phicContribution" value="0"></div>
                    <div><label>HDMF Contribution</label><input type="number" id="hdmfContribution" value="0"></div>
                </div>

                <div class="button-container">
                    <button type="button" class="next-btn" onclick="syncToPayslip()">Next → Payslip</button>
                </div>
            </div>
        </div>

        <!-- ================= PAYSLIP TAB ================= -->
        <div class="tab-content payroll-scroll-area payroll-scroll-hide" id="payslip">
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
                        <thead><tr><th>Type</th><th>Days/Hours</th><th>Amount</th></td></thead>
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
                        <thead><tr><th>Type</th><th>Amount</th></td></thead>
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
                        <thead><tr><th>Type</th><th>Amount</th></td></thead>
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

<!-- MODAL FOR STATIC PAYSLIP -->
<div id="staticPayslipModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <div id="staticPayslipContent"></div>
        <div class="modal-footer">
            <button class="send-pdf-btn" id="sendPdfBtn">Send Payslip to Employee</button>
        </div>
    </div>
</div>

<!-- IMPORT TIMESHEET MODAL -->
<div id="importTimesheetModal" class="import-modal">
    <div class="import-modal-content">
        <span style="float:right; cursor:pointer; font-size:24px;" onclick="closeImportModal()">&times;</span>
        <h3>Import Timesheet (CSV)</h3>
        <form id="importTimesheetForm" enctype="multipart/form-data">
            <input type="file" name="timesheet_file" accept=".csv" required>
            <p class="text-xs text-gray-500 mt-2">Expected columns: employee_number, date, time_in, time_out, break_duration</p>
            <button type="submit" style="margin-top:10px; background:#007bff; color:white; padding:6px 12px; border:none; border-radius:5px;">Upload</button>
        </form>
    </div>
</div>

<script>
// ================= GLOBAL VARIABLES =================
let employeesList = [];
let selectedEmployeeIds = new Set();
let currentEmployeeIndex = 0;
let bulkEmployeesArray = [];

// Logo URL injected from PHP
var companyLogoUrl = <?= json_encode($companyLogoUrl) ?>;

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
        setupBulkSearch();
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
        setupBulkSearch();
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

function setupBulkSearch() {
    const searchInput = document.getElementById('bulkSearchInput');
    if (!searchInput) return;
    searchInput.addEventListener('input', function() {
        const term = this.value.toLowerCase();
        const items = document.querySelectorAll('#bulkEmployeeList .bulk-employee-item');
        items.forEach(item => {
            const label = item.querySelector('label')?.innerText.toLowerCase() || '';
            item.style.display = label.includes(term) ? 'flex' : 'none';
        });
    });
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
    const mode = document.querySelector('.mode-btn.active').textContent.includes('Individual') ? 'individual' : 'bulk';
    
    if (mode === 'individual') {
        const employeeId = document.getElementById('selectedEmployeeId').value;
        if (!employeeId) {
            alert('Please select an employee first.');
            return;
        }
        await fillEmployeeData(employeeId);
    } else {
        if (selectedEmployeeIds.size === 0) {
            alert('Please select at least one employee.');
            return;
        }
        bulkEmployeesArray = Array.from(selectedEmployeeIds);
        currentEmployeeIndex = 0;
        await processNextBulkEmployee();
    }
}

async function processNextBulkEmployee() {
    if (currentEmployeeIndex >= bulkEmployeesArray.length) {
        alert('All employees have been processed.');
        return;
    }
    const empId = bulkEmployeesArray[currentEmployeeIndex];
    const emp = employeesList.find(e => e.id == empId);
    if (confirm(`Auto-fill data for ${emp.name}? Click OK to continue, Cancel to skip.`)) {
        await fillEmployeeData(empId);
    }
    currentEmployeeIndex++;
    setTimeout(() => processNextBulkEmployee(), 500);
}

async function fillEmployeeData(employeeId) {
    const periodStart = document.getElementById('periodStart').value;
    const periodEnd = document.getElementById('periodEnd').value;
    if (!periodStart || !periodEnd) {
        alert('Please set a valid Pay Period.');
        return false;
    }

    // 1. Get basic employee details
    const month = periodStart.substring(0, 7);
    const empUrl = `<?= site_url('client_portal/payroll/employee_details') ?>/${employeeId}?month=${month}`;
    let empData;
    try {
        const empResp = await fetch(empUrl);
        if (!empResp.ok) throw new Error('Failed to fetch employee data');
        empData = await empResp.json();
    } catch (err) {
        alert('Could not load employee details: ' + err.message);
        return false;
    }

    document.getElementById('empPosition').value = empData.position || '';
    document.getElementById('monthlySalary').value = empData.monthly_salary || 0;
    const monthly = parseFloat(empData.monthly_salary) || 0;
    document.getElementById('semiMonthlySalary').value = (monthly / 2).toFixed(2);
    const dailyRateVal = empData.daily_rate || (monthly / 22).toFixed(2);
    document.getElementById('dailyRate').value = dailyRateVal;
    const hourlyRateVal = empData.hourly_rate || (monthly / 160);
    document.getElementById('hourlyRate').value = hourlyRateVal.toFixed(2);

    // 2. Fetch attendance summary
    const sumUrl = `<?= site_url('client_portal/payroll/attendance-summary') ?>/${employeeId}/${periodStart}/${periodEnd}`;
    let summary;
    try {
        const sumResp = await fetch(sumUrl);
        if (!sumResp.ok) throw new Error('Failed to fetch attendance summary');
        summary = await sumResp.json();
    } catch (err) {
        alert('Could not load attendance summary: ' + err.message);
        return false;
    }

    // 3. Populate raw sheet with correct capping of absence deduction
    document.getElementById('absentDays').value = summary.absent_days;
    const dailyRate = parseFloat(document.getElementById('dailyRate').value) || 0;
    const semiMonthlyPay = parseFloat(document.getElementById('semiMonthlySalary').value) || 0;
    let absentAmount = summary.absent_days * dailyRate;
    if (absentAmount > semiMonthlyPay) absentAmount = semiMonthlyPay;
    document.getElementById('absentPhp').value = absentAmount.toFixed(2);
    
    const tardyHours = summary.tardy_minutes / 60;
    const hourlyRate = parseFloat(document.getElementById('hourlyRate').value) || 0;
    const undertimeAmount = tardyHours * hourlyRate;
    document.getElementById('utPhp').value = undertimeAmount.toFixed(2);
    
    const nightDiffRate = 0.10;
    const nightDiffPay = summary.night_diff_hours * hourlyRate * nightDiffRate;
    document.getElementById('workdayNightDiffPhp').value = nightDiffPay.toFixed(2);
    
    const otRegularRate = 1.25;
    const otRestRate   = 1.30;
    const otHolidayRate = 2.00;
    const regOtPay = summary.reg_ot_hours * hourlyRate * otRegularRate;
    const restOtPay = summary.rest_ot_hours * hourlyRate * otRestRate;
    const holOtPay  = summary.holiday_ot_hours * hourlyRate * otHolidayRate;
    document.getElementById('workdayOtPhp').value = regOtPay.toFixed(2);
    document.getElementById('satOtPhp').value = restOtPay.toFixed(2);
    document.getElementById('sunOtPhp').value = holOtPay.toFixed(2);
    
    const emp = employeesList.find(e => e.id == employeeId);
    if (emp) document.getElementById('empName').value = emp.name;
    document.getElementById('selectedEmployeeId').value = employeeId;
    
    updateContributions(monthly);
    updateWithholdingTax(monthly);
    
    alert('Raw sheet auto‑filled with attendance data.');
    return true;
}

function updateContributions(monthlySalary) {
    let sss = 0;
    if (monthlySalary <= 4250) sss = 292.5;
    else if (monthlySalary <= 4750) sss = 337.5;
    else if (monthlySalary <= 5250) sss = 382.5;
    else if (monthlySalary <= 5750) sss = 427.5;
    else if (monthlySalary <= 6250) sss = 472.5;
    else if (monthlySalary <= 6750) sss = 517.5;
    else if (monthlySalary <= 7250) sss = 562.5;
    else if (monthlySalary <= 7750) sss = 607.5;
    else if (monthlySalary <= 8250) sss = 652.5;
    else if (monthlySalary <= 8750) sss = 697.5;
    else if (monthlySalary <= 9250) sss = 742.5;
    else if (monthlySalary <= 9750) sss = 787.5;
    else if (monthlySalary <= 10250) sss = 832.5;
    else if (monthlySalary <= 10750) sss = 877.5;
    else if (monthlySalary <= 11250) sss = 922.5;
    else if (monthlySalary <= 11750) sss = 967.5;
    else if (monthlySalary <= 12250) sss = 1012.5;
    else if (monthlySalary <= 12750) sss = 1057.5;
    else if (monthlySalary <= 13250) sss = 1102.5;
    else if (monthlySalary <= 13750) sss = 1147.5;
    else if (monthlySalary <= 14250) sss = 1192.5;
    else if (monthlySalary <= 14750) sss = 1237.5;
    else if (monthlySalary <= 15250) sss = 1282.5;
    else if (monthlySalary <= 15750) sss = 1327.5;
    else if (monthlySalary <= 16250) sss = 1372.5;
    else if (monthlySalary <= 16750) sss = 1417.5;
    else if (monthlySalary <= 17250) sss = 1462.5;
    else if (monthlySalary <= 17750) sss = 1507.5;
    else if (monthlySalary <= 18250) sss = 1552.5;
    else if (monthlySalary <= 18750) sss = 1597.5;
    else if (monthlySalary <= 19250) sss = 1642.5;
    else if (monthlySalary <= 19750) sss = 1687.5;
    else if (monthlySalary <= 20250) sss = 1732.5;
    else if (monthlySalary <= 20750) sss = 1777.5;
    else if (monthlySalary <= 21250) sss = 1822.5;
    else if (monthlySalary <= 21750) sss = 1867.5;
    else if (monthlySalary <= 22250) sss = 1912.5;
    else if (monthlySalary <= 22750) sss = 1957.5;
    else if (monthlySalary <= 23250) sss = 2002.5;
    else if (monthlySalary <= 23750) sss = 2047.5;
    else if (monthlySalary <= 24250) sss = 2092.5;
    else if (monthlySalary <= 24750) sss = 2137.5;
    else if (monthlySalary <= 25250) sss = 2182.5;
    else if (monthlySalary <= 25750) sss = 2227.5;
    else if (monthlySalary <= 26250) sss = 2272.5;
    else if (monthlySalary <= 26750) sss = 2317.5;
    else if (monthlySalary <= 27250) sss = 2362.5;
    else if (monthlySalary <= 27750) sss = 2407.5;
    else if (monthlySalary <= 28250) sss = 2452.5;
    else if (monthlySalary <= 28750) sss = 2497.5;
    else if (monthlySalary <= 29250) sss = 2542.5;
    else if (monthlySalary <= 29750) sss = 2587.5;
    else sss = 2925;
    
    const phic = Math.min(550, monthlySalary * 0.025);
    const hdmf = Math.min(200, monthlySalary * 0.02);
    document.getElementById('sssEE').value = sss.toFixed(2);
    document.getElementById('phicEE').value = phic.toFixed(2);
    document.getElementById('hdmfEE').value = hdmf.toFixed(2);
}

function updateWithholdingTax(monthlySalary) {
    let tax = 0;
    if (monthlySalary <= 20833) tax = 0;
    else if (monthlySalary <= 33333) tax = (monthlySalary - 20833) * 0.15;
    else if (monthlySalary <= 66667) tax = 2083.33 + (monthlySalary - 33333) * 0.20;
    else if (monthlySalary <= 166667) tax = 7083.33 + (monthlySalary - 66667) * 0.25;
    else if (monthlySalary <= 666667) tax = 30833.33 + (monthlySalary - 166667) * 0.30;
    else tax = 180833.33 + (monthlySalary - 666667) * 0.35;
    document.getElementById('wtPayable').value = (tax / 2).toFixed(2);
}

// ================= SYNC RAW SHEET TO REGISTER =================
function syncToRegister() {
    const monthly = parseFloat(document.getElementById('monthlySalary').value) || 0;
    document.getElementById('monthlyBasicSalary').value = monthly;
    document.getElementById('semiMonthlyBasicSalary').value = (monthly / 2).toFixed(2);
    
    document.getElementById('deMinYearEnd').value = document.getElementById('yearEndGifts').value;
    document.getElementById('deMinRiceSubsidy').value = document.getElementById('riceSubsidy').value;
    document.getElementById('deMinUniform').value = document.getElementById('uniformAllowance').value;
    document.getElementById('deMinLaundry').value = document.getElementById('laundryAllowance').value;
    document.getElementById('deMinMedical').value = (parseFloat(document.getElementById('medicalAllowance').value) + parseFloat(document.getElementById('medicalAllowanceAdd').value)) || 0;
    
    document.getElementById('salaryAdjustmentReg').value = document.getElementById('salaryAdjustment').value;
    const overtime = (parseFloat(document.getElementById('workdayOtPhp').value) + parseFloat(document.getElementById('satOtPhp').value) + parseFloat(document.getElementById('sunOtPhp').value) + parseFloat(document.getElementById('holOtPhp').value)).toFixed(2);
    document.getElementById('overtimeReg').value = overtime;
    document.getElementById('ndReg').value = document.getElementById('workdayNightDiffPhp').value;
    const absences = (parseFloat(document.getElementById('absentPhp').value) + parseFloat(document.getElementById('utPhp').value)).toFixed(2);
    document.getElementById('absencesTardiness').value = absences;
    document.getElementById('holidayPayReg').value = document.getElementById('holidayPay').value;
    const gross = (parseFloat(document.getElementById('semiMonthlyBasicSalary').value) + parseFloat(overtime) + parseFloat(document.getElementById('ndReg').value) + parseFloat(document.getElementById('holidayPayReg').value) + parseFloat(document.getElementById('salaryAdjustmentReg').value)).toFixed(2);
    document.getElementById('grossCompReg').value = gross;
    document.getElementById('grossTaxableComp').value = gross;
    
    document.getElementById('sssEE').value = document.getElementById('sssEE').value;
    document.getElementById('phicEE').value = document.getElementById('phicEE').value;
    document.getElementById('hdmfEE').value = document.getElementById('hdmfEE').value;
    document.getElementById('wtPayable').value = document.getElementById('wtPayable').value;
    
    const netPay = (parseFloat(gross) - (parseFloat(document.getElementById('sssEE').value) + parseFloat(document.getElementById('phicEE').value) + parseFloat(document.getElementById('hdmfEE').value) + parseFloat(document.getElementById('wtPayable').value))).toFixed(2);
    document.getElementById('netPayRegister').value = netPay;
    
    nextTab('register');
}

function syncToPayslip() {
    document.getElementById('payslipEmpName').value = document.getElementById('empName').value;
    document.getElementById('payslipPosition').value = document.getElementById('empPosition').value;
    document.getElementById('payslipPeriodStart').value = document.getElementById('periodStart').value;
    document.getElementById('payslipPeriodEnd').value = document.getElementById('periodEnd').value;
    buildPayslipTable();
    nextTab('payslip');
}

// ================= SAFE NUMBER PARSING =================
function safeParseFloat(value, defaultValue = 0) {
    let num = parseFloat(value);
    return isNaN(num) ? defaultValue : num;
}

// ================= PAYSLIP FUNCTIONS (CORRECTED) =================
function getComputedPayslipValues() {
    let monthly = safeParseFloat(document.getElementById('monthlySalary')?.value);
    let semiMonthly = safeParseFloat(document.getElementById('semiMonthlySalary')?.value);
    let basicPay = semiMonthly > 0 ? semiMonthly : (monthly / 2);
    if(basicPay <= 0 && monthly > 0) basicPay = monthly/2;
    let ratePerHour = monthly > 0 ? monthly / 160 : 126.44;
    let hoursWorked = basicPay / ratePerHour;
    
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
                       safeParseFloat(document.getElementById('holidayPay')?.value);
    
    let nonTaxable = safeParseFloat(document.getElementById('yearEndGifts')?.value) +
                     safeParseFloat(document.getElementById('riceSubsidy')?.value) +
                     safeParseFloat(document.getElementById('uniformAllowance')?.value) +
                     safeParseFloat(document.getElementById('laundryAllowance')?.value) +
                     safeParseFloat(document.getElementById('medicalAllowance')?.value) +
                     safeParseFloat(document.getElementById('medicalAllowanceAdd')?.value);
    
    let totalDeductionsFromPay = absentAmt + undertimeAmt;
    if (totalDeductionsFromPay > basicPay) totalDeductionsFromPay = basicPay;
    let netBasicPay = basicPay - totalDeductionsFromPay + nightPremium;
    if (netBasicPay < 0) netBasicPay = 0;
    
    let totalOvertime = regOt + satOt + sunOt + holOt;
    let grossPay = netBasicPay + totalOvertime + taxableOther + nonTaxable;
    
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

function buildPayslipTable() {
    let d = getComputedPayslipValues();
    document.getElementById('payslipEmpName').value = d.empName;
    document.getElementById('payslipPosition').value = d.position;
    document.getElementById('payslipPeriodStart').value = d.periodStart;
    document.getElementById('payslipPeriodEnd').value = d.periodEnd;

    const regRows = [
        { label: 'Basic Pay', id: 'edit_basicPay', days: '-', val: d.basicPay },
        { label: 'Rate per hour', id: 'edit_ratePerHour', days: '-', val: d.ratePerHour },
        { label: 'No. of Hours Worked', id: 'edit_hoursWorked', days: '-', val: d.hoursWorked },
        { label: 'Night Premium', id: 'edit_nightPremium', days: '-', val: d.nightPremium },
        { label: 'Less Absences', id: 'edit_absentAmt', days: '-', val: d.absentAmt },
        { label: 'Late/Undertime', id: 'edit_undertimeAmt', days: '-', val: d.undertimeAmt },
        { label: 'Reg OT', id: 'edit_regOt', days: '-', val: d.regOt },
        { label: 'Sat OT', id: 'edit_satOt', days: '-', val: d.satOt },
        { label: 'Sun OT', id: 'edit_sunOt', days: '-', val: d.sunOt },
        { label: 'Hol OT', id: 'edit_holOt', days: '-', val: d.holOt }
    ];
    let regHtml = '';
    regRows.forEach(row => {
        regHtml += `<tr><td class="text-left">${row.label}</td><td class="text-left">${row.days}</td><td class="text-right"><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any" style="width:100px;">NonNullable</td></tr>`;
    });
    document.getElementById('regularEarningsBody').innerHTML = regHtml;

    const taxableRows = [
        { desc: 'Salary Adjustment', id: 'edit_taxAdj', val: d.taxableOther }
    ];
    let taxableLeftHtml = '';
    taxableRows.forEach(row => {
        taxableLeftHtml += `<tr><td class="text-left"><input type="text" value="${row.desc}" style="width:100%; background:#e5e5e5; border:none;"></td><td class="text-right"><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any" style="width:100px;">NonNullable</td></tr>`;
    });
    document.getElementById('taxableLeftBody').innerHTML = taxableLeftHtml;

    const nonTaxRows = [
        { desc: 'De Minimis Benefit', id: 'edit_nonTaxable', val: d.nonTaxable }
    ];
    let nonTaxHtml = '';
    nonTaxRows.forEach(row => {
        nonTaxHtml += `<tr><td class="text-left"><input type="text" value="${row.desc}" style="width:100%; background:#e5e5e5; border:none;"></td><td class="text-right"><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any" style="width:100px;">NonNullable</td></tr>`;
    });
    document.getElementById('taxableRightBody').innerHTML = nonTaxHtml;

    const dedRows = [
        { label: 'SSS', id: 'edit_sss', val: d.sssDed },
        { label: 'Philhealth', id: 'edit_phic', val: d.phicDed },
        { label: 'Pag-IBIG', id: 'edit_hdmf', val: d.hdmfDed },
        { label: 'Withholding tax', id: 'edit_wtax', val: d.withholdingTax }
    ];
    let dedHtml = '';
    dedRows.forEach(row => {
        dedHtml += `<tr><td class="text-left">${row.label}</td><td class="text-right"><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any" style="width:100px;">NonNullable</td></tr>`;
    });
    document.getElementById('deductionsBody').innerHTML = dedHtml;

    const loanRows = [
        { desc: 'Salary Loan', id: 'edit_sssLoan', val: safeParseFloat(document.getElementById('sssSalaryLoan')?.value) },
        { desc: 'Calamity Loan', id: 'edit_calamityLoan', val: safeParseFloat(document.getElementById('sssCalamityLoan')?.value) },
        { desc: 'Salary Advance', id: 'edit_advSalary', val: safeParseFloat(document.getElementById('advSalary')?.value) }
    ];
    let loanHtml = '';
    loanRows.forEach(row => {
        loanHtml += `<tr><td class="text-left"><input type="text" value="${row.desc}" style="width:100%; background:#e5e5e5; border:none;">NonNullable</td>
                    <td class="text-right"><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any" style="width:100px;">NonNullable</td></tr>`;
    });
    document.getElementById('loansBody').innerHTML = loanHtml;

    const allEditIds = ['edit_basicPay','edit_ratePerHour','edit_hoursWorked','edit_nightPremium','edit_absentAmt','edit_undertimeAmt',
        'edit_regOt','edit_satOt','edit_sunOt','edit_holOt',
        'edit_taxAdj','edit_nonTaxable',
        'edit_sss','edit_phic','edit_hdmf','edit_wtax','edit_sssLoan','edit_calamityLoan','edit_advSalary'];
    allEditIds.forEach(id => {
        let el = document.getElementById(id);
        if(el) el.addEventListener('input', recalcTotals);
    });
    recalcTotals();
}

function recalcTotals() {
    let regularSum = 0;
    const regIds = ['edit_basicPay','edit_nightPremium','edit_regOt','edit_satOt','edit_sunOt','edit_holOt'];
    regIds.forEach(id => { regularSum += safeParseFloat(document.getElementById(id)?.value); });
    let absent = safeParseFloat(document.getElementById('edit_absentAmt')?.value);
    let undertime = safeParseFloat(document.getElementById('edit_undertimeAmt')?.value);
    let totalDeductionsFromPay = absent + undertime;
    let basicPayVal = safeParseFloat(document.getElementById('edit_basicPay')?.value);
    if (totalDeductionsFromPay > basicPayVal) totalDeductionsFromPay = basicPayVal;
    let netRegular = basicPayVal - totalDeductionsFromPay + safeParseFloat(document.getElementById('edit_nightPremium')?.value);
    if (netRegular < 0) netRegular = 0;
    
    let taxableSum = safeParseFloat(document.getElementById('edit_taxAdj')?.value);
    let nonTaxableSum = safeParseFloat(document.getElementById('edit_nonTaxable')?.value);
    let gross = netRegular + taxableSum + nonTaxableSum;
    
    let dedSum = 0;
    ['edit_sss','edit_phic','edit_hdmf','edit_wtax','edit_sssLoan','edit_calamityLoan','edit_advSalary'].forEach(id => {
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
    setNum('edit_regOt', d.regOt);
    setNum('edit_satOt', d.satOt);
    setNum('edit_sunOt', d.sunOt);
    setNum('edit_holOt', d.holOt);
    setNum('edit_taxAdj', d.taxableOther);
    setNum('edit_nonTaxable', d.nonTaxable);
    setNum('edit_sss', d.sssDed);
    setNum('edit_phic', d.phicDed);
    setNum('edit_hdmf', d.hdmfDed);
    setNum('edit_wtax', d.withholdingTax);
    setNum('edit_sssLoan', safeParseFloat(document.getElementById('sssSalaryLoan')?.value));
    setNum('edit_calamityLoan', safeParseFloat(document.getElementById('sssCalamityLoan')?.value));
    setNum('edit_advSalary', safeParseFloat(document.getElementById('advSalary')?.value));
    recalcTotals();
}
function setNum(id, val) { let el = document.getElementById(id); if(el) el.value = val.toFixed(2); }

// ================= GENERATE PAYSLIP (WITH LOGO) =================
function computePayslipData() {
    let monthly = safeParseFloat(document.getElementById('monthlySalary')?.value, 0);
    let semiMonthly = safeParseFloat(document.getElementById('semiMonthlySalary')?.value, 0);
    let basicPay = semiMonthly > 0 ? semiMonthly : (monthly / 2);
    if(basicPay <= 0 && monthly > 0) basicPay = monthly/2;
    let ratePerHour = monthly > 0 ? monthly / 160 : 126.44;
    let hoursWorked = basicPay / ratePerHour;
    
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
                       safeParseFloat(document.getElementById('holidayPay')?.value);
    
    let nonTaxable = safeParseFloat(document.getElementById('yearEndGifts')?.value) +
                     safeParseFloat(document.getElementById('riceSubsidy')?.value) +
                     safeParseFloat(document.getElementById('uniformAllowance')?.value) +
                     safeParseFloat(document.getElementById('laundryAllowance')?.value) +
                     safeParseFloat(document.getElementById('medicalAllowance')?.value) +
                     safeParseFloat(document.getElementById('medicalAllowanceAdd')?.value);
    
    let totalDeductionsFromPay = absentAmt + undertimeAmt;
    if (totalDeductionsFromPay > basicPay) totalDeductionsFromPay = basicPay;
    let netBasicPay = basicPay - totalDeductionsFromPay + nightPremium;
    if (netBasicPay < 0) netBasicPay = 0;
    
    let totalOvertime = regOt + satOt + sunOt + holOt;
    let grossPay = netBasicPay + totalOvertime + taxableOther + nonTaxable;
    
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

function generatePayslipHTML(d) {
    const logoHtml = companyLogoUrl ? `<div style="text-align:center; margin-bottom:20px;"><img src="${companyLogoUrl}" style="max-height:80px; margin:0 auto;"></div>` : '';
    return `
        <div style="font-family: Arial, sans-serif; background: #fff; border: 1px solid #000; padding: 20px; width: 100%; box-sizing: border-box;">
            ${logoHtml}
            <h2 style="text-align: center; margin: 5px 0 10px;">PAY SLIP</h2>
            <table style="width:100%; border-collapse: collapse; font-size:13px;">
                <tr><td style="border:1px solid #000; padding:5px;"><strong>Employee:</strong></td><td style="border:1px solid #000; padding:5px;">${escapeHtml(d.empName)}</td>
                    <td style="border:1px solid #000; padding:5px;"></td>
                    <td style="border:1px solid #000; padding:5px;"><strong>Payroll period:</strong></td>
                    <td style="border:1px solid #000; padding:5px;" colspan="2">${d.formatStart} – ${d.formatEnd}</td>
                </tr>
                <tr><td style="border:1px solid #000; padding:5px;"><strong>Position:</strong></td><td colspan="5" style="border:1px solid #000; padding:5px;">${escapeHtml(d.position)}</td></tr>
            </table>
            <table style="width:100%; border-collapse: collapse; font-size:13px; margin-top:10px;">
                <tr><th style="border:1px solid #000; padding:5px; background:#eee; text-align:center;">REGULAR EARNINGS</th>
                    <th style="border:1px solid #000; padding:5px; background:#eee; text-align:center;">TAXABLE OTHER EARNINGS</th>
                    <th style="border:1px solid #000; padding:5px; background:#eee; text-align:center;">NON-TAXABLE OTHER EARNINGS</th></tr>
                <tr><td style="border:1px solid #000; padding:5px; vertical-align:top;">
                        <table style="width:100%;">
                            <tr><td class="text-left">Basic pay:</td><td class="text-right">${d.basicPay.toFixed(2)}</td></tr>
                            <tr><td class="text-left">Rate per hour:</td><td class="text-right">${d.ratePerHour.toFixed(2)}</td></tr>
                            <tr><td class="text-left">No. of hours worked:</td><td class="text-right">${d.hoursWorked.toFixed(2)}</td></tr>
                            <tr><td class="text-left">Night premium:</td><td class="text-right">${d.nightPremium.toFixed(2)}</td></tr>
                            <tr><td class="text-left">Less: Absences</td><td class="text-right">${d.absentAmt.toFixed(2)}</td></tr>
                            <tr><td class="text-left">Late/Undertime</td><td class="text-right">${d.undertimeAmt.toFixed(2)}</td></tr>
                            <tr><td class="text-left"><strong>Net basic pay</strong></td><td class="text-right"><strong>${d.netBasicPay.toFixed(2)}</strong></td></tr>
                            <tr><td class="text-left">Reg. OT</td><td class="text-right">${d.regOt.toFixed(2)}</td></tr>
                            <tr><td class="text-left">Sat OT</td><td class="text-right">${d.satOt.toFixed(2)}</td></tr>
                            <tr><td class="text-left">Sun OT</td><td class="text-right">${d.sunOt.toFixed(2)}</td></tr>
                            <tr><td class="text-left">Holiday OT</td><td class="text-right">${d.holOt.toFixed(2)}</td></tr>
                        </table>
                    </td>
                    <td style="border:1px solid #000; padding:5px; vertical-align:top;">
                        <table style="width:100%;">
                            <tr><td class="text-left">Salary adjustment:</td><td class="text-right">${d.taxableOther.toFixed(2)}</td></tr>
                        </table>
                    </td>
                    <td style="border:1px solid #000; padding:5px; vertical-align:top;">
                        <table style="width:100%;">
                            <tr><td class="text-left">De Minimis Benefit:</td><td class="text-right">${d.nonTaxable.toFixed(2)}</td></tr>
                        </table>
                    </td>
                </tr>
                <tr><td colspan="2" style="border:1px solid #000; padding:5px; text-align:right;"><strong>GROSS PAY</strong></td>
                    <td style="border:1px solid #000; padding:5px; text-align:right;"><strong>${d.grossPay.toFixed(2)}</strong></td>
                </tr>
            </table>
            <table style="width:100%; border-collapse: collapse; font-size:13px; margin-top:10px;">
                <tr><th style="border:1px solid #000; padding:5px; background:#eee; text-align:center;">REGULAR DEDUCTIONS</th>
                    <th style="border:1px solid #000; padding:5px; background:#eee; text-align:center;">LOANS/ADVANCES</th></tr>
                <tr><td style="border:1px solid #000; padding:5px; vertical-align:top;">
                        <table style="width:100%;">
                            <tr><td class="text-left">SSS</td><td class="text-right">${d.sssDed.toFixed(2)}</td></tr>
                            <tr><td class="text-left">PhilHealth</td><td class="text-right">${d.phicDed.toFixed(2)}</td></tr>
                            <tr><td class="text-left">Pag-IBIG</td><td class="text-right">${d.hdmfDed.toFixed(2)}</td></tr>
                            <tr><td class="text-left">Withholding tax</td><td class="text-right">${d.withholdingTax.toFixed(2)}</td></tr>
                        </table>
                    </td>
                    <td style="border:1px solid #000; padding:5px; vertical-align:top;">
                        <table style="width:100%;">
                            <tr><td class="text-left">Salary Loan</td><td class="text-right">${safeParseFloat(document.getElementById('sssSalaryLoan')?.value).toFixed(2)}</td></tr>
                            <tr><td class="text-left">Calamity Loan</td><td class="text-right">${safeParseFloat(document.getElementById('sssCalamityLoan')?.value).toFixed(2)}</td></tr>
                            <tr><td class="text-left">Salary Advance</td><td class="text-right">${safeParseFloat(document.getElementById('advSalary')?.value).toFixed(2)}</td></tr>
                        </table>
                    </td>
                </tr>
                <tr><td colspan="2" style="border:1px solid #000; padding:5px; text-align:right;"><strong>TOTAL DEDUCTIONS</strong></td>
                    <td style="border:1px solid #000; padding:5px; text-align:right;"><strong>${d.totalDeductions.toFixed(2)}</strong></td>
                </tr>
            </table>
            <table style="width:100%; border-collapse: collapse; font-size:13px; margin-top:10px;">
                <tr><td style="border:1px solid #000; padding:5px;">Employee signature:</td>
                    <td style="border:1px solid #000; padding:5px; height:40px;"></td>
                    <td style="border:1px solid #000; padding:5px; text-align:right;"><strong>NET PAY:</strong></td>
                    <td style="border:1px solid #000; padding:5px; text-align:right; background:#f8d7da;"><strong>${d.netPay.toFixed(2)}</strong></td>
                </tr>
            </table>
        </div>
    `;
}

function generateStaticPayslip() {
    const d = computePayslipData();
    if (!d.empName || d.empName === 'Not Selected') {
        alert('Please select an employee and fill in the Raw Sheet first.');
        return;
    }
    
    const employeeIdToSubmit = document.getElementById('selectedEmployeeId').value;
    if (!employeeIdToSubmit) {
        alert('Employee ID not found. Please select an employee.');
        return;
    }
    const startDate = d.periodStart;
    const endDate = d.periodEnd;
    if (!startDate || !endDate) {
        alert('Period start/end missing.');
        return;
    }

    const payload = {
        employee_id: employeeIdToSubmit,
        period_start: startDate,
        period_end: endDate,
        basic_pay: d.basicPay,
        night_diff_pay: d.nightPremium,
        overtime_pay: d.regOt + d.satOt + d.sunOt + d.holOt,
        holiday_pay: safeParseFloat(document.getElementById('holidayPay')?.value),
        de_minimis: d.nonTaxable,
        other_earnings_taxable: d.taxableOther,
        other_earnings_non_taxable: 0,
        sss_ee: d.sssDed,
        phic_ee: d.phicDed,
        hdmf_ee: d.hdmfDed,
        withholding_tax: d.withholdingTax,
        sss_loan: safeParseFloat(document.getElementById('sssSalaryLoan')?.value),
        calamity_loan: safeParseFloat(document.getElementById('sssCalamityLoan')?.value),
        salary_advance: safeParseFloat(document.getElementById('advSalary')?.value),
        other_deductions: 0
    };

    fetch('<?= site_url('client_portal/payroll/compute') ?>?employee_id=' + employeeIdToSubmit + '&start_date=' + startDate + '&end_date=' + endDate, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    }).then(response => response.ok ? response.json() : Promise.reject())
      .then(result => {
          if (result.transaction_id) console.log('Transaction saved, ID:', result.transaction_id);
      }).catch(() => {});

    const html = generatePayslipHTML(d);
    document.getElementById('staticPayslipContent').innerHTML = html;
    document.getElementById('staticPayslipModal').style.display = 'block';
    window.currentPayslipData = d;

    let employeeIdToPersist = document.getElementById('selectedEmployeeId').value;
    if (!employeeIdToPersist && selectedEmployeeIds.size > 0) {
        employeeIdToPersist = Array.from(selectedEmployeeIds)[0];
    }
    if (employeeIdToPersist && d.periodStart && d.periodEnd) {
        const savePayload = {
            employee_id:   employeeIdToPersist,
            period_start:  d.periodStart,
            period_end:    d.periodEnd,
            employee_name: d.empName,
            position:      d.position,
            basic_pay:     d.basicPay,
            rate_per_hour: d.ratePerHour,
            hours_worked:  d.hoursWorked,
            night_diff_pay: d.nightPremium,
            absent_amt:    d.absentAmt,
            undertime_amt: d.undertimeAmt,
            net_basic_pay: d.netBasicPay,
            reg_ot:        d.regOt,
            sat_ot:        d.satOt,
            sun_ot:        d.sunOt,
            hol_ot:        d.holOt,
            overtime_pay:  d.regOt + d.satOt + d.sunOt + d.holOt,
            salary_adjustment: safeParseFloat(document.getElementById('salaryAdjustment')?.value),
            override_amount:   safeParseFloat(document.getElementById('overrideAmount')?.value),
            ned_amount:        safeParseFloat(document.getElementById('nedAmount')?.value),
            holiday_pay:       safeParseFloat(document.getElementById('holidayPay')?.value),
            other_earnings_taxable: d.taxableOther,
            year_end_gifts:     safeParseFloat(document.getElementById('yearEndGifts')?.value),
            rice_subsidy:       safeParseFloat(document.getElementById('riceSubsidy')?.value),
            uniform_allowance:  safeParseFloat(document.getElementById('uniformAllowance')?.value),
            laundry_allowance:  safeParseFloat(document.getElementById('laundryAllowance')?.value),
            medical_allowance:  safeParseFloat(document.getElementById('medicalAllowance')?.value) + safeParseFloat(document.getElementById('medicalAllowanceAdd')?.value),
            de_minimis:  d.nonTaxable,
            other_earnings_non_taxable: 0,
            sss_ee:          d.sssDed,
            phic_ee:         d.phicDed,
            hdmf_ee:         d.hdmfDed,
            withholding_tax: d.withholdingTax,
            sss_loan:        safeParseFloat(document.getElementById('sssSalaryLoan')?.value),
            calamity_loan:   safeParseFloat(document.getElementById('sssCalamityLoan')?.value),
            salary_advance:  safeParseFloat(document.getElementById('advSalary')?.value),
            other_deductions: 0,
            net_pay: d.netPay,
        };
        fetch('<?= site_url('client_portal/payroll/savePayslipRecord') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(savePayload)
        }).then(r => r.json()).then(result => {
            if (result.success) window.currentPayslipId = result.payslip_id;
        }).catch(err => console.error('Payslip save error:', err));
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, m => m === '&' ? '&amp;' : (m === '<' ? '&lt;' : '&gt;'));
}

// ================= SEND PDF =================
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
        employee_name: window.currentPayslipData.empName,
        position: window.currentPayslipData.position,
        basic_pay: window.currentPayslipData.basicPay,
        rate_per_hour: window.currentPayslipData.ratePerHour,
        hours_worked: window.currentPayslipData.hoursWorked,
        night_diff_pay: window.currentPayslipData.nightPremium,
        absent_amt: window.currentPayslipData.absentAmt,
        undertime_amt: window.currentPayslipData.undertimeAmt,
        net_basic_pay: window.currentPayslipData.netBasicPay,
        reg_ot: window.currentPayslipData.regOt,
        sat_ot: window.currentPayslipData.satOt,
        sun_ot: window.currentPayslipData.sunOt,
        hol_ot: window.currentPayslipData.holOt,
        overtime_pay: window.currentPayslipData.regOt + window.currentPayslipData.satOt + window.currentPayslipData.sunOt + window.currentPayslipData.holOt,
        salary_adjustment: safeParseFloat(document.getElementById('salaryAdjustment')?.value),
        override_amount: safeParseFloat(document.getElementById('overrideAmount')?.value),
        ned_amount: safeParseFloat(document.getElementById('nedAmount')?.value),
        holiday_pay: safeParseFloat(document.getElementById('holidayPay')?.value),
        other_earnings_taxable: window.currentPayslipData.taxableOther,
        year_end_gifts: safeParseFloat(document.getElementById('yearEndGifts')?.value),
        rice_subsidy: safeParseFloat(document.getElementById('riceSubsidy')?.value),
        uniform_allowance: safeParseFloat(document.getElementById('uniformAllowance')?.value),
        laundry_allowance: safeParseFloat(document.getElementById('laundryAllowance')?.value),
        medical_allowance: safeParseFloat(document.getElementById('medicalAllowance')?.value) + safeParseFloat(document.getElementById('medicalAllowanceAdd')?.value),
        de_minimis: window.currentPayslipData.nonTaxable,
        other_earnings_non_taxable: 0,
        sss_ee: window.currentPayslipData.sssDed,
        phic_ee: window.currentPayslipData.phicDed,
        hdmf_ee: window.currentPayslipData.hdmfDed,
        withholding_tax: window.currentPayslipData.withholdingTax,
        sss_loan: safeParseFloat(document.getElementById('sssSalaryLoan')?.value),
        calamity_loan: safeParseFloat(document.getElementById('sssCalamityLoan')?.value),
        salary_advance: safeParseFloat(document.getElementById('advSalary')?.value),
        other_deductions: 0,
        net_pay: window.currentPayslipData.netPay,
    };
    try {
        const response = await fetch('<?= site_url('client_portal/payroll/sendPdf') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            alert('Error sending PDF: ' + (result.error || `Server error ${response.status}`));
            return;
        }
        alert('PDF payslip sent successfully to employee email.');
        window.location.reload();
    } catch (error) {
        console.error('Send PDF error:', error);
        alert('Network error — could not reach server. Check console for details.');
    }
}

// ================= EXPORT RAW SHEET (CSV) =================
function exportRawSheet() {
    const employeeId = document.getElementById('selectedEmployeeId').value;
    if (!employeeId) {
        alert('Please select an employee first.');
        return;
    }
    const fields = {
        employee_id: employeeId,
        employee_name: document.getElementById('empName').value,
        position: document.getElementById('empPosition').value,
        period_start: document.getElementById('periodStart').value,
        period_end: document.getElementById('periodEnd').value,
        monthly_salary: document.getElementById('monthlySalary').value,
        semi_monthly_salary: document.getElementById('semiMonthlySalary').value,
        daily_rate: document.getElementById('dailyRate').value,
        hourly_rate: document.getElementById('hourlyRate').value,
        year_end_gifts: document.getElementById('yearEndGifts').value,
        rice_subsidy: document.getElementById('riceSubsidy').value,
        uniform_allowance: document.getElementById('uniformAllowance').value,
        laundry_allowance: document.getElementById('laundryAllowance').value,
        medical_allowance: document.getElementById('medicalAllowance').value,
        medical_allowance_add: document.getElementById('medicalAllowanceAdd').value,
        salary_adjustment: document.getElementById('salaryAdjustment').value,
        override_amount: document.getElementById('overrideAmount').value,
        ned_amount: document.getElementById('nedAmount').value,
        holiday_pay: document.getElementById('holidayPay').value,
        gross_comp: document.getElementById('grossComp').value,
        sss_salary_loan: document.getElementById('sssSalaryLoan').value,
        sss_calamity_loan: document.getElementById('sssCalamityLoan').value,
        salary_advance: document.getElementById('advSalary').value,
        calamity_advance: document.getElementById('advCalamity').value,
        ut_hours: document.getElementById('utHours').value,
        ut_php: document.getElementById('utPhp').value,
        absent_days: document.getElementById('absentDays').value,
        absent_php: document.getElementById('absentPhp').value,
        workday_ot_hours: document.getElementById('workdayOtHours').value,
        workday_ot_php: document.getElementById('workdayOtPhp').value,
        night_diff_days: document.getElementById('workdayNightDiffDays').value,
        night_diff_php: document.getElementById('workdayNightDiffPhp').value,
        sat_ot_hours: document.getElementById('satOtHours').value,
        sat_ot_php: document.getElementById('satOtPhp').value,
        sun_ot_hours: document.getElementById('sunOtHours').value,
        sun_ot_php: document.getElementById('sunOtPhp').value,
        hol_ot_php: document.getElementById('holOtPhp').value,
        sss_ee: document.getElementById('sssEE').value,
        phic_ee: document.getElementById('phicEE').value,
        hdmf_ee: document.getElementById('hdmfEE').value,
        wt_payable: document.getElementById('wtPayable').value,
    };
    const csvRows = [];
    csvRows.push(Object.keys(fields).join(','));
    csvRows.push(Object.values(fields).map(v => `"${v}"`).join(','));
    const blob = new Blob([csvRows.join('\n')], {type: 'text/csv'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `raw_sheet_${employeeId}_${new Date().toISOString().slice(0,19)}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}

// ================= EXPORT REGISTER (CSV) =================
function exportRegister() {
    const month = document.getElementById('monthPicker')?.value || new Date().toISOString().slice(0,7);
    window.location.href = `<?= site_url('client_portal/payroll/exportRegister') ?>?month=${month}`;
}

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
    document.getElementById('exportRawSheetBtn')?.addEventListener('click', exportRawSheet);
    document.getElementById('exportRegisterBtn')?.addEventListener('click', exportRegister);
    
    document.getElementById('exportTimesheetBtn')?.addEventListener('click', function() {
        const employeeId = document.getElementById('selectedEmployeeId').value;
        const start = document.getElementById('periodStart').value;
        const end = document.getElementById('periodEnd').value;
        if (!employeeId || !start || !end) {
            alert('Please select an employee and period first.');
            return;
        }
        window.location.href = `<?= site_url('client_portal/payroll/exportTimesheet') ?>?employee_id=${employeeId}&start_date=${start}&end_date=${end}`;
    });
    
    document.getElementById('importTimesheetBtn')?.addEventListener('click', function() {
        document.getElementById('importTimesheetModal').style.display = 'block';
    });
    
    document.getElementById('importTimesheetForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const response = await fetch('<?= site_url('client_portal/payroll/importTimesheet') ?>', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            alert('Timesheet imported successfully.');
            closeImportModal();
        } else {
            alert('Import failed: ' + result.error);
        }
    });
    
    window.closeImportModal = function() {
        document.getElementById('importTimesheetModal').style.display = 'none';
    };
    
    document.querySelector('.close-modal')?.addEventListener('click', function() {
        document.getElementById('staticPayslipModal').style.display = 'none';
    });
    window.onclick = function(event) {
        const modal = document.getElementById('staticPayslipModal');
        if (event.target == modal) modal.style.display = 'none';
    };
});
</script>
</body>
</html>