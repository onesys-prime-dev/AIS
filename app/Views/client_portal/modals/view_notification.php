<style>
    /* ============================================================
         RESET & BASE
    ============================================================ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    /* ============================================================
         MODAL OVERLAY
    ============================================================ */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 999999;
        padding: 16px;
        pointer-events: auto;
    }

    .modal-overlay[style*="display: flex"], .modal-overlay[style*="display:flex"] {
        display: flex !important;
        pointer-events: auto;
    }

    /* ============================================================
         NOTIFICATION MODAL CONTAINER
    ============================================================ */
    .notification-modal {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        width: 100%;
        max-width: 560px;
        max-height: calc(100vh - 32px);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
        animation: modalPop 0.2s ease;
    }

    #viewNotificationsModal .notification-modal { max-width: 600px; }

    @keyframes modalPop { from { transform: scale(0.96); opacity: 0; } to { transform: scale(1); opacity: 1; } }

    /* ============================================================
         MODAL HEADER
    ============================================================ */
    .notification-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding: 16px 20px 10px 20px;
        background: #ffffff;
        border-bottom: 1px solid #e5e7eb;
    }

    .notification-modal-heading { 
        display: inline-flex;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
        justify-content: flex-start;
        align-self: center;
        flex: unset;
    }

    /* .notification-modal-pills:empty,
    .notification-modal-byline:empty {
        display: none;
    } */

    .notification-modal-byline { display:flex; align-items:center; gap:8px; flex-wrap:wrap }
    .notification-modal-avatar { width:36px; height:36px; border-radius:50%; background:#ef4444; color:#ffffff; font-size:12px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; background-size:cover; background-position:center; background-repeat:no-repeat; border:2px solid #ffffff; box-shadow:0 4px 10px rgba(15, 23, 42, 0.12) }
    .notification-modal-author { font-size:13px; font-weight:500; color:#6b7280 }
    .notification-modal-sep { font-size:13px; color:#9ca3af }
    .notification-modal-date { font-size:13px; color:#6b7280 }

    .notification-modal-pills { display:flex; align-items:center; gap:6px; flex-wrap:wrap }
    .notification-modal-pill { display:inline-flex; align-items:center; gap:4px; font-size:12px; font-weight:500; padding:3px 10px; border-radius:20px; border:1px solid transparent }
    .notification-modal-pill-blue { background: rgba(239,68,68,0.12); color:#ef4444; border-color: rgba(239,68,68,0.18) }
    .notification-modal-pill-neutral { background: #f3f4f6; color:#6b7280; border-color: #e5e7eb }

    .notification-modal-kicker { display:none }
    .notification-modal-title { font-size:20px; font-weight:700; color:#111827; line-height:1.2; word-break:break-word }

    /* ============================================================
         CLOSE BUTTON
    ============================================================ */
    .notification-modal-close { background:none; border:none; color:#6b7280; cursor:pointer; font-size:17px; line-height:1; padding:2px 4px; border-radius:6px; flex-shrink:0; margin-top:2px; transition:color .15s }
    .notification-modal-close:hover { color:#111827 }

    /* ============================================================
         MODAL BODY
    ============================================================ */
    .notification-modal-body { flex:1; min-height:0; overflow-y:auto; padding:16px 20px 20px }
    .view-notification-body { display:flex; flex-direction:column; gap:14px }
    .notification-modal-body::-webkit-scrollbar { width:4px } .notification-modal-body::-webkit-scrollbar-thumb { background:#3a3a3a; border-radius:4px }

    /* ============================================================
         AUTHOR CARD (hidden in header-byline layout)
    ============================================================ */
    .view-notification-author-card { display:none }

    /* ============================================================
         META LIST
    ============================================================ */
    .view-notification-meta { background:#f9fafb; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; display:flex; flex-direction:column }
    .view-notification-meta-item { display:flex; align-items:center; gap:12px; padding:10px 16px; border-bottom:1px solid #e5e7eb }
    .view-notification-meta-item:last-child { border-bottom:none }
    .view-notification-meta-item .meta-icon { width:16px; height:16px; color:#ef4444; display:flex; align-items:center; justify-content:center }
    .view-notification-label { font-size:13px; color:#6b7280; width:80px; flex-shrink:0; font-weight:400 }
    .view-notification-meta strong { font-size:13px; font-weight:600; color:#111827 }

    /* ============================================================
         CONTENT PANELS
    ============================================================ */
    .view-notification-panel { display:flex; flex-direction:column; gap:8px }
    .view-notification-panel-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#ef4444 }
    /* Summary panel removed - details shown only */
    .view-notification-content { background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:12px 14px; font-size:13px; color:#111827; line-height:1.7; min-height:72px; white-space:pre-wrap; word-break:break-word; margin:0 }

    /* ============================================================
         ATTACHMENTS
    ============================================================ */
    #viewNotificationAttachmentsWrap { display:flex !important; flex-direction:column; gap:8px }
    .attachments-header { display:flex; align-items:center; justify-content:space-between }
    .attachments-label { display:flex; align-items:center; gap:6px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#6b7280 }
    .attachments-count { font-size:12px; color:#4b5563 }
    #viewNotificationAttachments { display:flex !important; flex-direction:column !important; gap:8px !important }

    .attachment-card { display:flex; align-items:center; gap:12px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:12px; padding:12px 14px; transition:border-color .15s, transform .15s, background .15s }
    .attachment-card:hover { border-color:#d1d5db; background:#f3f4f6 }
    .attachment-open-btn { width:42px; height:42px; border-radius:12px; border:1px solid transparent; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; cursor:pointer; transition:transform .15s, filter .15s, border-color .15s }
    .attachment-open-btn:hover { transform:translateY(-1px); filter:brightness(1.03) }
    .attachment-open-btn svg { width:22px; height:22px; display:block }
    .attachment-kind-image { background: rgba(239,68,68,0.12); color:#ef4444; border-color: rgba(239,68,68,0.18) }
    .attachment-kind-pdf { background: rgba(220,38,38,0.12); color:#b91c1c; border-color: rgba(220,38,38,0.18) }
    .attachment-kind-document { background: rgba(37,99,235,0.10); color:#2563eb; border-color: rgba(37,99,235,0.18) }
    .attachment-details { flex:1; min-width:0; display:flex; flex-direction:column; gap:2px }
    .attachment-name { font-size:13px; font-weight:600; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis }
    .attachment-size { font-size:11px; color:#6b7280 }
    .attachment-download { width:38px; height:38px; border-radius:10px; border:1px solid #e5e7eb; background:#ffffff; color:#6b7280; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; cursor:pointer; transition:background .15s, color .15s, border-color .15s, transform .15s }
    .attachment-download:hover { background:#f3f4f6; color:#111827; border-color:#d1d5db; transform:translateY(-1px) }
    .attachment-download svg { width:18px; height:18px; display:block }

    /* ============================================================
         ATTACHMENT PREVIEW MODAL
    ============================================================ */
    .attachment-preview-modal {
        background:#ffffff;
        border:1px solid #e5e7eb;
        border-radius:16px;
        width:min(920px, 100%);
        max-height:calc(100vh - 32px);
        display:flex;
        flex-direction:column;
        overflow:hidden;
        box-shadow:0 24px 60px rgba(15, 23, 42, 0.18);
        animation: modalPop 0.2s ease;
    }
    .attachment-preview-header {
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:16px;
        padding:16px 20px 12px;
        background:#ffffff;
        border-bottom:1px solid #e5e7eb;
    }
    .attachment-preview-heading { min-width:0; display:flex; flex-direction:column; gap:4px }
    .attachment-preview-kicker { font-size:11px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:#ef4444 }
    .attachment-preview-name { font-size:16px; font-weight:700; color:#111827; word-break:break-word }
    .attachment-preview-meta { font-size:12px; color:#6b7280 }
    .attachment-preview-close {
        background:none;
        border:none;
        color:#6b7280;
        cursor:pointer;
        font-size:17px;
        line-height:1;
        padding:2px 4px;
        border-radius:6px;
        flex-shrink:0;
        transition:color .15s;
    }
    .attachment-preview-close:hover { color:#111827 }
    .attachment-preview-body { flex:1; min-height:0; background:#f3f4f6; padding:16px 20px 18px; overflow:auto }
    .attachment-preview-stage {
        /* Fixed preview viewport for uniform display */
        height: 480px;
        display:flex;
        align-items:center;
        justify-content:center;
        width:100%;
        padding:12px;
    }
    .attachment-preview-stage img {
        width: auto;
        height: 100%;
        max-width: 100%;
        object-fit:contain;
        border-radius:12px;
        background:#ffffff;
        box-shadow:0 12px 32px rgba(15,23,42,0.12);
    }
    .attachment-preview-stage iframe {
        width:100%;
        height:100%;
        border:0;
        border-radius:12px;
        background:#ffffff;
        box-shadow:0 12px 32px rgba(15,23,42,0.12);
    }

    @media (max-width: 640px) {
        .attachment-preview-stage { height: calc(50vh); }
    }
    .attachment-preview-fallback {
        width:100%;
        max-width:420px;
        display:flex;
        flex-direction:column;
        align-items:center;
        gap:12px;
        padding:28px 20px;
        text-align:center;
        color:#d1d5db;
    }
    .attachment-preview-fallback-icon {
        width:64px;
        height:64px;
        border-radius:18px;
        display:flex;
        align-items:center;
        justify-content:center;
        background: rgba(239,68,68,0.10);
        color:#ef4444;
        border:1px solid rgba(239,68,68,0.18);
        flex-shrink:0;
    }
    .attachment-preview-fallback-icon svg { width:30px; height:30px }
    .attachment-preview-fallback-title { font-size:15px; font-weight:700; color:#f1f5f9 }
    .attachment-preview-fallback-text { font-size:13px; color:#9ca3af; line-height:1.6 }
    .attachment-preview-footer {
        display:flex;
        align-items:center;
        justify-content:flex-end;
        gap:10px;
        padding:12px 20px 16px;
        border-top:1px solid #e5e7eb;
        background:#ffffff;
    }
    .attachment-preview-download {
        display:inline-flex;
        align-items:center;
        gap:6px;
        background:#ef4444;
        color:#ffffff;
        border:none;
        border-radius:10px;
        padding:9px 14px;
        font-size:13px;
        font-weight:600;
        text-decoration:none;
        white-space:nowrap;
        transition:background .15s, transform .15s;
    }
    .attachment-preview-download:hover { background:#dc2626; transform:translateY(-1px) }
    .attachment-preview-download svg { width:18px; height:18px; flex-shrink:0 }

    /* ============================================================
         FOOTER
    ============================================================ */
    .notification-modal-footer { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-shrink:0; padding:13px 20px; border-top:1px solid #e5e7eb; background:#ffffff }
    .notification-delete-btn { display:inline-flex; align-items:center; gap:6px; background:none; border:none; color:#ef4444; font-size:13px; font-weight:600; cursor:pointer; padding:8px 4px; border-radius:8px }
    .notification-delete-btn svg { width:18px; height:18px; display:block; flex-shrink:0 }
    .notification-delete-btn:hover { color:#fca5a5 }
    .notification-edit-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: #ef4444;
        color: #ffffff;
        border: none;
        border-radius: 10px;
        padding: 9px 18px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        flex: 0 0 auto;
    }

    .notification-edit-btn svg {
        height: 20px;
        width: 20px;
        flex-shrink: 0;
    }

    .notification-edit-btn:hover { background:#dc2626; transform:translateY(-1px) }
    @media (max-width:640px) {
        .notification-modal { max-height: calc(100vh - 1rem); border-radius:12px }
        #viewNotificationsModal .notification-modal { max-width:100% }
        .notification-modal-header, .notification-modal-body, .notification-modal-footer { padding-left:16px; padding-right:16px }
        .notification-modal-title { font-size:18px }
    }
</style>

<div class="modal-overlay" id="viewNotificationsModal">
    <div class="notification-modal">

        <!-- HEADER -->
        <div class="notification-modal-header">
            <div class="notification-modal-heading">

                <!-- kicker hidden via CSS; title is the headline -->
                <span class="notification-modal-kicker">Announcement details</span>
                <span id="viewNotificationTitle" class="notification-modal-title">PSITE Event</span>

                <!-- Author byline (inside heading) -->
                <div class="notification-modal-byline">
                    <div class="notification-modal-avatar" id="viewNotificationAuthorAvatar">JB</div>
                    <span class="notification-modal-author" id="viewNotificationAuthorName">System</span>
                </div>

                <!-- Pills (category, priority, audience) -->
                <div class="notification-modal-pills" id="viewNotificationBadges">
                    <!-- dynamically filled by JS if needed -->
                </div>

            </div>
            <button type="button" class="notification-modal-close" aria-label="Close notification" onclick="closeNotificationModal()">✕</button>
        </div>

        <!-- BODY -->
        <div class="notification-modal-body view-notification-body">

            <!-- Meta rows -->
            <div class="view-notification-meta">

                <div class="view-notification-meta-item">
                    <span class="meta-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                    <span class="view-notification-label">Published</span>
                    <strong id="viewNotificationPublished">May 21, 2026 · 05:34 PM</strong>
                </div>

                <div class="view-notification-meta-item">
                    <span class="meta-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    </span>
                    <span class="view-notification-label">Category</span>
                    <strong id="viewNotificationCategory">Event</strong>
                </div>

                <div class="view-notification-meta-item">
                    <span class="meta-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </span>
                    <span class="view-notification-label">Priority</span>
                    <strong id="viewNotificationPriority">Normal</strong>
                </div>

                <div class="view-notification-meta-item">
                    <span class="meta-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </span>
                    <span class="view-notification-label">Audience</span>
                    <strong id="viewNotificationAudience">All Employees</strong>
                </div>

            </div>

            <!-- Summary removed - use Details only -->
            <!-- Details -->
            <div class="view-notification-panel">
                <span class="view-notification-panel-label">Details</span>
                <p class="view-notification-content" id="viewNotificationDetails"></p>
            </div>

            <!-- Attachments -->
            <div class="view-notification-panel" id="viewNotificationAttachmentsWrap" style="display:none;">
                <div class="attachments-header">
                    <span class="attachments-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                        Attachments
                    </span>
                    <span class="attachments-count" id="viewNotificationAttachmentsCount">0 files</span>
                </div>
                <div id="viewNotificationAttachments"></div>
            </div>

        </div>
        <!-- /BODY -->

        <!-- FOOTER -->
        <div class="notification-modal-footer">
            <button type="button" class="notification-delete-btn" onclick="deleteCurrentNotificationForMe()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                Delete
            </button>
            <button type="button" class="notification-edit-btn" onclick="editCurrentNotification()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit announcement
            </button>
        </div>

    </div>
</div>

<div class="modal-overlay" id="attachmentPreviewModal" style="display:none;">
    <div class="attachment-preview-modal" role="dialog" aria-modal="true" aria-labelledby="attachmentPreviewName">
        <div class="attachment-preview-header">
            <div class="attachment-preview-heading">
                <span class="attachment-preview-kicker">Attachment preview</span>
                <span class="attachment-preview-name" id="attachmentPreviewName">Attachment</span>
                <span class="attachment-preview-meta" id="attachmentPreviewMeta">File preview</span>
            </div>
            <button type="button" class="attachment-preview-close" aria-label="Close attachment preview" onclick="closeAttachmentPreviewModal()">✕</button>
        </div>
        <div class="attachment-preview-body">
            <div class="attachment-preview-stage" id="attachmentPreviewStage"></div>
        </div>
        <div class="attachment-preview-footer">
            <a id="attachmentPreviewDownload" class="attachment-preview-download" href="#" download>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>
                Download
            </a>
        </div>
    </div>
</div>

<script>
    function closeNotificationModal() { document.getElementById('viewNotificationsModal').style.display = 'none'; }
    function closeAttachmentPreviewModal() {
        const modal = document.getElementById('attachmentPreviewModal');
        const stage = document.getElementById('attachmentPreviewStage');
        if (stage) stage.innerHTML = '';
        if (modal) modal.style.display = 'none';
    }
    function deleteCurrentNotificationForMe() { if (typeof deleteCurrentNotificationForMeHandler === 'function') return deleteCurrentNotificationForMeHandler(); showToast('Delete triggered', 'info'); }
    function editCurrentNotification() { if (typeof editCurrentNotificationHandler === 'function') return editCurrentNotificationHandler(); showToast('Edit triggered', 'info'); }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAttachmentPreviewModal();
        }
    });

    document.addEventListener('click', function (event) {
        const previewModal = document.getElementById('attachmentPreviewModal');
        if (previewModal && event.target === previewModal) {
            closeAttachmentPreviewModal();
        }
    });
</script>