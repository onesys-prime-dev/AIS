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
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg shadow flex items-center gap-2">
            
            <i class="fa-solid fa-plus"></i>
            New Employee

        </button>

    </div>


    <!-- Employee Card -->
    <div class="bg-white rounded-2xl shadow-lg p-6">

        <!-- Header -->
        <div class="flex justify-between items-center mb-4">

            <h2 class="text-lg font-semibold text-gray-700">
                Recent People
            </h2>

            <div class="flex gap-3">

                <!-- Search -->
                <div class="relative">

                    <input 
                        type="text"
                        placeholder="Search by employee's name..."
                        class="bg-gray-100 text-sm px-4 py-2 rounded-lg pl-10 outline-none"
                    >

                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-gray-400"></i>

                </div>

            </div>

        </div>


        <!-- Table -->
        <div class="overflow-x-auto">

            <table class="w-full text-sm text-left">

                <!-- Table Header -->
                <thead>
                    <tr class="bg-gray-100 text-gray-600">

                        <th class="p-4">Employee Name</th>
                        <th class="p-4">Title</th>
                        <th class="p-4">ID</th>
                        <th class="p-4">Type</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-center">Action</th>

                    </tr>
                </thead>


                <!-- Table Body -->
                <tbody class="text-gray-700">

                    <?php foreach($employees as $employee): ?>

                    <tr class="border-b hover:bg-gray-50">

                        <!-- Name -->
                        <td class="p-4 flex items-center gap-3">

                            <div class="w-10 h-10 bg-gray-300 rounded-full"></div>

                            <span><?= esc($employee['first_name'] ?? 'Employee') ?></span>

                        </td>

                        <!-- Title -->
                        <td class="p-4">Employee</td>

                        <!-- ID -->
                        <td class="p-4"><?= esc($employee['employee_id'] ?? '---') ?></td>

                        <!-- Type -->
                        <td class="p-4">Full Time</td>


                        <!-- Status -->
                        <td class="p-4">

                        <?php if(($employee['status'] ?? '') == 'active'): ?>

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
                        <td class="p-4 flex justify-center gap-4">

                            <button class="text-gray-600 hover:text-indigo-600">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>

                            <button class="text-gray-600 hover:text-red-500">
                                <i class="fa-solid fa-trash"></i>
                            </button>

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
        'title' => 'Add New Employee',
        'content' => $content
    ]);

    ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

    <script src="<?= base_url('js/components/modal-handler.js') ?>"></script>

<?= $this->endSection() ?>