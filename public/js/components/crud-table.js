window.createCrudTable = function ({
    tableId,
    data,
    renderRow,
    search,
    filters = null,
    sort = null
}) {

    const rowData = Array.isArray(data) ? data : [];

    const container = document.querySelector(`[data-crud-table="${tableId}"]`);
    if (!container) return;

    const body = container.querySelector('.crud-body');
    const searchInput = container.querySelector('.crud-search');

    const filterToggle = container.querySelector('.crud-filter-toggle');
    const filterDropdown = container.querySelector('.crud-filter-dropdown');

    // Generic filter fields: every [data-filter] select in the dropdown
    // except sort_by/sort_order, keyed by its data-filter value. This lets
    // each crud_table instance (employees, loans, ...) define its own
    // filter set via PHP without any changes needed here.
    const filterEls = {};
    if (filterDropdown) {
        filterDropdown.querySelectorAll('[data-filter]').forEach(el => {
            const key = el.dataset.filter;
            if (key === 'sort_by' || key === 'sort_order') return;
            filterEls[key] = el;
        });
    }

    const filterSortBy = container.querySelector('[data-filter="sort_by"]');
    const filterSortOrder = container.querySelector('[data-filter="sort_order"]');

    const clearFiltersBtn = container.querySelector('.crud-clear-filters');

    function render(list) {

        if (!list.length) {
            body.innerHTML = `
                <div class="min-h-full flex items-center justify-center px-6 py-10 text-gray-400">
                    <div class="flex max-w-md flex-col items-center gap-3 rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-8 py-10 text-center shadow-sm">
                        <i class="fa-solid fa-users text-4xl opacity-40"></i>
                        <span class="text-lg font-semibold text-gray-500">No records found</span>
                        <span class="text-sm leading-6 text-gray-400">Try adjusting your search or filters</span>
                    </div>
                </div>
            `;
            return;
        }

        body.innerHTML = list.map(renderRow).join('');
    }

    function addUniqueOptions(selectEl, values) {

        if (!selectEl) return;

        const uniqueSorted = Array.from(
            new Set(
                values
                .filter(Boolean)
                .map(v => String(v).trim())
            )
        ).sort((a, b) => a.localeCompare(b));

        uniqueSorted.forEach(val => {

            const opt = document.createElement('option');

            opt.value = val;
            opt.textContent = val;

            selectEl.appendChild(opt);

        });

    }


    // Auto-populate any filter select marked data-filter-autofill="1" with
    // the unique values found in that field across the current row data.
    Object.entries(filterEls).forEach(([key, el]) => {
        if (el.dataset.filterAutofill !== undefined) {
            addUniqueOptions(el, rowData.map(r => r[key]));
        }
    });


     // APPLY FILTERS  
    function applyFilters() {

        const q = (searchInput?.value || '').toLowerCase().trim();

        // Generic map of every filter field's current (lowercased) value,
        // keyed by its data-filter name — e.g. values.status, values.loan_category.
        const values = {};
        Object.entries(filterEls).forEach(([key, el]) => {
            values[key] = (el.value || '').toLowerCase();
        });

        const status = values.status || '';
        const department = values.department || '';
        const employmentType = values.employment_type || '';

        const sortBy = filterSortBy?.value || 'name';
        const sortOrder = filterSortOrder?.value || 'asc';


        const filtered = rowData.filter(e => {

            if (typeof filters === 'function') {
                return filters({
                    item: e,
                    query: q,
                    status,
                    department,
                    employmentType,
                    values,
                    sortBy,
                    sortOrder,
                });
            }

            if (typeof search === 'function' && !search(e, q)) {
                return false;
            }

            const fullName = [
                e.first_name ?? '',
                e.middle_name ?? '',
                e.last_name ?? ''
            ].join(' ').toLowerCase();

            const ePosition = (e.position ?? '').toLowerCase();
            const eDepartment = (e.department ?? '').toLowerCase();
            const eNumber = (e.employee_number ?? '').toLowerCase();
            const eType = (e.employment_type ?? '').toLowerCase();

            const eStatus = e.status == 1 ? 'active' : 'inactive';


            const matchSearch =
                typeof search === 'function'
                    ? true
                    : (
                        !q ||
                        [fullName, ePosition, eDepartment, eNumber, eType, eStatus]
                            .join(' ')
                            .includes(q)
                    );


            const matchStatus =
                !status || eStatus === status;

            const matchDepartment =
                !department || eDepartment === department;

            const matchEmploymentType =
                !employmentType || eType === employmentType;


            return (
                matchSearch &&
                matchStatus &&
                matchDepartment &&
                matchEmploymentType
            );

        });


        const sorted = [...filtered].sort((a, b) => {

            if (typeof sort === 'function') {
                return sort({ a, b, sortBy, sortOrder });
            }

            let left = '';
            let right = '';

            if (sortBy === 'name') {

                left = [
                    a.first_name ?? '',
                    a.middle_name ?? '',
                    a.last_name ?? ''
                ].join(' ').trim().toLowerCase();

                right = [
                    b.first_name ?? '',
                    b.middle_name ?? '',
                    b.last_name ?? ''
                ].join(' ').trim().toLowerCase();

            }

            else if (sortBy === 'status') {

                left = String(a.status ?? 0);
                right = String(b.status ?? 0);

            }

            else {

                left = String(a[sortBy] ?? '').toLowerCase();
                right = String(b[sortBy] ?? '').toLowerCase();

            }


            if (left < right) return sortOrder === 'asc' ? -1 : 1;
            if (left > right) return sortOrder === 'asc' ? 1 : -1;

            return 0;

        });


        render(sorted);

    }


    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    Object.values(filterEls).forEach(el => {
        el.addEventListener('change', applyFilters);
    });

    if (filterSortBy) {
        filterSortBy.addEventListener('change', applyFilters);
    }

    if (filterSortOrder) {
        filterSortOrder.addEventListener('change', applyFilters);
    }


     //CLEAR FILTERS
    if (clearFiltersBtn) {

        clearFiltersBtn.addEventListener('click', () => {

            Object.values(filterEls).forEach(el => { el.value = ''; });

            if (filterSortBy) {
                if ([...filterSortBy.options].some(opt => opt.value === 'name')) {
                    filterSortBy.value = 'name';
                } else if (filterSortBy.options.length > 0) {
                    filterSortBy.value = filterSortBy.options[0].value;
                }
            }
            if (filterSortOrder) filterSortOrder.value = 'asc';

            applyFilters();

        });

    }


     //FILTER DROPDOWN
    function closeFilterDropdown() {

        if (!filterDropdown) return;

        filterDropdown.classList.add('hidden');

        if (filterToggle)
            filterToggle.setAttribute('aria-expanded', 'false');

    }


    function toggleFilterDropdown() {

        if (!filterDropdown) return;

        const isHidden = filterDropdown.classList.contains('hidden');

        filterDropdown.classList.toggle('hidden');

        if (filterToggle)
            filterToggle.setAttribute('aria-expanded', isHidden ? 'true' : 'false');

    }


    if (filterToggle) {
        filterToggle.addEventListener('click', toggleFilterDropdown);
    }


    document.addEventListener('click', evt => {

        if (!filterDropdown || !filterToggle) return;

        if (
            !filterDropdown.contains(evt.target) &&
            !filterToggle.contains(evt.target)
        ) {
            closeFilterDropdown();
        }

    });


    document.addEventListener('keydown', evt => {

        if (evt.key === 'Escape') {
            closeFilterDropdown();
        }

    });


    /* ----------------------------------------------------
       INITIAL RENDER
    ---------------------------------------------------- */

    applyFilters();

};