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

    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Employee Management</h1>
            <p class="text-gray-500 text-sm">Manage employee data here.</p>
        </div>

        <button 
            data-modal-open="employeeModal"
            class="bg-[#FF2D2D] hover:bg-[#ff2d2dd2] text-white px-3 py-3 rounded-lg shadow flex items-center gap-2">
                <div id="add-icon" class="w-7 h-7 cursor-pointer">
                    <img src="<?php echo base_url('images/icons/add.png'); ?>" alt="Add"/>
                </div>
            New Employee
        </button>

    </div>


    <!-- Employee Card -->
    <div class="bg-white rounded-2xl shadow-lg p-6">

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
                        type="text"
                        placeholder="Search by employee’s name..."
                        class="w-full bg-gray-200 text-gray-700 text-sm pl-10 pr-5 py-3 rounded-lg outline-none placeholder-gray-500"
                    >
                </div>
                <!-- Filter -->
                <button class="flex items-center gap-3 bg-gray-600 hover:bg-gray-700 text-white text-sm px-6 py-3 rounded-lg shadow-md">
                    <span>Filters</span>
                    <div>
                        <img src="<?php echo base_url('images/icons/filter.png'); ?>" class="w-4 h-4" alt="Filter" />   
                    </div>
                </button>
            </div>

        </div>
        
        <!-- Table -->
        <div class="overflow-x-auto">

            <table class="w-full text-sm text-left">

                <!-- Table Header -->
                <thead>
                    <tr class="bg-gray-300 text-gray-600">
                        <th class="p-4 rounded-tl-lg rounded-bl-lg">Employee Name</th>
                        <th class="p-4">Title</th>
                        <th class="p-4">Department</th>
                        <th class="p-4">ID</th>
                        <th class="p-4">Type</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-center rounded-tr-lg rounded-br-lg">Action</th>
                    </tr>
                </thead>


                <!-- Table Body -->
                <tbody class="text-gray-700">

                    <?php foreach($employees as $employee): ?>

                    <tr class="border-b hover:bg-gray-50">

                        <!-- Name -->
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gray-300 rounded-full"></div>

                                <span>
                                    <?= esc($employee['first_name'] ?? '') ?> 
                                    <?= esc($employee['middle_name'] ?? '') ?> 
                                    <?= esc($employee['last_name'] ?? '') ?>
                                </span>
                            </div>
                        </td>

                        <!-- Title -->
                        <td class="p-4"><?= esc($employee['position'] ?? '---') ?></td>

                        <!-- Department -->
                        <td class="p-4"><?= esc($employee['department'] ?? '---') ?></td>

                        <!-- ID -->
                        <td class="p-4"><?= esc($employee['employee_number'] ?? '---') ?></td>

                        <!-- Type -->
                        <td class="p-4"><?= esc($employee['employment_type'] ?? '---') ?></td>

                        <!-- Status -->
                        <td class="p-4">

                        <?php if(($employee['status'] ?? 0) == 1): ?>

                            <span class="bg-green-200 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">
                                Active
                            </span>

                        <?php else: ?>

                            <span class="bg-red-200 text-red-600 px-3 py-1 rounded-full text-xs font-semibold">
                                Inactive
                            </span>

                        <?php endif ?>

                        </td>

                        <!-- Actions -->
                        <td class="p-4">
                            <div class="flex justify-center">
                                <div id="action-utility-container" class="flex items-center gap-4">

                                    <div 
                                        class="editEmployeeBtn cursor-pointer flex items-center justify-center w-7 h-7 hover:scale-110 transition"
                                        data-id="<?= $employee['id'] ?>"
                                        role="button"
                                        tabindex="0">
                                        <img src="<?= base_url('images/icons/edit.png'); ?>" class="w-7 h-7" alt="Edit">
                                    </div>

                                    <div 
                                        id="deleteEmployeeBtn" 
                                        class="deleteEmployeeBtn cursor-pointer flex items-center justify-center w-7 h-7 hover:scale-110 transition" 
                                        data-id="<?= $employee['id'] ?>"
                                        role="button"
                                        tabindex="0">
                                        <img src="<?php echo base_url('images/icons/delete.png'); ?>" class="w-7 h-7" alt="Delete" />
                                    </div>

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

    <script src="<?= base_url('js/components/modal-handler.js') ?>"></script>
    <script>
        document.querySelectorAll(".editEmployeeBtn").forEach(btn => {

            btn.addEventListener("click", async () => {

                const employeeId = btn.dataset.id;

                // reset form first so previous values disappear
                document.getElementById("editEmployeeForm").reset();

                try {

                    const response = await fetch(
                        "<?= site_url('client_portal/employees/get') ?>/" + employeeId
                    );

                    const employee = await response.json();

                    document.getElementById("edit_first_name").value = employee.first_name || "";
                    document.getElementById("edit_middle_name").value = employee.middle_name || "";
                    document.getElementById("edit_last_name").value = employee.last_name || "";
                    document.getElementById("edit_department").value = employee.department || "";
                    document.getElementById("edit_position").value = employee.position || "";
                    document.getElementById("edit_employment_type").value = employee.employment_type || "";
                    document.getElementById("edit_dob").value = employee.birthdate || "";

                    document.getElementById("editEmployeeForm").action =
                        "<?= site_url('client_portal/employees/update') ?>/" + employeeId;

                    // OPEN MODAL AFTER DATA LOAD
                    document.getElementById("editEmployeeModal").classList.remove("hidden");

                } catch (error) {

                    console.error("Failed to fetch employee:", error);

                }

            });

        });
    </script>

<?= $this->endSection() ?>