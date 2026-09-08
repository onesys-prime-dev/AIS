<div class="view-timesheet-modal" data-modal="viewEmployeeTimesheetModal" style="display:flex;flex-direction:column;gap:1rem;max-height:65vh;overflow-y:auto;padding-right:4px;">

    <div class="flex items-center gap-3 flex-wrap">
        <div class="etr-emp-picker relative" style="width:260px;">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            </span>
            <input type="text" id="etrEmployeeSearch" placeholder="Search employee…" autocomplete="off"
                class="border border-gray-300 rounded-lg pl-10 pr-8 py-2 text-sm bg-white w-full focus:outline-none focus:ring-2 focus:ring-purple-400">
            <button type="button" id="etrEmployeeClearBtn" class="hidden absolute inset-y-0 right-2 items-center text-gray-400 hover:text-gray-600" title="Clear selection">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
            <input type="hidden" id="etrEmployee" value="">
            <div id="etrEmployeeDropdown" class="hidden absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-[240px] overflow-y-auto etr-scroll"></div>
        </div>

        <button type="button" onclick="etrToggleCustomRange()" id="etrCustomToggleBtn" class="etr-toggle-btn inline-flex items-center gap-1.5 border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-semibold transition">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            Custom Range
            <svg id="etrCustomChevron" class="transition-transform" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
        </button>
    </div>

    <!-- Custom range controls — hidden by default, an alternate way to pick an
         arbitrary window when the fixed cutoff periods below aren't enough. -->
    <div id="etrCustomRange" class="hidden flex items-center gap-3 flex-wrap bg-gray-50 border border-gray-200 rounded-lg p-3 etr-fade-in">
        <input type="date" id="etrStart" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-purple-400">
        <span class="text-gray-400 text-sm">to</span>
        <input type="date" id="etrEnd" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-purple-400">
        <button type="button" onclick="etrLoad()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
            Load
        </button>
    </div>

    <!-- Period list — populated when an employee is selected. Header stays
         pinned via sticky while the rows beneath it scroll. -->
    <div id="etrPeriodList" class="hidden max-h-[200px] overflow-y-auto border border-gray-200 rounded-xl shadow-sm etr-fade-in etr-scroll">
        <div class="grid grid-cols-[2fr_1.5fr_1fr] bg-[#DCDCDC] text-[#737373] text-xs font-bold uppercase tracking-wide sticky top-0 z-10 rounded-t-xl">
            <div class="px-4 py-2.5">Cutoff Period</div>
            <div class="px-4 py-2.5">Total Hours</div>
            <div class="px-4 py-2.5 text-center">Action</div>
        </div>
        <div id="etrPeriodRows"></div>
    </div>

    <p id="etrSubtitle" class="text-sm text-gray-500 font-medium"></p>

    <div class="max-h-[280px] overflow-auto border border-gray-200 rounded-xl etr-scroll">
        <div id="etrPlaceholder" class="etr-placeholder">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="opacity-30"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/></svg>
            <span class="etr-placeholder-text">Select an employee to see their timesheet periods.</span>
        </div>
        <div id="etrTableWrap" class="hidden etr-fade-in"></div>
    </div>
</div>

<style>
    /* Employee Timesheet review UI — Timesheet page modal only. */
    .etr-select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%236b7280' stroke-width='2' viewBox='0 0 24 24'%3E%3Cpath d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'/%3E%3Ccircle cx='12' cy='7' r='4'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:10px center; }
    .etr-toggle-btn.active { background:#f3e8ff; border-color:#c4b5fd; color:#6d28d9; }
    .etr-toggle-btn.active #etrCustomChevron { transform:rotate(180deg); }

    .etr-fade-in { animation: etrFadeIn .25s ease-out; }
    @keyframes etrFadeIn { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:translateY(0); } }

    .etr-placeholder { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; padding:64px 24px; color:#9ca3af; font-size:13px; text-align:center; }
    .etr-placeholder.hidden { display:none; }
    .etr-placeholder.is-loading .etr-spinner { display:inline-block; }
    .etr-spinner { display:none; width:22px; height:22px; border:2.5px solid #e5e7eb; border-top-color:#9333ea; border-radius:50%; animation: etrSpin .7s linear infinite; }
    @keyframes etrSpin { to { transform:rotate(360deg); } }

    .etr-period-row { display:grid; grid-template-columns:2fr 1.5fr 1fr; align-items:center; border-bottom:1px solid #f3f4f6; font-size:13px; transition:background-color .12s; }
    .etr-period-row:last-child { border-bottom:none; }
    .etr-period-row:hover { background:#faf5ff; }
    .etr-period-row.is-current { background:#faf5ff; }
    .etr-current-badge { display:inline-flex; align-items:center; gap:3px; background:#9333ea; color:#fff; font-size:9px; font-weight:700; letter-spacing:.05em; padding:2px 7px; border-radius:999px; margin-left:8px; vertical-align:middle; }
    .etr-action-btn { display:inline-flex; align-items:center; gap:4px; padding:5px 10px; border-radius:7px; font-size:11px; font-weight:600; cursor:pointer; transition:background-color .12s, transform .1s; border:none; }
    .etr-action-btn:active { transform:scale(.96); }
    .etr-btn-view   { background:#f3e8ff; color:#6d28d9; }
    .etr-btn-view:hover   { background:#e9d5ff; }
    .etr-btn-export { background:#ecfdf5; color:#047857; }
    .etr-btn-export:hover { background:#d1fae5; }

    .etr-scroll::-webkit-scrollbar { width:8px; height:8px; }
    .etr-scroll::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:8px; }
    .etr-scroll::-webkit-scrollbar-track { background:transparent; }

    .etr-emp-item { padding:8px 12px; font-size:13px; color:#374151; cursor:pointer; }
    .etr-emp-item:hover, .etr-emp-item.active { background:#f5f3ff; color:#6d28d9; }
    .etr-emp-empty { padding:14px 12px; font-size:12px; color:#9ca3af; text-align:center; }
</style>

<script>
(function () {
    const EMPLOYEES_URL = <?= json_encode(site_url('client_portal/payroll/employees_json')) ?>;
    const HISTORY_URL    = <?= json_encode(site_url('client_portal/timesheet/employee/history')) ?>;
    const TIMESHEET_URL  = <?= json_encode(site_url('client_portal/timesheet/employee')) ?>;
    const EXPORT_URL     = <?= json_encode(site_url('client_portal/timesheet/employee/export')) ?>;

    const empHidden      = document.getElementById('etrEmployee');
    const empSearch      = document.getElementById('etrEmployeeSearch');
    const empClearBtn    = document.getElementById('etrEmployeeClearBtn');
    const empDropdown    = document.getElementById('etrEmployeeDropdown');
    const customRangeEl = document.getElementById('etrCustomRange');
    const customBtn     = document.getElementById('etrCustomToggleBtn');
    const startInput    = document.getElementById('etrStart');
    const endInput      = document.getElementById('etrEnd');
    const periodList    = document.getElementById('etrPeriodList');
    const periodRows    = document.getElementById('etrPeriodRows');
    const subtitle      = document.getElementById('etrSubtitle');
    const placeholder   = document.getElementById('etrPlaceholder');
    const tableWrap      = document.getElementById('etrTableWrap');

    let employeesLoaded = false;
    let employeeList    = [];

    const now = new Date();
    const pad = n => String(n).padStart(2, '0');
    startInput.value = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-01`;
    endInput.value   = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;

    const icons = {
        view: '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
        export: '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></svg>',
    };

    // A request that never resolves (server hang, dropped connection, etc.)
    // must not leave the UI stuck on "Loading…" forever — abort and surface
    // a clear timeout error instead.
    async function fetchJson(url, timeoutMs = 20000) {
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), timeoutMs);
        try {
            const res = await fetch(url, { signal: controller.signal });
            return await res.json();
        } catch (e) {
            if (e.name === 'AbortError') throw new Error('Request timed out — the server took too long to respond.');
            throw e;
        } finally {
            clearTimeout(timer);
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>]/g, m => m === '&' ? '&amp;' : (m === '<' ? '&lt;' : '&gt;'));
    }

    function formatDate(dateStr) {
        const d = new Date(dateStr);
        if (Number.isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    }

    function badge(value, formatted, type) {
        if (!value || value === 0) return `<span class="text-gray-400">—</span>`;
        const styles = {
            ot: 'bg-green-100 text-green-700', rdot: 'bg-blue-100 text-blue-700',
            tardy: 'bg-yellow-100 text-yellow-700', nd: 'bg-purple-100 text-purple-700',
            reg: 'text-gray-800 font-medium', unot: 'bg-red-100 text-red-700',
            ut: 'bg-orange-100 text-orange-700',
        };
        const cls = styles[type] || 'text-gray-800';
        const pill = ['ot', 'rdot', 'tardy', 'nd', 'unot', 'ut'].includes(type);
        return pill
            ? `<span class="inline-block ${cls} px-2 py-0.5 rounded-full text-xs font-semibold">${formatted}</span>`
            : `<span class="${cls}">${formatted}</span>`;
    }

    function setPlaceholder(text, loading) {
        placeholder.classList.remove('hidden');
        placeholder.classList.toggle('is-loading', !!loading);
        placeholder.innerHTML = loading
            ? `<div class="etr-spinner" style="display:inline-block;"></div><span class="etr-placeholder-text">${text}</span>`
            : `<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="opacity-30"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/></svg><span class="etr-placeholder-text">${text}</span>`;
    }

    async function ensureEmployeesLoaded() {
        if (employeesLoaded) return;
        try {
            employeeList = await fetchJson(EMPLOYEES_URL) || [];
            employeesLoaded = true;
        } catch (e) {
            console.error('Failed to load employee list:', e);
        }
    }

    function renderEmpDropdown(filterText) {
        const q = (filterText || '').trim().toLowerCase();
        const matches = q
            ? employeeList.filter(emp => (emp.label || emp.name || '').toLowerCase().includes(q))
            : employeeList;

        empDropdown.innerHTML = matches.length
            ? matches.map(emp => `<div class="etr-emp-item" data-id="${emp.id}" data-name="${escapeHtml(emp.label || emp.name)}">${escapeHtml(emp.label || emp.name)}</div>`).join('')
            : `<div class="etr-emp-empty">No employees found.</div>`;
        empDropdown.classList.remove('hidden');
    }

    function closeEmpDropdown() {
        empDropdown.classList.add('hidden');
    }

    empSearch.addEventListener('focus', async () => {
        await ensureEmployeesLoaded();
        renderEmpDropdown(empSearch.value);
    });
    empSearch.addEventListener('input', () => renderEmpDropdown(empSearch.value));
    empSearch.addEventListener('keydown', e => {
        if (e.key === 'Escape') { closeEmpDropdown(); empSearch.blur(); }
        if (e.key === 'Enter') {
            e.preventDefault();
            const first = empDropdown.querySelector('.etr-emp-item');
            if (first) selectEmployee(first.dataset.id, first.dataset.name);
        }
    });

    // mousedown (not click) fires before the search input's blur handler, so
    // the selection registers before blur tries to revert the typed text.
    empDropdown.addEventListener('mousedown', e => {
        const item = e.target.closest('.etr-emp-item');
        if (!item) return;
        e.preventDefault();
        selectEmployee(item.dataset.id, item.dataset.name);
    });

    empSearch.addEventListener('blur', () => {
        setTimeout(() => {
            closeEmpDropdown();
            const confirmedName = empHidden.dataset.name || '';
            if (empHidden.value && empSearch.value !== confirmedName) {
                empSearch.value = confirmedName;
            } else if (!empHidden.value) {
                empSearch.value = '';
            }
        }, 150);
    });

    empClearBtn.addEventListener('click', () => {
        empHidden.value = '';
        empHidden.dataset.name = '';
        empSearch.value = '';
        empClearBtn.classList.add('hidden');
        empClearBtn.classList.remove('flex');
        onEmployeeChanged('');
        empSearch.focus();
    });

    function selectEmployee(id, name) {
        empHidden.value = id;
        empHidden.dataset.name = name;
        empSearch.value = name;
        closeEmpDropdown();
        empClearBtn.classList.remove('hidden');
        empClearBtn.classList.add('flex');
        onEmployeeChanged(id);
    }

    window.etrToggleCustomRange = function () {
        const show = customRangeEl.classList.contains('hidden');
        customRangeEl.classList.toggle('hidden', !show);
        customBtn.classList.toggle('active', show);
    };

    // Selecting a new employee resets everything and auto-loads their period list.
    async function onEmployeeChanged(employeeId) {
        subtitle.textContent = '';
        tableWrap.classList.add('hidden');
        tableWrap.innerHTML = '';
        periodList.classList.add('hidden');
        periodRows.innerHTML = '';

        if (!employeeId) {
            setPlaceholder('Select an employee to see their timesheet periods.');
            return;
        }

        setPlaceholder('Loading periods…', true);

        try {
            const data = await fetchJson(`${HISTORY_URL}?employee_id=${employeeId}`);
            if (data.error) throw new Error(data.error);

            renderPeriodList(data.current_period, data.history || []);
            setPlaceholder('Select a cutoff period above to view its timesheet.');
        } catch (e) {
            setPlaceholder('Error loading periods: ' + e.message);
        }
    }

    function renderPeriodList(currentPeriod, history) {
        let rowsHtml = '';

        if (currentPeriod) {
            rowsHtml += periodRowHtml(currentPeriod.start, currentPeriod.end, null, true);
        }
        history.forEach(p => {
            rowsHtml += periodRowHtml(p.period_start, p.period_end, p.total_hours, false);
        });

        if (!rowsHtml) {
            periodList.classList.add('hidden');
            return;
        }

        periodRows.innerHTML = rowsHtml;
        periodList.classList.remove('hidden');
    }

    function periodRowHtml(start, end, totalHours, isCurrent) {
        const label = `${formatDate(start)} – ${formatDate(end)}${isCurrent ? '<span class="etr-current-badge">Current</span>' : ''}`;
        const hours = totalHours !== null ? `${Number(totalHours).toFixed(2)} hrs` : '<span class="text-gray-400">In progress</span>';
        return `
            <div class="etr-period-row ${isCurrent ? 'is-current' : ''}">
                <div class="px-4 py-3">${label}</div>
                <div class="px-4 py-3">${hours}</div>
                <div class="px-4 py-3 flex justify-center gap-2">
                    <button onclick="etrViewPeriod('${start}','${end}')" class="etr-action-btn etr-btn-view">${icons.view} View</button>
                    <button onclick="etrExportPeriod('${start}','${end}')" class="etr-action-btn etr-btn-export">${icons.export} Export</button>
                </div>
            </div>`;
    }

    window.etrViewPeriod = function (start, end) {
        loadRange(start, end);
    };

    window.etrExportPeriod = function (start, end) {
        exportRange(start, end);
    };

    window.etrLoad = function () {
        const start = startInput.value;
        const end   = endInput.value;
        if (!start || !end) { if (typeof showToast === 'function') showToast('Select a date range.', 'warning'); return; }
        loadRange(start, end);
    };

    function exportRange(start, end) {
        const employeeId = empHidden.value;
        if (!employeeId) { if (typeof showToast === 'function') showToast('Select an employee first.', 'warning'); return; }
        window.open(`${EXPORT_URL}?employee_id=${employeeId}&start=${start}&end=${end}`, '_blank');
    }

    async function loadRange(start, end) {
        const employeeId = empHidden.value;
        if (!employeeId) { if (typeof showToast === 'function') showToast('Select an employee first.', 'warning'); return; }

        setPlaceholder('Loading…', true);
        tableWrap.classList.add('hidden');

        try {
            const url  = `${TIMESHEET_URL}?employee_id=${employeeId}&start=${start}&end=${end}`;
            const data = await fetchJson(url);

            if (data.error) throw new Error(data.error);

            subtitle.textContent = `${data.employee_name || ''}  ·  ${formatDate(start)} – ${formatDate(end)}`;
            renderTable(data.daily || [], data.totals || {});
        } catch (e) {
            setPlaceholder('Error loading timesheet: ' + e.message);
            tableWrap.classList.add('hidden');
        }
    }

    function renderTable(rows, totals) {
        if (!rows.length) {
            setPlaceholder('No attendance records found for this period.');
            tableWrap.classList.add('hidden');
            return;
        }

        // Count sessions per date so repeat clock-in/outs on the same day can be
        // visually grouped instead of repeating the full date on every row.
        const dateCounts = {};
        rows.forEach(day => { dateCounts[day.date] = (dateCounts[day.date] || 0) + 1; });

        let rowsHtml = '';
        let prevDate = null;
        rows.forEach((day, i) => {
            const isContinuation = day.date === prevDate;
            prevDate = day.date;

            const rowBg  = i % 2 === 0 ? '' : 'bg-gray-50/60';
            const border = isContinuation ? 'border-b border-dashed border-gray-100' : 'border-b-2 border-gray-200';

            const dateCell = isContinuation
                ? `<span class="text-gray-300 text-xs font-normal pl-3">↳ same day</span>`
                : `${formatDate(day.date)}${dateCounts[day.date] > 1 ? `<span class="ml-1 text-[10px] font-normal text-gray-400">(${dateCounts[day.date]}×)</span>` : ''}`;

            const timeIn  = day.in  && day.in  !== '-' ? escapeHtml(day.in)  : '<span class="text-gray-400">—</span>';
            const timeOut = day.out && day.out !== '-' ? escapeHtml(day.out) : '<span class="text-gray-400">—</span>';
            const remarksTitle = escapeHtml(day.remarks || '');
            const clientTitle  = escapeHtml(day.client || '');
            const taskTitle    = escapeHtml(day.task_performed || '');
            const clientCell = day.client ? `<span class="text-gray-700">${escapeHtml(day.client)}</span>` : '<span class="text-gray-300">—</span>';
            const dayTasks = day.tasks || [];
            const taskCell = dayTasks.length
                ? `<span class="text-gray-700">${escapeHtml(dayTasks[0].task_performed)}</span>${dayTasks.length > 1 ? `<span class="ml-1 text-[10px] font-semibold text-purple-500">+${dayTasks.length - 1}</span>` : ''}`
                : (day.task_performed ? `<span class="text-gray-700">${escapeHtml(day.task_performed)}</span>` : '<span class="text-gray-300">—</span>');

            rowsHtml += `
                <tr class="${border} ${rowBg}">
                    <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-700">${dateCell}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-gray-600">${timeIn}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-gray-600">${timeOut}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">${badge(day.regular_hours, (day.regular_hours || 0).toFixed(2), 'reg')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">${badge(day.ot_hours, (day.ot_hours || 0).toFixed(2), 'ot')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap" title="Worked but not approved">${badge(day.unapproved_ot_hours, (day.unapproved_ot_hours || 0).toFixed(2), 'unot')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">${badge(day.rdot_hours, (day.rdot_hours || 0).toFixed(2), 'rdot')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">${badge(Math.round(day.tardy_minutes || 0), Math.round(day.tardy_minutes || 0).toLocaleString(), 'tardy')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">${badge(day.undertime_hours, (day.undertime_hours || 0).toFixed(2), 'ut')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">${badge(day.night_diff, (day.night_diff || 0).toFixed(2), 'nd')}</td>
                    <td class="px-4 py-3 text-sm text-gray-500 max-w-[180px] truncate" title="${remarksTitle}">${escapeHtml(day.remarks) || '<span class="text-gray-300">—</span>'}</td>
                    <td class="px-4 py-3 text-sm max-w-[120px] truncate" title="${clientTitle}">${clientCell}</td>
                    <td class="px-4 py-3 text-sm max-w-[180px] truncate" title="${taskTitle}">${taskCell}</td>
                </tr>`;
        });

        const t = Object.assign({
            regular_hours: 0, ot_hours: 0, rdot_hours: 0, tardy_minutes: 0,
            night_diff: 0, unapproved_ot_hours: 0, undertime_hours: 0,
        }, totals);

        tableWrap.innerHTML = `
            <table class="min-w-[1280px] w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-[#DCDCDC] text-[#737373] border-y border-[#C5C5C5] h-[48px]">
                        <th class="px-4 text-left font-bold whitespace-nowrap">Date</th>
                        <th class="px-4 text-left font-bold whitespace-nowrap">Time In</th>
                        <th class="px-4 text-left font-bold whitespace-nowrap">Time Out</th>
                        <th class="px-4 text-right font-bold whitespace-nowrap">Regular Hrs</th>
                        <th class="px-4 text-right font-bold whitespace-nowrap">OT Hrs</th>
                        <th class="px-4 text-right font-bold whitespace-nowrap">Unapproved OT</th>
                        <th class="px-4 text-right font-bold whitespace-nowrap">RDOT Hrs</th>
                        <th class="px-4 text-right font-bold whitespace-nowrap">Tardy (min)</th>
                        <th class="px-4 text-right font-bold whitespace-nowrap">Undertime</th>
                        <th class="px-4 text-right font-bold whitespace-nowrap">Night Diff</th>
                        <th class="px-4 text-left font-bold whitespace-nowrap">Remarks</th>
                        <th class="px-4 text-left font-bold whitespace-nowrap">Client</th>
                        <th class="px-4 text-left font-bold whitespace-nowrap">Task Performed</th>
                    </tr>
                </thead>
                <tbody>${rowsHtml}</tbody>
                <tfoot>
                    <tr class="bg-[#DCDCDC] text-[#666] border-t border-[#C5C5C5] h-[48px] font-semibold">
                        <td colspan="3" class="px-4 text-center tracking-wide">TOTALS</td>
                        <td class="px-4 text-right">${t.regular_hours.toFixed(2)}</td>
                        <td class="px-4 text-right">${t.ot_hours.toFixed(2)}</td>
                        <td class="px-4 text-right">${t.unapproved_ot_hours.toFixed(2)}</td>
                        <td class="px-4 text-right">${t.rdot_hours.toFixed(2)}</td>
                        <td class="px-4 text-right">${Math.round(t.tardy_minutes).toLocaleString()}</td>
                        <td class="px-4 text-right">${t.undertime_hours.toFixed(2)}</td>
                        <td class="px-4 text-right">${t.night_diff.toFixed(2)}</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>`;

        placeholder.classList.add('hidden');
        tableWrap.classList.remove('hidden');
    }

    // Lazy-load the employee list the first time this modal is opened rather
    // than on every page load (it's only relevant to payroll-staff users).
    document.querySelector('[data-modal-open="viewEmployeeTimesheetModal"]')
        ?.addEventListener('click', ensureEmployeesLoaded);
})();
</script>
