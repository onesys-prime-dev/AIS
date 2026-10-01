<div class="h-full flex flex-col min-h-0 overflow-hidden">

    <div class="flex justify-between items-center my-4">
        <div class="p-1">
            <h1 class="text-3xl font-bold mb-1" style="color:var(--content-text,#1f2937);">Audit Log</h1>
            <p class="text-sm" style="color:var(--content-text-muted,#6b7280);">Every payroll, employee, and account change — who did it, when, and what changed.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex items-end gap-3 mb-3 flex-wrap">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">Module</label>
            <select id="alModule" class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400 w-44">
                <option value="">All modules</option>
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">Action</label>
            <select id="alAction" class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400 w-36">
                <option value="">All actions</option>
                <option value="created">Created</option>
                <option value="updated">Updated</option>
                <option value="deleted">Deleted</option>
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">From</label>
            <input type="date" id="alFrom" class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500">To</label>
            <input type="date" id="alTo" class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
        <div class="flex flex-col gap-1 flex-1 min-w-[180px]">
            <label class="text-xs font-medium text-gray-500">Search</label>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                </svg>
                <input type="text" id="alSearch" placeholder="Search description or user…"
                    class="pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-400 w-full">
            </div>
        </div>
        <button type="button" id="alApply" class="px-4 py-2 bg-[#7b5cff] hover:bg-[#6a4de0] text-white rounded-lg text-sm font-semibold transition">Apply</button>
        <button type="button" id="alReset" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold transition">Reset</button>
    </div>

    <!-- Table -->
    <div class="bg-white border border-gray-400 rounded-2xl shadow-lg flex flex-col min-h-0 overflow-hidden p-5 gap-3 flex-1">
        <div class="grid grid-cols-[1.4fr_1.1fr_0.9fr_1.1fr_2.5fr]
                    bg-[#DCDCDC] text-[#737373] border border-[#9E9E9E] rounded-lg overflow-hidden text-sm font-bold">
            <div class="p-4">Date &amp; Time</div>
            <div class="p-4">User</div>
            <div class="p-4">Action</div>
            <div class="p-4">Module</div>
            <div class="p-4">Description</div>
        </div>

        <div id="alRows" class="overflow-y-auto flex-1 text-gray-700 hide-scroll-bar"></div>

        <div id="alEmpty" class="hidden py-12 text-center text-gray-400 text-sm">
            No activity recorded for these filters.
        </div>
        <div id="alLoading" class="hidden py-12 text-center text-gray-400 text-sm">
            Loading…
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between pt-2 border-t border-gray-100">
            <span id="alCount" class="text-xs text-gray-500"></span>
            <div class="flex items-center gap-2">
                <button type="button" id="alPrev" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold transition disabled:opacity-40 disabled:cursor-not-allowed">Prev</button>
                <span id="alPageLabel" class="text-xs text-gray-500"></span>
                <button type="button" id="alNext" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold transition disabled:opacity-40 disabled:cursor-not-allowed">Next</button>
            </div>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div id="alDetailModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; overflow:auto;">
    <div style="background:#fff; margin:4% auto; padding:24px; max-width:640px; border-radius:14px; box-shadow:0 8px 32px rgba(0,0,0,.25); position:relative; max-height:88vh; overflow-y:auto;">
        <button onclick="document.getElementById('alDetailModal').style.display='none'"
            style="position:absolute; top:12px; right:16px; font-size:24px; background:none; border:none; cursor:pointer; color:#666;">&times;</button>
        <h3 style="font-size:16px;font-weight:700;color:#374151;margin-bottom:16px;">Log Entry Detail</h3>
        <div id="alDetailContent" style="min-height:60px;"></div>
    </div>
</div>

<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        ['alDetailModal'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el && el.parentElement !== document.body) document.body.appendChild(el);
        });
    });

    const AUDIT_URL = '<?= site_url('client_portal/payroll/getAuditLog') ?>';

    const MODULE_LABELS = {
        payroll: 'Payroll',
        employee_payslips: 'Payslips',
        payroll_settings: 'Payroll Settings',
        sss_brackets: 'SSS Brackets',
        wtax_brackets_monthly: 'Withholding Tax Brackets',
        wtax_brackets: 'Withholding Tax Brackets',
        employees: 'Employees',
        employee_accounts: 'Employee Accounts',
        employee_government_ids: 'Government IDs',
        employee_positions: 'Positions',
        attendance: 'Attendance',
        tasks: 'Tasks',
        saved_tasks: 'Task Templates',
        requests: 'Requests',
        users: 'Users',
        auth_groups_users: 'Roles',
        announcements: 'Announcements',
    };

    const ACTION_BADGE = {
        created: 'bg-green-100 text-green-700',
        updated: 'bg-blue-100 text-blue-700',
        deleted: 'bg-red-100 text-red-700',
    };

    function esc(s) {
        return String(s ?? '').replace(/[&<>"]/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[c]));
    }

    function fmtDateTime(s) {
        if (!s) return '';
        const d = new Date(String(s).replace(' ', 'T'));
        return isNaN(d) ? s : d.toLocaleString('en-US', { month:'short', day:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
    }

    function moduleLabel(m) {
        return MODULE_LABELS[m] || (m ? m.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '—');
    }

    function renderValueDiff(oldValues, newValues) {
        const keys = Array.from(new Set([...Object.keys(oldValues || {}), ...Object.keys(newValues || {})]));
        if (!keys.length) return '<p style="color:#9ca3af;font-size:13px;">No field-level changes recorded.</p>';
        return '<div style="font-size:13px;color:#4b5563;">' + keys.map(k => {
            const ov = oldValues && oldValues[k] !== undefined ? oldValues[k] : '—';
            const nv = newValues && newValues[k] !== undefined ? newValues[k] : '—';
            return `<div style="padding:6px 0;border-bottom:1px solid #f1f5f9;"><strong>${esc(k)}:</strong> ${esc(ov)} &rarr; ${esc(nv)}</div>`;
        }).join('') + '</div>';
    }

    let state = { page: 1, total: 0, perPage: 25 };

    function buildQuery() {
        const params = new URLSearchParams();
        params.set('page', state.page);
        const module = document.getElementById('alModule').value;
        const action = document.getElementById('alAction').value;
        const search = document.getElementById('alSearch').value.trim();
        const from   = document.getElementById('alFrom').value;
        const to     = document.getElementById('alTo').value;
        if (module) params.set('module', module);
        if (action) params.set('action', action);
        if (search) params.set('search', search);
        if (from && to) { params.set('from', from); params.set('to', to); }
        return params.toString();
    }

    function renderRow(row) {
        const badgeClass = ACTION_BADGE[row.action] || 'bg-gray-100 text-gray-600';
        return `
        <div class="grid grid-cols-[1.4fr_1.1fr_0.9fr_1.1fr_2.5fr] items-center border-b hover:bg-gray-50 transition-colors cursor-pointer al-row"
             data-id="${row.id}">
            <div class="p-4 whitespace-nowrap text-sm">${fmtDateTime(row.created_at)}</div>
            <div class="p-4 text-sm font-medium text-gray-800">${esc(row.username)}</div>
            <div class="p-4"><span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${badgeClass}">${esc(row.action)}</span></div>
            <div class="p-4 text-sm">${esc(moduleLabel(row.module))}</div>
            <div class="p-4 text-sm truncate">${esc(row.description)}</div>
        </div>`;
    }

    let currentRows = [];

    function render(json) {
        const container = document.getElementById('alRows');
        const empty     = document.getElementById('alEmpty');
        currentRows = json.rows || [];

        if (!currentRows.length) {
            container.innerHTML = '';
            empty.classList.remove('hidden');
        } else {
            empty.classList.add('hidden');
            container.innerHTML = currentRows.map(renderRow).join('');
            container.querySelectorAll('.al-row').forEach(el => {
                el.addEventListener('click', function () {
                    const row = currentRows.find(r => String(r.id) === this.dataset.id);
                    if (row) openDetail(row);
                });
            });
        }

        state.total   = json.total || 0;
        state.perPage = json.perPage || 25;
        const totalPages = Math.max(1, Math.ceil(state.total / state.perPage));
        document.getElementById('alPageLabel').textContent = `Page ${state.page} of ${totalPages}`;
        document.getElementById('alCount').textContent = `${state.total} entr${state.total === 1 ? 'y' : 'ies'}`;
        document.getElementById('alPrev').disabled = state.page <= 1;
        document.getElementById('alNext').disabled = state.page >= totalPages;
    }

    function openDetail(row) {
        const modal   = document.getElementById('alDetailModal');
        const content = document.getElementById('alDetailContent');
        const badgeClass = ACTION_BADGE[row.action] || 'bg-gray-100 text-gray-600';
        content.innerHTML = `
            <div style="margin-bottom:12px;">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${badgeClass}">${esc(row.action)}</span>
                <span style="margin-left:8px;color:#6b7280;font-size:13px;">${esc(moduleLabel(row.module))} &middot; ${fmtDateTime(row.created_at)}</span>
            </div>
            <div style="font-size:14px;font-weight:600;color:#374151;margin-bottom:4px;">${esc(row.description)}</div>
            <div style="font-size:12px;color:#9ca3af;margin-bottom:12px;">By ${esc(row.username)}${row.ip_address ? ' &middot; ' + esc(row.ip_address) : ''}</div>
            ${renderValueDiff(row.old_values, row.new_values)}
        `;
        modal.style.display = 'block';
    }

    async function load() {
        const loading = document.getElementById('alLoading');
        const container = document.getElementById('alRows');
        const empty = document.getElementById('alEmpty');
        loading.classList.remove('hidden');
        empty.classList.add('hidden');
        container.innerHTML = '';
        try {
            const res  = await fetch(AUDIT_URL + '?' + buildQuery());
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.error || 'Could not load audit log.');

            const moduleSelect = document.getElementById('alModule');
            if (!moduleSelect.dataset.populated && Array.isArray(json.modules)) {
                json.modules.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m;
                    opt.textContent = moduleLabel(m);
                    moduleSelect.appendChild(opt);
                });
                moduleSelect.dataset.populated = '1';
            }

            render(json);
        } catch (e) {
            container.innerHTML = `<p style="text-align:center;color:#dc2626;padding:40px 0;">${esc(e.message)}</p>`;
        } finally {
            loading.classList.add('hidden');
        }
    }

    document.getElementById('alApply').addEventListener('click', function () {
        state.page = 1;
        load();
    });
    document.getElementById('alReset').addEventListener('click', function () {
        document.getElementById('alModule').value = '';
        document.getElementById('alAction').value = '';
        document.getElementById('alSearch').value = '';
        document.getElementById('alFrom').value = '';
        document.getElementById('alTo').value = '';
        state.page = 1;
        load();
    });
    document.getElementById('alSearch').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { state.page = 1; load(); }
    });
    document.getElementById('alPrev').addEventListener('click', function () {
        if (state.page > 1) { state.page--; load(); }
    });
    document.getElementById('alNext').addEventListener('click', function () {
        state.page++; load();
    });

    window._loadAuditLog = load;
})();
</script>
