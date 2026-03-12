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

    <!-- Page Header with Month Selector -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Payroll Status</h1>
            <p class="text-gray-500 text-sm">Check the payroll status of your employees.</p>
        </div>

        <div class="flex items-center gap-4">
            <!-- Month Picker -->
            <input type="month" id="monthPicker" value="<?= $selected_month ?>" class="border rounded-lg px-4 py-2 text-sm">

            <button 
                data-modal-open="payrollModal"
                class="bg-[#FF2D2D] hover:bg-[#ff2d2dd2] text-white px-3 py-3 rounded-lg shadow flex items-center gap-2">
                <div id="add-icon" class="w-7 h-7 cursor-pointer">
                    <img src="<?= base_url('images/icons/add.png') ?>" alt="Add"/>
                </div>
                New Payroll Entry
            </button>
        </div>
    </div>

    <!-- Payroll Card -->
    <div class="bg-white rounded-2xl shadow-lg p-6">

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
                <button class="flex items-center gap-3 bg-gray-600 hover:bg-gray-700 text-white text-sm px-6 py-3 rounded-lg shadow-md">
                    <span>Filters</span>
                    <div>
                        <img src="<?= base_url('images/icons/filter.png') ?>" class="w-4 h-4" alt="Filter" />   
                    </div>
                </button>
            </div>
        </div>
        
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left" id="payrollTable">
                <thead>
                    <tr class="bg-gray-300 text-gray-600">
                        <th class="p-4 rounded-tl-lg rounded-bl-lg">Employee Name</th>
                        <th class="p-4">Phone</th>
                        <th class="p-4">Daily Rate</th>
                        <th class="p-4">DST</th>
                        <th class="p-4">Currency</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-center rounded-tr-lg rounded-br-lg">Action</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    <?php foreach($payrolls as $payroll): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-4 font-medium"><?= esc($payroll['employee_name'] ?? '---') ?></td>
                        <td class="p-4"><?= esc($payroll['phone'] ?? '---') ?></td>
                        <td class="p-4">
                            <?= ($payroll['currency'] == 'USD' ? '$' : '₱') ?>
                            <?= number_format($payroll['daily_rate'] ?? 0, 2) ?>
                        </td>
                        <td class="p-4 text-center">
                            <input type="checkbox" <?= $payroll['dst'] ? 'checked' : '' ?> disabled class="w-4 h-4">
                        </td>
                        <td class="p-4"><?= esc($payroll['currency'] ?? '---') ?></td>
                        <td class="p-4">
                            <?php if($payroll['status'] == 'Paid'): ?>
                                <span class="bg-green-200 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">Paid</span>
                            <?php else: ?>
                                <span class="bg-yellow-200 text-yellow-700 px-3 py-1 rounded-full text-xs font-semibold">Pending</span>
                            <?php endif ?>
                        </td>
                        <td class="p-4">
                            <div class="flex justify-center gap-4">
                                <div 
                                    class="editPayrollBtn cursor-pointer w-7 h-7 hover:scale-110 transition"
                                    data-id="<?= $payroll['id'] ?>"
                                    role="button"
                                    tabindex="0">
                                    <img src="<?= base_url('images/icons/edit.png') ?>" class="w-7 h-7" alt="Edit">
                                </div>
                                <div 
                                    class="deletePayrollBtn cursor-pointer w-7 h-7 hover:scale-110 transition"
                                    data-id="<?= $payroll['id'] ?>"
                                    role="button"
                                    tabindex="0">
                                    <img src="<?= base_url('images/icons/delete.png') ?>" class="w-7 h-7" alt="Delete">
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('modals') ?>

    <!-- Add Payroll Modal -->
    <div id="payrollModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-semibold text-gray-800">Add Payroll Entry</h3>
                <button data-modal-close class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <div class="p-6">
                <form id="addPayrollForm" action="<?= site_url('client_portal/payroll/create') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="month_year" value="<?= $selected_month ?>">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                        <select name="employee_id" required class="w-full border rounded-lg px-3 py-2">
                            <option value="">Select Employee</option>
                            <?php foreach($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= esc($emp['full_name']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" name="phone" class="w-full border rounded-lg px-3 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Daily Rate</label>
                        <input type="number" step="0.01" name="daily_rate" required class="w-full border rounded-lg px-3 py-2">
                    </div>

                    <div class="mb-4 flex items-center">
                        <input type="checkbox" name="dst" id="dst" class="w-4 h-4 mr-2">
                        <label for="dst" class="text-sm font-medium text-gray-700">DST</label>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                        <select name="currency" class="w-full border rounded-lg px-3 py-2">
                            <option value="PHP">PHP</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full border rounded-lg px-3 py-2">
                            <option value="Pending">Pending</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" data-modal-close class="px-4 py-2 bg-gray-300 rounded-lg">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Payroll Modal -->
    <div id="editPayrollModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-semibold text-gray-800">Edit Payroll Entry</h3>
                <button data-modal-close class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            <div class="p-6">
                <form id="editPayrollForm" action="" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="month_year" value="<?= $selected_month ?>">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                        <select name="employee_id" id="edit_employee_id" required class="w-full border rounded-lg px-3 py-2">
                            <option value="">Select Employee</option>
                            <?php foreach($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= esc($emp['full_name']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" name="phone" id="edit_phone" class="w-full border rounded-lg px-3 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Daily Rate</label>
                        <input type="number" step="0.01" name="daily_rate" id="edit_daily_rate" required class="w-full border rounded-lg px-3 py-2">
                    </div>

                    <div class="mb-4 flex items-center">
                        <input type="checkbox" name="dst" id="edit_dst" class="w-4 h-4 mr-2">
                        <label for="edit_dst" class="text-sm font-medium text-gray-700">DST</label>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                        <select name="currency" id="edit_currency" class="w-full border rounded-lg px-3 py-2">
                            <option value="PHP">PHP</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="edit_status" class="w-full border rounded-lg px-3 py-2">
                            <option value="Pending">Pending</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" data-modal-close class="px-4 py-2 bg-gray-300 rounded-lg">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script src="<?= base_url('js/components/modal-handler.js') ?>"></script>
<script>
    // Month picker redirect
    document.getElementById('monthPicker').addEventListener('change', function(e) {
        window.location.href = '<?= site_url('client_portal/payroll') ?>?month=' + e.target.value;
    });

    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let value = this.value.toLowerCase();
        let rows = document.querySelectorAll('#payrollTable tbody tr');
        rows.forEach(row => {
            let name = row.cells[0].textContent.toLowerCase();
            row.style.display = name.includes(value) ? '' : 'none';
        });
    });

    // Edit button handler
    document.querySelectorAll('.editPayrollBtn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const payrollId = btn.dataset.id;
            document.getElementById('editPayrollForm').reset();

            try {
                const response = await fetch('<?= site_url('client_portal/payroll/get') ?>/' + payrollId);
                const payroll = await response.json();

                document.getElementById('edit_employee_id').value = payroll.employee_id || '';
                document.getElementById('edit_phone').value = payroll.phone || '';
                document.getElementById('edit_daily_rate').value = payroll.daily_rate || '';
                document.getElementById('edit_dst').checked = payroll.dst == 1;
                document.getElementById('edit_currency').value = payroll.currency || 'PHP';
                document.getElementById('edit_status').value = payroll.status || 'Pending';

                document.getElementById('editPayrollForm').action = '<?= site_url('client_portal/payroll/update') ?>/' + payrollId;

                // Open modal
                document.getElementById('editPayrollModal').classList.remove('hidden');
            } catch (error) {
                console.error('Failed to fetch payroll:', error);
            }
        });
    });

    // Delete button handler (example with confirmation)
    document.querySelectorAll('.deletePayrollBtn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (confirm('Are you sure you want to delete this payroll record?')) {
                window.location.href = '<?= site_url('client_portal/payroll/delete') ?>/' + btn.dataset.id;
            }
        });
    });
</script>

<?= $this->endSection() ?>