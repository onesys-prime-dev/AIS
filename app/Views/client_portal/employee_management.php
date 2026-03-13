<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="p-5 h-full overflow-y-auto">

    <!-- Flash Toast Notifications -->
    <?php if (session()->getFlashdata('success')): ?>
    <div id="toast-success"
        class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 
        z-[9999] flex items-center gap-3 
        bg-green-100 border border-green-400 text-green-700 
        px-6 py-4 rounded-lg shadow-xl
        opacity-0 scale-95 transition-all duration-300">

        <i class="fa-solid fa-circle-check text-lg"></i>
        <span class="font-medium"><?= session()->getFlashdata('success') ?></span>

    </div>
    <?php endif; ?>


    <?php if (session()->getFlashdata('error')): ?>
    <div id="toast-error"
        class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 
        z-[9999] flex items-center gap-3 
        bg-red-100 border border-red-400 text-red-700 
        px-6 py-4 rounded-lg shadow-xl
        opacity-0 scale-95 transition-all duration-300">

        <i class="fa-solid fa-circle-exclamation text-lg"></i>
        <span class="font-medium"><?= session()->getFlashdata('error') ?></span>

    </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        
        <div class="p-1">
            <h1 class="text-3xl font-bold text-gray-800 mb-1">Employee Management</h1>
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
    <div class="bg-white rounded-2xl shadow-lg p-5 h-[85%] border border-gray-400">

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
                <div class="relative">
                    <button
                        id="filterToggle"
                        type="button"
                        aria-expanded="false"
                        aria-controls="employeeFilterDropdown"
                        class="flex items-center gap-3 bg-gray-600 hover:bg-gray-700 text-white text-sm px-6 py-3 rounded-lg shadow-md">
                        <span>Filters</span>
                        <div>
                            <img src="<?= base_url('icons/filter.png'); ?>" class="w-4 h-4" alt="Filter" />
                        </div>
                    </button>

                    <div
                        id="employeeFilterDropdown"
                        class="hidden absolute right-0 mt-3 w-80 bg-[#F3F4F4] border border-gray-200 rounded-xl shadow-xl z-20 p-4">
                        <p class="text-sm font-semibold text-gray-700 mb-3">Filter Employees</p>

                        <div class="space-y-3">
                            <div>
                                <label for="filterStatus" class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                                <select id="filterStatus" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-700">
                                    <option value="">All Statuses</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div>
                                <label for="filterDepartment" class="block text-xs font-medium text-gray-500 mb-1">Department</label>
                                <select id="filterDepartment" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-700">
                                    <option value="">All Departments</option>
                                </select>
                            </div>

                            <div>
                                <label for="filterEmploymentType" class="block text-xs font-medium text-gray-500 mb-1">Employment Type</label>
                                <select id="filterEmploymentType" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-700">
                                    <option value="">All Types</option>
                                </select>
                            </div>

                            <div>
                                <label for="filterSortBy" class="block text-xs font-medium text-gray-500 mb-1">Sort By</label>
                                <select id="filterSortBy" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-700">
                                    <option value="name">Employee Name</option>
                                    <option value="department">Department</option>
                                    <option value="employment_type">Employment Type</option>
                                    <option value="employee_number">Employee ID</option>
                                    <option value="status">Status</option>
                                </select>
                            </div>

                            <div>
                                <label for="filterSortOrder" class="block text-xs font-medium text-gray-500 mb-1">Order</label>
                                <select id="filterSortOrder" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-700">
                                    <option value="asc">Ascending</option>
                                    <option value="desc">Descending</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button id="clearFiltersBtn" type="button" class="text-sm px-3 py-2 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-100">
                                Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <!-- Table Header -->
                <div class="grid grid-cols-[2fr_1.5fr_1.5fr_1.2fr_1fr_1fr_1fr] bg-[#DCDCDC] text-[#737373] border border-[#9E9E9E] rounded-lg overflow-hidden text-sm font-medium">
                    <div class="p-4">Employee Name</div>
                    <div class="p-4">Position</div>
                    <div class="p-4">Department</div>
                    <div class="p-4">ID</div>
                    <div class="p-4">Type</div>
                    <div class="p-4">Status</div>
                    <div class="p-4 text-center">Action</div>
                </div>
                <!-- Table Body — rows rendered by JS -->
                <div id="employeeTableBody" class="text-gray-700"></div>
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
    
    <script src="<?= base_url('js/components/flash-notifications.js') ?>"></script>
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
            const body = document.getElementById('employeeTableBody');

            if (!list.length) {
                body.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-[320px] text-gray-400 gap-3">

                        <i class="fa-solid fa-users text-4xl opacity-40"></i>

                        <span class="text-lg font-medium">
                            No employees found
                        </span>

                        <span class="text-sm text-gray-400">
                            Try adjusting your search or filters
                        </span>

                    </div>
                `;
                return;
            }

            body.innerHTML = list.map(e => {
                const statusBadge = e.status == 1
                    ? '<span class="bg-green-200 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">Active</span>'
                    : '<span class="bg-red-200 text-red-600 px-3 py-1 rounded-full text-xs font-semibold">Inactive</span>';

                return `
                    <div class="grid grid-cols-[2fr_1.5fr_1.5fr_1.2fr_1fr_1fr_1fr] items-center border-b hover:bg-gray-50">

                        <div class="p-4 flex items-center gap-3">
                            <div class="w-10 h-10 bg-gray-300 rounded-full"></div>
                            <span class="font-medium">
                                ${htmlEsc(e.last_name)}, 
                                ${htmlEsc(e.first_name)}
                                ${e.middle_name ? htmlEsc(e.middle_name.charAt(0).toUpperCase()) + '.' : ''} 
                            </span>
                        </div>

                        <div class="p-4">${htmlEsc(e.position) || '---'}</div>

                        <div class="p-4">${htmlEsc(e.department) || '---'}</div>

                        <div class="p-4">${htmlEsc(e.employee_number) || '---'}</div>

                        <div class="p-4">${htmlEsc(e.employment_type) || '---'}</div>

                        <div class="p-4">${statusBadge}</div>

                        <div class="p-4 flex justify-center">
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

                    </div>`;
            }).join('');
        }

        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('employeeSearch');
            const filterToggle = document.getElementById('filterToggle');
            const filterDropdown = document.getElementById('employeeFilterDropdown');
            const filterStatus = document.getElementById('filterStatus');
            const filterDepartment = document.getElementById('filterDepartment');
            const filterEmploymentType = document.getElementById('filterEmploymentType');
            const filterSortBy = document.getElementById('filterSortBy');
            const filterSortOrder = document.getElementById('filterSortOrder');
            const clearFiltersBtn = document.getElementById('clearFiltersBtn');

            function addUniqueOptions(selectEl, values) {
                const uniqueSorted = Array.from(new Set(values.filter(Boolean).map(v => String(v).trim()))).sort((a, b) =>
                    a.localeCompare(b)
                );

                uniqueSorted.forEach(val => {
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = val;
                    selectEl.appendChild(opt);
                });
            }

            addUniqueOptions(filterDepartment, EMPLOYEES.map(e => e.department));
            addUniqueOptions(filterEmploymentType, EMPLOYEES.map(e => e.employment_type));

            function applyEmployeeFilters() {
                const q = (searchInput.value || '').toLowerCase().trim();
                const status = (filterStatus.value || '').toLowerCase();
                const department = (filterDepartment.value || '').toLowerCase();
                const employmentType = (filterEmploymentType.value || '').toLowerCase();
                const sortBy = filterSortBy.value || 'name';
                const sortOrder = filterSortOrder.value || 'asc';

                const filtered = EMPLOYEES.filter(e => {
                    const fullName = [e.first_name ?? '', e.middle_name ?? '', e.last_name ?? ''].join(' ').toLowerCase();
                    const ePosition = (e.position ?? '').toLowerCase();
                    const eDepartment = (e.department ?? '').toLowerCase();
                    const eNumber = (e.employee_number ?? '').toLowerCase();
                    const eType = (e.employment_type ?? '').toLowerCase();
                    const eStatus = e.status == 1 ? 'active' : 'inactive';

                    const matchSearch =
                        !q ||
                        [fullName, ePosition, eDepartment, eNumber, eType, eStatus].join(' ').includes(q);

                    const matchStatus = !status || eStatus === status;
                    const matchDepartment = !department || eDepartment === department;
                    const matchEmploymentType = !employmentType || eType === employmentType;

                    return matchSearch && matchStatus && matchDepartment && matchEmploymentType;
                });

                const sorted = [...filtered].sort((a, b) => {
                    let left = '';
                    let right = '';

                    if (sortBy === 'name') {
                        left = [a.first_name ?? '', a.middle_name ?? '', a.last_name ?? ''].join(' ').trim().toLowerCase();
                        right = [b.first_name ?? '', b.middle_name ?? '', b.last_name ?? ''].join(' ').trim().toLowerCase();
                    } else if (sortBy === 'status') {
                        left = String(a.status ?? 0);
                        right = String(b.status ?? 0);
                    } else {
                        left = String(a[sortBy] ?? '').toLowerCase();
                        right = String(b[sortBy] ?? '').toLowerCase();
                    }

                    if (left < right) return sortOrder === 'asc' ? -1 : 1;
                    if (left > right) return sortOrder === 'asc' ? 1 : -1;
                    return 0;
                });

                renderEmployees(sorted);
            }

            function closeFilterDropdown() {
                filterDropdown.classList.add('hidden');
                filterToggle.setAttribute('aria-expanded', 'false');
            }

            function toggleFilterDropdown() {
                const isHidden = filterDropdown.classList.contains('hidden');
                filterDropdown.classList.toggle('hidden');
                filterToggle.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
            }

            // Initial render — show all employees on page load
            renderEmployees(EMPLOYEES);

            // Search and filters
            searchInput.addEventListener('input', applyEmployeeFilters);
            filterStatus.addEventListener('change', applyEmployeeFilters);
            filterDepartment.addEventListener('change', applyEmployeeFilters);
            filterEmploymentType.addEventListener('change', applyEmployeeFilters);
            filterSortBy.addEventListener('change', applyEmployeeFilters);
            filterSortOrder.addEventListener('change', applyEmployeeFilters);

            filterToggle.addEventListener('click', toggleFilterDropdown);

            clearFiltersBtn.addEventListener('click', () => {
                filterStatus.value = '';
                filterDepartment.value = '';
                filterEmploymentType.value = '';
                filterSortBy.value = 'name';
                filterSortOrder.value = 'asc';
                applyEmployeeFilters();
            });

            document.addEventListener('click', evt => {
                if (!filterDropdown.contains(evt.target) && !filterToggle.contains(evt.target)) {
                    closeFilterDropdown();
                }
            });

            document.addEventListener('keydown', evt => {
                if (evt.key === 'Escape') {
                    closeFilterDropdown();
                }
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