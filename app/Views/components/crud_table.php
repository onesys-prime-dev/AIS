<div 
    class="bg-white rounded-2xl shadow-lg p-5 h-full border border-gray-400 relative flex flex-col min-h-0"
    data-crud-table="<?= esc($tableId) ?>">

    <!-- Header -->
    <div class="flex justify-between items-center mb-5">
        <h2 class="text-xl font-bold text-gray-700">
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


    <?php
    // Default to the employee filter set for backward compatibility with
    // any crud_table caller that doesn't pass its own $filters/$sortOptions.
    $filters = $filters ?? [
        [
            'key' => 'status',
            'label' => 'Status',
            'options' => [
                ['value' => '', 'label' => 'All'],
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
            ],
        ],
        // Presence (Online/Away/Offline) is a separate thing from Active/Inactive
        // above — that's account status, this is whether they're at their desk
        // right now. A snapshot at page load, same as Department/Employment
        // Type below; it won't track someone going online after you've opened
        // Filters, since those live-update via presence.js's own polling, not
        // a page reload.
        [
            'key' => 'online_status',
            'label' => 'Presence',
            'options' => [
                ['value' => '', 'label' => 'All'],
                ['value' => 'online', 'label' => 'Online'],
                ['value' => 'away', 'label' => 'Away'],
                ['value' => 'offline', 'label' => 'Offline'],
            ],
        ],
        [
            'key' => 'department',
            'label' => 'Department',
            'autofill' => true,
        ],
        [
            'key' => 'employment_type',
            'label' => 'Employment Type',
            'autofill' => true,
        ],
    ];

    $sortOptions = $sortOptions ?? [
        ['value' => 'name', 'label' => 'Name'],
        ['value' => 'status', 'label' => 'Status'],
        ['value' => 'department', 'label' => 'Department'],
        ['value' => 'employment_type', 'label' => 'Employment Type'],
    ];
    ?>

    <!-- FILTER DROPDOWN -->
    <div class="crud-filter-dropdown hidden absolute right-5 top-[70px] bg-white border border-gray-300 shadow-lg rounded-lg p-4 w-[300px] z-50 space-y-3">

        <?php foreach ($filters as $filter): ?>
        <div>
            <label class="text-xs text-gray-500"><?= esc($filter['label']) ?></label>
            <select
                data-filter="<?= esc($filter['key']) ?>"
                <?= !empty($filter['autofill']) ? 'data-filter-autofill="1"' : '' ?>
                class="w-full border rounded-lg p-2 text-sm">
                <?php foreach ($filter['options'] ?? [['value' => '', 'label' => 'All']] as $opt): ?>
                <option value="<?= esc($opt['value']) ?>"><?= esc($opt['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endforeach; ?>

        <hr>

        <!-- SORT BY -->
        <div>
            <label class="text-xs text-gray-500">Sort By</label>
            <select data-filter="sort_by" class="w-full border rounded-lg p-2 text-sm">
                <?php foreach ($sortOptions as $opt): ?>
                <option value="<?= esc($opt['value']) ?>"><?= esc($opt['label']) ?></option>
                <?php endforeach; ?>
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
    <div class="flex flex-col flex-1 min-h-0">
        <!-- HEAD -->
        <?php
        // gridStyle (raw CSS grid-template-columns value) always renders
        // correctly regardless of whether it's been through the Tailwind
        // build (public/css/main.css is precompiled, not JIT — an arbitrary
        // grid-cols-[...] class that was never compiled in silently does
        // nothing and every cell stacks vertically instead). Prefer it when
        // given; existing callers that only pass a precompiled `grid` class
        // keep working exactly as before.
        ?>
        <div class="grid <?= empty($gridStyle) ? esc($grid) : '' ?> bg-[#DCDCDC] text-[#737373] border border-[#9E9E9E] rounded-lg overflow-hidden text-sm font-medium"
            <?= !empty($gridStyle) ? 'style="grid-template-columns: ' . esc($gridStyle) . ';"' : '' ?>>
            <?php foreach ($columns as $col): ?>
            <div class="p-4 text-base font-bold <?= $col === 'Action' ? 'text-center' : '' ?>">
                <?= esc($col) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- BODY -->
        <div class="crud-body hide-scroll-bar text-gray-700 flex-1 min-h-0 overflow-y-auto"></div>
    </div>

</div>