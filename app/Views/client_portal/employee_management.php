<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="p-6">

    <!-- Error/Success Messages -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
            <strong>Error:</strong> <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
            <strong>Success:</strong> <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Employee Management</h1>
            <p class="text-gray-500 text-sm">Manage employee data here.</p>
        </div>

        <button 
            data-modal-open="employeeModal"
            class="bg-[#FF2D2D] hover:bg-[#ff2d2dd2] text-white px-3 py-3 rounded-lg shadow flex items-center gap-2">
                <div id="add-icon" class="w-7 h-7 cursor-pointer">
                    <img src="<?php echo base_url('icons/add.png'); ?>" alt="Add"/>
                </div>
            New Employee
        </button>

    </div>


    <!-- Employee Card -->
    <div class="bg-white rounded-2xl shadow-lg p-6">

        <!-- Header -->
        <div class="flex justify-between items-center mb-5">

            <h2 class="text-lg font-semibold text-gray-700">
                Recent People
            </h2>

            <div class="flex items-center gap-4">
                <!-- Search -->
                <div class="relative w-[350px]">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            class="w-5 h-5">

                            <path fill-rule="evenodd"
                                d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM2.25 10.5a8.25 8.25 0 1 1 14.59 5.28l4.69 4.69a.75.75 0 1 1-1.06 1.06l-4.69-4.69A8.25 8.25 0 0 1 2.25 10.5Z"
                                clip-rule="evenodd" />
                        </svg>
                    </span>
                    <input 
                        id="employeeSearch"
                        type="text"
                        placeholder="Search"
                        class="w-full bg-gray-200 text-gray-700 text-sm pl-10 pr-5 py-3 rounded-lg outline-none placeholder-gray-500"
                    >
                </div>
                <!-- Filter -->
                <button class="flex items-center gap-3 bg-gray-600 hover:bg-gray-700 text-white text-sm px-6 py-3 rounded-lg shadow-md">
                    <span>Filters</span>
                    <div>
                        <img src="<?= base_url('icons/filter.png'); ?>" class="w-4 h-4" alt="Filter" />   
                    </div>
                </button>
            </div>

        </div>
        
        <!-- Table -->
        <div class="overflow-x-auto">

            <table class="w-full text-sm text-left">

                <!-- Table Header -->
                <thead>
                    <tr class="bg-gray-300 text-gray-600">
                        <th class="p-4 rounded-tl-lg rounded-bl-lg">Employee Name</th>
                        <th class="p-4">Position</th>
                        <th class="p-4">Department</th>
                        <th class="p-4">ID</th>
                        <th class="p-4">Type</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-center rounded-tr-lg rounded-br-lg">Action</th>
                    </tr>
                </thead>


                <!-- Table Body — rows rendered by JS -->
                <tbody id="employeeTableBody" class="text-gray-700"></tbody>

            </table>

        </div>

    </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('modals') ?>

    <?php

    $content = view('client_portal/modals/add_employee_form');

    echo view('components/modal', [
        'id' => 'employeeModal',
        'title' => 'Employee Management',
        'content' => $content
    ]);

    ?>

    <?php

    $content = view('client_portal/modals/edit_employee_form');

    echo view('components/modal', [
        'id' => 'editEmployeeModal',
        'title' => 'Employee Management',
        'content' => $content
    ]);

    ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

    <script src="<?= base_url('js/components/modal-handler.js') ?>"></script>
    <script>
        const EMPLOYEES   = <?= json_encode($employees, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const EDIT_ICON   = "<?= base_url('icons/edit.png') ?>";
        const DELETE_ICON = "<?= base_url('icons/delete.png') ?>";
        const UPDATE_URL  = "<?= site_url('client_portal/employees/update') ?>";
        const DELETE_URL  = "<?= site_url('client_portal/employees/delete') ?>";
        const CURRENT_USER_ID = <?= (int) (auth()->user()->id ?? 0) ?>;
        const CSRF_NAME   = "<?= csrf_token() ?>";
        const CSRF_HASH   = "<?= csrf_hash() ?>";

        function htmlEsc(val) {
            if (val == null) return '';
            return String(val)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function renderEmployees(list) {
            const tbody = document.getElementById('employeeTableBody');

            if (!list.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="p-8 text-center text-gray-400">No employees found.</td></tr>';
                return;
            }

            tbody.innerHTML = list.map(e => {
                const statusBadge = e.status == 1
                    ? '<span class="bg-green-200 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">Active</span>'
                    : '<span class="bg-red-200 text-red-600 px-3 py-1 rounded-full text-xs font-semibold">Inactive</span>';

                return `
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gray-300 rounded-full"></div>
                            <span>${htmlEsc(e.first_name)} ${htmlEsc(e.middle_name)} ${htmlEsc(e.last_name)}</span>
                        </div>
                    </td>
                    <td class="p-4">${htmlEsc(e.position) || '---'}</td>
                    <td class="p-4">${htmlEsc(e.department) || '---'}</td>
                    <td class="p-4">${htmlEsc(e.employee_number) || '---'}</td>
                    <td class="p-4">${htmlEsc(e.employment_type) || '---'}</td>
                    <td class="p-4">${statusBadge}</td>
                    <td class="p-4">
                        <div class="flex justify-center">
                            <div class="flex items-center gap-4">
                                <div class="editEmployeeBtn cursor-pointer flex items-center justify-center w-7 h-7 hover:scale-110 transition"
                                    data-id="${htmlEsc(e.id)}"
                                    data-user_id="${htmlEsc(e.user_id)}"
                                    data-first_name="${htmlEsc(e.first_name)}"
                                    data-middle_name="${htmlEsc(e.middle_name)}"
                                    data-last_name="${htmlEsc(e.last_name)}"
                                    data-department="${htmlEsc(e.department)}"
                                    data-position="${htmlEsc(e.position)}"
                                    data-employment_type="${htmlEsc(e.employment_type)}"
                                    data-birthdate="${htmlEsc(e.birthdate)}"
                                    data-status="${htmlEsc(e.status ?? 0)}"
                                    role="button" tabindex="0">
                                    <img src="${EDIT_ICON}" class="w-7 h-7" alt="Edit">
                                </div>
                                <div class="deleteEmployeeBtn cursor-pointer flex items-center justify-center w-7 h-7 hover:scale-110 transition"
                                    data-id="${htmlEsc(e.id)}"
                                    role="button" tabindex="0">
                                    <img src="${DELETE_ICON}" class="w-7 h-7" alt="Delete">
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>`;
            }).join('');
        }

        document.addEventListener('DOMContentLoaded', () => {

            // Initial render — show all employees on page load
            renderEmployees(EMPLOYEES);

            // Search filter
            document.getElementById('employeeSearch').addEventListener('input', function () {
                const q = this.value.toLowerCase().trim();

                if (!q) { renderEmployees(EMPLOYEES); return; }

                const filtered = EMPLOYEES.filter(e => [
                    (e.first_name  ?? '') + ' ' + (e.middle_name ?? '') + ' ' + (e.last_name ?? ''),
                    e.position        ?? '',
                    e.department      ?? '',
                    e.employee_number ?? '',
                    e.employment_type ?? '',
                    e.status == 1 ? 'active' : 'inactive',
                ].join(' ').toLowerCase().includes(q));

                renderEmployees(filtered);
            });

            // Edit & Delete — delegated on tbody so dynamic rows are always wired
            document.getElementById('employeeTableBody').addEventListener('click', e => {

                const editBtn = e.target.closest('.editEmployeeBtn');
                if (editBtn) {
                    const form = document.getElementById('editEmployeeForm');
                    const statusSelect = document.getElementById('edit_status');
                    const inactiveOption = statusSelect.querySelector('option[value="0"]');
                    const selfGuardNote = document.getElementById('selfDeactivateGuardNote');
                    const targetUserId = Number(editBtn.dataset.user_id || 0);
                    const isEditingSelf = CURRENT_USER_ID > 0 && targetUserId === CURRENT_USER_ID;

                    document.getElementById('edit_first_name').value      = editBtn.dataset.first_name      || '';
                    document.getElementById('edit_middle_name').value     = editBtn.dataset.middle_name     || '';
                    document.getElementById('edit_last_name').value       = editBtn.dataset.last_name       || '';
                    document.getElementById('edit_department').value      = editBtn.dataset.department      || '';
                    document.getElementById('edit_position').value        = editBtn.dataset.position        || '';
                    document.getElementById('edit_employment_type').value = editBtn.dataset.employment_type || '';
                    document.getElementById('edit_dob').value             = editBtn.dataset.birthdate       || '';

                    if (inactiveOption) {
                        inactiveOption.disabled = isEditingSelf;
                        inactiveOption.hidden = isEditingSelf;
                    }

                    if (selfGuardNote) {
                        selfGuardNote.classList.toggle('hidden', !isEditingSelf);
                    }

                    if (isEditingSelf) {
                        statusSelect.value = '1';
                    } else {
                        statusSelect.value = editBtn.dataset.status || '1';
                    }

                    form.action = UPDATE_URL + '/' + editBtn.dataset.id;
                    document.getElementById('editEmployeeModal').classList.remove('hidden');
                    return;
                }

                const delBtn = e.target.closest('.deleteEmployeeBtn');
                if (delBtn && confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = DELETE_URL + '/' + delBtn.dataset.id;
                    form.innerHTML = `<input type="hidden" name="${CSRF_NAME}" value="${CSRF_HASH}">`;
                    document.body.appendChild(form);
                    form.submit();
                }

            });

        });
    </script>

<?= $this->endSection() ?>