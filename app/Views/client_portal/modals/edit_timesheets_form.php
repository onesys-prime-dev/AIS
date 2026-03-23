<form id="editTimesheetForm" method="post" action="">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="">
    <input type="hidden" name="employee_id" id="edit_timesheet_id" value="">

    <div class="mb-4">
        <label for="edit_date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
        <input type="text" id="edit_date" name="date"
            class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
    </div>

    <div class="mb-4">
        <label for="edit_start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
        <input type="text" name="start_time" id="edit_start_date" 
            class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
    </div>

    <div class="mb-4">
        <label for="edit_end_date" class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
        <input type="text" name="end_time" id="edit_end_date" 
            class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
    </div>

    <div class="mb-4">
        <label for="edit_task_performed" class="block text-sm font-medium text-gray-700 mb-1">Task Performed</label>
        <textarea name="task_performed" id="edit_task_performed" rows="4"
            class="w-full border rounded-lg p-2 resize-none overflow-y-auto task-scrollbar focus:outline-none focus:ring-2 focus:ring-purple-500" required></textarea>
    </div>
    
    <div class="flex justify-end gap-2">
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Update</button>
    </div>
</form>