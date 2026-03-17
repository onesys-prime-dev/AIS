<form id="editPayrollForm" method="post" action="">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="">

    <div class="mb-4">
        <label for="edit_employee_id" class="block text-sm font-medium text-gray-700">Employee</label>
        <select name="employee_id" id="edit_employee_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            <option value="">Select Employee</option>
            <?php foreach ($employees as $emp): ?>
            <option value="<?= $emp['id'] ?>"><?= $emp['full_name'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-4">
        <label for="edit_employee_name" class="block text-sm font-medium text-gray-700">Employee Name (readonly)</label>
        <input type="text" id="edit_employee_name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100" readonly>
    </div>

    <div class="mb-4">
        <label for="edit_phone" class="block text-sm font-medium text-gray-700">Phone</label>
        <input type="text" name="phone" id="edit_phone" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
    </div>

    <div class="mb-4">
        <label for="edit_daily_rate" class="block text-sm font-medium text-gray-700">Daily Rate</label>
        <input type="number" step="0.01" name="daily_rate" id="edit_daily_rate" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
    </div>

    <div class="mb-4 flex items-center">
        <input type="checkbox" name="dst" id="edit_dst" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm">
        <label for="edit_dst" class="ml-2 text-sm text-gray-700">DST (Daylight Saving Time)</label>
    </div>

    <div class="mb-4">
        <label for="edit_currency" class="block text-sm font-medium text-gray-700">Currency</label>
        <select name="currency" id="edit_currency" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="PHP">PHP</option>
            <option value="USD">USD</option>
        </select>
    </div>

    <div class="mb-4">
        <label for="edit_status" class="block text-sm font-medium text-gray-700">Status</label>
        <select name="status" id="edit_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="Pending">Pending</option>
            <option value="Paid">Paid</option>
        </select>
    </div>

    <div class="flex justify-end gap-2">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Update</button>
        <button type="button" class="deletePayrollBtn bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700" data-id="">Delete</button>
    </div>
</form>