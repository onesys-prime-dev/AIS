<form id="employeeForm" action="<?= site_url('client_portal/employees/create') ?>" method="post">
<?= csrf_field() ?>

<!-- STEP PROGRESS -->
<div class="flex justify-center mb-5">

    <div class="flex text-sm font-semibold gap-3">

        <!-- Step 1 -->
        <div id="progress1"
            class="flex items-center px-8 py-2 text-white bg-[#A891FC] rounded-l-full"
            style="clip-path: polygon(91% 0, 100% 50%, 91% 100%, 0% 100%, 0 50%, 0% 0%);">
            1. Personal Info
        </div>

        <!-- Step 2 -->
        <div id="progress2"
            class="flex items-center px-8 py-2 text-gray-800 bg-gray-300 -ml-4"
            style="clip-path: polygon(91% 0, 100% 50%, 91% 100%, 0% 100%, 9% 50%, 0% 0%);">
            2. Government IDs
        </div>

        <!-- Step 3 -->
        <div id="progress3"
            class="flex items-center px-8 py-2 text-gray-800 bg-gray-300 -ml-4 rounded-r-full"
            style="clip-path: polygon(100% 0, 100% 50%, 100% 100%, 0% 100%, 9% 50%, 0% 0%);">
            3. Job Details
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
            <label class="block text-sm mb-1">Employment type:</label>
            <input type="text" name="employment_type"
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <div>
            <label class="block text-sm mb-1">Email:</label>
            <input type="email" name="email" required
                class="w-full border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
                placeholder="employee@company.com">
        </div>

        <div>
            <label class="block text-sm mb-1">Password:</label>
            <div class="relative">
                <input id="employee-password" type="password" name="password" required
                    class="w-full border rounded-lg p-2 pr-12 focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="Enter a strong password">
                <button type="button" id="toggle-password" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none" aria-label="Show or hide password">
                    <!-- Eye Slash Icon (Password Hidden - Default State) -->
                    <svg id="eye-closed" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                    </svg>
                    <!-- Eye Icon (Password Visible) -->
                    <svg id="eye-open" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
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

<script>
document.getElementById('employeeForm').addEventListener('submit', function(e) {
    // Validate email and password before form submission
    const email = document.querySelector('input[name="email"]').value.trim();
    const password = document.querySelector('input[name="password"]').value.trim();
    
    if (!email) {
        e.preventDefault();
        alert('Email is required. Please enter a valid email address.');
        document.querySelector('input[name="email"]').focus();
        return false;
    }
    
    if (!password) {
        e.preventDefault();
        alert('Password is required. Please enter a password (minimum 8 characters).');
        document.querySelector('input[name="password"]').focus();
        return false;
    }
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        e.preventDefault();
        alert('Please enter a valid email address (example: employee@company.com).');
        document.querySelector('input[name="email"]').focus();
        return false;
    }
    
    if (password.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long.');
        document.querySelector('input[name="password"]').focus();
        return false;
    }
    
    // If all validations pass, form will submit
});
</script>