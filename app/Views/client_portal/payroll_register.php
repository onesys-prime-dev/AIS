<div class="bg-white rounded-2xl shadow-lg p-8 h-full border border-gray-300 flex flex-col min-h-0">

    <!-- ── Sub-tabs: Raw Sheet | Register | Payslip | History | Bank File — same pill-toggle
         style as the Individual/Bulk Send switch on the Generate Payslip tab ── -->
    <div class="reg-mode-switch">
        <button type="button" class="reg-subtab reg-subtab-active" data-regview="raw" onclick="switchRegView('raw', this)">Raw Sheet</button>
        <button type="button" class="reg-subtab" data-regview="table" onclick="switchRegView('table', this)">Register</button>
        <button type="button" class="reg-subtab" data-regview="payslip" onclick="switchRegView('payslip', this)">Payslip</button>
        <button type="button" class="reg-subtab" data-regview="history" onclick="switchRegView('history', this)">History</button>
        <button type="button" class="reg-subtab" data-regview="bank" onclick="switchRegView('bank', this)">Bank File</button>
    </div>
    <style>
        .reg-mode-switch{ background:#e5e5e5; width:520px; border-radius:20px; padding:3px; display:flex; margin-bottom:20px; }
        .reg-subtab{ flex:1; border:none; padding:6px; border-radius:20px; font-size:12px; cursor:pointer; background:transparent; font-weight:500; color:#374151; white-space:nowrap; }
        .reg-subtab-active{ background:#fff; box-shadow:0 1px 3px rgba(0,0,0,0.1); color:#111827; }
    </style>
    <script>
        function switchRegView(view, btn) {
            document.getElementById('regControls').style.display    = (view === 'history' || view === 'bank') ? 'none' : 'flex';
            document.getElementById('regViewRaw').style.display     = view === 'raw'     ? 'flex' : 'none';
            document.getElementById('regViewTable').style.display   = view === 'table'   ? 'flex' : 'none';
            document.getElementById('regViewPayslip').style.display = view === 'payslip' ? 'flex' : 'none';
            document.getElementById('regViewHistory').style.display = view === 'history' ? 'flex' : 'none';
            document.getElementById('regViewBankFile').style.display= view === 'bank'    ? 'flex' : 'none';
            document.querySelectorAll('.reg-subtab').forEach(b => b.classList.remove('reg-subtab-active'));
            btn.classList.add('reg-subtab-active');
        }
    </script>

    <!-- ── Shared header: title + period controls (hidden on History) ─── -->
    <div id="regControls" class="flex items-center justify-between flex-wrap gap-3 mb-4">
        <div>
            <h3 class="font-semibold text-xl">Payroll Register</h3>
            <p class="text-gray-500 text-sm mt-0.5">Matches client Excel register format. SSS/PHIC/HDMF are 0 for 1st half per DOLE practice.</p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <input type="month" id="regMonthPicker" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#7b5cff]">

            <select id="regPeriodPicker" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#7b5cff]">
                <option value="1st_half">1st Half</option>
                <option value="2nd_half">2nd Half</option>
                <option value="">Full Month</option>
            </select>

            <button onclick="loadRegister()" class="bg-[#7b5cff] hover:bg-[#6a4de0] text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow">
                Load
            </button>

            <button onclick="exportRegisterCsv()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                Export CSV
            </button>
        </div>
    </div>

    <!-- ── Raw Sheet view: inline-editable, mirrors Bulk Send's Raw Sheet ── -->
    <div id="regViewRaw" class="flex-1 min-h-0 flex flex-col gap-2">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <p class="text-xs text-gray-400">Editable — yellow cells only. Change a value and it's marked <span style="color:#b45309;font-weight:600;">unsaved</span> until you save it.</p>
            <button type="button" onclick="saveAllRawRows()" id="rawSaveAllBtn" style="display:none;"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                Save All Changes
            </button>
        </div>
        <div class="reg-table-scroll flex-1 min-h-0 overflow-auto bg-white border border-gray-300 rounded-lg shadow">
            <div id="rawLoading" class="py-16 text-center text-gray-400 text-sm">Select a month and period, then click Load.</div>
            <table id="regRawTable" class="reg-table hidden">
                <thead>
                    <tr>
                        <th rowspan="2" class="reg-th-action">Employee</th>
                        <th rowspan="2">Monthly<br>Salary</th>
                        <th colspan="6" class="group-hdr">DE MINIMIS BENEFIT (editable)</th>
                        <th rowspan="2">Other<br>Non-Tax</th>
                        <th colspan="3" class="group-hdr">SALARY ADJ.</th>
                        <th rowspan="2">Overtime</th>
                        <th rowspan="2">NSD</th>
                        <th rowspan="2">Holiday<br>Pay</th>
                        <th rowspan="2">Gross<br>Pay</th>
                        <th colspan="3" class="group-hdr">EMPLOYEE'S SHARE</th>
                        <th rowspan="2">Withholding<br>Tax</th>
                        <th colspan="3" class="group-hdr">LOANS</th>
                        <th rowspan="2">Advance</th>
                        <th rowspan="2">Net Pay</th>
                        <th rowspan="2" class="reg-th-action">Save</th>
                    </tr>
                    <tr>
                        <th class="sub-hdr">Year End</th>
                        <th class="sub-hdr">Rice</th>
                        <th class="sub-hdr">Uniform</th>
                        <th class="sub-hdr">Laundry</th>
                        <th class="sub-hdr">Meal</th>
                        <th class="sub-hdr">Medical</th>
                        <th class="sub-hdr">Adj.</th>
                        <th class="sub-hdr">Override</th>
                        <th class="sub-hdr">NED</th>
                        <th class="sub-hdr">SSS</th>
                        <th class="sub-hdr">PhilHealth</th>
                        <th class="sub-hdr">Pag-IBIG</th>
                        <th class="sub-hdr">SSS Loan</th>
                        <th class="sub-hdr">Calamity</th>
                        <th class="sub-hdr">HDMF Loan</th>
                    </tr>
                </thead>
                <tbody id="regRawBody"></tbody>
            </table>
        </div>
    </div>

    <!-- ── Payslip view: per-employee cards for the loaded period ─────── -->
    <div id="regViewPayslip" style="display:none;" class="flex-1 min-h-0 flex flex-col gap-2">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h3 class="font-semibold text-lg">Payslips — Loaded Period</h3>
            <div style="display:flex;gap:8px;">
                <button type="button" id="regSendAllBtn" onclick="sendAllRegisterPayslips(this)" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition" style="display:inline-flex;align-items:center;">
                    <span id="regSendBtnSpinner" class="btn-spinner" style="display:none;width:18px;height:18px;border-width:3px;"></span>
                    <span id="regSendBtnText">&#9993; Send All</span>
                </button>
                <button type="button" onclick="exportRegisterCsv()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">&#8681; Export CSV</button>
            </div>
        </div>
        <div id="regSendProgressWrap" style="display:none;">
            <div style="height:10px;background:#e5e7eb;border-radius:6px;overflow:hidden;">
                <div id="regSendProgressFill" style="height:100%;width:0%;background:#16a34a;border-radius:6px;transition:width .2s ease;"></div>
            </div>
        </div>
        <div id="payslipViewLoading" class="py-16 text-center text-gray-400 text-sm">Select a month and period, then click Load.</div>
        <div id="regPayslipList" class="flex-1 min-h-0 overflow-auto grid gap-2 content-start"></div>
    </div>

    <div id="regViewTable" style="display:none;" class="flex-1 min-h-0 flex flex-col gap-4">

    <!-- ── Table wrapper ─────────────────────────────────────────────── -->
    <div class="flex-1 min-h-0 overflow-auto bg-white border border-gray-300 rounded-lg shadow">

        <div id="regLoading" class="hidden py-16 text-center text-gray-400 text-sm">Loading…</div>
        <div id="regEmpty"   class="py-16 text-center text-gray-400 text-sm">Select a month and period, then click Load.</div>

        <table id="regTable" class="reg-table hidden">
            <thead>
                <tr>
                    <th rowspan="2" class="reg-th-action">No.</th>
                    <th rowspan="2" class="reg-th-action">Actions</th>
                    <th rowspan="2">Name of Employees</th>
                    <th rowspan="2">Monthly<br>Basic Salary</th>
                    <th rowspan="2">Semi-Monthly<br>Basic Salary</th>
                    <th colspan="6" class="group-hdr">DE MINIMIS BENEFIT</th>
                    <th rowspan="2">Other Non-Taxable<br>Compensation</th>
                    <th rowspan="2">Salary<br>Adjustment</th>
                    <th rowspan="2">Overtime</th>
                    <th rowspan="2">NSD</th>
                    <th rowspan="2">Absences /<br>Tardiness</th>
                    <th rowspan="2">Holiday<br>Pay</th>
                    <th rowspan="2">Gross<br>Compensation</th>
                    <th rowspan="2">Gross Taxable<br>Compensation</th>
                    <th rowspan="2">Non-Taxable<br>Compensation</th>
                    <th colspan="3" class="group-hdr">EMPLOYEE'S SHARE</th>
                    <th rowspan="2">Regular Net<br>Taxable Comp.</th>
                    <th rowspan="2">Withholding<br>Tax Due</th>
                    <th rowspan="2">Tax<br>Refund/Adj</th>
                    <th colspan="3" class="group-hdr">LOANS</th>
                    <th rowspan="2">Advances to<br>Employees</th>
                    <th rowspan="2">NET PAY</th>
                </tr>
                <tr>
                    <th class="sub-hdr">Year End<br>Gifts</th>
                    <th class="sub-hdr">Rice<br>Subsidy</th>
                    <th class="sub-hdr">Uniform /<br>Clothing</th>
                    <th class="sub-hdr">Laundry</th>
                    <th class="sub-hdr">Meal</th>
                    <th class="sub-hdr">Medical</th>
                    <th class="sub-hdr">SSS</th>
                    <th class="sub-hdr">PhilHealth</th>
                    <th class="sub-hdr">Pag-IBIG</th>
                    <th class="sub-hdr">SSS Loan</th>
                    <th class="sub-hdr">Calamity</th>
                    <th class="sub-hdr">HDMF Loan</th>
                </tr>
            </thead>
            <tbody id="regBody"></tbody>
            <tfoot id="regFoot"></tfoot>
        </table>
    </div>
    </div><!-- /#regViewTable -->

    <div id="regViewHistory" style="display:none;" class="flex-1 min-h-0 flex flex-col">
        <?= view('client_portal/payroll_payslip_history') ?>
    </div>

    <div id="regViewBankFile" style="display:none;" class="flex-1 min-h-0 flex flex-col">
        <?= view('client_portal/payroll_bank_file') ?>
    </div>

</div><!-- /outer -->

<!-- ── Payslip Edit Modal (must be before <script> so listeners can attach) ── -->
<div id="regEditOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9000;align-items:center;justify-content:center;">
    <div class="me-panel">

        <!-- Header -->
        <div class="me-header">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            <div class="me-header-text">
                <span class="me-header-title">Edit Payslip</span>
                <span id="regEditSubtitle" class="me-header-sub"></span>
            </div>
            <button id="regEditCloseBtn" class="me-close">&times;</button>
        </div>

        <!-- Body (populated by JS) -->
        <div id="regEditBody" class="me-body"></div>

        <!-- Footer -->
        <div class="me-footer">
            <span id="me-last-edit" class="me-last-edit"></span>
            <div style="display:flex;gap:8px;">
                <button id="regEditCancelBtn" class="me-btn-cancel">Cancel</button>
                <button id="regEditSaveBtn" data-id="" class="me-btn-save">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<style>
/* ── Register table ──────────────────────────────── */
.reg-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    min-width: 2200px;
}
.reg-table th {
    background: #4c1d95;
    color: #fff;
    padding: 7px 8px;
    border: 1px solid #3b1876;
    text-align: center;
    white-space: nowrap;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 1;
}
.reg-table th.group-hdr  { background: #7b5cff; border-color: #6a4de0; }
.reg-table th.sub-hdr    { background: #9b81ff; border-color: #8b6cf5; font-weight: 500; font-size: 11px; }
.reg-table th.reg-th-action { width: 36px; }
.reg-table td {
    padding: 6px 8px;
    border: 1px solid #e5e7eb;
    text-align: right;
    white-space: nowrap;
}
.reg-table td.left   { text-align: left; }
.reg-table td.center { text-align: center; }
.reg-table tbody tr:nth-child(even) { background: #f9fafb; }
.reg-table tbody tr:hover { background: #f5f3ff; }
.reg-table tfoot tr { background: #4c1d95; color: #fff; font-weight: 700; }
.reg-table tfoot td { border-color: #3b1876; padding: 7px 8px; }
.net-pay-cell { font-weight: 700; color: #166534; }
.zero-cell    { color: #9ca3af; }

/* ── Raw Sheet: pin the Employee column while scrolling right through
   20+ columns, so you never lose track of whose row you're editing ── */
.reg-table-scroll { scroll-behavior: smooth; }
.reg-table-scroll .reg-table thead tr:first-child th:nth-child(1),
.reg-table-scroll .reg-table tbody td:nth-child(1) {
    position: sticky;
    left: 0;
    z-index: 2;
    background: #fff;
    box-shadow: 2px 0 4px -2px rgba(0,0,0,0.15);
}
.reg-table-scroll .reg-table thead tr:first-child th:nth-child(1) { background: #4c1d95; z-index: 3; }
.reg-table-scroll .reg-table tbody tr:nth-child(even) td:nth-child(1) { background: #f9fafb; }
.reg-table-scroll .reg-table tbody tr:hover td:nth-child(1) { background: #f5f3ff; }

/* Unsaved-changes indicator for a Raw Sheet row */
tr.raw-row-dirty td:first-child { border-left: 3px solid #f59e0b; }
.raw-unsaved-badge { display:inline-block; margin-left:6px; font-size:10px; font-weight:700; color:#b45309; background:#fef3c7; border-radius:8px; padding:1px 6px; }

/* ── Edit button ─────────────────────────────────── */
.reg-edit-btn {
    display: inline-flex; align-items: center; gap: 3px;
    background: #f5f3ff; border: 1px solid #ddd6fe;
    color: #6d28d9; cursor: pointer;
    font-size: 11px; font-weight: 600;
    padding: 3px 8px; border-radius: 5px;
    white-space: nowrap; transition: background .15s;
}
.reg-edit-btn:hover { background: #ede9fe; border-color: #c4b5fd; }

/* ── Edit Modal Panel ────────────────────────────── */
.me-panel {
    background: #fff;
    border-radius: 12px;
    width: min(960px, 96vw);
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 8px 40px rgba(0,0,0,.35);
}
.me-header {
    background: #4c1d95;
    color: #fff;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}
.me-header-text {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 1px;
}
.me-header-title {
    font-weight: 700;
    font-size: 15px;
}
.me-header-sub {
    font-size: 12px;
    color: #d8b4fe;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.me-close {
    background: none;
    border: none;
    color: #fff;
    font-size: 24px;
    cursor: pointer;
    line-height: 1;
    padding: 0 4px;
    flex-shrink: 0;
    opacity: .8;
}
.me-close:hover { opacity: 1; }
.me-body {
    overflow-y: auto;
    flex: 1;
}

/* ── Two-column layout ───────────────────────────── */
.me-cols {
    display: grid;
    grid-template-columns: 1fr 1fr;
}
.me-col {
    border-right: 1px solid #e5e7eb;
}
.me-col:last-child { border-right: none; }

/* ── Section header (bulk-send style) ───────────── */
.me-sec-hdr {
    background: #DCDCDC;
    color: #737373;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    padding: 7px 14px;
    border-bottom: 1px solid #c8c8c8;
    user-select: none;
}

/* ── Row ─────────────────────────────────────────── */
.me-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 7px 14px;
    border-bottom: 1px solid #f3f4f6;
    gap: 10px;
    transition: background .1s;
}
.me-row:hover { background: #fafafa; }
.me-row-label {
    font-size: 12px;
    color: #374151;
    flex: 1;
    min-width: 0;
}
.me-hint {
    color: #9ca3af;
    font-size: 10px;
    margin-left: 3px;
    font-style: italic;
}
.me-row-input {
    width: 112px;
    text-align: right;
    font-size: 12px;
    padding: 5px 8px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #fff;
    flex-shrink: 0;
    -moz-appearance: textfield;
    transition: border-color .15s;
}
.me-row-input:focus {
    outline: 2px solid #7b5cff;
    outline-offset: 1px;
    border-color: #7b5cff;
}
.me-row-input::-webkit-inner-spin-button,
.me-row-input::-webkit-outer-spin-button { -webkit-appearance: none; }
.me-ro {
    background: #f3f4f6 !important;
    color: #6b7280 !important;
    cursor: default !important;
    border-color: transparent !important;
}
.me-neg { color: #dc2626 !important; }

/* ── Summary bar (gross / net pay) ──────────────── */
.me-summary {
    display: flex;
    background: #f9fafb;
    border-top: 2px solid #e5e7eb;
    padding: 12px 14px;
    gap: 16px;
    flex-shrink: 0;
}
.me-summary-col {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}
.me-summary-label {
    font-size: 11px;
    font-weight: 700;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: .05em;
    white-space: nowrap;
}
.me-summary-input {
    flex: 1;
    text-align: right;
    font-size: 14px;
    font-weight: 700;
    padding: 6px 10px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
    color: #374151;
    -moz-appearance: textfield;
}
.me-summary-net {
    color: #15803d !important;
    border-color: #86efac !important;
    background: #f0fdf4 !important;
}
.me-summary-input::-webkit-inner-spin-button,
.me-summary-input::-webkit-outer-spin-button { -webkit-appearance: none; }

/* ── Edit history ────────────────────────────────── */
.me-history {
    padding: 10px 14px;
    background: #fafafa;
    border-top: 1px solid #f0f0f0;
}
.me-history-title {
    font-size: 10px;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 5px;
}
.me-history-entry {
    font-size: 11px;
    color: #6b7280;
    padding: 3px 0;
    border-bottom: 1px dashed #f0f0f0;
    line-height: 1.5;
}
.me-history-entry:last-child { border: none; }
.me-history-badge {
    display: inline-block;
    background: #7b5cff;
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    padding: 1px 5px;
    border-radius: 3px;
    margin-right: 4px;
    vertical-align: middle;
}
.me-history-change {
    font-weight: 600;
    color: #374151;
}

/* ── Footer ──────────────────────────────────────── */
.me-footer {
    padding: 12px 20px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
    background: #fafafa;
}
.me-last-edit {
    font-size: 11px;
    color: #9ca3af;
    font-style: italic;
}
.me-btn-cancel {
    padding: 8px 20px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    background: #fff;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: background .15s;
}
.me-btn-cancel:hover { background: #f3f4f6; }
.me-btn-save {
    padding: 8px 22px;
    border-radius: 8px;
    border: none;
    background: #7b5cff;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
    font-size: 13px;
    transition: background .15s;
}
.me-btn-save:hover    { background: #6649e0; }
.me-btn-save:disabled { background: #c4b5fd; cursor: default; }

@media (max-width: 680px) {
    .me-cols { grid-template-columns: 1fr; }
    .me-col  { border-right: none; border-bottom: 1px solid #e5e7eb; }
}
</style>

<script>
(function () {
    /* ── URL constants (PHP-rendered absolute URLs) ── */
    const REG_URL_ROWS   = <?= json_encode(site_url('client_portal/payroll/getRegisterRows')) ?>;
    const REG_URL_RAW    = <?= json_encode(site_url('client_portal/payroll/getRegisterRawSheet')) ?>;
    const REG_URL_EXPORT = <?= json_encode(site_url('client_portal/payroll/exportRegister')) ?>;
    const REG_URL_EDIT   = <?= json_encode(site_url('client_portal/payroll/getPayslipEditData')) ?>;
    const REG_URL_SAVE   = <?= json_encode(site_url('client_portal/payroll/savePayslipEditData')) ?>;
    const REG_URL_SENDPDF = <?= json_encode(site_url('client_portal/payroll/sendPdf')) ?>;
    let _lastRegisterRows = []; // cached getRegisterRows() output, reused by the Payslip card view

    /* ── Helpers ─────────────────────────────────────── */
    const fmt    = n => parseFloat(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const zeroFmt = n => parseFloat(n || 0) === 0 ? '<span class="zero-cell">—</span>' : fmt(n);

    /* ── Month picker default ─────────────────────── */
    const now = new Date();
    document.getElementById('regMonthPicker').value =
        now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');

    /* ── Load register (and Raw Sheet + Payslip views alongside it) ──── */
    window.loadRegister = async function () {
        const month  = document.getElementById('regMonthPicker').value;
        const period = document.getElementById('regPeriodPicker').value;
        if (!month) { if (typeof showToast === 'function') showToast('Please select a month.', 'warning'); return; }

        // Raw Sheet and Payslip views share the same period selection —
        // load them alongside the Register table so switching tabs is instant.
        loadRegisterRawSheet(month, period);

        document.getElementById('regLoading').classList.remove('hidden');
        document.getElementById('regEmpty').classList.add('hidden');
        document.getElementById('regTable').classList.add('hidden');

        try {
            const url  = REG_URL_ROWS + '?month=' + month + (period ? '&period=' + period : '');
            const rows = await fetch(url).then(r => r.json());
            _lastRegisterRows = rows || [];
            renderRegisterPayslipCards(_lastRegisterRows);

            if (!rows || !rows.length) {
                document.getElementById('regEmpty').textContent = 'No payroll records found for this period.';
                document.getElementById('regEmpty').classList.remove('hidden');
                return;
            }

            const numCols = ['monthly_salary','semi_monthly','year_end_gifts','rice_subsidy',
                'uniform_allow','laundry_allow','meal_allow','medical_allow','other_non_tax',
                'salary_adj','overtime','nsd','absences','holiday_pay','gross_comp',
                'gross_taxable','non_taxable','sss','phic','hdmf','net_taxable','wth',
                'tax_refund','sss_loan','calamity_loan','hdmf_loan','advances','net_pay'];
            const totals = {};
            numCols.forEach(k => { totals[k] = 0; });

            const tbody = document.getElementById('regBody');
            tbody.innerHTML = '';
            rows.forEach(r => {
                numCols.forEach(k => { totals[k] += parseFloat(r[k] || 0); });
                tbody.insertAdjacentHTML('beforeend', `
                <tr>
                    <td class="center">${r.no}</td>
                    <td class="center">
                        <button class="reg-edit-btn" onclick="openRegEdit(${r.payslip_id})" title="Edit payslip">
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Edit
                        </button>
                    </td>
                    <td class="left">${r.employee_name}</td>
                    <td>${fmt(r.monthly_salary)}</td>
                    <td>${fmt(r.semi_monthly)}</td>
                    <td>${zeroFmt(r.year_end_gifts)}</td>
                    <td>${zeroFmt(r.rice_subsidy)}</td>
                    <td>${zeroFmt(r.uniform_allow)}</td>
                    <td>${zeroFmt(r.laundry_allow)}</td>
                    <td>${zeroFmt(r.meal_allow)}</td>
                    <td>${zeroFmt(r.medical_allow)}</td>
                    <td>${zeroFmt(r.other_non_tax)}</td>
                    <td>${zeroFmt(r.salary_adj)}</td>
                    <td>${zeroFmt(r.overtime)}</td>
                    <td>${zeroFmt(r.nsd)}</td>
                    <td>${zeroFmt(r.absences)}</td>
                    <td>${zeroFmt(r.holiday_pay)}</td>
                    <td>${fmt(r.gross_comp)}</td>
                    <td>${fmt(r.gross_taxable)}</td>
                    <td>${fmt(r.non_taxable)}</td>
                    <td>${zeroFmt(r.sss)}</td>
                    <td>${zeroFmt(r.phic)}</td>
                    <td>${zeroFmt(r.hdmf)}</td>
                    <td>${fmt(r.net_taxable)}</td>
                    <td>${fmt(r.wth)}</td>
                    <td>${zeroFmt(r.tax_refund)}</td>
                    <td>${zeroFmt(r.sss_loan)}</td>
                    <td>${zeroFmt(r.calamity_loan)}</td>
                    <td>${zeroFmt(r.hdmf_loan)}</td>
                    <td>${zeroFmt(r.advances)}</td>
                    <td class="net-pay-cell">${fmt(r.net_pay)}</td>
                </tr>`);
            });

            document.getElementById('regFoot').innerHTML = `
            <tr>
                <td colspan="3" style="text-align:left;">TOTAL (${rows.length} employees)</td>
                <td>${fmt(totals.monthly_salary)}</td>
                <td>${fmt(totals.semi_monthly)}</td>
                <td>${fmt(totals.year_end_gifts)}</td>
                <td>${fmt(totals.rice_subsidy)}</td>
                <td>${fmt(totals.uniform_allow)}</td>
                <td>${fmt(totals.laundry_allow)}</td>
                <td>${fmt(totals.meal_allow)}</td>
                <td>${fmt(totals.medical_allow)}</td>
                <td>${fmt(totals.other_non_tax)}</td>
                <td>${fmt(totals.salary_adj)}</td>
                <td>${fmt(totals.overtime)}</td>
                <td>${fmt(totals.nsd)}</td>
                <td>${fmt(totals.absences)}</td>
                <td>${fmt(totals.holiday_pay)}</td>
                <td>${fmt(totals.gross_comp)}</td>
                <td>${fmt(totals.gross_taxable)}</td>
                <td>${fmt(totals.non_taxable)}</td>
                <td>${fmt(totals.sss)}</td>
                <td>${fmt(totals.phic)}</td>
                <td>${fmt(totals.hdmf)}</td>
                <td>${fmt(totals.net_taxable)}</td>
                <td>${fmt(totals.wth)}</td>
                <td>${fmt(totals.tax_refund)}</td>
                <td>${fmt(totals.sss_loan)}</td>
                <td>${fmt(totals.calamity_loan)}</td>
                <td>${fmt(totals.hdmf_loan)}</td>
                <td>${fmt(totals.advances)}</td>
                <td>${fmt(totals.net_pay)}</td>
            </tr>`;

            document.getElementById('regTable').classList.remove('hidden');
        } catch (e) {
            document.getElementById('regEmpty').textContent = 'Error loading data: ' + e.message;
            document.getElementById('regEmpty').classList.remove('hidden');
        } finally {
            document.getElementById('regLoading').classList.add('hidden');
        }
    };

    window.exportRegisterCsv = function () {
        const month  = document.getElementById('regMonthPicker').value;
        const period = document.getElementById('regPeriodPicker').value;
        if (!month) { if (typeof showToast === 'function') showToast('Please select a month first.', 'warning'); return; }
        window.open(REG_URL_EXPORT + '?month=' + month + (period ? '&period=' + period : ''), '_blank');
    };

    /* ── Raw Sheet: inline-editable table, saves per row via savePayslipEditData ── */
    const rawEditableFields = [
        'year_end_gifts','rice_subsidy','uniform_allowance','laundry_allowance','meal_allowance','medical_allowance',
        'other_earnings_non_taxable','salary_adjustment','override_amount','ned_amount',
        'overtime_pay','night_diff_pay','holiday_pay',
        'sss_ee','phic_ee','hdmf_ee','withholding_tax',
        'sss_loan','calamity_loan','hdmf_loan','sal_adv',
    ];

    function rawInput(id, field, val) {
        return `<td style="padding:0;background:#fffde7;"><input type="number" step="0.01" data-id="${id}" data-f="${field}" value="${(parseFloat(val)||0).toFixed(2)}"
            oninput="recomputeRawRow(${id}); markRawRowDirty(${id})" style="width:82px;border:none;background:transparent;text-align:right;font-size:12px;padding:5px 6px;outline:none;"></td>`;
    }
    function rawRO(id, field, val, cls) {
        return `<td style="text-align:right;font-size:12px;padding:6px 8px;${cls||''}"><span data-id="${id}" data-f="${field}">${fmt(val)}</span></td>`;
    }
    function rawField(id, field) {
        return parseFloat(document.querySelector(`input[data-id="${id}"][data-f="${field}"]`)?.value) || 0;
    }

    window.loadRegisterRawSheet = async function (month, period) {
        const wrap  = document.getElementById('regRawTable');
        const empty = document.getElementById('rawLoading');
        empty.textContent = 'Loading…';
        empty.classList.remove('hidden');
        wrap.classList.add('hidden');

        try {
            const url  = REG_URL_RAW + '?month=' + month + (period ? '&period=' + period : '');
            const rows = await fetch(url).then(r => r.json());
            if (!rows || !rows.length) {
                empty.textContent = 'No payroll records found for this period.';
                return;
            }
            const tbody = document.getElementById('regRawBody');
            tbody.innerHTML = rows.map(r => `
                <tr id="rawrow-${r.payslip_id}" data-net-basic="${(parseFloat(r.net_basic_pay)||0) - (parseFloat(r.night_diff_pay)||0)}">
                    <td class="left" style="font-size:12px;font-weight:600;padding:6px 8px;">${r.employee_name}<span class="raw-unsaved-badge" style="display:none;">unsaved</span></td>
                    <td style="text-align:right;font-size:12px;padding:6px 8px;">${fmt(r.monthly_salary)}</td>
                    ${rawInput(r.payslip_id,'year_end_gifts',r.year_end_gifts)}
                    ${rawInput(r.payslip_id,'rice_subsidy',r.rice_subsidy)}
                    ${rawInput(r.payslip_id,'uniform_allowance',r.uniform_allowance)}
                    ${rawInput(r.payslip_id,'laundry_allowance',r.laundry_allowance)}
                    ${rawInput(r.payslip_id,'meal_allowance',r.meal_allowance)}
                    ${rawInput(r.payslip_id,'medical_allowance',r.medical_allowance)}
                    ${rawInput(r.payslip_id,'other_earnings_non_taxable',r.other_earnings_non_taxable)}
                    ${rawInput(r.payslip_id,'salary_adjustment',r.salary_adjustment)}
                    ${rawInput(r.payslip_id,'override_amount',r.override_amount)}
                    ${rawInput(r.payslip_id,'ned_amount',r.ned_amount)}
                    ${rawInput(r.payslip_id,'overtime_pay',r.overtime_pay)}
                    ${rawInput(r.payslip_id,'night_diff_pay',r.night_diff_pay)}
                    ${rawInput(r.payslip_id,'holiday_pay',r.holiday_pay)}
                    ${rawRO(r.payslip_id,'gross_pay',r.gross_pay,'font-weight:700;color:#166534;')}
                    ${rawInput(r.payslip_id,'sss_ee',r.sss_ee)}
                    ${rawInput(r.payslip_id,'phic_ee',r.phic_ee)}
                    ${rawInput(r.payslip_id,'hdmf_ee',r.hdmf_ee)}
                    ${rawInput(r.payslip_id,'withholding_tax',r.withholding_tax)}
                    ${rawInput(r.payslip_id,'sss_loan',r.sss_loan)}
                    ${rawInput(r.payslip_id,'calamity_loan',r.calamity_loan)}
                    ${rawInput(r.payslip_id,'hdmf_loan',r.hdmf_loan)}
                    ${rawInput(r.payslip_id,'sal_adv',r.sal_adv)}
                    ${rawRO(r.payslip_id,'net_pay',r.net_pay,'font-weight:700;color:#166534;')}
                    <td class="center">
                        <button id="rawsave-${r.payslip_id}" onclick="saveRawRow(${r.payslip_id}, this)" class="reg-edit-btn">Save</button>
                    </td>
                </tr>`).join('');
            wrap.classList.remove('hidden');
            empty.classList.add('hidden');
        } catch (e) {
            empty.textContent = 'Error loading data: ' + e.message;
        }
    };

    /* Live recompute for one Raw Sheet row — mirrors the Manual Edit modal's meRecalc() formula */
    window.recomputeRawRow = function (id) {
        // net_basic_pay (not an editable cell) is stored pre-stripped of its original
        // night_diff_pay — see loadRegisterRawSheet — so it can't double-count night
        // diff when added back in below alongside the (possibly edited) nsd field.
        const pureBasic = parseFloat(document.getElementById('rawrow-'+id)?.dataset.netBasic) || 0;
        const salAdj   = rawField(id,'salary_adjustment') + rawField(id,'override_amount') + rawField(id,'ned_amount');
        const ot       = rawField(id,'overtime_pay');
        const nsd      = rawField(id,'night_diff_pay');
        const holiday  = rawField(id,'holiday_pay');

        const deMin    = rawField(id,'year_end_gifts') + rawField(id,'rice_subsidy') + rawField(id,'uniform_allowance')
                        + rawField(id,'laundry_allowance') + rawField(id,'meal_allowance') + rawField(id,'medical_allowance');
        const otherNT  = rawField(id,'other_earnings_non_taxable');
        const nonTax   = deMin + otherNT;
        const gross    = pureBasic + ot + nsd + salAdj + holiday + nonTax;

        const sss      = rawField(id,'sss_ee');
        const phic     = rawField(id,'phic_ee');
        const hdmf     = rawField(id,'hdmf_ee');
        const wt       = rawField(id,'withholding_tax');
        const sssLoan  = rawField(id,'sss_loan');
        const calamity = rawField(id,'calamity_loan');
        const hdmfLoan = rawField(id,'hdmf_loan');
        const adv      = rawField(id,'sal_adv');

        const netPay = gross - sss - phic - hdmf - Math.max(0, wt) + Math.max(0, -wt) - sssLoan - calamity - hdmfLoan - adv;

        const grossEl = document.querySelector(`span[data-id="${id}"][data-f="gross_pay"]`);
        const netEl   = document.querySelector(`span[data-id="${id}"][data-f="net_pay"]`);
        if (grossEl) grossEl.textContent = fmt(gross);
        if (netEl)   netEl.textContent   = fmt(netPay);
    };

    /* Save one Raw Sheet row via the same endpoint the Manual Edit modal uses */
    /* Mark a row as having unsaved changes — shows the row's own badge/border
       and reveals the "Save All Changes" button once any row is dirty. */
    window.markRawRowDirty = function (id) {
        const row = document.getElementById('rawrow-'+id);
        if (!row || row.classList.contains('raw-row-dirty')) return;
        row.classList.add('raw-row-dirty');
        const badge = row.querySelector('.raw-unsaved-badge');
        if (badge) badge.style.display = 'inline-block';
        const saveBtn = document.getElementById('rawsave-'+id);
        if (saveBtn) { saveBtn.textContent = 'Save*'; saveBtn.style.background = '#fef3c7'; saveBtn.style.borderColor = '#f59e0b'; saveBtn.style.color = '#92400e'; }
        document.getElementById('rawSaveAllBtn').style.display = 'inline-block';
    };

    function clearRawRowDirty(id) {
        const row = document.getElementById('rawrow-'+id);
        if (!row) return;
        row.classList.remove('raw-row-dirty');
        const badge = row.querySelector('.raw-unsaved-badge');
        if (badge) badge.style.display = 'none';
        const saveBtn = document.getElementById('rawsave-'+id);
        if (saveBtn) { saveBtn.textContent = 'Save'; saveBtn.style.background = ''; saveBtn.style.borderColor = ''; saveBtn.style.color = ''; }
        if (!document.querySelectorAll('.raw-row-dirty').length) {
            document.getElementById('rawSaveAllBtn').style.display = 'none';
        }
    }

    /* Returns true/false so callers (saveAllRawRows) can count outcomes —
       does NOT throw, since a single row's failure shouldn't abort a batch. */
    window.saveRawRow = async function (id, btn, silent) {
        const origText = btn.textContent;
        btn.disabled = true; btn.textContent = '…';
        const payload = { id };
        rawEditableFields.forEach(f => { payload[f] = rawField(id, f); });
        try {
            const res  = await fetch(REG_URL_SAVE, {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (json.error) throw new Error(json.error);
            clearRawRowDirty(id);
            btn.disabled = false;
            btn.textContent = '✓ Saved';
            setTimeout(() => { if (btn.textContent === '✓ Saved') btn.textContent = 'Save'; }, 1500);
            return true;
        } catch (e) {
            btn.disabled = false; btn.textContent = origText;
            if (!silent && typeof showToast === 'function') showToast('Save failed: ' + e.message, 'error');
            return false;
        }
    };

    /* Save every row currently marked as having unsaved changes. */
    window.saveAllRawRows = async function () {
        const dirtyRows = Array.from(document.querySelectorAll('.raw-row-dirty'));
        if (!dirtyRows.length) return;
        const allBtn = document.getElementById('rawSaveAllBtn');
        allBtn.disabled = true;
        allBtn.textContent = `Saving ${dirtyRows.length}…`;
        let ok = 0, fail = 0;
        for (const row of dirtyRows) {
            const id = row.id.replace('rawrow-', '');
            const saveBtn = document.getElementById('rawsave-'+id);
            const success = await window.saveRawRow(id, saveBtn, /*silent=*/true);
            if (success) ok++; else fail++;
        }
        allBtn.disabled = false;
        allBtn.textContent = 'Save All Changes';
        if (typeof showToast === 'function') {
            showToast(fail ? `${ok} saved, ${fail} failed.` : `${ok} row${ok===1?'':'s'} saved.`, fail ? 'error' : 'success');
        }
    };

    /* ── Payslip view: reuses the View/Send functions already loaded by the
       embedded History section (openPayslipViewer / resendPayslipEmail) ── */
    function renderRegisterPayslipCards(rows) {
        const list  = document.getElementById('regPayslipList');
        const empty = document.getElementById('payslipViewLoading');
        if (!rows || !rows.length) {
            list.innerHTML = '';
            empty.textContent = 'No payroll records found for this period.';
            empty.classList.remove('hidden');
            return;
        }
        empty.classList.add('hidden');
        const fmtDate = s => s ? new Date(s + 'T00:00:00').toLocaleDateString('en-US', {month:'short', day:'2-digit', year:'numeric'}) : '';
        list.innerHTML = rows.map(r => `
            <div style="display:grid;grid-template-columns:2.5fr 1.2fr 1.2fr 1.2fr auto;align-items:center;border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;background:#fff;gap:10px;">
                <div><div style="font-weight:600;font-size:13px;">${r.employee_name}</div><div style="font-size:11px;color:#9ca3af;">${fmtDate(r.period_start)} – ${fmtDate(r.period_end)}</div></div>
                <div style="font-size:12px;color:#374151;">Gross: <strong>&#8369;${fmt(r.gross_comp)}</strong></div>
                <div style="font-size:12px;color:#374151;">Deductions: <strong>&#8369;${fmt(r.sss + r.phic + r.hdmf + r.wth + r.sss_loan + r.calamity_loan + r.hdmf_loan + r.advances)}</strong></div>
                <div style="font-size:12px;color:#15803d;">Net: <strong>&#8369;${fmt(r.net_pay)}</strong></div>
                <div style="display:flex;gap:6px;">
                    <button onclick="openPayslipViewer(${r.payslip_id})" class="px-3 py-1.5 bg-[#7b5cff] hover:bg-[#6a4de0] text-white rounded-lg text-xs font-semibold transition">View</button>
                    <button onclick="resendPayslipEmail(${r.payslip_id}, this)" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-semibold transition">Send</button>
                </div>
            </div>`).join('');
    }

    /* Send every payslip in the currently loaded period — same pattern as
       Bulk Send's "Send All", one confirm up front instead of one per row. */
    window.sendAllRegisterPayslips = async function (btn) {
        if (!_lastRegisterRows.length) { if (typeof showToast === 'function') showToast('Load a period first.', 'warning'); return; }
        if (!confirm(`Send payslips to ${_lastRegisterRows.length} employee(s)?`)) return;

        const spinner = document.getElementById('regSendBtnSpinner');
        const btnText = document.getElementById('regSendBtnText');
        const progressWrap = document.getElementById('regSendProgressWrap');
        const progressFill = document.getElementById('regSendProgressFill');
        const origText = btnText ? btnText.textContent : '';
        if (btn) btn.disabled = true;
        if (spinner) spinner.style.display = 'inline-block';
        if (progressWrap) progressWrap.style.display = 'block';
        if (progressFill) { progressFill.style.width = '0%'; progressFill.style.background = '#16a34a'; }

        let sent = 0, failed = 0, noData = 0, processed = 0;
        let firstErrMsg = '';
        const total = _lastRegisterRows.length;
        for (const r of _lastRegisterRows) {
            processed++;
            // Each one is a real PDF render + email send, not instant — show
            // progress instead of leaving the button looking stuck for however
            // long a large batch actually takes.
            if (btnText) btnText.textContent = `Sending ${processed} of ${total}…`;
            if (progressFill) progressFill.style.width = Math.round((processed - 1) / total * 100) + '%';

            const data = window.PAYSLIP_DATA ? window.PAYSLIP_DATA[r.payslip_id] : null;
            if (!data) { failed++; noData++; continue; }
            try {
                const res  = await fetch(REG_URL_SENDPDF, {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                if (!res.ok || !json.success) throw new Error(json.error || 'Send failed');
                sent++;
            } catch (e) {
                failed++;
                if (!firstErrMsg) firstErrMsg = String(e.message || e);
            }
        }

        if (btn) btn.disabled = false;
        if (spinner) spinner.style.display = 'none';
        if (btnText) btnText.textContent = origText || '✉ Send All';
        if (progressFill) { progressFill.style.width = '100%'; progressFill.style.background = failed ? (sent ? '#d97706' : '#dc2626') : '#16a34a'; }
        if (progressWrap) setTimeout(() => { progressWrap.style.display = 'none'; }, 1500);

        if (typeof showToast === 'function') {
            if (!failed) {
                showToast(`${sent} payslip(s) sent.`, 'success');
            } else {
                const reasonParts = [];
                if (firstErrMsg) reasonParts.push(firstErrMsg);
                if (noData) reasonParts.push(`${noData} had no stored payslip data to send`);
                const reason = reasonParts.length ? ` (${reasonParts.join('; ')})` : '';
                showToast(`${sent} sent, ${failed} failed${reason}.`, sent ? 'warning' : 'error');
            }
        }
    };

    // Auto-load on first tab activation
    document.addEventListener('DOMContentLoaded', function () {
        const regTab = document.querySelector('.tab[data-tab="payroll-register"]');
        if (regTab) {
            let loaded = false;
            regTab.addEventListener('click', function () {
                if (!loaded) { loaded = true; loadRegister(); }
            });
        }
    });

    /* ── Edit modal helpers ──────────────────────────── */
    function mef(id)      { return parseFloat(document.getElementById(id)?.value || 0) || 0; }
    function meSet(id, v) {
        const el = document.getElementById(id);
        if (!el) return;
        el.value = (isNaN(v) ? 0 : v).toFixed(2);
        el.classList.toggle('me-neg', v < -0.001);
    }

    /* Build a single editable / read-only row */
    function meRow(label, id, val, readOnly, hint) {
        const v   = (parseFloat(val) || 0).toFixed(2);
        const ro  = readOnly ? ' readonly' : ' oninput="meRecalc()"';
        const cls = readOnly ? ' me-ro' : '';
        const h   = hint ? `<span class="me-hint">${hint}</span>` : '';
        return `<div class="me-row">
            <span class="me-row-label">${label}${h}</span>
            <input type="number" step="0.01" id="${id}" value="${v}"${ro} class="me-row-input${cls}">
        </div>`;
    }

    /* Friendly field label for history display */
    function meFieldLabel(f) {
        const map = {
            salary_adjustment: 'Salary Adj.', override_amount: 'Override', ned_amount: 'NED',
            overtime_pay: 'OT Pay', night_diff_pay: 'NSD', holiday_pay: 'Holiday Pay',
            year_end_gifts: 'Year-End Gifts', rice_subsidy: 'Rice', uniform_allowance: 'Uniform',
            laundry_allowance: 'Laundry', meal_allowance: 'Meal', medical_allowance: 'Medical',
            salary_adjustment_non_tax: 'Sal.Adj.(NT)', other_earnings_non_taxable: 'Other NT',
            sss_ee: 'SSS', phic_ee: 'PHIC', hdmf_ee: 'HDMF',
            withholding_tax: 'W.Tax', sss_loan: 'SSS Loan', calamity_loan: 'Calamity',
            hdmf_loan: 'HDMF Loan', sal_adv: 'Advances',
        };
        return map[f] || f.replace(/_/g, ' ');
    }

    /* Build edit history block from _edit_history array */
    function meHistoryHtml(history) {
        if (!history || !history.length) return '';
        const recent = history.slice(-5).reverse();
        const rows = recent.map(h => {
            const changeText = (h.changes || []).map(c =>
                `<span class="me-history-change">${meFieldLabel(c.field)}: ${c.old_value} → ${c.new_value}</span>`
            ).join(', ');
            return `<div class="me-history-entry">
                <span class="me-history-badge">EDIT</span>
                <strong>${h.timestamp}</strong> by ${h.edited_by || 'admin'}
                ${changeText ? ' — ' + changeText : ' (no changes)'}
            </div>`;
        }).join('');
        return `<div class="me-history">
            <div class="me-history-title">Edit History (${history.length} save${history.length > 1 ? 's' : ''})</div>
            ${rows}
        </div>`;
    }

    /* Live recompute (exposed globally for oninput handlers) */
    window.meRecalc = function meRecalc() {
        // me_pure_basic (night diff stripped out) + the live me_nsd value below —
        // NOT me_net_basic, which still shows the original night-diff-inclusive
        // subtotal for reference and would double-count it here.
        const pureBasic = mef('me_pure_basic');
        const salAdj   = mef('me_salary_adj') + mef('me_override') + mef('me_ned');
        const ot       = mef('me_ot');
        const nsd      = mef('me_nsd');
        const holiday  = mef('me_holiday');

        const yearEnd  = mef('me_year_end');
        const rice     = mef('me_rice');
        const uniform  = mef('me_uniform');
        const laundry  = mef('me_laundry');
        const meal     = mef('me_meal');
        const medical  = mef('me_medical');
        const salNT    = mef('me_sal_non_tax');
        const otherNT  = mef('me_other_non_tax');

        const deMin    = yearEnd + rice + uniform + laundry + meal + medical + salNT;
        const nonTax   = deMin + otherNT;
        const gross    = pureBasic + ot + nsd + salAdj + holiday + nonTax;
        const grossTax = gross - nonTax;

        const sss      = mef('me_sss');
        const phic     = mef('me_phic');
        const hdmf     = mef('me_hdmf');
        const wt       = mef('me_wt');
        const sssLoan  = mef('me_sss_loan');
        const calamity = mef('me_calamity');
        const hdmfLoan = mef('me_hdmf_loan');
        const adv      = mef('me_adv');

        const netTax = grossTax - sss - phic - hdmf;
        const netPay = gross - sss - phic - hdmf
                     - Math.max(0, wt) + Math.max(0, -wt)
                     - sssLoan - calamity - hdmfLoan - adv;

        meSet('me_gross',     gross);
        meSet('me_non_tax',   nonTax);
        meSet('me_gross_tax', grossTax);
        meSet('me_net_tax',   netTax);
        meSet('me_net_pay',   netPay);
    };

    /* Open edit modal */
    window.openRegEdit = async function (payslipId) {
        if (!payslipId) { alert('No payslip ID found for this row.'); return; }

        const overlay   = document.getElementById('regEditOverlay');
        const body      = document.getElementById('regEditBody');
        const lastEdit  = document.getElementById('me-last-edit');
        const saveBtn   = document.getElementById('regEditSaveBtn');

        overlay.style.display = 'flex';
        body.innerHTML = '<p style="text-align:center;padding:40px;color:#888;font-size:14px;">Loading payslip data…</p>';
        document.getElementById('regEditSubtitle').textContent = '';
        lastEdit.textContent = '';
        saveBtn.dataset.id   = '';

        try {
            const res  = await fetch(REG_URL_EDIT + '?id=' + payslipId);
            const data = await res.json();
            if (data.error) throw new Error(data.error);

            document.getElementById('regEditSubtitle').textContent =
                data.employee_name + '  ·  ' + data.period_start + ' – ' + data.period_end;
            saveBtn.dataset.id = data.id;

            // Footer last-edit info
            const hist = data.edit_history || [];
            if (hist.length) {
                const last = hist[hist.length - 1];
                lastEdit.textContent = 'Last edited: ' + last.timestamp + ' by ' + (last.edited_by || 'admin');
            } else {
                lastEdit.textContent = 'No edits recorded yet.';
            }

            // net_basic_pay already has the original night_diff_pay baked in, and
            // night diff is also its own editable field below — stash the basic
            // pay with night diff stripped back out so meRecalc() can add the
            // (possibly edited) me_nsd value on top without double-counting it.
            const pureBasicPay = (parseFloat(data.net_basic_pay) || 0) - (parseFloat(data.night_diff_pay) || 0);

            body.innerHTML = `
            <div class="me-cols">
                <!-- Left: Income / Earnings -->
                <div class="me-col">
                    <div class="me-sec-hdr">Income &amp; Earnings</div>
                    <input type="hidden" id="me_pure_basic" value="${pureBasicPay.toFixed(2)}">
                    ${meRow('Net Basic Pay', 'me_net_basic', data.net_basic_pay, true, '(read-only)')}
                    ${meRow('Salary Adjustment', 'me_salary_adj', data.salary_adjustment, false, '(negative = deduction)')}
                    ${meRow('Override / Other Taxable', 'me_override', data.override_amount)}
                    ${meRow('NED Amount', 'me_ned', data.ned_amount)}
                    ${meRow('Overtime Pay', 'me_ot', data.overtime_pay)}
                    ${meRow('Night Shift Differential', 'me_nsd', data.night_diff_pay)}
                    ${meRow('Holiday Pay', 'me_holiday', data.holiday_pay)}

                    <div class="me-sec-hdr">De Minimis / Non-Taxable</div>
                    ${meRow('Year-End Gifts', 'me_year_end', data.year_end_gifts)}
                    ${meRow('Rice Subsidy', 'me_rice', data.rice_subsidy)}
                    ${meRow('Uniform / Clothing', 'me_uniform', data.uniform_allowance)}
                    ${meRow('Laundry Allowance', 'me_laundry', data.laundry_allowance)}
                    ${meRow('Meal Allowance', 'me_meal', data.meal_allowance)}
                    ${meRow('Medical Allowance', 'me_medical', data.medical_allowance)}
                    ${meRow('Other Non-Tax Sal. Adj.', 'me_sal_non_tax', data.salary_adjustment_non_tax)}
                    ${meRow('Other Non-Tax Earnings', 'me_other_non_tax', data.other_earnings_non_taxable)}
                </div>

                <!-- Right: Deductions + computed -->
                <div class="me-col">
                    <div class="me-sec-hdr">Government Contributions</div>
                    ${meRow('SSS (EE Share)', 'me_sss', data.sss_ee)}
                    ${meRow('PhilHealth (EE Share)', 'me_phic', data.phic_ee)}
                    ${meRow('Pag-IBIG (EE Share)', 'me_hdmf', data.hdmf_ee)}

                    <div class="me-sec-hdr">Withholding Tax</div>
                    ${meRow('Withholding Tax', 'me_wt', data.withholding_tax, false, '(negative = refund)')}

                    <div class="me-sec-hdr">Loans &amp; Advances</div>
                    ${meRow('SSS Loan', 'me_sss_loan', data.sss_loan)}
                    ${meRow('Calamity Loan', 'me_calamity', data.calamity_loan)}
                    ${meRow('HDMF Loan', 'me_hdmf_loan', data.hdmf_loan)}
                    ${meRow('Salary Advance', 'me_adv', data.sal_adv)}

                    <div class="me-sec-hdr">Computed (read-only)</div>
                    ${meRow('Non-Taxable Total', 'me_non_tax', 0, true)}
                    ${meRow('Gross Taxable Comp.', 'me_gross_tax', 0, true)}
                    ${meRow('Net Taxable Comp.', 'me_net_tax', 0, true)}
                </div>
            </div>

            <!-- Summary bar -->
            <div class="me-summary">
                <div class="me-summary-col">
                    <span class="me-summary-label">Gross Pay</span>
                    <input type="number" step="0.01" id="me_gross" readonly class="me-summary-input me-ro">
                </div>
                <div class="me-summary-col">
                    <span class="me-summary-label">Net Pay</span>
                    <input type="number" step="0.01" id="me_net_pay" readonly class="me-summary-input me-summary-net">
                </div>
            </div>

            ${meHistoryHtml(hist)}`;

            meRecalc();
        } catch (e) {
            body.innerHTML = `<p style="color:#dc2626;padding:20px;font-size:13px;">Error loading payslip: ${e.message}</p>`;
        }
    };

    /* ── Modal button wiring ──────────────────────── */
    document.getElementById('regEditSaveBtn').addEventListener('click', async function () {
        const id = parseInt(this.dataset.id || '0');
        if (!id) { alert('No payslip selected.'); return; }

        const payload = {
            id,
            salary_adjustment:           mef('me_salary_adj'),
            override_amount:             mef('me_override'),
            ned_amount:                  mef('me_ned'),
            overtime_pay:                mef('me_ot'),
            night_diff_pay:              mef('me_nsd'),
            holiday_pay:                 mef('me_holiday'),
            year_end_gifts:              mef('me_year_end'),
            rice_subsidy:                mef('me_rice'),
            uniform_allowance:           mef('me_uniform'),
            laundry_allowance:           mef('me_laundry'),
            meal_allowance:              mef('me_meal'),
            medical_allowance:           mef('me_medical'),
            salary_adjustment_non_tax:   mef('me_sal_non_tax'),
            other_earnings_non_taxable:  mef('me_other_non_tax'),
            sss_ee:                      mef('me_sss'),
            phic_ee:                     mef('me_phic'),
            hdmf_ee:                     mef('me_hdmf'),
            withholding_tax:             mef('me_wt'),
            sss_loan:                    mef('me_sss_loan'),
            calamity_loan:               mef('me_calamity'),
            hdmf_loan:                   mef('me_hdmf_loan'),
            sal_adv:                     mef('me_adv'),
        };

        this.disabled    = true;
        this.textContent = 'Saving…';
        try {
            const res  = await fetch(REG_URL_SAVE, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'Save failed');
            if (typeof showToast === 'function') showToast('Payslip updated successfully.', 'success');
            document.getElementById('regEditOverlay').style.display = 'none';
            loadRegister();
        } catch (e) {
            if (typeof showToast === 'function') showToast('Error: ' + e.message, 'error');
            else alert('Error: ' + e.message);
        } finally {
            this.disabled    = false;
            this.textContent = 'Save Changes';
        }
    });

    function closeRegEdit() {
        document.getElementById('regEditOverlay').style.display = 'none';
    }
    document.getElementById('regEditCloseBtn').addEventListener('click',  closeRegEdit);
    document.getElementById('regEditCancelBtn').addEventListener('click', closeRegEdit);
    document.getElementById('regEditOverlay').addEventListener('click', function (e) {
        if (e.target === this) closeRegEdit();
    });

})();
</script>
