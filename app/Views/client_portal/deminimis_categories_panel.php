<!-- app/Views/client_portal/deminimis_categories_panel.php -->
<div class="flex flex-col h-full gap-4" id="deminimis-categories-panel">

    <!-- Toolbar -->
    <div class="flex items-center justify-between gap-3 flex-shrink-0">
        <p class="text-sm text-gray-500 max-w-xl">
            Additional BIR de minimis categories, separate from the Rice/Uniform/Laundry/Medical
            fields already set per-employee. An amount entered above a category's cap becomes
            taxable instead of staying non-taxable — caps here are editable since BIR revises
            them periodically.
        </p>
        <div class="flex items-center gap-2 flex-shrink-0">
            <button type="button" id="deminimis-restore-btn"
                class="text-sm px-4 py-2 rounded-lg border border-[#7b5cff] text-[#7b5cff] hover:bg-[#f5f3ff] transition">
                Restore BIR Defaults
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="flex flex-col flex-1 min-h-0 rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <!-- Head -->
        <div class="grid grid-cols-[2fr_1fr_1fr_80px] bg-[#DCDCDC] text-[#737373] border-b border-[#9E9E9E] text-xs font-bold">
            <div class="p-3">Category</div>
            <div class="p-3">Cap Amount (₱)</div>
            <div class="p-3">Cap Period</div>
            <div class="p-3 text-center">Active</div>
        </div>
        <!-- Body -->
        <div id="deminimis-table-body" class="flex-1 overflow-y-auto scroll-bar divide-y divide-gray-100 text-sm">
            <div class="p-6 text-center text-gray-400">Loading categories...</div>
        </div>
    </div>

    <!-- Footer: Save -->
    <div class="flex justify-end flex-shrink-0">
        <button type="button" id="deminimis-save-btn"
            class="bg-[#7b5cff] hover:bg-[#6249e0] text-white px-6 py-2 rounded-lg shadow text-sm transition">
            Save Categories
        </button>
    </div>
</div>

<script>
(function () {
    const BASE = '<?= site_url('client_portal/payroll') ?>';
    const CSRF_HASH = "<?= csrf_hash() ?>";

    let categories = [];

    const tableBody  = document.getElementById('deminimis-table-body');
    const saveBtn    = document.getElementById('deminimis-save-btn');
    const restoreBtn = document.getElementById('deminimis-restore-btn');

    function renderTable() {
        if (!categories.length) {
            tableBody.innerHTML = `<div class="p-6 text-center text-gray-400">No categories yet — click "Restore BIR Defaults" to seed the common ones.</div>`;
            return;
        }

        tableBody.innerHTML = categories.map((c, i) => `
            <div class="grid grid-cols-[2fr_1fr_1fr_80px] items-center hover:bg-gray-50 transition" data-idx="${i}">
                <div class="p-2 space-y-1">
                    <input type="text" class="w-full border rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-[#7b5cff]"
                        name="label" value="${c.label ?? ''}">
                    <input type="text" class="w-full border rounded px-2 py-1 text-xs text-gray-500 focus:outline-none focus:ring-1 focus:ring-[#7b5cff]"
                        name="note" placeholder="Optional note (e.g. a % or day-count rule with no fixed peso cap)" value="${c.note ?? ''}">
                </div>
                <div class="p-2">
                    <input type="number" step="0.01" class="w-full border rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-[#7b5cff]"
                        name="cap_amount" value="${c.cap_amount ?? 0}">
                </div>
                <div class="p-2">
                    <select name="cap_period" class="w-full border rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-[#7b5cff]">
                        <option value="monthly" ${c.cap_period === 'monthly' ? 'selected' : ''}>Monthly</option>
                        <option value="annual" ${c.cap_period === 'annual' ? 'selected' : ''}>Annual</option>
                        <option value="per_payroll" ${c.cap_period === 'per_payroll' ? 'selected' : ''}>Per Payroll</option>
                    </select>
                </div>
                <div class="p-2 flex justify-center">
                    <input type="checkbox" name="is_active" class="w-4 h-4" ${c.is_active ? 'checked' : ''}>
                </div>
            </div>
        `).join('');

        // Keep the in-memory model synced as fields change, so Save reads
        // current values rather than the stale array.
        tableBody.querySelectorAll('[data-idx]').forEach(row => {
            const idx = Number(row.dataset.idx);
            row.querySelectorAll('input, select').forEach(input => {
                input.addEventListener('change', () => {
                    const field = input.name;
                    categories[idx][field] = input.type === 'checkbox' ? input.checked : input.value;
                });
            });
        });
    }

    async function loadCategories() {
        try {
            const res = await fetch(`${BASE}/deminimis/categories`);
            categories = await res.json();
            renderTable();
        } catch (err) {
            console.error('Failed to load De Minimis categories.', err);
            tableBody.innerHTML = `<div class="p-6 text-center text-red-400">Failed to load categories.</div>`;
        }
    }

    saveBtn.addEventListener('click', async () => {
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';
        try {
            const res = await fetch(`${BASE}/deminimis/saveCategories`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF_HASH },
                body: JSON.stringify({ categories }),
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Save failed.');
            showToast('De Minimis categories saved.', 'success');
        } catch (err) {
            showToast(err.message, 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Categories';
        }
    });

    restoreBtn.addEventListener('click', async () => {
        if (!confirm('This will add/refresh the standard BIR categories. Continue?')) return;

        restoreBtn.disabled = true;
        try {
            const res = await fetch(`${BASE}/deminimis/restoreDefaults`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF_HASH },
            });
            const data = await res.json();
            if (!data.success) throw new Error('Restore failed.');
            categories = data.categories;
            renderTable();
            showToast('BIR defaults restored.', 'success');
        } catch (err) {
            showToast(err.message, 'error');
        } finally {
            restoreBtn.disabled = false;
        }
    });

    loadCategories();
})();
</script>
