<form id="employeeForm" action="<?= site_url('client_portal/employees/create') ?>" method="post">
<?= csrf_field() ?>

<!-- STEP PROGRESS -->
<div class="flex justify-center mb-8">

    <div class="flex text-sm font-semibold">

        <!-- Step 1 -->
        <div id="progress1"
            class="flex items-center px-8 py-2 text-white bg-purple-600"
            style="clip-path: polygon(0 0, 90% 0, 100% 50%, 90% 100%, 0 100%);">
            1. Personal Info
        </div>

        <!-- Step 2 -->
        <div id="progress2"
            class="flex items-center px-8 py-2 text-gray-700 bg-gray-300 -ml-4"
            style="clip-path: polygon(10% 0, 90% 0, 100% 50%, 90% 100%, 10% 100%, 0 50%);">
            2. Job Details
        </div>

        <!-- Step 3 -->
        <div id="progress3"
            class="flex items-center px-8 py-2 text-gray-700 bg-gray-300 -ml-4"
            style="clip-path: polygon(10% 0, 100% 0, 100% 100%, 10% 100%, 0 50%);">
            3. Something
        </div>

    </div>

</div>


<!-- ================= STEP 1 ================= -->
<div class="form-step" id="step1">

    <div class="space-y-4">

        <div>
            <label class="block text-sm mb-1">First Name:</label>
            <input type="text" name="first_name"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <div>
            <label class="block text-sm mb-1">Middle Name:</label>
            <input type="text" name="middle_name"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <div>
            <label class="block text-sm mb-1">Last Name:</label>
            <input type="text" name="last_name"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <div>
            <label class="block text-sm mb-1">Date of Birth:</label>
            <input type="date" name="dob"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

    </div>

    <div class="flex justify-end mt-6">
        <button type="button"
            onclick="nextStep(2)"
            class="bg-gray-700 hover:bg-indig-800 text-white px-6 py-2 rounded-lg">

            Next
        </button>
    </div>

</div>


<!-- ================= STEP 2 ================= -->
<div class="form-step hidden" id="step2">

    <div class="space-y-4">

        <div>
            <label class="block text-sm mb-1">SSS:</label>
            <input type="text" name="sss"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <div>
            <label class="block text-sm mb-1">Pagibig:</label>
            <input type="text" name="pagibig"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <div>
            <label class="block text-sm mb-1">TIN:</label>
            <input type="text" name="tin"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <div>
            <label class="block text-sm mb-1">PhilHealth:</label>
            <input type="text" name="philhealth"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

    </div>

    <div class="flex justify-between mt-6">

        <button type="button"
            onclick="prevStep(1)"
            class="bg-gray-400 text-white px-6 py-2 rounded-lg">

            Back
        </button>

        <button type="button"
            onclick="nextStep(3)"
            class="bg-gray-700 hover:bg-gray-800 text-white px-6 py-2 rounded-lg">

            Next
        </button>

    </div>

</div>


<!-- ================= STEP 3 ================= -->
<div class="form-step hidden" id="step3">

    <div class="space-y-4">

        <div>
            <label class="block text-sm mb-1">Department:</label>
            <input type="text" name="department"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <div>
            <label class="block text-sm mb-1">Position:</label>
            <input type="text" name="position"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <div>
            <label class="block text-sm mb-1">Email:</label>
            <input type="text" name="email"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <div>
            <label class="block text-sm mb-1">Password:</label>
            <input type="text" name="password"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

    </div>

    <div class="flex justify-between mt-6">

        <button type="button"
            onclick="prevStep(2)"
            class="bg-gray-400 text-white px-6 py-2 rounded-lg">
            Back
        </button>

        <button type="submit"
            class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg">
            Create Employee
        </button>

    </div>

</div>

</form>