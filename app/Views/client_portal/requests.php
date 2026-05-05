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
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
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

.upper-request-body{
    display: flex;
    flex-direction: row;
    justify-content: space-between;
}

/* ── Card header ── */
.request-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: #fee2e2;
}

.request-header.pending  { background: #b5b4b4; }
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
.btn-view    { background: #f59e0b; }

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
.status-badge.rejected { background: #fb2323; }
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
    padding: 1rem;
}

.code-modal-overlay.open { display: flex; }

.code-modal {
    background: #fff;
    border-radius: 14px;
    padding: 28px 32px;
    width: 100%;
    max-width: 520px;
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
    min-height: 62px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.code-display.idle {
    color: #d1d5db;
    font-size: 14px;
    font-weight: 400;
    letter-spacing: 0;
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
    flex-wrap: wrap;
}

/* Generate button inside the modal */
.gen-code-action-btn {
    background: #FF2D2D;
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
.gen-code-action-btn:hover:not(:disabled) {
    background: #e01f1f;
    transform: translateY(-1px);
}
.gen-code-action-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
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

/* ── Preset toggle ── */
.preset-toggle-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 16px;
    font-size: 13px;
    color: #374151;
    font-weight: 600;
}

.toggle-switch {
    position: relative;
    width: 38px;
    height: 22px;
    flex-shrink: 0;
}
.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.toggle-track {
    position: absolute;
    inset: 0;
    background: #d1d5db;
    border-radius: 999px;
    cursor: pointer;
    transition: background 0.2s;
}
.toggle-track::before {
    content: '';
    position: absolute;
    height: 16px;
    width: 16px;
    left: 3px;
    top: 3px;
    background: #fff;
    border-radius: 50%;
    transition: transform 0.2s;
}
.toggle-switch input:checked + .toggle-track {
    background: #FF2D2D;
}
.toggle-switch input:checked + .toggle-track::before {
    transform: translateX(16px);
}

/* ── Preset fields panel ── */
.preset-panel {
    display: none;
    text-align: left;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 16px;
    gap: 12px;
    flex-direction: column;
}
.preset-panel.open { display: flex; }

.preset-field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.preset-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.preset-field label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6b7280;
}
.preset-field select,
.preset-field input,
.preset-field textarea {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 7px 10px;
    font-size: 13px;
    color: #111827;
    background: #fff;
    font-family: inherit;
    transition: border-color 0.15s;
}
.preset-field select:focus,
.preset-field input:focus,
.preset-field textarea:focus {
    outline: none;
    border-color: #FF2D2D;
    box-shadow: 0 0 0 2px rgba(255,45,45,0.12);
}
.preset-field textarea {
    resize: vertical;
    min-height: 64px;
}
.preset-field.full { grid-column: 1 / -1; }

.approval-codes-grid {
    display: grid;
    grid-template-columns: 2fr 1.5fr 1fr 1.5fr 1.5fr 1.5fr;
}

.request-review-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 1rem;
}

.request-review-modal-overlay.open { display: flex; }

.request-review-modal {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 760px;
    box-shadow: 0 25px 50px rgba(0,0,0,0.22);
    overflow: hidden;
}

.request-review-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 22px;
    background: #1e1e1e;
    color: #fff;
}

.request-review-modal-header h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
}

.request-review-modal-close {
    border: none;
    background: transparent;
    color: #fff;
    font-size: 24px;
    cursor: pointer;
    line-height: 1;
}

.request-review-modal-body {
    padding: 22px;
    display: grid;
    grid-template-columns: 1.1fr 0.9fr;
    gap: 18px;
}

.review-summary,
.review-action-panel {
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    background: #f9fafb;
    padding: 18px;
}

.review-summary h3,
.review-action-panel h3 {
    margin: 0 0 12px;
    font-size: 15px;
    font-weight: 700;
    color: #111827;
}

.review-summary-grid {
    display: grid;
    gap: 12px;
}

.review-summary-item {
    display: grid;
    gap: 4px;
}

.review-summary-label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6b7280;
    font-weight: 700;
}

.review-summary-value {
    color: #111827;
    font-weight: 500;
    line-height: 1.55;
    word-break: break-word;
}

.review-note-input {
    width: 100%;
    min-height: 160px;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    padding: 14px;
    resize: vertical;
    font: inherit;
    color: #111827;
    background: #fff;
}

.review-note-input:focus {
    outline: none;
    border-color: #FF2D2D;
    box-shadow: 0 0 0 3px rgba(255, 45, 45, 0.12);
}

.review-help {
    margin-top: 10px;
    font-size: 12px;
    color: #6b7280;
    line-height: 1.5;
}

.request-review-modal-footer {
    padding: 0 22px 22px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}

.review-action-btn {
    border: none;
    border-radius: 10px;
    padding: 10px 18px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #fff;
}

.review-action-btn:hover {
    transform: translateY(-1px);
}

.review-action-btn.reject { background: #ef4444; }
.review-action-btn.approve { background: #16a34a; }
.review-action-btn.cancel { background: #6b7280; }

@media (max-width: 768px) {
    .request-review-modal-body {
        grid-template-columns: 1fr;
    }
}
</style>


<div class="requests-container">

    <?php $requests = $requests ?? []; ?>

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
                                        <div class="request-avatar" style="background-image:url('<?= esc((string) $request['photo_path']) ?>');"></div>
                                    <?php else: ?>
                                        <div class="request-avatar"><?= esc($avatarInitials) ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="request-person-details">
                                    <div class="request-name"><?= esc((string) ($request['requested_by_name'] ?? (string) $request['requested_by'])) ?></div>
                                    <div class="request-date">Employee No: <?= esc((string) ($request['employee_number'] ?? 'N/A')) ?></div>
                                </div>
                            </div>

                            <div class="request-actions">
                                <?php if (auth()->user()->inGroup('client_payroll_staff') && $request['status'] == 'pending'): ?>
                                <button type="button" class="btn-circle btn-view" onclick="openRequestReviewModal(<?= (int) $request['id'] ?>)">⌕</button>
                                <?php else: ?>
                                <span class="status-badge <?= $request['status'] ?>">
                                    <?= ucfirst($request['status']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="request-body">
                            <div class="upper-request-body">
                                <div class="request-row">
                                    <span class="label">Duration:</span>
                                    <p class="message"><?= esc($dateText) ?>
                                    </p>
                                </div>
                                <div class="request-row ">
                                    <span class="label">Request Type:</span>
                                    <p class="message">
                                        <span class="value"><?= ucfirst($request['request_type']) ?></span>
                                    </p>
                                </div> 
                            </div>
                            <?php if ((string) ($request['status'] ?? '') === 'pending'): ?>                       
                                <div class="request-row">
                                    <span class="label">Message:</span>
                                    <p class="message"><?= esc((string) $request['reason']) ?></p>
                                </div>
                                <?php else: ?>
                                    <?php if (! empty($request['review_message'])): ?>
                                        <div class="request-row">
                                            <span class="label">Review Note:</span>
                                            <p class="message"><?= esc((string) $request['review_message']) ?></p>
                                        </div>
                                    <?php endif; ?>
                            <?php endif; ?>
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
        'columns' => ['Code', 'Generated By', 'Status', 'Used By', 'Preset Details', 'Generated At'],
    ]) ?>
    </div>
    <?php endif; ?>

</div>

<?php if (auth()->user()->inGroup('client_payroll_staff')): ?>
    <div class="request-review-modal-overlay" id="requestReviewModalOverlay">
        <div class="request-review-modal">
            <div class="request-review-modal-header">
                <h2>Review Request</h2>
                <button type="button" class="request-review-modal-close" onclick="closeRequestReviewModal()">&times;</button>
            </div>

            <form id="requestReviewForm" method="post">
                <input type="hidden" name="status" id="requestReviewStatus" value="pending">

                <div class="request-review-modal-body">
                    <div class="review-summary">
                        <h3>Request Details</h3>
                        <div class="review-summary-grid">
                            <div class="review-summary-item">
                                <span class="review-summary-label">Employee</span>
                                <span class="review-summary-value" id="reviewEmployeeName">---</span>
                            </div>
                            <div class="review-summary-item">
                                <span class="review-summary-label">Request Type</span>
                                <span class="review-summary-value" id="reviewRequestType">---</span>
                            </div>
                            <div class="review-summary-item">
                                <span class="review-summary-label">Date Range</span>
                                <span class="review-summary-value" id="reviewRequestDates">---</span>
                            </div>
                            <div class="review-summary-item">
                                <span class="review-summary-label">Employee Message</span>
                                <span class="review-summary-value" id="reviewRequestReason">---</span>
                            </div>
                            <div class="review-summary-item">
                                <span class="review-summary-label">Approval Condition</span>
                                <span class="review-summary-value" id="reviewApprovalCondition">---</span>
                            </div>
                        </div>
                    </div>

                    <div class="review-action-panel">
                        <h3 id="reviewPanelTitle">Review Note</h3>
                        <textarea class="review-note-input" id="reviewMessage" name="review_message" placeholder="Enter a rejection message or approval condition..."></textarea>
                        <div class="review-help">
                            Use this note to explain why the request was rejected or to record any approval condition before saving the decision.
                        </div>
                    </div>
                </div>

                <div class="request-review-modal-footer" id="requestReviewModalFooter">
                    <button type="button" class="review-action-btn cancel" onclick="closeRequestReviewModal()">Cancel</button>
                    <button type="button" class="review-action-btn reject" id="reviewRejectBtn" onclick="submitRequestReview('rejected')">Reject</button>
                    <button type="button" class="review-action-btn approve" id="reviewApproveBtn" onclick="submitRequestReview('approved')">Approve</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- ── Approval Code Modal (payroll staff only) ── -->
<?php if (auth()->user()->inGroup('client_payroll_staff')): ?>
<div class="code-modal-overlay" id="codeModalOverlay">
    <div class="code-modal">
        <h2>Generate Approval Code</h2>
        <p>Optionally preset request details before generating.<br>The code auto-approves and applies those details on use.</p>

        <!-- Preset toggle -->
        <div class="preset-toggle-row">
            <label class="toggle-switch" for="presetToggle">
                <input type="checkbox" id="presetToggle" onchange="togglePresetPanel(this.checked)">
                <span class="toggle-track"></span>
            </label>
            <span>Set Request Details</span>
        </div>

        <!-- Preset fields (hidden until toggle on) -->
        <div class="preset-panel" id="presetPanel">
            <div class="preset-field-row">
                <div class="preset-field">
                    <label for="presetType">Request Type</label>
                    <select id="presetType">
                        <option value="">— Any —</option>
                        <option value="leave">Leave</option>
                        <option value="overtime">Overtime</option>
                        <option value="undertime">Undertime</option>
                    </select>
                </div>
                <div class="preset-field">
                    <!-- spacer, can add more fields later -->
                </div>
            </div>
            <div class="preset-field-row">
                <div class="preset-field">
                    <label for="presetStart">Start Date</label>
                    <input type="date" id="presetStart" oninput="enforceEndDate()">
                </div>
                <div class="preset-field">
                    <label for="presetEnd">End Date <span style="font-weight:400;text-transform:none;">(optional)</span></label>
                    <input type="date" id="presetEnd" oninput="enforceEndDate()">
                </div>
            </div>
            <div class="preset-field full">
                <label for="presetReason">Automated Message <span style="font-weight:400;text-transform:none;">(optional)</span></label>
                <textarea id="presetReason" placeholder="e.g. Pre-approved Request – Q3 overtime"></textarea>
            </div>
        </div>

        <!-- Code display -->
        <div class="code-display idle" id="codeDisplay">No code generated yet</div>

        <div class="code-modal-actions">
            <!-- Generate button — always visible -->
            <button class="gen-code-action-btn" id="doGenerateCodeBtn" onclick="doGenerateCode()">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                Generate
            </button>

            <!-- Copy button — only visible after a code is generated -->
            <button class="copy-btn" id="copyCodeBtn" onclick="copyCode()" style="display:none;">
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
<?php
// Decode preset_data JSON strings into objects so JS receives them as plain objects
$approvalCodesForJs = $approvalCodes ?? [];
foreach ($approvalCodesForJs as &$ac) {
    if (! empty($ac['preset_data']) && is_string($ac['preset_data'])) {
        $decoded = json_decode($ac['preset_data'], true);
        $ac['preset_data'] = is_array($decoded) ? $decoded : null;
    }
}
unset($ac);
?>
const APPROVAL_CODES = <?= json_encode($approvalCodesForJs) ?>;
const REQUESTS = <?= json_encode($requests ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

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

// ── Request review modal ─────────────────────────────────────────────────────
<?php if (auth()->user()->inGroup('client_payroll_staff')): ?>

function formatRequestDateRange(request) {
    const startDate = request.start_date ? new Date(request.start_date) : null;
    const endDate = request.end_date ? new Date(request.end_date) : null;
    const duration = Number(request.duration || 0);

    const formatDate = (value) => {
        if (!value || isNaN(value.getTime())) return 'N/A';
        return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(value);
    };

    if (!startDate || isNaN(startDate.getTime())) {
        return 'N/A';
    }

    const startText = formatDate(startDate);
    const endText = endDate && !isNaN(endDate.getTime()) && endDate.getTime() !== startDate.getTime()
        ? formatDate(endDate)
        : null;

    const rangeText = endText ? `${startText} to ${endText}` : startText;
    const durationText = duration > 0 ? `(${duration} day${duration !== 1 ? 's' : ''})` : '';

    return durationText ? `${rangeText} ${durationText}` : rangeText;
}

function openRequestReviewModal(requestId) {
    const request = Array.isArray(REQUESTS) ? REQUESTS.find(item => String(item.id) === String(requestId)) : null;

    if (!request) return;

    document.getElementById('reviewEmployeeName').textContent = request.requested_by_name || 'N/A';
    document.getElementById('reviewRequestType').textContent = request.request_type ? request.request_type.charAt(0).toUpperCase() + request.request_type.slice(1) : 'N/A';
    document.getElementById('reviewRequestDates').textContent = formatRequestDateRange(request);
    document.getElementById('reviewRequestReason').textContent = request.reason || '---';
    document.getElementById('reviewApprovalCondition').textContent = request.approval_code ? `Approved via approval code: ${request.approval_code}` : 'Manual review required';

    const reviewMessage = document.getElementById('reviewMessage');
    const reviewPanelTitle = document.getElementById('reviewPanelTitle');
    const rejectBtn = document.getElementById('reviewRejectBtn');
    const approveBtn = document.getElementById('reviewApproveBtn');
    const modalFooter = document.getElementById('requestReviewModalFooter');

    reviewMessage.value = request.review_message || '';
    reviewMessage.readOnly = String(request.status) !== 'pending';
    reviewMessage.placeholder = String(request.status) === 'pending'
        ? 'Enter a rejection message or approval condition...'
        : 'Saved review note';
    reviewPanelTitle.textContent = String(request.status) === 'pending' ? 'Review Note' : 'Saved Review Note';

    const isPending = String(request.status) === 'pending';
    rejectBtn.style.display = isPending ? 'inline-flex' : 'none';
    approveBtn.style.display = isPending ? 'inline-flex' : 'none';
    modalFooter.style.justifyContent = isPending ? 'flex-end' : 'flex-end';

    document.getElementById('requestReviewStatus').value = 'pending';
    document.getElementById('requestReviewForm').action = '<?= site_url('client_portal/requests/update') ?>/' + requestId;
    document.getElementById('requestReviewModalOverlay').classList.add('open');
}

function closeRequestReviewModal() {
    document.getElementById('requestReviewModalOverlay').classList.remove('open');
}

function submitRequestReview(status) {
    const form = document.getElementById('requestReviewForm');
    const reviewMessage = document.getElementById('reviewMessage');

    if (status === 'rejected' && reviewMessage.value.trim() === '') {
        reviewMessage.focus();
        reviewMessage.setCustomValidity('Please enter a rejection message.');
        reviewMessage.reportValidity();
        reviewMessage.setCustomValidity('');
        return;
    }

    document.getElementById('requestReviewStatus').value = status;
    form.submit();
}

document.getElementById('requestReviewModalOverlay')?.addEventListener('click', function (e) {
    if (e.target === this) closeRequestReviewModal();
});

<?php endif; ?>

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

    // Render preset_data summary if present
    let presetCell = '<span style="color:#9ca3af;">—</span>';
    const preset = row.preset_data;
    if (preset && typeof preset === 'object' && Object.keys(preset).length > 0) {
        const parts = [];
        if (preset.request_type) parts.push(`<strong>${htmlEsc(preset.request_type.charAt(0).toUpperCase() + preset.request_type.slice(1))}</strong>`);
        if (preset.start_date)   parts.push(htmlEsc(preset.start_date) + (preset.end_date ? ` → ${htmlEsc(preset.end_date)}` : ''));
        presetCell = `<span style="font-size:12px;line-height:1.6;">${parts.join('<br>')}</span>`;
    } else if (preset && typeof preset === 'string') {
        // Arrive as JSON string from PHP — parse it
        try {
            const p = JSON.parse(preset);
            const parts = [];
            if (p.request_type) parts.push(`<strong>${htmlEsc(p.request_type.charAt(0).toUpperCase() + p.request_type.slice(1))}</strong>`);
            if (p.start_date)   parts.push(htmlEsc(p.start_date) + (p.end_date ? ` → ${htmlEsc(p.end_date)}` : ''));
            if (parts.length)   presetCell = `<span style="font-size:12px;line-height:1.6;">${parts.join('<br>')}</span>`;
        } catch (_) {}
    }

    return `
        <div class="approval-codes-grid items-center border-b hover:bg-gray-50">
            <div class="p-4 font-mono font-semibold tracking-widest text-gray-800">${htmlEsc(row.code)}</div>
            <div class="p-4">${htmlEsc(row.generated_by_name || '—')}</div>
            <div class="p-4">${statusBadge}</div>
            <div class="p-4">${usedBy}</div>
            <div class="p-4">${presetCell}</div>
            <div class="p-4 text-sm text-gray-500">${formatDateTime(row.created_at)}</div>
        </div>
    `;
}

configureCodesFilterUi();

function refreshApprovalCodesTable() {
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
}

refreshApprovalCodesTable();

<?php endif; ?>

// ── Approval Code Modal ───────────────────────────────────────────────────────
<?php if (auth()->user()->inGroup('client_payroll_staff')): ?>

let currentCode = null;

const COPY_ICON = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-4 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg> Copy`;
const CHECK_ICON = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Copied!`;

/** Open the modal in a clean idle state — no generation yet. */
function openCodeModal() {
    currentCode = null;

    const display   = document.getElementById('codeDisplay');
    const copyBtn   = document.getElementById('copyCodeBtn');
    const genBtn    = document.getElementById('doGenerateCodeBtn');
    const toggle    = document.getElementById('presetToggle');

    // Reset display
    display.textContent = 'No code generated yet';
    display.className   = 'code-display idle';

    // Hide copy until a code exists
    copyBtn.style.display = 'none';
    copyBtn.classList.remove('copied');
    copyBtn.innerHTML = COPY_ICON;

    // Reset generate button
    genBtn.disabled = false;

    // Reset preset panel
    toggle.checked = false;
    togglePresetPanel(false);
    document.getElementById('presetType').value  = '';
    document.getElementById('presetStart').value = '';
    document.getElementById('presetEnd').value   = '';
    document.getElementById('presetReason').value = '';

    document.getElementById('codeModalOverlay').classList.add('open');
}

function togglePresetPanel(on) {
    document.getElementById('presetPanel').classList.toggle('open', on);
}

function enforceEndDate() {
    const start = document.getElementById('presetStart').value;
    const end   = document.getElementById('presetEnd');
    if (start) {
        end.min = start;
        if (end.value && end.value < start) end.value = start;
    }
}

/** Called by the Generate button inside the modal. */
async function doGenerateCode() {
    const display = document.getElementById('codeDisplay');
    const copyBtn = document.getElementById('copyCodeBtn');
    const genBtn  = document.getElementById('doGenerateCodeBtn');

    // Show generating state
    display.textContent = 'Generating\u2026';
    display.className   = 'code-display generating';
    copyBtn.style.display = 'none';
    genBtn.disabled = true;

    // Build optional preset_data payload
    const usePreset  = document.getElementById('presetToggle').checked;
    let presetData   = null;
    if (usePreset) {
        presetData = {
            request_type: document.getElementById('presetType').value  || null,
            start_date:   document.getElementById('presetStart').value || null,
            end_date:     document.getElementById('presetEnd').value   || null,
            reason:       document.getElementById('presetReason').value.trim() || null,
        };
        // Strip null keys so the controller's "at least one field" check works cleanly
        Object.keys(presetData).forEach(k => presetData[k] === null && delete presetData[k]);
        if (Object.keys(presetData).length === 0) presetData = null;
    }

    try {
        const resp = await fetch('<?= site_url('client_portal/requests/generate-code') ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ preset_data: presetData }),
        });

        const data = await resp.json();

        if (data.success) {
            currentCode = data.code;
            display.textContent = data.code;
            display.className   = 'code-display';

            // Reveal copy button now that we have a code
            copyBtn.style.display = 'inline-flex';
            copyBtn.classList.remove('copied');
            copyBtn.innerHTML = COPY_ICON;

            // Inject new code into the live table without a page reload
            if (typeof createCrudTable === 'function') {
                APPROVAL_CODES.unshift({
                    code:              data.code,
                    generated_by_name: '<?= esc(auth()->user()->username) ?>',
                    is_used:           0,
                    used_by_name:      null,
                    preset_data:       data.preset_data || null,
                    created_at:        new Date().toISOString(),
                });
                refreshApprovalCodesTable();
            }
        } else {
            display.textContent = data.message || 'Error generating code.';
            display.className   = 'code-display idle';
        }
    } catch (err) {
        display.textContent = 'Network error. Please try again.';
        display.className   = 'code-display idle';
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