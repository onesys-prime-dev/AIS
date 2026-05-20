<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
    $timesheet = $timesheet ?? [];
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

    <!-- Card (same as original) -->
    <div class="bg-white rounded-lg shadow flex flex-col">
        <!-- Filter bar (unchanged) -->
        <div class="p-4 border-b flex justify-between items-center gap-4 flex-wrap">
            <div class="relative w-64">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </span>
                <input type="text" id="searchInput" placeholder="Search..." class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div class="flex gap-4">
                <select id="sortBy" class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="date">Sort by Date</option>
                    <option value="regular_hours">Sort by Regular Hours</option>
                    <option value="ot_hours">Sort by OT Hours</option>
                </select>
                <select id="departmentFilter" class="border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">All Departments</option>
                    <option value="Information Technology">IT</option>
                    <option value="Human Resource">HR</option>
                    <option value="Accounting">Accounting</option>
                    <option value="Marketing">Marketing</option>
                </select>
            </div>
        </div>

        <!-- TABLE: Excel‑style with horizontal scroll (matches Payroll Status look) -->
        <div class="overflow-x-auto">
            <table class="min-w-[1100px] w-full text-sm border-collapse">
                <thead class="bg-gray-100">
                    <tr class="border-b">
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">Date</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">IN</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">OUT</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">Regular Hrs</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">OT Hrs</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">RDOT Hrs</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">Tardy (min)</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">Night Diff</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Remarks</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">Action</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <!-- Dynamic rows will be inserted here -->
                </tbody>
                <tfoot class="bg-gray-50 font-semibold border-t">
                    <tr>
                        <td class="px-4 py-3 text-right" colspan="3">TOTAL</td>
                        <td class="px-4 py-3" id="totalRegular">0.00</td>
                        <td class="px-4 py-3" id="totalOt">0.00</td>
                        <td class="px-4 py-3" id="totalRdot">0.00</td>
                        <td class="px-4 py-3" id="totalTardy">0</td>
                        <td class="px-4 py-3" id="totalNightDiff">0.00</td>
                        <td class="px-4 py-3" colspan="2"></td>
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
        echo view('components/view_modal', ['id' => 'viewDailyTimeLog', 'title' => 'Daily Time Log', 'content' => $content]);
    ?>

    <!-- Timesheet History Modal -->
    <?php
        $content = view('client_portal/modals/view_timesheet_history');
        echo view('components/view_modal', ['id' => 'viewTimesheetHistoryModal', 'title' => 'Timesheets History', 'content' => $content]);
    ?>

    <!-- Payslip Modal -->
    <?php
        $content = view('client_portal/modals/view_payslip');
        echo view('components/view_modal', ['id' => 'viewPayslipModal', 'title' => 'Payslip', 'content' => $content]);
    ?>

    <!-- Edit Attendance Modal -->
    <div id="editAttendanceModal" class="modal-overlay" style="display:none;">
        <div class="modal">
            <div class="modal-header">
                <span>Edit Attendance</span>
                <button onclick="closeEditAttendanceModal()">✕</button>
            </div>
            <div class="modal-body">
                <label>Date</label>
                <input type="text" id="edit_att_date" readonly class="bg-gray-100">
                <label>Time In</label>
                <input type="time" id="edit_att_time_in" step="1">
                <label>Time Out</label>
                <input type="time" id="edit_att_time_out" step="1">
                <input type="hidden" id="edit_att_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn" onclick="closeEditAttendanceModal()">Cancel</button>
                <button type="button" class="submit-btn" onclick="saveAttendanceEdit()">Save</button>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/components/flash-notifications.js') ?>"></script>
<script src="<?= base_url('js/components/modal-handler.js') ?>"></script>

<script>
const DAILY_DATA = <?= json_encode($dailyTimesheet) ?>;
const TOTALS = <?= json_encode($totals) ?>;
const UPDATE_ATTENDANCE_URL = "<?= site_url('client_portal/timesheet/update-attendance') ?>";

let currentData = [...DAILY_DATA];

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, m => m === '&' ? '&amp;' : (m === '<' ? '&lt;' : '&gt;'));
}

function formatDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
}

function convertToTimeInput(timeStr) {
    if (timeStr === '-') return '08:00:00';
    let match = timeStr.match(/(\d+):(\d+)\s*(AM|PM)/i);
    if (!match) return timeStr;
    let h = parseInt(match[1]);
    let m = match[2];
    let ampm = match[3].toUpperCase();
    if (ampm === 'PM' && h !== 12) h += 12;
    if (ampm === 'AM' && h === 12) h = 0;
    return `${h.toString().padStart(2,'0')}:${m}:00`;
}

function renderTable() {
    const tbody = document.getElementById('tableBody');
    if (!tbody) return;
    if (currentData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="p-8 text-center text-gray-500">No attendance records for this period.</td></tr>';
        return;
    }
    let html = '';
    currentData.forEach(day => {
        html += `
            <tr class="border-b hover:bg-gray-50">
                <td class="px-4 py-3 whitespace-nowrap">${formatDate(day.date)}</td>
                <td class="px-4 py-3 editable-time cursor-pointer whitespace-nowrap" data-id="${day.attendance_id}" data-field="time_in" title="Double-click to edit">${escapeHtml(day.in)}</td>
                <td class="px-4 py-3 editable-time cursor-pointer whitespace-nowrap" data-id="${day.attendance_id}" data-field="time_out" title="Double-click to edit">${escapeHtml(day.out)}</td>
                <td class="px-4 py-3 whitespace-nowrap">${day.regular_hours.toFixed(2)}</td>
                <td class="px-4 py-3 whitespace-nowrap">${day.ot_hours.toFixed(2)}</td>
                <td class="px-4 py-3 whitespace-nowrap">${day.rdot_hours.toFixed(2)}</td>
                <td class="px-4 py-3 whitespace-nowrap">${Math.round(day.tardy_minutes)}</td>
                <td class="px-4 py-3 whitespace-nowrap">${day.night_diff.toFixed(2)}</td>
                <td class="px-4 py-3 max-w-xs truncate" title="${escapeHtml(day.remarks)}">${escapeHtml(day.remarks)}</td>
                <td class="px-4 py-3 text-center whitespace-nowrap">
                    <div class="cursor-pointer w-7 h-7 hover:scale-110 transition inline-block"
                         onclick="openEditAttendanceModal(${day.attendance_id}, '${day.date}', '${escapeHtml(day.in)}', '${escapeHtml(day.out)}')">
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

    attachDoubleClickEdit();
}

function attachDoubleClickEdit() {
    document.querySelectorAll('.editable-time').forEach(cell => {
        cell.removeEventListener('dblclick', handleDoubleClick);
        cell.addEventListener('dblclick', handleDoubleClick);
    });
}

function handleDoubleClick(e) {
    const cell = e.currentTarget;
    const currentValue = cell.innerText.trim();
    const attendanceId = cell.getAttribute('data-id');
    const field = cell.getAttribute('data-field');
    if (!attendanceId) return;

    const input = document.createElement('input');
    input.type = 'time';
    input.step = '1';
    input.value = currentValue !== '-' ? convertToTimeInput(currentValue) : '08:00:00';
    input.className = 'border rounded px-2 py-1 text-sm w-28';
    cell.innerText = '';
    cell.appendChild(input);
    input.focus();

    const save = () => {
        let newValue = input.value;
        newValue = newValue.includes(':') ? newValue + ':00' : newValue;
        fetch(UPDATE_ATTENDANCE_URL + '/' + attendanceId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ [field]: newValue })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) location.reload();
            else alert('Update failed: ' + (data.message || 'Unknown error'));
        })
        .catch(err => {
            console.error(err);
            alert('Network error');
        });
        cell.innerText = currentValue;
    };

    input.addEventListener('blur', save);
    input.addEventListener('keypress', (e) => { if (e.key === 'Enter') input.blur(); });
}

function openEditAttendanceModal(id, date, timeIn, timeOut) {
    document.getElementById('edit_att_id').value = id;
    document.getElementById('edit_att_date').value = date;
    document.getElementById('edit_att_time_in').value = timeIn !== '-' ? convertToTimeInput(timeIn) : '08:00:00';
    document.getElementById('edit_att_time_out').value = timeOut !== '-' ? convertToTimeInput(timeOut) : '17:00:00';
    document.getElementById('editAttendanceModal').style.display = 'flex';
}

function closeEditAttendanceModal() {
    document.getElementById('editAttendanceModal').style.display = 'none';
}

function saveAttendanceEdit() {
    const id = document.getElementById('edit_att_id').value;
    let timeIn = document.getElementById('edit_att_time_in').value;
    let timeOut = document.getElementById('edit_att_time_out').value;
    if (!timeIn || !timeOut) {
        alert('Please fill both Time In and Time Out');
        return;
    }
    timeIn = timeIn.includes(':') ? timeIn + ':00' : timeIn;
    timeOut = timeOut.includes(':') ? timeOut + ':00' : timeOut;

    fetch(UPDATE_ATTENDANCE_URL + '/' + id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({ time_in: timeIn, time_out: timeOut })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) location.reload();
        else alert('Update failed: ' + (data.message || 'Unknown error'));
    })
    .catch(err => {
        console.error(err);
        alert('Network error');
    });
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