<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<style>
.requests-container {
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-height: 0;
    padding-left: 2rem;
    padding-right: 2rem;
    padding-bottom: 2rem;
}

.page-holder {
    padding: 0.25rem;
}

.page-title {
    font-size: 1.875rem;
    line-height: 2.25rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.page-subtitle {
    color: #6b7280;
    font-size: 0.875rem;
    line-height: 1.25rem;
}

/* ── Tab switcher ── */
.tab-bar {
    display: flex;
    gap: 6px;
    align-items: center;
}

.tab-btn {
    padding: 7px 18px;
    border-radius: 8px;
    border: 1.5px solid #e5e7eb;
    background: #fff;
    color: #6b7280;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.18s ease;
}

.tab-btn:hover {
    background: #f3f4f6;
    color: #1f2937;
}

.tab-btn.active {
    background: #FF2D2D;
    color: #fff;
    border-color: #FF2D2D;
}

/* ── Grid layout ── */
.request-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 25px;
}

/* ── Card ── */
.request-card {
    background: whitesmoke;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 8px rgba(0,0,0,0.05);
}

.request-cards-container {
    background-color: white;
    padding: 1.5rem;
    border-radius: 1.25rem;
    height: 100%;
    border: 1px solid rgb(156 163 175 / 1);
    overflow-y: hidden;
}

.request-grid-container {
    overflow-y: auto;
    max-height: 100%;
    padding-right: 5px;
}

/* ── Card header ── */
.request-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: #fee2e2;
}

.request-header.pending  { background: #fee2e2; }
.request-header.approved { background: #dcfce7; }
.request-header.rejected { background: #fecaca; }

.request-name { font-weight: 600; }

.request-person {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.request-person-details { min-width: 0; }

.request-avatar {
    width: 48px; height: 48px;
    min-width: 48px; min-height: 48px;
    max-width: 48px; max-height: 48px;
    flex-shrink: 0;
    border-radius: 999px;
    background-color: #e5e7eb;
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    color: #374151;
    border: 1px solid #d1d5db;
}

.request-date {
    font-size: 14px;
    color: #6b7280;
}

.request-name,
.request-date {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* ── Action buttons ── */
.request-actions { display: flex; gap: 8px; }

.btn-circle {
    width: 32px; height: 32px;
    border-radius: 50%;
    border: none;
    color: white;
    font-weight: bold;
    cursor: pointer;
}

.btn-reject  { background: #ef4444; }
.btn-approve { background: #16a34a; }

/* ── Card body ── */
.request-body { padding: 16px; }
.request-row  { margin-bottom: 10px; }
.label        { color: #6b7280; }
.value        { font-weight: 500; }
.message      { margin-top: 5px; }

/* ── Status badges ── */
.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    color: white;
    font-size: 14px;
}

.status-badge.approved { background: #16a34a; }
.status-badge.rejected { background: #6b7280; }
.status-badge.pending  { background: #f59e0b; }

/* ── Page header ── */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    margin-bottom: 1rem;
}

.filter-container {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.filter-dropdown {
    background-color: #FF2D2D;
    color: #ffffff;
    padding: 0.75rem;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-dropdown:hover {
    background: #dc2626;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(239, 68, 68, 0.3);
}

.filter-dropdown option {
    background-color: #ffffff;
    color: #1f2937;
    padding: 10px;
}

.filter-dropdown option:checked {
    background-color: #ed4b4b;
    color: #ffffff;
}

.duration { margin-left: 5px; }

/* ── Generate Code Button ── */
.generate-code-btn {
    background: #FF2D2D;
    color: #fff;
    padding: 0.75rem;
    border-radius: 0.5rem;
    border: none;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
}

.generate-code-btn:hover {
    background: #fa1515;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(246, 92, 92, 0.3);
}

.generate-code-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* ── Tab panels ── */
.tab-panel {
    display: none;
    flex: 1;
    min-height: 0;
    height: 100%;
}

.tab-panel.active {
    display: flex;
    flex-direction: column;
}

/* ── Code Modal ── */
.code-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.code-modal-overlay.open { display: flex; }

.code-modal {
    background: #fff;
    border-radius: 14px;
    padding: 28px 32px;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 25px 50px rgba(0,0,0,0.2);
    animation: modalPop 0.2s ease;
    text-align: center;
}

@keyframes modalPop {
    from { transform: scale(0.95); opacity: 0; }
    to   { transform: scale(1);    opacity: 1; }
}

.code-modal h2 {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 6px;
}

.code-modal p {
    font-size: 13px;
    color: #6b7280;
    margin: 0 0 20px;
}

.code-display {
    background: #f3f4f6;
    border: 2px dashed #d1d5db;
    border-radius: 10px;
    padding: 16px 20px;
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 3px;
    color: #111827;
    font-family: 'Courier New', monospace;
    margin-bottom: 18px;
    word-break: break-all;
}

.code-display.generating {
    color: #9ca3af;
    font-size: 14px;
    font-weight: 400;
    letter-spacing: 0;
}

.code-modal-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.copy-btn {
    background: #8b5cf6;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 9px 20px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.copy-btn:hover    { 
    background: #7c3aed; 
    transform: translateY(-1px); 

}
.copy-btn.copied   { 
    background: #16a34a; 
}

.close-code-btn {
    background: #e5e7eb;
    color: #374151;
    border: none;
    border-radius: 8px;
    padding: 9px 20px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.close-code-btn:hover { 
    background: #d1d5db; 
    transform: translateY(-1px); 
}

.code-hint {
    margin-top: 14px;
    font-size: 12px;
    color: #9ca3af;
}

.approval-codes-grid {
    display: grid;
    grid-template-columns: 2fr 1.5fr 1fr 1.5fr 1.5fr;
}
</style>


<div class="requests-container">

    <div class="page-header">
        <div class="page-holder">
            <h1 class="page-title">Employee Requests</h1>
            <p class="page-subtitle">Keep track of employees status.</p>
        </div>

        <div class="filter-container">

            <?php if (auth()->user()->inGroup('client_payroll_staff')): ?>
            <!-- Tab switcher -->
            <div class="tab-bar">
                <button class="tab-btn active" id="tabBtnRequests"     onclick="switchTab('requests')">Requests</button>
                <button class="tab-btn"         id="tabBtnCodes"        onclick="switchTab('codes')">Approval Codes</button>
            </div>

            <!-- <div class="tabs">
                <button class="tab active" id="tabBtnRequests" data-tab="payroll-status">Requests</button>
                <button  class="tab" onclick="switchTab('codes')" data-tab="generate-payslip">Approval Codes</button>
            </div> -->
            <?php endif; ?>

            <!-- Per-tab controls -->
            <div id="requestsControls" style="display:flex;align-items:center;gap:1rem;">
                <?php if (auth()->user()->inGroup('client_payroll_staff')): ?>
                <button class="generate-code-btn" id="generateCodeBtn" onclick="openCodeModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    Generate Code
                </button>
                <?php endif; ?>

                <select id="statusFilter" class="filter-dropdown">
                    <option value="all">All Requests</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <!-- Codes tab has no extra header controls (crud_table has its own search) -->
            <div id="codesControls" style="display:none;"></div>

        </div>
    </div>

    <!-- ── TAB: Requests ── -->
    <div class="tab-panel active" id="panelRequests">
        <div class="request-cards-container">
            <div class="request-grid-container scroll-bar">
                <div class="request-grid">
                    <?php foreach ($requests as $request): ?>
                    <div class="request-card" data-status="<?= $request['status'] ?>">

                        <?php
                            $startDate     = ! empty($request['start_date']) ? date('F j, Y', strtotime($request['start_date'])) : 'N/A';
                            $endDate       = ! empty($request['end_date'])   ? date('F j, Y', strtotime($request['end_date']))   : null;
                            $dateText      = $startDate;
                            if ($endDate !== null && $endDate !== $startDate) {
                                $dateText .= ' to ' . $endDate;
                            }
                            $duration       = (int) ($request['duration'] ?? 1);
                            $displayName    = (string) ($request['requested_by_name'] ?? (string) ($request['requested_by'] ?? 'U'));
                            $initialsSource = trim($displayName);
                            $avatarInitials = strtoupper(substr($initialsSource !== '' ? $initialsSource : 'U', 0, 2));
                        ?>

                        <div class="request-header <?= $request['status'] ?>">
                            <div class="request-person">
                                <?php if (auth()->user()->inGroup('client_payroll_staff')): ?>
                                    <?php if (! empty($request['photo_path'])): ?>
                                        <div class="request-avatar" style="background-image:url('<?= esc($request['photo_path']) ?>');"></div>
                                    <?php else: ?>
                                        <div class="request-avatar"><?= esc($avatarInitials) ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="request-person-details">
                                    <div class="request-name"><?= esc($request['requested_by_name'] ?? (string) $request['requested_by']) ?></div>
                                    <div class="request-date">Employee No: <?= esc($request['employee_number'] ?? 'N/A') ?></div>
                                </div>
                            </div>

                            <div class="request-actions">
                                <?php if (auth()->user()->inGroup('client_payroll_staff') && $request['status'] == 'pending'): ?>
                                <form action="<?= site_url('client_portal/requests/update/'.$request['id']) ?>" method="post">
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="btn-circle btn-reject">✕</button>
                                </form>
                                <form action="<?= site_url('client_portal/requests/update/'.$request['id']) ?>" method="post">
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="btn-circle btn-approve">✓</button>
                                </form>
                                <?php else: ?>
                                <span class="status-badge <?= $request['status'] ?>">
                                    <?= ucfirst($request['status']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="request-body">
                            <div class="request-row">
                                <span class="label">Request Type:</span>
                                <span class="value"><?= ucfirst($request['request_type']) ?></span>
                            </div>
                            <div class="request-row">
                                <span class="label">Duration:</span>
                                <p class="message"><?= esc($dateText) ?>
                                    <span class="duration">(<?= esc((string) $duration) ?> day<?= $duration !== 1 ? 's' : '' ?>)</span>
                                </p>
                            </div>
                            <div class="request-row">
                                <span class="label">Message:</span>
                                <p class="message"><?= esc($request['reason']) ?></p>
                            </div>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ── TAB: Approval Codes (payroll staff only) ── -->
    <?php if (auth()->user()->inGroup('client_payroll_staff')): ?>
    <div class="tab-panel" id="panelCodes">
        <?= view('components/crud_table', [
        'title'   => 'Approval Codes',
        'tableId' => 'approvalCodesTable',
        'grid'    => 'approval-codes-grid',
        'columns' => ['Code', 'Generated By', 'Status', 'Used By', 'Generated At'],
    ]) ?>
    </div>
    <?php endif; ?>

</div>

<!-- ── Approval Code Modal (payroll staff only) ── -->
<?php if (auth()->user()->inGroup('client_payroll_staff')): ?>
<div class="code-modal-overlay" id="codeModalOverlay">
    <div class="code-modal">
        <h2>Approval Code</h2>
        <p>Share this one-time code with the employee.<br>It will auto-approve their request on submission.</p>

        <div class="code-display generating" id="codeDisplay">Generating&hellip;</div>

        <div class="code-modal-actions">
            <button class="copy-btn" id="copyCodeBtn" onclick="copyCode()" disabled>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-4 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                Copy
            </button>
            <button class="close-code-btn" onclick="closeCodeModal()">Close</button>
        </div>

        <div class="code-hint">Codes do not expire and can only be used once.</div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>

<script src="<?= base_url('js/components/crud-table.js') ?>"></script>

<script>
// ── Data from PHP ─────────────────────────────────────────────────────────────
const APPROVAL_CODES = <?= json_encode($approvalCodes ?? []) ?>;

// ── Helpers ──────────────────────────────────────────────────────────────────
function htmlEsc(val) {
    if (val == null) return '';
    return String(val)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function formatDateTime(val) {
    if (!val) return '---';
    const d = new Date(val);
    if (isNaN(d.getTime())) return htmlEsc(val);
    return new Intl.DateTimeFormat('en-US', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: 'numeric', minute: '2-digit', hour12: true,
    }).format(d);
}

// ── Tab switching ─────────────────────────────────────────────────────────────
function switchTab(tab) {
    const isRequests = tab === 'requests';

    document.getElementById('panelRequests').classList.toggle('active', isRequests);
    document.getElementById('panelCodes').classList.toggle('active', !isRequests);

    document.getElementById('tabBtnRequests').classList.toggle('active', isRequests);
    document.getElementById('tabBtnCodes').classList.toggle('active', !isRequests);

    document.getElementById('requestsControls').style.display = isRequests ? 'flex' : 'none';
    document.getElementById('codesControls').style.display    = isRequests ? 'none' : 'flex';
}

// ── Requests tab: status filter ───────────────────────────────────────────────
document.getElementById('statusFilter').addEventListener('change', function () {
    const selected = this.value;
    document.querySelectorAll('.request-card').forEach(card => {
        card.style.display = (selected === 'all' || card.dataset.status === selected) ? 'block' : 'none';
    });
});

// ── Approval Codes table ──────────────────────────────────────────────────────
<?php if (auth()->user()->inGroup('client_payroll_staff')): ?>

function configureCodesFilterUi() {
    const container = document.querySelector('[data-crud-table="approvalCodesTable"]');
    if (!container) return;

    // Hide filters that don't apply to this table
    ['status', 'department', 'employment_type'].forEach(f => {
        const el = container.querySelector(`[data-filter="${f}"]`)?.closest('div');
        if (el) el.classList.add('hidden');
    });

    const sortBy = container.querySelector('[data-filter="sort_by"]');
    if (sortBy) {
        sortBy.innerHTML = `
            <option value="created_at">Generated At</option>
            <option value="code">Code</option>
            <option value="generated_by_name">Generated By</option>
            <option value="is_used">Status</option>
        `;
        sortBy.value = 'created_at';
    }
}

function approvalCodeRowTemplate(row) {
    const statusBadge = row.is_used == 1 
    ? `<span style="background:#6b7280;color:#fff;padding:3px 10px;border-radius:20px;font-size:13px;">Used</span>`
    : `<span style="background:#16a34a;color:#fff;padding:3px 10px;border-radius:20px;font-size:13px;">Unused</span>`;

        const usedBy = row.is_used == 1
            ? htmlEsc(row.used_by_name || '—')
            : '<span style="color:#9ca3af;">—</span>';

    return `
        <div class="approval-codes-grid items-center border-b hover:bg-gray-50">
            <div class="p-4 font-mono font-semibold tracking-widest text-gray-800">${htmlEsc(row.code)}</div>
            <div class="p-4">${htmlEsc(row.generated_by_name || '—')}</div>
            <div class="p-4">${statusBadge}</div>
            <div class="p-4">${usedBy}</div>
            <div class="p-4 text-sm text-gray-500">${formatDateTime(row.created_at)}</div>
        </div>
    `;
}

configureCodesFilterUi();

createCrudTable({
    tableId: 'approvalCodesTable',
    data: Array.isArray(APPROVAL_CODES) ? APPROVAL_CODES : [],
    renderRow: approvalCodeRowTemplate,
    search: (item, q) =>
        `${item.code ?? ''} ${item.generated_by_name ?? ''} ${item.used_by_name ?? ''}`
            .toLowerCase()
            .includes(q),
    filters: ({ item, query }) => {
        if (!query) return true;
        return `${item.code ?? ''} ${item.generated_by_name ?? ''} ${item.used_by_name ?? ''}`
            .toLowerCase()
            .includes(query);
    },
    sort: ({ a, b, sortBy, sortOrder }) => {
        let left, right;

        if (sortBy === 'is_used') {
            left  = a.is_used ?? 0;
            right = b.is_used ?? 0;
        } else if (sortBy === 'created_at') {
            left  = new Date(a.created_at || 0).getTime();
            right = new Date(b.created_at || 0).getTime();
        } else {
            left  = String(a[sortBy] ?? '').toLowerCase();
            right = String(b[sortBy] ?? '').toLowerCase();
        }

        if (left < right) return sortOrder === 'asc' ? -1 : 1;
        if (left > right) return sortOrder === 'asc' ?  1 : -1;
        return 0;
    },
});

<?php endif; ?>

// ── Approval Code Modal ───────────────────────────────────────────────────────
<?php if (auth()->user()->inGroup('client_payroll_staff')): ?>

let currentCode = null;

const COPY_ICON = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-4 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg> Copy`;
const CHECK_ICON = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Copied!`;

async function openCodeModal() {
    currentCode = null;

    const overlay  = document.getElementById('codeModalOverlay');
    const display  = document.getElementById('codeDisplay');
    const copyBtn  = document.getElementById('copyCodeBtn');
    const genBtn   = document.getElementById('generateCodeBtn');

    display.textContent = 'Generating\u2026';
    display.classList.add('generating');
    copyBtn.disabled = true;
    copyBtn.classList.remove('copied');
    copyBtn.innerHTML = COPY_ICON;
    genBtn.disabled = true;
    overlay.classList.add('open');

    try {
        const resp = await fetch('<?= site_url('client_portal/requests/generate-code') ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        const data = await resp.json();

        if (data.success) {
            currentCode = data.code;
            display.textContent = data.code;
            display.classList.remove('generating');
            copyBtn.disabled = false;

            // Inject new code into the live table without a page reload
            if (typeof createCrudTable === 'function') {
                APPROVAL_CODES.unshift({
                    code:              data.code,
                    generated_by_name: '<?= esc(auth()->user()->username) ?>',
                    is_used:           0,
                    used_by_name:      null,
                    created_at:        new Date().toISOString(),
                });
                // Re-initialise the table with the updated array
                createCrudTable({
                    tableId: 'approvalCodesTable',
                    data: APPROVAL_CODES,
                    renderRow: approvalCodeRowTemplate,
                    search: (item, q) =>
                        `${item.code ?? ''} ${item.generated_by_name ?? ''} ${item.used_by_name ?? ''}`
                            .toLowerCase().includes(q),
                    filters: ({ item, query }) => {
                        if (!query) return true;
                        return `${item.code ?? ''} ${item.generated_by_name ?? ''} ${item.used_by_name ?? ''}`
                            .toLowerCase().includes(query);
                    },
                    sort: ({ a, b, sortBy, sortOrder }) => {
                        let left, right;
                        if (sortBy === 'is_used') {
                            left = a.is_used ?? 0; right = b.is_used ?? 0;
                        } else if (sortBy === 'created_at') {
                            left  = new Date(a.created_at || 0).getTime();
                            right = new Date(b.created_at || 0).getTime();
                        } else {
                            left  = String(a[sortBy] ?? '').toLowerCase();
                            right = String(b[sortBy] ?? '').toLowerCase();
                        }
                        if (left < right) return sortOrder === 'asc' ? -1 : 1;
                        if (left > right) return sortOrder === 'asc' ?  1 : -1;
                        return 0;
                    },
                });
            }
        } else {
            display.textContent = data.message || 'Error generating code.';
        }
    } catch (err) {
        display.textContent = 'Network error. Please try again.';
        console.error(err);
    } finally {
        genBtn.disabled = false;
    }
}

function closeCodeModal() {
    document.getElementById('codeModalOverlay').classList.remove('open');
    currentCode = null;
}

document.getElementById('codeModalOverlay')?.addEventListener('click', function (e) {
    if (e.target === this) closeCodeModal();
});

async function copyCode() {
    if (!currentCode) return;

    const copyBtn = document.getElementById('copyCodeBtn');

    try {
        await navigator.clipboard.writeText(currentCode);
    } catch {
        // Fallback for non-HTTPS / older browsers
        const ta = document.createElement('textarea');
        ta.value = currentCode;
        ta.style.cssText = 'position:fixed;opacity:0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
    }

    copyBtn.classList.add('copied');
    copyBtn.innerHTML = CHECK_ICON;
    setTimeout(() => {
        copyBtn.classList.remove('copied');
        copyBtn.innerHTML = COPY_ICON;
    }, 2000);
}

<?php endif; ?>
</script>

<?= $this->endSection() ?>