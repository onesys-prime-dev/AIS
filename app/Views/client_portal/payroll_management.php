<?php
// Fetch company logo from database
$companyBranding = model(\App\Models\Client\CompanyBrandingModel::class)
    ->where('company_id', 1)
    ->first();
$companyLogoUrl = $companyBranding && !empty($companyBranding['logo_path'])
    ? base_url($companyBranding['logo_path'])
    : '';

// Compute default period dates for the Raw Sheet
$_psModel  = new \App\Models\Client\PayrollSettingsModel();
$_cutoff   = (int)(($_psModel->getSetting('first_half_cutoff')) ?? 15);
$_today    = new DateTime();
$_day      = (int)$_today->format('d');
$_year     = $_today->format('Y');
$_month    = $_today->format('m');
$_lastDay  = (int)$_today->format('t');
if ($_day <= $_cutoff) {
    $_defaultPeriod = 'first_half';
    $_defaultStart  = "{$_year}-{$_month}-01";
    $_defaultEnd    = "{$_year}-{$_month}-" . str_pad($_cutoff, 2, '0', STR_PAD_LEFT);
} else {
    $_defaultPeriod = 'second_half';
    $_defaultStart  = "{$_year}-{$_month}-" . str_pad($_cutoff + 1, 2, '0', STR_PAD_LEFT);
    $_defaultEnd    = "{$_year}-{$_month}-{$_lastDay}";
}
$_defaultYearMonth = "{$_year}-{$_month}";
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

    .section{ margin-bottom:20px; }

    .subtitle{
        font-size:12px;
        color:#888;
    }

    .grid-2{ display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .grid-3{ display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; }
    .grid-4{ display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:14px; }
    .grid-5{ display:grid; grid-template-columns:1fr 1fr 1fr 1fr 1fr; gap:10px; }

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

    /* Period selector buttons */
.period-btn-group {
    display: flex;
    gap: 8px;
    margin-top: 4px;
}
.period-btn {
    flex: 1;
    padding: 9px 8px 7px;
    border: 2px solid #d1d5db;
    border-radius: 8px;
    background: #fff;
    font-size: 12px;
    font-weight: 600;
    color: #555;
    cursor: pointer;
    text-align: center;
    transition: border-color .15s, background .15s, color .15s;
    line-height: 1.2;
}
.period-btn:hover {
    border-color: #7b5cff;
    color: #7b5cff;
    background: #f5f3ff;
}
.period-btn.active {
    border-color: #7b5cff;
    background: #7b5cff;
    color: #fff;
}
.period-btn-sub {
    display: block;
    font-size: 10px;
    font-weight: 400;
    opacity: .85;
    margin-top: 2px;
}
.period-resolved {
    font-size: 11px;
    color: #666;
    margin-top: 6px;
    padding: 4px 8px;
    background: #f0f2f8;
    border-radius: 4px;
    display: inline-block;
}

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
        gap: 12px;
        margin-bottom: 12px;
    }
    .button-area {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 10px;
        margin-bottom: 20px;
    }
    .btn-purple { background: #7b5cff; }
    .btn-red { background: #ff3b3b; }
    .card-container {
        display: grid;
        grid-template-columns: 1.8fr 1fr 1fr;
        gap: 12px;
    }
    .bottom-cards {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-top: 12px;
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
        padding: 0;
        border: 1px solid #d0d0d8;
        width: 950px;
        max-width: 95%;
        border-radius: 10px;
        position: relative;
        max-height: 92vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    #staticPayslipModal .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        border-bottom: 1px solid #e5e7eb;
        flex: 0 0 auto;
        background: #f8f9fc;
        border-radius: 10px 10px 0 0;
    }
    #staticPayslipModal .modal-header h3 {
        font-size: 15px;
        font-weight: 700;
        color: #1a1a2e;
        margin: 0;
    }
    #staticPayslipContent {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 20px;
    }
    #staticPayslipModal .modal-footer {
        padding: 14px 20px;
        border-top: 1px solid #e5e7eb;
        text-align: center;
        flex: 0 0 auto;
        background: #f8f9fc;
        border-radius: 0 0 10px 10px;
    }
    #staticPayslipModal .close-modal {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        font-size: 20px;
        font-weight: bold;
        cursor: pointer;
        color: #666;
        background: #e9eaf0;
        line-height: 1;
        border: none;
        transition: background 0.15s, color 0.15s;
    }
    #staticPayslipModal .close-modal:hover {
        background: #dc2626;
        color: #fff;
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

/* ─── LAYOUT & UI IMPROVEMENTS ─── */
/* Raw-sheet & register section cards */
.rs-card {
    background: #f9fafc;
    border: 1px solid #e2e4ef;
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 14px;
}
.rs-card h3 {
    font-size: 13px;
    font-weight: 700;
    color: #3a3a5c;
    margin-bottom: 10px;
    padding-bottom: 6px;
    border-bottom: 1px solid #e5e5ee;
}
.rs-card .subtitle { margin-top: -6px; margin-bottom: 8px; }

/* ── OT compact 2×2 grid ── */
.ot-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.ot-card {
    background: #fff;
    border: 1px solid #dde1ee;
    border-radius: 6px;
    padding: 10px 12px;
}
.ot-card-title {
    font-size: 12px;
    font-weight: 700;
    color: #3a3a5c;
    margin-bottom: 7px;
    padding-bottom: 4px;
    border-bottom: 1px solid #eee;
}
.ot-field-row {
    display: grid;
    grid-template-columns: 1fr 88px 106px;
    align-items: start;
    gap: 5px;
    margin-bottom: 7px;
}
.ot-field-row label { font-size: 11.5px; margin: 0; color: #444; line-height: 1.3; padding-top: 2px; }
.ot-field-row input { height: 30px; font-size: 12px; padding: 3px 6px; }
.ot-rate-hint { font-size: 10px; color: #999; display: block; margin-top: 1px; font-style: italic; }
.ot-col-hdrs {
    display: grid;
    grid-template-columns: 1fr 88px 106px;
    gap: 5px;
    margin-bottom: 4px;
    padding-bottom: 3px;
    border-bottom: 1px dashed #e5e5f0;
}
.ot-col-hdrs span { font-size: 10px; font-weight: 600; color: #999; text-align: center; }
.ot-col-hdrs span:first-child { text-align: left; }

/* Payslip tab cards */
.card-container { gap: 12px; }
.bottom-cards { gap: 12px; }
.top-grid { gap: 12px; }
.totals-card { padding: 10px 16px; gap: 10px; }

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
                    <p class="subtitle">Employee &amp; payroll period</p>
                    <div class="grid-2" style="margin-bottom:12px;">
                        <div>
                            <label>Position</label>
                            <input type="text" id="empPosition" placeholder="e.g. Service Desk Analyst L-1">
                        </div>
                        <div>
                            <label>Month</label>
                            <input type="month" id="periodMonth" value="<?= $_defaultYearMonth ?>"
                                   style="height:40px;padding:6px 8px;border:1px solid #cfcfcf;border-radius:8px;font-size:13px;background:#e9e9e9;width:100%;"
                                   onchange="resolvePeriodDates()">
                        </div>
                    </div>
                    <div>
                        <label>Pay Period</label>
                        <div class="period-btn-group">
                            <button type="button" class="period-btn <?= $_defaultPeriod === 'first_half'  ? 'active' : '' ?>" data-period="first_half"  onclick="selectPeriodBtn(this)">
                                1st Half<span class="period-btn-sub" id="lbl_first_half"></span>
                            </button>
                            <button type="button" class="period-btn <?= $_defaultPeriod === 'second_half' ? 'active' : '' ?>" data-period="second_half" onclick="selectPeriodBtn(this)">
                                2nd Half<span class="period-btn-sub" id="lbl_second_half"></span>
                            </button>
                            <button type="button" class="period-btn" data-period="monthly" onclick="selectPeriodBtn(this)">
                                Monthly<span class="period-btn-sub" id="lbl_monthly"></span>
                            </button>
                        </div>
                        <div id="resolvedPeriodDisplay" class="period-resolved"></div>
                    </div>
                    <!-- Hidden inputs used by all existing API/compute code -->
                    <input type="hidden" id="periodStart" value="<?= $_defaultStart ?>">
                    <input type="hidden" id="periodEnd"   value="<?= $_defaultEnd ?>">
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
            <div class="section rs-card">
                <h3 class="font-semibold">De Minimis Benefit</h3>
                <div class="grid-3">
                    <div><label>Year End Gifts</label><input type="number" id="yearEndGifts" value="0"></div>
                    <div><label>Rice Subsidy</label><input type="number" id="riceSubsidy" value="0"></div>
                    <div><label>Uniform / Cleaning Allowance</label><input type="number" id="uniformAllowance" value="0"></div>
                    <div><label>Laundry Allowance</label><input type="number" id="laundryAllowance" value="0"></div>
                    <div><label>Medical Allowance</label><input type="number" id="medicalAllowance" value="0"></div>
                    <div><label>Medical Allowance (Additional)</label><input type="number" id="medicalAllowanceAdd" value="0"></div>
                </div>
            </div>

            <!-- OTHER NON-SOCIAL COMPENSATION -->
            <div class="section rs-card">
                <h3 class="font-semibold">Other Non-Social Compensation</h3>
                <div class="grid-3">
                    <div><label>Salary Adjustment</label><input type="number" id="salaryAdjustment" value="0"></div>
                    <div><label>Override</label><input type="number" id="overrideAmount" value="0"></div>
                    <div><label>NED</label><input type="number" id="nedAmount" value="0"></div>
                    <div><label>Gross Compensation</label><input type="number" id="grossComp" value="0"></div>
                </div>
                <div class="grid-2" style="margin-top:10px;">
                    <div>
                        <label>Reg Holiday Hours</label>
                        <input type="number" id="regHolHours" value="0" oninput="calcHolPay()">
                        <label class="mt-2">Reg Holiday Pay (2.0&times;)</label>
                        <input type="number" id="regHolPay" value="0" oninput="updateHolidayTotal()">
                    </div>
                    <div>
                        <label>Spec Holiday Hours</label>
                        <input type="number" id="specHolHours" value="0" oninput="calcHolPay()">
                        <label class="mt-2">Spec Holiday Pay (1.3&times;)</label>
                        <input type="number" id="specHolPay" value="0" oninput="updateHolidayTotal()">
                    </div>
                </div>
                <input type="hidden" id="holidayPay" value="0">
            </div>

            <!-- LOANS + ADVANCES & UNDERTIME / ABSENT — side by side -->
            <div class="grid-2 section">
                <div class="rs-card">
                    <h3 class="font-semibold">Loans &amp; Advances</h3>
                    <div class="grid-2">
                        <div><label>SSS Salary Loan</label><input type="number" id="sssSalaryLoan" value="0"></div>
                        <div><label>Calamity Loan</label><input type="number" id="sssCalamityLoan" value="0"></div>
                        <div><label>Salary Advance</label><input type="number" id="advSalary" value="0"></div>
                        <div><label>Calamity Advance</label><input type="number" id="advCalamity" value="0"></div>
                    </div>
                </div>
                <div class="rs-card">
                    <h3 class="font-semibold">Undertime &amp; Absences</h3>
                    <div class="grid-2">
                        <div><label>Undertime Hours</label><input type="number" id="utHours" value="0"></div>
                        <div><label>Undertime (PHP)</label><input type="number" id="utPhp" value="0"></div>
                        <div><label>Absent Day(s)</label><input type="number" id="absentDays" value="0"></div>
                        <div><label>Absent (PHP)</label><input type="number" id="absentPhp" value="0"></div>
                    </div>
                </div>
            </div>

            <!-- OVERTIME & NIGHT DIFFERENTIAL — 2×2 compact grid -->
            <div class="section">
                <h3 class="font-semibold mb-2">Overtime &amp; Night Differential</h3>
                <div class="ot-grid">

                    <!-- Regular Workday -->
                    <div class="ot-card">
                        <div class="ot-card-title">Workday OT <span style="font-weight:400;color:#777;font-size:11px;">Hourly rate &times; 125%</span></div>
                        <div class="ot-col-hdrs"><span>Description</span><span>Hours</span><span>PHP Amount</span></div>
                        <div class="ot-field-row">
                            <label>Night Differential (basic)
                                <span class="ot-rate-hint">Hourly rate &times; 10% &mdash; for basic/regular night hrs</span>
                            </label>
                            <input type="number" id="workdayNightDiffDays" value="0" oninput="calcRawNightPay('workdayNightDiffDays','workdayNightDiffPhp')">
                            <input type="number" id="workdayNightDiffPhp" value="0">
                        </div>
                        <div class="ot-field-row">
                            <label>Overtime &le;10 hrs
                                <span class="ot-rate-hint">Hourly rate &times; 125% &times; hrs worked</span>
                            </label>
                            <input type="number" id="workdayOtHours" value="0" oninput="calcRawOtPay('workdayOtHours','workdayOtPhp','regOt')">
                            <input type="number" id="workdayOtPhp" value="0">
                        </div>
                        <div class="ot-field-row">
                            <label>Overtime &gt;10 hrs
                                <span class="ot-rate-hint">Hourly rate &times; 125% &times; hrs past 10</span>
                            </label>
                            <input type="number" id="regOtGt10Hours" value="0" oninput="calcRawOtPay('regOtGt10Hours','regOtGt10Php','regOt')">
                            <input type="number" id="regOtGt10Php" value="0">
                        </div>
                        <div class="ot-field-row">
                            <label>OT Night Premium
                                <span class="ot-rate-hint">OT hrs during night shift &times; 10% on top of 125%</span>
                            </label>
                            <input type="number" id="regOtNightHours" value="0" oninput="calcRawNightPay('regOtNightHours','regOtNightPhp')">
                            <input type="number" id="regOtNightPhp" value="0">
                        </div>
                    </div>

                    <!-- Sat / Rest-Day -->
                    <div class="ot-card">
                        <div class="ot-card-title">Sat / Rest-Day OT <span style="font-weight:400;color:#777;font-size:11px;">Hourly rate &times; 130% &times; 130% = 169%</span></div>
                        <div class="ot-col-hdrs"><span>Description</span><span>Hours</span><span>PHP Amount</span></div>
                        <div class="ot-field-row">
                            <label>Overtime &le;10 hrs
                                <span class="ot-rate-hint">Hourly rate &times; 169% &times; hrs worked</span>
                            </label>
                            <input type="number" id="satOtHours" value="0" oninput="calcRawOtPay('satOtHours','satOtPhp','restOt')">
                            <input type="number" id="satOtPhp" value="0">
                        </div>
                        <div class="ot-field-row">
                            <label>Overtime &gt;10 hrs
                                <span class="ot-rate-hint">Hourly rate &times; 169% &times; hrs past 10</span>
                            </label>
                            <input type="number" id="satOtGt10Hours" value="0" oninput="calcRawOtPay('satOtGt10Hours','satOtGt10Php','restOt')">
                            <input type="number" id="satOtGt10Php" value="0">
                        </div>
                        <div class="ot-field-row">
                            <label>Night Premium
                                <span class="ot-rate-hint">Hrs during night &times; 10% on top of rest-day rate</span>
                            </label>
                            <input type="number" id="satNightDiffHours" value="0" oninput="calcRawNightPay('satNightDiffHours','satNightDiffPhp')">
                            <input type="number" id="satNightDiffPhp" value="0">
                        </div>
                    </div>

                    <!-- Sun / Special Holiday -->
                    <div class="ot-card">
                        <div class="ot-card-title">Sun / Special Holiday OT <span style="font-weight:400;color:#777;font-size:11px;">Hourly rate &times; 169%</span></div>
                        <div class="ot-col-hdrs"><span>Description</span><span>Hours</span><span>PHP Amount</span></div>
                        <div class="ot-field-row">
                            <label>Overtime &le;10 hrs
                                <span class="ot-rate-hint">Hourly rate &times; 169% &times; hrs worked</span>
                            </label>
                            <input type="number" id="sunOtHours" value="0" oninput="calcRawOtPay('sunOtHours','sunOtPhp','restOt')">
                            <input type="number" id="sunOtPhp" value="0">
                        </div>
                        <div class="ot-field-row">
                            <label>Overtime &gt;10 hrs
                                <span class="ot-rate-hint">Hourly rate &times; 169% &times; hrs past 10</span>
                            </label>
                            <input type="number" id="sunOtGt10Hours" value="0" oninput="calcRawOtPay('sunOtGt10Hours','sunOtGt10Php','restOt')">
                            <input type="number" id="sunOtGt10Php" value="0">
                        </div>
                        <div class="ot-field-row">
                            <label>Night Premium
                                <span class="ot-rate-hint">Hrs during night &times; 10% on top of 169% rate</span>
                            </label>
                            <input type="number" id="sunNightDiffHours" value="0" oninput="calcRawNightPay('sunNightDiffHours','sunNightDiffPhp')">
                            <input type="number" id="sunNightDiffPhp" value="0">
                        </div>
                    </div>

                    <!-- Regular Holiday -->
                    <div class="ot-card">
                        <div class="ot-card-title">Regular Holiday OT <span style="font-weight:400;color:#777;font-size:11px;">Hourly rate &times; 200% &times; 130% = 260%</span></div>
                        <div class="ot-col-hdrs"><span>Description</span><span>Hours</span><span>PHP Amount</span></div>
                        <div class="ot-field-row">
                            <label>Overtime &le;10 hrs
                                <span class="ot-rate-hint">Hourly rate &times; 260% &times; hrs worked</span>
                            </label>
                            <input type="number" id="holOtHours" value="0" oninput="calcRawOtPay('holOtHours','holOtPhp','holOt')">
                            <input type="number" id="holOtPhp" value="0">
                        </div>
                        <div class="ot-field-row">
                            <label>Overtime &gt;10 hrs
                                <span class="ot-rate-hint">Hourly rate &times; 260% &times; hrs past 10</span>
                            </label>
                            <input type="number" id="holOtGt10Hours" value="0" oninput="calcRawOtPay('holOtGt10Hours','holOtGt10Php','holOt')">
                            <input type="number" id="holOtGt10Php" value="0">
                        </div>
                        <div class="ot-field-row">
                            <label>Night Premium
                                <span class="ot-rate-hint">Hrs during night &times; 10% on top of 260% rate</span>
                            </label>
                            <input type="number" id="holNightDiffHours" value="0" oninput="calcRawNightPay('holNightDiffHours','holNightDiffPhp')">
                            <input type="number" id="holNightDiffPhp" value="0">
                        </div>
                    </div>

                </div>
            </div>
            <div class="section rs-card">
                <h3 class="font-semibold">VL Cash Conversion <small style="font-weight:normal;color:#666;font-size:11px;">(Non-Taxable &mdash; Daily Rate = Monthly &times; 12 &divide; 261)</small></h3>
                <div class="grid-3">
                    <div><label>VL Days</label><input type="number" id="vlDaysRaw" value="0" min="0" step="any" oninput="calcRawVLCash()"></div>
                    <div><label>Daily Rate (auto)</label><input type="number" id="vlDailyRateRaw" value="0" readonly style="background:#f0f0f0;"></div>
                    <div><label>Amount (PHP)</label><input type="number" id="vlCashPhp" value="0" min="0" step="any"></div>
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
            <div style="max-width:100%;">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-semibold text-lg">Payroll Register</h3>
                    <button type="button" class="next-btn" id="exportRegisterBtn" style="background:#28a745;">Export Register (CSV)</button>
                </div>

                <!-- Employee & Salary -->
                <div class="rs-card">
                    <h3 class="font-semibold">Employee &amp; Salary</h3>
                    <div class="grid-3">
                        <div><label>Search Employee</label><input type="text" id="searchEmployee" placeholder="Search..."></div>
                        <div><label>Monthly Basic Salary</label><input type="number" id="monthlyBasicSalary" value="0"></div>
                        <div><label>Semi-Monthly Basic Salary</label><input type="number" id="semiMonthlyBasicSalary" value="0"></div>
                    </div>
                </div>

                <!-- De Minimis -->
                <div class="rs-card">
                    <h3 class="font-semibold">De Minimis Benefit</h3>
                    <div class="grid-3">
                        <div><label>Year End / 13th Month</label><input type="number" id="deMinYearEnd" value="0"></div>
                        <div><label>Rice Subsidy</label><input type="number" id="deMinRiceSubsidy" value="0"></div>
                        <div><label>Uniform / Clothing Allow.</label><input type="number" id="deMinUniform" value="0"></div>
                        <div><label>Laundry Allowance</label><input type="number" id="deMinLaundry" value="0"></div>
                        <div><label>Medical Allowance</label><input type="number" id="deMinMedical" value="0"></div>
                    </div>
                </div>

                <!-- Earnings Summary -->
                <div class="rs-card">
                    <h3 class="font-semibold">Earnings Summary</h3>
                    <div class="grid-3">
                        <div><label>Other Non-Taxable Comp.</label><input type="number" id="otherNonTaxable" value="0"></div>
                        <div><label>Salary Adjustment</label><input type="number" id="salaryAdjustmentReg" value="0"></div>
                        <div><label>Overtime</label><input type="number" id="overtimeReg" value="0"></div>
                        <div><label>Night Differential</label><input type="number" id="ndReg" value="0"></div>
                        <div><label>Absences / Tardiness</label><input type="number" id="absencesTardiness" value="0"></div>
                        <div><label>Holiday Pay</label><input type="number" id="holidayPayReg" value="0"></div>
                        <div><label>Gross Compensation</label><input type="number" id="grossCompReg" value="0"></div>
                        <div><label>Gross Taxable Compensation</label><input type="number" id="grossTaxableComp" value="0"></div>
                        <div><label>Gross Non-Taxable Comp.</label><input type="number" id="grossNonTaxableComp" value="0"></div>
                    </div>
                </div>

                <!-- Employee's Share (Government) -->
                <div class="rs-card">
                    <h3 class="font-semibold">Employee's Government Contributions</h3>
                    <div class="grid-3">
                        <div><label>SSS &mdash; Employee Share</label><input type="number" id="sssEE" value="0"></div>
                        <div><label>SSS &mdash; WISP</label><input type="number" id="sssWisp" value="0"></div>
                        <div><label>SSS &mdash; Total</label><input type="number" id="sssTotal" value="0"></div>
                        <div><label>PhilHealth (PHIC)</label><input type="number" id="phicEE" value="0"></div>
                        <div><label>Pag-IBIG (HDMF)</label><input type="number" id="hdmfEE" value="0"></div>
                    </div>
                    <div class="grid-3" style="margin-top:10px;">
                        <div><label>Regular Net Taxable Comp.</label><input type="number" id="regNetTaxable" value="0"></div>
                        <div><label>Net Taxable Comp. Prev. Cut-Off</label><input type="number" id="netTaxProv" value="0"></div>
                        <div><label>Monthly Taxable Compensation</label><input type="number" id="monthlyTaxableComp" value="0"></div>
                    </div>
                </div>

                <!-- Withholding Tax -->
                <div class="rs-card">
                    <h3 class="font-semibold">Withholding Tax</h3>
                    <div class="grid-3">
                        <div><label>Tax Bracket</label><input type="text" id="wtBracket" placeholder="---"></div>
                        <div><label>Base Amount</label><input type="number" id="wtBasic" value="0"></div>
                        <div><label>Rate (%)</label><input type="text" id="wtPercentage" placeholder="---"></div>
                        <div><label>Fixed Tax</label><input type="number" id="wtBasic2" value="0"></div>
                        <div><label>Excess Over Bracket</label><input type="number" id="wtExcess" value="0"></div>
                    </div>
                    <div class="grid-2" style="margin-top:10px;">
                        <div><label>WT Paid Last Cut-Off</label><input type="number" id="wtPaidLast" value="0"></div>
                        <div><label>WT Payable This Period</label><input type="number" id="wtPayable" value="0"></div>
                    </div>
                </div>

                <!-- Loans, Advances & Totals -->
                <div class="rs-card">
                    <h3 class="font-semibold">Loans, Advances &amp; Totals</h3>
                    <div class="grid-3">
                        <div><label>SSS Salary Loan</label><input type="number" id="regSssLoanSalary" value="0"></div>
                        <div><label>SSS Calamity Loan</label><input type="number" id="regSssLoanCalamity" value="0"></div>
                        <div><label>Advance to Employees</label><input type="number" id="advWorkingLeave" value="0"></div>
                        <div><label>Non-Taxable Compensation</label><input type="number" id="advNonTaxable" value="0"></div>
                        <div><label>Net Pay</label><input type="number" id="netPayRegister" value="0"></div>
                        <div><label>Monthly Gross Compensation</label><input type="number" id="monthlyGrossComp" value="0"></div>
                        <div><label>SSS Contribution (ER)</label><input type="number" id="sssContribution" value="0"></div>
                        <div><label>PHIC Contribution (ER)</label><input type="number" id="phicContribution" value="0"></div>
                        <div><label>HDMF Contribution (ER)</label><input type="number" id="hdmfContribution" value="0"></div>
                    </div>
                </div>

                <div class="button-container">
                    <button type="button" class="next-btn" onclick="syncToPayslip()">Next &rarr; Payslip</button>
                </div>
            </div>
        </div>

        <!-- ================= PAYSLIP TAB ================= -->
        <div class="tab-content payroll-scroll-area payroll-scroll-hide" id="payslip">
            <div class="rs-card" style="margin-bottom:12px;">
                <h3 class="font-semibold">Payslip Details</h3>
                <div class="grid-2" style="margin-bottom:10px;">
                    <div>
                        <label>Employee Name</label>
                        <input type="text" id="payslipEmpName">
                    </div>
                    <div>
                        <label>Position</label>
                        <input type="text" id="payslipPosition">
                    </div>
                </div>
                <div class="grid-3">
                    <div>
                        <label>Period Start</label>
                        <input type="date" id="payslipPeriodStart">
                    </div>
                    <div>
                        <label>Period End</label>
                        <input type="date" id="payslipPeriodEnd">
                    </div>
                    <div>
                        <label>Payroll Period Type</label>
                        <select id="payslipPeriodType" style="height:40px;padding:5px 8px;border:1px solid #cfcfcf;border-radius:8px;font-size:13px;background:#e9e9e9;width:100%;">
                            <option value="first_half">1st Half &mdash; semi-monthly WT</option>
                            <option value="second_half">2nd Half &mdash; cumulative monthly WT</option>
                            <option value="monthly">Monthly &mdash; full monthly WT</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="priorHalfFields" class="top-grid" style="margin-top:8px;display:none;">
                <div>
                    <label style="font-size:0.8em;color:#666;">1st Half Net Taxable Comp.</label>
                    <input type="number" id="edit_priorGross" value="0" min="0" style="width:100%;">
                </div>
                <div>
                    <label style="font-size:0.8em;color:#666;">1st Half WT Withheld</label>
                    <input type="number" id="edit_priorWT" value="0" min="0" style="width:100%;">
                </div>
            </div>

            <div id="priorHalfStatus" style="display:none;padding:6px 10px;border-radius:4px;font-size:0.82em;margin-top:6px;"></div>

            <!-- VL Cash Conversion — payslip tab manual override -->
            <div class="rs-card" style="background:#f0f9e8;border-color:#b9dba5;margin-top:10px;margin-bottom:10px;">
                <h3 style="color:#2d6a2d;border-color:#b9dba5;">VL Cash Conversion <small style="font-weight:normal;color:#555;font-size:11px;">(Non-Taxable &mdash; Daily Rate = Monthly &times; 12 &divide; 261)</small></h3>
                <div class="grid-3">
                    <div>
                        <label>VL Days</label>
                        <input type="number" id="edit_vlDays" value="0" min="0" step="any" oninput="calcVLCash()">
                    </div>
                    <div>
                        <label>Daily Rate (auto)</label>
                        <input type="number" id="edit_dailyRate" value="0" readonly style="background:#f0f0f0;">
                    </div>
                    <div>
                        <label>Amount (&#8369;)</label>
                        <input type="number" id="edit_vlCashConversion" value="0" min="0" step="any"
                               oninput="_payslipEdited=true;recalcTotals();">
                    </div>
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
        <div class="modal-header">
            <h3>Payslip Preview</h3>
            <button class="close-modal" aria-label="Close">&times;</button>
        </div>
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

        const matches = employeesList.filter(e => e.name.toLowerCase().includes(q)).slice(0, 10);
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
    const dailyRateVal = empData.daily_rate ? parseFloat(empData.daily_rate) : (monthly / 21.75);
    document.getElementById('dailyRate').value = dailyRateVal.toFixed(2);
    // DOLE standard: 174 hours/month (21.75 days × 8 h)
    const hourlyRateVal = empData.hourly_rate ? parseFloat(empData.hourly_rate) : (monthly / 174);
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
    
    const nightDiffRate = parseFloat(window._payrollSettings?.night_diff_rate) || 0.10;
    const nightDiffPay = summary.night_diff_hours * hourlyRate * nightDiffRate;
    document.getElementById('workdayNightDiffDays').value = summary.night_diff_hours.toFixed(2);
    document.getElementById('workdayNightDiffPhp').value  = nightDiffPay.toFixed(2);
    // All per-type OT and night prem fields not tracked in attendance — reset to 0
    ['regOtGt10Hours','regOtGt10Php','regOtNightHours','regOtNightPhp',
     'satOtGt10Hours','satOtGt10Php','satNightDiffHours','satNightDiffPhp',
     'sunOtHours','sunOtPhp','sunOtGt10Hours','sunOtGt10Php','sunNightDiffHours','sunNightDiffPhp',
     'holOtGt10Hours','holOtGt10Php','holNightDiffHours','holNightDiffPhp'
    ].forEach(id => { const el = document.getElementById(id); if (el) el.value = '0'; });
    // Reset VL Cash fields
    const _vld = document.getElementById('vlDaysRaw');       if (_vld) _vld.value = '0';
    const _vlp = document.getElementById('vlCashPhp');       if (_vlp) _vlp.value = '0';
    calcRawVLCash();

    const otRegularRate = parseFloat(window._payrollSettings?.ot_regular_rate) || 1.25;
    const otRestRate    = parseFloat(window._payrollSettings?.ot_rest_rate)    || 1.69;
    const otHolidayRate = parseFloat(window._payrollSettings?.ot_holiday_rate) || 2.60;
    const regOtPay  = summary.reg_ot_hours     * hourlyRate * otRegularRate;
    const restOtPay = summary.rest_ot_hours    * hourlyRate * otRestRate;
    const holOtPay  = summary.holiday_ot_hours * hourlyRate * otHolidayRate;
    document.getElementById('workdayOtHours').value = summary.reg_ot_hours.toFixed(2);
    document.getElementById('workdayOtPhp').value   = regOtPay.toFixed(2);
    document.getElementById('satOtHours').value     = summary.rest_ot_hours.toFixed(2);
    document.getElementById('satOtPhp').value       = restOtPay.toFixed(2);
    document.getElementById('sunOtPhp').value       = '0.00';
    document.getElementById('holOtPhp').value       = holOtPay.toFixed(2);

    const emp = employeesList.find(e => e.id == employeeId);
    if (emp) document.getElementById('empName').value = emp.name;
    document.getElementById('selectedEmployeeId').value = employeeId;

    updateContributions(monthly);

    // Compute WT on actual taxable base: basic + NSD + all OT − absences/undertime
    const salAdj = parseFloat(document.getElementById('salaryAdjustment').value || 0)
                 + parseFloat(document.getElementById('overrideAmount').value   || 0)
                 + parseFloat(document.getElementById('nedAmount').value        || 0);
    const holPay = parseFloat(document.getElementById('holidayPay').value       || 0);
    const taxableBase = (monthly / 2) + nightDiffPay + regOtPay + restOtPay + holOtPay
                      + holPay + salAdj - absentAmount - undertimeAmount;
    updateWithholdingTax(Math.max(0, taxableBase));

    alert('Raw sheet auto‑filled with attendance data.');
    return true;
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
        [27000,750,600],[27500,750,625],[28000,750,650],[28500,750,675],[29000,750,700],[29500,750,725],
        [30000,750,750],[30500,750,775],[31000,750,800],[31500,750,825],[32000,750,850],[32500,750,875],
        [33000,750,900],[33500,750,925],[34000,750,950],[34500,750,975],[35000,750,1000]
    ];
    let row = t[t.length - 1];
    for (let i = t.length - 1; i >= 0; i--) { if (monthlySalary >= t[i][0]) { row = t[i]; break; } }
    return { regular: row[1], wisp: row[2], total: row[1] + row[2] };
}

(function() {
    fetch('<?= site_url("client_portal/payroll/getSettings") ?>')
        .then(function(r) { return r.json(); })
        .then(function(s) {
            window._payrollSettings = s;
            resolvePeriodDates(); // refresh labels & dates with DB cutoff value
        })
        .catch(function() {});
})();

/* ── Period selector ── */

// Compute a YYYY-MM-DD given salary year/month, a day number, and a month offset.
// day = 0 → last day of the target month; day > last day → clamped to last day.
function buildPeriodDate(salaryY, salaryM, day, offset) {
    // Resolve target month with offset (offset: -1 = prev, 0 = same, +1 = next)
    var rawM = salaryM + parseInt(offset || 0);
    var tY   = salaryY + Math.floor((rawM - 1) / 12);
    var tM   = ((rawM - 1) % 12 + 12) % 12 + 1; // 1-indexed, always positive
    var lastDay = new Date(tY, tM, 0).getDate();
    var d = parseInt(day) === 0 ? lastDay : Math.min(parseInt(day) || 1, lastDay);
    return tY + '-' + String(tM).padStart(2, '0') + '-' + String(d).padStart(2, '0');
}

function resolvePeriodDates() {
    var monthEl = document.getElementById('periodMonth');
    if (!monthEl || !monthEl.value) return;

    var parts = monthEl.value.split('-');
    var y = parseInt(parts[0]);
    var m = parseInt(parts[1]);
    var s = window._payrollSettings || {};

    var ranges;
    if (s.half1_start_day) {
        // Cross-month configuration: use per-period settings
        var lastDay = new Date(y, m, 0).getDate();
        ranges = {
            first_half: {
                start: buildPeriodDate(y, m, s.half1_start_day, s.half1_start_offset || 0),
                end:   buildPeriodDate(y, m, s.half1_end_day,   s.half1_end_offset   || 0),
            },
            second_half: {
                start: buildPeriodDate(y, m, s.half2_start_day, s.half2_start_offset || 0),
                end:   buildPeriodDate(y, m, s.half2_end_day,   s.half2_end_offset   || 0),
            },
            monthly: {
                start: buildPeriodDate(y, m, 1,        0),
                end:   buildPeriodDate(y, m, 0,        0), // 0 = last day
            },
        };
    } else {
        // Simple same-month cutoff (legacy / default)
        var cutoff  = parseInt(s.first_half_cutoff) || 15;
        var lastDay = new Date(y, m, 0).getDate();
        var pad     = function(n) { return String(n).padStart(2, '0'); };
        var ym      = y + '-' + pad(m);
        ranges = {
            first_half:  { start: ym + '-01',               end: ym + '-' + pad(cutoff)        },
            second_half: { start: ym + '-' + pad(cutoff+1), end: ym + '-' + pad(lastDay)       },
            monthly:     { start: ym + '-01',               end: ym + '-' + pad(lastDay)       },
        };
    }

    var fmtRange = function(s, e) {
        var opts = { month: 'short', day: 'numeric' };
        var ds = new Date(s + 'T00:00:00'), de = new Date(e + 'T00:00:00');
        return ds.toLocaleDateString('en-PH', opts) + ' – ' + de.toLocaleDateString('en-PH', opts);
    };

    // Update sub-label on each button
    Object.keys(ranges).forEach(function(p) {
        var lbl = document.getElementById('lbl_' + p);
        if (lbl) lbl.textContent = fmtRange(ranges[p].start, ranges[p].end);
    });

    // Set hidden start/end from active button
    var activeBtn = document.querySelector('.period-btn.active');
    var key       = (activeBtn && activeBtn.dataset.period) || 'first_half';
    var chosen    = ranges[key];

    document.getElementById('periodStart').value = chosen.start;
    document.getElementById('periodEnd').value   = chosen.end;

    var disp = document.getElementById('resolvedPeriodDisplay');
    if (disp) disp.textContent = 'Period: ' + fmtRange(chosen.start, chosen.end);
}

function selectPeriodBtn(btn) {
    document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    resolvePeriodDates();
}

document.addEventListener('DOMContentLoaded', function() { resolvePeriodDates(); });

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

    const phicRate = (parseFloat(window._payrollSettings?.phic_rate) || 0.05) / 2;
    const phicCap  = parseFloat(window._payrollSettings?.phic_cap)  || 100000;
    const phic = Math.min(monthlySalary, phicCap) * phicRate;

    const hdmfMax = parseFloat(window._payrollSettings?.hdmf_max) || 200;
    const hdmf = Math.min(hdmfMax, monthlySalary * 0.02);

    document.getElementById('sssEE').value    = sss.toFixed(2);
    if (document.getElementById('sssWisp'))  document.getElementById('sssWisp').value  = sssWisp.toFixed(2);
    if (document.getElementById('sssTotal')) document.getElementById('sssTotal').value = sss.toFixed(2);
    document.getElementById('phicEE').value   = phic.toFixed(2);
    document.getElementById('hdmfEE').value   = hdmf.toFixed(2);

    return { sss, phic, hdmf };
}

// BIR TRAIN Law WT — period type drives the computation method:
//
//   1st Half: semi-monthly brackets on gross taxable, NO contribution deduction.
//   2nd Half: net taxable = gross − SSS − PHIC − HDMF; monthly brackets on
//             (prior net taxable + current net taxable) − prior WT.
//   Monthly:  net taxable = gross − SSS − PHIC − HDMF; monthly brackets applied directly.
function updateWithholdingTax(currentGross) {
    const periodType = document.getElementById('payslipPeriodType')?.value || 'first_half';

    const getContrib = (editId, srcId) => {
        const v = safeParseFloat(document.getElementById(editId)?.value);
        return v > 0 ? v : safeParseFloat(document.getElementById(srcId)?.value);
    };
    const getContribs = () => ({
        sss:  getContrib('edit_sss',  'sssEE'),
        phic: getContrib('edit_phic', 'phicEE'),
        hdmf: getContrib('edit_hdmf', 'hdmfEE'),
    });

    const monthlyBrackets = (t) => {
        if      (t <= 20833)  return 0;
        else if (t <= 33332)  return          0 + (t -  20833) * 0.15;
        else if (t <= 66666)  return    1875.00 + (t -  33333) * 0.20;
        else if (t <= 166666) return    8541.80 + (t -  66667) * 0.25;
        else if (t <= 666666) return   33541.80 + (t - 166667) * 0.30;
        else                  return  183541.80 + (t - 666667) * 0.35;
    };

    let wt = 0;

    if (periodType === 'first_half') {
        // Semi-monthly brackets on gross taxable — no contribution deduction
        wt = semiMonthlyBracket(currentGross);

    } else if (periodType === 'second_half') {
        // Cumulative monthly: (prior net taxable + current net taxable) via monthly brackets, minus prior WT
        const { sss, phic, hdmf } = getContribs();
        const priorNetTaxable   = safeParseFloat(document.getElementById('edit_priorGross')?.value);
        const priorWT           = safeParseFloat(document.getElementById('edit_priorWT')?.value);
        const currentNetTaxable = Math.max(0, currentGross - sss - phic - hdmf);
        const monthlyTaxable    = Math.max(0, priorNetTaxable + currentNetTaxable);
        const monthlyWT         = monthlyBrackets(monthlyTaxable);
        wt = Math.max(0, monthlyWT - priorWT);
        // Show computation breakdown so values can be verified
        const statusEl = document.getElementById('priorHalfStatus');
        if (statusEl && statusEl.style.display !== 'none') {
            const contrib = sss + phic + hdmf;
            statusEl.innerHTML =
                `✓ 2nd-half cumulative: ` +
                `Prior net taxable <b>${priorNetTaxable.toFixed(2)}</b> + ` +
                `Current net taxable <b>${currentNetTaxable.toFixed(2)}</b> ` +
                `(${currentGross.toFixed(2)} − ${contrib.toFixed(2)} contribs) = ` +
                `Monthly taxable <b>${monthlyTaxable.toFixed(2)}</b> → ` +
                `Monthly WT <b>${monthlyWT.toFixed(4)}</b> − Prior WT <b>${priorWT.toFixed(2)}</b> = ` +
                `<b>${(Math.floor(wt * 100) / 100).toFixed(2)}</b>`;
        }

    } else { // monthly
        // Monthly brackets applied directly to (gross − contributions)
        const { sss, phic, hdmf } = getContribs();
        const netTaxable = Math.max(0, currentGross - sss - phic - hdmf);
        wt = monthlyBrackets(netTaxable);
    }

    // BIR standard: truncate to centavo (no rounding up)
    wt = Math.floor(wt * 100) / 100;
    document.getElementById('wtPayable').value = wt.toFixed(2);
    return wt;
}

// ── Raw-sheet helper calculators ─────────────────────────────────────────────
function calcRawNightPay(hoursId, payId) {
    const hrs  = safeParseFloat(document.getElementById(hoursId)?.value);
    const hr   = safeParseFloat(document.getElementById('hourlyRate')?.value);
    const rate = parseFloat(window._payrollSettings?.night_diff_rate) || 0.10;
    const el   = document.getElementById(payId);
    if (el) el.value = (hrs * hr * rate).toFixed(2);
}
function calcRawOtPay(hoursId, payId, rateKey) {
    const hrs = safeParseFloat(document.getElementById(hoursId)?.value);
    const hr  = safeParseFloat(document.getElementById('hourlyRate')?.value);
    const s   = window._payrollSettings || {};
    const rates = {
        regOt:  parseFloat(s.ot_regular_rate)  || 1.25,
        restOt: parseFloat(s.ot_rest_rate)     || 1.69,
        holOt:  parseFloat(s.ot_holiday_rate)  || 2.60,
    };
    const el = document.getElementById(payId);
    if (el) el.value = (hrs * hr * (rates[rateKey] || 1.25)).toFixed(2);
}
function calcRawVLCash() {
    const monthly = safeParseFloat(document.getElementById('monthlySalary')?.value);
    const daily   = monthly > 0 ? Math.round(monthly * 12 / 261 * 100) / 100 : 0;
    const dlEl = document.getElementById('vlDailyRateRaw');
    if (dlEl) dlEl.value = daily.toFixed(2);
    const days = safeParseFloat(document.getElementById('vlDaysRaw')?.value);
    const cash = Math.floor(days * daily * 100) / 100;
    const phEl = document.getElementById('vlCashPhp');
    if (phEl) phEl.value = cash.toFixed(2);
}
function calcVLCash() {
    const monthly = safeParseFloat(document.getElementById('monthlySalary')?.value);
    if (monthly > 0) {
        const daily = Math.round(monthly * 12 / 261 * 100) / 100;
        setNum('edit_dailyRate', daily);
    }
    const days  = safeParseFloat(document.getElementById('edit_vlDays')?.value);
    const daily = safeParseFloat(document.getElementById('edit_dailyRate')?.value);
    setNum('edit_vlCashConversion', Math.floor(days * daily * 100) / 100);
    _payslipEdited = true;
    recalcTotals();
}

// ================= SYNC RAW SHEET TO REGISTER =================
function syncToRegister() {
    _payslipEdited = false; // allow buildPayslipTable to fully rebuild on next tab visit
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
    const overtime = parseFloat(document.getElementById('workdayOtPhp')?.value || 0)
                   + parseFloat(document.getElementById('regOtGt10Php')?.value || 0)
                   + parseFloat(document.getElementById('satOtPhp')?.value || 0)
                   + parseFloat(document.getElementById('satOtGt10Php')?.value || 0)
                   + parseFloat(document.getElementById('sunOtPhp')?.value || 0)
                   + parseFloat(document.getElementById('sunOtGt10Php')?.value || 0)
                   + parseFloat(document.getElementById('holOtPhp')?.value || 0)
                   + parseFloat(document.getElementById('holOtGt10Php')?.value || 0);
    document.getElementById('overtimeReg').value = overtime.toFixed(2);
    const nd = parseFloat(document.getElementById('workdayNightDiffPhp')?.value || 0)
             + parseFloat(document.getElementById('regOtNightPhp')?.value || 0)
             + parseFloat(document.getElementById('satNightDiffPhp')?.value || 0)
             + parseFloat(document.getElementById('sunNightDiffPhp')?.value || 0)
             + parseFloat(document.getElementById('holNightDiffPhp')?.value || 0);
    document.getElementById('ndReg').value = nd.toFixed(2);
    const absAmt  = parseFloat(document.getElementById('absentPhp').value || 0)
                  + parseFloat(document.getElementById('utPhp').value || 0);
    document.getElementById('absencesTardiness').value = absAmt.toFixed(2);
    const holPay  = parseFloat(document.getElementById('holidayPay').value || 0);
    document.getElementById('holidayPayReg').value = holPay.toFixed(2);
    const salAdj  = parseFloat(document.getElementById('salaryAdjustmentReg').value || 0);

    // Gross Taxable = basic + OT + ND + holiday pay + salary adj − absences (no de minimis)
    // Gross Compensation = Gross Taxable + de minimis + VL Cash (non-taxable)
    // (matches Excel: R = E + SUM(F:Q), S = E + SUM(M:Q))
    const vlCash       = parseFloat(document.getElementById('vlCashPhp')?.value || 0);
    const grossTaxable = semiMonthly + overtime + nd + holPay + salAdj - absAmt;
    const grossComp    = grossTaxable + totalDeMin + vlCash;
    // Recalculate WT on actual gross taxable (includes OT, ND, holiday pay — NOT VL cash)
    updateWithholdingTax(Math.max(0, grossTaxable));
    document.getElementById('grossCompReg').value     = grossComp.toFixed(2);
    document.getElementById('grossTaxableComp').value = grossTaxable.toFixed(2);
    if (document.getElementById('grossNonTaxableComp'))
        document.getElementById('grossNonTaxableComp').value = (totalDeMin + vlCash).toFixed(2);

    const sss     = parseFloat(document.getElementById('sssEE').value   || 0);
    const phic    = parseFloat(document.getElementById('phicEE').value  || 0);
    const hdmf    = parseFloat(document.getElementById('hdmfEE').value  || 0);
    const wtax    = parseFloat(document.getElementById('wtPayable').value || 0);
    const sssLoan = parseFloat(document.getElementById('sssSalaryLoan').value || 0);
    const calLoan = parseFloat(document.getElementById('sssCalamityLoan').value || 0);
    const advance = parseFloat(document.getElementById('advSalary').value || 0);

    // Net Pay = Gross Comp − gov't contributions − withholding tax − loans/advances
    const netPay  = grossComp - sss - phic - hdmf - wtax - sssLoan - calLoan - advance;
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
let _payslipEdited = false; // true once user manually edits any payslip-tab field

// ================= PAYSLIP FUNCTIONS (CORRECTED) =================
function getComputedPayslipValues() {
    let monthly = safeParseFloat(document.getElementById('monthlySalary')?.value);
    let semiMonthly = safeParseFloat(document.getElementById('semiMonthlySalary')?.value);
    let basicPay = semiMonthly > 0 ? semiMonthly : (monthly / 2);
    if(basicPay <= 0 && monthly > 0) basicPay = monthly/2;
    let ratePerHour = safeParseFloat(document.getElementById('hourlyRate')?.value) || (monthly > 0 ? monthly / 174 : 0);
    let hoursWorked = ratePerHour > 0 ? basicPay / ratePerHour : 0;
    
    let nightPremium = safeParseFloat(document.getElementById('workdayNightDiffPhp')?.value);
    let absentAmt = safeParseFloat(document.getElementById('absentPhp')?.value);
    let undertimeAmt = safeParseFloat(document.getElementById('utPhp')?.value);
    let regOt         = safeParseFloat(document.getElementById('workdayOtPhp')?.value);
    let regOtGt10     = safeParseFloat(document.getElementById('regOtGt10Php')?.value);
    let regOtNightPrem= safeParseFloat(document.getElementById('regOtNightPhp')?.value);
    let satOt         = safeParseFloat(document.getElementById('satOtPhp')?.value);
    let satOtGt10     = safeParseFloat(document.getElementById('satOtGt10Php')?.value);
    let satNightPrem  = safeParseFloat(document.getElementById('satNightDiffPhp')?.value);
    let sunOt         = safeParseFloat(document.getElementById('sunOtPhp')?.value);
    let sunOtGt10     = safeParseFloat(document.getElementById('sunOtGt10Php')?.value);
    let sunNightPrem  = safeParseFloat(document.getElementById('sunNightDiffPhp')?.value);
    let holOt         = safeParseFloat(document.getElementById('holOtPhp')?.value);
    let holOtGt10     = safeParseFloat(document.getElementById('holOtGt10Php')?.value);
    let holNightPrem  = safeParseFloat(document.getElementById('holNightDiffPhp')?.value);
    let vlCashConversion = safeParseFloat(document.getElementById('vlCashPhp')?.value);
    
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
    
    let totalOvertime = regOt + regOtGt10 + regOtNightPrem
                      + satOt + satOtGt10 + satNightPrem
                      + sunOt + sunOtGt10 + sunNightPrem
                      + holOt + holOtGt10 + holNightPrem;
    let grossPay = netBasicPay + totalOvertime + taxableOther + nonTaxable + vlCashConversion;
    
    let sssDed         = safeParseFloat(document.getElementById('sssEE')?.value);
    let phicDed        = safeParseFloat(document.getElementById('phicEE')?.value);
    let hdmfDed        = safeParseFloat(document.getElementById('hdmfEE')?.value);
    let withholdingTax = safeParseFloat(document.getElementById('wtPayable')?.value);
    let sssLoan        = safeParseFloat(document.getElementById('sssSalaryLoan')?.value);
    let calamityLoan   = safeParseFloat(document.getElementById('sssCalamityLoan')?.value);
    let salaryAdvance  = safeParseFloat(document.getElementById('advSalary')?.value);
    let totalDeductions = sssDed + phicDed + hdmfDed + withholdingTax + sssLoan + calamityLoan + salaryAdvance;
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
        netBasicPay,
        regOt, regOtGt10, regOtNightPrem,
        satOt, satOtGt10, satNightPrem,
        sunOt, sunOtGt10, sunNightPrem,
        holOt, holOtGt10, holNightPrem,
        vlCashConversion, taxableOther, nonTaxable,
        grossPay, sssDed, phicDed, hdmfDed, withholdingTax,
        sssLoan, calamityLoan, salaryAdvance, totalDeductions, netPay,
        formatStart: formatDate(pStart), formatEnd: formatDate(pEnd)
    };
}

// Returns the per-hour effective rate for a linked payslip row.
function _editRateFor(rateKey) {
    let hr = safeParseFloat(document.getElementById('edit_ratePerHour')?.value);
    // Fallback: compute from raw-sheet fields when the edit field is 0 or missing
    if (!hr) {
        hr = safeParseFloat(document.getElementById('hourlyRate')?.value);
        if (!hr) {
            const monthly = safeParseFloat(document.getElementById('monthlySalary')?.value);
            hr = monthly > 0 ? monthly / 174 : 0;
        }
    }
    const s  = window._payrollSettings || {};
    switch (rateKey) {
        case 'night':   return hr * (parseFloat(s.night_diff_rate)  || 0.10);
        case 'plain':   return hr;
        case 'regOt':   return hr * (parseFloat(s.ot_regular_rate)  || 1.275);
        case 'restOt':  return hr * (parseFloat(s.ot_rest_rate)     || 1.69);
        case 'holOt':   return hr * (parseFloat(s.ot_holiday_rate)  || 2.00);
    }
    return hr;
}

function buildPayslipTable() {
    // When the user has manually edited fields, skip rebuild so edits are preserved,
    // but still refresh the period type and prior-half data (period button may have changed).
    if (_payslipEdited) { autoDetectPeriodType(); fetchAndSetPriorHalfWT(); return; }
    let d = getComputedPayslipValues();
    document.getElementById('payslipEmpName').value = d.empName;
    document.getElementById('payslipPosition').value = d.position;
    document.getElementById('payslipPeriodStart').value = d.periodStart;
    document.getElementById('payslipPeriodEnd').value = d.periodEnd;

    const s = window._payrollSettings || {};
    const hr = d.ratePerHour;
    const safeHrs = (amt, rate) => (rate > 0 && amt > 0) ? amt / rate : 0;
    const ndRate  = parseFloat(s.night_diff_rate)  || 0.10;
    const regOtR  = parseFloat(s.ot_regular_rate)  || 1.275;
    const restOtR = parseFloat(s.ot_rest_rate)     || 1.69;
    const holOtR  = parseFloat(s.ot_holiday_rate)  || 2.00;

    const regRows = [
        { label: 'Basic Pay',           id: 'edit_basicPay',     hoursId: null,             hrs: 0,                                     rateKey: null,    val: d.basicPay },
        { label: 'Rate per hour',       id: 'edit_ratePerHour',  hoursId: null,             hrs: 0,                                     rateKey: null,    val: d.ratePerHour },
        { label: 'No. of Hours Worked', id: 'edit_hoursWorked',  hoursId: null,             hrs: 0,                                     rateKey: null,    val: d.hoursWorked },
        { label: 'Night Premium',       id: 'edit_nightPremium', hoursId: 'edit_nightHrs',  hrs: safeHrs(d.nightPremium, hr * ndRate),  rateKey: 'night',   val: d.nightPremium },
        { label: 'Less Absences',       id: 'edit_absentAmt',    hoursId: 'edit_absentHrs', hrs: safeHrs(d.absentAmt,    hr),           rateKey: 'plain',   val: d.absentAmt },
        { label: 'Late/Undertime',      id: 'edit_undertimeAmt', hoursId: 'edit_utHrs',     hrs: safeHrs(d.undertimeAmt, hr),           rateKey: 'plain',   val: d.undertimeAmt },
        { label: 'Reg. OT&le;10 hrs',    id: 'edit_regOt',           hoursId: 'edit_regOtHrs',           hrs: safeHrs(d.regOt,          hr * regOtR),  rateKey: 'regOt',   val: d.regOt },
        { label: 'Reg. OT&gt;10 hrs',    id: 'edit_regOtGt10',       hoursId: 'edit_regOtGt10Hrs',       hrs: safeHrs(d.regOtGt10,      hr * regOtR),  rateKey: 'regOt',   val: d.regOtGt10 },
        { label: 'Night premium',         id: 'edit_regOtNightPrem',  hoursId: 'edit_regOtNightPremHrs',  hrs: safeHrs(d.regOtNightPrem, hr * ndRate),  rateKey: 'night',   val: d.regOtNightPrem },
        { label: 'Sat OT&le;10 hrs',     id: 'edit_satOt',           hoursId: 'edit_satOtHrs',           hrs: safeHrs(d.satOt,          hr * restOtR), rateKey: 'restOt',  val: d.satOt },
        { label: 'Sat OT&gt;10 hrs',     id: 'edit_satOtGt10',       hoursId: 'edit_satOtGt10Hrs',       hrs: safeHrs(d.satOtGt10,      hr * restOtR), rateKey: 'restOt',  val: d.satOtGt10 },
        { label: 'Sat Night premium',     id: 'edit_satNightPrem',    hoursId: 'edit_satNightPremHrs',    hrs: safeHrs(d.satNightPrem,   hr * ndRate),  rateKey: 'night',   val: d.satNightPrem },
        { label: 'Sun OT&le;10 hrs',     id: 'edit_sunOt',           hoursId: 'edit_sunOtHrs',           hrs: safeHrs(d.sunOt,          hr * restOtR), rateKey: 'restOt',  val: d.sunOt },
        { label: 'Sun OT&gt;10 hrs',     id: 'edit_sunOtGt10',       hoursId: 'edit_sunOtGt10Hrs',       hrs: safeHrs(d.sunOtGt10,      hr * restOtR), rateKey: 'restOt',  val: d.sunOtGt10 },
        { label: 'Sun Night premium',     id: 'edit_sunNightPrem',    hoursId: 'edit_sunNightPremHrs',    hrs: safeHrs(d.sunNightPrem,   hr * ndRate),  rateKey: 'night',   val: d.sunNightPrem },
        { label: 'Hol OT&le;10 hrs',     id: 'edit_holOt',           hoursId: 'edit_holOtHrs',           hrs: safeHrs(d.holOt,          hr * holOtR),  rateKey: 'holOt',   val: d.holOt },
        { label: 'Hol OT&gt;10 hrs',     id: 'edit_holOtGt10',       hoursId: 'edit_holOtGt10Hrs',       hrs: safeHrs(d.holOtGt10,      hr * holOtR),  rateKey: 'holOt',   val: d.holOtGt10 },
        { label: 'Hol Night premium',     id: 'edit_holNightPrem',    hoursId: 'edit_holNightPremHrs',    hrs: safeHrs(d.holNightPrem,   hr * ndRate),  rateKey: 'night',   val: d.holNightPrem },
    ];
    let regHtml = '';
    regRows.forEach(row => {
        const hrsCell = row.hoursId
            ? `<input type="number" id="${row.hoursId}" value="${row.hrs.toFixed(2)}" step="any" min="0" style="width:65px;"> hrs`
            : '-';
        regHtml += `<tr><td class="text-left">${row.label}</td><td class="text-left" style="white-space:nowrap">${hrsCell}</td><td class="text-right"><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any" style="width:100px;"></td></tr>`;
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
        { desc: 'De Minimis Benefit',    id: 'edit_nonTaxable',       val: d.nonTaxable },
        { desc: 'VL Cash Conversion',    id: 'edit_vlCashConversion', val: d.vlCashConversion },
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

    const loanRows = [
        { desc: 'Salary Loan', id: 'edit_sssLoan', val: safeParseFloat(document.getElementById('sssSalaryLoan')?.value) },
        { desc: 'Calamity Loan', id: 'edit_calamityLoan', val: safeParseFloat(document.getElementById('sssCalamityLoan')?.value) },
        { desc: 'Salary Advance', id: 'edit_advSalary', val: safeParseFloat(document.getElementById('advSalary')?.value) }
    ];
    let loanHtml = '';
    loanRows.forEach(row => {
        loanHtml += `<tr><td class="text-left"><input type="text" value="${row.desc}" style="width:100%; background:#e5e5e5; border:none;"></td>
                    <td class="text-right"><input type="number" id="${row.id}" value="${row.val.toFixed(2)}" step="any" style="width:100px;"></td></tr>`;
    });
    document.getElementById('loansBody').innerHTML = loanHtml;

    // Bidirectional hours ↔ amount for time-based rows
    regRows.filter(r => r.hoursId).forEach(row => {
        const hEl = document.getElementById(row.hoursId);
        const aEl = document.getElementById(row.id);
        if (!hEl || !aEl) return;
        hEl.addEventListener('input', () => {
            _payslipEdited = true;
            const rate = _editRateFor(row.rateKey);
            if (rate > 0) aEl.value = (safeParseFloat(hEl.value) * rate).toFixed(2);
            recalcTotals();
        });
        aEl.addEventListener('input', () => {
            _payslipEdited = true;
            const rate = _editRateFor(row.rateKey);
            if (rate > 0) hEl.value = (safeParseFloat(aEl.value) / rate).toFixed(2);
            recalcTotals();
        });
    });
    // Plain recalc for non-linked fields — mark payslip as edited on any change
    ['edit_basicPay','edit_ratePerHour','edit_hoursWorked',
     'edit_taxAdj','edit_nonTaxable',
     'edit_sss','edit_phic','edit_hdmf','edit_wtax','edit_sssLoan','edit_calamityLoan','edit_advSalary',
     'edit_priorGross','edit_priorWT'
    ].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', () => { _payslipEdited = true; recalcTotals(); });
    });
    // Header fields (name, position, dates) — preserve on tab switch
    ['payslipEmpName','payslipPosition','payslipPeriodStart','payslipPeriodEnd'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => { _payslipEdited = true; });
        if (el) el.addEventListener('input',  () => { _payslipEdited = true; });
    });
    // Always detect period type and fetch prior-half data when the payslip table is
    // (re)built — this runs on tab switch, not just on manual date edits.
    // fetchAndSetPriorHalfWT() calls recalcTotals() internally when done.
    autoDetectPeriodType();
    fetchAndSetPriorHalfWT();
}

// Auto-set the period type dropdown from Payroll Settings → Default Period Type.
// Priority: settings override → active Raw Sheet period button → date-based fallback.
// Called on period start change and before auto-fill. User can still override manually.
function autoDetectPeriodType() {
    const typeEl = document.getElementById('payslipPeriodType');
    if (!typeEl) return;
    const defaultType = window._payrollSettings?.default_period_type || 'auto';
    if (defaultType !== 'auto') {
        typeEl.value = defaultType;
        return;
    }

    // Use the active period button — it's the most reliable signal, especially for
    // cross-month cutoffs where the start-day comparison would give wrong results.
    const activeBtn = document.querySelector('.period-btn.active');
    if (activeBtn && activeBtn.dataset.period) {
        typeEl.value = activeBtn.dataset.period;
        return;
    }

    // Fallback (no button active): derive from period start date vs simple cutoff day.
    // Only reliable when first_half_cutoff is used without cross-month settings.
    const periodStart = document.getElementById('payslipPeriodStart')?.value;
    if (!periodStart) return;
    const s = window._payrollSettings || {};
    if (s.half1_start_day) {
        // Cross-month config: cannot determine type from start day alone; default to second_half
        typeEl.value = 'second_half';
    } else {
        const day    = parseInt(periodStart.split('-')[2], 10);
        const cutoff = parseInt(s.first_half_cutoff || '15', 10);
        typeEl.value = (day <= cutoff) ? 'first_half' : 'second_half';
    }
}

// BIR semi-monthly brackets (used for 1st half WT and for estimating prior WT).
function semiMonthlyBracket(t) {
    t = Math.max(0, t);
    if      (t <= 10416)  return 0;
    else if (t <= 16666)  return        0 + (t -  10417) * 0.15;
    else if (t <= 33332)  return   937.50 + (t -  16667) * 0.20;
    else if (t <= 83332)  return  4270.70 + (t -  33333) * 0.25;
    else if (t <= 333332) return 16770.70 + (t -  83333) * 0.30;
    else                  return 91770.70 + (t - 333333) * 0.35;
}

// Called whenever the period type or period start changes.
// Manages contributions (zero for 1st half, restore for 2nd/monthly),
// shows/hides the prior-period fields, and fetches 1st-half data for 2nd-half payslips.
function fetchAndSetPriorHalfWT() {
    const periodType  = document.getElementById('payslipPeriodType')?.value || 'first_half';
    const periodStart = document.getElementById('payslipPeriodStart')?.value;
    const empId       = document.getElementById('selectedEmployeeId')?.value;
    const pgEl        = document.getElementById('edit_priorGross');
    const pwEl        = document.getElementById('edit_priorWT');
    const statusEl    = document.getElementById('priorHalfStatus');
    const priorFields = document.getElementById('priorHalfFields');

    const hideStatus = () => { if (statusEl) { statusEl.style.display = 'none'; statusEl.textContent = ''; } };
    const showStatus = (msg, color, bg) => {
        if (!statusEl) return;
        statusEl.textContent = msg;
        statusEl.style.display = 'block';
        statusEl.style.color = color;
        statusEl.style.background = bg;
        statusEl.style.border = '1px solid ' + color;
    };
    const clearPriorFields = () => { if (pgEl) pgEl.value = '0.00'; if (pwEl) pwEl.value = '0.00'; };

    // Show prior-period inputs only for 2nd half (needed for cumulative WT)
    if (priorFields) priorFields.style.display = (periodType === 'second_half') ? '' : 'none';

    if (periodType === 'first_half') {
        // Contributions deducted only in 2nd half — zero them here
        clearPriorFields();
        ['edit_sss','edit_phic','edit_hdmf'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '0.00';
        });
        hideStatus();
        recalcTotals();
        return;
    }

    // 2nd half or monthly: ensure contributions are populated from the source fields
    [['edit_sss','sssEE'],['edit_phic','phicEE'],['edit_hdmf','hdmfEE']].forEach(([editId, srcId]) => {
        const editEl = document.getElementById(editId);
        const srcEl  = document.getElementById(srcId);
        if (editEl && srcEl && safeParseFloat(editEl.value) === 0) {
            const v = safeParseFloat(srcEl.value);
            if (v > 0) editEl.value = v.toFixed(2);
        }
    });

    if (periodType === 'monthly') {
        // Monthly: no prior period needed
        clearPriorFields();
        hideStatus();
        recalcTotals();
        return;
    }

    // 2nd half: fetch prior 1st-half data (sessionStorage first, then DB)
    if (!periodStart || !empId) { clearPriorFields(); hideStatus(); recalcTotals(); return; }

    // Use salary month (the period picker month) as cache key — cross-month 1st half starts
    // in the previous calendar month but belongs to the same salary month (e.g. May 26–Jun 10
    // is the 1st half of the June salary). Both halves must share the same cache key.
    const salaryYm = document.getElementById('periodMonth')?.value || periodStart.substring(0, 7);
    const key = `prior_half_${empId}_${salaryYm}`;

    // Resolve prior (1st-half) period dates from the company's period configuration.
    // No values are assumed — only what is actually configured in Payroll Settings.
    const s = window._payrollSettings || {};
    const [sy, sm] = salaryYm.split('-').map(Number);
    let priorStart, priorEnd;
    if (s.half1_start_day) {
        // Cross-month mode: use the configured half1 start/end
        priorStart = buildPeriodDate(sy, sm, s.half1_start_day, s.half1_start_offset || 0);
        priorEnd   = buildPeriodDate(sy, sm, s.half1_end_day,   s.half1_end_offset   || 0);
    } else if (s.first_half_cutoff) {
        // Simple cutoff mode: 1st half is always day-1 to cutoff within the salary month
        const cutoff = parseInt(s.first_half_cutoff);
        priorStart = salaryYm + '-01';
        priorEnd   = salaryYm + '-' + String(cutoff).padStart(2, '0');
    } else {
        // No period configuration found — cannot determine prior period; let PHP decide
        priorStart = salaryYm + '-01';
        priorEnd   = salaryYm + '-15';   // PHP will attempt configured fallbacks first
    }

    const cached = sessionStorage.getItem(key);
    if (cached) {
        try {
            const c = JSON.parse(cached);
            if (pgEl) pgEl.value = parseFloat(c.net_taxable).toFixed(2);
            if (pwEl) pwEl.value = parseFloat(c.wt).toFixed(2);
            showStatus(`✓ 1st-half data loaded (${priorStart} to ${priorEnd}): Net Taxable = ${parseFloat(c.net_taxable).toFixed(2)}, WT = ${parseFloat(c.wt).toFixed(2)}`, '#155724', '#d4edda');
            recalcTotals();
            return;
        } catch(e) { sessionStorage.removeItem(key); }
    }

    // Fall back to DB lookup — pass explicit prior period dates so the server
    // does not need to guess them from a hardcoded same-month cutoff rule.
    fetch(`<?= site_url('client_portal/payroll/getPriorHalfWT') ?>/${empId}/${priorStart}/${priorEnd}`)
        .then(r => r.json())
        .then(data => {
            if (data.found) {
                if (pgEl) pgEl.value = parseFloat(data.net_taxable).toFixed(2);
                if (pwEl) pwEl.value = parseFloat(data.wt).toFixed(2);
                sessionStorage.setItem(key, JSON.stringify({ net_taxable: data.net_taxable, wt: data.wt }));
                showStatus(`✓ 1st-half data loaded from saved record (${priorStart} to ${priorEnd}): Net Taxable = ${parseFloat(data.net_taxable).toFixed(2)}, WT = ${parseFloat(data.wt).toFixed(2)}`, '#155724', '#d4edda');
            } else {
                // Estimate from basic pay so 2nd half WT is not computed against zero prior
                const basicPay = safeParseFloat(document.getElementById('edit_basicPay')?.value)
                               || safeParseFloat(document.getElementById('semiMonthlySalary')?.value);
                if (basicPay > 0) {
                    const estWT = Math.floor(semiMonthlyBracket(basicPay) * 100) / 100;
                    if (pgEl) pgEl.value = basicPay.toFixed(2);
                    if (pwEl) pwEl.value = estWT.toFixed(2);
                    showStatus(
                        `⚠ No saved 1st-half record found (${priorStart} to ${priorEnd}). ` +
                        `Estimated from basic pay — Prior net taxable: ${basicPay.toFixed(2)}, Prior WT: ${estWT.toFixed(2)}. ` +
                        `Adjust manually if the 1st half had different earnings.`,
                        '#856404', '#fff3cd'
                    );
                } else {
                    clearPriorFields();
                    showStatus(`⚠ No 1st-half payslip found (${priorStart} to ${priorEnd}). Generate the 1st-half payslip first, or enter the prior values manually.`, '#856404', '#fff3cd');
                }
            }
            recalcTotals();
        })
        .catch(() => { clearPriorFields(); hideStatus(); recalcTotals(); });
}

function recalcTotals() {
    let basicPayVal          = safeParseFloat(document.getElementById('edit_basicPay')?.value);
    let nightPremium         = safeParseFloat(document.getElementById('edit_nightPremium')?.value);
    let absent               = safeParseFloat(document.getElementById('edit_absentAmt')?.value);
    let undertime            = safeParseFloat(document.getElementById('edit_undertimeAmt')?.value);
    let totalDeductionsFromPay = Math.min(absent + undertime, basicPayVal);
    let netBasicPay          = Math.max(0, basicPayVal - totalDeductionsFromPay + nightPremium);

    let regOt          = safeParseFloat(document.getElementById('edit_regOt')?.value);
    let regOtGt10      = safeParseFloat(document.getElementById('edit_regOtGt10')?.value);
    let regOtNightPrem = safeParseFloat(document.getElementById('edit_regOtNightPrem')?.value);
    let satOt          = safeParseFloat(document.getElementById('edit_satOt')?.value);
    let satOtGt10      = safeParseFloat(document.getElementById('edit_satOtGt10')?.value);
    let satNightPrem   = safeParseFloat(document.getElementById('edit_satNightPrem')?.value);
    let sunOt          = safeParseFloat(document.getElementById('edit_sunOt')?.value);
    let sunOtGt10      = safeParseFloat(document.getElementById('edit_sunOtGt10')?.value);
    let sunNightPrem   = safeParseFloat(document.getElementById('edit_sunNightPrem')?.value);
    let holOt          = safeParseFloat(document.getElementById('edit_holOt')?.value);
    let holOtGt10      = safeParseFloat(document.getElementById('edit_holOtGt10')?.value);
    let holNightPrem   = safeParseFloat(document.getElementById('edit_holNightPrem')?.value);
    let vlCashConv     = safeParseFloat(document.getElementById('edit_vlCashConversion')?.value);
    let totalOt = regOt + regOtGt10 + regOtNightPrem
                + satOt + satOtGt10 + satNightPrem
                + sunOt + sunOtGt10 + sunNightPrem
                + holOt + holOtGt10 + holNightPrem;

    let taxableSum    = safeParseFloat(document.getElementById('edit_taxAdj')?.value);
    let nonTaxableSum = safeParseFloat(document.getElementById('edit_nonTaxable')?.value);
    // Gross = net basic pay + taxable OT/ND + other taxable + non-taxable + VL cash (non-taxable)
    let gross = netBasicPay + totalOt + taxableSum + nonTaxableSum + vlCashConv;

    // WT base = taxable earnings only (VL Cash is non-taxable, excluded)
    const wtBase = netBasicPay + totalOt + taxableSum;
    const newWT  = updateWithholdingTax(wtBase);
    const wtEl   = document.getElementById('edit_wtax');
    if (wtEl) wtEl.value = newWT.toFixed(2);

    // Cache 1st-half results so the 2nd-half payslip can read them in the same session.
    // Key uses the salary month (periodMonth picker) so cross-month periods (e.g. May 26–Jun 10
    // = 1st half of June salary) are stored under the same month as the 2nd half lookup.
    const _periodType  = document.getElementById('payslipPeriodType')?.value;
    const _periodStart = document.getElementById('payslipPeriodStart')?.value;
    const _empId       = document.getElementById('selectedEmployeeId')?.value;
    if (_periodType === 'first_half' && _periodStart && _empId) {
        const _salaryYm = document.getElementById('periodMonth')?.value || _periodStart.substring(0, 7);
        const _key = `prior_half_${_empId}_${_salaryYm}`;
        sessionStorage.setItem(_key, JSON.stringify({ net_taxable: wtBase, wt: newWT }));
    }

    let dedSum = 0;
    ['edit_sss','edit_phic','edit_hdmf','edit_wtax','edit_sssLoan','edit_calamityLoan','edit_advSalary'].forEach(id => {
        dedSum += safeParseFloat(document.getElementById(id)?.value);
    });
    let netPay = gross - dedSum;

    document.getElementById('totalGross').value      = gross.toFixed(2);
    document.getElementById('totalDeductions').value = dedSum.toFixed(2);
    document.getElementById('totalNet').value        = netPay.toFixed(2);
}

function autoFillPayslip() {
    _payslipEdited = false; // force a full rebuild so raw-sheet values are re-applied
    let d = getComputedPayslipValues();
    setNum('edit_basicPay', d.basicPay);
    setNum('edit_ratePerHour', d.ratePerHour);
    setNum('edit_hoursWorked', d.hoursWorked);
    setNum('edit_nightPremium', d.nightPremium);
    setNum('edit_absentAmt', d.absentAmt);
    setNum('edit_undertimeAmt', d.undertimeAmt);
    setNum('edit_regOt',          d.regOt);
    setNum('edit_regOtGt10',      d.regOtGt10);
    setNum('edit_regOtNightPrem', d.regOtNightPrem);
    setNum('edit_satOt',          d.satOt);
    setNum('edit_satOtGt10',      d.satOtGt10);
    setNum('edit_satNightPrem',   d.satNightPrem);
    setNum('edit_sunOt',          d.sunOt);
    setNum('edit_sunOtGt10',      d.sunOtGt10);
    setNum('edit_sunNightPrem',   d.sunNightPrem);
    setNum('edit_holOt',          d.holOt);
    setNum('edit_holOtGt10',      d.holOtGt10);
    setNum('edit_holNightPrem',   d.holNightPrem);
    setNum('edit_vlCashConversion', d.vlCashConversion);
    setNum('edit_taxAdj', d.taxableOther);
    setNum('edit_nonTaxable', d.nonTaxable);
    // Sync hours fields
    const hr  = d.ratePerHour;
    const s   = window._payrollSettings || {};
    const sh  = (amt, rate) => (rate > 0 && amt > 0) ? amt / rate : 0;
    const ndR = parseFloat(s.night_diff_rate)  || 0.10;
    setNum('edit_nightHrs',          sh(d.nightPremium,  hr * ndR));
    setNum('edit_absentHrs',         sh(d.absentAmt,     hr));
    setNum('edit_utHrs',             sh(d.undertimeAmt,  hr));
    const regR  = parseFloat(s.ot_regular_rate)  || 1.25;
    const restR = parseFloat(s.ot_rest_rate)     || 1.69;
    const holR  = parseFloat(s.ot_holiday_rate)  || 2.60;
    setNum('edit_regOtHrs',           sh(d.regOt,          hr * regR));
    setNum('edit_regOtGt10Hrs',       sh(d.regOtGt10,      hr * regR));
    setNum('edit_regOtNightPremHrs',  sh(d.regOtNightPrem, hr * ndR));
    setNum('edit_satOtHrs',           sh(d.satOt,          hr * restR));
    setNum('edit_satOtGt10Hrs',       sh(d.satOtGt10,      hr * restR));
    setNum('edit_satNightPremHrs',    sh(d.satNightPrem,   hr * ndR));
    setNum('edit_sunOtHrs',           sh(d.sunOt,          hr * restR));
    setNum('edit_sunOtGt10Hrs',       sh(d.sunOtGt10,      hr * restR));
    setNum('edit_sunNightPremHrs',    sh(d.sunNightPrem,   hr * ndR));
    setNum('edit_holOtHrs',           sh(d.holOt,          hr * holR));
    setNum('edit_holOtGt10Hrs',       sh(d.holOtGt10,      hr * holR));
    setNum('edit_holNightPremHrs',    sh(d.holNightPrem,   hr * ndR));
    // Also initialise the daily-rate field on payslip tab for manual VL adjustment
    const _monthly = safeParseFloat(document.getElementById('monthlySalary')?.value);
    if (_monthly > 0) setNum('edit_dailyRate', Math.round(_monthly * 12 / 261 * 100) / 100);
    setNum('edit_sss', d.sssDed);
    setNum('edit_phic', d.phicDed);
    setNum('edit_hdmf', d.hdmfDed);
    setNum('edit_wtax', d.withholdingTax);
    setNum('edit_sssLoan', safeParseFloat(document.getElementById('sssSalaryLoan')?.value));
    setNum('edit_calamityLoan', safeParseFloat(document.getElementById('sssCalamityLoan')?.value));
    setNum('edit_advSalary', safeParseFloat(document.getElementById('advSalary')?.value));
    autoDetectPeriodType(); // set dropdown from period date + cutoff setting
    fetchAndSetPriorHalfWT();
}
function setNum(id, val) { let el = document.getElementById(id); if(el) el.value = val.toFixed(2); }

// ================= GENERATE PAYSLIP (WITH LOGO) =================
function computePayslipData() {
    let monthly = safeParseFloat(document.getElementById('monthlySalary')?.value, 0);
    let semiMonthly = safeParseFloat(document.getElementById('semiMonthlySalary')?.value, 0);
    let basicPay = semiMonthly > 0 ? semiMonthly : (monthly / 2);
    if(basicPay <= 0 && monthly > 0) basicPay = monthly/2;
    let ratePerHour = safeParseFloat(document.getElementById('hourlyRate')?.value) || (monthly > 0 ? monthly / 174 : 0);
    let hoursWorked = ratePerHour > 0 ? basicPay / ratePerHour : 0;
    
    let nightPremium = safeParseFloat(document.getElementById('workdayNightDiffPhp')?.value);
    let absentAmt = safeParseFloat(document.getElementById('absentPhp')?.value);
    let undertimeAmt = safeParseFloat(document.getElementById('utPhp')?.value);
    let regOt         = safeParseFloat(document.getElementById('workdayOtPhp')?.value);
    let regOtGt10     = safeParseFloat(document.getElementById('regOtGt10Php')?.value);
    let regOtNightPrem= safeParseFloat(document.getElementById('regOtNightPhp')?.value);
    let satOt         = safeParseFloat(document.getElementById('satOtPhp')?.value);
    let satOtGt10     = safeParseFloat(document.getElementById('satOtGt10Php')?.value);
    let satNightPrem  = safeParseFloat(document.getElementById('satNightDiffPhp')?.value);
    let sunOt         = safeParseFloat(document.getElementById('sunOtPhp')?.value);
    let sunOtGt10     = safeParseFloat(document.getElementById('sunOtGt10Php')?.value);
    let sunNightPrem  = safeParseFloat(document.getElementById('sunNightDiffPhp')?.value);
    let holOt         = safeParseFloat(document.getElementById('holOtPhp')?.value);
    let holOtGt10     = safeParseFloat(document.getElementById('holOtGt10Php')?.value);
    let holNightPrem  = safeParseFloat(document.getElementById('holNightDiffPhp')?.value);
    let vlCashConversion = safeParseFloat(document.getElementById('vlCashPhp')?.value);
    
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
    
    let totalOvertime = regOt + regOtGt10 + regOtNightPrem
                      + satOt + satOtGt10 + satNightPrem
                      + sunOt + sunOtGt10 + sunNightPrem
                      + holOt + holOtGt10 + holNightPrem;
    let grossPay = netBasicPay + totalOvertime + taxableOther + nonTaxable + vlCashConversion;
    
    let sssDed         = safeParseFloat(document.getElementById('sssEE')?.value);
    let phicDed        = safeParseFloat(document.getElementById('phicEE')?.value);
    let hdmfDed        = safeParseFloat(document.getElementById('hdmfEE')?.value);
    let withholdingTax = safeParseFloat(document.getElementById('wtPayable')?.value);
    let sssLoan        = safeParseFloat(document.getElementById('sssSalaryLoan')?.value);
    let calamityLoan   = safeParseFloat(document.getElementById('sssCalamityLoan')?.value);
    let salaryAdvance  = safeParseFloat(document.getElementById('advSalary')?.value);
    let totalDeductions = sssDed + phicDed + hdmfDed + withholdingTax + sssLoan + calamityLoan + salaryAdvance;
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
        netBasicPay,
        regOt, regOtGt10, regOtNightPrem,
        satOt, satOtGt10, satNightPrem,
        sunOt, sunOtGt10, sunNightPrem,
        holOt, holOtGt10, holNightPrem,
        vlCashConversion, taxableOther, nonTaxable,
        grossPay, sssDed, phicDed, hdmfDed, withholdingTax,
        sssLoan, calamityLoan, salaryAdvance, totalDeductions, netPay,
        formatStart: formatDate(pStart), formatEnd: formatDate(pEnd)
    };
}

function generatePayslipHTML(d) {
    const logoHtml = companyLogoUrl ? `<div style="text-align:center;margin-bottom:20px;"><img src="${companyLogoUrl}" style="max-height:80px;margin:0 auto;"></div>` : '';
    const b = 'border:1px solid #000;', p = 'padding:4px 6px;';
    const bp = b + p;
    const s      = window._payrollSettings || {};
    const hr     = d.ratePerHour || 0;
    const ndR    = parseFloat(s.night_diff_rate)  || 0.10;
    const regOtR = parseFloat(s.ot_regular_rate)  || 1.25;
    const restR  = parseFloat(s.ot_rest_rate)     || 1.69;
    const holOtR = parseFloat(s.ot_holiday_rate)  || 2.60;

    /* All 12 OT rows — always show, zero values display as '-' */
    const otDefs = [
        { label: 'Reg. OT &le;10 hrs',     raw: d.regOt          || 0, rate: hr * regOtR },
        { label: 'Reg. OT &gt;10 hrs',     raw: d.regOtGt10      || 0, rate: hr * regOtR },
        { label: 'Reg. Night Premium',      raw: d.regOtNightPrem || 0, rate: hr * ndR    },
        { label: 'Sat/Rest OT &le;10 hrs',  raw: d.satOt          || 0, rate: hr * restR  },
        { label: 'Sat/Rest OT &gt;10 hrs',  raw: d.satOtGt10      || 0, rate: hr * restR  },
        { label: 'Sat/Rest Night Prem',     raw: d.satNightPrem   || 0, rate: hr * ndR    },
        { label: 'Sun/Spl OT &le;10 hrs',   raw: d.sunOt          || 0, rate: hr * restR  },
        { label: 'Sun/Spl OT &gt;10 hrs',   raw: d.sunOtGt10      || 0, rate: hr * restR  },
        { label: 'Sun/Spl Night Prem',      raw: d.sunNightPrem   || 0, rate: hr * ndR    },
        { label: 'Hol. OT &le;10 hrs',     raw: d.holOt          || 0, rate: hr * holOtR },
        { label: 'Hol. OT &gt;10 hrs',     raw: d.holOtGt10      || 0, rate: hr * holOtR },
        { label: 'Hol. Night Premium',      raw: d.holNightPrem   || 0, rate: hr * ndR    },
    ];
    const otRowsHtml = otDefs.map(r => {
        const hrs = r.rate > 0 && r.raw > 0 ? Math.round(r.raw / r.rate) : 0;
        return `<tr><td style="${bp}">${r.label}</td><td style="${bp}text-align:center;">${hrs > 0 ? hrs : '-'}</td><td style="${bp}text-align:right;">${r.raw > 0 ? r.raw.toFixed(2) : '-'}</td></tr>`;
    }).join('');

    /* Fixed rows: basicPay + rate/hr + hoursWorked + nightPremium + absences + undertime + netBasicPay = 7, plus 12 OT rows */
    const rowspan = 19;
    const nightHrs = hr > 0 && (d.nightPremium || 0) > 0 ? Math.round(d.nightPremium / (hr * ndR)) : 0;
    const absDays  = hr > 0 && (d.absentAmt    || 0) > 0 ? Math.round(d.absentAmt    / (hr * 8))   : 0;
    const utHrs    = hr > 0 && (d.undertimeAmt || 0) > 0 ? Math.round(d.undertimeAmt / hr)          : 0;

    /* Taxable / Non-Taxable cell — show dash instead of blank when empty */
    const taxCell = (d.taxableOther || 0) > 0
        ? `<strong>Salary adjustment:</strong><br>${(d.taxableOther).toFixed(2)}`
        : '&mdash;';
    const ntParts = [];
    if ((d.nonTaxable      || 0) > 0) ntParts.push(`<strong>De Minimis Benefit</strong> &nbsp; ${(d.nonTaxable).toFixed(2)}`);
    if ((d.vlCashConversion || 0) > 0) ntParts.push(`<strong>VL Cash Conversion</strong> &nbsp; ${(d.vlCashConversion).toFixed(2)}`);
    const nonTaxCell = ntParts.length > 0 ? ntParts.join('<br>') : '&mdash;';

    return `
        <div style="font-family:Arial,sans-serif;background:#fff;border:1px solid #000;padding:20px;width:100%;box-sizing:border-box;font-size:12px;">
            ${logoHtml}
            <h2 style="text-align:center;margin:5px 0 10px;">PAY SLIP</h2>
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <tr>
                    <td style="${bp}"><strong>Employee:</strong></td>
                    <td style="${bp}">${escapeHtml(d.empName)}</td>
                    <td style="${bp}"></td>
                    <td style="${bp}"><strong>Payroll period:</strong></td>
                    <td style="${bp}" colspan="2">${d.formatStart} &ndash; ${d.formatEnd}</td>
                </tr>
                <tr><td style="${bp}"><strong>Position:</strong></td><td colspan="5" style="${bp}">${escapeHtml(d.position)}</td></tr>
            </table>
            <table style="width:100%;border-collapse:collapse;font-size:12px;margin-top:8px;">
                <tr>
                    <th colspan="3" style="${bp}background:#eee;text-align:center;">REGULAR EARNINGS</th>
                    <th style="${bp}background:#eee;text-align:center;">TAXABLE OTHER EARNINGS</th>
                    <th style="${bp}background:#eee;text-align:center;">NON-TAXABLE OTHER EARNINGS</th>
                </tr>
                <tr>
                    <th style="${bp}background:#f5f5f5;text-align:left;">Description</th>
                    <th style="${bp}background:#f5f5f5;text-align:center;">Days / Hours</th>
                    <th style="${bp}background:#f5f5f5;text-align:right;">Amount</th>
                    <th style="${bp}background:#f5f5f5;"></th>
                    <th style="${bp}background:#f5f5f5;"></th>
                </tr>
                <tr>
                    <td style="${bp}">Basic pay:</td>
                    <td style="${bp}text-align:center;"></td>
                    <td style="${bp}text-align:right;">${(d.basicPay || 0).toFixed(2)}</td>
                    <td rowspan="${rowspan}" style="${bp}vertical-align:top;padding:6px;">${taxCell}</td>
                    <td rowspan="${rowspan}" style="${bp}vertical-align:top;padding:6px;">${nonTaxCell}</td>
                </tr>
                <tr><td style="${bp}">Rate per hour</td><td style="${bp}text-align:center;">${(d.ratePerHour || 0).toFixed(2)}</td><td style="${bp}"></td></tr>
                <tr><td style="${bp}">No. of hours worked:</td><td style="${bp}text-align:center;">${Math.round(d.hoursWorked || 0)}</td><td style="${bp}"></td></tr>
                <tr><td style="${bp}">Night premium</td><td style="${bp}text-align:center;">${nightHrs > 0 ? nightHrs : '-'}</td><td style="${bp}text-align:right;">${(d.nightPremium || 0) > 0 ? (d.nightPremium).toFixed(2) : '-'}</td></tr>
                <tr><td style="${bp}">Less: Absences</td><td style="${bp}text-align:center;">${absDays > 0 ? absDays : '-'}</td><td style="${bp}text-align:right;">${(d.absentAmt || 0) > 0 ? (d.absentAmt).toFixed(2) : '-'}</td></tr>
                <tr><td style="${bp}">&nbsp;&nbsp;Late / Undertime</td><td style="${bp}text-align:center;">${utHrs > 0 ? utHrs : '-'}</td><td style="${bp}text-align:right;">${(d.undertimeAmt || 0) > 0 ? (d.undertimeAmt).toFixed(2) : '-'}</td></tr>
                <tr><td style="${bp}"><strong>Net basic pay</strong></td><td style="${bp}"></td><td style="${bp}text-align:right;"><strong>${(d.netBasicPay || 0).toFixed(2)}</strong></td></tr>
                ${otRowsHtml}
            </table>
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <tr>
                    <td colspan="3" style="${bp}text-align:right;"><strong>GROSS PAY</strong></td>
                    <td style="${bp}text-align:right;"><strong>${(d.grossPay || 0).toFixed(2)}</strong></td>
                </tr>
            </table>
            <table style="width:100%;border-collapse:collapse;font-size:12px;margin-top:8px;">
                <tr>
                    <th colspan="2" style="${bp}background:#eee;text-align:center;">REGULAR DEDUCTIONS</th>
                    <th colspan="2" style="${bp}background:#eee;text-align:center;">LOANS / ADVANCES</th>
                </tr>
                <tr><td style="${bp}">SSS</td><td style="${bp}text-align:right;">${(d.sssDed || 0).toFixed(2)}</td><td style="${bp}">SSS Salary Loan</td><td style="${bp}text-align:right;">${(d.sssLoan || 0) > 0 ? (d.sssLoan).toFixed(2) : '&mdash;'}</td></tr>
                <tr><td style="${bp}">PhilHealth</td><td style="${bp}text-align:right;">${(d.phicDed || 0).toFixed(2)}</td><td style="${bp}">Calamity Loan</td><td style="${bp}text-align:right;">${(d.calamityLoan || 0) > 0 ? (d.calamityLoan).toFixed(2) : '&mdash;'}</td></tr>
                <tr><td style="${bp}">HDMF / Pag-IBIG</td><td style="${bp}text-align:right;">${(d.hdmfDed || 0).toFixed(2)}</td><td style="${bp}">Salary Advance</td><td style="${bp}text-align:right;">${(d.salaryAdvance || 0) > 0 ? (d.salaryAdvance).toFixed(2) : '&mdash;'}</td></tr>
                <tr>
                    <td style="${bp}">Withholding Tax</td>
                    <td style="${bp}text-align:right;">${(d.withholdingTax || 0).toFixed(2)}</td>
                    <td colspan="2" style="${bp}text-align:right;"><strong>TOTAL DEDUCTIONS &nbsp; ${(d.totalDeductions || 0).toFixed(2)}</strong></td>
                </tr>
            </table>
            <table style="width:100%;border-collapse:collapse;font-size:12px;margin-top:8px;">
                <tr>
                    <td style="${bp}">Employee signature:</td>
                    <td style="${bp}height:35px;"></td>
                    <td style="${bp}text-align:right;"><strong>NET PAY:</strong></td>
                    <td style="${bp}text-align:right;background:#f8d7da;"><strong>${(d.netPay || 0).toFixed(2)}</strong></td>
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
    const regOt             = sp('edit_regOt');
    const regOtGt10         = sp('edit_regOtGt10');
    const regOtNightPrem    = sp('edit_regOtNightPrem');
    const satOt             = sp('edit_satOt');
    const satOtGt10         = sp('edit_satOtGt10');
    const satNightPrem      = sp('edit_satNightPrem');
    const sunOt             = sp('edit_sunOt');
    const sunOtGt10         = sp('edit_sunOtGt10');
    const sunNightPrem      = sp('edit_sunNightPrem');
    const holOt             = sp('edit_holOt');
    const holOtGt10         = sp('edit_holOtGt10');
    const holNightPrem      = sp('edit_holNightPrem');
    const vlCashConversion  = sp('edit_vlCashConversion');
    const taxableOther      = sp('edit_taxAdj');
    const nonTaxable        = sp('edit_nonTaxable');
    const sssDed            = sp('edit_sss');
    const phicDed           = sp('edit_phic');
    const hdmfDed           = sp('edit_hdmf');
    const withholdingTax    = sp('edit_wtax');
    const sssLoan           = sp('edit_sssLoan');
    const calamityLoan      = sp('edit_calamityLoan');
    const salaryAdvance     = sp('edit_advSalary');

    const totalDeductionsFromPay = Math.min(absentAmt + undertimeAmt, basicPay);
    const netBasicPay   = Math.max(0, basicPay - totalDeductionsFromPay + nightPremium);
    const totalOvertime = regOt + regOtGt10 + regOtNightPrem
                        + satOt + satOtGt10 + satNightPrem
                        + sunOt + sunOtGt10 + sunNightPrem
                        + holOt + holOtGt10 + holNightPrem;
    const grossPay      = netBasicPay + totalOvertime + taxableOther + nonTaxable + vlCashConversion;
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
        netBasicPay,
        regOt, regOtGt10, regOtNightPrem,
        satOt, satOtGt10, satNightPrem,
        sunOt, sunOtGt10, sunNightPrem,
        holOt, holOtGt10, holNightPrem,
        vlCashConversion, taxableOther, nonTaxable,
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
        overtime_pay: d.regOt + d.regOtGt10 + d.regOtNightPrem
                    + d.satOt + d.satOtGt10 + d.satNightPrem
                    + d.sunOt + d.sunOtGt10 + d.sunNightPrem
                    + d.holOt + d.holOtGt10 + d.holNightPrem,
        holiday_pay: safeParseFloat(document.getElementById('holidayPay')?.value),
        de_minimis: d.nonTaxable,
        vl_cash_conversion: d.vlCashConversion,
        other_earnings_taxable: d.taxableOther,
        other_earnings_non_taxable: d.vlCashConversion,
        sss_ee: d.sssDed,
        phic_ee: d.phicDed,
        hdmf_ee: d.hdmfDed,
        withholding_tax: d.withholdingTax,
        sss_loan: d.sssLoan,
        calamity_loan: d.calamityLoan,
        salary_advance: d.salaryAdvance,
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
            reg_ot:         d.regOt,
            reg_ot_gt10:    d.regOtGt10,
            reg_ot_night:   d.regOtNightPrem,
            sat_ot:         d.satOt,
            sat_ot_gt10:    d.satOtGt10,
            sat_night_prem: d.satNightPrem,
            sun_ot:         d.sunOt,
            sun_ot_gt10:    d.sunOtGt10,
            sun_night_prem: d.sunNightPrem,
            hol_ot:         d.holOt,
            hol_ot_gt10:    d.holOtGt10,
            hol_night_prem: d.holNightPrem,
            vl_cash_conversion: d.vlCashConversion,
            overtime_pay:  d.regOt + d.regOtGt10 + d.regOtNightPrem
                         + d.satOt + d.satOtGt10 + d.satNightPrem
                         + d.sunOt + d.sunOtGt10 + d.sunNightPrem
                         + d.holOt + d.holOtGt10 + d.holNightPrem,
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
            sss_loan:        d.sssLoan,
            calamity_loan:   d.calamityLoan,
            salary_advance:  d.salaryAdvance,
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
        reg_ot:         window.currentPayslipData.regOt || 0,
        reg_ot_gt10:    window.currentPayslipData.regOtGt10 || 0,
        reg_ot_night:   window.currentPayslipData.regOtNightPrem || 0,
        sat_ot:         window.currentPayslipData.satOt || 0,
        sat_ot_gt10:    window.currentPayslipData.satOtGt10 || 0,
        sat_night_prem: window.currentPayslipData.satNightPrem || 0,
        sun_ot:         window.currentPayslipData.sunOt || 0,
        sun_ot_gt10:    window.currentPayslipData.sunOtGt10 || 0,
        sun_night_prem: window.currentPayslipData.sunNightPrem || 0,
        hol_ot:         window.currentPayslipData.holOt || 0,
        hol_ot_gt10:    window.currentPayslipData.holOtGt10 || 0,
        hol_night_prem: window.currentPayslipData.holNightPrem || 0,
        overtime_pay: (window.currentPayslipData.regOt || 0) + (window.currentPayslipData.regOtGt10 || 0) + (window.currentPayslipData.regOtNightPrem || 0)
                    + (window.currentPayslipData.satOt || 0) + (window.currentPayslipData.satOtGt10 || 0) + (window.currentPayslipData.satNightPrem || 0)
                    + (window.currentPayslipData.sunOt || 0) + (window.currentPayslipData.sunOtGt10 || 0) + (window.currentPayslipData.sunNightPrem || 0)
                    + (window.currentPayslipData.holOt || 0) + (window.currentPayslipData.holOtGt10 || 0) + (window.currentPayslipData.holNightPrem || 0),
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
        vl_cash_conversion: window.currentPayslipData.vlCashConversion || 0,
        other_earnings_non_taxable: window.currentPayslipData.vlCashConversion || 0,
        gross_pay: window.currentPayslipData.grossPay,
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
        sss_salary_loan:      g('sssSalaryLoan'),
        sss_calamity_loan:    g('sssCalamityLoan'),
        salary_advance:       g('advSalary'),
        calamity_advance:     g('advCalamity'),
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
        sss_loan_salary:           g('regSssLoanSalary'),
        sss_loan_calamity:         g('regSssLoanCalamity'),
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
    // Auto-detect period type and update contributions/prior fields when date or type changes
    document.getElementById('payslipPeriodStart')?.addEventListener('change', () => { autoDetectPeriodType(); fetchAndSetPriorHalfWT(); });
    document.getElementById('payslipPeriodType')?.addEventListener('change', fetchAndSetPriorHalfWT);
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

    // ── Raw Sheet: hours inputs auto-calculate PHP equivalents ──
    // Reads current hourly/daily rate and payroll settings at event time so
    // the calculation is always fresh even if the employee was just changed.
    function _rawHr() {
        const hr = safeParseFloat(document.getElementById('hourlyRate')?.value);
        if (hr) return hr;
        const monthly = safeParseFloat(document.getElementById('monthlySalary')?.value);
        return monthly > 0 ? monthly / 174 : 0;
    }
    function _rawDaily() {
        const d = safeParseFloat(document.getElementById('dailyRate')?.value);
        if (d) return d;
        const monthly = safeParseFloat(document.getElementById('monthlySalary')?.value);
        return monthly > 0 ? monthly / 21.75 : 0;
    }
    function _rs(key, fallback) { return parseFloat(window._payrollSettings?.[key]) || fallback; }

    document.getElementById('workdayOtHours')?.addEventListener('input', function() {
        const pay = safeParseFloat(this.value) * _rawHr() * _rs('ot_regular_rate', 1.275);
        document.getElementById('workdayOtPhp').value = pay.toFixed(2);
    });
    document.getElementById('workdayNightDiffDays')?.addEventListener('input', function() {
        const pay = safeParseFloat(this.value) * _rawHr() * _rs('night_diff_rate', 0.10);
        document.getElementById('workdayNightDiffPhp').value = pay.toFixed(2);
    });
    document.getElementById('satOtHours')?.addEventListener('input', function() {
        const pay = safeParseFloat(this.value) * _rawHr() * _rs('ot_rest_rate', 1.69);
        document.getElementById('satOtPhp').value = pay.toFixed(2);
    });
    document.getElementById('utHours')?.addEventListener('input', function() {
        document.getElementById('utPhp').value = (safeParseFloat(this.value) * _rawHr()).toFixed(2);
    });
    document.getElementById('absentDays')?.addEventListener('input', function() {
        document.getElementById('absentPhp').value = (safeParseFloat(this.value) * _rawDaily()).toFixed(2);
    });
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