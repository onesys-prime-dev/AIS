<div class="h-full flex flex-col min-h-0 overflow-visible">

    <div class="flex justify-between items-center my-4">
        <div class="p-1">
            <h1 class="text-3xl font-bold text-gray-800 mb-1">Payroll Status</h1>
            <p class="text-gray-500 text-sm">All active employees for the selected month. Mark payroll as Paid once processed. Salary data comes from Employee Management.</p>
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

    <!-- Table header -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm flex flex-col min-h-0 overflow-hidden">
        <div class="grid grid-cols-[2.5fr_1.5fr_1.5fr_1.2fr_1fr] bg-gray-50 border-b text-xs font-semibold text-gray-500 uppercase tracking-wide px-4 py-3">
            <div>Employee</div>
            <div>Monthly Salary</div>
            <div>Payroll Status</div>
            <div>Payslip</div>
            <div class="text-center">Action</div>
        </div>

        <div id="payrollStatusRows" class="overflow-y-auto flex-1">
            <!-- Rows injected by JS -->
        </div>

        <div id="payrollStatusEmpty" class="hidden py-12 text-center text-gray-400 text-sm">
            No active employees found.
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
    const PAYROLLS   = <?= json_encode($payrolls ?? []) ?>;
    const MONTH      = <?= json_encode($selected_month ?? date('Y-m')) ?>;
    const MARK_URL   = "<?= site_url('client_portal/payroll/markStatus') ?>";
    const CSRF_NAME  = "<?= csrf_token() ?>";
    const CSRF_HASH  = "<?= csrf_hash() ?>";

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

        // Action buttons
        const isPaid = currentStatus === 'Paid';
        const markPaidBtn = !isPaid
            ? `<button type="button" onclick="markStatus('${esc(p.employee_id)}','Paid',this)" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-lg transition">Mark Paid</button>`
            : '';
        const markPendingBtn = isPaid
            ? `<button type="button" onclick="markStatus('${esc(p.employee_id)}','Pending',this)" class="px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-semibold rounded-lg transition">Set Pending</button>`
            : '';

        return `
        <div class="grid grid-cols-[2.5fr_1.5fr_1.5fr_1.2fr_1fr] items-center border-b hover:bg-gray-50 px-4 py-3" data-employee-id="${esc(p.employee_id)}">
            <div class="flex items-center gap-3">
                ${avatar}
                <div>
                    <div class="font-medium text-gray-800">${esc(p.employee_name)}</div>
                    <div class="text-xs text-gray-400">${esc(p.position || 'No position set')}</div>
                </div>
            </div>
            <div>${salaryHtml}</div>
            <div class="status-cell">${statusBadge(currentStatus)}</div>
            <div>${payslipBadge(p.payslip_sent_at)}</div>
            <div class="flex justify-center gap-2 action-cell">
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
                body: JSON.stringify({ employee_id: employeeId, month_year: MONTH, status })
            });
            const result = await resp.json();
            if (!resp.ok || !result.success) throw new Error(result.error || 'Server error');

            // Update row in-place
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
            alert('Could not update status: ' + err.message);
        }
    };

    render(PAYROLLS);

    // Month picker
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
