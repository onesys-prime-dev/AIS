<div class="modal-overlay" id="viewNotificationsModal">
    <div class="notification-modal">
        <div class="notification-modal-header">
            <div class="notification-modal-heading">
                <span id="viewNotificationTitle" class="notification-modal-title">Notification</span>
            </div>
            <button type="button" class="notification-modal-close" aria-label="Close notification" onclick="closeNotificationModal()">✕</button>
        </div>

        <div class="notification-modal-body view-notification-body">
            <div class="view-notification-meta">
                <div class="view-notification-meta-item">
                    <span class="view-notification-label">Published</span>
                    <strong id="viewNotificationPublished">N/A</strong>
                </div>
                <div class="view-notification-meta-item">
                    <span class="view-notification-label">Category</span>
                    <strong id="viewNotificationCategory">General</strong>
                </div>
                <div class="view-notification-meta-item">
                    <span class="view-notification-label">Priority</span>
                    <strong id="viewNotificationPriority">Normal</strong>
                </div>
                <div class="view-notification-meta-item">
                    <span class="view-notification-label">Audience</span>
                    <strong id="viewNotificationAudience">All Employees</strong>
                </div>
            </div>

            <div class="view-notification-panel">
                <span class="view-notification-panel-label">Summary</span>
                <p class="view-notification-content" id="viewNotificationSummary"></p>
            </div>

            <div class="view-notification-panel">
                <span class="view-notification-panel-label">Details</span>
                <p class="view-notification-content" id="viewNotificationDetails"></p>
            </div>
        </div>

        <div class="notification-modal-footer">
            <button type="button" class="notification-cancel-btn" onclick="closeNotificationModal()">Close</button>
        </div>
    </div>
</div>