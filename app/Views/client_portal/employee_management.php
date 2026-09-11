<div class="flex flex-col w-full min-w-0 min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex justify-between items-center my-4">

        <div class="p-1">
            <h1 class="text-3xl font-bold text-gray-800 mb-1">
                Employee Management
            </h1>

            <p class="text-gray-500 text-sm">
                Manage employee data here.
            </p>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="flex items-center gap-3">
            <!-- IMPORT -->
            <?php if (\App\Libraries\PortalAccess::can('compensation')): ?>
            <div>
                <input type="file" id="employeeImportInput" accept=".xlsx,.xls,.csv" class="hidden">
                <button type="button" onclick="document.getElementById('employeeImportInput').click()"
                    class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 px-4 py-3 rounded-lg shadow-sm flex items-center gap-2 transition">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 4v12m0 0l-4-4m4 4l4-4" />
                    </svg>
                    <span class="font-medium"> Import </span>
                </button>
            </div>

            <!-- IMPORT HISTORY -->
            <button type="button" id="importHistoryBtn" onclick="openEmployeeImportHistory()"
                class="bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 px-4 py-3 rounded-lg shadow-sm flex items-center gap-2 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium"> History </span>
            </button>

            <!-- EXPORT -->
            <?php endif; ?>
            <button type="button" id="exportEmployeesBtn" class="bg-[#9764F6] hover:bg-[#8a58e0] text-white px-4 py-3 rounded-lg shadow-sm flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="w-5 h-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M16 8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                <span class="font-medium"> Export </span>
            </button>

            <!-- ADD EMPLOYEE -->
            <button type="button" data-modal-open="employeeModal" onclick="openModalById('employeeModal')" class="bg-[#FF2D2D] hover:bg-[#ff2d2dd2] 
                    text-white px-4 py-3 rounded-lg shadow-sm inline-flex items-center gap-2 whitespace-nowrap h-auto w-auto shrink-0 transition">
                <div class="w-5 h-5 shrink-0 flex items-center justify-center">
                    <img src="<?= base_url('icons/add.png'); ?>" alt="Add" class="w-5 h-5 object-contain block">
                </div>
                <span class="font-medium"> New Employee </span>
            </button>
        </div>
    </div>

    <?= view('components/crud_table', [
        'title' => 'Employee Management',
        'tableId' => 'employeeTable',
        'grid' => 'grid-cols-[2fr_1.5fr_1.5fr_1.2fr_0.9fr_0.8fr_0.8fr_0.8fr_1fr]',
        'columns' => [
            'Employee Name',
            'Position',
            'Department',
            'ID',
            'Type',
            'Leave Total',
            'Leave Left',
            'Status',
            'Action'
        ]
    ]) ?>

    <div id="employeeImportHistoryModal" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,0.5);">
        <div style="background:#fefefe;margin:10vh auto;padding:20px;border:1px solid #888;width:720px;max-width:94vw;box-sizing:border-box;border-radius:10px;">
            <span style="float:right;cursor:pointer;font-size:24px;" onclick="document.getElementById('employeeImportHistoryModal').style.display='none'">&times;</span>
            <h3 class="text-lg font-bold text-gray-800">Employee Import History</h3>
            <div id="employeeImportHistoryList" style="max-height:60vh;overflow:auto;margin-top:10px;">
                <p style="color:#888;font-size:13px;">Loading…</p>
            </div>
        </div>
    </div>
</div>
