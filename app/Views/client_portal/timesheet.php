<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
    $timesheet = $timesheet ?? [];
    $dailyTimesheet = $dailyTimesheet ?? [];
    $totals = $totals ?? [
        'regular_hours' => 0,
        'ot_hours' => 0,
        'rdot_hours' => 0,
        'tardy_minutes' => 0,
        'night_diff' => 0,
    ];
?>

<div class="h-full flex flex-col min-h-0 overflow-hidden px-8 pb-8">

    <!-- Page Header (unchanged) -->
    <div class="flex justify-between items-center my-4">
        <div class="p-1">
            <h1 class="text-3xl font-bold text-gray-800 mb-1">Timesheets</h1>
            <p class="text-gray-500 text-sm">Manage timesheets here.</p>
        </div>
        <div class="flex items-center gap-4">
            <button type="button"
                data-modal-open="viewDailyTimeLog"
                onclick="openModalById('viewDailyTimeLog')"
                class="bg-[#FFFFFF] hover:bg-[#f3f4f6] px-3 py-3 rounded-lg shadow hover:shadow-[0_6px_12px_rgba(0,0,0,0.3)] flex items-center gap-2 transition hover:translate-y-[-2px]">
                <img src="<?= base_url('icons/activity.png'); ?>" class="w-7 h-7" alt="Activity Log">
            </button>

            <button type="button"
                data-modal-open="viewTimesheetHistoryModal"
                onclick="openModalById('viewTimesheetHistoryModal')"
                class="bg-[#FFFFFF] hover:bg-[#f3f4f6] px-3 py-3 rounded-lg shadow hover:shadow-[0_6px_12px_rgba(0,0,0,0.2)] flex items-center gap-2 transition hover:translate-y-[-2px]">
                <img src="<?= base_url('icons/alarms.png'); ?>" class="w-7 h-7" alt="History">
            </button>

            <button type="button"
                data-modal-open="viewPayslipModal"
                onclick="openModalById('viewPayslipModal')"
                class="bg-[#FF2D2D] hover:bg-[#dc2626] text-white px-3 py-3 rounded-lg shadow hover:shadow-[0_6px_12px_rgba(239,68,68,0.3)] flex items-center gap-2 transition hover:translate-y-[-2px]">
                <img src="<?= base_url('icons/payslip.png'); ?>" class="w-7 h-7" alt="Payslip">
                View Payslip
            </button>
        </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white h-full rounded-2xl shadow-lg border border-gray-400 flex flex-col overflow-hidden">

        <!-- Filter bar -->
        <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center gap-4 flex-wrap">
            <div class="relative w-72">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM2.25 10.5a8.25 8.25 0 1 1 14.59 5.28l4.69 4.69a.75.75 0 1 1-1.06 1.06l-4.69-4.69A8.25 8.25 0 0 1 2.25 10.5Z" clip-rule="evenodd"/>
                    </svg>
                </span>
                <input type="text" id="searchInput" placeholder="Search date, remarks…"
                    class="w-full bg-gray-100 text-gray-700 text-sm pl-10 pr-4 py-2.5 rounded-lg outline-none focus:ring-2 focus:ring-purple-400 border border-transparent focus:border-purple-300">
            </div>
            <div class="flex gap-3">
                <select id="sortBy" class="bg-gray-100 text-gray-700 text-sm border border-gray-200 rounded-lg px-3 py-2.5 outline-none focus:ring-2 focus:ring-purple-400">
                    <option value="date">Sort: Date</option>
                    <option value="regular_hours">Sort: Regular Hrs</option>
                    <option value="ot_hours">Sort: OT Hrs</option>
                </select>
                <select id="departmentFilter" class="bg-gray-100 text-gray-700 text-sm border border-gray-200 rounded-lg px-3 py-2.5 outline-none focus:ring-2 focus:ring-purple-400">
                    <option value="">All Departments</option>
                    <option value="Information Technology">IT</option>
                    <option value="Human Resource">HR</option>
                    <option value="Accounting">Accounting</option>
                    <option value="Marketing">Marketing</option>
                </select>
            </div>
        </div>

        <!-- TABLE -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- HEADER -->
            <table class="min-w-[1280px] w-full text-md border-collapse table-fixed">
                <colgroup>
                    <col class="w-[120px]">
                    <col class="w-[100px]">
                    <col class="w-[100px]">
                    <col class="w-[110px]">
                    <col class="w-[90px]">
                    <col class="w-[100px]">
                    <col class="w-[110px]">
                    <col class="w-[110px]">
                    <col class="w-[150px]">
                    <col class="w-[130px]">
                    <col class="w-[170px]">
                    <col class="w-[90px]">
                </colgroup>

                <thead>
                    <tr class="bg-[#DCDCDC] text-[#737373] border-y border-[#C5C5C5] h-[52px]">

                        <th class="px-4 text-left font-bold">Date</th>
                        <th class="px-4 text-left font-bold">Time In</th>
                        <th class="px-4 text-left font-bold">Time Out</th>

                        <th class="px-4 text-right font-bold">Regular Hrs</th>
                        <th class="px-4 text-right font-bold">OT Hrs</th>
                        <th class="px-4 text-right font-bold">RDOT Hrs</th>
                        <th class="px-4 text-right font-bold">Tardy (min)</th>
                        <th class="px-4 text-right font-bold">Night Diff</th>

                        <th class="px-4 text-left font-bold">Remarks</th>

                        <th class="px-4 text-left font-bold">
                            Client
                            <span class="ml-1 text-[10px] text-purple-500">✎</span>
                        </th>

                        <th class="px-4 text-left font-bold">
                            Task Performed
                            <span class="ml-1 text-[10px] text-purple-500">✎</span>
                        </th>

                        <th class="px-4 text-center font-bold">Action</th>

                    </tr>
                </thead>
            </table>

            <!-- BODY -->
            <div class="overflow-y-auto hide-scroll-bar flex-1">

                <table class="min-w-[1280px] w-full text-sm border-collapse table-fixed">

                    <colgroup>
                        <col class="w-[120px]">
                        <col class="w-[100px]">
                        <col class="w-[100px]">
                        <col class="w-[110px]">
                        <col class="w-[90px]">
                        <col class="w-[100px]">
                        <col class="w-[110px]">
                        <col class="w-[110px]">
                        <col class="w-[150px]">
                        <col class="w-[130px]">
                        <col class="w-[170px]">
                        <col class="w-[90px]">
                    </colgroup>

                    <tbody id="tableBody">
                        <!-- DATA ROWS -->
                    </tbody>

                </table>

            </div>

            <!-- FOOTER -->
            <table class="min-w-[1280px] w-full text-sm border-collapse table-fixed">

                <colgroup>
                    <col class="w-[120px]">
                    <col class="w-[100px]">
                    <col class="w-[100px]">
                    <col class="w-[110px]">
                    <col class="w-[90px]">
                    <col class="w-[100px]">
                    <col class="w-[110px]">
                    <col class="w-[110px]">
                    <col class="w-[150px]">
                    <col class="w-[130px]">
                    <col class="w-[170px]">
                    <col class="w-[90px]">
                </colgroup>

                <tfoot>

                    <tr class="bg-[#DCDCDC] text-[#666] border-t border-[#C5C5C5] h-[52px] font-semibold">

                        <!-- DATE -->
                        <td></td>

                        <!-- TIME IN -->
                        <td></td>

                        <!-- TIME OUT -->
                        <td class="text-center border-r border-[#C5C5C5] tracking-wide">
                            TOTALS
                        </td>

                        <!-- REGULAR -->
                        <td class="px-4 text-right">
                            <span id="totalRegular">0.00</span>
                        </td>

                        <!-- OT -->
                        <td class="px-4 text-right">
                            <span id="totalOt">0.00</span>
                        </td>

                        <!-- RDOT -->
                        <td class="px-4 text-right">
                            <span id="totalRdot">0.00</span>
                        </td>

                        <!-- TARDY -->
                        <td class="px-4 text-right">
                            <span id="totalTardy">0</span>
                        </td>

                        <!-- NIGHT DIFF -->
                        <td class="px-4 text-right">
                            <span id="totalNightDiff">0.00</span>
                        </td>

                        <!-- REMARKS -->
                        <td></td>

                        <!-- CLIENT -->
                        <td></td>

                        <!-- TASK -->
                        <td></td>

                        <!-- ACTION -->
                        <td></td>

                    </tr>

                </tfoot>

            </table>

        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('modals') ?>
    <!-- Daily Time Log Modal -->
    <?php
        $content = view('client_portal/modals/view_daily_time_log', ['dailyTimeLog' => $dailyTimeLog ?? []]);
        echo view('components/view_modal', [
            'id'    => 'viewDailyTimeLog',
            'title' => 'Daily Time Log',
            'content' => $content
        ]);
    ?>

    <!-- Timesheet History Modal -->
    <?php
        $content = view('client_portal/modals/view_timesheet_history', ['timesheetHistory' => $timesheetHistory ?? []]);
        echo view('components/view_modal', [
            'id'    => 'viewTimesheetHistoryModal',
            'title' => 'Timesheets History',
            'content' => $content
        ]);
    ?>

    <!-- Payslip Modal -->
    <?php
        $content = view('client_portal/modals/view_payslip');
        echo view('components/view_modal', [
            'id'      => 'viewPayslipModal',
            'content' => $content
        ]);
    ?>

    <!-- View Timesheet Details Modal -->
    <?php
        $content = '<div id="historyDetailsContainer"></div>';
        echo view('components/view_modal', [
            'id' => 'viewHistoryDetailsModal',
            'title' => 'Timesheet Details',
            'content' => $content,
        ]);
    ?>

    <!-- Task Log Modal -->
    <div id="taskLogModal"
        class="fixed inset-0 z-[9999] hidden items-center justify-center">

        <!-- DARK BLURRED BACKDROP -->
        <div class="absolute inset-0" style="
        background: rgba(0, 0, 0, 0.78);
        backdrop-filter: blur(7px);
        -webkit-backdrop-filter: blur(7px);
     "></div>

        <!-- MODAL -->
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden animate-[fadeIn_.2s_ease-out]">

            <!-- HEADER -->
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">
                        Edit Task Log
                    </h3>

                    <p id="task_att_date"
                    class="text-xs text-gray-400 mt-0.5">
                    </p>
                </div>

                <button onclick="closeTaskLogModal()"
                        class="text-gray-400 hover:text-gray-600 transition text-2xl leading-none">
                    &times;
                </button>
            </div>

            <!-- BODY -->
            <div class="px-6 py-5 space-y-4">

                <!-- CLIENT -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">
                        Client
                    </label>

                    <input
                        type="text"
                        id="task_att_client"
                        placeholder="e.g. Acme Corp"
                        class="w-full bg-gray-50 rounded-lg px-3 py-2.5 text-sm text-gray-700 border border-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 transition">
                </div>

                <!-- TASK -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">
                        Task Performed
                    </label>

                    <textarea
                        id="task_att_text"
                        rows="4"
                        placeholder="Describe the tasks you performed…"
                        class="w-full bg-gray-50 rounded-lg px-3 py-2.5 text-sm text-gray-700 border border-gray-200 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400 resize-none transition"></textarea>
                </div>

                <input type="hidden" id="task_att_id">
            </div>

            <!-- FOOTER -->
            <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">

                <button
                    type="button"
                    onclick="closeTaskLogModal()"
                    class="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-100 transition">
                    Cancel
                </button>

                <button
                    type="button"
                    onclick="saveTaskLog()"
                    class="px-5 py-2 text-sm rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-semibold shadow-md hover:shadow-lg transition">
                    Save
                </button>

            </div>

        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/components/flash-notifications.js') ?>"></script>
<script src="<?= base_url('js/components/modal-handler.js') ?>?v=2"></script>
<script src="<?= base_url('js/components/crud-table.js') ?>"></script>

<script>
const DAILY_DATA = <?= json_encode($dailyTimesheet) ?>;
const TOTALS = <?= json_encode($totals) ?>;
const UPDATE_TASK_LOG_URL = "<?= site_url('client_portal/timesheet/update-task-log') ?>";

let currentData = [...DAILY_DATA];

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, m => m === '&' ? '&amp;' : (m === '<' ? '&lt;' : '&gt;'));
}

function formatDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
}


function badge(value, formatted, type) {
    if (!value || value === 0) return `<span class="text-gray-400">—</span>`;
    const styles = {
        ot:    'bg-green-100 text-green-700',
        rdot:  'bg-blue-100 text-blue-700',
        tardy: 'bg-yellow-100 text-yellow-700',
        nd:    'bg-purple-100 text-purple-700',
        reg:   'text-gray-800 font-medium',
    };
    const cls = styles[type] || 'text-gray-800';
    const pill = ['ot','rdot','tardy','nd'].includes(type);
    return pill
        ? `<span class="inline-block ${cls} px-2 py-0.5 rounded-full text-xs font-semibold">${formatted}</span>`
        : `<span class="${cls}">${formatted}</span>`;
}

function renderTable() {
    const tbody = document.getElementById('tableBody');
    if (!tbody) return;
    if (currentData.length === 0) {
        tbody.innerHTML = `
            <tr><td colspan="12" class="px-6 py-12 text-center">
                <div class="flex flex-col items-center gap-2 text-gray-400">
                    <svg class="w-10 h-10 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span class="text-base font-semibold text-gray-500">No attendance records</span>
                    <span class="text-sm">No records found for this period</span>
                </div>
            </td></tr>`;
        return;
    }
    let html = '';
    currentData.forEach((day, i) => {
        const rowBg = i % 2 === 0 ? '' : 'bg-gray-50/60';
        const timeInDisplay  = day.in  && day.in  !== '-' ? escapeHtml(day.in)  : '<span class="text-gray-400">—</span>';
        const timeOutDisplay = day.out && day.out !== '-' ? escapeHtml(day.out) : '<span class="text-gray-400">—</span>';
        const safeTask   = (day.task_performed || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
        const safeClient = (day.client || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
        const clientCell = day.client
            ? `<span class="text-gray-700">${escapeHtml(day.client)}</span>`
            : `<span class="text-gray-300">—</span>`;
        const taskCell = day.task_performed
            ? `<span class="text-gray-700">${escapeHtml(day.task_performed)}</span>`
            : `<span class="text-gray-300">—</span>`;
        html += `
            <tr class="border-b border-gray-100 hover:bg-purple-50/40 transition-colors ${rowBg}">
                <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-700">${formatDate(day.date)}</td>
                <td class="px-4 py-3 whitespace-nowrap text-gray-600">${timeInDisplay}</td>
                <td class="px-4 py-3 whitespace-nowrap text-gray-600">${timeOutDisplay}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">${badge(day.regular_hours, day.regular_hours.toFixed(2), 'reg')}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">${badge(day.ot_hours, day.ot_hours.toFixed(2), 'ot')}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">${badge(day.rdot_hours, day.rdot_hours.toFixed(2), 'rdot')}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">${badge(Math.round(day.tardy_minutes), Math.round(day.tardy_minutes), 'tardy')}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">${badge(day.night_diff, day.night_diff.toFixed(2), 'nd')}</td>
                <td class="px-4 py-3 text-sm text-gray-500" title="${escapeHtml(day.remarks)}">${escapeHtml(day.remarks) || '<span class="text-gray-300">—</span>'}</td>
                <td class="px-4 py-3 text-sm" title="${escapeHtml(day.client)}">${clientCell}</td>
                <td class="px-4 py-3 text-sm" title="${escapeHtml(day.task_performed)}">${taskCell}</td>
                <td class="px-4 py-3 text-center whitespace-nowrap">
                    <div class="cursor-pointer w-7 h-7 hover:scale-110 transition inline-block"
                         onclick="openTaskLogModal(${day.attendance_id}, '${day.date}', '${safeTask}', '${safeClient}')">
                        <img src="<?= base_url('icons/edit.png') ?>" class="w-7 h-7" alt="Edit">
                    </div>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;

    document.getElementById('totalRegular').innerText = TOTALS.regular_hours.toFixed(2);
    document.getElementById('totalOt').innerText = TOTALS.ot_hours.toFixed(2);
    document.getElementById('totalRdot').innerText = TOTALS.rdot_hours.toFixed(2);
    document.getElementById('totalTardy').innerText = Math.round(TOTALS.tardy_minutes);
    document.getElementById('totalNightDiff').innerText = TOTALS.night_diff.toFixed(2);
}

function openTaskLogModal(attendanceId, date, currentTask, currentClient) {

    document.getElementById('task_att_id').value = attendanceId;

    document.getElementById('task_att_date').textContent = date;

    document.getElementById('task_att_text').value =
        currentTask || '';

    document.getElementById('task_att_client').value =
        currentClient || '';

    const modal = document.getElementById('taskLogModal');

    modal.classList.remove('hidden');

    modal.classList.add('flex');

    document.body.classList.add('overflow-hidden');
}

function closeTaskLogModal() {

    const modal = document.getElementById('taskLogModal');

    modal.classList.remove('flex');

    modal.classList.add('hidden');

    document.body.classList.remove('overflow-hidden');
}

function saveTaskLog() {
    const id     = document.getElementById('task_att_id').value;
    const text   = document.getElementById('task_att_text').value.trim();
    const client = document.getElementById('task_att_client').value.trim();
    fetch(UPDATE_TASK_LOG_URL + '/' + id, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_performed: text, client: client })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) location.reload();
        else alert('Update failed: ' + (data.message || 'Unknown error'));
    })
    .catch(err => { console.error(err); alert('Network error'); });
}

function filterAndSort() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const sortBy = document.getElementById('sortBy').value;
    const department = document.getElementById('departmentFilter').value;

    let filtered = DAILY_DATA.filter(day => {
        const matchesSearch = !searchTerm || 
            day.date.includes(searchTerm) ||
            (day.remarks && day.remarks.toLowerCase().includes(searchTerm)) ||
            day.in.toLowerCase().includes(searchTerm) ||
            day.out.toLowerCase().includes(searchTerm);
        const matchesDept = !department || (day.department && day.department === department);
        return matchesSearch && matchesDept;
    });

    filtered.sort((a, b) => {
        if (sortBy === 'date') return new Date(b.date) - new Date(a.date);
        if (sortBy === 'regular_hours') return b.regular_hours - a.regular_hours;
        if (sortBy === 'ot_hours') return b.ot_hours - a.ot_hours;
        return 0;
    });

    currentData = filtered;
    renderTable();
}

document.getElementById('searchInput')?.addEventListener('input', filterAndSort);
document.getElementById('sortBy')?.addEventListener('change', filterAndSort);
document.getElementById('departmentFilter')?.addEventListener('change', filterAndSort);

renderTable();
</script>
<?= $this->endSection() ?>