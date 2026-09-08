<div class="h-full flex flex-col min-h-0 overflow-visible">

    <div class="flex justify-between items-center my-4">
        <div class="p-1">
            <h1 class="text-3xl font-bold text-gray-800 mb-1">Payroll Status</h1>
            <p class="text-gray-500 text-sm">Track payroll per period. Mark as Paid once processed.</p>
        </div>

        <div class="flex items-center gap-3">
            <!-- Month Picker -->
            <div class="month-picker relative" id="monthPickerContainer">
                <div class="flex items-center h-12 bg-white border border-gray-200 rounded-lg shadow-sm px-3 hover:bg-[#f3f4f6] hover:shadow-[0_6px_12px_rgba(0,0,0,0.3)] transition hover:translate-y-[-2px]">
                    <button type="button" class="month-picker-icon mr-2" aria-label="Open month picker" onclick="openMonthPicker()">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v11a2 2 0 002 2z"/>
                        </svg>
                    </button>
                    <button type="button" class="month-picker-label" onclick="openMonthPicker()" aria-label="Select month">
                        <?= date('F Y', strtotime(($selected_month ?? date('Y-m')) . '-01')) ?>
                    </button>
                    <input type="hidden" id="monthPicker" value="<?= $selected_month ?? date('Y-m') ?>">
                </div>
                <div class="month-picker-modal" id="monthPickerModal" role="dialog" aria-label="Select month">
                    <div class="month-picker-modal-header">
                        <button type="button" class="month-picker-nav" aria-label="Previous year" onclick="shiftPickerYear(-1)">‹</button>
                        <strong id="monthPickerYearLabel"></strong>
                        <button type="button" class="month-picker-nav" aria-label="Next year" onclick="shiftPickerYear(1)">›</button>
                    </div>
                    <div class="month-picker-grid" id="monthPickerGrid"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Period tabs + Search row -->
    <div class="flex items-center justify-between mb-3 gap-3 flex-wrap">
        <!-- Period Tabs -->
        <div class="flex items-center bg-gray-100 rounded-xl p-1 gap-1" id="periodTabBar">
<?php
    $selPeriod = $selected_period ?? 'monthly';
    // Format a Y-m-d date as "Mon d" (e.g. "Jun 1")
    $fmtPD = fn($d) => $d ? date('M j', strtotime($d)) : null;
    $h1Start = $fmtPD($period_start_date ?? null);
    $h1End   = $fmtPD($period_end_date   ?? null);
    // For the inactive tab labels we need the other half's dates; they aren't computed here,
    // so we fall back to generic labels when the other half isn't selected.
?>
            <button type="button" data-period="monthly"
                class="period-tab px-4 py-2 rounded-lg text-sm font-medium transition-all <?= $selPeriod === 'monthly' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                Monthly
            </button>
            <button type="button" data-period="1st_half"
                class="period-tab px-4 py-2 rounded-lg text-sm font-medium transition-all <?= $selPeriod === '1st_half' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                1st Half<?php if ($selPeriod === '1st_half' && $h1Start && $h1End): ?>
                    <span class="text-xs <?= $selPeriod === '1st_half' ? 'text-gray-500' : 'text-gray-400' ?> ml-1">(<?= esc($h1Start) ?>–<?= esc($h1End) ?>)</span>
                <?php endif ?>
            </button>
            <button type="button" data-period="2nd_half"
                class="period-tab px-4 py-2 rounded-lg text-sm font-medium transition-all <?= $selPeriod === '2nd_half' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                2nd Half<?php if ($selPeriod === '2nd_half' && $h1Start && $h1End): ?>
                    <span class="text-xs <?= $selPeriod === '2nd_half' ? 'text-gray-500' : 'text-gray-400' ?> ml-1">(<?= esc($h1Start) ?>–<?= esc($h1End) ?>)</span>
                <?php endif ?>
            </button>
        </div>

        <!-- Search -->
        <div class="relative flex-shrink-0">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input type="text" id="employeeSearch" placeholder="Search employee…"
                class="pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-400 w-52 transition">
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white border border-gray-400 rounded-2xl shadow-lg flex flex-col min-h-0 overflow-hidden p-5 gap-3">
        <div class="grid grid-cols-[2.5fr_1.5fr_1.5fr_1.2fr_1fr]
                    bg-[#DCDCDC] text-[#737373] border border-[#9E9E9E] rounded-lg overflow-hidden text-sm font-bold">
            <div class="p-4">Employee</div>
            <div class="p-4">Monthly Salary</div>
            <div class="p-4">Payroll Status</div>
            <div class="p-4">Payslip</div>
            <div class="p-4 text-center">Action</div>
        </div>

        <div id="payrollStatusRows" class="overflow-y-auto flex-1 text-gray-700 hide-scroll-bar">
            <!-- Rows injected by JS -->
        </div>

        <div id="payrollStatusEmpty" class="hidden py-12 text-center text-gray-400 text-sm">
            No active employees found.
        </div>

        <div id="payrollStatusNoMatch" class="hidden py-12 text-center text-gray-400 text-sm">
            No employees match your search.
        </div>
    </div>
</div>

<style>
    .month-picker { position: relative; }
    .month-picker-icon { background:transparent; border:0; padding:0; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; }
    .month-picker-label { background:transparent; border:0; padding:0; color:#374151; font-size:0.95rem; line-height:1.5rem; display:inline-flex; align-items:center; cursor:pointer; min-width:110px; text-align:center; }
    .month-picker-modal { position:absolute; top:calc(100% + 8px); left:0; width:250px; background:#ffffff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 16px 32px rgba(15,23,42,0.16); padding:10px; z-index:9999; display:none; }
    .month-picker-modal.open { display:block; z-index:99999; }
    .month-picker-modal-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; padding:2px 2px 8px; border-bottom:1px solid #f1f5f9; }
    .month-picker-modal-header strong { color:#111827; font-size:0.95rem; }
    .month-picker-nav { width:30px; height:30px; border:0; border-radius:8px; background:#f3f4f6; color:#374151; cursor:pointer; font-size:1rem; line-height:1; }
    .month-picker-nav:hover { background:#e5e7eb; }
    .month-picker-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; }
    .month-picker-cell { border:0; border-radius:10px; padding:8px 6px; background:#f3f4f6; color:#374151; font-size:0.82rem; cursor:pointer; text-align:center; }
    .month-picker-cell:hover { background:#e2e8f0; }
    .month-picker-cell.active { background:#2563eb; color:#ffffff; }
</style>

<script>
(function () {
    const PAYROLLS    = <?= json_encode($payrolls ?? []) ?>;
    const MONTH       = <?= json_encode($selected_month ?? date('Y-m')) ?>;
    const PERIOD      = <?= json_encode($selected_period ?? 'monthly') ?>;
    const MARK_URL    = "<?= site_url('client_portal/payroll/markStatus') ?>";
    const CSRF_NAME   = "<?= csrf_token() ?>";
    const CSRF_HASH   = "<?= csrf_hash() ?>";

    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, m =>
            ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[m]);
    }

    function money(v) {
        return Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function statusBadge(status) {
        if (status === 'Paid')    return '<span class="inline-flex items-center gap-1 bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">&#10003; Paid</span>';
        if (status === 'Pending') return '<span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-semibold">&#9679; Pending</span>';
        return '<span class="inline-flex items-center gap-1 bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-xs font-semibold">— Not Set</span>';
    }

    function payslipBadge(sentAt) {
        if (sentAt) return '<span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-semibold">&#10003; Sent</span>';
        return '<span class="inline-flex items-center gap-1 bg-gray-100 text-gray-400 px-3 py-1 rounded-full text-xs font-semibold">Not Sent</span>';
    }

    function renderRow(p) {
        const initials = (p.employee_name || 'U').substring(0, 2).toUpperCase();
        const avatar = p.photo_path
            ? `<div class="w-10 h-10 rounded-full flex-shrink-0" style="background-image:url('${esc(p.photo_path)}');background-size:cover;background-position:center;"></div>`
            : `<div class="w-10 h-10 bg-gray-300 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold text-gray-700">${initials}</div>`;

        const hasSalary = parseFloat(p.monthly_salary) > 0;
        const salaryHtml = hasSalary
            ? `<span class="font-medium text-gray-800">₱ ${money(p.monthly_salary)}</span>`
            : `<span class="text-red-400 text-xs">No salary set</span>`;

        const currentStatus = p.payroll_status || null;
        const isPaid = currentStatus === 'Paid';
        const markPaidBtn = !isPaid
            ? `<button type="button" onclick="markStatus('${esc(p.employee_id)}','Paid',this)" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-lg transition">Mark Paid</button>`
            : '';
        const markPendingBtn = isPaid
            ? `<button type="button" onclick="markStatus('${esc(p.employee_id)}','Pending',this)" class="px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-semibold rounded-lg transition">Set Pending</button>`
            : '';

        const searchKey = (p.employee_name + ' ' + (p.position || '')).toLowerCase();

        return `
        <div class="grid grid-cols-[2.5fr_1.5fr_1.5fr_1.2fr_1fr] items-center border-b hover:bg-gray-50 transition-colors ps-row"
             data-employee-id="${esc(p.employee_id)}"
             data-search="${esc(searchKey)}">
            <div class="p-4 flex items-center gap-3">
                ${avatar}
                <div>
                    <div class="font-medium text-gray-800">${esc(p.employee_name)}</div>
                    <div class="text-xs text-gray-400">${esc(p.position || 'No position set')}</div>
                </div>
            </div>
            <div class="p-4">${salaryHtml}</div>
            <div class="p-4 status-cell">${statusBadge(currentStatus)}</div>
            <div class="p-4">${payslipBadge(p.payslip_sent_at)}</div>
            <div class="p-4 flex justify-center gap-2 action-cell">
                ${markPaidBtn}${markPendingBtn}
            </div>
        </div>`;
    }

    function render(data) {
        const container = document.getElementById('payrollStatusRows');
        const empty     = document.getElementById('payrollStatusEmpty');
        if (!data.length) {
            container.innerHTML = '';
            empty.classList.remove('hidden');
            return;
        }
        empty.classList.add('hidden');
        container.innerHTML = data.map(renderRow).join('');
    }

    window.markStatus = async function(employeeId, status, btn) {
        btn.disabled = true;
        btn.textContent = '…';
        try {
            const resp = await fetch(MARK_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ employee_id: employeeId, month_year: MONTH, status, period_type: PERIOD })
            });
            const result = await resp.json();
            if (!resp.ok || !result.success) throw new Error(result.error || 'Server error');

            const row = btn.closest('[data-employee-id]');
            row.querySelector('.status-cell').innerHTML = statusBadge(status);
            const actionCell = row.querySelector('.action-cell');
            if (status === 'Paid') {
                actionCell.innerHTML = `<button type="button" onclick="markStatus('${employeeId}','Pending',this)" class="px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-semibold rounded-lg transition">Set Pending</button>`;
            } else {
                actionCell.innerHTML = `<button type="button" onclick="markStatus('${employeeId}','Paid',this)" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-lg transition">Mark Paid</button>`;
            }
        } catch (err) {
            btn.disabled = false;
            btn.textContent = status === 'Paid' ? 'Mark Paid' : 'Set Pending';
            showToast('Could not update status: ' + err.message, 'error');
        }
    };

    render(PAYROLLS);

    // ── Search ────────────────────────────────────────────────────────────────
    document.getElementById('employeeSearch')?.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        const rows = document.querySelectorAll('.ps-row');
        const noMatch = document.getElementById('payrollStatusNoMatch');
        let visible = 0;
        rows.forEach(row => {
            const match = !q || row.dataset.search.includes(q);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        noMatch.classList.toggle('hidden', visible > 0 || rows.length === 0);
    });

    // ── Period Tabs ───────────────────────────────────────────────────────────
    document.querySelectorAll('.period-tab').forEach(btn => {
        btn.addEventListener('click', function () {
            const period = this.dataset.period;
            const url = new URL(window.location.href);
            url.searchParams.set('month', MONTH);
            url.searchParams.set('period', period);
            window.location.href = url.toString();
        });
    });

    // ── Month Picker ──────────────────────────────────────────────────────────
    const MONTH_NAMES_SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    let pickerYear = null;

    function renderMonthGrid() {
        const grid = document.getElementById('monthPickerGrid');
        const yearLabel = document.getElementById('monthPickerYearLabel');
        const input = document.getElementById('monthPicker');
        if (!grid || !yearLabel || !input || pickerYear == null) return;
        const [selectedYear, selectedMonth] = (input.value || '').split('-');
        yearLabel.textContent = String(pickerYear);
        grid.innerHTML = MONTH_NAMES_SHORT.map((name, idx) => {
            const monthNum = String(idx + 1).padStart(2, '0');
            const isActive = Number(selectedYear) === pickerYear && selectedMonth === monthNum;
            return `<button type="button" class="month-picker-cell${isActive?' active':''}" onclick="selectPickerMonth(${idx+1})">${name}</button>`;
        }).join('');
    }

    window.openMonthPicker = function() {
        const modal = document.getElementById('monthPickerModal');
        const input = document.getElementById('monthPicker');
        if (!modal || !input) return;
        pickerYear = Number((input.value || '').split('-')[0]) || new Date().getFullYear();
        renderMonthGrid();
        modal.classList.add('open');
    };

    window.shiftPickerYear = function(offset) {
        pickerYear = (pickerYear ?? new Date().getFullYear()) + offset;
        renderMonthGrid();
    };

    window.selectPickerMonth = function(monthNumber) {
        const input = document.getElementById('monthPicker');
        if (!input || pickerYear == null) return;
        input.value = `${pickerYear}-${String(monthNumber).padStart(2,'0')}`;
        const label = document.querySelector('.month-picker-label');
        if (label) {
            const d = new Date(pickerYear, monthNumber - 1, 1);
            label.textContent = d.toLocaleString('en-US', { month:'long', year:'numeric' });
        }
        input.dispatchEvent(new Event('change', { bubbles: true }));
        document.getElementById('monthPickerModal')?.classList.remove('open');
    };

    document.getElementById('monthPicker')?.addEventListener('change', function () {
        const url = new URL(window.location.href);
        url.searchParams.set('month', this.value);
        url.searchParams.set('period', PERIOD);
        window.location.href = url.toString();
    });

    document.addEventListener('click', function(e) {
        const container = document.getElementById('monthPickerContainer');
        if (container && !container.contains(e.target)) {
            document.getElementById('monthPickerModal')?.classList.remove('open');
        }
    });
})();
</script>
