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
        min-height:300px;
    }

    /* Bulk table containers (Raw Sheet / Register / Payslip list) — sized close to
       the full viewport by default so it reads like a full-screen table without
       actually engaging the browser's Fullscreen API. */
    .bulk-table-scroll{
        overflow-x:auto;
        overflow-y:auto;
        max-height:88vh;
    }
    /* When the parent view is put into fullscreen, use the full screen height instead */
    [id$="-view"]:fullscreen{
        background:#fff;
        padding:16px;
        overflow-y:auto;
    }
    [id$="-view"]:fullscreen .bulk-table-scroll{
        max-height:none;
    }

    /* Hide individual panels when bulk mode is active */
    body.bulk-active #individual-mode,
    body.bulk-active #individual-register-mode,
    body.bulk-active #individual-payslip-mode,
    body.bulk-active #individual-employee-section,
    body.bulk-active #individualActionBtns { display:none !important; }

    /* Bulk mode stacks a couple of nested padded containers that each eat real
       editing space with margin you can't use. Shrink them (spacing only, not
       a look/design change) so the table gets more of the actual screen.
       NOTE: does NOT touch <body> itself — this page is embedded inside the
       app shell's own body (h-screen fixed-height layout), and padding the
       real <body> broke that layout, making the whole page appear to shift. */
    body.bulk-active #payrollOuterCard{ padding:12px !important; }
    body.bulk-active [data-panel="generate-payslip"]{ padding:8px !important; }
    /* Padding differs between Individual/Bulk, so switching modes resizes the
       card — animate that instead of letting it jump instantly. */
    #payrollOuterCard,
    [data-panel="generate-payslip"]{ transition:padding 0.45s cubic-bezier(0.4,0,0.2,1); }

    /* Table height is set dynamically via JS (see _fillTableToViewport) so it
       fills exactly to the bottom of the screen wherever it lands, instead of
       a fixed max-height that may leave blank space or run past the fold. */
    .bulk-table-scroll{ transition:max-height 0.35s cubic-bezier(0.4,0,0.2,1); }

    /* Smooth scrolling for both the page and the table's own scroll box. */
    #payrollOuterCard, .bulk-table-scroll{ scroll-behavior:smooth; }

    /* Fade+slide-in used whenever a panel gets unhidden via JS (setup summary
       collapsing/expanding, review details toggling, tables appearing after
       generate) so content eases in instead of popping into place. */
    .bulk-fade-in{ animation:bulkFadeIn 0.3s cubic-bezier(0.2,0.8,0.2,1); }
    @keyframes bulkFadeIn{
        from{ opacity:0; transform:translateY(6px); }
        to{ opacity:1; transform:translateY(0); }
    }

    /* Smooth hover/press feedback on the controls used throughout the bulk flow */
    .mode-btn, .period-btn, #bulkGenerateBtn, #bulkSendAllBtn, #bulkExportBtn,
    #bulk-panel button, .bulk-table-scroll button{
        transition:background-color 0.15s ease, transform 0.1s ease, opacity 0.15s ease;
    }
    #bulk-panel button:active, .bulk-table-scroll button:active{ transform:scale(0.97); }

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

/* Toast notifications now provided globally by css/toast.css + js/components/toast.js */

.loan-category-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-top: 15px;
}

.loan-category {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 15px;
    background: #fafafa;
}

.loan-category h4 {
    margin-bottom: 15px;
    font-size: 15px;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 8px;
}

.loan-category label {
    display: block;
    margin-bottom: 5px;
    font-size: 13px;
}

.loan-category input {
    width: 100%;
    margin-bottom: 12px;
}
</style>
</head>
<body class="p-6">

<div id="payrollOuterCard" class="bg-white rounded-2xl shadow-lg p-8 h-full overflow-y-auto border border-gray-300 flex flex-col">

    <!-- GLOBAL MODE SWITCH -->
    <div class="mode-switch" style="margin-bottom:10px;">
        <button type="button" class="mode-btn active" onclick="switchMode('individual', event)">Individual</button>
        <button type="button" class="mode-btn" onclick="switchMode('bulk', event)">Bulk Send</button>
    </div>

    <!-- BULK PANEL — shown above all tabs when in Bulk mode -->
    <div id="bulk-panel" style="display:none;margin-bottom:14px;">

        <!-- Compact summary bar — shown once generated, instead of the full setup below -->
        <div id="bulkSetupSummary" style="display:none;align-items:center;gap:12px;flex-wrap:wrap;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:10px;padding:10px 14px;margin-bottom:12px;">
            <span id="bulkSetupSummaryText" style="font-size:13px;font-weight:600;color:#4c1d95;"></span>
            <button type="button" onclick="toggleBulkSetup(true)" style="margin-left:auto;font-size:12px;padding:5px 12px;border:1px solid #c4b5fd;border-radius:6px;background:#fff;color:#6d28d9;cursor:pointer;font-weight:600;">Change Selection</button>
        </div>

        <!-- Full setup — employee picker, period, actions. Collapses after a successful generate. -->
        <div id="bulkSetupSection">
            <div style="display:grid;grid-template-columns:280px 1fr;gap:16px;align-items:start;">
                <!-- Left: employee checklist -->
                <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:12px;">
                    <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:8px;">Select Employees</div>
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:8px;">
                        <button type="button" onclick="bulkSelectAll()" style="font-size:12px;padding:4px 10px;border:1px solid #d1d5db;border-radius:6px;background:#fff;cursor:pointer;">All</button>
                        <button type="button" onclick="bulkDeselectAll()" style="font-size:12px;padding:4px 10px;border:1px solid #d1d5db;border-radius:6px;background:#fff;cursor:pointer;">Clear</button>
                        <input type="text" id="bulkListSearch" placeholder="Filter…"
                            oninput="filterBulkList(this.value)"
                            style="flex:1;padding:5px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:12px;">
                    </div>
                    <div id="bulkEmployeeList" style="max-height:180px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:8px;background:#fff;">
                        <div style="padding:14px;text-align:center;color:#9ca3af;font-size:13px;">Loading…</div>
                    </div>
                    <div style="margin-top:6px;font-size:12px;color:#6b7280;" id="bulkSelectionCount">0 employees selected</div>
                </div>

                <!-- Right: period + actions -->
                <div>
                    <div style="display:grid;grid-template-columns:150px 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Month</label>
                            <input type="month" id="bulkPeriodMonth" value="<?= $_defaultYearMonth ?>"
                                style="height:36px;padding:5px 8px;border:1px solid #cfcfcf;border-radius:8px;font-size:13px;background:#e9e9e9;width:100%;"
                                onchange="syncBulkPeriod()">
                        </div>
                        <div>
                            <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Period</label>
                            <div class="period-btn-group">
                                <button type="button" class="period-btn bulk-period-btn <?= $_defaultPeriod === 'first_half'  ? 'active' : '' ?>" data-period="first_half"  onclick="selectBulkPeriodBtn(this)">1st Half</button>
                                <button type="button" class="period-btn bulk-period-btn <?= $_defaultPeriod === 'second_half' ? 'active' : '' ?>" data-period="second_half" onclick="selectBulkPeriodBtn(this)">2nd Half</button>
                                <button type="button" class="period-btn bulk-period-btn" data-period="monthly" onclick="selectBulkPeriodBtn(this)">Monthly</button>
                            </div>
                            <div id="bulkPeriodDisplay" style="margin-top:4px;font-size:12px;color:#6b7280;"></div>
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;flex-wrap:wrap;">
                        <button type="button" id="bulkGenerateBtn" onclick="startBulkGenerate()"
                            style="background:#7b5cff;color:#fff;border:none;border-radius:8px;padding:9px 20px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;">
                            <span id="bulkBtnText">Generate Payslips</span>
                            <span id="bulkBtnSpinner" style="display:none;">⏳</span>
                        </button>
                        <button type="button" id="bulkSendAllBtn" onclick="sendBulkAll()" style="display:none;
                            background:#16a34a;color:#fff;border:none;border-radius:8px;padding:9px 20px;font-size:13px;font-weight:600;cursor:pointer;">
                            ✉ Send All
                        </button>
                        <button type="button" id="bulkExportBtn" onclick="exportBulkCSV()" style="display:none;
                            background:#374151;color:#fff;border:none;border-radius:8px;padding:9px 20px;font-size:13px;font-weight:600;cursor:pointer;">
                            ↓ Export CSV
                        </button>
                        <span id="bulkStatusMsg" style="font-size:13px;color:#6b7280;"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Review table — collapses to a one-line summary once generated, same
             pattern as the setup panel above, so it doesn't stay in the way of
             the actual table below. -->
        <div id="bulkReviewPanel" style="display:none;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
            <div id="bulkReviewSummary" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:8px 12px;font-size:13px;font-weight:600;color:#374151;background:#f9fafb;"></div>
            <div id="bulkReviewDetails" style="display:none;">
                <div style="display:grid;grid-template-columns:2fr 1.1fr 1.1fr 1.2fr 90px;background:#DCDCDC;color:#737373;font-size:13px;font-weight:700;border-top:1px solid #e5e7eb;border-bottom:1px solid #9E9E9E;">
                    <div style="padding:8px 12px;">Employee</div>
                    <div style="padding:8px 12px;">Gross Pay</div>
                    <div style="padding:8px 12px;">Deductions</div>
                    <div style="padding:8px 12px;">Net Pay</div>
                    <div style="padding:8px 12px;text-align:center;">View</div>
                </div>
                <div id="bulkReviewRows"></div>
            </div>
        </div>
    </div>

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

    <div class="flex flex-col flex-1" style="min-height:0;overflow:visible;">

        <!-- ================= RAW SHEET ================= -->
        <div class="tab-content active payroll-scroll-area payroll-scroll-hide" id="raw">

            <!-- Employee Selection (individual mode only) -->
            <div class="section" id="individual-employee-section">
                <h3 class="font-semibold">Employee Selection</h3>
                <div class="employee-search-container">
                    <label>Employee Name</label>
                    <input type="text" id="empSearchInput" placeholder="Type to search..." autocomplete="off">
                    <div id="employeeSuggestions" class="employee-suggestions payroll-scroll-hide"></div>
                    <input type="hidden" id="selectedEmployeeId" value="">
                </div>
                <div class="mt-3">
                    <label>Selected Employee</label>
                    <input type="text" id="empName" placeholder="—" readonly class="bg-gray-200">
                </div>
            </div>

            <div class="flex justify-end mb-4 gap-2" id="individualActionBtns">
                <button type="button" class="auto-fill-btn" onclick="autoFillRawSheet()">Auto-fill from Employee</button>
                <button type="button" class="next-btn" id="exportRawSheetBtn" style="background:#28a745;">Export Raw Sheet</button>
            </div>

            <!-- Bulk mode notice (shown inside raw tab when bulk is active) -->
            <div id="bulk-raw-notice" style="display:none;background:#f0f4ff;border:1px solid #c7d2fe;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#3730a3;">
                &#8593; Bulk mode active — select employees and period above, then click <strong>Generate Payslips</strong> to populate the table below.
            </div>

            <!-- Bulk raw sheet table — shown after generate in bulk mode -->
            <div id="bulk-raw-view" style="display:none;overflow-x:auto;margin-bottom:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:8px;">
                    <h3 class="font-semibold text-lg">Raw Sheet — All Employees</h3>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <label for="bulkRawImportFile" style="cursor:pointer;background:#374151;color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;">&#8679; Import CSV</label>
                        <input type="file" id="bulkRawImportFile" accept=".csv" style="display:none;" onchange="importBulkCSV('raw',this)">
                        <button type="button" onclick="exportBulkCSV('raw')" style="background:#28a745;color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;cursor:pointer;">&#8681; Export CSV</button>
                        <button type="button" onclick="toggleBulkFullscreen('bulk-raw-view')" style="background:#7b5cff;color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;cursor:pointer;">&#10021; Full Screen</button>
                    </div>
                </div>
                <div id="bulk-raw-tbody" class="bulk-table-scroll"></div>
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
                        <input type="hidden" id="otHourlyRate" value="0">
                    </div>
                </div>

            <!-- DE MINIMIS BENEFIT -->
            <div class="section rs-card">
                <h3 class="font-semibold">De Minimis Benefit</h3>
                <div class="grid-3">
                    <div><label>Year End Gifts</label><input type="number" id="yearEndGifts" value="0"></div>
                    <div><label>Rice Subsidy</label><input type="number" id="riceSubsidy" value="0"></div>
                    <div><label>Uniform / Clothing Allowance</label><input type="number" id="uniformAllowance" value="0"></div>
                    <div><label>Laundry Allowance</label><input type="number" id="laundryAllowance" value="0"></div>
                    <div><label>Meal Allowance</label><input type="number" id="mealAllowance" value="0"></div>
                    <div><label>Medical Allowance</label><input type="number" id="medicalAllowance" value="0"></div>
                    <div><label>Medical Allowance (Additional)</label><input type="number" id="medicalAllowanceAdd" value="0"></div>
                    <div><label>Other Non-Taxable</label><input type="number" id="salaryAdjustmentNonTax" value="0"></div>
                </div>
            </div>

            <!-- OTHER NON-SOCIAL COMPENSATION -->
            <div class="section rs-card">
                <h3 class="font-semibold">Other Non-Social Compensation</h3>
                <div class="grid-3">
                    <div><label>Salary Adjustment (Taxable)</label><input type="number" id="salaryAdjustment" value="0"></div>
                    <div><label>Salary Adjustment (Non-Taxable)</label><input type="number" id="salNonTaxAdj" value="0"></div>
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
                    <div></div>
                </div>
                <input type="hidden" id="holidayPay" value="0">
            </div>

            <!-- LOANS + ADVANCES & UNDERTIME / ABSENT — side by side -->
            <div class="grid-2 section">
                <div class="rs-card">
                    <h3 class="font-semibold">Loans &amp; Advances</h3>
                    <div class="loan-category-grid">
                        <!-- SSS -->
                        <div class="loan-category">
                            <h4>SSS Loans</h4>

                            <div>
                                <label>Salary Loan</label>
                                <input type="number" id="sssSalaryLoan" value="0">
                            </div>

                            <div>
                                <label>Calamity Loan</label>
                                <input type="number" id="sssCalamityLoan" value="0">
                            </div>
                        </div>

                        <!-- HDMF -->
                        <div class="loan-category">
                            <h4>HDMF / MP2</h4>

                            <div>
                                <label>HDMF Loan</label>
                                <input type="number" id="hdmfLoan" value="0">
                            </div>

                            <div>
                                <label>Multi-Purpose Loan</label>
                                <input type="number" id="hdmfMplLoan" value="0">
                            </div>

                            <div>
                                <label>Calamity Loan</label>
                                <input type="number" id="hdmfCalamityLoan" value="0">
                            </div>
                        </div>

                        <!-- Advances -->
                        <div class="loan-category">
                            <h4>Advances</h4>

                            <div>
                                <label>Salary Advance</label>
                                <input type="number" id="advSalary" value="0">
                            </div>

                            <div>
                                <label>Cash Advance</label>
                                <input type="number" id="advCash" value="0">
                            </div>

                            <div>
                                <label>Emergency Advance</label>
                                <input type="number" id="advEmergency" value="0">
                            </div>
                        </div>

                    </div>
                </div>
                <div class="rs-card">
                    <h3 class="font-semibold">Undertime &amp; Absences</h3>
                    <div class="grid-2">
                        <div><label>Undertime Hours</label><input type="number" id="utHours" value="0"></div>
                        <div><label>Undertime (PHP)</label><input type="number" id="utPhp" value="0"></div>
                        <div><label>Absent Day(s)</label><input type="number" id="absentDays" value="0"></div>
                        <div><label>Absent (PHP)</label><input type="number" id="absentPhp" value="0"></div>
                        <div><label>Break Hours <span style="font-weight:400;color:#777;font-size:11px;">(reference only)</span></label><input type="number" id="breakHoursRef" value="0" readonly style="background:#f3f4f6;"></div>
                    </div>
                </div>
            </div>

            <!-- OVERTIME & NIGHT DIFFERENTIAL — 2×2 compact grid -->
            <div class="section">
                <h3 class="font-semibold mb-2">Overtime &amp; Night Differential</h3>
                <div class="ot-grid">

                    <!-- Regular Workday -->
                    <div class="ot-card">
                        <div class="ot-card-title">Workday OT &amp; NSD <span style="font-weight:400;color:#777;font-size:11px;">OT: Hourly rate &times; 125% &nbsp;|&nbsp; NSD: Hourly rate &times; 10%</span></div>
                        <div class="ot-col-hdrs"><span>Description</span><span>Hours</span><span>PHP Amount</span></div>
                        <input type="hidden" id="workdayNightDiffDays" value="0">
                        <input type="hidden" id="regOtNightHours" value="0">
                        <input type="hidden" id="regOtNightPhp" value="0">
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
                            <label>Night Differential
                                <span class="ot-rate-hint">Hourly rate &times; 10% &times; night hrs</span>
                            </label>
                            <input type="number" id="workdayNightDiffHours" value="0" oninput="calcRawNightPay('workdayNightDiffHours','workdayNightDiffPhp')">
                            <input type="number" id="workdayNightDiffPhp" value="0">
                        </div>
                    </div>

                    <!-- Sat / Rest-Day -->
                    <div class="ot-card">
                        <div class="ot-card-title">Sat / Rest-Day OT <span style="font-weight:400;color:#777;font-size:11px;">&le;8 hrs: 130% &nbsp;|&nbsp; &gt;8 hrs: 169%</span></div>
                        <div class="ot-col-hdrs"><span>Description</span><span>Hours</span><span>PHP Amount</span></div>
                        <div class="ot-field-row">
                            <label>Regular hrs &le;8
                                <span class="ot-rate-hint">Hourly rate &times; 130% &times; hrs</span>
                            </label>
                            <input type="number" id="satOtHours" value="0" oninput="calcRawOtPay('satOtHours','satOtPhp','restDayBase')">
                            <input type="number" id="satOtPhp" value="0">
                        </div>
                        <div class="ot-field-row">
                            <label>OT hrs &gt;8
                                <span class="ot-rate-hint">Hourly rate &times; 169% &times; hrs past 8</span>
                            </label>
                            <input type="number" id="satOtGt10Hours" value="0" oninput="calcRawOtPay('satOtGt10Hours','satOtGt10Php','restOt')">
                            <input type="number" id="satOtGt10Php" value="0">
                        </div>
                        <div class="ot-field-row">
                            <label>Night Premium
                                <span class="ot-rate-hint">Hourly rate &times; 130% &times; 10%</span>
                            </label>
                            <input type="number" id="satNightDiffHours" value="0" oninput="calcRawNightPay('satNightDiffHours','satNightDiffPhp','restDay')">
                            <input type="number" id="satNightDiffPhp" value="0">
                        </div>
                    </div>

                    <!-- Sunday -->
                    <div class="ot-card">
                        <div class="ot-card-title">Sunday OT <span style="font-weight:400;color:#777;font-size:11px;">&le;8 hrs: 130% &nbsp;|&nbsp; &gt;8 hrs: 169% &nbsp;|&nbsp; Special Holiday: see Holiday Pay section</span></div>
                        <div class="ot-col-hdrs"><span>Description</span><span>Hours</span><span>PHP Amount</span></div>
                        <div class="ot-field-row">
                            <label>Regular hrs &le;8
                                <span class="ot-rate-hint">Hourly rate &times; 130% &times; hrs</span>
                            </label>
                            <input type="number" id="sunOtHours" value="0" oninput="calcRawOtPay('sunOtHours','sunOtPhp','restDayBase')">
                            <input type="number" id="sunOtPhp" value="0">
                        </div>
                        <div class="ot-field-row">
                            <label>OT hrs &gt;8
                                <span class="ot-rate-hint">Hourly rate &times; 169% &times; hrs past 8</span>
                            </label>
                            <input type="number" id="sunOtGt10Hours" value="0" oninput="calcRawOtPay('sunOtGt10Hours','sunOtGt10Php','restOt')">
                            <input type="number" id="sunOtGt10Php" value="0">
                        </div>
                        <div class="ot-field-row">
                            <label>Night Premium
                                <span class="ot-rate-hint">Hourly rate &times; 30% &times; 10% = 3%</span>
                            </label>
                            <input type="number" id="sunNightDiffHours" value="0" oninput="calcRawNightPay('sunNightDiffHours','sunNightDiffPhp','restDayAdd')">
                            <input type="number" id="sunNightDiffPhp" value="0">
                        </div>
                    </div>

                    <!-- Regular Holiday -->
                    <div class="ot-card">
                        <div class="ot-card-title">Regular Holiday Pay <span style="font-weight:400;color:#777;font-size:11px;">&le;8 hrs: +100% (additional premium) &nbsp;|&nbsp; OT &gt;8 hrs: 260%</span></div>
                        <div class="ot-col-hdrs"><span>Description</span><span>Hours</span><span>PHP Amount</span></div>
                        <div class="ot-field-row">
                            <label>Regular hrs &le;8
                                <span class="ot-rate-hint">Hourly rate &times; 100% &times; hrs (additional premium; basic covers the other 100%)</span>
                            </label>
                            <input type="number" id="holOtHours" value="0" oninput="calcRawOtPay('holOtHours','holOtPhp','holBase')">
                            <input type="number" id="holOtPhp" value="0">
                        </div>
                        <div class="ot-field-row">
                            <label>OT hrs &gt;8
                                <span class="ot-rate-hint">Hourly rate &times; 260% &times; hrs past 8</span>
                            </label>
                            <input type="number" id="holOtGt10Hours" value="0" oninput="calcRawOtPay('holOtGt10Hours','holOtGt10Php','holOt')">
                            <input type="number" id="holOtGt10Php" value="0">
                        </div>
                        <div class="ot-field-row">
                            <label>Night Premium
                                <span class="ot-rate-hint">Hourly rate &times; 200% &times; 10%</span>
                            </label>
                            <input type="number" id="holNightDiffHours" value="0" oninput="calcRawNightPay('holNightDiffHours','holNightDiffPhp','holiday')">
                            <input type="number" id="holNightDiffPhp" value="0">
                        </div>
                    </div>

                    <!-- Special Holiday -->
                    <div class="ot-card">
                        <div class="ot-card-title">Special Holiday <span style="font-weight:400;color:#777;font-size:11px;">&le;8 hrs: +30% &nbsp;|&nbsp; &gt;8 hrs: 169%</span></div>
                        <div class="ot-col-hdrs"><span>Description</span><span>Hours</span><span>PHP Amount</span></div>
                        <div class="ot-field-row">
                            <label>Regular hrs &le;8
                                <span class="ot-rate-hint">+30% extra (basic salary covers first 100%)</span>
                            </label>
                            <input type="number" id="specHolHours" value="0" oninput="calcHolPay()">
                            <input type="number" id="specHolPay" value="0" oninput="updateHolidayTotal();">
                        </div>
                        <div id="specHolHint" style="display:none;margin-top:5px;font-size:11.5px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;padding:6px 10px;">
                            <span style="font-weight:600;color:#166534;">+30% extra: <span id="specHolHintAmt"></span></span>
                            <span title="Monthly-paid employees already receive 100% of this day in their basic salary. Only the 30% premium is added here." style="cursor:help;color:#6b7280;margin-left:4px;">ⓘ</span>
                            <div style="color:#4b5563;margin-top:3px;font-size:11px;">└─ This is just the 30% extra. Total for this day&nbsp;=&nbsp;130%.</div>
                        </div>
                        <div class="ot-field-row">
                            <label>OT hrs &gt;8
                                <span class="ot-rate-hint">Hourly rate &times; 169% &times; hrs past 8</span>
                            </label>
                            <input type="number" id="specHolOtHours" value="0" oninput="calcHolPay()">
                            <input type="number" id="specHolOtPay" value="0" oninput="updateHolidayTotal()">
                        </div>
                        <div class="ot-field-row">
                            <label>Night Premium
                                <span class="ot-rate-hint">Hourly rate &times; 130% &times; 10% = 13%</span>
                            </label>
                            <input type="number" id="specHolNsdHours" value="0" oninput="calcRawNightPay('specHolNsdHours','specHolNsdPay','restDay')">
                            <input type="number" id="specHolNsdPay" value="0" oninput="updateHolidayTotal()">
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
            </div><!-- /#individual-mode -->
        </div>

        <!-- ================= PAYROLL REGISTER ================= -->
        <div class="tab-content payroll-scroll-area payroll-scroll-hide" id="register">

            <!-- Bulk register table — shown after generate in bulk mode -->
            <div id="bulk-register-view" style="display:none;overflow-x:auto;margin-bottom:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:8px;">
                    <h3 class="font-semibold text-lg">Payroll Register — All Employees</h3>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <label for="bulkRegImportFile" style="cursor:pointer;background:#374151;color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;">&#8679; Import CSV</label>
                        <input type="file" id="bulkRegImportFile" accept=".csv" style="display:none;" onchange="importBulkCSV('register',this)">
                        <button type="button" onclick="exportBulkCSV('register')" style="background:#28a745;color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;cursor:pointer;">&#8681; Export CSV</button>
                        <button type="button" onclick="toggleBulkFullscreen('bulk-register-view')" style="background:#7b5cff;color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;cursor:pointer;">&#10021; Full Screen</button>
                    </div>
                </div>
                <!-- Table injected by _buildRegisterTable() -->
                <div id="bulk-register-tbody" class="bulk-table-scroll"></div>
            </div>

            <div id="individual-register-mode">
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
                        <div><label>Meal Allowance</label><input type="number" id="deMinMealAllowance" value="0"></div>
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
                        <div><label>Non-Taxable Compensation</label><input type="number" id="grossNonTaxableComp" value="0"></div>
                    </div>
                </div>

                <!-- Employee's Share (Government) -->
                <div class="rs-card">
                    <h3 class="font-semibold">Employee's Government Contributions <span id="reg-contrib-note" style="font-size:11px;font-weight:normal;color:#888;margin-left:8px;"></span></h3>
                    <div class="grid-3">
                        <div><label>SSS &mdash; RSS</label><input type="number" id="sssEE" value="0"></div>
                        <div><label>SSS &mdash; WISP</label><input type="number" id="sssWisp" value="0"></div>
                        <div><label>SSS &mdash; Total</label><input type="number" id="sssTotal" value="0"></div>
                        <div><label>PhilHealth (PHIC)</label><input type="number" id="phicEE" value="0"></div>
                        <div><label>Pag-IBIG (HDMF)</label><input type="number" id="hdmfEE" value="0"></div>
                    </div>
                    <div class="grid-3" style="margin-top:10px;">
                        <div><label id="reg-netTaxable-lbl">Regular Net Taxable Comp.</label><input type="number" id="regNetTaxable" value="0"></div>
                        <div id="reg-netTaxProv-wrap"><label>Net Taxable Comp. (1st Half)</label><input type="number" id="netTaxProv" value="0"></div>
                        <div><label id="reg-monthlyTax-lbl">Monthly Taxable Compensation</label><input type="number" id="monthlyTaxableComp" value="0"></div>
                    </div>
                </div>

                <!-- Withholding Tax -->
                <div class="rs-card">
                    <h3 class="font-semibold">Withholding Tax <span id="reg-wt-method-note" style="font-size:11px;font-weight:normal;color:#888;margin-left:8px;"></span></h3>
                    <div class="grid-3">
                        <div><label>Tax Bracket</label><input type="text" id="wtBracket" placeholder="---"></div>
                        <div><label>Base Amount</label><input type="number" id="wtBasic" value="0"></div>
                        <div><label>Rate (%)</label><input type="text" id="wtPercentage" placeholder="---"></div>
                        <div><label>Fixed Tax</label><input type="number" id="wtBasic2" value="0"></div>
                        <div><label>Excess Over Bracket</label><input type="number" id="wtExcess" value="0"></div>
                    </div>
                    <div class="grid-2" style="margin-top:10px;">
                        <div id="reg-wtPaidLast-wrap"><label>WT Paid Last Cut-Off (1st Half)</label><input type="number" id="wtPaidLast" value="0"></div>
                        <div><label>WT Payable This Period</label><input type="number" id="wtPayable" value="0"></div>
                    </div>
                </div>

                <!-- Loans, Advances & Totals -->
                <div class="rs-card">
                    <h3 class="font-semibold">Loans, Advances &amp; Totals</h3>
                    <div class="grid-3">
                        <div><label>SSS Salary Loan</label><input type="number" id="regSssLoanSalary" value="0"></div>
                        <div><label>SSS Calamity Loan</label><input type="number" id="regSssLoanCalamity" value="0"></div>
                        <div><label>HDMF Loan</label><input type="number" id="regHdmfLoan" value="0"></div>
                        <div><label>Multi-Purpose Loan</label><input type="number" id="regHdmfMplLoan" value="0"></div>
                        <div><label>HDMF Calamity Loan</label><input type="number" id="regHdmfCalamityLoan" value="0"></div>
                        <div><label>Salary Advance</label><input type="number" id="regAdvSalary" value="0"></div>
                        <div><label>Cash Advance</label><input type="number" id="regAdvCash" value="0"></div>
                        <div><label>Emergency Advance</label><input type="number" id="regAdvEmergency" value="0"></div>
                        <div><label>Non-Taxable Compensation</label><input type="number" id="advNonTaxable" value="0"></div>
                        <div><label>Net Pay</label><input type="number" id="netPayRegister" value="0"></div>
                        <div><label>SSS Contribution (ER)</label><input type="number" id="sssContribution" value="0"></div>
                        <div><label>PHIC Contribution (ER)</label><input type="number" id="phicContribution" value="0"></div>
                        <div><label>HDMF Contribution (ER)</label><input type="number" id="hdmfContribution" value="0"></div>
                    </div>
                </div>

                <div class="button-container">
                    <button type="button" class="next-btn" onclick="syncToPayslip()">Next &rarr; Payslip</button>
                </div>
            </div>
            </div><!-- /#individual-register-mode -->
        </div>

        <!-- ================= PAYSLIP TAB ================= -->
        <div class="tab-content payroll-scroll-area payroll-scroll-hide" id="payslip">

            <!-- Bulk payslip list — shown after generate in bulk mode -->
            <div id="bulk-payslip-view" style="display:none;margin-bottom:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:8px;">
                    <h3 class="font-semibold text-lg">Payslips — All Employees</h3>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="button" onclick="sendBulkAll()" style="background:#16a34a;color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;cursor:pointer;">&#9993; Send All</button>
                        <button type="button" onclick="exportBulkCSV('register')" style="background:#28a745;color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;cursor:pointer;">&#8681; Export CSV</button>
                        <button type="button" onclick="toggleBulkFullscreen('bulk-payslip-view')" style="background:#7b5cff;color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:13px;font-weight:600;cursor:pointer;">&#10021; Full Screen</button>
                    </div>
                </div>
                <div id="bulk-payslip-list" class="bulk-table-scroll" style="display:grid;gap:8px;">
                    <div style="text-align:center;padding:20px;color:#9ca3af;font-size:13px;">Generate payslips to see list</div>
                </div>
            </div>

            <div id="individual-payslip-mode">
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
            <input type="hidden" id="edit_priorDeMin" value="0">
            <input type="hidden" id="edit_priorGrossTotal" value="0">

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
            </div><!-- /#individual-payslip-mode -->
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
            <input type="file" id="timesheetImportFile" name="timesheet_file" accept=".csv" required>
            <p class="text-xs text-gray-500 mt-2">Expected columns: employee_number, date, time_in, time_out, break_duration</p>

            <div id="importColumnMapping" style="display:none; margin-top:14px;">
                <p style="font-size:13px; color:#555; margin-bottom:8px;">
                    This file's columns don't match ours automatically — confirm which column is which below.
                </p>
                <div id="importColumnMappingFields" style="display:flex; flex-direction:column; gap:8px;"></div>
            </div>

            <div id="importEmployeeResolution" style="display:none; margin-top:14px;">
                <p style="font-size:13px; color:#555; margin-bottom:8px;">
                    These employee numbers in the file don't match anyone — pick who each one really is, or leave it to skip those rows.
                </p>
                <div id="importEmployeeResolutionFields" style="display:flex; flex-direction:column; gap:8px;"></div>
            </div>

            <div style="margin-top:10px; display:flex; gap:8px;">
                <button type="button" id="importMappingBackBtn" style="display:none; background:#e9ecef; color:#333; padding:6px 12px; border:none; border-radius:5px;">Back</button>
                <button type="submit" id="importTimesheetSubmitBtn" style="background:#007bff; color:white; padding:6px 12px; border:none; border-radius:5px;">Upload</button>
            </div>
        </form>
    </div>
</div>

<script>
// Toast notifications: window.showToast(message, type) now provided globally by js/components/toast.js

// ================= GLOBAL VARIABLES =================
let employeesList = [];
let selectedEmployeeIds = new Set();

// Logo URL injected from PHP
var companyLogoUrl = <?= json_encode($companyLogoUrl) ?>;

// ================= TAB NAVIGATION =================
function nextTab(tabId){
    document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    document.querySelectorAll('.sub-tab').forEach(t=>t.classList.remove('active'));
    document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
    if(tabId === 'payslip') buildPayslipTable();
    // A different table is now visible — resize it to fill the screen too.
    setTimeout(_fillTableToViewport, 0);
}

document.querySelectorAll('.sub-tab').forEach(btn=>{
    btn.onclick = () => nextTab(btn.dataset.tab);
});

// Replays the fade-in animation on an element each time it's unhidden
// (re-adding the same class doesn't restart a CSS animation, so force a reflow).
function _fadeInEl(el) {
    if (!el) return;
    el.classList.remove('bulk-fade-in');
    void el.offsetWidth;
    el.classList.add('bulk-fade-in');
}

// Collapse the employee/period setup into a compact summary bar after a
// successful generate (frees up screen space for the results), or expand it
// back when the user clicks "Change Selection".
function toggleBulkSetup(expand) {
    const section = document.getElementById('bulkSetupSection');
    const summary = document.getElementById('bulkSetupSummary');
    if (!section || !summary) return;
    section.style.display = expand ? 'block' : 'none';
    summary.style.display = expand ? 'none' : 'flex';
    _fadeInEl(expand ? section : summary);
    if (!expand) {
        const count = selectedEmployeeIds.size;
        const activeBtn = document.querySelector('.bulk-period-btn.active');
        const periodLabel = activeBtn ? activeBtn.textContent.trim() : '';
        const monthEl = document.getElementById('bulkPeriodMonth');
        const monthLabel = monthEl?.value || '';
        const text = document.getElementById('bulkSetupSummaryText');
        if (text) text.textContent = `✓ ${count} employee${count === 1 ? '' : 's'} · ${periodLabel} · ${monthLabel}`;
    }
}

// Toggle the per-employee review table (defaults to collapsed after a
// generate — the summary line is enough, and the full breakdown is one
// click away instead of permanently taking up space above the real table).
function toggleBulkReviewDetails() {
    const details = document.getElementById('bulkReviewDetails');
    const btn = document.getElementById('bulkReviewToggleBtn');
    if (!details) return;
    const show = details.style.display === 'none';
    details.style.display = show ? 'block' : 'none';
    if (show) _fadeInEl(details);
    if (btn) btn.textContent = show ? 'Hide Details ▲' : 'Show Details ▼';
}

// Grow the currently-visible bulk table's scroll box to fill down to the
// bottom of the card (#payrollOuterCard) from wherever it starts — not just
// "most of the screen," the actual full remaining space inside the card.
// Only recalculated on events that actually change layout (generate
// finishing, switching tabs, resizing the window) — NOT on every scroll
// tick, which fought the user's own scrolling (shrinking the table as they
// scrolled up kept resetting their scroll position, making it feel "stuck").
function _fillTableToViewport() {
    const visible = document.querySelector('.tab-content.active .bulk-table-scroll');
    const card = document.getElementById('payrollOuterCard');
    if (!visible || !card || visible.offsetParent === null) return;
    const cardBottom = card.getBoundingClientRect().bottom;
    const tableTop = visible.getBoundingClientRect().top;
    const available = cardBottom - tableTop - 12;
    visible.style.maxHeight = Math.max(200, available) + 'px';
}
window.addEventListener('resize', () => _fillTableToViewport());

// Expand/collapse a bulk table view to fill the whole screen.
function toggleBulkFullscreen(viewId) {
    const el = document.getElementById(viewId);
    if (!el) return;
    if (document.fullscreenElement) {
        document.exitFullscreen();
    } else {
        el.requestFullscreen?.().catch(err => {
            showToast('Could not enter full screen: ' + err.message, 'error');
        });
    }
}

// ================= MODE SWITCH =================
function switchMode(mode, e){
    const isBulk = mode === 'bulk';
    // CSS class hides all individual panels without needing per-element JS
    document.body.classList.toggle('bulk-active', isBulk);
    document.getElementById('bulk-panel').style.display = isBulk ? 'block' : 'none';
    // Show bulk tab views only if we already have generated data
    const hasData = bulkGeneratedPayloads.length > 0;
    // Only show the "click Generate Payslips" hint before the first generate —
    // once there's data it's just wasted space above the table.
    const notice = document.getElementById('bulk-raw-notice');
    if (notice) notice.style.display = (isBulk && !hasData) ? 'block' : 'none';
    ['bulk-raw-view','bulk-register-view','bulk-payslip-view'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        const show = isBulk && hasData;
        el.style.display = show ? 'block' : 'none';
        if (show) _fadeInEl(el);
    });
    document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
    e.target.classList.add('active');

    if (isBulk) {
        syncBulkPeriod();
    } else {
        selectedEmployeeIds.clear();
        updateSelectedEmployeeDisplay();
        document.getElementById('selectedEmployeeId').value = '';
        document.getElementById('empSearchInput').value = '';
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
        renderBulkEmployeeList();
    } catch (error) {
        console.error('Failed to load employees:', error);
        employeesList = [
            { id: '1', name: 'Test Employee 1', label: 'Test Employee 1' },
            { id: '2', name: 'Test Employee 2', label: 'Test Employee 2' }
        ];
        initSearchSuggestions();
        renderBulkEmployeeList();
        console.warn('Using dummy employee data — check server error above.');
    }
}

// ── Bulk employee checklist ───────────────────────────────────────────────

function renderBulkEmployeeList(filter) {
    const container = document.getElementById('bulkEmployeeList');
    if (!container) return;
    const q = (filter || '').trim().toLowerCase();
    const list = q ? employeesList.filter(e => e.name.toLowerCase().includes(q)) : employeesList;
    if (!list.length) {
        container.innerHTML = '<div style="padding:14px;text-align:center;color:#9ca3af;font-size:13px;">No employees found.</div>';
        return;
    }
    container.innerHTML = list.map(emp => {
        const checked = selectedEmployeeIds.has(String(emp.id));
        return `<label style="display:flex;align-items:center;gap:10px;padding:8px 12px;cursor:pointer;border-bottom:1px solid #f3f4f6;font-size:13px;">
            <input type="checkbox" value="${emp.id}" ${checked ? 'checked' : ''}
                   style="accent-color:#7b5cff;width:15px;height:15px;cursor:pointer;flex-shrink:0;"
                   onchange="toggleBulkEmployee('${emp.id}', this.checked)">
            <span>${emp.name}</span>
        </label>`;
    }).join('');
}

function filterBulkList(val) { renderBulkEmployeeList(val); }

function bulkSelectAll() {
    employeesList.forEach(e => selectedEmployeeIds.add(String(e.id)));
    renderBulkEmployeeList(document.getElementById('bulkListSearch')?.value);
    updateSelectedEmployeeDisplay();
}

function bulkDeselectAll() {
    selectedEmployeeIds.clear();
    renderBulkEmployeeList(document.getElementById('bulkListSearch')?.value);
    updateSelectedEmployeeDisplay();
}

function toggleBulkEmployee(id, checked) {
    if (checked) selectedEmployeeIds.add(String(id));
    else { selectedEmployeeIds.delete(id); selectedEmployeeIds.delete(String(id)); }
    updateSelectedEmployeeDisplay();
}

function updateSelectedEmployeeDisplay() {
    const count   = selectedEmployeeIds.size;
    const countEl = document.getElementById('bulkSelectionCount');
    const btnText = document.getElementById('bulkBtnText');
    if (countEl) countEl.textContent = count + ' employee' + (count === 1 ? '' : 's') + ' selected';
    if (btnText) btnText.textContent = count > 0
        ? 'Generate Payslips (' + count + ' employees)'
        : 'Generate Payslips';
}

// ── Bulk period sync ──────────────────────────────────────────────────────

function syncBulkPeriod() {
    const monthEl = document.getElementById('bulkPeriodMonth');
    if (!monthEl || !monthEl.value) return;
    const parts = monthEl.value.split('-');
    const y = parseInt(parts[0]), m = parseInt(parts[1]);
    const s = window._payrollSettings || {};
    const pad = n => String(n).padStart(2, '0');
    let ranges;
    if (s.half1_start_day) {
        ranges = {
            first_half:  { start: buildPeriodDate(y,m,s.half1_start_day,s.half1_start_offset||0), end: buildPeriodDate(y,m,s.half1_end_day,s.half1_end_offset||0) },
            second_half: { start: buildPeriodDate(y,m,s.half2_start_day,s.half2_start_offset||0), end: buildPeriodDate(y,m,s.half2_end_day,s.half2_end_offset||0) },
            monthly:     { start: buildPeriodDate(y,m,1,0), end: buildPeriodDate(y,m,0,0) },
        };
    } else {
        const cutoff = parseInt(s.first_half_cutoff)||15;
        const lastDay = new Date(y,m,0).getDate();
        const ym = y+'-'+pad(m);
        ranges = {
            first_half:  { start:ym+'-01', end:ym+'-'+pad(cutoff) },
            second_half: { start:ym+'-'+pad(cutoff+1), end:ym+'-'+pad(lastDay) },
            monthly:     { start:ym+'-01', end:ym+'-'+pad(lastDay) },
        };
    }
    const activeBtn = document.querySelector('.bulk-period-btn.active');
    const chosen = ranges[activeBtn ? activeBtn.dataset.period : 'first_half'] || ranges.first_half;
    document.getElementById('periodStart').value = chosen.start;
    document.getElementById('periodEnd').value   = chosen.end;
    const disp = document.getElementById('bulkPeriodDisplay');
    if (disp) disp.textContent = chosen.start + ' → ' + chosen.end;
}

function selectBulkPeriodBtn(btn) {
    document.querySelectorAll('.bulk-period-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    syncBulkPeriod();
}

// ── Bulk generate & review ────────────────────────────────────────────────

let bulkGeneratedPayloads = []; // stores {empId, empName, payload, d} after generate

async function startBulkGenerate() {
    const ids = Array.from(selectedEmployeeIds);
    if (!ids.length) { showToast('Select at least one employee first.', 'warning'); return; }

    syncBulkPeriod();
    const periodStart = document.getElementById('periodStart').value;
    const periodEnd   = document.getElementById('periodEnd').value;
    if (!periodStart || !periodEnd) { showToast('Please set a valid pay period.', 'warning'); return; }

    // Lock UI
    const genBtn  = document.getElementById('bulkGenerateBtn');
    const btnText = document.getElementById('bulkBtnText');
    const spinner = document.getElementById('bulkBtnSpinner');
    const sendBtn = document.getElementById('bulkSendAllBtn');
    const msgEl   = document.getElementById('bulkStatusMsg');
    genBtn.disabled = true; spinner.style.display = 'inline';
    sendBtn.style.display = 'none';
    msgEl.textContent = '';
    bulkGeneratedPayloads = [];

    // Show review table with loading placeholders
    const reviewPanel   = document.getElementById('bulkReviewPanel');
    const reviewRows    = document.getElementById('bulkReviewRows');
    const reviewSumm    = document.getElementById('bulkReviewSummary');
    const reviewDetails = document.getElementById('bulkReviewDetails');
    reviewPanel.style.display = 'block';
    _fadeInEl(reviewPanel);
    if (reviewDetails) reviewDetails.style.display = 'none'; // collapsed by default each generate
    reviewRows.innerHTML = ids.map((id, i) => {
        const emp = employeesList.find(e => String(e.id) === String(id));
        return `<div id="brow-${id}" style="display:grid;grid-template-columns:2fr 1.1fr 1.1fr 1.2fr 90px;align-items:center;border-bottom:1px solid #f3f4f6;font-size:13px;">
            <div style="padding:10px 14px;">${emp ? emp.name : id}</div>
            <div id="bcell-basic-${id}" style="padding:10px 14px;color:#9ca3af;">—</div>
            <div id="bcell-ded-${id}"   style="padding:10px 14px;color:#9ca3af;">—</div>
            <div id="bcell-net-${id}"   style="padding:10px 14px;color:#9ca3af;">—</div>
            <div style="padding:10px 14px;text-align:center;"><span id="bcell-status-${id}" style="font-size:11px;color:#9ca3af;">⏳</span></div>
        </div>`;
    }).join('');
    reviewSumm.textContent = '';

    const fmt = n => '₱' + Number(n||0).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
    let ok = 0, fail = 0, firstErrMsg = '';

    let processed = 0;
    for (const empId of ids) {
        const statusEl = document.getElementById('bcell-status-'+empId);
        try {
            // Set employee identity for computePayslipData
            const emp = employeesList.find(e => String(e.id) === String(empId));
            document.getElementById('empName').value = emp ? emp.name : '';
            document.getElementById('selectedEmployeeId').value = empId;

            processed++;
            if (btnText) btnText.textContent = `Generating ${processed} of ${ids.length}…`;
            reviewSumm.textContent = `Generating ${processed} of ${ids.length} — please wait, this can take a while for a large selection…`;
            reviewSumm.style.color = '#6b7280';

            const filled = await fillEmployeeData(empId, true);
            if (!filled) throw new Error('Data load failed');

            // Ensure period type and prior-half WT are resolved before computing.
            // fillEmployeeData calls updateWithholdingTax with the wrong period type
            // (defaults to first_half); re-running after autoDetectPeriodType and
            // fetching prior-half data gives the correct 2nd-half cumulative WT.
            autoDetectPeriodType();
            await fetchAndSetPriorHalfWT();

            // Use getComputedPayslipValues (not computePayslipData) so WT is computed
            // from the raw-sheet taxable base and SSS uses total monthly compensation.
            const d = getComputedPayslipValues();
            if (!d || !d.empName) throw new Error('Computation failed');

            // Build payload once — reused for save and send
            const payload = _buildBulkPayload(empId, d);
            bulkGeneratedPayloads.push({ empId, empName: emp ? emp.name : String(empId), payload, d });

            // Update review row
            const deductions = (d.absentAmt||0) + (d.undertimeAmt||0) + (d.sssDed||0) + (d.phicDed||0) + (d.hdmfDed||0) + Math.max(0, d.withholdingTax||0);
            document.getElementById('bcell-basic-'+empId).textContent = fmt(d.grossPay);
            document.getElementById('bcell-ded-'+empId).textContent   = deductions > 0 ? fmt(deductions) : '—';
            document.getElementById('bcell-net-'+empId).innerHTML     = '<strong>' + fmt(d.netPay) + '</strong>';
            statusEl.innerHTML = `<button onclick="viewBulkPayslip(${bulkGeneratedPayloads.length-1})"
                style="background:#7b5cff;color:#fff;border:none;border-radius:6px;padding:4px 10px;font-size:11px;cursor:pointer;font-weight:600;">View</button>`;
            ok++;
        } catch(err) {
            const msg = String(err && err.message || err);
            if (!firstErrMsg) firstErrMsg = msg;
            statusEl.innerHTML = `<span style="color:#dc2626;font-size:11px;cursor:help;" title="${msg.replace(/"/g,'&quot;')}">Error ⓘ</span>`;
            console.error('Bulk generate error for '+empId+':', err);
            fail++;
        }
    }

    genBtn.disabled = false; spinner.style.display = 'none';
    if (btnText) btnText.textContent = 'Regenerate (' + ids.length + ' employees)';
    reviewSumm.innerHTML = `<span>${ok} generated`
        + (fail ? `, ${fail} failed (${firstErrMsg})` : '')
        + `</span><button id="bulkReviewToggleBtn" type="button" onclick="toggleBulkReviewDetails()"
            style="margin-left:auto;font-size:12px;padding:5px 10px;border:1px solid #d1d5db;border-radius:6px;background:#fff;color:#374151;cursor:pointer;font-weight:600;">Show Details ▼</button>`;
    if (ok > 0) {
        sendBtn.style.display = 'inline-flex';
        const exportBtn = document.getElementById('bulkExportBtn');
        if (exportBtn) exportBtn.style.display = 'inline-flex';
        // Populate the tab tables (Raw Sheet, Register, Payslip tabs).
        // Wrapped: an uncaught error here used to abort silently, leaving the
        // status message blank and the tab tables permanently hidden with no
        // visible sign anything went wrong.
        try {
            renderBulkTabViews();
        } catch (err) {
            console.error('renderBulkTabViews failed:', err);
            msgEl.style.color = '#dc2626';
            msgEl.textContent = 'Generated, but the tables below failed to render: ' + err.message;
            return;
        }
        // Free up screen space: collapse the employee/period picker, hide the
        // now-redundant "click Generate" hint, and scroll straight to the
        // editable table on the active tab (not just the summary above it) —
        // the goal is landing on a big, near-full-screen table without
        // needing Full Screen.
        toggleBulkSetup(false);
        const notice = document.getElementById('bulk-raw-notice');
        if (notice) notice.style.display = 'none';
        const activeTabId = document.querySelector('.sub-tab.active')?.dataset.tab;
        const targetView = { raw: 'bulk-raw-view', register: 'bulk-register-view', payslip: 'bulk-payslip-view' }[activeTabId];
        const scrollTarget = document.getElementById(targetView) || reviewPanel;
        scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // The scroll listener keeps this updated live, but also fill once
        // right after the smooth-scroll settles as a safety net.
        setTimeout(_fillTableToViewport, 500);
    }
    msgEl.style.color = fail ? '#dc2626' : '#16a34a';
    msgEl.textContent = fail ? fail + ' failed — check console' : '';
}

function _buildBulkPayload(empId, d) {
    const gv = id => parseFloat(document.getElementById(id)?.value || 0);
    return {
        employee_id: empId,
        period_start: d.periodStart, period_end: d.periodEnd,
        employee_name: d.empName, position: d.position,
        basic_pay: d.basicPay, rate_per_hour: d.ratePerHour,
        hours_worked: d.hoursWorked, night_diff_pay: d.nightPremium,
        absent_amt: d.absentAmt, undertime_amt: d.undertimeAmt,
        net_basic_pay: d.netBasicPay,
        reg_ot: d.regOt||0, reg_ot_gt10: d.regOtGt10||0, reg_ot_night: d.regOtNightPrem||0,
        sat_ot: d.satOt||0, sat_ot_gt10: d.satOtGt10||0, sat_night_prem: d.satNightPrem||0,
        sun_ot: d.sunOt||0, sun_ot_gt10: d.sunOtGt10||0, sun_night_prem: d.sunNightPrem||0,
        hol_ot: d.holOt||0, hol_ot_gt10: d.holOtGt10||0, hol_night_prem: d.holNightPrem||0,
        total_overtime: d.totalOvertime||0,
        overtime_pay: d.totalOvertime||0,
        salary_adjustment: gv('salaryAdjustment'), override_amount: gv('overrideAmount'),
        ned_amount: gv('nedAmount'), holiday_pay: gv('holidayPay'),
        other_earnings_taxable: d.taxableOther,
        year_end_gifts: gv('yearEndGifts'), rice_subsidy: gv('riceSubsidy'),
        uniform_allowance: gv('uniformAllowance'), laundry_allowance: gv('laundryAllowance'),
        meal_allowance: gv('mealAllowance'), medical_allowance: gv('medicalAllowance') + gv('medicalAllowanceAdd'),
        salary_adjustment_non_tax: gv('salaryAdjustmentNonTax'),
        sal_non_tax_adj: gv('salNonTaxAdj'),
        de_minimis: d.nonTaxable, vl_cash_conversion: d.vlCashConversion||0,
        other_earnings_non_taxable: (d.vlCashConversion||0) + gv('salNonTaxAdj'),
        gross_pay: d.grossPay, sss_ee: d.sssDed, phic_ee: d.phicDed, hdmf_ee: d.hdmfDed,
        withholding_tax: d.withholdingTax,
        sss_loan: gv('sssSalaryLoan'), calamity_loan: gv('sssCalamityLoan'),
        hdmf_loan: gv('hdmfLoan'), salary_advance: gv('advSalary'),
        cash_advance: gv('advCash'),
        emergency_advance: gv('advEmergency'),
        hdmf_mpl_loan: gv('hdmfMplLoan'),
        hdmf_calamity_loan: gv('hdmfCalamityLoan'), other_deductions: 0,
        net_pay: d.netPay,
    };
}

async function viewBulkPayslip(idx) {
    const item = bulkGeneratedPayloads[idx];
    if (!item) return;
    const btn = document.querySelector(`[onclick="viewBulkPayslip(${idx})"]`);
    if (btn) { btn.disabled = true; btn.textContent = '…'; }
    // Re-fill the form for this employee so computePayslipData reads correct values
    await fillEmployeeData(item.empId, true);
    document.getElementById('empName').value = item.empName;
    document.getElementById('selectedEmployeeId').value = item.empId;
    // Also sync to payslip tab so user can see it there
    await syncToRegister();
    syncToPayslip();
    generateStaticPayslip();
    if (btn) { btn.disabled = false; btn.textContent = 'View'; }
}

async function sendBulkAll() {
    if (!bulkGeneratedPayloads.length) { showToast('Generate payslips first.', 'warning'); return; }
    const sendBtn = document.getElementById('bulkSendAllBtn');
    const msgEl   = document.getElementById('bulkStatusMsg');
    const reviewSumm = document.getElementById('bulkReviewSummary');
    sendBtn.disabled = true;
    sendBtn.textContent = 'Sending…';

    let sent = 0, failed = 0;
    for (const { empId, empName, payload } of bulkGeneratedPayloads) {
        const statusEl = document.getElementById('bcell-status-'+empId);
        try {
            // Save record
            await fetch('<?= site_url('client_portal/payroll/savePayslipRecord') ?>', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify(payload)
            }).then(r => r.json());

            // Send PDF
            const resp = await fetch('<?= site_url('client_portal/payroll/sendPdf') ?>', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ ...payload, send_method: 'bulk' })
            });
            const res = await resp.json();
            if (!resp.ok || !res.success) throw new Error(res.error||'Send failed');

            if (statusEl) statusEl.innerHTML = '<span style="color:#16a34a;font-size:11px;font-weight:700;">✓ Sent</span>';
            sent++;
        } catch(err) {
            if (statusEl) statusEl.innerHTML = '<span style="color:#dc2626;font-size:11px;">✗ Error</span>';
            console.error('Send error for '+empName+':', err);
            failed++;
        }
    }

    sendBtn.disabled = false;
    sendBtn.textContent = sent > 0 ? '✉ Send All Payslips' : '✉ Retry';
    reviewSumm.textContent = sent + ' sent' + (failed ? ', ' + failed + ' failed' : '') + '.';
    reviewSumm.style.color = failed ? '#dc2626' : '#16a34a';
    msgEl.textContent = '';
    if (sent > 0 && !failed) sendBtn.style.display = 'none';
}

// ================= BULK CSV EXPORT =================
// ─── Shared row computation — single source of truth for table + CSV ──────────
function _bRowCalc(p, d) {
    // OT = Workday OT + Sat regular + Sat OT (matches Excel register column N)
    const totalOT = (d.regOt||0)+(d.regOtGt10||0)+(d.satOt||0)+(d.satOtGt10||0);
    const absTotal    = (d.absentAmt||0)+(d.undertimeAmt||0);
    const yearEnd     = p.year_end_gifts||0,   rice    = p.rice_subsidy||0;
    const uniform     = p.uniform_allowance||0, laundry = p.laundry_allowance||0;
    const meal        = p.meal_allowance||0,   medical = p.medical_allowance||0;
    const deMin       = yearEnd+rice+uniform+laundry+meal+medical+(p.salary_adjustment_non_tax||0);
    const otherNonTax = (d.vlCashConversion||0) + (p.sal_non_tax_adj||0);
    const nonTax      = deMin+otherNonTax;
    const salAdj      = (p.salary_adjustment||0) + (p.override_amount||0) + (p.ned_amount||0);
    // NSD = workday NSD + Sat NSD + Sun NSD + Reg Hol NSD (matches Excel column O; no regOtNightPrem)
    const nsd = (d.nightPremium||0)+(d.satNightPrem||0)+(d.sunNightPrem||0)+(d.holNightPrem||0);
    // Holiday Pay = holiday pay + Sun reg + Sun OT + Reg Hol OT (Saturday is in OT column)
    const holiday = (p.holiday_pay||0)+(d.sunOt||0)+(d.sunOtGt10||0)+(d.holOt||0)+(d.holOtGt10||0);
    // Mirror computePayslipData(): cap absences at basicPay, NSD goes into netBasicPay
    const absCapped   = Math.min(absTotal, d.basicPay);
    const netBasicPay = Math.max(0, d.basicPay - absCapped + nsd);
    const grossTaxable= netBasicPay + totalOT + salAdj + holiday;
    const grossComp   = grossTaxable+nonTax;
    const sssTotal    = d.sssDed||0, phic = d.phicDed||0, hdmf = d.hdmfDed||0;
    const wtRaw       = d.withholdingTax||0;   // signed: negative = refund
    const wt          = Math.max(0, wtRaw);    // deduction column (non-negative)
    const taxAdj      = Math.max(0, -wtRaw);   // refund column (non-negative)
    const sssLoan     = p.sss_loan||0, calLoan = p.calamity_loan||0, advances = p.salary_advance||0;
    const hdmfLoan    = p.hdmf_loan||d.hdmfLoan||0, calAdv = p.cal_adv||d.calAdv||0;
    const hdmfMplLoan = p.hdmf_mpl_loan||0, hdmfCalamityLoan = p.hdmf_calamity_loan||0;
    const cashAdvance = p.cash_advance||0, emergencyAdvance = p.emergency_advance||0;
    const regNetTax   = grossTaxable-sssTotal-phic-hdmf;
    // Net pay: deduct wt (≥0) then add back refund (taxAdj ≥0); equivalent to subtracting signed wtRaw.
    const netPay      = grossComp-sssTotal-phic-hdmf-wt+taxAdj-sssLoan-calLoan-hdmfLoan-hdmfMplLoan-hdmfCalamityLoan-advances-cashAdvance-emergencyAdvance-calAdv;
    return { monthly:d.basicPay*2, semi:d.basicPay, yearEnd, rice, uniform, laundry, meal, medical, deMin,
        otherNonTax, salAdj, totalOT, nsd, absTotal, holiday,
        grossComp, grossTaxable, nonTax,
        sssRss:0, sssWisp:0, sssTotal, phic, hdmf,
        regNetTax, netTaxPrev:0, monthlyTax:0,
        wtBracket:0, wtBasic:0, wtPct:0, wtBasicAmt:0, wtExcess:0,
        wtPaid:0, wt, sssLoan, calLoan, hdmfLoan, hdmfMplLoan, hdmfCalamityLoan,
        advances, cashAdvance, emergencyAdvance, calAdv, taxAdj, netPay };
}

function exportBulkCSV(type = 'raw') {
    if (!bulkGeneratedPayloads.length) { showToast('Nothing to export — generate payslips first.', 'warning'); return; }
    const esc = v => '"' + String(v ?? '').replace(/"/g, '""') + '"';
    const fmt = v => Number(v||0).toFixed(2);
    const headers = [
        'employee_id','employee_name','period_start','period_end',
        'monthly_basic_salary','semi_monthly_basic_salary',
        'year_end_gifts','rice_subsidy','uniform_allowance','laundry_allowance','meal_allowance','medical_allowance',
        'other_non_taxable_compensation','salary_adjustment','overtime','nsd','absences_tardiness','holiday_pay',
        'gross_compensation','gross_taxable_compensation','non_taxable_compensation',
        'sss_rss','sss_wisp','sss_total','phic','hdmf',
        'regular_net_taxable','net_taxable_prev_cutoff','monthly_taxable_compensation',
        'wt_bracket','wt_basic','wt_pct','wt_basic_amt','wt_excess',
        'wt_paid_last_cutoff','withholding_tax_payable',
        'sss_loan_salary','sss_loan_calamity','advances_to_employees','tax_adjustment_refund',
        'net_pay'
    ];
    const rows = bulkGeneratedPayloads.map(({empId, empName, payload: p, d}) => {
        const c = _bRowCalc(p, d);
        return [
            empId, empName, d.periodStart||'', d.periodEnd||'',
            fmt(c.monthly), fmt(c.semi),
            fmt(c.yearEnd), fmt(c.rice), fmt(c.uniform), fmt(c.laundry), fmt(c.meal), fmt(c.medical),
            fmt(c.otherNonTax), fmt(c.salAdj), fmt(c.totalOT), fmt(c.nsd), fmt(c.absTotal), fmt(c.holiday),
            fmt(c.grossComp), fmt(c.grossTaxable), fmt(c.nonTax),
            fmt(c.sssRss), fmt(c.sssWisp), fmt(c.sssTotal), fmt(c.phic), fmt(c.hdmf),
            fmt(c.regNetTax), fmt(c.netTaxPrev), fmt(c.monthlyTax),
            fmt(c.wtBracket), fmt(c.wtBasic), fmt(c.wtPct), fmt(c.wtBasicAmt), fmt(c.wtExcess),
            fmt(c.wtPaid), fmt(c.wt),
            fmt(c.sssLoan), fmt(c.calLoan), fmt(c.advances), fmt(c.taxAdj),
            fmt(c.netPay)
        ];
    });
    const csv = [headers.join(','), ...rows.map(r => r.map(esc).join(','))].join('\n');
    const blob = new Blob(['﻿'+csv], { type:'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = `payroll_register_${new Date().toISOString().slice(0,10)}.csv`; a.click();
    URL.revokeObjectURL(url);
}

// ================= BULK TAB VIEWS =================
const _bfmt = n => Number(n||0).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});

function _buildRegisterTable(containerId) {
    const wrap = document.getElementById(containerId);
    if (!wrap) return;
    const data = bulkGeneratedPayloads;
    const B1='#1e5fa8', B2='#2e75b6', B3='#3a7fc1', GR='#145a32';
    const th = (t,rs,cs,bg) => `<th rowspan="${rs||1}" colspan="${cs||1}" style="padding:4px 6px;border:1px solid #1e4a8a;text-align:center;white-space:nowrap;vertical-align:middle;background:${bg||B1};color:#fff;font-size:9.5px;font-weight:700;line-height:1.3;">${t}</th>`;

    const thead = `<thead>
    <tr>
        ${th('No.',3,1,B1)} ${th('Name of Employees',3,1,B1)}
        ${th('Monthly<br>Basic Salary',3,1,B1)} ${th('Semi-Monthly<br>Basic Salary',3,1,B1)}
        ${th('DE MINIMIS BENEFIT',1,6,B2)}
        ${th('Other Non-Taxable<br>Compensation',3,1,B1)} ${th('Salary<br>Adjustment',3,1,B1)}
        ${th('Overtime',3,1,B1)} ${th('NSD',3,1,B1)}
        ${th('Absences /<br>Tardiness',3,1,B1)} ${th('Holiday Pay',3,1,B1)}
        ${th('Gross<br>Compensation',3,1,GR)} ${th('Gross Taxable<br>Compensation',3,1,B1)} ${th('Non-Taxable<br>Compensation',3,1,B1)}
        ${th("EMPLOYEE'S SHARE",2,5,B2)}
        ${th('Regular Net<br>Taxable Comp.',3,1,B1)} ${th('Net Taxable<br>Prev. Cut-off',3,1,B1)} ${th('Monthly Taxable<br>Compensation',3,1,B1)}
        ${th('Withholding Tax Computation',1,5,B2)}
        ${th('WT Paid<br>Last Cut-off',3,1,B1)} ${th('Withholding<br>Tax Payable',3,1,B1)}
        ${th('SSS Loan',1,2,B2)}
        ${th('Advances to<br>Employees',3,1,B1)} ${th('Tax Adjustments<br>(Refund)',3,1,B1)} ${th('NET PAY',3,1,GR)}
    </tr>
    <tr>
        ${th('Year End<br>Gifts',2,1,B3)} ${th('Rice<br>Subsidy',2,1,B3)} ${th('Uniform/<br>Clothing',2,1,B3)}
        ${th('Laundry',2,1,B3)} ${th('Meal<br>Allow.',2,1,B3)} ${th('Medical<br>Allow.',2,1,B3)}
        ${th('Bracket',2,1,B3)} ${th('Basic',2,1,B3)} ${th('%',2,1,B3)} ${th('Basic',2,1,B3)} ${th('Excess',2,1,B3)}
        ${th('Salary',2,1,B3)} ${th('Calamity',2,1,B3)}
    </tr>
    <tr>
        ${th('SSS-RSS',1,1,B3)} ${th('SSS-WISP',1,1,B3)} ${th('SSS-TOTAL',1,1,B3)} ${th('PHIC',1,1,B3)} ${th('HDMF',1,1,B3)}
    </tr></thead>`;

    // Editable input cell (yellow) — user can override these
    const inp = (empId, f, v, w=72) => `<td style="padding:0;border:1px solid #d1d5db;background:#fffde7;vertical-align:middle;"><input type="number" step="0.01" data-emp="${empId}" data-f="${f}" value="${Number(v||0).toFixed(2)}" oninput="recomputeBulkRow('${empId}',this)" style="width:${w}px;border:none;background:transparent;text-align:right;font-size:10px;padding:2px 5px;outline:none;"></td>`;
    // Read-only computed span
    const ro = (empId, f, v, green) => {
        const st = green ? 'color:#1a7f37;font-weight:700;' : '';
        return `<td style="padding:0;border:1px solid #d1d5db;${green?'background:#f0fff4;':''}vertical-align:middle;"><span data-emp="${empId}" data-f="${f}" style="display:block;text-align:right;font-size:10px;padding:2px 5px;min-width:68px;${st}">${_bfmt(v)}</span></td>`;
    };
    const dash = () => `<td style="padding:2px 5px;border:1px solid #d1d5db;text-align:center;font-size:10px;color:#bbb;">—</td>`;

    const rows = data.map(({empId, empName, payload: p, d}, i) => {
        const c = _bRowCalc(p, d);
        const bg = i%2===0 ? '#fff' : '#f8fafc';
        return `<tr>
            <td style="padding:3px 6px;border:1px solid #d1d5db;text-align:center;background:${bg};font-size:10px;">${i+1}</td>
            <td style="padding:3px 8px;border:1px solid #d1d5db;background:${bg};font-size:10px;white-space:nowrap;">${empName}</td>
            ${ro(empId,'monthly',c.monthly)} ${ro(empId,'semi',c.semi)}
            ${inp(empId,'year_end',c.yearEnd)} ${inp(empId,'rice',c.rice)} ${inp(empId,'uniform',c.uniform)}
            ${inp(empId,'laundry',c.laundry)} ${inp(empId,'meal',c.meal)} ${inp(empId,'medical',c.medical)}
            ${ro(empId,'other_nontax',c.otherNonTax)} ${inp(empId,'sal_adj',c.salAdj,78)}
            ${inp(empId,'ot',c.totalOT,78)} ${inp(empId,'nsd',c.nsd)}
            ${inp(empId,'absences',c.absTotal,78)} ${inp(empId,'holiday',c.holiday)}
            ${ro(empId,'gross_comp',c.grossComp,true)} ${ro(empId,'gross_taxable',c.grossTaxable)} ${ro(empId,'non_taxable',c.nonTax)}
            ${ro(empId,'sss_rss',c.sssRss)} ${ro(empId,'sss_wisp',c.sssWisp)} ${ro(empId,'sss_total',c.sssTotal)} ${ro(empId,'phic',c.phic)} ${ro(empId,'hdmf',c.hdmf)}
            ${ro(empId,'reg_net_tax',c.regNetTax)} ${dash()} ${dash()}
            ${dash()} ${dash()} ${dash()} ${dash()} ${dash()}
            ${dash()} ${ro(empId,'wt',c.wt)}
            ${inp(empId,'sss_loan',c.sssLoan)} ${inp(empId,'cal_loan',c.calLoan)}
            ${inp(empId,'advances',c.advances)} ${ro(empId,'tax_adj',c.taxAdj)}
            ${ro(empId,'net_pay',c.netPay,true)}
        </tr>`;
    });

    // TOTAL footer
    const T = data.reduce((acc,{payload:p,d})=>{ const c=_bRowCalc(p,d); Object.keys(c).forEach(k=>{acc[k]=(acc[k]||0)+c[k];}); return acc; }, {});
    const tt = (v,green) => `<td style="padding:3px 5px;border:1px solid #d1d5db;text-align:right;font-size:10px;font-weight:700;${green?'color:#1a7f37;':''}">${_bfmt(v)}</td>`;
    const td0 = () => `<td style="padding:3px 5px;border:1px solid #d1d5db;text-align:center;font-size:10px;color:#bbb;">—</td>`;
    const tfoot = `<tfoot><tr style="background:#dbeafe;">
        <td colspan="2" style="padding:5px 8px;border:1px solid #d1d5db;font-size:10px;font-weight:700;text-align:right;">TOTAL</td>
        ${tt(T.monthly)} ${tt(T.semi)}
        ${tt(T.yearEnd)} ${tt(T.rice)} ${tt(T.uniform)} ${tt(T.laundry)} ${tt(T.meal)} ${tt(T.medical)}
        ${tt(T.otherNonTax)} ${tt(T.salAdj)} ${tt(T.totalOT)} ${tt(T.nsd)} ${tt(T.absTotal)} ${tt(T.holiday)}
        ${tt(T.grossComp,true)} ${tt(T.grossTaxable)} ${tt(T.nonTax)}
        ${tt(T.sssRss)} ${tt(T.sssWisp)} ${tt(T.sssTotal)} ${tt(T.phic)} ${tt(T.hdmf)}
        ${tt(T.regNetTax)} ${td0()} ${td0()}
        ${td0()} ${td0()} ${td0()} ${td0()} ${td0()}
        ${td0()} ${tt(T.wt)}
        ${tt(T.sssLoan)} ${tt(T.calLoan)} ${tt(T.advances)} ${tt(T.taxAdj)}
        ${tt(T.netPay,true)}
    </tr></tfoot>`;

    wrap.innerHTML = `<table style="border-collapse:collapse;white-space:nowrap;">${thead}<tbody>${rows.join('')}</tbody>${tfoot}</table>`;
}

function renderBulkTabViews() {
    if (!bulkGeneratedPayloads.length) return;

    // Both tabs use the same full Excel-matched table structure.
    // Raw Sheet gets editable inputs (yellow); Register gets read-only spans.
    _buildRegisterTable('bulk-raw-tbody');
    _buildRegisterTable('bulk-register-tbody');

    // Payslip list
    const payslipList = document.getElementById('bulk-payslip-list');
    if (payslipList) {
        payslipList.innerHTML = bulkGeneratedPayloads.map(({empId, empName, d}, i) => {
            return `<div style="display:grid;grid-template-columns:2.5fr 1.2fr 1.2fr 1.2fr auto;align-items:center;border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;background:#fff;gap:10px;" id="bprow-${empId}">
                <div><div style="font-weight:600;font-size:13px;">${empName}</div><div style="font-size:11px;color:#9ca3af;">${d.formatStart||''} – ${d.formatEnd||''}</div></div>
                <div style="font-size:12px;color:#374151;">Gross: <strong id="bpgross-${empId}">&#8369;${_bfmt(d.grossPay)}</strong></div>
                <div style="font-size:12px;color:#374151;">Deductions: <strong>&#8369;${_bfmt(d.totalDeductions)}</strong></div>
                <div style="font-size:12px;color:#15803d;">Net: <strong id="bpnet-${empId}">&#8369;${_bfmt(d.netPay)}</strong></div>
                <div style="display:flex;gap:6px;">
                    <button onclick="viewBulkPayslip(${i})" style="background:#7b5cff;color:#fff;border:none;border-radius:6px;padding:6px 12px;font-size:12px;cursor:pointer;font-weight:600;">View</button>
                    <button id="bpsend-${empId}" onclick="sendSingleBulk(${i},this)" style="background:#16a34a;color:#fff;border:none;border-radius:6px;padding:6px 12px;font-size:12px;cursor:pointer;font-weight:600;">Send</button>
                </div>
            </div>`;
        }).join('');
    }

    ['bulk-raw-view','bulk-register-view','bulk-payslip-view'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.style.display = 'block';
        _fadeInEl(el);
    });
}

function recomputeBulkRow(empId, trigger) {
    const idx = bulkGeneratedPayloads.findIndex(b => String(b.empId) === String(empId));
    if (idx === -1) return;

    // Step 1: sync the edited input to the matching input in the other table
    if (trigger) {
        document.querySelectorAll(`input[data-emp="${empId}"][data-f="${trigger.dataset.f}"]`).forEach(el => {
            if (el !== trigger) el.value = trigger.value;
        });
    }

    const { d, payload: p } = bulkGeneratedPayloads[idx];

    // Step 2: read all editable fields (first matching input — both tables are now in sync)
    const gf = f => parseFloat(document.querySelector(`input[data-emp="${empId}"][data-f="${f}"]`)?.value) || 0;

    const yearEnd  = gf('year_end'),  rice    = gf('rice'),    uniform = gf('uniform');
    const laundry  = gf('laundry'),   meal    = gf('meal'),    medical = gf('medical');
    const salAdj   = gf('sal_adj'),   ot      = gf('ot'),      nsd     = gf('nsd');
    const absences = gf('absences'),  holiday = gf('holiday');
    const sssLoan  = gf('sss_loan'),  calLoan = gf('cal_loan'), advances = gf('advances');
    // HDMF loan and calamity advance aren't editable columns in the register — read from payload/d
    const hdmfLoan = p.hdmf_loan || d.hdmfLoan || 0;
    const calAdv   = p.cal_adv   || d.calAdv   || 0;

    // Step 3: compute — mirrors computePayslipData() logic exactly
    const deMin        = yearEnd + rice + uniform + laundry + meal + medical + (p.salary_adjustment_non_tax || 0);
    const otherNonTax  = (d.vlCashConversion || 0) + (p.sal_non_tax_adj || 0);
    const nonTax       = deMin + otherNonTax;
    const absCapped    = Math.min(absences, d.basicPay);           // can't deduct more than basic pay
    const netBasicPay  = Math.max(0, d.basicPay - absCapped + nsd); // NSD included in net basic pay
    const grossTaxable = netBasicPay + ot + salAdj + holiday;       // taxable portion
    const grossComp    = Math.round((grossTaxable + nonTax) * 100) / 100;  // total gross (taxable + non-taxable)
    const sssTotal     = d.sssDed || 0, phic = d.phicDed || 0, hdmf = d.hdmfDed || 0;
    const wtRaw        = d.withholdingTax || 0;  // signed: negative = refund
    const wt           = Math.max(0, wtRaw);     // deduction (non-negative for display)
    const taxAdj       = Math.max(0, -wtRaw);    // refund (non-negative for display)
    const regNetTax    = grossTaxable - sssTotal - phic - hdmf;
    const totalDed     = sssTotal + phic + hdmf + wt + sssLoan + calLoan + hdmfLoan + advances + calAdv;
    const netPay       = grossComp - totalDed + taxAdj;

    // Step 4: update all computed spans in BOTH tables simultaneously
    const sf = (f, v) => document.querySelectorAll(`span[data-emp="${empId}"][data-f="${f}"]`).forEach(el => { el.textContent = _bfmt(v); });
    sf('gross_comp',    grossComp);
    sf('gross_taxable', grossTaxable);
    sf('non_taxable',   nonTax);
    sf('sss_total',     sssTotal);
    sf('phic',          phic);
    sf('hdmf',          hdmf);
    sf('reg_net_tax',   regNetTax);
    sf('wt',            wt);
    sf('tax_adj',       taxAdj);
    sf('net_pay',       netPay);

    // Step 5: persist to payload so Send uses updated values
    Object.assign(p, {
        year_end_gifts:yearEnd, rice_subsidy:rice, uniform_allowance:uniform,
        laundry_allowance:laundry, meal_allowance:meal, medical_allowance:medical,
        salary_adjustment:salAdj, holiday_pay:holiday,
        sss_loan:sssLoan, calamity_loan:calLoan, hdmf_loan:hdmfLoan,
        salary_advance:advances, cal_adv:calAdv,
        de_minimis:deMin, gross_pay:grossComp, net_pay:netPay
    });
    d.absentAmt = absences; d.grossPay = grossComp; d.netPay = netPay;
    d.taxableOther = salAdj + holiday; d.totalDeductions = totalDed; d.nonTaxable = nonTax;

    // Step 6: live-update payslip list card and bulk panel row
    const grossEl = document.getElementById('bpgross-'+empId);
    const netEl   = document.getElementById('bpnet-'+empId);
    if (grossEl) grossEl.textContent = '₱'+_bfmt(grossComp);
    if (netEl)   netEl.textContent   = '₱'+_bfmt(netPay);
    const bcNet = document.getElementById('bcell-net-'+empId);
    if (bcNet) bcNet.innerHTML = '<strong>₱'+_bfmt(netPay)+'</strong>';
}

async function sendSingleBulk(idx, btn) {
    const item = bulkGeneratedPayloads[idx];
    if (!item) return;
    btn.disabled = true; btn.textContent = '…';
    try {
        await fetch('<?= site_url('client_portal/payroll/savePayslipRecord') ?>', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify(item.payload)
        });
        const resp = await fetch('<?= site_url('client_portal/payroll/sendPdf') ?>', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ ...item.payload, send_method: 'bulk' })
        });
        const res = await resp.json();
        if (!resp.ok || !res.success) throw new Error(res.error||'Send failed');
        btn.textContent = '✓ Sent'; btn.style.background = '#6b7280';
        // Also update bulk panel review row status
        const statusEl = document.getElementById('bcell-status-'+item.empId);
        if (statusEl) statusEl.innerHTML = '<span style="color:#16a34a;font-size:11px;font-weight:700;">✓ Sent</span>';
    } catch(err) {
        btn.disabled = false; btn.textContent = 'Retry';
        showToast('Send failed: ' + err.message, 'error');
    }
}

function importBulkCSV(type, input) {
    const file = input.files[0];
    if (!file) return;
    if (!bulkGeneratedPayloads.length) {
        showToast('Generate payslips first before importing CSV adjustments.', 'warning');
        input.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
        const lines = e.target.result.split(/\r?\n/).filter(l => l.trim());
        if (lines.length < 2) { showToast('CSV has no data rows.', 'error'); return; }
        const hdrs = lines[0].split(',').map(h => h.replace(/^"|"$/g,'').trim());
        const parseRow = line => {
            const cols=[]; let cur=''; let inQ=false;
            for (const ch of line+',') {
                if (ch==='"') inQ=!inQ;
                else if (ch===',' && !inQ) { cols.push(cur.trim()); cur=''; }
                else cur+=ch;
            }
            const obj={}; hdrs.forEach((h,i)=>{ obj[h]=(cols[i]||'').replace(/^"|"$/g,'').trim(); }); return obj;
        };
        const n = f => parseFloat(f)||0;
        let matched = 0;
        lines.slice(1).forEach(line => {
            const row = parseRow(line);
            const empId = row['employee_id'];
            const idx = bulkGeneratedPayloads.findIndex(b => String(b.empId) === String(empId));
            if (idx === -1) return;
            const { payload: p, d } = bulkGeneratedPayloads[idx];
            // Map CSV columns → payload/d fields
            if (row['year_end_gifts']          !== undefined) p.year_end_gifts      = n(row['year_end_gifts']);
            if (row['rice_subsidy']            !== undefined) p.rice_subsidy        = n(row['rice_subsidy']);
            if (row['uniform_allowance']       !== undefined) p.uniform_allowance   = n(row['uniform_allowance']);
            if (row['laundry_allowance']       !== undefined) p.laundry_allowance   = n(row['laundry_allowance']);
            if (row['meal_allowance']          !== undefined) p.meal_allowance      = n(row['meal_allowance']);
            if (row['medical_allowance']       !== undefined) p.medical_allowance   = n(row['medical_allowance']);
            if (row['salary_adjustment']       !== undefined) p.salary_adjustment   = n(row['salary_adjustment']);
            if (row['sss_loan_salary']         !== undefined) p.sss_loan            = n(row['sss_loan_salary']);
            if (row['sss_loan_calamity']       !== undefined) p.calamity_loan       = n(row['sss_loan_calamity']);
            if (row['advances_to_employees']   !== undefined) p.salary_advance      = n(row['advances_to_employees']);
            if (row['absences_tardiness']      !== undefined) d.absentAmt           = n(row['absences_tardiness']);
            if (row['nsd'] !== undefined) {
                d.nightPremium = n(row['nsd']);
                // Zero individual night prems — imported NSD total already covers all of them
                d.regOtNightPrem=0; d.satNightPrem=0; d.sunNightPrem=0; d.holNightPrem=0;
            }
            if (row['holiday_pay'] !== undefined) {
                p.holiday_pay = n(row['holiday_pay']);
                // Zero sat/sun/hol OT — imported holiday total already covers them
                d.satOt=0; d.satOtGt10=0; d.sunOt=0; d.sunOtGt10=0; d.holOt=0; d.holOtGt10=0;
            }
            if (row['overtime'] !== undefined) {
                d.regOt=n(row['overtime']); d.satOt=0; d.sunOt=0; d.holOt=0;
                d.regOtGt10=0; d.satOtGt10=0; d.sunOtGt10=0; d.holOtGt10=0;
                d.regOtNightPrem=0; d.satNightPrem=0; d.sunNightPrem=0; d.holNightPrem=0;
            }
            matched++;
        });
        input.value = '';
        if (!matched) { showToast('No matching employees found. Ensure employee_id column matches.', 'error'); return; }
        renderBulkTabViews();
        showToast('Imported updates for ' + matched + ' employee(s).', 'success');
    };
    reader.readAsText(file);
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

// ================= AUTO-FILL RAW SHEET (individual mode only) =================
async function autoFillRawSheet() {
    const employeeId = document.getElementById('selectedEmployeeId').value;
    if (!employeeId) {
        showToast('Please select an employee first.', 'warning');
        return;
    }
    await fillEmployeeData(employeeId);
}

async function fillEmployeeData(employeeId, silent = false) {
    const periodStart = document.getElementById('periodStart').value;
    const periodEnd = document.getElementById('periodEnd').value;
    if (!periodStart || !periodEnd) {
        if (!silent) showToast('Please set a valid Pay Period.', 'warning');
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
            if (!silent) showToast('Could not load employee details: ' + (empData.error || `Server error ${empResp.status}`), 'error');
            return false;
        }
    } catch (err) {
        if (!silent) showToast('Could not load employee details: ' + err.message, 'error');
        return false;
    }

    document.getElementById('empPosition').value = empData.position || '';
    document.getElementById('monthlySalary').value = empData.monthly_salary || 0;
    const monthly = parseFloat(empData.monthly_salary) || 0;
    document.getElementById('semiMonthlySalary').value = (monthly / 2).toFixed(2);
    const dailyRateVal = empData.daily_rate ? parseFloat(empData.daily_rate) : (monthly * 12 / 261);
    document.getElementById('dailyRate').value = dailyRateVal.toFixed(2);
    // DOLE standard: hourly = daily / 8. Use stored daily_rate (basic pay, not total monthly incl. allowances).
    // Keep 4dp so OT/NSD computed from this field matches Excel precision.
    const hourlyRateVal = empData.hourly_rate ? parseFloat(empData.hourly_rate) : (dailyRateVal / 8);
    document.getElementById('hourlyRate').value = hourlyRateVal.toFixed(4);
    // Compute OT base hourly purely from monthly_salary and allowances_default (always returned).
    // OT base = (monthly - non-basic allowances) × 12/261/8 per DOLE.
    const _allowancesDef = parseFloat(empData.allowances_default) || 0;
    const _basicMonthly  = (_allowancesDef > 0 && _allowancesDef < monthly) ? (monthly - _allowancesDef) : monthly;
    const otHrVal = _basicMonthly * 12 / 261 / 8;
    document.getElementById('otHourlyRate').value = otHrVal.toFixed(4);

    // Amounts stored in employee management are MONTHLY figures.
    // Divide by 2 for 1st-half and 2nd-half periods; keep full amount for monthly.
    const _activePeriod = document.querySelector('.period-btn.active')?.dataset.period || 'first_half';
    const _dmDiv = (_activePeriod === 'monthly') ? 1 : 2;

    // Populate individual breakdown fields; fall back to total in rice subsidy if no breakdown saved
    const hasBreakdown = (empData.rice_subsidy > 0) || (empData.uniform_allowance > 0)
                      || (empData.laundry_allowance > 0) || (empData.medical_allowance > 0);
    if (hasBreakdown) {
        document.getElementById('riceSubsidy').value      = ((empData.rice_subsidy      || 0) / _dmDiv).toFixed(2);
        document.getElementById('uniformAllowance').value = ((empData.uniform_allowance  || 0) / _dmDiv).toFixed(2);
        document.getElementById('laundryAllowance').value = ((empData.laundry_allowance  || 0) / _dmDiv).toFixed(2);
        document.getElementById('medicalAllowance').value = ((empData.medical_allowance  || 0) / _dmDiv).toFixed(2);
    } else if (empData.de_minimis_default > 0) {
        document.getElementById('riceSubsidy').value      = (empData.de_minimis_default / _dmDiv).toFixed(2);
        document.getElementById('uniformAllowance').value = '0.00';
        document.getElementById('laundryAllowance').value = '0.00';
        document.getElementById('medicalAllowance').value = '0.00';
    } else {
        // Neither a breakdown nor a default total — reset explicitly so bulk-mode
        // generation doesn't silently carry over the previous employee's values.
        document.getElementById('riceSubsidy').value      = '0.00';
        document.getElementById('uniformAllowance').value = '0.00';
        document.getElementById('laundryAllowance').value = '0.00';
        document.getElementById('medicalAllowance').value = '0.00';
    }
    const _salAdjNonTaxEl = document.getElementById('salaryAdjustmentNonTax');
    if (_salAdjNonTaxEl) {
        _salAdjNonTaxEl.value = ((empData.other_non_taxable_default || 0) / _dmDiv).toFixed(2);
    }

    // 2. Fetch attendance summary
    const sumUrl = `<?= site_url('client_portal/payroll/attendance-summary') ?>/${employeeId}/${periodStart}/${periodEnd}`;
    let summary;
    try {
        const sumResp = await fetch(sumUrl);
        const sumData = await sumResp.json().catch(() => null);
        if (!sumResp.ok) {
            const msg = (sumData && sumData.error) ? sumData.error : `Server error ${sumResp.status}`;
            if (!silent) showToast('Could not load attendance summary: ' + msg, 'error');
            return false;
        }
        summary = sumData;
    } catch (err) {
        if (!silent) showToast('Could not load attendance summary: ' + err.message, 'error');
        return false;
    }

    // 3. Populate raw sheet with correct capping of absence deduction
    document.getElementById('absentDays').value = summary.absent_days;
    const breakHoursRefEl = document.getElementById('breakHoursRef');
    if (breakHoursRefEl) breakHoursRefEl.value = (summary.break_hours || 0).toFixed(2);
    const dailyRate = parseFloat(document.getElementById('dailyRate').value) || 0;
    const semiMonthlyPay = parseFloat(document.getElementById('semiMonthlySalary').value) || 0;
    let absentAmount = summary.absent_days * dailyRate;
    if (absentAmount > semiMonthlyPay) absentAmount = semiMonthlyPay;
    document.getElementById('absentPhp').value = absentAmount.toFixed(2);
    
    const tardyHours  = summary.tardy_minutes / 60;
    const hourlyRate  = parseFloat(document.getElementById('hourlyRate').value) || 0;
    const otHourlyRate = parseFloat(document.getElementById('otHourlyRate').value) || hourlyRate;
    const undertimeAmount = tardyHours * hourlyRate;
    document.getElementById('utHours').value = tardyHours.toFixed(2);
    document.getElementById('utPhp').value   = undertimeAmount.toFixed(2);
    
    const s_rates      = window._payrollSettings || {};
    const ndRate       = parseFloat(s_rates.night_diff_rate)  || 0.10;
    const regOtRate    = parseFloat(s_rates.ot_regular_rate)  || 1.25;
    const restBaseRate = parseFloat(s_rates.rest_day_rate)    || 1.30;
    const restOtRate   = parseFloat(s_rates.ot_rest_rate)     || 1.69;
    const regHolRate   = parseFloat(s_rates.reg_holiday_rate) || 2.00;
    const holOtRate    = parseFloat(s_rates.ot_holiday_rate)  || 2.60;
    // Excel-matching NSD multipliers:
    // Sat NSD: J×130%×10% = 13%  (Raw Sheet: AP8 = J8*130%*10%*AO8)
    // Sun NSD: J×30%×10%  = 3%   (Raw Sheet: AV8 = J8*0.3*0.1*AU8)
    // Reg Hol NSD: J×200%×10% = 20%
    const satNsdMult = restBaseRate * ndRate;
    const sunNsdMult = (restBaseRate - 1.0) * ndRate;
    const holNdMult  = regHolRate * ndRate;

    const _sv = (id, val) => { const el = document.getElementById(id); if (el) el.value = (typeof val === 'number' ? val.toFixed(2) : String(val)); };

    // Reset all OT / NSD fields before repopulating.
    // regHolHours is also cleared so a prior manual entry cannot double-count
    // with the holiday premium that autofill places in holOtPhp.
    ['workdayOtHours','workdayOtPhp','regOtGt10Hours','regOtGt10Php','regOtNightHours','regOtNightPhp',
     'workdayNightDiffHours','workdayNightDiffPhp',
     'satOtHours','satOtPhp','satOtGt10Hours','satOtGt10Php','satNightDiffHours','satNightDiffPhp',
     'sunOtHours','sunOtPhp','sunOtGt10Hours','sunOtGt10Php','sunNightDiffHours','sunNightDiffPhp',
     'holOtHours','holOtPhp','holOtGt10Hours','holOtGt10Php','holNightDiffHours','holNightDiffPhp',
     'regHolHours','regHolPay',
    ].forEach(id => { const el = document.getElementById(id); if (el) el.value = '0'; });
    const _vld = document.getElementById('vlDaysRaw'); if (_vld) _vld.value = '0';
    const _vlp = document.getElementById('vlCashPhp'); if (_vlp) _vlp.value = '0';
    calcRawVLCash();

    // === WORKDAY OT (J × 1.25) — split ≤2h OT and >2h OT (matching ≤10h / >10h fields) ===
    const regOtTotal   = summary.reg_ot_hours || 0;
    const regOtLe10    = Math.min(regOtTotal, 2);
    const regOtGt10    = Math.max(0, regOtTotal - 2);
    const regOtPay     = regOtLe10 * otHourlyRate * regOtRate;
    const regOtGt10Pay = regOtGt10 * otHourlyRate * regOtRate;
    _sv('workdayOtHours', regOtLe10);  _sv('workdayOtPhp', regOtPay);
    _sv('regOtGt10Hours', regOtGt10);  _sv('regOtGt10Php', regOtGt10Pay);

    // === WORKDAY NSD (J × 10%) ===
    const wdNsdHrs = summary.workday_nsd_hours || 0;
    const wdNsdPay = wdNsdHrs * otHourlyRate * ndRate;
    _sv('workdayNightDiffHours', wdNsdHrs);
    _sv('workdayNightDiffPhp', wdNsdPay);

    // === SATURDAY — regular ≤8h: J×1.30 | OT >8h: J×1.69 | NSD: J×0.13 (130%×10%) ===
    const satRegHrs = summary.sat_reg_hours || 0;
    const satOtHrs  = summary.sat_ot_hours  || 0;
    const satNsdHrs = summary.sat_nsd_hours || 0;
    const satRegPay = satRegHrs * otHourlyRate * restBaseRate;
    const satOtPay  = satOtHrs  * otHourlyRate * restOtRate;
    const satNsdPay = satNsdHrs * otHourlyRate * satNsdMult;
    _sv('satOtHours', satRegHrs);        _sv('satOtPhp', satRegPay);
    _sv('satOtGt10Hours', satOtHrs);     _sv('satOtGt10Php', satOtPay);
    _sv('satNightDiffHours', satNsdHrs); _sv('satNightDiffPhp', satNsdPay);

    // === SUNDAY — regular ≤8h: J×1.30 | OT >8h: J×1.69 | NSD: J×0.03 (30%×10%) ===
    const sunRegHrs = summary.sun_reg_hours || 0;
    const sunOtHrs  = summary.sun_ot_hours  || 0;
    const sunNsdHrs = summary.sun_nsd_hours || 0;
    const sunRegPay = sunRegHrs * otHourlyRate * restBaseRate;
    const sunOtPay  = sunOtHrs  * otHourlyRate * restOtRate;
    const sunNsdPay = sunNsdHrs * otHourlyRate * sunNsdMult;
    _sv('sunOtHours', sunRegHrs);        _sv('sunOtPhp', sunRegPay);
    _sv('sunOtGt10Hours', sunOtHrs);     _sv('sunOtGt10Php', sunOtPay);
    _sv('sunNightDiffHours', sunNsdHrs); _sv('sunNightDiffPhp', sunNsdPay);

    // === SPECIAL HOLIDAY — regular +30% premium | OT >8h: 169% ===
    const _spHolEl   = document.getElementById('specHolHours');
    const _spHolOtEl  = document.getElementById('specHolOtHours');
    const _spHolNsdEl = document.getElementById('specHolNsdHours');
    if (_spHolEl)    _spHolEl.value    = (summary.spec_hol_reg_hours || 0).toFixed(2);
    if (_spHolOtEl)  _spHolOtEl.value  = (summary.spec_hol_ot_hours  || 0).toFixed(2);
    if (_spHolNsdEl) _spHolNsdEl.value = (summary.spec_hol_nsd_hours || 0).toFixed(2);
    if (_spHolEl || _spHolOtEl || _spHolNsdEl) calcHolPay();
    if (_spHolNsdEl && parseFloat(_spHolNsdEl.value) > 0) calcRawNightPay('specHolNsdHours','specHolNsdPay','restDay');
    _specHolHint();

    // === REGULAR HOLIDAY — regular ≤8h: J×1.0 | OT >8h: J×2.60 | NSD: J×0.20 ===
    const holRegHrs    = summary.hol_reg_hours || 0;
    const holOtHrs     = summary.hol_ot_hours  || 0;
    const holNsdHrs    = summary.hol_nsd_hours || 0;
    const holRegPay    = holRegHrs * otHourlyRate * (regHolRate - 1.0);
    const holOtGt10Pay = holOtHrs  * otHourlyRate * holOtRate;
    const holNsdPay    = holNsdHrs * otHourlyRate * holNdMult;
    _sv('holOtHours', holRegHrs);        _sv('holOtPhp', holRegPay);
    _sv('holOtGt10Hours', holOtHrs);     _sv('holOtGt10Php', holOtGt10Pay);
    _sv('holNightDiffHours', holNsdHrs); _sv('holNightDiffPhp', holNsdPay);

    const emp = employeesList.find(e => e.id == employeeId);
    if (emp) document.getElementById('empName').value = emp.name;
    document.getElementById('selectedEmployeeId').value = employeeId;

    updateContributions(monthly);

    // Sync payslip tab period dates so autoDetectPeriodType reads the right dates,
    // then detect period type before computing WT (ensures 2nd-half uses cumulative method).
    const _rawStart = document.getElementById('periodStart')?.value;
    const _rawEnd   = document.getElementById('periodEnd')?.value;
    const _pStart = document.getElementById('payslipPeriodStart');
    const _pEnd   = document.getElementById('payslipPeriodEnd');
    if (_pStart && _rawStart) _pStart.value = _rawStart;
    if (_pEnd   && _rawEnd)   _pEnd.value   = _rawEnd;
    autoDetectPeriodType();

    // Taxable base for withholding tax: basic + all OT/NSD/holiday pay − deductions
    const salAdj        = parseFloat(document.getElementById('salaryAdjustment').value || 0)
                        + parseFloat(document.getElementById('overrideAmount').value   || 0)
                        + parseFloat(document.getElementById('nedAmount').value        || 0);
    const specHolPayAmt   = safeParseFloat(document.getElementById('specHolPay')?.value);
    const specHolOtPayAmt  = safeParseFloat(document.getElementById('specHolOtPay')?.value);
    const specHolNsdPayAmt = safeParseFloat(document.getElementById('specHolNsdPay')?.value);
    const taxableBase   = (monthly / 2)
                        + wdNsdPay  + regOtPay  + regOtGt10Pay
                        + satRegPay + satOtPay  + satNsdPay
                        + sunRegPay + sunOtPay  + sunNsdPay
                        + holRegPay + holOtGt10Pay + holNsdPay + specHolPayAmt + specHolOtPayAmt + specHolNsdPayAmt
                        + salAdj - absentAmount - undertimeAmount;
    updateWithholdingTax(Math.max(0, taxableBase));

    // ------------------------------------------------------
    // Fetch loans for this payroll period
    // ------------------------------------------------------
    try {

        const loanUrl =
            `<?= site_url('client_portal/payroll/loanBreakdown') ?>/${employeeId}/${periodStart}/${periodEnd}`;

        const loanResp = await fetch(loanUrl);

        if (!loanResp.ok) {
            throw new Error(
                `Server error ${loanResp.status}`
            );
        }

        const loans = await loanResp.json();

        console.log('Loans:', loans);

        // Reset all loan fields first
        document.getElementById('sssSalaryLoan').value = 0;
        document.getElementById('sssCalamityLoan').value = 0;

        document.getElementById('hdmfLoan').value = 0;
        document.getElementById('hdmfMplLoan').value = 0;
        document.getElementById('hdmfCalamityLoan').value = 0;

        document.getElementById('advSalary').value = 0;
        document.getElementById('advCash').value = 0;
        document.getElementById('advEmergency').value = 0;

        loans.forEach(loan => {
            const amount = parseFloat(loan.amount || 0);
            const category = (loan.loan_category || '').trim().toLowerCase();
            const loanType = (loan.loan_type || '').trim().toLowerCase();

            switch (loanType) {

                case 'salary loan':
                    addAmount('sssSalaryLoan', amount);
                    break;

                case 'hdmf (pag-ibig) loan':
                case 'hdmf loan':
                case 'pag-ibig loan':
                    addAmount('hdmfLoan', amount);
                    break;

                case 'multi-purpose loan':
                case 'mpl':
                    addAmount('hdmfMplLoan', amount);
                    break;

                case 'calamity loan':
                    if (category === 'sss loans') {
                        addAmount('sssCalamityLoan', amount);
                    }
                    else if (category === 'mp2 savings program') {
                        addAmount('hdmfCalamityLoan', amount);
                    }
                    break;

                case 'salary advance':
                    addAmount('advSalary', amount);
                    break;

                case 'cash advance':
                    addAmount('advCash', amount);
                    break;

                case 'emergency advance':
                    addAmount('advEmergency', amount);
                    break;
            }
        });

    } catch (err) {
        console.error(
            'Loan fetch error:',
            err
        );
    }

    if (!silent) showToast('Raw sheet auto-filled with attendance data.', 'success');
    return true;
}

function addAmount(id, amount) {
    const el = document.getElementById(id);
    if (!el) return;

    el.value =
    (
        parseFloat(el.value || 0)
        + amount
    ).toFixed(2);
}

// ── SSS / PHIC / HDMF auto-fill (2025 schedule, employee share only) ──────
// Hardcoded fallback — used only if the sss_brackets table (edited via Payroll
// Settings → SSS Brackets) hasn't loaded yet or the fetch failed. Kept in sync
// with that table's values (verified against the client's "2025 SSS Table"
// sheet: range_start = lower bound of the bracket; ee_regular = 5% × Regular
// SS MSC capped at 20,000; ee_wisp = 5% × WISP MSC from MSC 20,500+; the 20250
// bracket's wisp share is 50, not the "expected" 25 from a clean per-500
// progression — the first WISP bracket isn't a smooth continuation of the
// progression seen from 20750 onward, matching the official reference table).
const SSS_FALLBACK_TABLE = [
    [0,    250,  0],[5250, 275,  0],[5750, 300,  0],[6250, 325,  0],[6750, 350,  0],
    [7250, 375,  0],[7750, 400,  0],[8250, 425,  0],[8750, 450,  0],[9250, 475,  0],
    [9750, 500,  0],[10250,525,  0],[10750,550,  0],[11250,575,  0],[11750,600,  0],
    [12250,625,  0],[12750,650,  0],[13250,675,  0],[13750,700,  0],[14250,725,  0],
    [14750,750,  0],[15250,775,  0],[15750,800,  0],[16250,825,  0],[16750,850,  0],
    [17250,875,  0],[17750,900,  0],[18250,925,  0],[18750,950,  0],[19250,975,  0],
    [19750,1000, 0],
    [20250,1000, 50],[20750,1000, 50],[21250,1000, 75],[21750,1000,100],[22250,1000,125],
    [22750,1000,150],[23250,1000,175],[23750,1000,200],[24250,1000,225],[24750,1000,250],
    [25250,1000,275],[25750,1000,300],[26250,1000,325],[26750,1000,350],[27250,1000,375],
    [27750,1000,400],[28250,1000,425],[28750,1000,450],[29250,1000,475],[29750,1000,500],
    [30250,1000,525],[30750,1000,550],[31250,1000,575],[31750,1000,600],[32250,1000,625],
    [32750,1000,650],[33250,1000,675],[33750,1000,700],[34250,1000,725],[34750,1000,750],
];

function computeSSS(monthlySalary) {
    // Prefer the live DB-backed table (bundled into getSettings() as sss_brackets)
    // so edits made in Payroll Settings actually change real payroll math.
    const dbBrackets = window._payrollSettings?.sss_brackets;
    const t = (Array.isArray(dbBrackets) && dbBrackets.length)
        ? dbBrackets.map(b => [parseFloat(b.range_start) || 0, parseFloat(b.ee_regular) || 0, parseFloat(b.ee_wisp) || 0])
        : SSS_FALLBACK_TABLE;
    let row = t[t.length - 1];
    for (let i = t.length - 1; i >= 0; i--) { if (monthlySalary >= t[i][0]) { row = t[i]; break; } }
    return { regular: row[1], wisp: row[2], total: row[1] + row[2] };
}

(function() {
    fetch('<?= site_url("client_portal/payroll/getSettings") ?>')
        .then(function(r) { return r.json(); })
        .then(function(s) {
            window._payrollSettings = s;
            resolvePeriodDates();
            syncBulkPeriod();
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
    const hr  = parseFloat(document.getElementById('otHourlyRate')?.value || 0) || parseFloat(document.getElementById('hourlyRate')?.value || 0);
    const rh  = parseFloat(document.getElementById('regHolHours')?.value    || 0);
    const sh  = parseFloat(document.getElementById('specHolHours')?.value   || 0);
    const sho = parseFloat(document.getElementById('specHolOtHours')?.value || 0);
    const regRate   = (parseFloat(window._payrollSettings?.reg_holiday_rate)  || 2.00) - 1.0;
    const specRate  = (parseFloat(window._payrollSettings?.spec_holiday_rate) || 1.30) - 1.0;
    // Spec Holiday OT: 169% (DOLE: 130% rest-day OT rate applies on special holiday OT)
    const specOtRate = parseFloat(window._payrollSettings?.ot_rest_rate) || 1.69;
    document.getElementById('regHolPay').value    = (rh  * hr * regRate).toFixed(2);
    document.getElementById('specHolPay').value   = (sh  * hr * specRate).toFixed(2);
    document.getElementById('specHolOtPay').value = (sho * hr * specOtRate).toFixed(2);
    updateHolidayTotal();
    document.getElementById('specHolHint')?.style.setProperty('display','none');
}
function _specHolHint() {
    const val  = safeParseFloat(document.getElementById('specHolPay')?.value);
    const hint = document.getElementById('specHolHint');
    const amt  = document.getElementById('specHolHintAmt');
    if (!hint || !amt) return;
    if (val > 0) {
        amt.textContent = '₱' + val.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
        hint.style.display = 'block';
    } else {
        hint.style.display = 'none';
    }
}

function updateHolidayTotal() {
    const reg    = parseFloat(document.getElementById('regHolPay')?.value    || 0);
    const spec   = parseFloat(document.getElementById('specHolPay')?.value   || 0);
    const specOt  = parseFloat(document.getElementById('specHolOtPay')?.value  || 0);
    const specNsd = parseFloat(document.getElementById('specHolNsdPay')?.value || 0);
    document.getElementById('holidayPay').value = (reg + spec + specOt + specNsd).toFixed(2);
}

function updateContributions(monthlySalary) {
    const { regular: sssReg, wisp: sssWisp, total: sss } = computeSSS(monthlySalary);

    const phicRate = (parseFloat(window._payrollSettings?.phic_rate) || 0.05) / 2;
    const phicCap  = parseFloat(window._payrollSettings?.phic_cap)  || 100000;
    // No minimum base — verified against the client's live Payroll Register formula
    // (IF(salary<=100000, salary*5%/2, 100000*2.5%)), which applies the straight
    // percentage with no floor for low earners.
    const phic = Math.min(monthlySalary, phicCap) * phicRate;

    const hdmfMax  = parseFloat(window._payrollSettings?.hdmf_max)  || 200;
    const hdmfRate = parseFloat(window._payrollSettings?.hdmf_rate) || 0.02;
    const hdmf = Math.min(hdmfMax, monthlySalary * hdmfRate);

    // Register fields show 0 for 1st half — contributions are only deducted on 2nd half.
    // Actual amounts are still returned for use in payslip computation.
    const activePeriod = document.querySelector('.period-btn.active')?.dataset.period || 'first_half';
    const regSss  = (activePeriod === 'first_half') ? 0 : sss;
    const regWisp = (activePeriod === 'first_half') ? 0 : sssWisp;
    const regPhic = (activePeriod === 'first_half') ? 0 : phic;
    const regHdmf = (activePeriod === 'first_half') ? 0 : hdmf;

    document.getElementById('sssEE').value    = regSss.toFixed(2);
    if (document.getElementById('sssWisp'))  document.getElementById('sssWisp').value  = regWisp.toFixed(2);
    if (document.getElementById('sssTotal')) document.getElementById('sssTotal').value = regSss.toFixed(2);
    document.getElementById('phicEE').value   = regPhic.toFixed(2);
    document.getElementById('hdmfEE').value   = regHdmf.toFixed(2);

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
        else if (t <= 33333)  return          0 + (t -  20833) * 0.15;
        else if (t <= 66667)  return    1875.00 + (t -  33333) * 0.20;
        else if (t <= 166667) return    8541.80 + (t -  66667) * 0.25;
        else if (t <= 666667) return   33541.80 + (t - 166667) * 0.30;
        else                  return  183541.80 + (t - 666667) * 0.35;
    };

    let wt = 0;

    if (periodType === 'first_half') {
        // Semi-monthly brackets on gross taxable — no contribution deduction
        wt = semiMonthlyBracket(currentGross);

    } else if (periodType === 'second_half') {
        // Cumulative monthly: (prior net taxable + current net taxable) via monthly brackets, minus prior WT.
        // Work in integer centavos for the subtraction so floating-point cannot cause off-by-one rounding.
        const { sss, phic, hdmf } = getContribs();
        const priorNetTaxable   = safeParseFloat(document.getElementById('edit_priorGross')?.value);
        const priorWT           = safeParseFloat(document.getElementById('edit_priorWT')?.value);
        const currentNetTaxable = Math.max(0, currentGross - sss - phic - hdmf);
        const monthlyTaxable    = Math.max(0, priorNetTaxable + currentNetTaxable);
        const monthlyWT         = monthlyBrackets(monthlyTaxable);
        // Round monthlyWT to centavos first, then subtract priorWT in integer cents to avoid
        // float64 errors (e.g. 9183.2175 losing a fractional bit and rounding the wrong way).
        const monthlyWTCents    = Math.round(monthlyWT * 100);
        const priorWTCents      = Math.round(priorWT * 100);
        // Allow negative result: negative means employee overpaid in 1st half (refund due).
        wt = (monthlyWTCents - priorWTCents) / 100;
        // Show computation breakdown so values can be verified
        const statusEl = document.getElementById('priorHalfStatus');
        if (statusEl && statusEl.style.display !== 'none') {
            const contrib = sss + phic + hdmf;
            const refundNote = wt < 0 ? ` <span style="color:#d97706">(REFUND: ${Math.abs(wt).toFixed(2)} added to net pay)</span>` : '';
            statusEl.innerHTML =
                `✓ 2nd-half cumulative: ` +
                `Prior net taxable <b>${priorNetTaxable.toFixed(2)}</b> + ` +
                `Current net taxable <b>${currentNetTaxable.toFixed(2)}</b> ` +
                `(${currentGross.toFixed(2)} − ${contrib.toFixed(2)} contribs) = ` +
                `Monthly taxable <b>${monthlyTaxable.toFixed(2)}</b> → ` +
                `Monthly WT <b>${monthlyWT.toFixed(4)}</b> − Prior WT <b>${priorWT.toFixed(2)}</b> = ` +
                `<b>${wt.toFixed(2)}</b>${refundNote}`;
        }

    } else { // monthly
        // Monthly brackets applied directly to (gross − contributions)
        const { sss, phic, hdmf } = getContribs();
        const netTaxable = Math.max(0, currentGross - sss - phic - hdmf);
        wt = monthlyBrackets(netTaxable);
    }

    // Round to centavo. Semi-monthly bracket values are exact centavos so this is the same
    // as truncation for 1st half; for 2nd-half cumulative the prior-WT subtraction can
    // produce a mid-centavo fraction (e.g. 9183.2175) that must be rounded, not floored,
    // to match the BIR reference computation.
    wt = Math.round(wt * 100) / 100;
    document.getElementById('wtPayable').value = wt.toFixed(2);
    return wt;
}

// ── Raw-sheet helper calculators ─────────────────────────────────────────────
function calcRawNightPay(hoursId, payId, rateKey) {
    const hrs = safeParseFloat(document.getElementById(hoursId)?.value);
    const hr  = safeParseFloat(document.getElementById('otHourlyRate')?.value) || safeParseFloat(document.getElementById('hourlyRate')?.value);
    const nd  = parseFloat(window._payrollSettings?.night_diff_rate) || 0.10;
    const s   = window._payrollSettings || {};
    // NSD multipliers per day type (matches Excel Raw Sheet formulas):
    // Workday: 1.00 × 10% = 10%
    // Saturday: 1.30 × 10% = 13%  (AP8 = J8*130%*10%*AO8)
    // Sunday:   0.30 × 10% = 3%   (AV8 = J8*0.3*0.1*AU8) — only the additional 30% premium × 10%
    // Reg Holiday: 2.00 × 10% = 20%
    const restBase = parseFloat(s.rest_day_rate)    || 1.30;
    const baseRates = {
        restDay:    restBase,              // Saturday NSD: 130% × 10% = 13%
        restDayAdd: restBase - 1.0,        // Sunday NSD: 30% × 10% = 3%
        holiday:    parseFloat(s.reg_holiday_rate) || 2.00,  // Reg Holiday NSD: 200% × 10% = 20%
    };
    const mult = rateKey ? (baseRates[rateKey] || 1) : 1;
    const el   = document.getElementById(payId);
    if (el) el.value = (hrs * hr * mult * nd).toFixed(2);
}
function calcRawOtPay(hoursId, payId, rateKey) {
    const hrs = safeParseFloat(document.getElementById(hoursId)?.value);
    const hr  = safeParseFloat(document.getElementById('otHourlyRate')?.value) || safeParseFloat(document.getElementById('hourlyRate')?.value);
    const s   = window._payrollSettings || {};
    const rates = {
        regOt:       parseFloat(s.ot_regular_rate)  || 1.25,  // Workday OT: 125%
        restDayBase: parseFloat(s.rest_day_rate)    || 1.30,  // Rest-day ≤8hrs: 130%
        restOt:      parseFloat(s.ot_rest_rate)     || 1.69,  // Rest-day >8hrs: 169%
        holBase:     (parseFloat(s.reg_holiday_rate) || 2.00) - 1.0,  // Reg Holiday ≤8hrs: +100% additional (basic covers other 100%)
        holOt:       parseFloat(s.ot_holiday_rate)  || 2.60,  // Reg Holiday OT: 260%
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

// ================= REGISTER PERIOD VISIBILITY =================
// Shows/hides fields and updates labels in the Register tab based on period type.
// Called by syncToRegister() so the register always matches the active period.
function applyRegisterPeriodVisibility(periodType) {
    const is2nd = (periodType === 'second_half');
    const isMon = (periodType === 'monthly');

    // "Net Taxable (1st Half)" and "WT Paid Last Cut-Off" are only meaningful for 2nd half
    const priorTaxWrap = document.getElementById('reg-netTaxProv-wrap');
    if (priorTaxWrap) priorTaxWrap.style.display = is2nd ? '' : 'none';
    const wtPaidWrap = document.getElementById('reg-wtPaidLast-wrap');
    if (wtPaidWrap) wtPaidWrap.style.display = is2nd ? '' : 'none';

    // Labels that change meaning per period
    const netTaxLbl = document.getElementById('reg-netTaxable-lbl');
    if (netTaxLbl) {
        netTaxLbl.textContent = (periodType === 'first_half')
            ? 'Net Taxable Comp. (this cut-off)'
            : 'Regular Net Taxable Comp.';
    }
    const monthlyTaxLbl = document.getElementById('reg-monthlyTax-lbl');
    if (monthlyTaxLbl) {
        monthlyTaxLbl.textContent = is2nd
            ? 'Monthly Taxable Comp. (1st + 2nd Half)'
            : (isMon ? 'Monthly Taxable Compensation' : 'Taxable Compensation (semi-monthly)');
    }

    // WT method note next to section title
    const wtNote = document.getElementById('reg-wt-method-note');
    if (wtNote) {
        wtNote.textContent = {
            first_half:  '— semi-monthly brackets on gross taxable',
            second_half: '— cumulative monthly method (prior + current)',
            monthly:     '— monthly brackets on net taxable',
        }[periodType] || '';
    }

    // Contribution note
    const cNote = document.getElementById('reg-contrib-note');
    if (cNote) {
        cNote.textContent = (periodType === 'first_half')
            ? '(not deducted on 1st half — applied on 2nd half)'
            : '(deducted from net pay and taxable income)';
    }
}

// ================= SYNC RAW SHEET TO REGISTER =================
async function syncToRegister() {
    _payslipEdited = false; // allow buildPayslipTable to fully rebuild on next tab visit
    const monthly     = parseFloat(document.getElementById('monthlySalary').value) || 0;
    const semiMonthly = monthly / 2;
    document.getElementById('monthlyBasicSalary').value     = monthly;
    document.getElementById('semiMonthlyBasicSalary').value = semiMonthly.toFixed(2);

    // De minimis items (non-taxable)
    document.getElementById('deMinYearEnd').value     = document.getElementById('yearEndGifts').value;
    document.getElementById('deMinRiceSubsidy').value = document.getElementById('riceSubsidy').value;
    document.getElementById('deMinUniform').value     = document.getElementById('uniformAllowance').value;
    document.getElementById('deMinLaundry').value     = document.getElementById('laundryAllowance').value;
    if (document.getElementById('deMinMealAllowance'))
        document.getElementById('deMinMealAllowance').value = document.getElementById('mealAllowance')?.value || 0;
    document.getElementById('deMinMedical').value     = (parseFloat(document.getElementById('medicalAllowance').value) + parseFloat(document.getElementById('medicalAllowanceAdd').value)) || 0;
    const totalDeMin = ['deMinYearEnd','deMinRiceSubsidy','deMinUniform','deMinLaundry','deMinMealAllowance','deMinMedical']
        .reduce((s, id) => s + (parseFloat(document.getElementById(id)?.value) || 0), 0)
        + (parseFloat(document.getElementById('salaryAdjustmentNonTax')?.value) || 0);

    const _totalSalAdj = (parseFloat(document.getElementById('salaryAdjustment')?.value) || 0)
                       + (parseFloat(document.getElementById('overrideAmount')?.value)    || 0)
                       + (parseFloat(document.getElementById('nedAmount')?.value)         || 0);
    document.getElementById('salaryAdjustmentReg').value = _totalSalAdj.toFixed(2);
    // OT column = Workday OT + Saturday regular + Saturday OT (matches Excel N = AH8+AL8+AN8)
    const overtime = parseFloat(document.getElementById('workdayOtPhp')?.value    || 0)
                   + parseFloat(document.getElementById('regOtGt10Php')?.value    || 0)
                   + parseFloat(document.getElementById('satOtPhp')?.value        || 0)
                   + parseFloat(document.getElementById('satOtGt10Php')?.value    || 0);
    document.getElementById('overtimeReg').value = overtime.toFixed(2);
    // NSD column = Workday NSD + Sat NSD + Sun NSD + Reg Hol NSD (matches Excel O = AJ8+AP8+AV8+BH8)
    const nd = parseFloat(document.getElementById('workdayNightDiffPhp')?.value || 0)
             + parseFloat(document.getElementById('satNightDiffPhp')?.value     || 0)
             + parseFloat(document.getElementById('sunNightDiffPhp')?.value     || 0)
             + parseFloat(document.getElementById('holNightDiffPhp')?.value     || 0);
    document.getElementById('ndReg').value = nd.toFixed(2);
    const absAmt = parseFloat(document.getElementById('absentPhp').value || 0)
                 + parseFloat(document.getElementById('utPhp').value     || 0);
    document.getElementById('absencesTardiness').value = absAmt.toFixed(2);
    // Holiday Pay = holiday pay + Sun reg + Sun OT + Reg Hol OT (Saturday is in OT column)
    const holPay = parseFloat(document.getElementById('holidayPay').value    || 0)
                 + parseFloat(document.getElementById('sunOtPhp')?.value     || 0)
                 + parseFloat(document.getElementById('sunOtGt10Php')?.value || 0)
                 + parseFloat(document.getElementById('holOtPhp')?.value     || 0)
                 + parseFloat(document.getElementById('holOtGt10Php')?.value || 0);
    document.getElementById('holidayPayReg').value = holPay.toFixed(2);
    const salAdj = parseFloat(document.getElementById('salaryAdjustmentReg').value || 0);

    // Gross Taxable = basic + OT + ND + holiday pay + salary adj − absences (no de minimis)
    // Gross Compensation = Gross Taxable + de minimis + VL Cash + non-taxable sal adj
    const vlCash       = parseFloat(document.getElementById('vlCashPhp')?.value || 0);
    const salNonTaxAdj = parseFloat(document.getElementById('salNonTaxAdj')?.value || 0);
    const otherNonTax  = vlCash + salNonTaxAdj;
    const grossTaxable = semiMonthly + overtime + nd + holPay + salAdj - absAmt;
    const grossComp    = Math.round((grossTaxable + totalDeMin + otherNonTax) * 100) / 100;

    // Ensure period type is detected and prior-half data is loaded before computing WT.
    // payslipPeriodStart is only set when the payslip tab is visited; sync it here from the
    // raw-sheet's periodStart so fetchAndSetPriorHalfWT can build the correct cache key.
    const _rawStart = document.getElementById('periodStart')?.value;
    const _rawEnd   = document.getElementById('periodEnd')?.value;
    if (_rawStart) {
        const _psl = document.getElementById('payslipPeriodStart');
        if (_psl) _psl.value = _rawStart;
    }
    if (_rawEnd) {
        const _pel = document.getElementById('payslipPeriodEnd');
        if (_pel) _pel.value = _rawEnd;
    }
    autoDetectPeriodType();
    await fetchAndSetPriorHalfWT();

    // For 2nd half: now that prior_gross is available, update SSS using total monthly compensation.
    const _srPeriodType = document.getElementById('payslipPeriodType')?.value;
    if (_srPeriodType === 'second_half') {
        const _srPriorGross  = safeParseFloat(document.getElementById('edit_priorGrossTotal')?.value);
        if (_srPriorGross > 0) {
            // Use normalised gross: base earnings + |salary adj| + 1st-half de minimis.
            const _srRawSalAdj   = safeParseFloat(document.getElementById('salaryAdjustment')?.value);
            const _srPriorDeMin  = safeParseFloat(document.getElementById('edit_priorDeMin')?.value);
            const _srBaseEarnings = semiMonthly + overtime + nd + holPay - absAmt;
            const _srGrossForSSS  = _srPriorDeMin > 0
                ? (_srBaseEarnings + Math.abs(_srRawSalAdj) + _srPriorDeMin)
                : grossComp;
            const _srSSSComp = computeSSS(_srPriorGross + _srGrossForSSS);
            ['sssEE', 'sssTotal', 'edit_sss'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = _srSSSComp.total.toFixed(2);
            });
            const _wispEl = document.getElementById('sssWisp');
            if (_wispEl) _wispEl.value = _srSSSComp.wisp.toFixed(2);
        }
    }

    updateWithholdingTax(Math.max(0, grossTaxable));
    document.getElementById('grossCompReg').value     = grossComp.toFixed(2);
    document.getElementById('grossTaxableComp').value = grossTaxable.toFixed(2);
    if (document.getElementById('grossNonTaxableComp'))
        document.getElementById('grossNonTaxableComp').value = (totalDeMin + otherNonTax).toFixed(2);
    if (document.getElementById('otherNonTaxable'))
        document.getElementById('otherNonTaxable').value = otherNonTax.toFixed(2);

    // Government contributions — already set by updateContributions(); read back for downstream use
    const sss  = parseFloat(document.getElementById('sssEE').value  || 0); // total EE (regular + WISP)
    const phic = parseFloat(document.getElementById('phicEE').value || 0);
    const hdmf = parseFloat(document.getElementById('hdmfEE').value || 0);
    // sssEE already holds regular + WISP total; sssTotal is the same value
    if (document.getElementById('sssTotal'))
        document.getElementById('sssTotal').value = sss.toFixed(2);

    // ── Period-type-based net taxable fields ──────────────────────────────────
    // 1st half: BIR applies semi-monthly brackets on gross taxable — no contribution deduction.
    // 2nd half: contributions deducted; cumulative monthly method via prior-half data.
    // Monthly:  contributions deducted; monthly brackets applied directly.
    const periodType = document.getElementById('payslipPeriodType')?.value || 'first_half';
    let regNetTaxable, netTaxProv, monthlyTaxableComp;
    if (periodType === 'first_half') {
        regNetTaxable      = Math.max(0, grossTaxable);
        netTaxProv         = 0;
        monthlyTaxableComp = regNetTaxable;
    } else if (periodType === 'second_half') {
        regNetTaxable      = Math.max(0, grossTaxable - sss - phic - hdmf);
        netTaxProv         = parseFloat(document.getElementById('edit_priorGross')?.value || 0);
        monthlyTaxableComp = Math.max(0, netTaxProv + regNetTaxable);
    } else {
        regNetTaxable      = Math.max(0, grossTaxable - sss - phic - hdmf);
        netTaxProv         = 0;
        monthlyTaxableComp = regNetTaxable;
    }
    document.getElementById('regNetTaxable').value      = regNetTaxable.toFixed(2);
    document.getElementById('netTaxProv').value         = netTaxProv.toFixed(2);
    document.getElementById('monthlyTaxableComp').value = monthlyTaxableComp.toFixed(2);

    // ── WHT bracket breakdown ─────────────────────────────────────────────────
    // Returns { lo (bracket floor), fixedTax (fixed amount), rate, excess (taxable − lo) }
    const _bracketInfo = (taxable, isSemiMonthly) => {
        let lo, fixedTax, rate;
        if (isSemiMonthly) {
            if      (taxable <= 10417)  { lo =      0; fixedTax =          0; rate = 0;    }
            else if (taxable <= 16667)  { lo =  10417; fixedTax =          0; rate = 0.15; }
            else if (taxable <= 33333)  { lo =  16667; fixedTax =     937.50; rate = 0.20; }
            else if (taxable <= 83333)  { lo =  33333; fixedTax =    4270.70; rate = 0.25; }
            else if (taxable <= 333333) { lo =  83333; fixedTax =   16770.70; rate = 0.30; }
            else                        { lo = 333333; fixedTax =   91770.00; rate = 0.35; }
        } else {
            if      (taxable <= 20833)  { lo =      0; fixedTax =          0; rate = 0;    }
            else if (taxable <= 33333)  { lo =  20833; fixedTax =          0; rate = 0.15; }
            else if (taxable <= 66667)  { lo =  33333; fixedTax =    1875.00; rate = 0.20; }
            else if (taxable <= 166667) { lo =  66667; fixedTax =    8541.80; rate = 0.25; }
            else if (taxable <= 666667) { lo = 166667; fixedTax =   33541.80; rate = 0.30; }
            else                        { lo = 666667; fixedTax =  183541.80; rate = 0.35; }
        }
        return { lo, fixedTax, rate, excess: Math.max(0, taxable - lo) };
    };
    const wtTaxable = (periodType === 'first_half') ? Math.max(0, grossTaxable) : monthlyTaxableComp;
    const bd = _bracketInfo(wtTaxable, periodType === 'first_half');
    if (document.getElementById('wtBracket'))
        document.getElementById('wtBracket').value = bd.lo.toFixed(2);
    if (document.getElementById('wtBasic'))
        document.getElementById('wtBasic').value = bd.lo.toFixed(2);
    if (document.getElementById('wtPercentage'))
        document.getElementById('wtPercentage').value = (bd.rate * 100).toFixed(0) + '%';
    if (document.getElementById('wtBasic2'))
        document.getElementById('wtBasic2').value = bd.fixedTax.toFixed(2);
    if (document.getElementById('wtExcess'))
        document.getElementById('wtExcess').value = bd.excess.toFixed(2);

    // WT paid in prior cut-off (2nd half only — 1st half always starts at 0)
    const priorWT = (periodType === 'second_half')
        ? parseFloat(document.getElementById('edit_priorWT')?.value || 0)
        : 0;
    if (document.getElementById('wtPaidLast'))
        document.getElementById('wtPaidLast').value = priorWT.toFixed(2);

    // ── Loans / advances → copy raw-sheet values to register fields ───────────
    const sssLoan  = parseFloat(document.getElementById('sssSalaryLoan')?.value  || 0);
    const calLoan  = parseFloat(document.getElementById('sssCalamityLoan')?.value || 0);
    const hdmfLoan = parseFloat(document.getElementById('hdmfLoan')?.value        || 0);
    const advSalary =
    parseFloat(document.getElementById('advSalary')?.value || 0);

    const advCash =
        parseFloat(document.getElementById('advCash')?.value || 0);

    const advEmergency =
        parseFloat(document.getElementById('advEmergency')?.value || 0);

    const hdmfMplLoan =
        parseFloat(document.getElementById('hdmfMplLoan')?.value || 0);

    const hdmfCalamityLoan =
        parseFloat(document.getElementById('hdmfCalamityLoan')?.value || 0);

    document.getElementById('regAdvSalary').value =
        advSalary.toFixed(2);

    document.getElementById('regAdvCash').value =
        advCash.toFixed(2);

    document.getElementById('regAdvEmergency').value =
        advEmergency.toFixed(2);

    document.getElementById('regHdmfMplLoan').value =
        hdmfMplLoan.toFixed(2);

    document.getElementById('regHdmfCalamityLoan').value =
        hdmfCalamityLoan.toFixed(2);
    if (document.getElementById('regSssLoanSalary'))
        document.getElementById('regSssLoanSalary').value   = sssLoan.toFixed(2);
    if (document.getElementById('regSssLoanCalamity'))
        document.getElementById('regSssLoanCalamity').value = calLoan.toFixed(2);
    if (document.getElementById('regHdmfLoan'))
        document.getElementById('regHdmfLoan').value        = hdmfLoan.toFixed(2);
    if (document.getElementById('advWorkingLeave'))
        document.getElementById('advWorkingLeave').value    = (advance + calAdv).toFixed(2);

    // Non-taxable comp summary and annualized monthly gross
    if (document.getElementById('advNonTaxable'))
        document.getElementById('advNonTaxable').value   = (totalDeMin + otherNonTax).toFixed(2);

    // ── Net Pay ───────────────────────────────────────────────────────────────
    const wtax   = parseFloat(document.getElementById('wtPayable').value || 0);
    const netPay =
        grossComp
        - sss
        - phic
        - hdmf
        - wtax
        - sssLoan
        - calLoan
        - hdmfLoan
        - hdmfMplLoan
        - hdmfCalamityLoan
        - advSalary
        - advCash
        - advEmergency;
    document.getElementById('netPayRegister').value = netPay.toFixed(2);

    // Apply period-specific field visibility and label updates
    applyRegisterPeriodVisibility(periodType);

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
    
    let salAdjNonTax = safeParseFloat(document.getElementById('salaryAdjustmentNonTax')?.value);
    let salNonTaxAdj = safeParseFloat(document.getElementById('salNonTaxAdj')?.value);
    let nonTaxable = safeParseFloat(document.getElementById('yearEndGifts')?.value) +
                     safeParseFloat(document.getElementById('riceSubsidy')?.value) +
                     safeParseFloat(document.getElementById('uniformAllowance')?.value) +
                     safeParseFloat(document.getElementById('laundryAllowance')?.value) +
                     safeParseFloat(document.getElementById('mealAllowance')?.value) +
                     safeParseFloat(document.getElementById('medicalAllowance')?.value) +
                     safeParseFloat(document.getElementById('medicalAllowanceAdd')?.value) +
                     salAdjNonTax + salNonTaxAdj;

    let totalDeductionsFromPay = absentAmt + undertimeAmt;
    if (totalDeductionsFromPay > basicPay) totalDeductionsFromPay = basicPay;
    let netBasicPay = basicPay - totalDeductionsFromPay + nightPremium;
    if (netBasicPay < 0) netBasicPay = 0;
    
    const _rotNDHrs_c   = safeParseFloat(document.getElementById('regOtNightHours')?.value);
    const _satNDHrs_c   = safeParseFloat(document.getElementById('satNightDiffHours')?.value);
    const _sunNDHrs_c   = safeParseFloat(document.getElementById('sunNightDiffHours')?.value);
    const _holNDHrs_c   = safeParseFloat(document.getElementById('holNightDiffHours')?.value);
    const _storedOTNSD_c = regOtNightPrem + satNightPrem + sunNightPrem + holNightPrem;
    const _corrOTNSD_c  = _exactOTNSDCorr(_rotNDHrs_c, _satNDHrs_c, _sunNDHrs_c, _holNDHrs_c, _storedOTNSD_c);
    let totalOvertime = regOt + regOtGt10 + _corrOTNSD_c
                      + satOt + satOtGt10
                      + sunOt + sunOtGt10
                      + holOt + holOtGt10;
    let grossPay = netBasicPay + totalOvertime + taxableOther + nonTaxable + vlCashConversion;
    grossPay = Math.round(grossPay * 100) / 100;  // centavo-round to absorb float64 accumulation

    let sssDed         = safeParseFloat(document.getElementById('sssEE')?.value);
    // For 2nd half: recompute SSS using total monthly compensation per BIR/SSS circular.
    // Guard on grossPay > 0 prevents running before raw-sheet fields are populated.
    const _gcPeriodType = document.getElementById('payslipPeriodType')?.value;
    if (_gcPeriodType === 'second_half' && grossPay > 0) {
        const _gcPriorGross  = safeParseFloat(document.getElementById('edit_priorGrossTotal')?.value);
        if (_gcPriorGross > 0) {
            // grossForSSS: use base earnings (basic+OT) + |salary adj| + 1st-half de minimis.
            // Salary deductions (negative adj) don't reduce the MSC; the 2nd-half payslip may
            // display the full monthly de minimis so we normalise with the 1st-half semi-monthly amount.
            const _gcSalAdj    = safeParseFloat(document.getElementById('salaryAdjustment')?.value);
            const _gcPriorDeMin= safeParseFloat(document.getElementById('edit_priorDeMin')?.value);
            const _gcHolidayPay= safeParseFloat(document.getElementById('holidayPay')?.value);
            const _gcGrossForSSS = _gcPriorDeMin > 0
                ? (netBasicPay + totalOvertime + _gcHolidayPay + Math.abs(_gcSalAdj) + _gcPriorDeMin)
                : grossPay;
            const _gcSSSComp = computeSSS(_gcPriorGross + _gcGrossForSSS);
            sssDed = _gcSSSComp.total;
            const _gcSSSEl = document.getElementById('sssEE');
            if (_gcSSSEl) _gcSSSEl.value = _gcSSSComp.total.toFixed(2);
            const _gcWispEl = document.getElementById('sssWisp');
            if (_gcWispEl) _gcWispEl.value = _gcSSSComp.wisp.toFixed(2);
        }
    }
    let phicDed        = safeParseFloat(document.getElementById('phicEE')?.value);
    let hdmfDed        = safeParseFloat(document.getElementById('hdmfEE')?.value);
    // Recompute WT from the raw-sheet taxable base rather than reading wtPayable.
    // In the bulk flow fetchAndSetPriorHalfWT() calls recalcTotals() which overwrites
    // wtPayable using the payslip-tab edit_basicPay field (not populated in bulk),
    // corrupting the value. Computing fresh here uses the correct period type and
    // prior WT already set by fetchAndSetPriorHalfWT before this function is called.
    const _wtBase = Math.max(0, netBasicPay + totalOvertime + taxableOther);
    // withholdingTax is signed: positive = deduction, negative = refund (2nd-half over-withheld).
    let withholdingTax = updateWithholdingTax(_wtBase);
    let sssLoan          = safeParseFloat(document.getElementById('sssSalaryLoan')?.value);
    let calamityLoan     = safeParseFloat(document.getElementById('sssCalamityLoan')?.value);
    let hdmfLoan         = safeParseFloat(document.getElementById('hdmfLoan')?.value);
    let hdmfMplLoan      = safeParseFloat(document.getElementById('hdmfMplLoan')?.value);
    let hdmfCalamityLoan = safeParseFloat(document.getElementById('hdmfCalamityLoan')?.value);
    let salaryAdvance    = safeParseFloat(document.getElementById('advSalary')?.value);
    let cashAdvance      = safeParseFloat(document.getElementById('advCash')?.value);
    let emergencyAdvance = safeParseFloat(document.getElementById('advEmergency')?.value);
    let calAdv           = safeParseFloat(document.getElementById('advCalamity')?.value);
    // Negative WTH = refund: subtract negative (i.e. add back the refund) from deductions.
    let totalDeductions = sssDed + phicDed + hdmfDed + Math.max(0, withholdingTax)
        + sssLoan + calamityLoan + hdmfLoan + hdmfMplLoan + hdmfCalamityLoan
        + salaryAdvance + cashAdvance + emergencyAdvance + calAdv;
    let netPay = grossPay - totalDeductions + Math.max(0, -withholdingTax);

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
        totalOvertime,
        vlCashConversion, salAdjNonTax, salNonTaxAdj, taxableOther, nonTaxable,
        grossPay, sssDed, phicDed, hdmfDed, withholdingTax,
        sssLoan, calamityLoan, hdmfLoan, hdmfMplLoan, hdmfCalamityLoan,
        salaryAdvance, cashAdvance, emergencyAdvance, calAdv, totalDeductions, netPay,
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
        case 'regOt':   return hr * (parseFloat(s.ot_regular_rate)  || 1.25);
        case 'restOt':  return hr * (parseFloat(s.ot_rest_rate)     || 1.69);
        case 'holBase': return hr * ((parseFloat(s.reg_holiday_rate) || 2.00) - 1.0);
        case 'holOt':   return hr * (parseFloat(s.ot_holiday_rate)  || 2.60);
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
    const regOtR  = parseFloat(s.ot_regular_rate)  || 1.25;
    const restOtR = parseFloat(s.ot_rest_rate)     || 1.69;
    const holBaseR = (parseFloat(s.reg_holiday_rate) || 2.00) - 1.0;
    const holOtR  = parseFloat(s.ot_holiday_rate)  || 2.60;

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
        { label: 'Hol Pay &le;8 hrs',     id: 'edit_holOt',           hoursId: 'edit_holOtHrs',           hrs: safeHrs(d.holOt,          hr * holBaseR), rateKey: 'holBase', val: d.holOt },
        { label: 'Hol OT &gt;8 hrs',     id: 'edit_holOtGt10',       hoursId: 'edit_holOtGt10Hrs',       hrs: safeHrs(d.holOtGt10,      hr * holOtR),  rateKey: 'holOt',   val: d.holOtGt10 },
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
        { desc: 'De Minimis Benefit',              id: 'edit_nonTaxable',       val: d.nonTaxable - (d.salNonTaxAdj||0) },
        { desc: 'Salary Adj. (Non-Taxable)',        id: 'edit_salNonTaxAdj',     val: d.salNonTaxAdj||0 },
        { desc: 'VL Cash Conversion',              id: 'edit_vlCashConversion', val: d.vlCashConversion },
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
        ['Salary Loan', 'regSssLoanSalary'],
        ['Calamity Loan', 'regSssLoanCalamity'],
        ['HDMF Loan', 'regHdmfLoan'],
        ['Multi-Purpose Loan', 'regHdmfMplLoan'],
        ['HDMF Calamity Loan', 'regHdmfCalamityLoan'],
        ['Salary Advance', 'regAdvSalary'],
        ['Cash Advance', 'regAdvCash'],
        ['Emergency Advance', 'regAdvEmergency']
    ];

    let loanHtml = '';

    loanRows.forEach(([label, id]) => {
        const amount =
            safeParseFloat(
                document.getElementById(id)?.value
            );

        if (amount <= 0) return;

        loanHtml += `
            <tr>
                <td>${label}</td>
                <td class="text-right">
                    <input
                        type="number"
                        id="edit_${id}"
                        value="${amount.toFixed(2)}"
                        step="any"
                        style="width:100px;"
                    >
                </td>
            </tr>
        `;
    });

    document.getElementById('loansBody').innerHTML = loanHtml;

    document
    .querySelectorAll('#loansBody input')
    .forEach(el => {
        el.addEventListener('input', () => {
            _payslipEdited = true;
            recalcTotals();
        });
    });

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
     'edit_sss','edit_phic','edit_hdmf','edit_wtax','edit_sssLoan','edit_calamityLoan','edit_hdmfLoan','edit_advSalary','edit_calAdv',
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
    if      (t <= 10417)  return 0;
    else if (t <= 16667)  return        0 + (t -  10417) * 0.15;
    else if (t <= 33333)  return   937.50 + (t -  16667) * 0.20;
    else if (t <= 83333)  return  4270.70 + (t -  33333) * 0.25;
    else if (t <= 333333) return 16770.70 + (t -  83333) * 0.30;
    else                  return 91770.00 + (t - 333333) * 0.35;
}

// Called whenever the period type or period start changes.
// Manages contributions (zero for 1st half, restore for 2nd/monthly),
// shows/hides the prior-period fields, and fetches 1st-half data for 2nd-half payslips.
function fetchAndSetPriorHalfWT() { return new Promise(resolve => {
    const periodType  = document.getElementById('payslipPeriodType')?.value || 'first_half';
    const periodStart = document.getElementById('payslipPeriodStart')?.value;
    const empId       = document.getElementById('selectedEmployeeId')?.value;
    const pgEl        = document.getElementById('edit_priorGross');
    const pwEl        = document.getElementById('edit_priorWT');
    const pgTotalEl   = document.getElementById('edit_priorGrossTotal');
    const pdmEl       = document.getElementById('edit_priorDeMin');
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
    const clearPriorFields = () => {
        if (pgEl)      pgEl.value      = '0.00';
        if (pwEl)      pwEl.value      = '0.00';
        if (pgTotalEl) pgTotalEl.value = '0';
        if (pdmEl)     pdmEl.value     = '0';
    };

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
        resolve();
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
        resolve();
        return;
    }

    // 2nd half: fetch prior 1st-half data (sessionStorage first, then DB)
    if (!periodStart || !empId) { clearPriorFields(); hideStatus(); recalcTotals(); resolve(); return; }

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
            if (pgEl)     pgEl.value     = parseFloat(c.net_taxable).toFixed(2);
            if (pwEl)     pwEl.value     = parseFloat(c.wt).toFixed(2);
            if (pgTotalEl) pgTotalEl.value = parseFloat(c.prior_gross      || 0).toFixed(2);
            if (pdmEl)    pdmEl.value    = parseFloat(c.prior_de_minimis  || 0).toFixed(2);
            showStatus(`✓ 1st-half data loaded (${priorStart} to ${priorEnd}): Net Taxable = ${parseFloat(c.net_taxable).toFixed(2)}, WT = ${parseFloat(c.wt).toFixed(2)}`, '#155724', '#d4edda');
            recalcTotals();
            resolve();
            return;
        } catch(e) { sessionStorage.removeItem(key); }
    }

    // Fall back to DB lookup — pass explicit prior period dates so the server
    // does not need to guess them from a hardcoded same-month cutoff rule.
    fetch(`<?= site_url('client_portal/payroll/getPriorHalfWT') ?>/${empId}/${priorStart}/${priorEnd}`)
        .then(r => r.json())
        .then(data => {
            if (data.found) {
                if (pgEl)     pgEl.value     = parseFloat(data.net_taxable).toFixed(2);
                if (pwEl)     pwEl.value     = parseFloat(data.wt).toFixed(2);
                if (pgTotalEl) pgTotalEl.value = parseFloat(data.prior_gross      || 0).toFixed(2);
                if (pdmEl)    pdmEl.value    = parseFloat(data.prior_de_minimis  || 0).toFixed(2);
                sessionStorage.setItem(key, JSON.stringify({
                    net_taxable:      data.net_taxable,
                    wt:               data.wt,
                    prior_gross:      data.prior_gross      || 0,
                    prior_de_minimis: data.prior_de_minimis || 0,
                }));
                showStatus(`✓ 1st-half data loaded from saved record (${priorStart} to ${priorEnd}): Net Taxable = ${parseFloat(data.net_taxable).toFixed(2)}, WT = ${parseFloat(data.wt).toFixed(2)}`, '#155724', '#d4edda');
            } else {
                if (pgTotalEl) pgTotalEl.value = '0';
                if (pdmEl)     pdmEl.value     = '0';
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
            resolve();
        })
        .catch(() => { clearPriorFields(); hideStatus(); recalcTotals(); resolve(); });
}); }

// Corrects the OT NSD total by computing from hours × (monthly÷174) × ndRate and rounding
// the aggregate once — avoids the 0.01 sum-of-rounded-parts vs round-of-sum discrepancy.
// Falls back to storedSum when the correction exceeds 0.01 (guard against mult mismatches).
function _exactOTNSDCorr(rotHrs, satHrs, sunHrs, holHrs, storedSum) {
    const monthly = safeParseFloat(document.getElementById('monthlySalary')?.value);
    const ndRate  = parseFloat(window._payrollSettings?.night_diff_rate) || 0.10;
    if (monthly <= 0 || (rotHrs + satHrs + sunHrs + holHrs) <= 0) return storedSum;
    const s = window._payrollSettings || {};
    const restMult = parseFloat(s.rest_day_rate)    || 1.30;
    const holMult  = parseFloat(s.reg_holiday_rate) || 2.00;
    const exactRate = monthly / 174;
    // Sat NSD: 1.30×10% = 0.13; Sun NSD: 0.30×10% = 0.03 (Raw Sheet: AV=J×0.3×0.1×hrs)
    const rawNSD = rotHrs * exactRate * ndRate
                 + satHrs * exactRate * restMult * ndRate
                 + sunHrs * exactRate * (restMult - 1.0) * ndRate
                 + holHrs * exactRate * holMult  * ndRate;
    const corrNSD = Math.round(rawNSD * 100) / 100;
    return Math.abs(corrNSD - storedSum) <= 0.01 ? corrNSD : storedSum;
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
    const _rotNDHrs_r = safeParseFloat(document.getElementById('edit_regOtNightPremHrs')?.value);
    const _satNDHrs_r = safeParseFloat(document.getElementById('edit_satNightPremHrs')?.value);
    const _sunNDHrs_r = safeParseFloat(document.getElementById('edit_sunNightPremHrs')?.value);
    const _holNDHrs_r = safeParseFloat(document.getElementById('edit_holNightPremHrs')?.value);
    const _storedOTNSD_r = regOtNightPrem + satNightPrem + sunNightPrem + holNightPrem;
    const _corrOTNSD_r = _exactOTNSDCorr(_rotNDHrs_r, _satNDHrs_r, _sunNDHrs_r, _holNDHrs_r, _storedOTNSD_r);
    let totalOt = regOt + regOtGt10 + _corrOTNSD_r
                + satOt + satOtGt10
                + sunOt + sunOtGt10
                + holOt + holOtGt10;

    let taxableSum    = safeParseFloat(document.getElementById('edit_taxAdj')?.value);
    let nonTaxableSum = safeParseFloat(document.getElementById('edit_nonTaxable')?.value);
    // Gross = net basic pay + taxable OT/ND + other taxable + non-taxable + VL cash (non-taxable)
    let gross = netBasicPay + totalOt + taxableSum + nonTaxableSum + vlCashConv;
    gross = Math.round(gross * 100) / 100;  // centavo-round to absorb float64 accumulation

    // WT base = taxable earnings only (VL Cash is non-taxable, excluded)
    const wtBase = netBasicPay + totalOt + taxableSum;

    // For 2nd half: recompute SSS using normalised monthly compensation per BIR/SSS rules.
    // Guard on gross > 0: when called from syncToRegister before edit fields are filled,
    // gross = 0 and the update is handled there instead.
    const _recalcPeriodType = document.getElementById('payslipPeriodType')?.value;
    if (_recalcPeriodType === 'second_half' && gross > 0) {
        const priorGrossTotal = safeParseFloat(document.getElementById('edit_priorGrossTotal')?.value);
        if (priorGrossTotal > 0) {
            // Normalise: base earnings + |salary adj| + 1st-half de minimis so salary deductions
            // don't reduce MSC and the 2nd-half "full monthly de minimis display" is normalised.
            const _rrSalAdj    = safeParseFloat(document.getElementById('salaryAdjustment')?.value);
            const _rrPriorDeMin= safeParseFloat(document.getElementById('edit_priorDeMin')?.value);
            const _rrHolidayPay= safeParseFloat(document.getElementById('holidayPay')?.value);
            const _rrGrossForSSS = _rrPriorDeMin > 0
                ? (netBasicPay + totalOt + _rrHolidayPay + Math.abs(_rrSalAdj) + _rrPriorDeMin)
                : gross;
            const totalMonthlyComp = priorGrossTotal + _rrGrossForSSS;
            const _sssComp = computeSSS(totalMonthlyComp);
            ['sssEE', 'sssTotal', 'edit_sss'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = _sssComp.total.toFixed(2);
            });
            const wispEl = document.getElementById('sssWisp');
            if (wispEl) wispEl.value = _sssComp.wisp.toFixed(2);
        }
    }

    const newWT  = updateWithholdingTax(wtBase);
    const wtEl   = document.getElementById('edit_wtax');
    if (wtEl) wtEl.value = newWT.toFixed(2);

    // Cache 1st-half results so the 2nd-half payslip can read them in the same session.
    // Key uses the salary month (periodMonth picker) so cross-month periods (e.g. May 26–Jun 10
    // = 1st half of June salary) are stored under the same month as the 2nd half lookup.
    const _periodType  = _recalcPeriodType;
    const _periodStart = document.getElementById('payslipPeriodStart')?.value;
    const _empId       = document.getElementById('selectedEmployeeId')?.value;
    if (_periodType === 'first_half' && _periodStart && _empId) {
        const _salaryYm = document.getElementById('periodMonth')?.value || _periodStart.substring(0, 7);
        const _key = `prior_half_${_empId}_${_salaryYm}`;
        // Include gross (total including non-taxable) so 2nd-half can use it for SSS MSC computation.
        sessionStorage.setItem(_key, JSON.stringify({ net_taxable: wtBase, wt: newWT, prior_gross: gross }));
    }

    // WTH is signed: negative = refund. Deductions column shows only the deduction (≥0); refund is added to net pay separately.
    const _wtaxRaw = safeParseFloat(document.getElementById('edit_wtax')?.value);
    let dedSum = Math.max(0, _wtaxRaw);
    // Loan/advance edit fields are named "edit_reg" + the Register field id
    // (e.g. edit_regHdmfLoan) by buildPayslipTable()'s loanRows loop — not
    // "edit_hdmfLoan" — and only exist at all when their amount is non-zero.
    ['edit_sss','edit_phic','edit_hdmf',
     'edit_regSssLoanSalary','edit_regSssLoanCalamity','edit_regHdmfLoan',
     'edit_regHdmfMplLoan','edit_regHdmfCalamityLoan',
     'edit_regAdvSalary','edit_regAdvCash','edit_regAdvEmergency','edit_calAdv'].forEach(id => {
        dedSum += safeParseFloat(document.getElementById(id)?.value);
    });
    const _wtaxRefund = Math.max(0, -_wtaxRaw);
    let netPay = gross - dedSum + _wtaxRefund;

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
    setNum('edit_holOtHrs',           sh(d.holOt,          hr * 2.00));
    setNum('edit_holOtGt10Hrs',       sh(d.holOtGt10,      hr * holR));
    setNum('edit_holNightPremHrs',    sh(d.holNightPrem,   hr * ndR));
    // Also initialise the daily-rate field on payslip tab for manual VL adjustment
    const _monthly = safeParseFloat(document.getElementById('monthlySalary')?.value);
    if (_monthly > 0) setNum('edit_dailyRate', Math.round(_monthly * 12 / 261 * 100) / 100);
    setNum('edit_sss', d.sssDed);
    setNum('edit_phic', d.phicDed);
    setNum('edit_hdmf', d.hdmfDed);
    setNum('edit_wtax', d.withholdingTax);
    setNum('edit_regSssLoanSalary', safeParseFloat(document.getElementById('sssSalaryLoan')?.value));
    setNum('edit_regSssLoanCalamity', safeParseFloat(document.getElementById('sssCalamityLoan')?.value));
    setNum('edit_regHdmfLoan', safeParseFloat(document.getElementById('hdmfLoan')?.value));
    setNum('edit_regHdmfMplLoan', safeParseFloat(document.getElementById('hdmfMplLoan')?.value));
    setNum('edit_regHdmfCalamityLoan', safeParseFloat(document.getElementById('hdmfCalamityLoan')?.value));
    setNum('edit_regAdvSalary', safeParseFloat(document.getElementById('advSalary')?.value));
    setNum('edit_regAdvCash', safeParseFloat(document.getElementById('advCash')?.value));
    setNum('edit_regAdvEmergency', safeParseFloat(document.getElementById('advEmergency')?.value));

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
    
    let salAdjNonTax = safeParseFloat(document.getElementById('salaryAdjustmentNonTax')?.value);
    let salNonTaxAdj = safeParseFloat(document.getElementById('salNonTaxAdj')?.value);
    let nonTaxable = safeParseFloat(document.getElementById('yearEndGifts')?.value) +
                     safeParseFloat(document.getElementById('riceSubsidy')?.value) +
                     safeParseFloat(document.getElementById('uniformAllowance')?.value) +
                     safeParseFloat(document.getElementById('laundryAllowance')?.value) +
                     safeParseFloat(document.getElementById('mealAllowance')?.value) +
                     safeParseFloat(document.getElementById('medicalAllowance')?.value) +
                     safeParseFloat(document.getElementById('medicalAllowanceAdd')?.value) +
                     salAdjNonTax + salNonTaxAdj;

    let totalDeductionsFromPay = absentAmt + undertimeAmt;
    if (totalDeductionsFromPay > basicPay) totalDeductionsFromPay = basicPay;
    let netBasicPay = basicPay - totalDeductionsFromPay + nightPremium;
    if (netBasicPay < 0) netBasicPay = 0;
    
    const _rotNDHrs_c   = safeParseFloat(document.getElementById('regOtNightHours')?.value);
    const _satNDHrs_c   = safeParseFloat(document.getElementById('satNightDiffHours')?.value);
    const _sunNDHrs_c   = safeParseFloat(document.getElementById('sunNightDiffHours')?.value);
    const _holNDHrs_c   = safeParseFloat(document.getElementById('holNightDiffHours')?.value);
    const _storedOTNSD_c = regOtNightPrem + satNightPrem + sunNightPrem + holNightPrem;
    const _corrOTNSD_c  = _exactOTNSDCorr(_rotNDHrs_c, _satNDHrs_c, _sunNDHrs_c, _holNDHrs_c, _storedOTNSD_c);
    let totalOvertime = regOt + regOtGt10 + _corrOTNSD_c
                      + satOt + satOtGt10
                      + sunOt + sunOtGt10
                      + holOt + holOtGt10;
    let grossPay = netBasicPay + totalOvertime + taxableOther + nonTaxable + vlCashConversion;
    grossPay = Math.round(grossPay * 100) / 100;  // centavo-round to absorb float64 accumulation

    let sssDed         = safeParseFloat(document.getElementById('sssEE')?.value);
    let phicDed        = safeParseFloat(document.getElementById('phicEE')?.value);
    let hdmfDed        = safeParseFloat(document.getElementById('hdmfEE')?.value);
    // withholdingTax is signed: negative = refund (2nd-half over-withheld)
    let withholdingTax = safeParseFloat(document.getElementById('wtPayable')?.value);
    let sssLoan          = safeParseFloat(document.getElementById('sssSalaryLoan')?.value);
    let calamityLoan     = safeParseFloat(document.getElementById('sssCalamityLoan')?.value);
    let hdmfLoan         = safeParseFloat(document.getElementById('hdmfLoan')?.value);
    let hdmfMplLoan      = safeParseFloat(document.getElementById('hdmfMplLoan')?.value);
    let hdmfCalamityLoan = safeParseFloat(document.getElementById('hdmfCalamityLoan')?.value);
    let salaryAdvance    = safeParseFloat(document.getElementById('advSalary')?.value);
    let cashAdvance      = safeParseFloat(document.getElementById('advCash')?.value);
    let emergencyAdvance = safeParseFloat(document.getElementById('advEmergency')?.value);
    let calAdv           = safeParseFloat(document.getElementById('advCalamity')?.value);
    let totalDeductions = sssDed + phicDed + hdmfDed + Math.max(0, withholdingTax)
        + sssLoan + calamityLoan + hdmfLoan + hdmfMplLoan + hdmfCalamityLoan
        + salaryAdvance + cashAdvance + emergencyAdvance + calAdv;
    let netPay = grossPay - totalDeductions + Math.max(0, -withholdingTax);

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
        totalOvertime,
        vlCashConversion, salAdjNonTax, salNonTaxAdj, taxableOther, nonTaxable,
        grossPay, sssDed, phicDed, hdmfDed, withholdingTax,
        sssLoan, calamityLoan, hdmfLoan, hdmfMplLoan, hdmfCalamityLoan,
        salaryAdvance, cashAdvance, emergencyAdvance, calAdv, totalDeductions, netPay,
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
        { label: 'Hol. Pay &le;8 hrs',     raw: d.holOt          || 0, rate: hr * ((parseFloat(s.reg_holiday_rate) || 2.00) - 1.0) },
        { label: 'Hol. OT &gt;8 hrs',      raw: d.holOtGt10      || 0, rate: hr * holOtR },
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
    const taxCell = (d.taxableOther || 0) !== 0
        ? `<strong>Salary adjustment:</strong><br>${(d.taxableOther).toFixed(2)}`
        : '&mdash;';
    const ntParts = [];
    const deMinBase = (d.nonTaxable || 0) - (d.salNonTaxAdj || 0);
    if (deMinBase > 0) ntParts.push(`<strong>De Minimis Benefit</strong> &nbsp; ${deMinBase.toFixed(2)}`);
    if ((d.salNonTaxAdj    || 0) > 0) ntParts.push(`<strong>Salary Adjustment (Non-Taxable)</strong> &nbsp; ${(d.salNonTaxAdj).toFixed(2)}`);
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
                <colgroup>
                    <col style="width:36%">
                    <col style="width:9%">
                    <col style="width:13%">
                    <col style="width:21%">
                    <col style="width:21%">
                </colgroup>
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
            ${(() => {
                const _wthRaw = d.withholdingTax || 0;
                const regDeds = [
                    ['SSS',             (d.sssDed  || 0)],
                    ['PhilHealth',      (d.phicDed || 0)],
                    ['HDMF / Pag-IBIG', (d.hdmfDed || 0)],
                    ['Withholding Tax', Math.max(0, _wthRaw)],
                    ...(_wthRaw < 0 ? [['Tax Refund (Credit)', Math.abs(_wthRaw)]] : []),
                ];
                const loans = [
                    ['SSS Salary Loan', d.sssLoan      || 0],
                    ['SSS Calamity Loan', d.calamityLoan || 0],
                    ['HDMF Loan',       d.hdmfLoan     || 0],
                    ['HDMF Multi-Purpose Loan', d.hdmfMplLoan || 0],
                    ['HDMF Calamity Loan', d.hdmfCalamityLoan || 0],
                    ['Salary Advance',  d.salaryAdvance|| 0],
                    ['Cash Advance',    d.cashAdvance  || 0],
                    ['Emergency Advance', d.emergencyAdvance || 0],
                    ['Cal. Advance',    d.calAdv       || 0],
                ].filter(([, v]) => v > 0);
                const maxRows = Math.max(regDeds.length, loans.length);
                const loanHdr = `<th colspan="2" style="${bp}background:#eee;text-align:center;">LOANS / ADVANCES</th>`;
                let rows = '';
                for (let i = 0; i < maxRows; i++) {
                    const l = regDeds[i];
                    const r = loans[i];
                    rows += `<tr>
                        ${l ? `<td style="${bp}">${l[0]}</td><td style="${bp}text-align:right;">${l[1].toFixed(2)}</td>` : `<td colspan="2" style="${bp}"></td>`}
                        ${r ? `<td style="${bp}">${r[0]}</td><td style="${bp}text-align:right;">${r[1].toFixed(2)}</td>` : `<td colspan="2" style="${bp}"></td>`}
                    </tr>`;
                }
                return `<table style="width:100%;border-collapse:collapse;font-size:12px;margin-top:8px;">
                    <tr><th colspan="2" style="${bp}background:#eee;text-align:center;">REGULAR DEDUCTIONS</th>${loanHdr}</tr>
                    ${rows}
                    <tr><td colspan="2" style="${bp}"></td><td colspan="2" style="${bp}text-align:right;"><strong>TOTAL DEDUCTIONS &nbsp; ${(d.totalDeductions || 0).toFixed(2)}</strong></td></tr>
                </table>`;
            })()}
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
    const salAdjNonTax      = safeParseFloat(document.getElementById('salaryAdjustmentNonTax')?.value);
    const salNonTaxAdj      = sp('edit_salNonTaxAdj') || safeParseFloat(document.getElementById('salNonTaxAdj')?.value);
    const sssDed            = sp('edit_sss');
    const phicDed           = sp('edit_phic');
    const hdmfDed           = sp('edit_hdmf');
    const withholdingTax    = sp('edit_wtax'); // signed: negative = refund
    // These ids must match what buildPayslipTable()'s loanRows loop actually
    // creates — "edit_reg" + the Register tab's field id (e.g. edit_regHdmfLoan,
    // not edit_hdmfLoan) — since that's the only place these inputs get built.
    // A field is skipped entirely (never created) when its amount is 0, so a
    // missing element here is expected and correctly reads as 0 via sp().
    const sssLoan           = sp('edit_regSssLoanSalary');
    const calamityLoan      = sp('edit_regSssLoanCalamity');
    const hdmfLoan           = sp('edit_regHdmfLoan');
    const hdmfMplLoan        = sp('edit_regHdmfMplLoan');
    const hdmfCalamityLoan   = sp('edit_regHdmfCalamityLoan');
    const salaryAdvance     = sp('edit_regAdvSalary');
    const cashAdvance        = sp('edit_regAdvCash');
    const emergencyAdvance   = sp('edit_regAdvEmergency');
    const calAdv            = sp('edit_calAdv');

    const totalDeductionsFromPay = Math.min(absentAmt + undertimeAmt, basicPay);
    const netBasicPay   = Math.max(0, basicPay - totalDeductionsFromPay + nightPremium);
    const _rotNDHrs_e   = sp('edit_regOtNightPremHrs');
    const _satNDHrs_e   = sp('edit_satNightPremHrs');
    const _sunNDHrs_e   = sp('edit_sunNightPremHrs');
    const _holNDHrs_e   = sp('edit_holNightPremHrs');
    const _storedOTNSD_e = regOtNightPrem + satNightPrem + sunNightPrem + holNightPrem;
    const _corrOTNSD_e  = _exactOTNSDCorr(_rotNDHrs_e, _satNDHrs_e, _sunNDHrs_e, _holNDHrs_e, _storedOTNSD_e);
    const totalOvertime = regOt + regOtGt10 + _corrOTNSD_e
                        + satOt + satOtGt10
                        + sunOt + sunOtGt10
                        + holOt + holOtGt10;
    const grossPay      = Math.round((netBasicPay + totalOvertime + taxableOther + nonTaxable + salNonTaxAdj + vlCashConversion) * 100) / 100;
    const totalDeductions = sssDed + phicDed + hdmfDed + Math.max(0, withholdingTax)
        + sssLoan + calamityLoan + hdmfLoan + hdmfMplLoan + hdmfCalamityLoan
        + salaryAdvance + cashAdvance + emergencyAdvance + calAdv;
    const netPay        = grossPay - totalDeductions + Math.max(0, -withholdingTax);

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
        totalOvertime,
        vlCashConversion, salAdjNonTax, salNonTaxAdj, taxableOther, nonTaxable,
        grossPay, sssDed, phicDed, hdmfDed, withholdingTax,
        sssLoan, calamityLoan, hdmfLoan, hdmfMplLoan, hdmfCalamityLoan,
        salaryAdvance, cashAdvance, emergencyAdvance, calAdv, totalDeductions, netPay,
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
        showToast('Please select an employee and fill in the Raw Sheet first.', 'warning');
        return;
    }
    
    const employeeIdToSubmit = document.getElementById('selectedEmployeeId').value;
    if (!employeeIdToSubmit) {
        showToast('Employee ID not found. Please select an employee.', 'warning');
        return;
    }
    const startDate = d.periodStart;
    const endDate = d.periodEnd;
    if (!startDate || !endDate) {
        showToast('Period start/end missing.', 'warning');
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
            total_overtime: d.totalOvertime,
            overtime_pay:  d.totalOvertime,
            salary_adjustment: safeParseFloat(document.getElementById('salaryAdjustment')?.value),
            override_amount:   safeParseFloat(document.getElementById('overrideAmount')?.value),
            ned_amount:        safeParseFloat(document.getElementById('nedAmount')?.value),
            holiday_pay:       safeParseFloat(document.getElementById('holidayPay')?.value),
            other_earnings_taxable: d.taxableOther,
            year_end_gifts:     safeParseFloat(document.getElementById('yearEndGifts')?.value),
            rice_subsidy:       safeParseFloat(document.getElementById('riceSubsidy')?.value),
            uniform_allowance:  safeParseFloat(document.getElementById('uniformAllowance')?.value),
            laundry_allowance:  safeParseFloat(document.getElementById('laundryAllowance')?.value),
            meal_allowance:     safeParseFloat(document.getElementById('mealAllowance')?.value),
            medical_allowance:  safeParseFloat(document.getElementById('medicalAllowance')?.value) + safeParseFloat(document.getElementById('medicalAllowanceAdd')?.value),
            salary_adjustment_non_tax: safeParseFloat(document.getElementById('salaryAdjustmentNonTax')?.value),
            sal_non_tax_adj:    safeParseFloat(document.getElementById('salNonTaxAdj')?.value),
            de_minimis:  d.nonTaxable,
            vl_cash_conversion: d.vlCashConversion || 0,
            other_earnings_non_taxable: (d.vlCashConversion || 0) + safeParseFloat(document.getElementById('salNonTaxAdj')?.value),
            gross_pay:       d.grossPay,
            sss_ee:          d.sssDed,
            phic_ee:         d.phicDed,
            hdmf_ee:         d.hdmfDed,
            withholding_tax: d.withholdingTax,
            sss_loan:        d.sssLoan,
            calamity_loan:   d.calamityLoan,
            hdmf_loan:       d.hdmfLoan,
            hdmf_mpl_loan:      d.hdmfMplLoan || 0,
            hdmf_calamity_loan: d.hdmfCalamityLoan || 0,
            sal_adv:         d.salaryAdvance,
            salary_advance:  d.salaryAdvance,
            cash_advance:      d.cashAdvance || 0,
            emergency_advance: d.emergencyAdvance || 0,
            cal_adv:         d.calAdv || 0,
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
        showToast('Please generate the payslip first (click "Generate Payslip").', 'warning');
        return;
    }
    const employeeId = document.getElementById('selectedEmployeeId').value;
    if (!employeeId) {
        showToast('Please select an employee first.', 'warning');
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
        total_overtime: window.currentPayslipData.totalOvertime || 0,
        overtime_pay: window.currentPayslipData.totalOvertime || 0,
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
        hdmf_loan: safeParseFloat(document.getElementById('hdmfLoan')?.value),
        hdmf_mpl_loan: safeParseFloat(document.getElementById('hdmfMplLoan')?.value),
        hdmf_calamity_loan: safeParseFloat(document.getElementById('hdmfCalamityLoan')?.value),
        salary_advance: safeParseFloat(document.getElementById('advSalary')?.value),
        cash_advance: safeParseFloat(document.getElementById('advCash')?.value),
        emergency_advance: safeParseFloat(document.getElementById('advEmergency')?.value),
        other_deductions: 0,
        net_pay: window.currentPayslipData.netPay,
    };
    try {
        const response = await fetch('<?= site_url('client_portal/payroll/sendPdf') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ...payload, send_method: 'individual' })
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            showToast('Error sending PDF: ' + (result.error || `Server error ${response.status}`), 'error');
            return;
        }
        showToast('PDF payslip sent successfully to employee email.', 'success');
        window.location.reload();
    } catch (error) {
        console.error('Send PDF error:', error);
        showToast('Network error — could not reach server. Check console for details.', 'error');
    }
}

// ================= EXPORT RAW SHEET (CSV) =================
function exportRawSheet() {
    // Bulk mode: export all generated payslips as multi-row CSV
    if (document.querySelector('.mode-btn.active')?.textContent === 'Bulk Send') {
        if (!bulkGeneratedPayloads.length) { showToast('Generate payslips first, then export.', 'warning'); return; }
        exportBulkCSV('raw');
        return;
    }
    const employeeId = document.getElementById('selectedEmployeeId').value;
    if (!employeeId) {
        showToast('Please select an employee first.', 'warning');
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
    if (document.querySelector('.mode-btn.active')?.textContent === 'Bulk Send') {
        if (!bulkGeneratedPayloads.length) { showToast('Generate payslips first, then export.', 'warning'); return; }
        exportBulkCSV('register');
        return;
    }
    const employeeId = document.getElementById('selectedEmployeeId').value;
    if (!employeeId) {
        showToast('Please select an employee first, then fill in the register fields before exporting.', 'warning');
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
        de_min_meal_allowance:     g('deMinMealAllowance'),
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
        hdmf_loan:                 g('regHdmfLoan'),
        advance_to_employees:      g('advWorkingLeave'),
        non_taxable_comp:          g('advNonTaxable'),
        net_pay:                   g('netPayRegister'),
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
            showToast('Please select an employee and period first.', 'warning');
            return;
        }
        window.location.href = `<?= site_url('client_portal/payroll/exportTimesheet') ?>?employee_id=${employeeId}&start_date=${start}&end_date=${end}`;
    });
    
    document.getElementById('importTimesheetBtn')?.addEventListener('click', function() {
        document.getElementById('importTimesheetModal').style.display = 'block';
    });
    
    // Timesheet import — two stages. Stage 1 asks the server to read just
    // the header row and guess a mapping; if the file's headers already
    // match ours exactly it imports immediately (unchanged one-click flow).
    // Otherwise stage 2 shows the guessed mapping for the uploader to
    // confirm or correct before anything actually gets imported.
    (function() {
        const form = document.getElementById('importTimesheetForm');
        const fileInput = document.getElementById('timesheetImportFile');
        const mappingSection = document.getElementById('importColumnMapping');
        const mappingFieldsEl = document.getElementById('importColumnMappingFields');
        const employeeSection = document.getElementById('importEmployeeResolution');
        const employeeFieldsEl = document.getElementById('importEmployeeResolutionFields');
        const submitBtn = document.getElementById('importTimesheetSubmitBtn');
        const backBtn = document.getElementById('importMappingBackBtn');

        let stage = 'select'; // 'select' | 'confirm'
        let previewFields = [];

        function resetToSelectStage() {
            stage = 'select';
            mappingSection.style.display = 'none';
            mappingFieldsEl.innerHTML = '';
            employeeSection.style.display = 'none';
            employeeFieldsEl.innerHTML = '';
            backBtn.style.display = 'none';
            submitBtn.textContent = 'Upload';
            submitBtn.disabled = false;
        }
        backBtn?.addEventListener('click', resetToSelectStage);
        fileInput?.addEventListener('change', resetToSelectStage);

        function buildMappingUI(headers, fields, guess) {
            mappingFieldsEl.innerHTML = fields.map(f => {
                const opts = ['<option value="">&mdash; not in this file &mdash;</option>']
                    .concat(headers.map(h => {
                        const sel = (guess[f.key] === h) ? 'selected' : '';
                        return `<option value="${escapeHtml(h)}" ${sel}>${escapeHtml(h)}</option>`;
                    }));
                return `
                    <div style="display:flex; align-items:center; gap:8px;">
                        <label style="width:150px; font-size:13px;">${escapeHtml(f.label)}${f.required ? ' *' : ''}</label>
                        <select data-field="${f.key}" ${f.required ? 'required' : ''} style="flex:1; padding:4px; border:1px solid #ccc; border-radius:4px;">
                            ${opts.join('')}
                        </select>
                    </div>
                `;
            }).join('');
        }

        // One row per distinct unrecognized employee_number value found in
        // the file (not per CSV row — the same typo'd number can appear on
        // many attendance rows), with a dropdown of every real employee and
        // the closest fuzzy match pre-selected when there is one.
        function buildEmployeeResolutionUI(unmatched, employees) {
            employeeFieldsEl.innerHTML = unmatched.map(u => {
                const rowLabel = u.count === 1 ? '1 row' : `${u.count} rows`;
                const options = ['<option value="">&mdash; skip these rows &mdash;</option>']
                    .concat(employees.map(e => {
                        const sel = (u.suggested_employee_id && e.id === u.suggested_employee_id) ? 'selected' : '';
                        return `<option value="${e.id}" ${sel}>${escapeHtml(e.label)}</option>`;
                    }));
                return `
                    <div style="display:flex; align-items:center; gap:8px;">
                        <label style="width:150px; font-size:13px;" title="As it appears in the file">"${escapeHtml(u.value)}" <span style="color:#888;">(${rowLabel})</span></label>
                        <select data-employee-value="${escapeHtml(u.value)}" style="flex:1; padding:4px; border:1px solid #ccc; border-radius:4px;">
                            ${options.join('')}
                        </select>
                    </div>
                `;
            }).join('');
        }

        async function doImport(columnMap, employeeMap) {
            const formData = new FormData(form);
            if (columnMap) formData.set('column_map', JSON.stringify(columnMap));
            if (employeeMap) formData.set('employee_map', JSON.stringify(employeeMap));

            submitBtn.disabled = true;
            let result;
            try {
                const response = await fetch('<?= site_url('client_portal/payroll/importTimesheet') ?>', {
                    method: 'POST',
                    body: formData
                });
                result = await response.json();
            } catch (err) {
                submitBtn.disabled = false;
                showToast('Import failed: could not reach the server.', 'error');
                return;
            }
            submitBtn.disabled = false;

            if (result.success) {
                const skippedCount = result.skipped || 0;
                if (skippedCount > 0) {
                    console.warn('Timesheet import — skipped rows:', result.errors);
                    showToast(`${result.imported} row(s) imported, ${skippedCount} skipped — see browser console for details.`, 'warning');
                } else {
                    showToast(result.message || `${result.imported} row(s) imported successfully.`, 'success');
                }
                closeImportModal();
                form.reset();
                resetToSelectStage();
            } else {
                showToast('Import failed: ' + (result.error || result.message || 'Unknown error'), 'error');
            }
        }

        form?.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (stage === 'select') {
                const formData = new FormData(form);
                submitBtn.disabled = true;
                let result;
                try {
                    const response = await fetch('<?= site_url('client_portal/payroll/previewTimesheetImport') ?>', {
                        method: 'POST',
                        body: formData
                    });
                    result = await response.json();
                } catch (err) {
                    submitBtn.disabled = false;
                    showToast('Could not read the file.', 'error');
                    return;
                }
                submitBtn.disabled = false;

                if (!result.success) {
                    showToast('Import failed: ' + (result.error || 'Unknown error'), 'error');
                    return;
                }

                if (result.exact_match) {
                    await doImport(null, null);
                    return;
                }

                previewFields = result.fields;

                const needsColumnMapping = result.fields.some(f => result.guess[f.key] !== f.key);
                if (needsColumnMapping) {
                    buildMappingUI(result.headers, result.fields, result.guess);
                    mappingSection.style.display = 'block';
                }

                const unmatched = result.unmatched_employees || [];
                if (unmatched.length > 0) {
                    buildEmployeeResolutionUI(unmatched, result.employees || []);
                    employeeSection.style.display = 'block';
                }

                backBtn.style.display = 'inline-block';
                submitBtn.textContent = 'Confirm Import';
                stage = 'confirm';

                const messages = [];
                if (needsColumnMapping) messages.push("columns didn't match automatically");
                if (unmatched.length > 0) messages.push(`${unmatched.length} employee number(s) not recognized`);
                showToast(`Please confirm before importing — ${messages.join(', ')}.`, 'warning');
                return;
            }

            // stage === 'confirm': build whatever mappings were shown and actually import.
            const columnMap = {};
            let missingRequired = false;
            mappingFieldsEl.querySelectorAll('select[data-field]').forEach(sel => {
                columnMap[sel.dataset.field] = sel.value;
                const field = previewFields.find(f => f.key === sel.dataset.field);
                if (field && field.required && !sel.value) missingRequired = true;
            });
            if (missingRequired) {
                showToast('Please map every required column before importing.', 'error');
                return;
            }

            const employeeMap = {};
            employeeFieldsEl.querySelectorAll('select[data-employee-value]').forEach(sel => {
                if (sel.value) employeeMap[sel.dataset.employeeValue] = sel.value;
            });

            await doImport(
                Object.keys(columnMap).length ? columnMap : null,
                Object.keys(employeeMap).length ? employeeMap : null
            );
        });
    })();
    
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
        // BIR: 13th month + other benefits ≤ ₱90,000 is non-taxable; excess is taxable.
        const TAX_FREE_CAP = 90000;
        const nonTaxPart   = Math.min(amount, TAX_FREE_CAP);
        const taxablePart  = Math.max(0, amount - TAX_FREE_CAP);

        const giftsEl = document.getElementById('yearEndGifts');
        if (giftsEl) giftsEl.value = nonTaxPart.toFixed(2);
        const regEl = document.getElementById('deMinYearEnd');
        if (regEl) regEl.value = nonTaxPart.toFixed(2);

        if (taxablePart > 0) {
            const adjEl = document.getElementById('salaryAdjustment');
            if (adjEl) adjEl.value = ((parseFloat(adjEl.value) || 0) + taxablePart).toFixed(2);
        }

        bannerMsg = `13th month pay of <strong>₱${amount.toLocaleString('en-PH',{minimumFractionDigits:2})}</strong> `
                  + `pre-filled for <strong>${empName}</strong> (${year}): `
                  + `<em>Year End Gifts</em> ₱${nonTaxPart.toLocaleString('en-PH',{minimumFractionDigits:2})} (non-taxable)`
                  + (taxablePart > 0 ? ` + <em>Salary Adjustment</em> ₱${taxablePart.toLocaleString('en-PH',{minimumFractionDigits:2})} (taxable excess)` : '');
    } else {
        const isRefund = payload.isRefund;
        if (isRefund) {
            // Year-end tax refund: non-taxable return of over-withheld WT.
            // Goes into Sal Non-Tax Adj so it increases net pay without inflating gross taxable.
            const ntaEl = document.getElementById('salNonTaxAdj');
            if (ntaEl) ntaEl.value = Math.abs(amount).toFixed(2);
            bannerMsg = `Year-end tax refund of <strong>₱${Math.abs(amount).toLocaleString('en-PH',{minimumFractionDigits:2})}</strong> `
                      + `pre-filled into <em>Sal Non-Tax Adj</em> for <strong>${empName}</strong> (${year}).`;
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

    // Sync register tab so all derived fields (gross comp, WT, net pay) reflect the pre-filled values.
    if (typeof syncToRegister === 'function') await syncToRegister();

    // Scroll to top of raw sheet
    const rawScroll = document.querySelector('#raw.payroll-scroll-area');
    if (rawScroll) rawScroll.scrollTop = 0;
};
</script>
</body>
</html>