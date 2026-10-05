<form id="addPayrollForm" action="<?= site_url('client_portal/payroll/create') ?>" method="post">
<?= csrf_field() ?>
    <input type="hidden" name="month_year" value="<?= $selected_month ?>">

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
            <div class="relative">
                <input
                    type="text"
                    id="employeeSearch"
                    placeholder="Search employee..."
                    autocomplete="off"
                    class="w-full border rounded-lg px-3 py-2"
                    required
                >

                <input type="hidden" name="employee_id" id="employee_id" required>

                <!-- Custom dropdown -->
                <div
                    id="employeeDropdown"
                    class="absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50 hidden max-h-56 overflow-auto"
                ></div>
            </div>

            <p id="employee_error" class="text-red-500 text-xs mt-1 hidden">
                Please select a valid employee from the list.
            </p>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
            <input type="text" name="phone" class="w-full border rounded-lg px-3 py-2">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Daily Rate Override <span class="text-gray-400 font-normal">(optional — leave blank to use employee's salary)</span></label>
            <input type="number" step="0.01" name="daily_rate" class="w-full border rounded-lg px-3 py-2" placeholder="Auto from Employee Management">
        </div>

        <div class="mb-4 flex items-center">
            <input type="checkbox" name="dst" id="dst" class="w-4 h-4 mr-2">
            <label for="dst" class="text-sm font-medium text-gray-700">DST</label>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
            <select name="currency" class="w-full border rounded-lg px-3 py-2">
                <option value="PHP">PHP</option>
                <option value="USD">USD</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border rounded-lg px-3 py-2">
                    <option value="Pending">Pending</option>
                    <option value="Paid">Paid</option>
                </select>
        </div>

        <div class="flex justify-end gap-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Save</button>
        </div>
</form>
<script>
    (async () => {
        const form = document.getElementById('addPayrollForm');
        const searchInput = document.getElementById('employeeSearch');
        const employeeIdInput = document.getElementById('employee_id');
        const dropdown = document.getElementById('employeeDropdown');
        const error = document.getElementById('employee_error');

        if (!form || !searchInput || !employeeIdInput || !dropdown || !error) return;

        let employees = [];
        let filtered = [];
        let activeIndex = -1;

        async function loadEmployees() {
            try {
                const res = await fetch("<?= site_url('client_portal/payroll/employees_json') ?>", {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });

                if (!res.ok) throw new Error('Failed to load employees: ' + res.status);

                const data = await res.json();
                employees = Array.isArray(data) ? data : [];
            } catch (err) {
                employees = [];
                console.error('employees_json error:', err);
            }
        }

        function hideDropdown() {
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
            activeIndex = -1;
        }

        function showDropdown() {
            dropdown.classList.remove('hidden');
        }

        function renderDropdown(items) {
            if (!items.length) {
                hideDropdown();
                return;
            }

            dropdown.innerHTML = items.map((emp, i) => `
                <button
                    type="button"
                    data-index="${i}"
                    class="w-full text-left px-3 py-2 hover:bg-gray-100 ${i === activeIndex ? 'bg-gray-100' : ''}"
                >
                    ${emp.label || emp.name || ''}
                </button>
            `).join('');

            showDropdown();
        }

        function exactMatchId(value) {
            const typed = value.trim().toLowerCase();
            const hit = employees.find(e =>
                (e.name || '').trim().toLowerCase() === typed ||
                (e.label || '').trim().toLowerCase() === typed
            );
            return hit ? String(hit.id) : '';
        }

        function updateList() {
            const q = searchInput.value.trim().toLowerCase();

            employeeIdInput.value = exactMatchId(searchInput.value);
            error.classList.add('hidden');

            if (!q) {
                hideDropdown();
                return;
            }

            filtered = employees
                .filter(e =>
                    (e.name || '').toLowerCase().includes(q) ||
                    (e.label || '').toLowerCase().includes(q)
                )
                .slice(0, 8);

            activeIndex = -1;
            renderDropdown(filtered);
        }

        function selectEmployee(emp) {
            searchInput.value = emp.label || emp.name || '';
            employeeIdInput.value = String(emp.id || '');
            error.classList.add('hidden');
            hideDropdown();
        }

        await loadEmployees();

        searchInput.addEventListener('input', updateList);

        searchInput.addEventListener('focus', () => {
            if (!searchInput.value.trim()) hideDropdown();
        });

        searchInput.addEventListener('keydown', (e) => {
            if (dropdown.classList.contains('hidden') || !filtered.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = (activeIndex + 1) % filtered.length;
                renderDropdown(filtered);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = (activeIndex - 1 + filtered.length) % filtered.length;
                renderDropdown(filtered);
            } else if (e.key === 'Enter') {
                if (activeIndex >= 0) {
                    e.preventDefault();
                    selectEmployee(filtered[activeIndex]);
                }
            } else if (e.key === 'Escape') {
                hideDropdown();
            }
        });

        dropdown.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-index]');
            if (!btn) return;
            const idx = Number(btn.dataset.index);
            if (!Number.isNaN(idx) && filtered[idx]) {
                selectEmployee(filtered[idx]);
            }
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#employeeSearch') && !e.target.closest('#employeeDropdown')) {
                hideDropdown();
            }
        });

        form.addEventListener('submit', (e) => {
            if (!employeeIdInput.value) {
                employeeIdInput.value = exactMatchId(searchInput.value);
            }

            if (!employeeIdInput.value) {
                e.preventDefault();
                error.classList.remove('hidden');
                searchInput.focus();
            }
        });
    })();
</script>