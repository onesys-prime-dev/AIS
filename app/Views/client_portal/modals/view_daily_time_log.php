<div class="view-dailytime-modal" data-modal="viewDailyTimeLog" >
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
        'title' => 'Daily Time Log',
        'tableId' => 'dailyTimeLogTable',
        'grid' => 'grid-cols-[1fr_1fr_1fr_1fr_1fr_1fr_0.8fr]',
        'columns' => [
            'Date',
            'Time In',
            'Break',
            'Time Out',
            'Total Hours',
            'Status',
            'Action'
        ]
    ]) ?>

    <div id="dailyTimeLogDetailModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 z-[10000]">
        <div class="w-full max-w-2xl h-[400px] rounded-xl bg-white p-6 shadow-xl relative">
            <button type="button" id="dailyTimeLogDetailClose" class="absolute right-4 top-4 text-gray-500 hover:text-black text-xl leading-none">
                ✕
            </button>

            <h3 class="text-xl font-bold text-gray-800 mb-5">Daily Time Log Details</h3>

            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Selected Log</p>
                    <p id="dailyTimeLogDetailDate" class="mt-1 text-sm font-semibold text-gray-800">---</p>
                </div>

                <span
                    id="dailyTimeLogDetailStatusBadge"
                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-gray-100 text-gray-700">
                    ---
                </span>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Time In</p>
                    <p id="dailyTimeLogDetailTimeIn" class="mt-1 text-sm font-semibold text-gray-800">---</p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Time Out</p>
                    <p id="dailyTimeLogDetailTimeOut" class="mt-1 text-sm font-semibold text-gray-800">---</p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Break Start</p>
                    <p id="dailyTimeLogDetailBreakStart" class="mt-1 text-sm font-semibold text-gray-800">---</p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Break End</p>
                    <p id="dailyTimeLogDetailBreakEnd" class="mt-1 text-sm font-semibold text-gray-800">---</p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Break Duration</p>
                    <p id="dailyTimeLogDetailBreakDuration" class="mt-1 text-sm font-semibold text-gray-800">---</p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total Hours</p>
                    <p id="dailyTimeLogDetailTotalHours" class="mt-1 text-sm font-semibold text-gray-800">---</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const DAILY_TIME_LOG = <?= json_encode($dailyTimeLog ?? []) ?>;
    const DAILY_TIME_LOG_URL = "<?= site_url('client_portal/timesheets/daily-log') ?>";

    function formatTimeValue(value) {
        if (!value) return '---';

        const date = new Date(`1970-01-01T${value}`);
        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        return date.toLocaleTimeString([], {
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function getDailyLogStatus(item) {
        if (!item.time_in && !item.time_out) return 'Incomplete';
        if (item.time_in && !item.time_out) return 'Ongoing';
        if (item.time_in && item.time_out) return 'Complete';
        return 'Incomplete';
    }

    function formatBreakValue(item) {
        const start = item.break_start ? formatTimeValue(item.break_start) : '';
        const end = item.break_end ? formatTimeValue(item.break_end) : '';

        if (start && end) return `${start} - ${end}`;
        if (start) return `Started at ${start}`;
        if (item.break_duration) return `${Number(item.break_duration).toFixed(2)} hrs`;

        return 'No break';
    }

    function dailyTimeLogRowTemplate(item) {
        const status = getDailyLogStatus(item);
        const statusStyles = {
            Complete: 'bg-green-100 text-green-700',
            Ongoing: 'bg-yellow-100 text-yellow-700',
            Incomplete: 'bg-red-100 text-red-700',
        };

        return `
            <div class="grid grid-cols-[1fr_1fr_1fr_1fr_1fr_1fr_0.8fr] items-center border-b hover:bg-gray-50">
                <div class="p-4">${item.date || '---'}</div>
                <div class="p-4">${formatTimeValue(item.time_in)}</div>
                <div class="p-4">${formatBreakValue(item)}</div>
                <div class="p-4">${formatTimeValue(item.time_out)}</div>
                <div class="p-4">${item.total_hours ? `${Number(item.total_hours).toFixed(2)} hrs` : '---'}</div>
                <div class="p-4">
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${statusStyles[status] || 'bg-gray-100 text-gray-700'}">
                        ${status}
                    </span>
                </div>
                <div class="p-4 text-center">
                    <button
                        type="button"
                        class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200"
                        data-action="view-daily-time-log"
                        data-date="${item.date || ''}"
                        data-time_in="${item.time_in || ''}"
                        data-break_start="${item.break_start || ''}"
                        data-break_end="${item.break_end || ''}"
                        data-break_duration="${item.break_duration || ''}"
                        data-time_out="${item.time_out || ''}"
                        data-total_hours="${item.total_hours || ''}"
                        data-status="${status}">
                        View
                    </button>
                </div>
            </div>
        `;
    }

    (async () => {
        let dailyTimeLog = Array.isArray(DAILY_TIME_LOG) ? DAILY_TIME_LOG : [];

        try {
            const response = await fetch(DAILY_TIME_LOG_URL, {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });

            if (response.ok) {
                const data = await response.json();
                dailyTimeLog = Array.isArray(data) ? data : dailyTimeLog;
            }
        } catch (error) {
            console.error('Failed to load daily time log:', error);
        }

        createCrudTable({
            tableId: 'dailyTimeLogTable',
            data: dailyTimeLog,
            renderRow: dailyTimeLogRowTemplate,
            search: (item, q) =>
                `${item.date ?? ''} ${item.time_in ?? ''} ${item.break_start ?? ''} ${item.break_end ?? ''} ${item.time_out ?? ''} ${getDailyLogStatus(item)}`
                    .toLowerCase()
                    .includes(q)
        });
    })();

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-action="view-daily-time-log"]');
        if (!trigger) return;

        const detailModal = document.getElementById('dailyTimeLogDetailModal');
        if (!detailModal) return;

        const setField = (id, value) => {
            const element = document.getElementById(id);
            if (element) element.textContent = value || '---';
        };

        const setStatusBadge = (value) => {
            const element = document.getElementById('dailyTimeLogDetailStatusBadge');
            if (!element) return;

            const statusStyles = {
                Complete: 'bg-green-100 text-green-700',
                Ongoing: 'bg-yellow-100 text-yellow-700',
                Incomplete: 'bg-red-100 text-red-700',
            };

            element.className = `inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${statusStyles[value] || 'bg-gray-100 text-gray-700'}`;
            element.textContent = value || '---';
        };

        setField('dailyTimeLogDetailDate', trigger.dataset.date);
        setStatusBadge(trigger.dataset.status);
        setField('dailyTimeLogDetailTimeIn', formatTimeValue(trigger.dataset.time_in));
        setField('dailyTimeLogDetailTimeOut', formatTimeValue(trigger.dataset.time_out));
        setField('dailyTimeLogDetailBreakStart', formatTimeValue(trigger.dataset.break_start));
        setField('dailyTimeLogDetailBreakEnd', formatTimeValue(trigger.dataset.break_end));
        setField(
            'dailyTimeLogDetailBreakDuration',
            trigger.dataset.break_duration ? `${Number(trigger.dataset.break_duration).toFixed(2)} hrs` : 'No break'
        );
        setField(
            'dailyTimeLogDetailTotalHours',
            trigger.dataset.total_hours ? `${Number(trigger.dataset.total_hours).toFixed(2)} hrs` : '---'
        );

        detailModal.classList.remove('hidden');
        detailModal.classList.add('flex');
    });

    document.addEventListener('click', function (event) {
        if (event.target && event.target.id === 'dailyTimeLogDetailModal') {
            event.target.classList.add('hidden');
            event.target.classList.remove('flex');
        }

        if (event.target && event.target.id === 'dailyTimeLogDetailClose') {
            const detailModal = document.getElementById('dailyTimeLogDetailModal');
            if (detailModal) {
                detailModal.classList.add('hidden');
                detailModal.classList.remove('flex');
            }
        }
    });
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

    .view-dailytime-modal{
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
</style>