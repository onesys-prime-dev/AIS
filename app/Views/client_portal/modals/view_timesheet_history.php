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
            console.log(data);
            openModalById('viewHistoryDetailsModal');
            renderHistoryDetails(data.daily || []);
        });
    }

    function renderHistoryDetails(rows)
    {
        const container =
            document.getElementById(
                'historyDetailsContainer'
            );

        if (!container) return;

        if (!rows.length) {
            container.innerHTML =
                '<p>No records found.</p>';

            return;
        }

        let html = `
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">Date</th>
                        <th class="p-3 text-left">In</th>
                        <th class="p-3 text-left">Out</th>
                        <th class="p-3 text-left">Regular</th>
                        <th class="p-3 text-left">OT</th>
                    </tr>
                </thead>
                <tbody>
        `;

        rows.forEach(row => {
            html += `
                <tr>
                   <td class="p-3 border-b">${row.date}</td>
                   <td class="p-3 border-b">${row.in}</td>
                   <td class="p-3 border-b">${row.out}</td>
                   <td class="p-3 border-b">${row.regular_hours}</td>
                   <td class="p-3 border-b">${row.ot_hours}</td>
                </tr>
            `;
        });

        html += `
                </tbody>
            </table>
        `;

        container.innerHTML = html;
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