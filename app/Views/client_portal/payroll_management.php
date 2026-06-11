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
    /* Bulk chip-select */
    .bulk-search-wrapper {
        position: relative;
    }
    .bulk-search-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 13px;
        outline: none;
        box-sizing: border-box;
    }
    .bulk-search-input:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 2px rgba(99,102,241,.15);
    }
    .bulk-dropdown {
        display: none;
        position: absolute;
        top: calc(100% + 4px);
        left: 0; right: 0;
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,.1);
        max-height: 200px;
        overflow-y: auto;
        z-index: 200;
    }
    .bulk-dropdown-item {
        padding: 8px 12px;
        font-size: 13px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .bulk-dropdown-item:hover,
    .bulk-dropdown-item.focused {
        background: #f0f0ff;
    }
    .bulk-dropdown-item.already-selected {
        color: #9ca3af;
        cursor: default;
    }
    .bulk-dropdown-item.already-selected:hover {
        background: transparent;
    }
    .bulk-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
        min-height: 32px;
    }
    .bulk-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #e0e7ff;
        color: #3730a3;
        border-radius: 20px;
        padding: 4px 10px 4px 12px;
        font-size: 12px;
        font-weight: 500;
    }
    .bulk-chip-remove {
        background: none;
        border: none;
        cursor: pointer;
        color: #6366f1;
        font-size: 14px;
        line-height: 1;
        padding: 0;
        display: flex;
        align-items: center;
    }
    .bulk-chip-remove:hover { color: #dc2626; }

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

    <!-- Year-end pre-fill banner (hidden by default, shown when opened from 13th month/annualization tab) -->
    <div id="yearEndPrefillBanner" style="display:none;background:#eff6ff;border:1px solid #bfdbfe;
         border-radius:8px;padding:10px 16px;margin-bottom:14px;font-size:13px;color:#1e40af;
         display:none;align-items:center;gap:12px;">
        <span style="font-size:16px;">&#8594;</span>
        <span id="yearEndBannerMsg"></span>
        <button onclick="document.getElementById('yearEndPrefillBanner').style.display='none'"
                style="margin-left:auto;background:none;border:none;font-size:18px;
                       cursor:pointer;color:#60a5fa;line-height:1;">&times;</button>
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
                        <label>Add Employees</label>
                        <div class="bulk-search-wrapper">
                            <input type="text" id="bulkSearchInput" class="bulk-search-input"
                                placeholder="Type a name to search…" autocomplete="off">
                            <div id="bulkDropdown" class="bulk-dropdown"></div>
                        </div>
                        <div id="bulkChips" class="bulk-chips"></div>
                        <div class="mt-1 text-xs text-gray-400" id="bulkSelectionCount">0 employees selected</div>
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
                    <div>
                        <label>Reg Holiday Hours</label>
                        <input type="number" id="regHolHours" value="0" oninput="calcHolPay()">
                        <label class="mt-1">Reg Holiday Pay (2.0&times;)</label>
                        <input type="number" id="regHolPay" value="0" oninput="updateHolidayTotal()">
                    </div>
                    <div>
                        <label>Spec Holiday Hours</label>
                        <input type="number" id="specHolHours" value="0" oninput="calcHolPay()">
                        <label class="mt-1">Spec Holiday Pay (1.3&times;)</label>
                        <input type="number" id="specHolPay" value="0" oninput="updateHolidayTotal()">
                    </div>
                    <div><label>Gross Compensation</label><input type="number" id="grossComp" value="0"></div>
                </div>
                <input type="hidden" id="holidayPay" value="0">
            </div>

            <!-- LOANS + ADVANCES -->
           <!--  <div class="grid-2 section">
                <div>
                    <h3 class="font-semibold">Loans and Advances</h3>
                    <div><label>Salary Loan</label><input type="number" id="sssSalaryLoan" value="0"></div>
                    <div class="mt-2"><label>Calamity</label><input type="number" id="sssCalamityLoan" value="0"></div>
                </div>
                <div>
                    <h3 class="font-semibold">Advances to Employees</h3>
                    <div><label>Salary Advance</label><input type="number" id="advSalary" value="0"></div>
                    <div class="mt-2"><label>Calamity Advance</label><input type="number" id="advCalamity" value="0"></div>
                </div>
            </div> -->

            <!-- DYNAMIC EMPLOYEE LOANS -->
            <div class="section">

                <h3 class="font-semibold">
                    Loans and Advances
                </h3>

                <p class="subtitle">
                    Active deductions for this payroll period
                </p>

                <div
                    id="employeeLoansContainer"
                    class="grid-2 mt-3"
                >

                    <div class="text-sm text-gray-400">
                        No active loans found.
                    </div>

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
                <h3 class="font-semibold">Rest-Day / Special Holiday Overtime <small style="font-weight:normal;color:#666;">(130%&times;130% = 169%)</small></h3>
                <div class="grid-3">
                    <div><label>Hours</label><input type="number" id="satOtHours" value="0"></div>
                    <div><label>PHP Equivalent</label><input type="number" id="satOtPhp" value="0"></div>
                </div>
            </div>
            <div class="section">
                <h3 class="font-semibold">Regular Holiday Overtime <small style="font-weight:normal;color:#666;">(200%&times;130% = 260%)</small></h3>
                <div class="grid-3">
                    <div><label>Hol OT PHP</label><input type="number" id="holOtPhp" value="0"></div>
                </div>
                <input type="hidden" id="sunOtHours" value="0">
                <input type="hidden" id="sunOtPhp" value="0">
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

                <h3 class="font-semibold mt-4">Loans and Advances</h3>
                <p class="subtitle">Please complete the employee data</p>
                <div class="grid-2">
                    <div><label>Salary</label><input type="number" id="regSssLoanSalary" value="0"></div>
                    <div><label>Calamity</label><input type="number" id="regSssLoanCalamity" value="0"></div>
                </div> -->

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
        _renderBulkChips();
        updateSelectedEmployeeDisplay();
        document.getElementById('selectedEmployeeId').value = '';
        document.getElementById('empSearchInput').value = '';
    } else {
        document.getElementById('bulkSearchInput').value = '';
        document.getElementById('bulkDropdown').style.display = 'none';
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
        console.log(employeesList);
        initSearchSuggestions();
        setupBulkSearch();
    } catch (error) {
        console.error('Failed to load employees:', error);
        employeesList = [
            { id: '1', name: 'Test Employee 1', label: 'Test Employee 1' },
            { id: '2', name: 'Test Employee 2', label: 'Test Employee 2' }
        ];
        initSearchSuggestions();
        alert('Using dummy employee data. Check console for server error.');
    }
}

// ── Bulk chip-select ──────────────────────────────────────────────────────
function setupBulkSearch() {
    const input    = document.getElementById('bulkSearchInput');
    const dropdown = document.getElementById('bulkDropdown');
    if (!input || !dropdown) return;

    let focusedIndex = -1;

    function renderDropdown(term) {
        const q = term.trim().toLowerCase();
        if (!q) { dropdown.style.display = 'none'; return; }

        const matches = employeesList.filter(e => {
            const name = (e.name || '').toLowerCase();
            return name.includes(q);
        }).slice(0, 10);
        if (!matches.length) { dropdown.style.display = 'none'; return; }

        dropdown.innerHTML = matches.map((emp, i) => {
            const already = selectedEmployeeIds.has(emp.id) || selectedEmployeeIds.has(String(emp.id));
            return `<div class="bulk-dropdown-item${already ? ' already-selected' : ''}"
                         data-id="${emp.id}" data-name="${emp.name}" data-index="${i}">
                        ${already ? '✓ ' : ''}${emp.name}
                    </div>`;
        }).join('');
        dropdown.style.display = 'block';
        focusedIndex = -1;
    }

    function getFocusedItems() {
        return Array.from(dropdown.querySelectorAll('.bulk-dropdown-item:not(.already-selected)'));
    }

    input.addEventListener('input', () => renderDropdown(input.value));

    input.addEventListener('keydown', e => {
        const items = getFocusedItems();
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            focusedIndex = Math.min(focusedIndex + 1, items.length - 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            focusedIndex = Math.max(focusedIndex - 1, 0);
        } else if (e.key === 'Enter' && focusedIndex >= 0) {
            e.preventDefault();
            items[focusedIndex]?.click();
            return;
        } else if (e.key === 'Escape') {
            dropdown.style.display = 'none';
            return;
        }
        items.forEach((el, i) => el.classList.toggle('focused', i === focusedIndex));
    });

    dropdown.addEventListener('mousedown', e => {
        const item = e.target.closest('.bulk-dropdown-item');
        if (!item || item.classList.contains('already-selected')) return;
        e.preventDefault();
        addBulkEmployee(item.dataset.id, item.dataset.name);
        input.value = '';
        dropdown.style.display = 'none';
        input.focus();
    });

    document.addEventListener('click', e => {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
}

function addBulkEmployee(id, name) {
    const sid = String(id);
    if (selectedEmployeeIds.has(sid) || selectedEmployeeIds.has(parseInt(sid))) return;
    selectedEmployeeIds.add(sid);
    _renderBulkChips();
    updateSelectedEmployeeDisplay();
}

function removeBulkEmployee(id) {
    selectedEmployeeIds.delete(id);
    selectedEmployeeIds.delete(String(id));
    selectedEmployeeIds.delete(parseInt(id));
    _renderBulkChips();
    updateSelectedEmployeeDisplay();
}

function _renderBulkChips() {
    const container = document.getElementById('bulkChips');
    if (!container) return;
    const chips = [];
    selectedEmployeeIds.forEach(id => {
        const emp = employeesList.find(e => String(e.id) === String(id));
        if (!emp) return;
        chips.push(`<span class="bulk-chip">
            ${emp.name}
            <button type="button" class="bulk-chip-remove" onclick="removeBulkEmployee('${id}')" title="Remove">&#x2715;</button>
        </span>`);
    });
    container.innerHTML = chips.join('');
}

function toggleBulkEmployee(id, checked) {
    if (checked) addBulkEmployee(id, '');
    else removeBulkEmployee(id);
}

function updateSelectedEmployeeDisplay() {
    const count     = selectedEmployeeIds.size;
    const countEl   = document.getElementById('bulkSelectionCount');
    const btn       = document.getElementById('bulkGenerateSendBtn');
    const nameField = document.getElementById('empName');
    if (countEl) countEl.textContent = count + ' employee' + (count === 1 ? '' : 's') + ' selected';
    if (btn)     btn.textContent = `Generate & Send All Selected (${count})`;
    if (count === 0) { nameField.value = ''; return; }
    const names = [];
    selectedEmployeeIds.forEach(id => {
        const emp = employeesList.find(e => String(e.id) === String(id));
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

        const matches = employeesList.filter(emp => {
            const name = (emp.name || '').toLowerCase();
            return name.includes(query);
        });

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
    console.log('FILL EMPLOYEE DATA ID:', employeeId);
    const periodStart = document.getElementById('periodStart').value;
    const periodEnd = document.getElementById('periodEnd').value;
    if (!periodStart || !periodEnd) {
        alert('Please set a valid Pay Period.');
        return false;
    }

    // 1. Get basic employee details (source: employee management, not payroll status)
    const month = periodStart.substring(0, 7);
    const empUrl = `<?= site_url('client_portal/payroll/employee_details') ?>/${employeeId}?month=${month}`;
    let empData;
    try {
        const empResp = await fetch(empUrl);
        empData = await empResp.json();
        if (!empResp.ok) {
            alert('Could not load employee details: ' + (empData.error || `Server error ${empResp.status}`));
            return false;
        }
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

    // Populate de minimis and allowances from employee management
    if (empData.de_minimis_default > 0) {
        document.getElementById('riceSubsidy').value = empData.de_minimis_default;
    }
    if (empData.allowances_default > 0) {
        document.getElementById('salaryAdjustment').value = empData.allowances_default;
    }

    // 2. Fetch attendance summary
    const sumUrl = `<?= site_url('client_portal/payroll/attendance-summary') ?>/${employeeId}/${periodStart}/${periodEnd}`;
    let summary;
    try {
        const sumResp = await fetch(sumUrl);
        const sumData = await sumResp.json().catch(() => null);
        if (!sumResp.ok) {
            const msg = (sumData && sumData.error) ? sumData.error : `Server error ${sumResp.status}`;
            alert('Could not load attendance summary: ' + msg);
            return false;
        }
        summary = sumData;
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
    document.getElementById('utHours').value = tardyHours.toFixed(2);
    document.getElementById('utPhp').value   = undertimeAmount.toFixed(2);
    
    const nightDiffRate = 0.10;
    const nightDiffPay = summary.night_diff_hours * hourlyRate * nightDiffRate;
    document.getElementById('workdayNightDiffPhp').value = nightDiffPay.toFixed(2);
    
    const otRegularRate = 1.25;
    const otRestRate    = parseFloat(window._payrollSettings?.ot_rest_rate)    || 1.69;
    const otHolidayRate = parseFloat(window._payrollSettings?.ot_holiday_rate) || 2.60;
    const regOtPay  = summary.reg_ot_hours     * hourlyRate * otRegularRate;
    const restOtPay = summary.rest_ot_hours    * hourlyRate * otRestRate;
    const holOtPay  = summary.holiday_ot_hours * hourlyRate * otHolidayRate;
    document.getElementById('workdayOtPhp').value = regOtPay.toFixed(2);
    document.getElementById('satOtPhp').value     = restOtPay.toFixed(2);
    document.getElementById('sunOtPhp').value     = '0.00';
    document.getElementById('holOtPhp').value     = holOtPay.toFixed(2);
    
    const emp = employeesList.find(e => e.id == employeeId);
    if (emp) document.getElementById('empName').value = emp.name;
    document.getElementById('selectedEmployeeId').value = employeeId;

    // ================= LOAN FETCH =================
    try {
        const loanUrl =
            `<?= site_url('client_portal/payroll/loanBreakdown') ?>/${employeeId}/${periodStart}/${periodEnd}`;
        console.log('LOAN FETCH URL:', loanUrl);
        const loanResp = await fetch(loanUrl);
        if (!loanResp.ok) {
            throw new Error('Failed to fetch loans');
        }
        const loanData = await loanResp.json();
        renderEmployeeLoans(loanData);

    } catch (err) {
        console.error('Loan fetch failed:', err);
        renderEmployeeLoans({});
    }
    
    const contribs = updateContributions(monthly);
    // BIR RR 3-2015: mandatory contributions do NOT reduce taxable compensation for WT.
    // WT is computed on gross semi-monthly income directly.
    updateWithholdingTax(monthly / 2);

    alert('Raw sheet auto‑filled with attendance data.');
    return true;
}

function renderEmployeeLoans(loans) {

    const container =
        document.getElementById(
            'employeeLoansContainer'
        );

    if (!container) {
        console.error('employeeLoansContainer not found');
        return;
    }

    container.innerHTML = '';

    /*
    |--------------------------------------------------------------------------
    | EMPTY STATE
    |--------------------------------------------------------------------------
    */

    if (!Array.isArray(loans) || loans.length === 0) {

        container.innerHTML = `

            <div class="text-sm text-gray-400">

                No active loans for this payroll period.

            </div>

        `;

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | MATCH RAW SHEET DESIGN
    |--------------------------------------------------------------------------
    */

    let html = `

        <div class="grid grid-2">

    `;

    loans.forEach((loan, index) => {

        const amount =
            parseFloat(loan.amount || 0);

        html += `

            <div>

                <label>

                    ${loan.loan_name || 'Loan'}

                    <span class="text-xs text-gray-400 ml-1">
                        (${loan.due_date || '-'})
                    </span>

                </label>

                <input
                    type="number"
                    id="loan_${index}"
                    data-loan-name="${loan.loan_name || 'Loan'}"
                    value="${amount.toFixed(2)}"
                    readonly
                >

            </div>

        `;
    });

    html += `

        </div>

    `;

    container.innerHTML = html;

    console.log('Rendered Loans:', loans);
}

function calculateTotalLoanDeductions() {

    let total = 0;

    document
        .querySelectorAll(
            '#employeeLoansContainer input'
        )
        .forEach(input => {

            total +=
                parseFloat(input.value || 0);

        });

    return total;

}

// ── SSS / PHIC / HDMF auto-fill (2025 schedule, employee share only) ──────
function computeSSS(monthlySalary) {
    // 2025 SSS Table: [range_start, ee_regular, ee_wisp] — 5% EE, MPF from 15000+
    const t = [
        [0,25,0],[500,50,0],[1000,75,0],[1500,100,0],[2000,125,0],[2500,150,0],
        [3000,175,0],[3500,200,0],[4000,225,0],[4500,250,0],[5000,275,0],[5500,300,0],
        [6000,325,0],[6500,350,0],[7000,375,0],[7500,400,0],[8000,425,0],[8500,450,0],
        [9000,475,0],[9500,500,0],[10000,525,0],[10500,550,0],[11000,575,0],[11500,600,0],
        [12000,625,0],[12500,650,0],[13000,675,0],[13500,700,0],[14000,725,0],[14500,750,0],
        [15000,750,25],[15500,750,25],[16000,750,50],[16500,750,75],[17000,750,100],[17500,750,125],
        [18000,750,150],[18500,750,175],[19000,750,200],[19500,750,225],[20000,750,250],[20500,750,275],
        [21000,750,300],[21500,750,325],[22000,750,350],[22500,750,375],[23000,750,400],[23500,750,425],
        [24000,750,450],[24500,750,475],[25000,750,500],[25500,750,525],[26000,750,550],[26500,750,575],
        [27000,750,600],[27500,750,625],[28000,750,650],[28500,750,675],[29000,750,700],[29500,750,725]
    ];
    let row = t[t.length - 1];
    for (let i = t.length - 1; i >= 0; i--) { if (monthlySalary >= t[i][0]) { row = t[i]; break; } }
    return { regular: row[1], wisp: row[2], total: row[1] + row[2] };
}

(function() {
    fetch('\<?= site_url("client_portal/payroll/getSettings") ?>')
        .then(function(r) { return r.json(); })
        .then(function(s) { window._payrollSettings = s; })
        .catch(function() {});
})();

function calcHolPay() {
    const hr = parseFloat(document.getElementById('hourlyRate')?.value || 0);
    const rh = parseFloat(document.getElementById('regHolHours')?.value || 0);
    const sh = parseFloat(document.getElementById('specHolHours')?.value || 0);
    const regRate  = parseFloat(window._payrollSettings?.reg_holiday_rate)  || 2.00;
    const specRate = parseFloat(window._payrollSettings?.spec_holiday_rate) || 1.30;
    document.getElementById('regHolPay').value  = (rh * hr * regRate).toFixed(2);
    document.getElementById('specHolPay').value = (sh * hr * specRate).toFixed(2);
    updateHolidayTotal();
}

function updateHolidayTotal() {
    const reg  = parseFloat(document.getElementById('regHolPay')?.value  || 0);
    const spec = parseFloat(document.getElementById('specHolPay')?.value || 0);
    document.getElementById('holidayPay').value = (reg + spec).toFixed(2);
}

function updateContributions(monthlySalary) {
    const { regular: sssReg, wisp: sssWisp, total: sss } = computeSSS(monthlySalary);

    // PhilHealth: employee pays 2.5% of monthly salary (50% of 5% premium), cap ₱2,500/mo
    // Excel formula: =IF(D<=100000, D*0.05/2, 100000*0.025)
    const phic = monthlySalary <= 100000 ? monthlySalary * 0.025 : 2500;

    // Pag-IBIG: 2% of monthly salary, employee mandatory cap ₱100 per semi-monthly period
    const hdmf = Math.min(100, monthlySalary * 0.02);

    document.getElementById('sssEE').value    = sssReg.toFixed(2);
    if (document.getElementById('sssWisp'))  document.getElementById('sssWisp').value  = sssWisp.toFixed(2);
    if (document.getElementById('sssTotal')) document.getElementById('sssTotal').value = sss.toFixed(2);
    document.getElementById('phicEE').value   = phic.toFixed(2);
    document.getElementById('hdmfEE').value   = hdmf.toFixed(2);

    return { sss, phic, hdmf };
}

// Monthly NET taxable = (semi-monthly gross taxable - gov't contributions) × 2
// Excel: Z = S(gross taxable) - W(sss) - X(phic) - Y(hdmf); Monthly = Z + prev Z
// We approximate Monthly Taxable = 2 × current Z (symmetric semi-monthly periods).
// BIR Revised WT Table (Jan 1 2023) — official semi-monthly brackets
// Applied directly to semi-monthly taxable compensation each period.
function updateWithholdingTax(semiMonthlyNetTaxable) {
    let semiTax = 0;
    if (semiMonthlyNetTaxable <= 10417)       semiTax = 0;
    else if (semiMonthlyNetTaxable <= 16667)  semiTax =      0 + (semiMonthlyNetTaxable -  10417) * 0.15;
    else if (semiMonthlyNetTaxable <= 33333)  semiTax =  937.50 + (semiMonthlyNetTaxable -  16667) * 0.20;
    else if (semiMonthlyNetTaxable <= 83333)  semiTax = 4270.70 + (semiMonthlyNetTaxable -  33333) * 0.25;
    else if (semiMonthlyNetTaxable <= 333333) semiTax = 16770.70 + (semiMonthlyNetTaxable -  83333) * 0.30;
    else                                      semiTax = 91770.70 + (semiMonthlyNetTaxable - 333333) * 0.35;
    document.getElementById('wtPayable').value = semiTax.toFixed(2);
    return semiTax;
}

// ================= SYNC RAW SHEET TO REGISTER =================
function syncToRegister() {
    const monthly     = parseFloat(document.getElementById('monthlySalary').value) || 0;
    const semiMonthly = monthly / 2;
    document.getElementById('monthlyBasicSalary').value    = monthly;
    document.getElementById('semiMonthlyBasicSalary').value = semiMonthly.toFixed(2);

    // De minimis items (non-taxable)
    document.getElementById('deMinYearEnd').value      = document.getElementById('yearEndGifts').value;
    document.getElementById('deMinRiceSubsidy').value  = document.getElementById('riceSubsidy').value;
    document.getElementById('deMinUniform').value      = document.getElementById('uniformAllowance').value;
    document.getElementById('deMinLaundry').value      = document.getElementById('laundryAllowance').value;
    document.getElementById('deMinMedical').value      = (parseFloat(document.getElementById('medicalAllowance').value) + parseFloat(document.getElementById('medicalAllowanceAdd').value)) || 0;
    const totalDeMin = ['deMinYearEnd','deMinRiceSubsidy','deMinUniform','deMinLaundry','deMinMedical']
        .reduce((s, id) => s + (parseFloat(document.getElementById(id)?.value) || 0), 0);

    document.getElementById('salaryAdjustmentReg').value = document.getElementById('salaryAdjustment').value;
    const overtime = parseFloat(document.getElementById('workdayOtPhp').value || 0)
                   + parseFloat(document.getElementById('satOtPhp').value || 0)
                   + parseFloat(document.getElementById('sunOtPhp').value || 0)
                   + parseFloat(document.getElementById('holOtPhp').value || 0);
    document.getElementById('overtimeReg').value = overtime.toFixed(2);
    const nd      = parseFloat(document.getElementById('workdayNightDiffPhp').value || 0);
    document.getElementById('ndReg').value = nd.toFixed(2);
    const absAmt  = parseFloat(document.getElementById('absentPhp').value || 0)
                  + parseFloat(document.getElementById('utPhp').value || 0);
    document.getElementById('absencesTardiness').value = absAmt.toFixed(2);
    const holPay  = parseFloat(document.getElementById('holidayPay').value || 0);
    document.getElementById('holidayPayReg').value = holPay.toFixed(2);
    const salAdj  = parseFloat(document.getElementById('salaryAdjustmentReg').value || 0);

    // Gross Taxable = basic + OT + ND + holiday pay + salary adj − absences (no de minimis)
    // Gross Compensation = Gross Taxable + de minimis
    // (matches Excel: R = E + SUM(F:Q), S = E + SUM(M:Q))
    const grossTaxable = semiMonthly + overtime + nd + holPay + salAdj - absAmt;
    const grossComp    = grossTaxable + totalDeMin;
    // Recalculate WT on actual gross taxable (includes OT, ND, holiday pay)
    updateWithholdingTax(Math.max(0, grossTaxable));
    document.getElementById('grossCompReg').value     = grossComp.toFixed(2);
    document.getElementById('grossTaxableComp').value = grossTaxable.toFixed(2);
    if (document.getElementById('grossNonTaxableComp'))
        document.getElementById('grossNonTaxableComp').value = totalDeMin.toFixed(2);

    const sss     = parseFloat(document.getElementById('sssEE').value   || 0);
    const phic    = parseFloat(document.getElementById('phicEE').value  || 0);
    const hdmf    = parseFloat(document.getElementById('hdmfEE').value  || 0);
    const wtax    = parseFloat(document.getElementById('wtPayable').value || 0);
    const totalLoans = calculateTotalLoanDeductions();

    // Net Pay = Gross Comp − gov't contributions − withholding tax − loans/advances
    const netPay  = grossComp - sss - phic - hdmf - wtax - totalLoans;
    document.getElementById('netPayRegister').value = netPay.toFixed(2);

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

    let monthly =
        safeParseFloat(
            document.getElementById('monthlySalary')?.value
        );

    let semiMonthly =
        safeParseFloat(
            document.getElementById('semiMonthlySalary')?.value
        );

    let basicPay =
        semiMonthly > 0
            ? semiMonthly
            : (monthly / 2);

    if (basicPay <= 0 && monthly > 0) {
        basicPay = monthly / 2;
    }

    let ratePerHour =
        monthly > 0
            ? monthly / 160
            : 126.44;

    let hoursWorked =
        basicPay / ratePerHour;

    let nightPremium =
        safeParseFloat(
            document.getElementById('workdayNightDiffPhp')?.value
        );

    let absentAmt =
        safeParseFloat(
            document.getElementById('absentPhp')?.value
        );

    let undertimeAmt =
        safeParseFloat(
            document.getElementById('utPhp')?.value
        );

    let regOt =
        safeParseFloat(
            document.getElementById('workdayOtPhp')?.value
        );

    let satOt =
        safeParseFloat(
            document.getElementById('satOtPhp')?.value
        );

    let sunOt =
        safeParseFloat(
            document.getElementById('sunOtPhp')?.value
        );

    let holOt =
        safeParseFloat(
            document.getElementById('holOtPhp')?.value
        );

    let taxableOther =
        safeParseFloat(document.getElementById('salaryAdjustment')?.value) +
        safeParseFloat(document.getElementById('overrideAmount')?.value) +
        safeParseFloat(document.getElementById('nedAmount')?.value) +
        safeParseFloat(document.getElementById('holidayPay')?.value);

    let nonTaxable =
        safeParseFloat(document.getElementById('yearEndGifts')?.value) +
        safeParseFloat(document.getElementById('riceSubsidy')?.value) +
        safeParseFloat(document.getElementById('uniformAllowance')?.value) +
        safeParseFloat(document.getElementById('laundryAllowance')?.value) +
        safeParseFloat(document.getElementById('medicalAllowance')?.value) +
        safeParseFloat(document.getElementById('medicalAllowanceAdd')?.value);

    let totalOvertime =
        regOt + satOt + sunOt + holOt;

    // GROSS PAY = ALL EARNINGS
    let grossPay =
        basicPay +
        totalOvertime +
        nightPremium +
        taxableOther +
        nonTaxable;

    // ATTENDANCE DEDUCTIONS
    let attendanceDeductions =
        absentAmt + undertimeAmt;

    let netBasicPay =
        basicPay -
        attendanceDeductions +
        nightPremium;

    if (netBasicPay < 0) {
        netBasicPay = 0;
    }

    let sssDed =
        safeParseFloat(
            document.getElementById('sssEE')?.value
        );

    let phicDed =
        safeParseFloat(
            document.getElementById('phicEE')?.value
        );

    let hdmfDed =
        safeParseFloat(
            document.getElementById('hdmfEE')?.value
        );

    let withholdingTax =
        safeParseFloat(
            document.getElementById('wtPayable')?.value
        );

    let dynamicLoanTotal = 0;

    document
        .querySelectorAll('#employeeLoansContainer input')
        .forEach(input => {

            dynamicLoanTotal +=
                safeParseFloat(input.value);

        });

    let totalDeductions =
        attendanceDeductions +
        sssDed +
        phicDed +
        hdmfDed +
        withholdingTax +
        dynamicLoanTotal;

    let netPay =
        grossPay - totalDeductions;

    let empName =
        document.getElementById('empName')?.value || '';

    if (!empName || empName.trim() === '') {
        empName = 'Not Selected';
    }

    let position =
        document.getElementById('empPosition')?.value || 'N/A';

    let pStart =
        document.getElementById('periodStart')?.value || '';

    let pEnd =
        document.getElementById('periodEnd')?.value || '';

    const formatDate = (dateStr) => {

        if (!dateStr) {
            return '';
        }

        let d = new Date(dateStr);

        return d.toLocaleDateString(
            'en-GB',
            {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            }
        );
    };

    return {

        empName,
        position,
        periodStart: pStart,
        periodEnd: pEnd,

        basicPay,
        ratePerHour,
        hoursWorked,

        nightPremium,

        absentAmt,
        undertimeAmt,

        netBasicPay,

        regOt,
        satOt,
        sunOt,
        holOt,

        taxableOther,
        nonTaxable,

        grossPay,

        sssDed,
        phicDed,
        hdmfDed,
        withholdingTax,

        attendanceDeductions,
        totalDeductions,

        netPay,

        formatStart: formatDate(pStart),
        formatEnd: formatDate(pEnd)
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
        regHtml += `<tr><td class="text-left">${row.label}</td><td class="text-left">${row.days}</td><td class="text-right"><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any" style="width:100px;"></td></tr>`;
    });
    document.getElementById('regularEarningsBody').innerHTML = regHtml;

    const taxableRows = [
        { desc: 'Salary Adjustment', id: 'edit_taxAdj', val: d.taxableOther }
    ];
    let taxableLeftHtml = '';
    taxableRows.forEach(row => {
        taxableLeftHtml += `<tr><td class="text-left"><input type="text" value="${row.desc}" style="width:100%; background:#e5e5e5; border:none;"></td><td class="text-right"><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any" style="width:100px;"></td></tr>`;
    });
    document.getElementById('taxableLeftBody').innerHTML = taxableLeftHtml;

    const nonTaxRows = [
        { desc: 'De Minimis Benefit', id: 'edit_nonTaxable', val: d.nonTaxable }
    ];
    let nonTaxHtml = '';
    nonTaxRows.forEach(row => {
        nonTaxHtml += `<tr><td class="text-left"><input type="text" value="${row.desc}" style="width:100%; background:#e5e5e5; border:none;"></td><td class="text-right"><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any" style="width:100px;"></td></tr>`;
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
        dedHtml += `<tr><td class="text-left">${row.label}</td><td class="text-right"><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any" style="width:100px;"></td></tr>`;
    });
    document.getElementById('deductionsBody').innerHTML = dedHtml;

    let loanHtml = '';

    document
        .querySelectorAll(
            '#employeeLoansContainer input'
        )
        .forEach(input => {

            const amount =
                safeParseFloat(input.value);

            if (amount <= 0) {
                return;
            }

            const label =
                input.dataset.loanName || 'Loan';

            const editId =
                `edit_${input.id}`;

            loanHtml += `

                <tr>
                    <td class="text-left">
                        ${label}
                    </td>

                    <td class="text-right">
                        <input
                            type="number"
                            id="${editId}"
                            value="${amount.toFixed(2)}"
                            step="any"
                            style="width:100px;"
                        >
                    </td>
                </tr>
            `;
        });
        document.getElementById('loansBody').innerHTML = loanHtml;

    const allEditIds = [

        'edit_basicPay',
        'edit_ratePerHour',
        'edit_hoursWorked',
        'edit_nightPremium',
        'edit_absentAmt',
        'edit_undertimeAmt',
        'edit_regOt',
        'edit_satOt',
        'edit_sunOt',
        'edit_holOt',

        'edit_taxAdj',
        'edit_nonTaxable',

        'edit_sss',
        'edit_phic',
        'edit_hdmf',
        'edit_wtax'

    ];

    document.querySelectorAll('#loansBody input[type="number"]').forEach(input => {allEditIds.push(input.id);});
    allEditIds.forEach(id => {
        let el = document.getElementById(id);
        if(el) el.addEventListener('input', recalcTotals);
    });
    recalcTotals();
}

function recalcTotals() {
    let basicPayVal          = safeParseFloat(document.getElementById('edit_basicPay')?.value);
    let nightPremium         = safeParseFloat(document.getElementById('edit_nightPremium')?.value);
    let absent               = safeParseFloat(document.getElementById('edit_absentAmt')?.value);
    let undertime            = safeParseFloat(document.getElementById('edit_undertimeAmt')?.value);
    let totalDeductionsFromPay = Math.min(absent + undertime, basicPayVal);
    let netBasicPay          = Math.max(0, basicPayVal - totalDeductionsFromPay + nightPremium);

    let regOt  = safeParseFloat(document.getElementById('edit_regOt')?.value);
    let satOt  = safeParseFloat(document.getElementById('edit_satOt')?.value);
    let sunOt  = safeParseFloat(document.getElementById('edit_sunOt')?.value);
    let holOt  = safeParseFloat(document.getElementById('edit_holOt')?.value);
    let totalOt = regOt + satOt + sunOt + holOt;

    let taxableSum    = safeParseFloat(document.getElementById('edit_taxAdj')?.value);
    let nonTaxableSum = safeParseFloat(document.getElementById('edit_nonTaxable')?.value);
    // Gross = net basic pay + all overtime + other taxable + non-taxable (matches Excel)
    let gross = netBasicPay + totalOt + taxableSum + nonTaxableSum;

    let dedSum = 0;
    [
        'edit_sss',
        'edit_phic',
        'edit_hdmf',
        'edit_wtax'
    ].forEach(id => {

        dedSum +=
            safeParseFloat(
                document.getElementById(id)?.value
            );

    });

    document
        .querySelectorAll(
            '#loansBody input[type="number"]'
        )
        .forEach(input => {

            dedSum +=
                safeParseFloat(input.value);

        });
    let netPay = gross - dedSum;

    document.getElementById('totalGross').value      = gross.toFixed(2);
    document.getElementById('totalDeductions').value = dedSum.toFixed(2);
    document.getElementById('totalNet').value        = netPay.toFixed(2);
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
    recalcTotals();
}
function setNum(id, val) { let el = document.getElementById(id); if(el) el.value = val.toFixed(2); }

// ================= GENERATE PAYSLIP (WITH LOGO) =================
function computePayslipData() {

    let d =
        getComputedPayslipValues();

    return {

        empName: d.empName,
        position: d.position,

        periodStart: d.periodStart,
        periodEnd: d.periodEnd,

        basicPay: d.basicPay,
        ratePerHour: d.ratePerHour,
        hoursWorked: d.hoursWorked,

        nightPremium: d.nightPremium,

        absentAmt: d.absentAmt,
        undertimeAmt: d.undertimeAmt,

        netBasicPay: d.netBasicPay,

        regOt: d.regOt,
        satOt: d.satOt,
        sunOt: d.sunOt,
        holOt: d.holOt,

        taxableOther: d.taxableOther,
        nonTaxable: d.nonTaxable,

        grossPay: d.grossPay,

        sssDed: d.sssDed,
        phicDed: d.phicDed,
        hdmfDed: d.hdmfDed,

        withholdingTax: d.withholdingTax,
        attendanceDeductions: d.attendanceDeductions,

        loanDeductions:
            d.totalDeductions -
            d.attendanceDeductions -
            d.sssDed -
            d.phicDed -
            d.hdmfDed -
            d.withholdingTax,

        totalDeductions: d.totalDeductions,

        netPay: d.netPay,

        formatStart: d.formatStart,
        formatEnd: d.formatEnd
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
                            ${generateDynamicLoanRows()}
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

// Read values from the editable payslip-tab fields (edit_*) so that manual
// overrides on the Payslip tab are honoured when the payslip is generated.
function getEditedPayslipValues() {
    const sp = (id) => safeParseFloat(document.getElementById(id)?.value);
    const sv = (id) => document.getElementById(id)?.value || '';

    const basicPay      = sp('edit_basicPay');
    const ratePerHour   = sp('edit_ratePerHour');
    const hoursWorked   = sp('edit_hoursWorked');
    const nightPremium  = sp('edit_nightPremium');
    const absentAmt     = sp('edit_absentAmt');
    const undertimeAmt  = sp('edit_undertimeAmt');
    const regOt         = sp('edit_regOt');
    const satOt         = sp('edit_satOt');
    const sunOt         = sp('edit_sunOt');
    const holOt         = sp('edit_holOt');
    const taxableOther  = sp('edit_taxAdj');
    const nonTaxable    = sp('edit_nonTaxable');
    const sssDed        = sp('edit_sss');
    const phicDed       = sp('edit_phic');
    const hdmfDed       = sp('edit_hdmf');
    const withholdingTax= sp('edit_wtax');
    const sssLoan       = sp('edit_sssLoan');
    const calamityLoan  = sp('edit_calamityLoan');
    const salaryAdvance = sp('edit_advSalary');

    const totalDeductionsFromPay = Math.min(absentAmt + undertimeAmt, basicPay);
    const netBasicPay   = Math.max(0, basicPay - totalDeductionsFromPay + nightPremium);
    const totalOvertime = regOt + satOt + sunOt + holOt;
    const grossPay      = netBasicPay + totalOvertime + taxableOther + nonTaxable;
    const totalDeductions = sssDed + phicDed + hdmfDed + withholdingTax + sssLoan + calamityLoan + salaryAdvance;
    const netPay        = grossPay - totalDeductions;

    const empName  = sv('payslipEmpName')  || sv('empName')     || 'Not Selected';
    const position = sv('payslipPosition') || sv('empPosition') || 'N/A';
    const pStart   = sv('payslipPeriodStart') || sv('periodStart') || '';
    const pEnd     = sv('payslipPeriodEnd')   || sv('periodEnd')   || '';

    const formatDate = (s) => {
        if (!s) return '';
        const d = new Date(s);
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    };

    return {
        empName, position, periodStart: pStart, periodEnd: pEnd,
        basicPay, ratePerHour, hoursWorked, nightPremium, absentAmt, undertimeAmt,
        netBasicPay, regOt, satOt, sunOt, holOt, taxableOther, nonTaxable,
        grossPay, sssDed, phicDed, hdmfDed, withholdingTax,
        sssLoan, calamityLoan, salaryAdvance, totalDeductions, netPay,
        formatStart: formatDate(pStart), formatEnd: formatDate(pEnd)
    };
}

function generateStaticPayslip() {
    // Use the payslip tab's editable fields if populated (user may have adjusted values);
    // fall back to raw-sheet fields when the payslip tab hasn't been visited yet.
    const editEl = document.getElementById('edit_basicPay');
    const d = (editEl && editEl.value !== '' && editEl.value !== '0.00')
        ? getEditedPayslipValues()
        : computePayslipData();
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
        monthly_salary: safeParseFloat(document.getElementById('monthlySalary')?.value),
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
        loan_deductions: getDynamicLoanPayload(),
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
            gross_pay:       d.grossPay,
            sss_ee:          d.sssDed,
            phic_ee:         d.phicDed,
            hdmf_ee:         d.hdmfDed,
            withholding_tax: d.withholdingTax,
            loan_deductions: getDynamicLoanPayload(),
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

function getDynamicLoanPayload() {

    const loans = [];

    document
        .querySelectorAll(
            '#employeeLoansContainer input'
        )
        .forEach(input => {

            const amount =
                safeParseFloat(input.value);

            if (amount <= 0) {
                return;
            }

            loans.push({

                loan_type: input.id,

                amount: amount

            });

        });

    return loans;

}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, m => m === '&' ? '&amp;' : (m === '<' ? '&lt;' : '&gt;'));
}

function generateDynamicLoanRows() {

    let rows = '';

    document
        .querySelectorAll(
            '#employeeLoansContainer input'
        )
        .forEach(input => {

            const amount =
                safeParseFloat(input.value);

            if (amount <= 0) {
                return;
            }

            const label =
                input.dataset.loanName || 'Loan';

            rows += `
                <tr>
                    <td class="text-left">
                        ${label}
                    </td>
                    <td class="text-right">
                        ${amount.toFixed(2)}
                    </td>
                </tr>
            `;
        });
    return rows;
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
        gross_pay: window.currentPayslipData.grossPay,
        sss_ee: window.currentPayslipData.sssDed,
        phic_ee: window.currentPayslipData.phicDed,
        hdmf_ee: window.currentPayslipData.hdmfDed,
        withholding_tax: window.currentPayslipData.withholdingTax,
        loan_deductions: getDynamicLoanPayload(),
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
    const g = id => document.getElementById(id)?.value ?? '';
    const fields = {
        employee_id:          employeeId,
        employee_name:        g('empName'),
        position:             g('empPosition'),
        period_start:         g('periodStart'),
        period_end:           g('periodEnd'),
        monthly_salary:       g('monthlySalary'),
        semi_monthly_salary:  g('semiMonthlySalary'),
        daily_rate:           g('dailyRate'),
        hourly_rate:          g('hourlyRate'),
        year_end_gifts:       g('yearEndGifts'),
        rice_subsidy:         g('riceSubsidy'),
        uniform_allowance:    g('uniformAllowance'),
        laundry_allowance:    g('laundryAllowance'),
        medical_allowance:    g('medicalAllowance'),
        medical_allowance_add:g('medicalAllowanceAdd'),
        salary_adjustment:    g('salaryAdjustment'),
        override_amount:      g('overrideAmount'),
        ned_amount:           g('nedAmount'),
        holiday_pay:          g('holidayPay'),
        gross_comp:           g('grossComp'),
        loan_deductions:      JSON.stringify(getDynamicLoanPayload()),
        ut_hours:             g('utHours'),
        ut_php:               g('utPhp'),
        absent_days:          g('absentDays'),
        absent_php:           g('absentPhp'),
        workday_ot_hours:     g('workdayOtHours'),
        workday_ot_php:       g('workdayOtPhp'),
        night_diff_days:      g('workdayNightDiffDays'),
        night_diff_php:       g('workdayNightDiffPhp'),
        sat_ot_hours:         g('satOtHours'),
        sat_ot_php:           g('satOtPhp'),
        sun_ot_hours:         g('sunOtHours'),
        sun_ot_php:           g('sunOtPhp'),
        hol_ot_php:           g('holOtPhp'),
        sss_ee:               g('sssEE'),
        sss_wisp:             g('sssWisp'),
        sss_total:            g('sssTotal'),
        phic_ee:              g('phicEE'),
        hdmf_ee:              g('hdmfEE'),
        wt_payable:           g('wtPayable'),
    };
    const esc = v => '"' + String(v).replace(/"/g, '""') + '"';
    const csvRows = [
        Object.keys(fields).join(','),
        Object.values(fields).map(esc).join(','),
    ];
    const BOM = '﻿';
    const blob = new Blob([BOM + csvRows.join('\n')], {type: 'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `raw_sheet_${employeeId}_${new Date().toISOString().slice(0,19).replace(/:/g,'-')}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}

// ================= EXPORT REGISTER (CSV) =================
function exportRegister() {
    const employeeId = document.getElementById('selectedEmployeeId').value;
    if (!employeeId) {
        alert('Please select an employee first, then fill in the register fields before exporting.');
        return;
    }
    const g = id => document.getElementById(id)?.value ?? '';
    const fields = {
        employee_id:               employeeId,
        employee_name:             g('empName'),
        period_start:              g('periodStart'),
        period_end:                g('periodEnd'),
        monthly_basic_salary:      g('monthlyBasicSalary'),
        semi_monthly_basic_salary: g('semiMonthlyBasicSalary'),
        de_min_year_end:           g('deMinYearEnd'),
        de_min_rice_subsidy:       g('deMinRiceSubsidy'),
        de_min_uniform:            g('deMinUniform'),
        de_min_laundry:            g('deMinLaundry'),
        de_min_medical:            g('deMinMedical'),
        other_non_taxable:         g('otherNonTaxable'),
        salary_adjustment:         g('salaryAdjustmentReg'),
        overtime:                  g('overtimeReg'),
        night_diff:                g('ndReg'),
        absences_tardiness:        g('absencesTardiness'),
        holiday_pay:               g('holidayPayReg'),
        gross_compensation:        g('grossCompReg'),
        gross_taxable_comp:        g('grossTaxableComp'),
        gross_non_taxable_comp:    g('grossNonTaxableComp'),
        sss_ee:                    g('sssEE'),
        sss_wisp:                  g('sssWisp'),
        sss_total:                 g('sssTotal'),
        phic_ee:                   g('phicEE'),
        hdmf_ee:                   g('hdmfEE'),
        reg_net_taxable:           g('regNetTaxable'),
        net_tax_prev_cutoff:       g('netTaxProv'),
        monthly_taxable_comp:      g('monthlyTaxableComp'),
        wt_bracket:                g('wtBracket'),
        wt_basic:                  g('wtBasic'),
        wt_percentage:             g('wtPercentage'),
        wt_excess:                 g('wtExcess'),
        wt_paid_last:              g('wtPaidLast'),
        wt_payable:                g('wtPayable'),
        advance_to_employees:      g('advWorkingLeave'),
        non_taxable_comp:          g('advNonTaxable'),
        net_pay:                   g('netPayRegister'),
        monthly_gross_comp:        g('monthlyGrossComp'),
        sss_contribution:          g('sssContribution'),
        phic_contribution:         g('phicContribution'),
        hdmf_contribution:         g('hdmfContribution'),
    };
    const esc = v => '"' + String(v).replace(/"/g, '""') + '"';
    const csvRows = [
        Object.keys(fields).join(','),
        Object.values(fields).map(esc).join(','),
    ];
    const BOM = '﻿';
    const empSlug = (g('empName') || employeeId).replace(/[^a-z0-9]/gi, '_');
    const period  = (g('periodStart') || new Date().toISOString().slice(0,10)).replace(/-/g, '');
    const blob = new Blob([BOM + csvRows.join('\n')], {type: 'text/csv;charset=utf-8;'});
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = `payroll_register_${empSlug}_${period}.csv`;
    a.click();
    URL.revokeObjectURL(url);
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
            alert(result.message || 'Timesheet imported successfully.');
            closeImportModal();
            document.getElementById('importTimesheetForm').reset();
        } else {
            alert('Import failed: ' + (result.error || result.message || 'Unknown error'));
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

// ── Year-End → Payroll bridge ─────────────────────────────────────────────
// Called by payroll_13th_month.php when the user clicks "Apply to Payroll".
// type: '13th' | 'annualization'
// payload: { empId, empName, year, amount, isRefund (annualization only) }
window.applyYearEndToPayroll = async function (type, payload) {
    const { empId, empName, year, amount } = payload;

    // 1. Switch inner raw-sheet sub-tab to Raw
    document.querySelectorAll('.sub-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(p => p.classList.remove('active'));
    const rawTab = document.querySelector('.sub-tab[data-tab="raw"]');
    if (rawTab) rawTab.classList.add('active');
    const rawPanel = document.getElementById('raw');
    if (rawPanel) rawPanel.classList.add('active');

    // 2. Set employee
    const empInput  = document.getElementById('empSearchInput');
    const empHidden = document.getElementById('selectedEmployeeId');
    const empNameEl = document.getElementById('empName');
    if (empInput)  empInput.value  = empName;
    if (empHidden) empHidden.value = empId;
    if (empNameEl) empNameEl.value = empName;
    if (typeof selectedEmployeeIds !== 'undefined') {
        selectedEmployeeIds.clear();
        selectedEmployeeIds.add(String(empId));
    }

    // 3. Set period to Dec 16–31 of the selected year
    const periodStartEl = document.getElementById('periodStart');
    const periodEndEl   = document.getElementById('periodEnd');
    if (periodStartEl) periodStartEl.value = `${year}-12-16`;
    if (periodEndEl)   periodEndEl.value   = `${year}-12-31`;

    // 4. Auto-fill salary from employee record
    if (typeof fillEmployeeData === 'function') {
        await fillEmployeeData(String(empId));
    }

    // 5. Pre-fill the relevant field and show banner
    let bannerMsg = '';

    if (type === '13th') {
        const giftsEl = document.getElementById('yearEndGifts');
        if (giftsEl) giftsEl.value = amount.toFixed(2);
        // Mirror to register field
        const regEl = document.getElementById('deMinYearEnd');
        if (regEl) regEl.value = amount.toFixed(2);
        bannerMsg = `13th month pay of <strong>₱${amount.toLocaleString('en-PH',{minimumFractionDigits:2})}</strong> `
                  + `pre-filled into <em>Year End Gifts</em> for <strong>${empName}</strong> (${year}).`;
    } else {
        const isRefund = payload.isRefund;
        if (isRefund) {
            // Negative adjustment = refund → add to salary adjustment (earnings side)
            const adjEl = document.getElementById('salaryAdjustment');
            if (adjEl) adjEl.value = Math.abs(amount).toFixed(2);
            bannerMsg = `Year-end tax refund of <strong>₱${Math.abs(amount).toLocaleString('en-PH',{minimumFractionDigits:2})}</strong> `
                      + `pre-filled into <em>Salary Adjustment</em> for <strong>${empName}</strong> (${year}).`;
        } else {
            // Positive adjustment = additional withholding → salary advance (deduction side)
            const advEl = document.getElementById('advSalary');
            if (advEl) advEl.value = amount.toFixed(2);
            bannerMsg = `Year-end additional withholding of <strong>₱${amount.toLocaleString('en-PH',{minimumFractionDigits:2})}</strong> `
                      + `pre-filled into <em>Salary Advance</em> for <strong>${empName}</strong> (${year}). `
                      + `Verify before generating payslip.`;
        }
    }

    // Show banner
    const banner = document.getElementById('yearEndPrefillBanner');
    const msg    = document.getElementById('yearEndBannerMsg');
    if (banner && msg) {
        msg.innerHTML = bannerMsg;
        banner.style.display = 'flex';
    }

    // Scroll to top of raw sheet
    const rawScroll = document.querySelector('#raw.payroll-scroll-area');
    if (rawScroll) rawScroll.scrollTop = 0;
};
</script>
</body>
</html>