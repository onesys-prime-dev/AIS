<!-- app/Views/client_portal/sss_brackets_panel.php -->
<div class="flex flex-col h-full gap-4" id="sss-brackets-panel">

    <!-- Toolbar -->
    <div class="flex items-center justify-between gap-3 flex-shrink-0">
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-600">Effective Year</label>
            <select id="sss-year-select" class="border rounded-lg px-3 py-2 text-sm bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-[#7b5cff]">
                <!-- Populated via JS -->
            </select>
            <span id="sss-active-badge" class="hidden text-xs bg-green-100 text-green-700 font-semibold px-2 py-0.5 rounded-full">Active</span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" id="sss-set-active-btn"
                class="text-sm px-4 py-2 rounded-lg border border-[#7b5cff] text-[#7b5cff] hover:bg-[#f5f3ff] transition hidden">
                Set as Active
            </button>
            <button type="button" id="sss-add-year-btn"
                class="text-sm px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition">
                + New Year
            </button>
            <button type="button" id="sss-add-row-btn"
                class="text-sm px-4 py-2 rounded-lg bg-[#7b5cff] hover:bg-[#6249e0] text-white shadow transition">
                + Add Bracket
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="flex flex-col flex-1 min-h-0 rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <!-- Head -->
        <div class="grid grid-cols-[1fr_1fr_1fr_1fr_1fr_1fr_1fr_80px] bg-[#DCDCDC] text-[#737373] border-b border-[#9E9E9E] text-xs font-bold">
            <div class="p-3">Range Start (₱)</div>
            <div class="p-3">Range End (₱)</div>
            <div class="p-3">MSC (₱)</div>
            <div class="p-3">EE Regular</div>
            <div class="p-3">EE WISP</div>
            <div class="p-3">ER Regular</div>
            <div class="p-3">ER WISP</div>
            <div class="p-3 text-center">Action</div>
        </div>
        <!-- Body -->
        <div id="sss-table-body" class="flex-1 overflow-y-auto scroll-bar divide-y divide-gray-100 text-sm">
            <div class="p-6 text-center text-gray-400">Loading brackets...</div>
        </div>
    </div>

    <!-- Footer: Save -->
    <div class="flex justify-end flex-shrink-0">
        <button type="button" id="sss-save-btn"
            class="bg-[#7b5cff] hover:bg-[#6249e0] text-white px-6 py-2 rounded-lg shadow text-sm transition">
            Save Brackets
        </button>
    </div>
</div>

<!-- Add New Year Modal -->
<div id="sss-year-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-80">
        <h4 class="font-semibold text-lg mb-4">Create New Year Table</h4>
        <label class="text-sm text-gray-600 block mb-1">Year</label>
        <input type="number" id="sss-new-year-input" class="w-full border rounded-lg p-2 mb-2 text-sm" placeholder="e.g., 2026" min="2020" max="2099">
        <label class="text-sm text-gray-600 block mb-1 mt-3">Copy from year</label>
        <select id="sss-copy-from-select" class="w-full border rounded-lg p-2 text-sm mb-4">
            <option value="">— blank table —</option>
        </select>
        <div class="flex justify-end gap-2">
            <button type="button" id="sss-year-modal-cancel" class="px-4 py-2 text-sm rounded-lg border hover:bg-gray-50">Cancel</button>
            <button type="button" id="sss-year-modal-confirm" class="px-4 py-2 text-sm rounded-lg bg-[#7b5cff] text-white hover:bg-[#6249e0]">Create</button>
        </div>
    </div>
</div>

<script>
(function () {
    const BASE = '<?= site_url('client_portal/payroll') ?>';

    // ── State ──────────────────────────────────────────────────────────────
    let years   = [];   // [{year, is_active}]
    let current = null; // selected year
    let rows    = [];   // bracket rows for current year (mutable copy)

    // ── DOM refs ───────────────────────────────────────────────────────────
    const yearSelect     = document.getElementById('sss-year-select');
    const activeBadge    = document.getElementById('sss-active-badge');
    const setActiveBtn   = document.getElementById('sss-set-active-btn');
    const addYearBtn     = document.getElementById('sss-add-year-btn');
    const addRowBtn      = document.getElementById('sss-add-row-btn');
    const saveBtn        = document.getElementById('sss-save-btn');
    const tableBody      = document.getElementById('sss-table-body');
    const yearModal      = document.getElementById('sss-year-modal');
    const newYearInput   = document.getElementById('sss-new-year-input');
    const copyFromSelect = document.getElementById('sss-copy-from-select');
    const modalCancel    = document.getElementById('sss-year-modal-cancel');
    const modalConfirm   = document.getElementById('sss-year-modal-confirm');

    // ── Helpers ────────────────────────────────────────────────────────────
    function fmt(v) { return v === null || v === '' ? '∞' : Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    function numInput(val, name, step = '0.01') {
        return `<input type="number" step="${step}" class="w-full border rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-[#7b5cff]"
                    name="${name}" value="${val ?? ''}" placeholder="${name === 'range_end' ? '∞' : '0'}">`;
    }

    // ── Render table ───────────────────────────────────────────────────────
    function renderTable() {
        if (!rows.length) {
            tableBody.innerHTML = `<div class="p-6 text-center text-gray-400">No brackets yet. Click "+ Add Bracket" to start.</div>`;
            return;
        }

        tableBody.innerHTML = rows.map((r, i) => `
            <div class="grid grid-cols-[1fr_1fr_1fr_1fr_1fr_1fr_1fr_80px] items-center hover:bg-gray-50 transition" data-idx="${i}">
                <div class="p-2">${numInput(r.range_start,           'range_start')}</div>
                <div class="p-2">${numInput(r.range_end,             'range_end')}</div>
                <div class="p-2">${numInput(r.monthly_salary_credit, 'monthly_salary_credit')}</div>
                <div class="p-2">${numInput(r.ee_regular,            'ee_regular')}</div>
                <div class="p-2">${numInput(r.ee_wisp,               'ee_wisp')}</div>
                <div class="p-2">${numInput(r.er_regular,            'er_regular')}</div>
                <div class="p-2">${numInput(r.er_wisp,               'er_wisp')}</div>
                <div class="p-2 flex justify-center">
                    <button type="button" class="sss-delete-row text-red-400 hover:text-red-600 transition p-1" data-idx="${i}" title="Remove row">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0a2 2 0 00-2-2H9a2 2 0 00-2 2m10 0H5"/>
                        </svg>
                    </button>
                </div>
            </div>
        `).join('');

        // Sync input changes back to rows[]
        tableBody.querySelectorAll('input').forEach(inp => {
            inp.addEventListener('change', e => {
                const row = e.target.closest('[data-idx]');
                const idx = parseInt(row.dataset.idx);
                const field = e.target.name;
                rows[idx][field] = e.target.value === '' ? null : parseFloat(e.target.value);
            });
        });

        // Delete buttons
        tableBody.querySelectorAll('.sss-delete-row').forEach(btn => {
            btn.addEventListener('click', e => {
                const idx = parseInt(btn.dataset.idx);
                rows.splice(idx, 1);
                renderTable();
            });
        });
    }

    // ── Load years list ────────────────────────────────────────────────────
    async function loadYears() {
        const res  = await fetch(`${BASE}/sss/years`);
        years      = await res.json(); // [{year, is_active}]

        yearSelect.innerHTML = years.map(y =>
            `<option value="${y.year}">${y.year}</option>`
        ).join('');

        // Populate copy-from dropdown in modal
        copyFromSelect.innerHTML = `<option value="">— blank table —</option>` +
            years.map(y => `<option value="${y.year}">${y.year}</option>`).join('');

        // Default to active year, or first
        const active = years.find(y => y.is_active);
        current = active ? active.year : (years[0]?.year ?? null);
        if (current) {
            yearSelect.value = current;
            loadBrackets(current);
        }
        updateActiveBadge();
    }

    // ── Load brackets for a year ───────────────────────────────────────────
    async function loadBrackets(year) {
        tableBody.innerHTML = `<div class="p-6 text-center text-gray-400">Loading...</div>`;
        const res = await fetch(`${BASE}/sss/brackets?year=${year}`);
        rows = await res.json();
        renderTable();
    }

    // ── Active badge ───────────────────────────────────────────────────────
    function updateActiveBadge() {
        const sel = years.find(y => y.year == current);
        if (sel && parseInt(sel.is_active) === 1) {
            activeBadge.classList.remove('hidden');
            setActiveBtn.classList.add('hidden');
        } else {
            activeBadge.classList.add('hidden');
            setActiveBtn.classList.remove('hidden');
        }
    }

    // ── Save brackets ──────────────────────────────────────────────────────
    async function saveBrackets() {
        saveBtn.textContent = 'Saving...';
        saveBtn.disabled = true;
        try {
            const res = await fetch(`${BASE}/sss/saveBrackets`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ year: current, brackets: rows })
            });
            const data = await res.json();
            if (data.success) {
                saveBtn.textContent = 'Saved ✓';
                setTimeout(() => { saveBtn.textContent = 'Save Brackets'; }, 2000);
            } else {
                alert('Save failed: ' + (data.message ?? 'Unknown error'));
            }
        } catch (e) {
            alert('Network error. Please try again.');
        } finally {
            saveBtn.disabled = false;
        }
    }

    // ── Event bindings ─────────────────────────────────────────────────────
    yearSelect.addEventListener('change', () => {
        current = yearSelect.value;
        loadBrackets(current);
        updateActiveBadge();
    });

    addRowBtn.addEventListener('click', () => {
        rows.push({ range_start: null, range_end: null, monthly_salary_credit: null, ee_regular: 0, ee_wisp: 0, er_regular: 0, er_wisp: 0 });
        renderTable();
        // Scroll to bottom
        tableBody.scrollTop = tableBody.scrollHeight;
    });

    saveBtn.addEventListener('click', saveBrackets);

    setActiveBtn.addEventListener('click', async () => {
        if (!confirm(`Set ${current} as the active SSS table?`)) return;
        const res = await fetch(`${BASE}/sss/setActive`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ year: current })
        });
        const data = await res.json();
        if (data.success) {
            years.forEach(y => y.is_active = (y.year == current));
            updateActiveBadge();
        }
    });

    // Add New Year modal
    addYearBtn.addEventListener('click', () => {
        newYearInput.value = new Date().getFullYear() + 1;
        yearModal.classList.remove('hidden');
    });
    modalCancel.addEventListener('click', () => yearModal.classList.add('hidden'));
    modalConfirm.addEventListener('click', async () => {
        const yr = parseInt(newYearInput.value);
        if (!yr || yr < 2020 || yr > 2099) { alert('Please enter a valid year.'); return; }
        if (years.find(y => y.year == yr)) { alert(`Year ${yr} already exists.`); return; }

        const copyFrom = copyFromSelect.value || null;
        const res = await fetch(`${BASE}/sss/createYear`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ year: yr, copy_from: copyFrom })
        });
        const data = await res.json();
        if (data.success) {
            yearModal.classList.add('hidden');
            await loadYears();
            yearSelect.value = yr;
            current = yr;
            loadBrackets(yr);
            updateActiveBadge();
        } else {
            alert('Error: ' + (data.message ?? 'Could not create year.'));
        }
    });

    // ── Init ───────────────────────────────────────────────────────────────
    loadYears();
})();
</script>
