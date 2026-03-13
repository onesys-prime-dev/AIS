<form id="addPayrollForm" action="<?= site_url('client_portal/payroll/create') ?>" method="post">
<?= csrf_field() ?>
    <input type="hidden" name="month_year" value="<?= $selected_month ?>">

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
            <select name="employee_id" required class="w-full border rounded-lg px-3 py-2">
                <option value="">Select Employee</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= esc($emp['id']) ?>"><?= esc($emp['full_name']) ?></option>
                <?php endforeach; ?>
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