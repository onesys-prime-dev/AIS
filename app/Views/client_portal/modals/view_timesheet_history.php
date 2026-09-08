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
        'grid' => 'grid-cols-[2fr_1.5fr_1fr]',
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
            <div class="grid grid-cols-[2fr_1.5fr_1fr] items-center border-b hover:bg-gray-50 w-full">
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

        let rowsHtml = '';
        rows.forEach((day, i) => {
            const rowBg = i % 2 === 0 ? '' : 'bg-gray-50/60';
            const timeIn  = day.in  && day.in  !== '-' ? esc(day.in)  : '<span class="text-gray-400">—</span>';
            const timeOut = day.out && day.out !== '-' ? esc(day.out) : '<span class="text-gray-400">—</span>';
            const clientCell = day.client ? `<span class="text-gray-700">${esc(day.client)}</span>` : '<span class="text-gray-300">—</span>';
            const taskCell   = day.task_performed ? `<span class="text-gray-700">${esc(day.task_performed)}</span>` : '<span class="text-gray-300">—</span>';

            rowsHtml += `
                <tr class="border-b border-gray-100 ${rowBg}">
                    <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-700">${fmtDate(day.date)}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-gray-600">${timeIn}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-gray-600">${timeOut}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">${historyCell(day.regular_hours, (day.regular_hours || 0).toFixed(2), 'reg')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">${historyCell(day.ot_hours, (day.ot_hours || 0).toFixed(2), 'ot')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap" title="Worked but not approved">${historyCell(day.unapproved_ot_hours, (day.unapproved_ot_hours || 0).toFixed(2), 'unot')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">${historyCell(day.rdot_hours, (day.rdot_hours || 0).toFixed(2), 'rdot')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">${historyCell(Math.round(day.tardy_minutes || 0), Math.round(day.tardy_minutes || 0), 'tardy')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">${historyCell(day.undertime_hours, (day.undertime_hours || 0).toFixed(2), 'ut')}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">${historyCell(day.night_diff, (day.night_diff || 0).toFixed(2), 'nd')}</td>
                    <td class="px-4 py-3 text-sm text-gray-500">${esc(day.remarks) || '<span class="text-gray-300">—</span>'}</td>
                    <td class="px-4 py-3 text-sm">${clientCell}</td>
                    <td class="px-4 py-3 text-sm">${taskCell}</td>
                </tr>`;
        });

        const t = Object.assign({
            regular_hours: 0, ot_hours: 0, rdot_hours: 0, tardy_minutes: 0,
            night_diff: 0, unapproved_ot_hours: 0, undertime_hours: 0,
        }, totals);

        container.innerHTML = `
            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="min-w-[1240px] w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-[#DCDCDC] text-[#737373] border-y border-[#C5C5C5] h-[48px]">
                            <th class="px-4 text-left font-bold">Date</th>
                            <th class="px-4 text-left font-bold">Time In</th>
                            <th class="px-4 text-left font-bold">Time Out</th>
                            <th class="px-4 text-right font-bold">Regular Hrs</th>
                            <th class="px-4 text-right font-bold">OT Hrs</th>
                            <th class="px-4 text-right font-bold">Unapproved OT</th>
                            <th class="px-4 text-right font-bold">RDOT Hrs</th>
                            <th class="px-4 text-right font-bold">Tardy (min)</th>
                            <th class="px-4 text-right font-bold">Undertime</th>
                            <th class="px-4 text-right font-bold">Night Diff</th>
                            <th class="px-4 text-left font-bold">Remarks</th>
                            <th class="px-4 text-left font-bold">Client</th>
                            <th class="px-4 text-left font-bold">Task Performed</th>
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
                            <td class="px-4 text-right">${Math.round(t.tardy_minutes)}</td>
                            <td class="px-4 text-right">${t.undertime_hours.toFixed(2)}</td>
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
        const list = Array.isArray(TIMESHEET_HISTORY) ? TIMESHEET_HISTORY : [];

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