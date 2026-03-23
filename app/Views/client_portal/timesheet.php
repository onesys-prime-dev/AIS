<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="h-full flex flex-col min-h-0 overflow-hidden px-8 pb-8">

    <!-- Page Header -->
    <div class="flex justify-between items-center my-4">
        
        <div class="p-1">
            <h1 class="text-3xl font-bold text-gray-800 mb-1">Timesheets</h1>
            <p class="text-gray-500 text-sm">Manage timesheets here.</p>
        </div>

        <div class="flex items-center gap-4">
            <!-- Month Picker -->
            <button 
                data-modal-open="timesheetsModal"
                class="bg-[#FFFFFF] hover:bg-[#bbbbbbd6] text-white px-3 py-3 rounded-lg shadow flex items-center gap-2">
                    <div id="add-icon" class="w-7 h-7 cursor-pointer">
                        <img src="<?php echo base_url('icons/alarms.png'); ?>" alt="Add"/>
                    </div>
            </button>
            <button 
                data-modal-open="timesheetsModal"
                class="bg-[#FF2D2D] hover:bg-[#ff2d2dd2] text-white px-3 py-3 rounded-lg shadow flex items-center gap-2">
                    <div id="add-icon" class="w-7 h-7 cursor-pointer">
                        <img src="<?php echo base_url('icons/payslip.png'); ?>" alt="Add"/>
                    </div>
                View Payslip
            </button>
        </div>

    </div>

    <?= view('components/crud_table', [
        'title' => 'Timesheet',
        'tableId' => 'timesheetTable',
        'grid' => 'grid-cols-[2fr_1.5fr_1.5fr_1.2fr_1fr_1fr]',
        'columns' => [
            'Date',
            'Time Start',
            'Time End',
            'Department',
            'Task Performed',
            'Action'
        ]
    ]) ?>

</div>

<?= $this->endSection() ?>

<?= $this->section('modals') ?>

    <?php
        $content = view('client_portal/modals/edit_timesheets_form');
        echo view('components/modal', [
            'id' => 'editTimesheetModal',
            'title' => 'Timesheets Management',
            'content' => $content
        ]);
    ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script src="<?= base_url('js/components/flash-notifications.js') ?>"></script>
<script src="<?= base_url('js/components/modal-handler.js') ?>"></script>
<script src="<?= base_url('js/components/crud-actions.js') ?>"></script>
<script src="<?= base_url('js/components/crud-table.js') ?>"></script>

<script>

const TIMESHEETS = <?= json_encode($timesheet) ?>;
const CURRENT_USER_ID = <?= (int) ($currentUserId ?? 0) ?>;
const EDIT_ICON = "<?= base_url('icons/edit.png') ?>";
const DELETE_ICON = "<?= base_url('icons/delete.png') ?>";
const UPDATE_URL = "<?= site_url('client_portal/timesheets/update') ?>";
const DELETE_URL = "<?= site_url('client_portal/timesheets/delete') ?>";

    function toSortableDate(value) {
        const date = new Date(value || '');
        return Number.isNaN(date.getTime()) ? 0 : date.getTime();
    }

    function configureTimesheetFilterUi() {
        const tableContainer = document.querySelector('[data-crud-table="timesheetTable"]');
        if (!tableContainer) return;

        const statusWrap = tableContainer.querySelector('[data-filter="status"]')?.closest('div');
        const employmentWrap = tableContainer.querySelector('[data-filter="employment_type"]')?.closest('div');
        if (statusWrap) statusWrap.classList.add('hidden');
        if (employmentWrap) employmentWrap.classList.add('hidden');

        const sortBy = tableContainer.querySelector('[data-filter="sort_by"]');
        if (sortBy) {
            sortBy.innerHTML = `
                <option value="date">Date</option>
                <option value="start_time">Start Time</option>
                <option value="end_time">End Time</option>
                <option value="department">Department</option>
                <option value="task_performed">Task Performed</option>
            `;
            sortBy.value = 'date';
        }
    }

    function htmlEsc(val) {
        if (val == null) return '';
        return String(val)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function timesheetsRowTemplate(p) {
        return `
            <div class="grid grid-cols-[2fr_1.5fr_1.5fr_1.2fr_1fr_1fr] items-center border-b hover:bg-gray-50">

                <div class="p-4 flex items-center gap-3">
                    ${htmlEsc(p.date) || '---'}
                </div>

                <div class="p-4">${htmlEsc(p.start_time) || '---'}</div>
                <div class="p-4">${htmlEsc(p.end_time) || '---'}</div>
                <div class="p-4">${htmlEsc(p.department) || '---'}</div>
                <div class="p-4">${htmlEsc(p.task_performed) || '---'}</div>

                <div class="p-4 flex justify-center gap-4">

                    <div
                        class="cursor-pointer w-7 h-7 hover:scale-110 transition"
                        data-action="edit"
                        data-modal="editTimesheetModal"
                        data-update-url="${UPDATE_URL}"
                        data-id="${htmlEsc(p.id)}"
                        data-user_id="${htmlEsc(p.user_id)}"
                        data-date="${htmlEsc(p.date)}"
                        data-start_time="${htmlEsc(p.start_time)}"
                        data-end_time="${htmlEsc(p.end_time)}"
                        data-department="${htmlEsc(p.department)}"
                        data-task_performed="${htmlEsc(p.task_performed)}">

                        <img src="${EDIT_ICON}" class="w-7 h-7" alt="Edit">

                    </div>

                    <div
                        class="cursor-pointer w-7 h-7 hover:scale-110 transition"
                        data-action="delete"
                        data-delete-url="${DELETE_URL}"
                        data-id="${htmlEsc(p.id)}">

                        <img src="${DELETE_ICON}" class="w-7 h-7" alt="Delete">

                    </div>

                </div>

            </div>
            `;
        }

    configureTimesheetFilterUi();

    createCrudTable({
        tableId: "timesheetTable",
        data: Array.isArray(TIMESHEETS) ? TIMESHEETS : [],
        renderRow: timesheetsRowTemplate,
        search: (item, q) =>
            `${item.date ?? ''} ${item.start_time ?? ''} ${item.end_time ?? ''} ${item.department ?? ''} ${item.task_performed ?? ''}`
                .toLowerCase()
                .includes(q),
        filters: ({ item, query, department }) => {
            const matchesSearch =
                !query ||
                `${item.date ?? ''} ${item.start_time ?? ''} ${item.end_time ?? ''} ${item.department ?? ''} ${item.task_performed ?? ''}`
                    .toLowerCase()
                    .includes(query);

            const matchesDepartment =
                !department || String(item.department ?? '').toLowerCase() === department;

            return matchesSearch && matchesDepartment;
        },
        sort: ({ a, b, sortBy, sortOrder }) => {
            let left = '';
            let right = '';

            if (sortBy === 'date') {
                left = toSortableDate(a.date);
                right = toSortableDate(b.date);
            } else {
                left = String(a[sortBy] ?? '').toLowerCase();
                right = String(b[sortBy] ?? '').toLowerCase();
            }

            if (left < right) return sortOrder === 'asc' ? -1 : 1;
            if (left > right) return sortOrder === 'asc' ? 1 : -1;
            return 0;
        }
    });
</script>

<?= $this->endSection() ?>