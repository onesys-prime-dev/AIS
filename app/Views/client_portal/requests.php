<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $authUser        = auth()->loggedIn() ? auth()->user() : null;
    $isPayrollStaff  = $authUser ? \App\Libraries\PortalAccess::reviewsRequests($authUser) : false;
    $authUsername    = $authUser->username ?? '';
?>
<style>

.requests-content-container{
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: visible;
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
    background: #fff;
    border: 0.5px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    width: 100%;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.request-card.clickable {
    cursor: pointer;
}

.request-card:hover {
    border-color: #d1d5db;
    box-shadow: 0 6px 18px rgba(0,0,0,0.13);
}

/* ── Status stripe ── */
.request-card-stripe {
    height: 3px;
}
.request-card-stripe.approved { background: #1D9E75; }
.request-card-stripe.rejected { background: #E24B4A; }
.request-card-stripe.pending  { background: #BA7517; }

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
}

/* ── Card header ── */
.request-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 14px 10px;
    border-bottom: 0.5px solid #e5e7eb;
    background: #fff;
}

.request-header.approved { background: #f0fdf6; }
.request-header.rejected { background: #fff5f5; }
.request-header.pending  { background: #fffbeb; }

.request-name { font-size: 15px; font-weight: 500; color: #111827; }

.request-person {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.request-person-details { min-width: 0; }

.request-avatar {
    width: 36px; height: 36px;
    min-width: 36px; min-height: 36px;
    max-width: 36px; max-height: 36px;
    flex-shrink: 0;
    border-radius: 999px;
    background-color: #f3f4f6;
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 500;
    color: #6b7280;
    border: 0.5px solid #e5e7eb;
}

.request-date {
    font-size: 13px;
    color: #6b7280;
    margin-top: 1px;
}

.request-name,
.request-date {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* ── Action buttons ── */
.request-actions { display: flex; gap: 8px; align-items: center; }

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
.request-body { padding: 10px 14px; }

.request-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 8px;
    padding: 5px 0;
    border-bottom: 0.5px solid #f3f4f6;
}

.request-row:last-child { border-bottom: none; }

.label  { font-size: 13px; color: #6b7280; white-space: nowrap; flex-shrink: 0; }
.value  { font-size: 13px; color: #111827; font-weight: 500; text-align: right; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.message { font-size: 13px; color: #111827; font-weight: 500; text-align: right; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin: 0; }

/* ── Note footer ── */
.request-note-footer {
    display: flex;
    gap: 6px;
    align-items: flex-start;
    padding: 8px 14px;
    border-top: 0.5px solid #e5e7eb;
    background: #fff;
}

.request-note-lbl {
    font-size: 13px;
    color: #6b7280;
    white-space: nowrap;
    flex-shrink: 0;
    padding-top: 1px;
}

.request-note-val {
    font-size: 13px;
    color: #111827;
    line-height: 1.5;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

/* ── Status badges ── */
.status-badge {
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    white-space: nowrap;
    flex-shrink: 0;
}

.status-badge.approved { background: #E1F5EE; color: #0F6E56; }
.status-badge.rejected { background: #FCEBEB; color: #A32D2D; }
.status-badge.pending  { background: #FAEEDA; color: #854F0B; }
.status-badge.backdated { background: #EDE9FE; color: #5B21B6; font-size: 11px; padding: 2px 7px; }

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
    max-height: calc(100vh - 2rem);
    box-shadow: 0 25px 50px rgba(0,0,0,0.2);
    animation: modalPop 0.2s ease;
    text-align: center;
    overflow-y: auto;
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

/* ── Duration field ── */
.code-duration-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 16px;
    text-align: left;
}

.code-duration-field label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6b7280;
}

.code-duration-field input {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 7px 10px;
    font-size: 13px;
    color: #111827;
    background: #fff;
    font-family: inherit;
    transition: border-color 0.15s;
}

.code-duration-field input:focus {
    outline: none;
    border-color: #FF2D2D;
    box-shadow: 0 0 0 2px rgba(255,45,45,0.12);
}

.code-duration-field textarea {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 7px 10px;
    font-size: 13px;
    color: #111827;
    background: #fff;
    font-family: inherit;
    transition: border-color 0.15s;
    resize: vertical;
}

.code-duration-field textarea:focus {
    outline: none;
    border-color: #FF2D2D;
    box-shadow: 0 0 0 2px rgba(255,45,45,0.12);
}

.approval-codes-grid {
    display: grid;
    grid-template-columns: 2fr 1.5fr 1fr 1.5fr 1.5fr 1.5fr;
}

/* ── Request Detail Modal (read-only, for employees) ── */
.request-detail-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 1rem;
    overflow: auto;
}

.request-detail-modal-overlay.open { display: flex; }

.request-detail-modal {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 520px;
    max-height: calc(100vh - 2rem);
    box-shadow: 0 25px 50px rgba(0,0,0,0.22);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: modalPop 0.2s ease;
}

.request-detail-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 22px;
    color: #fff;
}

.request-detail-modal-header.approved { background: linear-gradient(135deg, #15803d 0%, #16a34a 100%); }
.request-detail-modal-header.rejected { background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%); }
.request-detail-modal-header.pending  { background: linear-gradient(135deg, #92400e 0%, #f59e0b 100%); }

.request-detail-modal-header h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
}

.request-detail-modal-close {
    border: none;
    background: transparent;
    color: #fff;
    font-size: 24px;
    cursor: pointer;
    line-height: 1;
}

.request-detail-modal-body {
    padding: 22px;
    overflow-y: auto;
    display: grid;
    gap: 14px;
}

.request-detail-item {
    display: grid;
    gap: 4px;
    border-bottom: 1px solid #f3f4f6;
    padding-bottom: 12px;
}

.request-detail-item:last-child { border-bottom: none; padding-bottom: 0; }

.request-detail-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6b7280;
    font-weight: 700;
}

.request-detail-value {
    color: #111827;
    font-weight: 500;
    line-height: 1.55;
    word-break: break-word;
}

.request-detail-modal-footer {
    padding: 0 22px 22px;
    display: flex;
    justify-content: flex-end;
}

.detail-close-btn {
    background: #e5e7eb;
    color: #374151;
    border: none;
    border-radius: 10px;
    padding: 10px 22px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s;
}

.detail-close-btn:hover { background: #d1d5db; }

/* ── Request Review Modal (payroll staff — pending only) ── */
.request-review-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 1rem;
    overflow: auto;
}

.request-review-modal-overlay.open { display: flex; }

.request-review-modal {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 760px;
    max-height: calc(100vh - 2rem);
    box-shadow: 0 25px 50px rgba(0,0,0,0.22);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.request-review-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 22px;
    background: linear-gradient(135deg, #181818 0%, #2f2e2e 100%);
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
    min-height: 0;
    flex: 1;
    overflow-y: auto;
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
    flex-shrink: 0;
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

.review-action-btn:hover { transform: translateY(-1px); }
.review-action-btn.reject  { background: #ef4444; }
.review-action-btn.approve { background: #16a34a; }
.review-action-btn.cancel  { background: #6b7280; }

@media (max-width: 768px) {
    .request-review-modal { max-height: calc(100vh - 1rem); }
    .request-review-modal-body { grid-template-columns: 1fr; }
}

/* ── Staff Detail Modal (payroll staff — approved/rejected, read-only) ── */
.staff-detail-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 1rem;
}
.staff-detail-modal-overlay.open { display: flex; }

.staff-detail-modal {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 500px;
    max-height: calc(100vh - 2rem);
    box-shadow: 0 25px 50px rgba(0,0,0,0.22);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: modalPop 0.2s ease;
}

.staff-detail-modal-top {
    padding: 18px 20px;
    min-height: 88px;
    border-bottom: 0.5px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.staff-detail-modal-top-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.staff-detail-avatar {
    width: 46px; height: 46px; min-width: 46px;
    border-radius: 50%;
    background: #f3f4f6;
    border: 0.5px solid #e5e7eb;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; font-weight: 600; color: #374151;
    background-size: cover; background-position: center;
    flex-shrink: 0;
}

.staff-detail-name-block { min-width: 0; }
.staff-detail-name  { font-size: 18px; font-weight: 700; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.staff-detail-empno { font-size: 13px; color: rgba(255,255,255,0.85); margin-top: 4px; }

.staff-detail-close-x {
    border: none;
    background: #f3f4f6;
    color: #6b7280;
    border-radius: 50%;
    width: 30px; height: 30px;
    font-size: 18px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    line-height: 1;
    flex-shrink: 0;
    transition: background 0.15s;
}
.staff-detail-close-x:hover { background: #e5e7eb; color: #111827; }

/* Info tiles row */
.staff-detail-tiles {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    border-bottom: 0.5px solid #e5e7eb;
}

.staff-detail-tile {
    padding: 14px 18px;
    border-right: 0.5px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.staff-detail-tile:last-child { border-right: none; }

.staff-detail-tile-lbl {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #9ca3af;
    font-weight: 600;
}

.staff-detail-tile-val {
    font-size: 13px;
    font-weight: 600;
    color: #111827;
}

/* Body rows */
.staff-detail-modal-body {
    padding: 4px 22px 8px;
    overflow-y: auto;
    flex: 1;
}

.staff-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 0.5px solid #f3f4f6;
}
.staff-detail-row:last-of-type { border-bottom: none; }

.staff-detail-lbl { font-size: 12px; color: #6b7280; white-space: nowrap; flex-shrink: 0; }
.staff-detail-val { font-size: 13px; color: #111827; font-weight: 500; text-align: right; }

.staff-detail-note-block {
    margin: 8px 0 12px;
    border-radius: 10px;
    padding: 12px 14px;
    border: 0.5px solid;
}
.staff-detail-note-block.approved {
    background: #f0fdf6;
    border-color: #bbf7d0;
}
.staff-detail-note-block.rejected {
    background: #fff5f5;
    border-color: #fecaca;
}

.staff-detail-note-lbl {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 700;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.staff-detail-note-block.approved .staff-detail-note-lbl { color: #15803d; }
.staff-detail-note-block.rejected .staff-detail-note-lbl { color: #b91c1c; }

.staff-detail-note-text {
    font-size: 13px;
    font-weight: 600;
    line-height: 1.6;
}
.staff-detail-note-block.approved .staff-detail-note-text { color: #166534; }
.staff-detail-note-block.rejected .staff-detail-note-text { color: #991b1b; }

.staff-detail-modal-footer {
    padding: 12px 22px 18px;
    display: flex;
    justify-content: flex-end;
    border-top: 0.5px solid #e5e7eb;
}
.staff-detail-close-btn {
    background: #f3f4f6; color: #374151;
    border: none; border-radius: 10px;
    padding: 9px 22px; font-weight: 600; font-size: 14px;
    cursor: pointer; transition: background 0.15s;
}
.staff-detail-close-btn:hover { background: #e5e7eb; }

.staff-detail-badge {
    padding: 4px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600; flex-shrink: 0;
}
.staff-detail-badge.approved { background: #E1F5EE; color: #0F6E56; }
.staff-detail-badge.rejected { background: #FCEBEB; color: #A32D2D; }

.staff-detail-close-btn {
    background: #f3f4f6; color: #374151;
    border: none; border-radius: 10px;
    padding: 9px 22px; font-weight: 600; font-size: 14px;
    cursor: pointer; transition: background 0.15s;
}
.staff-detail-close-btn:hover { background: #e5e7eb; }

.staff-detail-modal-top.approved {
    background: linear-gradient(135deg, #0f8a63 0%, #1D9E75 100%);
    color: #fff;
}

.staff-detail-modal-top.rejected {
    background: linear-gradient(135deg, #b3261e 0%, #cf3c3c 100%);
    color: #fff;
}

.staff-detail-modal-top.pending {
    background: linear-gradient(135deg, #8a5a00 0%, #BA7517 100%);
    color: #fff;
}

</style>

    <?php $requests = $requests ?? []; ?>

    <?php if (\App\Libraries\PortalAccess::can('request_codes')): ?>
        <div class="tabs">
            <button type="button" class="tab active" id="tabBtnRequests" onclick="switchTab('requests')">Requests</button>
            <button type="button" class="tab" id="tabBtnCodes" onclick="switchTab('codes')">Approval Codes</button>
        </div>
    <?php endif; ?>
    <div class="requests-content-container scroll-bar">
        <div class="page-header">
            <div class="page-holder">
                <h1 class="page-title">Employee Requests</h1>
                <p class="page-subtitle">Keep track of employees status.</p>
            </div>

            <div class="filter-container">
                <div id="requestsControls" style="display:flex;align-items:center;gap:1rem;">
                    <?php if (\App\Libraries\PortalAccess::can('request_codes')): ?>
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

                <div id="codesControls" style="display:none;"></div>
            </div>
        </div>

        <!-- ── TAB: Requests ── -->
        <div class="tab-panel active" id="panelRequests">
            <div class="request-cards-container">
                <div class="request-grid-container hide-scroll-bar">
                    <div class="request-grid">
                        <?php foreach ($requests as $request): ?>
                        <?php
                            $isClickable = in_array($request['status'], ['approved', 'rejected']);
                            $clickAttr   = '';
                            if ($isClickable) {
                                if ($isPayrollStaff) {
                                    $clickAttr = 'onclick="openStaffDetailModal(' . (int) $request['id'] . ')"';
                                } else {
                                    $clickAttr = 'onclick="openRequestDetailModal(' . (int) $request['id'] . ')"';
                                }
                            }
                        ?>
                        <div class="request-card<?= $isClickable ? ' clickable' : '' ?>" id="request-<?= (int) $request['id'] ?>" data-status="<?= $request['status'] ?>" <?= $clickAttr ?>>

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

                            <?php
                                $typeLabel = ucfirst($request['request_type']);
                                if ($request['request_type'] === 'leave' && !empty($request['leave_category']) && $request['leave_category'] !== 'leave') {
                                    $typeLabel = ucfirst($request['leave_category']) . ' Leave';
                                }
                                // Filed after the fact — start_date already passed before this request existed.
                                $isBackdated = !empty($request['start_date']) && !empty($request['created_at'])
                                    && date('Y-m-d', strtotime($request['start_date'])) < date('Y-m-d', strtotime($request['created_at']));
                                $noteLabel = ((string)($request['status'] ?? '') === 'pending') ? 'Message' : 'Review note';
                                $noteText  = ((string)($request['status'] ?? '') === 'pending')
                                    ? (string)$request['reason']
                                    : (string)($request['review_message'] ?? '');
                            ?>

                            <div class="request-card-stripe <?= esc($request['status']) ?>"></div>

                            <div class="request-header <?= esc($request['status']) ?>">
                                <div class="request-person">
                                    <?php if ($isPayrollStaff): ?>
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
                                    <?php if (\App\Libraries\PortalAccess::canReviewRequest((int) $request['id']) && $request['status'] == 'pending'): ?>
                                        <button type="button" class="btn-circle btn-view" onclick="openRequestReviewModal(<?= (int) $request['id'] ?>)">⌕</button>
                                    <?php else: ?>
                                        <span class="status-badge <?= esc($request['status']) ?>">
                                            <?= ucfirst($request['status']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="request-body">
                                <div class="request-row">
                                    <span class="label">Request type</span>
                                    <span class="value">
                                        <?= esc($typeLabel) ?>
                                        <?php if ($isBackdated): ?>
                                            <span class="status-badge backdated" title="Filed after the requested date already passed">Backdated</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="request-row">
                                    <span class="label">Date range</span>
                                    <span class="value"><?= esc($dateText) ?></span>
                                </div>
                                <div class="request-row">
                                    <span class="label">Duration</span>
                                    <span class="value"><?= esc((string)$duration) ?> day<?= $duration !== 1 ? 's' : '' ?></span>
                                </div>
                            </div>

                            <?php if ($noteText !== ''): ?>
                            <div class="request-note-footer">
                                <span class="request-note-lbl"><?= esc($noteLabel) ?></span>
                                <span class="request-note-val"><?= esc($noteText) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── TAB: Approval Codes (payroll staff only) ── -->
        <?php if (\App\Libraries\PortalAccess::can('request_codes')): ?>
        <div class="tab-panel" id="panelCodes">
            <?= view('components/crud_table', [
            'title'   => 'Approval Codes',
            'tableId' => 'approvalCodesTable',
            'grid'    => 'approval-codes-grid',
            'columns' => ['Code', 'Generated By', 'Status', 'Used By', 'Duration', 'Generated At'],
        ]) ?>
        </div>
        <?php endif; ?>
    </div>

<!-- ── Request Review Modal (payroll staff — pending requests) ── -->
<?php if ($isPayrollStaff): ?>
    <div class="request-review-modal-overlay" id="requestReviewModalOverlay">
        <div class="request-review-modal">
            <div class="request-review-modal-header">
                <h2>Review Request</h2>
                <button type="button" class="request-review-modal-close" onclick="closeRequestReviewModal()">&times;</button>
            </div>

            <form id="requestReviewForm" method="post">
                <?= csrf_field() ?>
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

    <!-- ── Staff Detail Modal (payroll staff — approved/rejected, read-only) ── -->
    <div class="staff-detail-modal-overlay" id="staffDetailModalOverlay">
        <div class="staff-detail-modal">

            <!-- Header: avatar + name + close -->
            <div class="staff-detail-modal-top">
                <div class="staff-detail-modal-top-left">
                    <div class="staff-detail-avatar" id="staffDetailAvatar"></div>
                    <div class="staff-detail-name-block">
                        <div class="staff-detail-name" id="staffDetailName">---</div>
                        <div class="staff-detail-empno" id="staffDetailEmpNo">---</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="staff-detail-badge" id="staffDetailBadge"></span>
                    <button type="button" class="staff-detail-close-x" onclick="closeStaffDetailModal()">&times;</button>
                </div>
            </div>

            <!-- Three info tiles: type / duration / condition -->
            <div class="staff-detail-tiles">
                <div class="staff-detail-tile">
                    <span class="staff-detail-tile-lbl">Request type</span>
                    <span class="staff-detail-tile-val" id="staffDetailType">---</span>
                </div>
                <div class="staff-detail-tile">
                    <span class="staff-detail-tile-lbl">Duration</span>
                    <span class="staff-detail-tile-val" id="staffDetailDuration">---</span>
                </div>
                <div class="staff-detail-tile">
                    <span class="staff-detail-tile-lbl">Condition</span>
                    <span class="staff-detail-tile-val" id="staffDetailCondition">---</span>
                </div>
            </div>

            <!-- Detail rows -->
            <div class="staff-detail-modal-body">
                <div class="staff-detail-row">
                    <span class="staff-detail-lbl">Schedule</span>
                    <span class="staff-detail-val" id="staffDetailDates">---</span>
                </div>
                <div class="staff-detail-row">
                    <span class="staff-detail-lbl">Employee message</span>
                    <span class="staff-detail-val" id="staffDetailReason">---</span>
                </div>

                <!-- Review note (colored by status) -->
                <div class="staff-detail-note-block" id="staffDetailNoteBlock" style="display:none;">
                    <div class="staff-detail-note-lbl">
                        &#9432; Review note
                    </div>
                    <div class="staff-detail-note-text" id="staffDetailNote">---</div>
                </div>
            </div>

            <div class="staff-detail-modal-footer">
                <button type="button" class="staff-detail-close-btn" onclick="closeStaffDetailModal()">Close</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ── Request Detail Modal (employees, read-only) ── -->
<?php if (! $isPayrollStaff): ?>
<div class="request-detail-modal-overlay" id="requestDetailModalOverlay">
    <div class="request-detail-modal">
        <div class="request-detail-modal-header" id="requestDetailModalHeader">
            <h2 id="requestDetailModalTitle">Request Details</h2>
            <button type="button" class="request-detail-modal-close" onclick="closeRequestDetailModal()">&times;</button>
        </div>
        <div class="request-detail-modal-body">
            <div class="request-detail-item">
                <span class="request-detail-label">Request Type</span>
                <span class="request-detail-value" id="detailRequestType">---</span>
            </div>
            <div class="request-detail-item">
                <span class="request-detail-label">Date Range</span>
                <span class="request-detail-value" id="detailDateRange">---</span>
            </div>
            <div class="request-detail-item">
                <span class="request-detail-label">Your Message</span>
                <span class="request-detail-value" id="detailReason">---</span>
            </div>
            <div class="request-detail-item" id="detailReviewNoteRow">
                <span class="request-detail-label">Review Note</span>
                <span class="request-detail-value" id="detailReviewNote">---</span>
            </div>
            <div class="request-detail-item">
                <span class="request-detail-label">Status</span>
                <span class="request-detail-value" id="detailStatus">---</span>
            </div>
        </div>
        <div class="request-detail-modal-footer">
            <button type="button" class="detail-close-btn" onclick="closeRequestDetailModal()">Close</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Approval Code Modal (payroll staff only) ── -->
<?php if ($isPayrollStaff): ?>
<div class="code-modal-overlay" id="codeModalOverlay">
    <div class="code-modal">
        <h2>Generate Approval Code</h2>
        <p>Set the number of leave days to attach to this approval code.<br>The code auto-approves and applies that duration when used.</p>

        <div class="code-duration-field">
            <label for="presetDuration">Duration / Days of Leave</label>
            <input type="number" id="presetDuration" min="1" step="1" value="1" placeholder="e.g. 5">
        </div>

        <div class="code-duration-field">
            <label for="presetReviewMessage">Review Message (applied when code is used)</label>
            <textarea id="presetReviewMessage" rows="3" placeholder="e.g. Auto-approved by HR" maxlength="255"></textarea>
        </div>

        <div class="code-display idle" id="codeDisplay">No code generated yet</div>

        <div class="code-modal-actions">
            <button class="gen-code-action-btn" id="doGenerateCodeBtn" onclick="doGenerateCode()">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                Generate
            </button>

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

// ── Helpers ───────────────────────────────────────────────────────────────────
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

function formatRequestDateRange(request) {
    const startDate = request.start_date ? new Date(request.start_date) : null;
    const endDate   = request.end_date   ? new Date(request.end_date)   : null;
    const duration  = Number(request.duration || 0);
    const fmt = (d) => d && !isNaN(d.getTime())
        ? new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(d)
        : 'N/A';
    if (!startDate || isNaN(startDate.getTime())) return 'N/A';
    const startText = fmt(startDate);
    const endText   = endDate && !isNaN(endDate.getTime()) && endDate.getTime() !== startDate.getTime()
        ? fmt(endDate) : null;
    const rangeText = endText ? `${startText} to ${endText}` : startText;
    return duration > 0 ? `${rangeText} (${duration} day${duration !== 1 ? 's' : ''})` : rangeText;
}

function getTypeLabel(request) {
    return (request.request_type === 'leave' && request.leave_category && request.leave_category !== 'leave')
        ? request.leave_category.charAt(0).toUpperCase() + request.leave_category.slice(1) + ' Leave'
        : (request.request_type ? request.request_type.charAt(0).toUpperCase() + request.request_type.slice(1) : 'N/A');
}

// Filed after the fact — start_date already passed before this request was created.
function isBackdatedRequest(request) {
    if (!request.start_date || !request.created_at) return false;
    const start = new Date(request.start_date);
    const filed = new Date(request.created_at);
    if (isNaN(start.getTime()) || isNaN(filed.getTime())) return false;
    const startDay = new Date(start.getFullYear(), start.getMonth(), start.getDate());
    const filedDay = new Date(filed.getFullYear(), filed.getMonth(), filed.getDate());
    return startDay < filedDay;
}

function getTypeLabelHtml(request) {
    const label = htmlEsc(getTypeLabel(request));
    return isBackdatedRequest(request)
        ? `${label} <span class="status-badge backdated" title="Filed after the requested date already passed">Backdated</span>`
        : label;
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

// ── Status filter ─────────────────────────────────────────────────────────────
document.getElementById('statusFilter').addEventListener('change', function () {
    const selected = this.value;
    document.querySelectorAll('.request-card').forEach(card => {
        card.style.display = (selected === 'all' || card.dataset.status === selected) ? 'block' : 'none';
    });
});

// ── Staff Detail Modal (payroll staff — approved/rejected) ────────────────────
<?php if ($isPayrollStaff): ?>

function openStaffDetailModal(requestId) {
    const request = Array.isArray(REQUESTS) ? REQUESTS.find(r => String(r.id) === String(requestId)) : null;
    if (!request) return;

    const status = String(request.status || '');

    const top = document.querySelector('.staff-detail-modal-top');
    top.className = 'staff-detail-modal-top ' + status;

    // Badge
    const badge = document.getElementById('staffDetailBadge');
    badge.className = 'staff-detail-badge ' + status;
    badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);

    // Avatar
    const avatarEl = document.getElementById('staffDetailAvatar');
    const name = request.requested_by_name || '';
    const initials = name.trim().split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) || 'U';
    if (request.photo_path) {
        avatarEl.style.backgroundImage = `url('${request.photo_path}')`;
        avatarEl.textContent = '';
    } else {
        avatarEl.style.backgroundImage = '';
        avatarEl.textContent = initials;
    }

    // Header
    document.getElementById('staffDetailName').textContent  = name || 'N/A';
    document.getElementById('staffDetailEmpNo').textContent = 'Employee No: ' + (request.employee_number || 'N/A');

    // Tiles
    document.getElementById('staffDetailType').innerHTML       = getTypeLabelHtml(request);
    const dur = Number(request.duration || 0);
    document.getElementById('staffDetailDuration').textContent = dur > 0 ? `${dur} day${dur !== 1 ? 's' : ''}` : 'N/A';
    document.getElementById('staffDetailCondition').textContent = request.approval_code ? 'Approval code' : 'Manual review';

    // Detail rows
    document.getElementById('staffDetailDates').textContent  = formatRequestDateRange(request);
    document.getElementById('staffDetailReason').textContent = request.reason || '---';

    // Review note — colored by status
    const noteBlock = document.getElementById('staffDetailNoteBlock');
    const noteText  = document.getElementById('staffDetailNote');
    if (request.review_message) {
        noteText.textContent    = request.review_message;
        noteBlock.className     = 'staff-detail-note-block ' + status;
        noteBlock.style.display = '';
    } else {
        noteBlock.style.display = 'none';
    }

    document.getElementById('staffDetailModalOverlay').classList.add('open');
}

function closeStaffDetailModal() {
    document.getElementById('staffDetailModalOverlay').classList.remove('open');
}

document.getElementById('staffDetailModalOverlay')?.addEventListener('click', function (e) {
    if (e.target === this) closeStaffDetailModal();
});

// ── Request Review Modal (payroll staff — pending only) ───────────────────────
function openRequestReviewModal(requestId) {
    const request = Array.isArray(REQUESTS) ? REQUESTS.find(item => String(item.id) === String(requestId)) : null;
    if (!request) return;

    document.getElementById('reviewEmployeeName').textContent    = request.requested_by_name || 'N/A';
    document.getElementById('reviewRequestType').innerHTML       = getTypeLabelHtml(request);
    document.getElementById('reviewRequestDates').textContent    = formatRequestDateRange(request);
    document.getElementById('reviewRequestReason').textContent   = request.reason || '---';
    document.getElementById('reviewApprovalCondition').textContent = request.approval_code
        ? `Approved via approval code: ${request.approval_code}`
        : 'Manual review required';

    document.getElementById('reviewMessage').value = '';
    document.getElementById('requestReviewStatus').value = 'pending';
    document.getElementById('requestReviewForm').action = '<?= site_url('client_portal/requests/update') ?>/' + requestId;
    document.getElementById('requestReviewModalOverlay').classList.add('open');
}

function closeRequestReviewModal() {
    document.getElementById('requestReviewModalOverlay').classList.remove('open');
}

function submitRequestReview(status) {
    const form          = document.getElementById('requestReviewForm');
    const reviewMessage = document.getElementById('reviewMessage');

    if (status === 'rejected' && reviewMessage.value.trim() === '') {
        reviewMessage.focus();
        reviewMessage.setCustomValidity('Please enter a rejection message.');
        reviewMessage.reportValidity();
        reviewMessage.setCustomValidity('');
        return;
    }

    document.getElementById('requestReviewStatus').value = status;

    const approveBtn = document.getElementById('reviewApproveBtn');
    const rejectBtn  = document.getElementById('reviewRejectBtn');
    [approveBtn, rejectBtn].forEach(btn => { if (btn) btn.disabled = true; });

    fetch(form.getAttribute('action'), {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(res => res.json().catch(() => ({
            success: false,
            message: 'Something went wrong. Please try again.',
        })))
        .then(data => {
            window.showToast(data.message || (data.success ? 'Done.' : 'Something went wrong.'), data.success ? 'success' : 'error');
            if (data.success) {
                closeRequestReviewModal();
                // The list (pending counts, statuses, balances) needs a fresh
                // pull from the server — simplest correct way is a reload,
                // same end result the old full-page form submit already had.
                window.location.reload();
            }
        })
        .catch(() => {
            window.showToast('Network error. Please try again.', 'error');
        })
        .finally(() => {
            [approveBtn, rejectBtn].forEach(btn => { if (btn) btn.disabled = false; });
        });
}

document.getElementById('requestReviewModalOverlay')?.addEventListener('click', function (e) {
    if (e.target === this) closeRequestReviewModal();
});

<?php endif; ?>

// ── Employee Detail Modal (employees — approved/rejected, read-only) ───────────
<?php if (! $isPayrollStaff): ?>

function openRequestDetailModal(requestId) {
    const request = Array.isArray(REQUESTS) ? REQUESTS.find(r => String(r.id) === String(requestId)) : null;
    if (!request) return;

    const status      = String(request.status || 'pending');
    const statusLabel = status.charAt(0).toUpperCase() + status.slice(1);

    const header = document.getElementById('requestDetailModalHeader');
    header.className = 'request-detail-modal-header ' + status;
    document.getElementById('requestDetailModalTitle').textContent = statusLabel + ' Request';
    document.getElementById('detailRequestType').innerHTML         = getTypeLabelHtml(request);
    document.getElementById('detailDateRange').textContent         = formatRequestDateRange(request);
    document.getElementById('detailReason').textContent            = request.reason || '---';

    // Review note — colored by status
    const noteBlock = document.getElementById('staffDetailNoteBlock');
    const noteText  = document.getElementById('staffDetailNote');
    if (request.review_message) {
        noteText.textContent    = request.review_message;
        noteBlock.className     = 'staff-detail-note-block ' + status;
        noteBlock.style.display = '';
    } else {
        noteBlock.style.display = 'none';
    }

    const badgeColour = status === 'approved' ? '#16a34a' : status === 'rejected' ? '#ef4444' : '#f59e0b';
    document.getElementById('detailStatus').innerHTML =
        `<span style="background:${badgeColour};color:#fff;padding:3px 12px;border-radius:20px;font-size:13px;">${statusLabel}</span>`;

    document.getElementById('requestDetailModalOverlay').classList.add('open');
}

function closeRequestDetailModal() {
    document.getElementById('requestDetailModalOverlay').classList.remove('open');
}

document.getElementById('requestDetailModalOverlay')?.addEventListener('click', function (e) {
    if (e.target === this) closeRequestDetailModal();
});

<?php endif; ?>

// ── Approval Codes table ──────────────────────────────────────────────────────
<?php if ($isPayrollStaff): ?>

function configureCodesFilterUi() {
    const container = document.querySelector('[data-crud-table="approvalCodesTable"]');
    if (!container) return;

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

    let presetCell = '<span style="color:#9ca3af;">—</span>';
    const preset = row.preset_data;
    if (preset && typeof preset === 'object' && Object.keys(preset).length > 0) {
        if (preset.duration) {
            presetCell = `<span style="font-size:12px;line-height:1.6;"><strong>${htmlEsc(preset.duration)} day${Number(preset.duration) !== 1 ? 's' : ''}</strong></span>`;
        }
    } else if (preset && typeof preset === 'string') {
        try {
            const p = JSON.parse(preset);
            if (p.duration) {
                presetCell = `<span style="font-size:12px;line-height:1.6;"><strong>${htmlEsc(p.duration)} day${Number(p.duration) !== 1 ? 's' : ''}</strong></span>`;
            }
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
<?php if ($isPayrollStaff): ?>

let currentCode = null;

const COPY_ICON  = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-4 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg> Copy`;
const CHECK_ICON = `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Copied!`;

function openCodeModal() {
    currentCode = null;
    const display  = document.getElementById('codeDisplay');
    const copyBtn  = document.getElementById('copyCodeBtn');
    const genBtn   = document.getElementById('doGenerateCodeBtn');

    display.textContent = 'No code generated yet';
    display.className   = 'code-display idle';
    copyBtn.style.display = 'none';
    copyBtn.classList.remove('copied');
    copyBtn.innerHTML = COPY_ICON;
    genBtn.disabled = false;
    document.getElementById('presetDuration').value = '1';
    const reviewInput = document.getElementById('presetReviewMessage');
    if (reviewInput) reviewInput.value = '';

    document.getElementById('codeModalOverlay').classList.add('open');
}

async function doGenerateCode() {
    const display       = document.getElementById('codeDisplay');
    const copyBtn       = document.getElementById('copyCodeBtn');
    const genBtn        = document.getElementById('doGenerateCodeBtn');
    const durationInput = document.getElementById('presetDuration');
    const duration      = parseInt(durationInput.value, 10);

    display.textContent   = 'Generating\u2026';
    display.className     = 'code-display generating';
    copyBtn.style.display = 'none';
    genBtn.disabled       = true;

    if (!Number.isFinite(duration) || duration < 1) {
        display.textContent = 'Please enter a duration of at least 1 day.';
        display.className   = 'code-display idle';
        genBtn.disabled     = false;
        durationInput.focus();
        return;
    }

    const reviewMessage = (document.getElementById('presetReviewMessage') || {}).value || '';
    const presetData    = { duration };
    if (reviewMessage.trim()) presetData.review_message = reviewMessage.trim();

    try {
        const resp = await fetch('<?= site_url('client_portal/requests/generate-code') ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
            body: JSON.stringify({ preset_data: presetData }),
        });

        const data = await resp.json();

        if (data.success) {
            currentCode           = data.code;
            display.textContent   = data.code;
            display.className     = 'code-display';
            copyBtn.style.display = 'inline-flex';
            copyBtn.classList.remove('copied');
            copyBtn.innerHTML     = COPY_ICON;

            if (typeof createCrudTable === 'function') {
                APPROVAL_CODES.unshift({
                    code:              data.code,
                    generated_by_name: data.generated_by_name || '<?= esc($authUsername) ?>',
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