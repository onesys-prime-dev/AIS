<form id="editEmployeeForm" method="post">
<?= csrf_field() ?>

    <input type="hidden" id="edit_employee_id">

    <div class="grid grid-cols-2 gap-6">

        <!-- LEFT COLUMN -->
        <div class="space-y-4">

            <div>
                <label class="block text-sm mb-1">First Name</label>
                <input type="text" id="edit_first_name" name="first_name"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">Middle Name</label>
                <input type="text" id="edit_middle_name" name="middle_name"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">Last Name</label>
                <input type="text" id="edit_last_name" name="last_name"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">Department</label>
                <input type="text" id="edit_department" name="department"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

        </div>

        <!-- RIGHT COLUMN -->
        <div class="space-y-4">

            <div>
                <label class="block text-sm mb-1">Position</label>
                <input type="text" id="edit_position" name="position"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">Employment Type</label>
                <input type="text" id="edit_employment_type" name="employment_type"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">Date of Birth</label>
                <input type="text" id="edit_dob" name="dob"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm mb-1">Status</label>
                <select id="edit_status" name="status"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="1">Active</option>
                    <option value="0">Inactive / Deactivated</option>
                </select>

                <p class="mt-1 text-xs text-gray-500">
                    Inactive employees keep their historical details, but their account can no longer be used to sign in.
                </p>

                <p id="selfDeactivateGuardNote" class="mt-1 text-xs text-amber-700 hidden">
                    You cannot set your own account to Inactive while logged in.
                </p>
            </div>

        </div>

    </div>

    <div class="flex justify-end mt-6">
        <button type="submit"
            class="bg-[#9764F6] hover:bg-[#8a58e0] text-white px-6 py-2 rounded-lg">
            Update Employee
        </button>
    </div>

</form>