<div 
    class="bg-white rounded-2xl shadow-lg p-5 h-[85%] border border-gray-400 relative"
    data-crud-table="<?= esc($tableId) ?>">

    <!-- Header -->
    <div class="flex justify-between items-center mb-5">
        <h2 class="text-lg font-semibold text-gray-700">
            <?= esc($title ?? 'Records') ?>
        </h2>

        <div class="flex items-center gap-4">

            <!-- Search -->
            <div class="relative w-[350px]">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd"
                    d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5
                    6.75 6.75 0 0 0 0-13.5ZM2.25 10.5
                    a8.25 8.25 0 1 1 14.59 5.28l4.69
                    4.69a.75.75 0 1 1-1.06 1.06l-4.69
                    -4.69A8.25 8.25 0 0 1 2.25 10.5Z"
                    clip-rule="evenodd"/>
                    </svg>
                </span>

                <input
                    type="text"
                    placeholder="Search..."
                    class="crud-search w-full bg-gray-200 text-gray-700 text-sm pl-10 pr-5 py-3 rounded-lg outline-none"
                    >
            </div>

            <!-- Filter Button -->
            <button
                class="crud-filter-toggle flex items-center gap-3 bg-gray-600 hover:bg-gray-700 text-white text-sm px-6 py-3 rounded-lg shadow-md">
                <span>Filters</span>
                <img src="<?= base_url('icons/filter.png') ?>" class="w-4 h-4">
            </button>

        </div>
    </div>


    <!-- FILTER DROPDOWN -->
    <div class="crud-filter-dropdown hidden absolute right-5 top-[70px] bg-white border border-gray-300 shadow-lg rounded-lg p-4 w-[300px] z-50 space-y-3">
        <!-- STATUS -->
        <div>
            <label class="text-xs text-gray-500">Status</label>
            <select data-filter="status" class="w-full border rounded-lg p-2 text-sm">
                <option value="">All</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <!-- DEPARTMENT -->
        <div>
            <label class="text-xs text-gray-500">Department</label>
                <select data-filter="department" class="w-full border rounded-lg p-2 text-sm">
                <option value="">All</option>
            </select>
        </div>

        <!-- EMPLOYMENT TYPE -->
        <div>
            <label class="text-xs text-gray-500">Employment Type</label>
            <select data-filter="employment_type" class="w-full border rounded-lg p-2 text-sm">
                <option value="">All</option>
            </select>
        </div>

        <hr>

        <!-- SORT BY -->
        <div>
            <label class="text-xs text-gray-500">Sort By</label>
            <select data-filter="sort_by" class="w-full border rounded-lg p-2 text-sm">
                <option value="name">Name</option>
                <option value="status">Status</option>
                <option value="department">Department</option>
                <option value="employment_type">Employment Type</option>
            </select>
        </div>

        <!-- SORT ORDER -->
        <div>
            <label class="text-xs text-gray-500">Order</label>
            <select data-filter="sort_order" class="w-full border rounded-lg p-2 text-sm">
                <option value="asc">Ascending</option>
                <option value="desc">Descending</option>
            </select>
        </div>

        <button
            type="button"
            class="crud-clear-filters w-full bg-gray-200 hover:bg-gray-300 text-sm py-2 rounded-lg">
            Clear Filters
        </button>

    </div>

    <!-- TABLE -->
    <div class="flex flex-col h-full">
        <!-- HEAD -->
        <div class="grid <?= esc($grid) ?> bg-[#DCDCDC] text-[#737373] border border-[#9E9E9E] rounded-lg overflow-hidden text-sm font-medium">
            <?php foreach ($columns as $col): ?>
            <div class="p-4 <?= $col === 'Action' ? 'text-center' : '' ?>">
                <?= esc($col) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- BODY -->
        <div class="crud-body text-gray-700"></div>
    </div>

</div>