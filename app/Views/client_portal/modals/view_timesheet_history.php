<?php
    $timesheetHistory = $timesheetHistory ?? [];
?>

<div class="view-timesheet-modal" data-modal="viewTimesheetHistoryModal" >
    <div class="history-search-wrap">
        <span class="search-icon-prefix">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd"
                d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5
                6.75 6.75 0 0 0 0-13.5ZM2.25 10.5
                a8.25 8.25 0 1 1 14.59 5.28l4.69
                4.69a.75.75 0 1 1-1.06 1.06l-4.69
                -4.69A8.25 8.25 0 0 1 2.25 10.5Z"
                clip-rule="evenodd"/>
            </svg>
        </span>

        <input
            type="text"
            placeholder="Search..."
            class="crud-search history-search-input"
        >
    </div>

    <?= view('components/table', [
        'title' => 'Timesheet History',
        'tableId' => 'timesheetHistoryTable',
        'gridStyle' => 'minmax(0,2fr) minmax(0,1.5fr) minmax(0,1fr)',
        'columns' => [
            'Date Range',
            'Total Hours',
            'Action'
        ]
    ]) ?>
</div>

<script>
    const TIMESHEET_HISTORY = <?= json_encode($timesheetHistory) ?>;

    function formatHistoryDate(value) {
        if (!value) return '---';

        const date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        return date.toLocaleDateString([], {
            year: 'numeric',
            month: 'short',
            day: '2-digit'
        });
    }

    function historyRowTemplate(item) {
        const dateRange = `${formatHistoryDate(item.period_start)} - ${formatHistoryDate(item.period_end)}`;

        return `
            <div class="grid items-center border-b hover:bg-gray-50 w-full" style="grid-template-columns: minmax(0,2fr) minmax(0,1.5fr) minmax(0,1fr);">
                <div class="p-4">${dateRange}</div>
                <div class="p-4">${item.total_hours ? `${Number(item.total_hours).toFixed(2)} hrs` : '---'}</div>
                <div class="p-4">
                    <div class="flex justify-center gap-2">

                        <button
                            onclick="viewHistory('${item.period_start}', '${item.period_end}')"
                            class="px-3 py-2 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition">
                            View
                        </button>

                        <button
                            onclick="exportHistory('${item.period_start}', '${item.period_end}')"
                            class="px-3 py-2 rounded-lg bg-green-100 text-green-700 text-xs font-semibold hover:bg-green-200 transition">
                            Export
                        </button>

                    </div>
                </div>
            </div>
        `;
    }

    function exportHistory(start, end)
    {
        window.location.href = `<?= site_url('client_portal/timesheet/export-history') ?>?start=${start}&end=${end}`;
    }

    function toggleHistoryTaskRow(rowId) {
        const row = document.getElementById(rowId);
        if (!row) return;
        row.classList.toggle('hidden');
        const chevron = document.getElementById(rowId + '-chevron');
        if (chevron) chevron.style.transform = row.classList.contains('hidden') ? '' : 'rotate(180deg)';
    }

    function viewHistory(start, end)
    {
        fetch(
            `<?= site_url('client_portal/timesheet/history-details') ?>?start=${start}&end=${end}`
        )
        .then(res => res.json())
        .then(data => {
            openModalById('viewHistoryDetailsModal');
            const subtitle = document.getElementById('historyDetailsSubtitle');
            if (subtitle) subtitle.textContent = `${formatHistoryDate(start)} – ${formatHistoryDate(end)}`;
            renderHistoryDetails(data.daily || [], data.totals || {});
        });
    }

    // Same badge styling as the main timesheet table, reused here (both
    // scripts run on the same page, so these are already defined globally
    // by the time a user opens this modal).
    function historyCell(value, formatted, type) {
        return (typeof badge === 'function')
            ? badge(value, formatted, type)
            : (formatted ?? '—');
    }

    function renderHistoryDetails(rows, totals)
    {
        const container = document.getElementById('historyDetailsContainer');
        if (!container) return;

        if (!rows.length) {
            container.innerHTML = `
                <div class="flex flex-col items-center gap-2 text-gray-400 py-10">
                    <span class="text-base font-semibold text-gray-500">No records found</span>
                    <span class="text-sm">No attendance records for this period.</span>
                </div>`;
            return;
        }

        const esc = (typeof escapeHtml === 'function') ? escapeHtml : (s => s ?? '');
        const fmtDate = (typeof formatDate === 'function') ? formatDate : (d => d);

        function fmtHistoryTaskTime(timeStr) {
            const d = new Date(`2000-01-01T${String(timeStr).length === 5 ? timeStr + ':00' : timeStr}`);
            return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        }

        // Spans crossing midnight (end <= start) run into the next day, same
        // convention used by the Timesheet page's own task-duration math.
        function historyTaskDuration(startStr, endStr) {
            const start = new Date(`2000-01-01T${String(startStr).length === 5 ? startStr + ':00' : startStr}`);
            let end = new Date(`2000-01-01T${String(endStr).length === 5 ? endStr + ':00' : endStr}`);
            if (end <= start) end = new Date(end.getTime() + 86400000);
            const totalMin = Math.round((end - start) / 60000);
            const h = Math.floor(totalMin / 60);
            const m = totalMin % 60;
            if (h && m) return `${h}h ${m}m`;
            if (h) return `${h}h`;
            return `${m}m`;
        }

        let rowsHtml = '';
        rows.forEach((day, i) => {
            const rowBg = i % 2 === 0 ? '' : 'bg-gray-50/60';
            const timeIn  = day.in  && day.in  !== '-' ? esc(day.in)  : '<span class="text-gray-400">—</span>';
            const nextDay = day.out_date && day.out_date !== day.date ? ` <span class="text-[10px] font-semibold text-gray-400">${esc(fmtDate(day.out_date))}</span>` : '';
            const timeOut = day.out && day.out !== '-' ? esc(day.out) + nextDay : '<span class="text-gray-400">—</span>';
            const clientCell = day.client ? `<span class="text-gray-700">${esc(day.client)}</span>` : '<span class="text-gray-300">—</span>';

            const dayTasks = day.tasks || [];
            const taskTooltip = dayTasks.length
                ? dayTasks.map(t => `${fmtHistoryTaskTime(t.start_time)}–${fmtHistoryTaskTime(t.end_time)} (${historyTaskDuration(t.start_time, t.end_time)}): ${t.task_performed}${t.client ? ' (' + t.client + ')' : ''}`).join('\n')
                : (day.task_performed || '');
            const taskTotalMs = dayTasks.reduce((sum, t) => {
                const start = new Date(`2000-01-01T${String(t.start_time).length === 5 ? t.start_time + ':00' : t.start_time}`);
                let end = new Date(`2000-01-01T${String(t.end_time).length === 5 ? t.end_time + ':00' : t.end_time}`);
                if (end <= start) end = new Date(end.getTime() + 86400000);
                return sum + (end - start);
            }, 0);
            const taskTotalLabel = (() => {
                const totalMin = Math.round(taskTotalMs / 60000);
                const h = Math.floor(totalMin / 60);
                const m = totalMin % 60;
                if (h && m) return `${h}h ${m}m`;
                if (h) return `${h}h`;
                return `${m}m`;
            })();
            const rowId = `task-expand-history-${i}`;
            const taskCell = dayTasks.length
                ? (dayTasks.length > 1
                    ? `<div class="flex items-center gap-2 min-w-0">
                           <span class="text-gray-700 truncate" style="max-width:150px;" title="${esc(dayTasks[0].task_performed)}">${esc(dayTasks[0].task_performed)}</span>
                           <span class="flex-shrink-0 inline-flex items-center gap-1 bg-purple-50 text-purple-700 px-2 py-0.5 rounded-full text-[11px] font-semibold" title="Click this row to show all ${dayTasks.length} tasks">
                                    ${dayTasks.length} tasks · ${taskTotalLabel}
                                    <svg id="${rowId}-chevron" class="transition-transform" style="width:10px;height:10px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </span>
                       </div>`
                    : `<span class="text-gray-700 truncate inline-block align-middle" style="max-width:150px;" title="${esc(dayTasks[0].task_performed)}">${esc(dayTasks[0].task_performed)}</span> <span class="text-gray-400 text-xs">· ${taskTotalLabel}</span>`)
                : (day.task_performed ? `<span class="text-gray-700">${esc(day.task_performed)}</span>` : '<span class="text-gray-300">—</span>');
            const taskBreakdownRows = dayTasks.length > 1 ? dayTasks.map(t => `
                    <tr class="border-t border-gray-100">
                        <td class="py-1.5 pr-3 whitespace-nowrap text-gray-600">${fmtHistoryTaskTime(t.start_time)} – ${fmtHistoryTaskTime(t.end_time)}</td>
                        <td class="py-1.5 pr-3 whitespace-nowrap text-purple-600 font-semibold">${historyTaskDuration(t.start_time, t.end_time)}</td>
                        <td class="py-1.5 pr-3 whitespace-nowrap text-gray-600">${t.client ? esc(t.client) : '<span class="text-gray-300">—</span>'}</td>
                        <td class="py-1.5 text-gray-700">${esc(t.task_performed)}</td>
                    </tr>`).join('') : '';

            const breakDisplay = day.break_hours > 0 ? Number(day.break_hours).toFixed(2) : '<span class="text-gray-400">—</span>';

            const historyRowClickAttr = dayTasks.length > 1 ? ` onclick="toggleHistoryTaskRow('${rowId}')" style="cursor:pointer;"` : '';
            rowsHtml += `
                <tr class="border-b border-gray-100 ${rowBg}"${historyRowClickAttr}>
                    <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-700">${fmtDate(day.date)}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-gray-600">${timeIn}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-gray-600">${timeOut}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap text-gray-600" title="Unpaid break deducted from the day">${breakDisplay}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">${historyCell(day.regular_hours, (day.regular_hours || 0).toFixed(2), 'reg')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">${historyCell(day.ot_hours, (day.ot_hours || 0).toFixed(2), 'ot')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap" title="Worked but not approved">${historyCell(day.unapproved_ot_hours, (day.unapproved_ot_hours || 0).toFixed(2), 'unot')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">${historyCell(day.rdot_hours, (day.rdot_hours || 0).toFixed(2), 'rdot')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">${historyCell(Math.round(day.tardy_minutes || 0), Math.round(day.tardy_minutes || 0), 'tardy')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap" title="Approved undertime — excused, no pay impact">${historyCell(day.undertime_hours, (day.undertime_hours || 0).toFixed(2), 'ut')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap" title="Time missed that wasn't approved — counted against you">${historyCell(day.unapproved_undertime_hours, (day.unapproved_undertime_hours || 0).toFixed(2), 'uut')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">${historyCell(day.night_diff, (day.night_diff || 0).toFixed(2), 'nd')}</td>
                    <td class="px-4 py-3 text-sm text-gray-500">${esc(day.remarks) || '<span class="text-gray-300">—</span>'}</td>
                    <td class="px-4 py-3 text-sm">${clientCell}</td>
                    <td class="px-4 py-3 text-sm" title="${esc(taskTooltip)}">${taskCell}</td>
                </tr>
                ${dayTasks.length > 1 ? `
                <tr id="${rowId}" class="hidden bg-purple-50/20 border-b border-gray-100">
                    <td colspan="15" class="px-4 py-2.5">
                        <div class="bg-white border border-purple-100 rounded-lg px-3 py-2" style="margin-left:40px;">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr>
                                        <th class="text-left font-semibold text-gray-400 py-1 pr-3">Time</th>
                                        <th class="text-left font-semibold text-gray-400 py-1 pr-3">Duration</th>
                                        <th class="text-left font-semibold text-gray-400 py-1 pr-3">Client</th>
                                        <th class="text-left font-semibold text-gray-400 py-1">Task</th>
                                    </tr>
                                </thead>
                                <tbody>${taskBreakdownRows}</tbody>
                            </table>
                        </div>
                    </td>
                </tr>` : ''}`;
        });

        const t = Object.assign({
            regular_hours: 0, ot_hours: 0, rdot_hours: 0, tardy_minutes: 0,
            night_diff: 0, unapproved_ot_hours: 0, undertime_hours: 0,
            unapproved_undertime_hours: 0,
        }, totals);

        container.innerHTML = `
            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="min-w-[1330px] w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-[#DCDCDC] text-[#737373] border-y border-[#C5C5C5] h-[48px]">
                            <th class="px-4 text-left font-bold">Date</th>
                            <th class="px-4 text-left font-bold">Time In</th>
                            <th class="px-4 text-left font-bold">Time Out</th>
                            <th class="px-4 text-right font-bold">Break</th>
                            <th class="px-4 text-right font-bold">Regular Hrs</th>
                            <th class="px-4 text-right font-bold">OT Hrs</th>
                            <th class="px-4 text-right font-bold">Unapproved OT</th>
                            <th class="px-4 text-right font-bold">RDOT Hrs</th>
                            <th class="px-4 text-right font-bold">Tardy (min)</th>
                            <th class="px-4 text-right font-bold">Undertime</th>
                            <th class="px-4 text-right font-bold">Unapproved UT</th>
                            <th class="px-4 text-right font-bold">Night Diff</th>
                            <th class="px-4 text-left font-bold">Remarks</th>
                            <th class="px-4 text-left font-bold">Client</th>
                            <th class="px-4 text-left font-bold">Task Performed</th>
                        </tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                    <tfoot>
                        <tr class="bg-[#DCDCDC] text-[#666] border-t border-[#C5C5C5] h-[48px] font-semibold">
                            <td colspan="4" class="px-4 text-center tracking-wide">TOTALS</td>
                            <td class="px-4 text-right">${t.regular_hours.toFixed(2)}</td>
                            <td class="px-4 text-right">${t.ot_hours.toFixed(2)}</td>
                            <td class="px-4 text-right">${t.unapproved_ot_hours.toFixed(2)}</td>
                            <td class="px-4 text-right">${t.rdot_hours.toFixed(2)}</td>
                            <td class="px-4 text-right">${Math.round(t.tardy_minutes)}</td>
                            <td class="px-4 text-right">${t.undertime_hours.toFixed(2)}</td>
                            <td class="px-4 text-right">${t.unapproved_undertime_hours.toFixed(2)}</td>
                            <td class="px-4 text-right">${t.night_diff.toFixed(2)}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>`;
    }

    (function () {
        const tableId = 'timesheetHistoryTable';
        const container = document.querySelector(`[data-crud-table="${tableId}"]`);
        if (!container) return;

        const body = container.querySelector('.crud-body');
        const searchInput = container.querySelector('.crud-search');
        let list = Array.isArray(TIMESHEET_HISTORY) ? TIMESHEET_HISTORY : [];

        function renderEmptyMessage() {
            if (!body) return;

            body.innerHTML = `
                <div class="min-h-full flex items-center justify-center px-6 py-10 text-gray-400">
                    <div class="flex max-w-md flex-col items-center gap-3 rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-8 py-10 text-center shadow-sm">
                        <span class="text-lg font-semibold text-gray-500">No timesheets history found</span>
                        <span class="text-sm leading-6 text-gray-400">There are no previous cutoff records to display.</span>
                    </div>
                </div>
            `;
        }

        function renderRows(items) {
            if (!body) return;

            if (!items.length) {
                renderEmptyMessage();
                return;
            }

            body.innerHTML = items.map(historyRowTemplate).join('');
        }

        function applySearch() {
            const query = (searchInput?.value || '').toLowerCase().trim();

            const filtered = list.filter(item => {
                const searchable = `${formatHistoryDate(item.period_start)} ${formatHistoryDate(item.period_end)} ${item.total_hours ?? ''} ${item.entries_count ?? ''} ${item.days_covered ?? ''}`
                    .toLowerCase();

                return !query || searchable.includes(query);
            });

            renderRows(filtered);
        }

        if (searchInput) {
            searchInput.addEventListener('input', applySearch);
        }

        applySearch();

        // Refresh from the server every time the modal is opened, so new
        // cutoffs / hours show up without reloading the page.
        async function refreshHistory() {
            try {
                const res = await fetch('<?= site_url('client_portal/timesheet/history-list') ?>', {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });
                if (!res.ok) return;
                const data = await res.json();
                if (Array.isArray(data)) {
                    list = data;
                    applySearch();
                }
            } catch (e) {
                console.error('Failed to refresh timesheet history:', e);
            }
        }

        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-modal-open="viewTimesheetHistoryModal"]')) refreshHistory();
        });
    })();
</script>

<style>
    .history-search-wrap {
        position: relative;
    }

    .history-search-input {
        width: 100%;
        background: #e5e7eb;
        color: #374151;
        font-size: 0.875rem;
        padding: 0.75rem 1.25rem 0.75rem 2.5rem;
        border-radius: 0.5rem;
        border: 1px solid #9E9E9E;
        outline: none;
    }

    .search-icon-prefix {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        display: flex;
        align-items: center;
        padding-left: 0.75rem;
        color: #475569;
        pointer-events: none;
    }

    .view-timesheet-modal{
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
</style>