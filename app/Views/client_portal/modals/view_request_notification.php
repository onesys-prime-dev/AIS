<div class="modal-overlay" id="viewRequestNotificationModal" style="display:none;">
    <div class="request-notification-modal">
        <div class="request-notification-header">
            <div>
                <h2 id="viewRequestNotificationTitle" class="request-notification-title">Request Notification</h2>
            </div>
            <button type="button" class="request-notification-close" onclick="closeNotificationModal()">
                <span>✕</span>
            </button>
        </div>

        <div class="request-notification-body">
            <div class="request-notification-main">
                <div class="request-author-card">
                    <div class="request-author-avatar" id="viewRequestAuthorAvatar">JB</div>
                    <div class="request-author-meta">
                        <div class="request-author-name" id="viewRequestAuthorName"></div>
                        <div class="request-author-subtitle"></div>
                    </div>
                </div>

                <div class="request-details-list">
                    <div class="request-detail-row">
                        <span class="request-detail-label">Request type</span>
                        <strong class="request-detail-value" id="viewRequestType">Leave</strong>
                    </div>
                    <div class="request-detail-row">
                        <span class="request-detail-label">Date submitted</span>
                        <strong class="request-detail-value" id="viewRequestSubmittedAt"></strong>
                    </div>
                    <div class="request-detail-row">
                        <span class="request-detail-label">Processed by</span>
                        <strong class="request-detail-value" id="viewRequestProcessedBy">System</strong>
                    </div>
                    <div class="request-detail-row">
                        <span class="request-detail-label">Reference</span>
                        <strong class="request-detail-value" id="viewRequestRef">#1</strong>
                    </div>
                </div>
            </div>

            <aside class="request-notification-side">
                <div class="request-status-block">
                    <div class="request-side-label">Status</div>
                    <div id="viewRequestStatusBadge" class="request-status-badge is-neutral">Pending</div>
                </div>

                <div class="request-approved-by-block">
                    <div class="request-side-label">Approved by</div>
                    <div class="request-approved-person">
                        <div class="request-approved-avatar" id="viewRequestApprovedAvatar">JB</div>
                        <div>
                            <div class="request-approved-name" id="viewRequestApprovedBy">Juan Bonifacio</div>
                            <div class="request-approved-role" id="viewRequestApprovalRole">Reviewer</div>
                        </div>
                    </div>
                </div>

                <div class="request-notes-block">
                    <div class="request-side-label">HR notes</div>
                    <div class="request-notes-content" id="viewRequestDetails">Your leave has been reviewed and approved. Please coordinate with your team lead before your leave date.</div>
                </div>
            </aside>
        </div>

        <div class="request-notification-footer">
            <button type="button" class="request-action-btn danger" onclick="deleteCurrentNotificationForMe()">
                <i class="fa-regular fa-trash-can"></i>
                Delete
            </button>
            <!-- <button type="button" class="request-action-btn secondary" onclick="closeNotificationModal()">Close</button> -->
            <a id="viewRequestLink" href="#" class="request-action-btn primary">
                <span>View request</span>
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
            </a>
        </div>
    </div>
</div>

<style>
    #viewRequestNotificationModal.modal-overlay {
        align-items: center;
        justify-content: center;
        padding: 18px;
    }

    #viewRequestNotificationModal .request-notification-modal {
        width: min(760px, 100%);
        background: linear-gradient(180deg, #ffffff 0%, #fafafa 100%);
        color: #111827;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        box-shadow: 0 26px 60px rgba(15, 23, 42, 0.16);
        overflow: hidden;
    }

    #viewRequestNotificationModal .request-notification-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 22px;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    }

    #viewRequestNotificationModal .request-notification-kicker {
        color: #6d28d9;
        font-size: 13px;
        font-weight: 700;
        text-transform: lowercase;
        margin-bottom: 2px;
    }

    #viewRequestNotificationModal .request-notification-title {
        margin: 0;
        font-size: 18px;
        line-height: 1.2;
        font-weight: 800;
        color: #111827;
    }

    #viewRequestNotificationModal .request-notification-close {
        width: 34px;
        height: 34px;
        border: 0;
        border-radius: 999px;
        background: #f3f4f6;
        color: #374151;
        cursor: pointer;
        flex-shrink: 0;

        display: grid;
        place-items: center;

        padding: 0;

        font-size: 18px;
        line-height: 0;

        appearance: none;
        -webkit-appearance: none;
    }

    #viewRequestNotificationModal .request-notification-close span{
        font-size: 18px;
        line-height: 1;
    }

    #viewRequestNotificationModal .request-notification-body {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(260px, 0.95fr);
        min-height: 340px;
    }

    #viewRequestNotificationModal .request-notification-main {
        padding: 22px;
        border-right: 1px solid #e5e7eb;
    }

    #viewRequestNotificationModal .request-author-card {
        display: flex;
        align-items: center;
        gap: 14px;
        padding-bottom: 18px;
        margin-bottom: 18px;
        border-bottom: 1px solid #e5e7eb;
    }

    #viewRequestNotificationModal .request-author-avatar,
    #viewRequestNotificationModal .request-approved-avatar {
        width: 38px;
        height: 38px;
        border-radius: 999px;
        display: grid;
        place-items: center;
        background: linear-gradient(135deg, #7c3aed, #5b21b6);
        color: #fff;
        font-weight: 800;
        font-size: 12px;
        flex-shrink: 0;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    #viewRequestNotificationModal .request-author-avatar
    {
        width: 60px;
        height: 60px;
        border-radius: 999px;
        display: grid;
        place-items: center;
        background: linear-gradient(135deg, #7c3aed, #5b21b6);
        color: #fff;
        font-weight: 800;
        font-size: 12px;
        flex-shrink: 0;
        background-size: cover;
        background-color: #e5e7eb;
        background-position: center;
        background-repeat: no-repeat;
    }

    #viewRequestNotificationModal .request-author-meta {
        min-width: 0;
    }

    #viewRequestNotificationModal .request-author-name {
        font-size: 16px;
        font-weight: 800;
        color: #111827;
        line-height: 1.2;
    }

    #viewRequestNotificationModal .request-author-subtitle {
        font-size: 13px;
        color: #6b7280;
        margin-top: 2px;
    }

    #viewRequestNotificationModal .request-details-list {
        display: grid;
        gap: 12px;
    }

    #viewRequestNotificationModal .request-detail-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 10px 0;
        border-bottom: 1px solid #e5e7eb;
    }

    #viewRequestNotificationModal .request-detail-label,
    #viewRequestNotificationModal .request-side-label {
        color: #6b7280;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    #viewRequestNotificationModal .request-detail-value {
        color: #111827;
        font-size: 14px;
        font-weight: 700;
        text-align: right;
        word-break: break-word;
    }

    #viewRequestNotificationModal .request-notification-side {
        display: grid;
        gap: 18px;
        padding: 22px;
        background: #f9fafb;
    }

    #viewRequestNotificationModal .request-status-block,
    #viewRequestNotificationModal .request-approved-by-block,
    #viewRequestNotificationModal .request-notes-block {
        display: grid;
        gap: 10px;
    }

    #viewRequestNotificationModal .request-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        width: fit-content;
        padding: 6px 12px;
        border-radius: 999px;
        font-weight: 800;
        font-size: 14px;
    }

    #viewRequestNotificationModal .request-status-badge::before {
        content: '';
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: currentColor;
        opacity: 0.9;
    }

    #viewRequestNotificationModal .request-status-badge.is-neutral {
        background: #eef2ff;
        color: #4f46e5;
    }

    #viewRequestNotificationModal .request-status-badge.is-approved {
        background: #dcfce7;
        color: #15803d;
    }

    #viewRequestNotificationModal .request-status-badge.is-rejected {
        background: #fee2e2;
        color: #b91c1c;
    }

    #viewRequestNotificationModal .request-approved-person {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    #viewRequestNotificationModal .request-approved-name {
        color: #111827;
        font-weight: 800;
        font-size: 14px;
        line-height: 1.2;
    }

    #viewRequestNotificationModal .request-approved-role {
        color: #6b7280;
        font-size: 12px;
        margin-top: 2px;
    }

    #viewRequestNotificationModal .request-notes-content {
        border: 1px solid #e5e7eb;
        background: #ffffff;
        border-radius: 12px;
        padding: 14px 16px;
        color: #374151;
        font-size: 14px;
        line-height: 1.7;
        white-space: pre-wrap;
    }

    #viewRequestNotificationModal .request-notification-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        padding: 16px 22px 20px;
        border-top: 1px solid #e5e7eb;
        background: #ffffff;
    }

    #viewRequestNotificationModal .request-action-btn {
        height: 40px;
        padding: 0 16px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        border: 0;
        cursor: pointer;
        transition: transform 0.15s ease, opacity 0.15s ease;
    }

    #viewRequestNotificationModal .request-action-btn:hover {
        transform: translateY(-1px);
    }

    #viewRequestNotificationModal .request-action-btn.danger {
        background: #f8d7da;
        color: #b42318;
    }

    #viewRequestNotificationModal .request-action-btn.secondary {
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #e5e7eb;
    }

    #viewRequestNotificationModal .request-action-btn.primary {
        background: #6d28d9;
        color: #ffffff;
    }

    @media (max-width: 840px) {
        #viewRequestNotificationModal .request-notification-body {
            grid-template-columns: 1fr;
        }

        #viewRequestNotificationModal .request-notification-main {
            border-right: 0;
            border-bottom: 1px solid #e5e7eb;
        }
    }

    @media (max-width: 560px) {
        #viewRequestNotificationModal .request-notification-header,
        #viewRequestNotificationModal .request-notification-main,
        #viewRequestNotificationModal .request-notification-side,
        #viewRequestNotificationModal .request-notification-footer {
            padding-left: 16px;
            padding-right: 16px;
        }

        #viewRequestNotificationModal .request-notification-footer {
            flex-wrap: wrap;
            justify-content: stretch;
        }

        #viewRequestNotificationModal .request-action-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>