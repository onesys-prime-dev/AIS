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

    const filterStatus = container.querySelector('[data-filter="status"]');
    const filterDepartment = container.querySelector('[data-filter="department"]');
    const filterEmploymentType = container.querySelector('[data-filter="employment_type"]');

    const filterSortBy = container.querySelector('[data-filter="sort_by"]');
    const filterSortOrder = container.querySelector('[data-filter="sort_order"]');

    const clearFiltersBtn = container.querySelector('.crud-clear-filters');

    function render(list) {

        if (!list.length) {
            body.innerHTML = `
                <div class="flex items-center justify-center h-full text-gray-400">
                    <div class="flex flex-col items-center gap-3">
                        <i class="fa-solid fa-users text-4xl opacity-40"></i>
                        <span class="text-lg font-medium">No records found</span>
                        <span class="text-sm">Try adjusting your search or filters</span>
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


    if (filterDepartment) {
        addUniqueOptions(filterDepartment, rowData.map(e => e.department));
    }

    if (filterEmploymentType) {
        addUniqueOptions(filterEmploymentType, rowData.map(e => e.employment_type));
    }


     // APPLY FILTERS  
    function applyFilters() {

        const q = (searchInput?.value || '').toLowerCase().trim();

        const status = (filterStatus?.value || '').toLowerCase();
        const department = (filterDepartment?.value || '').toLowerCase();
        const employmentType = (filterEmploymentType?.value || '').toLowerCase();

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

    if (filterStatus) {
        filterStatus.addEventListener('change', applyFilters);
    }

    if (filterDepartment) {
        filterDepartment.addEventListener('change', applyFilters);
    }

    if (filterEmploymentType) {
        filterEmploymentType.addEventListener('change', applyFilters);
    }

    if (filterSortBy) {
        filterSortBy.addEventListener('change', applyFilters);
    }

    if (filterSortOrder) {
        filterSortOrder.addEventListener('change', applyFilters);
    }


     //CLEAR FILTERS
    if (clearFiltersBtn) {

        clearFiltersBtn.addEventListener('click', () => {

            if (filterStatus) filterStatus.value = '';
            if (filterDepartment) filterDepartment.value = '';
            if (filterEmploymentType) filterEmploymentType.value = '';

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