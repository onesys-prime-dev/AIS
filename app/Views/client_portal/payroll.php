<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="p-5 h-full overflow-y-auto">

    <div class="flex justify-between items-center mb-6">
        
        <div class="p-1">
            <h1 class="text-3xl font-bold text-gray-800 mb-1">Payroll Status</h1>
            <p class="text-gray-500 text-sm">Check the payroll status of your employees.</p>
        </div>

        <div class="flex items-center gap-4">
            <!-- Month Picker -->
            <input type="month" id="monthPicker" value="<?= $selected_month ?>" class="bg-[#969595] hover:bg-[#797777d2] text-white px-3 py-3 rounded-lg shadow flex items-center gap-2">
            <button 
                data-modal-open="payrollModal"
                class="bg-[#FF2D2D] hover:bg-[#ff2d2dd2] text-white px-3 py-3 rounded-lg shadow flex items-center gap-2">
                    <div id="add-icon" class="w-7 h-7 cursor-pointer">
                        <img src="<?php echo base_url('icons/add.png'); ?>" alt="Add"/>
                    </div>
                New Payroll Entry
            </button>
        </div>
    </div>

    <!-- Payroll Card -->
    <div class="bg-white rounded-2xl shadow-lg p-5 h-[85%] border border-gray-400">

        <!-- Header with Search & Filter -->
        <div class="flex justify-between items-center mb-5">
            <h2 class="text-lg font-semibold text-gray-700">
                <?= date('F Y', strtotime($selected_month . '-01')) ?> Payroll
            </h2>

            <div class="flex items-center gap-4">
                <!-- Search -->
                <div class="relative w-[350px]">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                            <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM2.25 10.5a8.25 8.25 0 1 1 14.59 5.28l4.69 4.69a.75.75 0 1 1-1.06 1.06l-4.69-4.69A8.25 8.25 0 0 1 2.25 10.5Z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <input 
                        type="text"
                        id="searchInput"
                        placeholder="Search by employee's name..."
                        class="w-full bg-gray-200 text-gray-700 text-sm pl-10 pr-5 py-3 rounded-lg outline-none placeholder-gray-500"
                    >
                </div>
                <!-- Filter -->
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
            </div>
        </div>
        
        <!-- Table -->
        <div class="overflow-x-auto">
            <div class="grid grid-cols-[2fr_1.5fr_1.5fr_1.2fr_1fr_1fr_1fr] bg-[#DCDCDC] text-[#737373] border border-[#9E9E9E] rounded-lg overflow-hidden text-sm font-medium">
                <div class="p-4">Employee Name</div>
                <div class="p-4">Phone</div>
                <div class="p-4">Daily Rate</div>
                <div class="p-4">DST</div>
                <div class="p-4">Currency</div>
                <div class="p-4">Status</div>
                <div class="p-4 text-center">Action</div>
            </div>
            <div id="payrollTableBody" class="text-gray-700"></div>
        </div>

    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('modals') ?>

    <!-- Add Payroll Modal -->
    <?php
        $content = view('client_portal/modals/add_payroll_form');
        echo view('components/modal', [
            'id' => 'payrollModal',
            'title' => 'Add Payroll Entry',
            'content' => $content
        ]);
    ?>

    <!-- Edit Payroll Modal -->
    <?php
        $content = view('client_portal/modals/edit_payroll_form');
        echo view('components/modal', [
            'id' => 'editPayrollModal',
            'title' => 'Edit Payroll Entry',
            'content' => $content
        ]);
    ?>
        

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

    <script src="<?= base_url('js/components/flash-notifications.js') ?>"></script>
    <script src="<?= base_url('js/components/modal-handler.js') ?>"></script>
    <script>
        const PAYROLLS   = <?= json_encode($payrolls, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const UPDATE_URL = "<?= site_url('client_portal/payroll/update') ?>";
        const DELETE_URL = "<?= site_url('client_portal/payroll/delete') ?>";

        function htmlEsc(v) {
            if (v == null) return '';
            return String(v)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function money(v) {
            return Number(v || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function renderPayrolls(list) {
            const body = document.getElementById('payrollTableBody');
            if (!body) return;

            if (!list.length) {
                body.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-[320px] text-gray-400 gap-3">
                        <i class="fa-solid fa-users text-4xl opacity-40"></i>
                        <span class="text-lg font-medium">No employees found</span>
                        <span class="text-sm text-gray-400">Try adjusting your search or filters</span>
                    </div>
                `;
                return;
            }

            body.innerHTML = list.map(p => `
                <div class="grid grid-cols-[2fr_1.5fr_1.5fr_1.2fr_1fr_1fr_1fr] items-center border-b last:border-b-0 hover:bg-gray-50">
                    <!-- Employee Name -->
                    <div class="p-4 flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-300 rounded-full"></div>
                        <span class="font-medium">${htmlEsc(p.employee_name || '---')}</span>
                    </div>    
                    <!-- Phone -->
                    <div class="p-4">${htmlEsc(p.phone || '---')}</div>
                    <!-- Daily Rate -->
                    <div class="p-4">${htmlEsc(p.currency === 'USD' ? '$' : '₱')} ${htmlEsc(money(p.daily_rate))}</div>
                    <!-- DST: ☐ or ☑ -->
                    <div class="p-4 text-xl">${Number(p.dst) === 1 ? '☑' : '☐'}</div>
                    <!-- Currency (plain text) -->
                    <div class="p-4">${htmlEsc(p.currency || 'PHP')}</div>
                    <!-- Status -->
                    <div class="p-4">
                        ${p.status === 'Paid'
                            ? '<span class="bg-green-200 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">Paid</span>'
                            : '<span class="bg-yellow-200 text-yellow-700 px-3 py-1 rounded-full text-xs font-semibold">Pending</span>'}
                    </div>
                    <!-- Action: single document icon -->
                    <div class="p-4 flex justify-center">
                        <div class="viewPayrollBtn cursor-pointer hover:scale-110 transition" 
                             data-id="${htmlEsc(p.id)}"
                             data-employee_id="${htmlEsc(p.employee_id)}"
                             data-employee_name="${htmlEsc(p.employee_name)}"
                             data-phone="${htmlEsc(p.phone)}"
                             data-daily_rate="${htmlEsc(p.daily_rate)}"
                             data-dst="${htmlEsc(p.dst)}"
                             data-currency="${htmlEsc(p.currency)}"
                             data-status="${htmlEsc(p.status ?? 'Pending')}"
                             role="button" tabindex="0">
                            <span class="text-2xl">📄</span>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Month picker redirect
        document.getElementById('monthPicker')?.addEventListener('change', (e) => {
            window.location.href = '<?= site_url('client_portal/payroll') ?>?month=' + e.target.value;
        });

        // Initial render
        renderPayrolls(PAYROLLS);

        // Search
        document.getElementById('searchInput')?.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            const filtered = PAYROLLS.filter(p =>
                String(p.employee_name || '').toLowerCase().includes(q)
            );
            renderPayrolls(filtered);
        });

        // Click on document icon → open edit modal with data
        document.getElementById('payrollTableBody')?.addEventListener('click', (e) => {
            const viewBtn = e.target.closest('.viewPayrollBtn');
            if (!viewBtn) return;

            const payrollId = viewBtn.dataset.id;
            const form = document.getElementById('editPayrollForm');
            if (!form) return;

            form.reset();

            // Populate form fields (adjust IDs to match your edit form)
            document.getElementById('edit_employee_id').value = viewBtn.dataset.employee_id || '';
            const employeeNameEl = document.getElementById('edit_employee_name');
            if (employeeNameEl) employeeNameEl.value = viewBtn.dataset.employee_name || '';
            document.getElementById('edit_phone').value = viewBtn.dataset.phone || '';
            document.getElementById('edit_daily_rate').value = viewBtn.dataset.daily_rate || '';
            document.getElementById('edit_dst').checked = Number(viewBtn.dataset.dst) === 1;
            document.getElementById('edit_currency').value = viewBtn.dataset.currency || 'PHP';
            document.getElementById('edit_status').value = viewBtn.dataset.status || 'Pending';
            form.action = `${UPDATE_URL}/${payrollId}`;

            // Also set the delete button's data-id inside the modal
            const deleteBtn = form.querySelector('.deletePayrollBtn');
            if (deleteBtn) {
                deleteBtn.dataset.id = payrollId;
            }

            // Show modal
            document.getElementById('editPayrollModal').classList.remove('hidden');
        });

        // Delete button inside edit modal
        document.addEventListener('click', (e) => {
            const deleteBtn = e.target.closest('.deletePayrollBtn');
            if (deleteBtn && confirm('Are you sure you want to delete this payroll record?')) {
                const id = deleteBtn.dataset.id;
                window.location.href = `${DELETE_URL}/${id}`;
            }
        });
    </script>

<?= $this->endSection() ?>