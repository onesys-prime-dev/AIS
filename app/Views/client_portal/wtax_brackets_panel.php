<!-- app/Views/client_portal/wtax_brackets_panel.php -->
<div class="flex flex-col h-full gap-4" id="wtax-brackets-panel">

    <!-- Toolbar -->
    <div class="flex items-center justify-between gap-3 flex-shrink-0">
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-gray-600">Effective Year</label>
            <select id="wtax-year-select" class="border rounded-lg px-3 py-2 text-sm bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-[#7b5cff]">
                <!-- Populated via JS -->
            </select>
            <span id="wtax-active-badge" class="hidden text-xs bg-green-100 text-green-700 font-semibold px-2 py-0.5 rounded-full">Active</span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" id="wtax-set-active-btn"
                class="text-sm px-4 py-2 rounded-lg border border-[#7b5cff] text-[#7b5cff] hover:bg-[#f5f3ff] transition hidden">
                Set as Active
            </button>
            <button type="button" id="wtax-add-year-btn"
                class="text-sm px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition">
                + New Year
            </button>
            <button type="button" id="wtax-add-row-btn"
                class="text-sm px-4 py-2 rounded-lg bg-[#7b5cff] hover:bg-[#6249e0] text-white shadow transition">
                + Add Bracket
            </button>
        </div>
    </div>

    <!-- Explainer chip -->
    <div class="flex-shrink-0 bg-blue-50 border border-blue-100 text-blue-700 text-xs rounded-lg px-4 py-2 flex items-start gap-2">
        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/>
        </svg>
        <span>
            <strong>Formula:</strong> Withholding Tax = <strong>(Excess &times; % Over) + Fixed Tax</strong><br>
            where <em>Excess = Taxable Income &minus; Excess Over</em>. &nbsp;"% Over" is a decimal: 0.15 = 15%, 0.20 = 20%, etc.
        </span>
    </div>

    <!-- Table -->
    <div class="flex flex-col flex-1 min-h-0 rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <!-- Head -->
        <div class="grid grid-cols-[1fr_1fr_1fr_1fr_1fr_80px] bg-[#DCDCDC] text-[#737373] border-b border-[#9E9E9E] text-xs font-bold">
            <div class="p-3">Min Amount (₱)</div>
            <div class="p-3">Max Amount (₱)</div>
            <div class="p-3">Excess Over (₱) <span class="font-normal opacity-70">= Min</span></div>
            <div class="p-3">Fixed Tax (₱)</div>
            <div class="p-3">% Over (decimal)</div>
            <div class="p-3 text-center">Action</div>
        </div>
        <!-- Body -->
        <div id="wtax-table-body" class="flex-1 overflow-y-auto scroll-bar divide-y divide-gray-100 text-sm">
            <div class="p-6 text-center text-gray-400">Loading brackets...</div>
        </div>
    </div>

    <!-- Footer: computed preview + save -->
    <div class="flex items-center justify-between flex-shrink-0">
        <button type="button" id="wtax-preview-btn"
            class="text-sm text-[#7b5cff] hover:underline">
            Preview sample computation →
        </button>
        <button type="button" id="wtax-save-btn"
            class="bg-[#7b5cff] hover:bg-[#6249e0] text-white px-6 py-2 rounded-lg shadow text-sm transition">
            Save Brackets
        </button>
    </div>

    <!-- Preview Panel (hidden by default) -->
    <div id="wtax-preview-panel" class="hidden flex-shrink-0 bg-gray-50 border border-gray-200 rounded-xl p-4">
        <div class="flex items-center gap-3 mb-3">
            <label class="text-sm font-medium text-gray-600">Monthly Taxable Income (₱)</label>
            <input type="number" id="wtax-preview-income" class="border rounded-lg px-3 py-1.5 text-sm w-40 focus:outline-none focus:ring-1 focus:ring-[#7b5cff]" value="50000" step="1000">
            <button type="button" id="wtax-compute-btn" class="text-sm px-4 py-1.5 bg-gray-700 hover:bg-gray-800 text-white rounded-lg transition">Compute</button>
        </div>
        <div id="wtax-preview-result" class="text-sm text-gray-700"></div>
    </div>
</div>

<!-- Add New Year Modal -->
<div id="wtax-year-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-80">
        <h4 class="font-semibold text-lg mb-4">Create New Year Table</h4>
        <label class="text-sm text-gray-600 block mb-1">Year</label>
        <input type="number" id="wtax-new-year-input" class="w-full border rounded-lg p-2 mb-2 text-sm" placeholder="e.g., 2026" min="2020" max="2099">
        <label class="text-sm text-gray-600 block mb-1 mt-3">Copy from year</label>
        <select id="wtax-copy-from-select" class="w-full border rounded-lg p-2 text-sm mb-4">
            <option value="">— blank table —</option>
        </select>
        <div class="flex justify-end gap-2">
            <button type="button" id="wtax-year-modal-cancel" class="px-4 py-2 text-sm rounded-lg border hover:bg-gray-50">Cancel</button>
            <button type="button" id="wtax-year-modal-confirm" class="px-4 py-2 text-sm rounded-lg bg-[#7b5cff] text-white hover:bg-[#6249e0]">Create</button>
        </div>
    </div>
</div>

<script>
(function () {
    const BASE = '<?= site_url('client_portal/payroll') ?>';

    let years   = [];
    let current = null;
    let rows    = [];

    const yearSelect     = document.getElementById('wtax-year-select');
    const activeBadge    = document.getElementById('wtax-active-badge');
    const setActiveBtn   = document.getElementById('wtax-set-active-btn');
    const addYearBtn     = document.getElementById('wtax-add-year-btn');
    const addRowBtn      = document.getElementById('wtax-add-row-btn');
    const saveBtn        = document.getElementById('wtax-save-btn');
    const tableBody      = document.getElementById('wtax-table-body');
    const yearModal      = document.getElementById('wtax-year-modal');
    const newYearInput   = document.getElementById('wtax-new-year-input');
    const copyFromSelect = document.getElementById('wtax-copy-from-select');
    const modalCancel    = document.getElementById('wtax-year-modal-cancel');
    const modalConfirm   = document.getElementById('wtax-year-modal-confirm');
    const previewBtn     = document.getElementById('wtax-preview-btn');
    const previewPanel   = document.getElementById('wtax-preview-panel');
    const computeBtn     = document.getElementById('wtax-compute-btn');
    const previewIncome  = document.getElementById('wtax-preview-income');
    const previewResult  = document.getElementById('wtax-preview-result');

    // ── Render ─────────────────────────────────────────────────────────────
    function renderTable() {
        if (!rows.length) {
            tableBody.innerHTML = `<div class="p-6 text-center text-gray-400">No brackets yet. Click "+ Add Bracket" to start.</div>`;
            return;
        }

        tableBody.innerHTML = rows.map((r, i) => {
            const minFmt = r.min_amount != null
                ? Number(r.min_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })
                : '—';
            return `
            <div class="grid grid-cols-[1fr_1fr_1fr_1fr_1fr_80px] items-center hover:bg-gray-50 transition" data-idx="${i}">
                <div class="p-2">
                    <input type="number" step="0.01" name="min_amount" class="w-full border rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-[#7b5cff]"
                        value="${r.min_amount ?? ''}">
                </div>
                <div class="p-2">
                    <input type="number" step="0.01" name="max_amount" class="w-full border rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-[#7b5cff]"
                        value="${r.max_amount ?? ''}" placeholder="∞ (leave blank for top bracket)">
                </div>
                <div class="p-2">
                    <div class="w-full border border-gray-200 bg-gray-50 rounded px-2 py-1 text-sm text-gray-500 select-none" title="Excess = Taxable Income − this value">
                        ₱${minFmt}
                    </div>
                </div>
                <div class="p-2">
                    <input type="number" step="0.01" name="fixed_tax" class="w-full border rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-[#7b5cff]"
                        value="${r.fixed_tax ?? '0'}">
                </div>
                <div class="p-2">
                    <input type="number" step="0.0001" name="percent_over" class="w-full border rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-[#7b5cff]"
                        value="${r.percent_over ?? '0'}" placeholder="0.0000">
                </div>
                <div class="p-2 flex justify-center">
                    <button type="button" class="wtax-delete-row text-red-400 hover:text-red-600 transition p-1" data-idx="${i}" title="Remove row">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0a2 2 0 00-2-2H9a2 2 0 00-2 2m10 0H5"/>
                        </svg>
                    </button>
                </div>
            </div>
        `; }).join('');

        tableBody.querySelectorAll('input').forEach(inp => {
            inp.addEventListener('change', e => {
                const row   = e.target.closest('[data-idx]');
                const idx   = parseInt(row.dataset.idx);
                const field = e.target.name;
                rows[idx][field] = e.target.value === '' ? null : parseFloat(e.target.value);
                // Keep Excess Over display in sync with min_amount
                if (field === 'min_amount') {
                    const excessCell = row.querySelector('[title="Excess = Taxable Income − this value"]');
                    if (excessCell) {
                        const v = rows[idx].min_amount;
                        excessCell.textContent = v != null
                            ? '₱' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2 })
                            : '—';
                    }
                }
            });
        });

        tableBody.querySelectorAll('.wtax-delete-row').forEach(btn => {
            btn.addEventListener('click', () => {
                rows.splice(parseInt(btn.dataset.idx), 1);
                renderTable();
            });
        });
    }

    // ── Load years ─────────────────────────────────────────────────────────
    async function loadYears() {
        const res = await fetch(`${BASE}/wtax/years`);
        years     = await res.json();

        yearSelect.innerHTML = years.map(y =>
            `<option value="${y.year}">${y.year}</option>`
        ).join('');

        copyFromSelect.innerHTML = `<option value="">— blank table —</option>` +
            years.map(y => `<option value="${y.year}">${y.year}</option>`).join('');

        const active = years.find(y => y.is_active);
        current = active ? active.year : (years[0]?.year ?? null);
        if (current) { yearSelect.value = current; loadBrackets(current); }
        updateActiveBadge();
    }

    async function loadBrackets(year) {
        tableBody.innerHTML = `<div class="p-6 text-center text-gray-400">Loading...</div>`;
        const res = await fetch(`${BASE}/wtax/brackets?year=${year}`);
        rows = await res.json();
        renderTable();
    }

    function updateActiveBadge() {
        const sel = years.find(y => y.year == current);
        if (sel?.is_active) {
            activeBadge.classList.remove('hidden');
            setActiveBtn.classList.add('hidden');
        } else {
            activeBadge.classList.add('hidden');
            setActiveBtn.classList.remove('hidden');
        }
    }

    // ── Save ───────────────────────────────────────────────────────────────
    async function saveBrackets() {
        saveBtn.textContent = 'Saving...';
        saveBtn.disabled = true;
        try {
            const res = await fetch(`${BASE}/wtax/saveBrackets`, {
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

    // ── Preview computation ────────────────────────────────────────────────
    function computeWtax(income) {
        // Find the matching bracket
        const bracket = [...rows]
            .sort((a, b) => (a.min_amount ?? 0) - (b.min_amount ?? 0))
            .find(r => income >= (r.min_amount ?? 0) && (r.max_amount === null || income <= r.max_amount));

        if (!bracket) return null;

        const fixed   = parseFloat(bracket.fixed_tax)   || 0;
        const pct     = parseFloat(bracket.percent_over) || 0;
        const minAmt  = parseFloat(bracket.min_amount)   || 0;
        const tax     = fixed + (income - minAmt) * pct;
        return { tax, fixed, pct, minAmt, bracket };
    }

    function renderPreview() {
        const income = parseFloat(previewIncome.value);
        if (isNaN(income) || income < 0) { previewResult.innerHTML = '<span class="text-red-500">Please enter a valid income.</span>'; return; }

        const result = computeWtax(income);
        if (!result) { previewResult.innerHTML = '<span class="text-red-500">No matching bracket found. Check your bracket table.</span>'; return; }

        const { tax, fixed, pct, minAmt } = result;
        const php = v => `₱${v.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

        const excess    = income - minAmt;
        const excessTax = excess * pct;
        previewResult.innerHTML = `
            <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                <span class="text-gray-500">Taxable income:</span>
                <span class="font-medium">${php(income)}</span>

                <span class="text-gray-500">Excess over (${php(minAmt)}):</span>
                <span>${php(excess)}</span>

                <span class="text-gray-500">Excess × ${(pct*100).toFixed(2)}%:</span>
                <span>${php(excessTax)}</span>

                <span class="text-gray-500">Fixed tax:</span>
                <span>+ ${php(fixed)}</span>

                <span class="col-span-2 border-t border-gray-200 my-1"></span>

                <span class="text-gray-600 font-semibold">= (${php(excessTax)} × ${(pct*100).toFixed(2)}%) + ${php(fixed)}</span>
                <span class="font-bold text-[#7b5cff] text-base">${php(tax)}</span>
            </div>
        `;
    }

    // ── Events ─────────────────────────────────────────────────────────────
    yearSelect.addEventListener('change', () => {
        current = yearSelect.value;
        loadBrackets(current);
        updateActiveBadge();
    });

    addRowBtn.addEventListener('click', () => {
        rows.push({ min_amount: null, max_amount: null, fixed_tax: 0, percent_over: 0 });
        renderTable();
        tableBody.scrollTop = tableBody.scrollHeight;
    });

    saveBtn.addEventListener('click', saveBrackets);

    setActiveBtn.addEventListener('click', async () => {
        if (!confirm(`Set ${current} as the active withholding tax table?`)) return;
        const res = await fetch(`${BASE}/wtax/setActive`, {
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

    addYearBtn.addEventListener('click', () => {
        newYearInput.value = new Date().getFullYear() + 1;
        yearModal.classList.remove('hidden');
    });
    modalCancel.addEventListener('click', () => yearModal.classList.add('hidden'));
    modalConfirm.addEventListener('click', async () => {
        const yr = parseInt(newYearInput.value);
        if (!yr || yr < 2020 || yr > 2099) { alert('Please enter a valid year.'); return; }
        if (years.find(y => y.year == yr)) { alert(`Year ${yr} already exists.`); return; }

        const res = await fetch(`${BASE}/wtax/createYear`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ year: yr, copy_from: copyFromSelect.value || null })
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

    previewBtn.addEventListener('click', () => {
        previewPanel.classList.toggle('hidden');
        if (!previewPanel.classList.contains('hidden')) renderPreview();
    });
    computeBtn.addEventListener('click', renderPreview);
    previewIncome.addEventListener('keydown', e => { if (e.key === 'Enter') renderPreview(); });

    // ── Init ───────────────────────────────────────────────────────────────
    loadYears();
})();
</script>
