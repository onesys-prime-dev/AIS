<form id="editPayrollForm" method="post" action="">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="">
    <input type="hidden" name="employee_id" id="edit_employee_id" value="">
 
    <div class="mb-4">
        <label for="edit_employee_name" class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
        <input type="text" name="employee_name" id="edit_employee_name" class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
    </div>

    <div class="mb-4">
        <label for="edit_phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
        <input type="text" name="phone" id="edit_phone" class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
    </div>

    <div class="mb-4">
        <label for="edit_daily_rate" class="block text-sm font-medium text-gray-700 mb-1">Daily Rate Override <span class="text-gray-400 font-normal">(optional)</span></label>
        <input type="number" step="0.01" name="daily_rate" id="edit_daily_rate" class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Auto from Employee Management">
    </div>

    <div class="mb-4 flex items-center">
        <input type="checkbox" name="dst" id="edit_dst" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm">
        <label for="edit_dst" class="ml-2 text-sm text-gray-700">DST (Daylight Saving Time)</label>
    </div>

    <div class="mb-4">
        <label for="edit_currency" class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
        <select name="currency" id="edit_currency" class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            <option value="PHP">PHP</option>
            <option value="USD">USD</option>
        </select>
    </div>

    <div class="mb-4">
        <label for="edit_status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select name="status" id="edit_status" class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            <option value="Pending">Pending</option>
            <option value="Paid">Paid</option>
        </select>
    </div>

        <div class="flex justify-end gap-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Update</button>
        </div>
</form>