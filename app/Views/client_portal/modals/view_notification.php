<div class="modal-overlay" id="viewNotificationsModal">
    <div class="modal">
        <div class="modal-header">
            <span id="viewNotificationTitle">Notification</span>
            <button type="button" onclick="closeNotificationModal()">✕</button>
        </div>

        <div class="modal-body view-notification-body">
            <div class="view-notification-meta">
                <p><strong>Published:</strong> <span id="viewNotificationPublished">N/A</span></p>
                <p><strong>Category:</strong> <span id="viewNotificationCategory">General</span></p>
                <p><strong>Priority:</strong> <span id="viewNotificationPriority">Normal</span></p>
                <p><strong>Audience:</strong> <span id="viewNotificationAudience">All Employees</span></p>
            </div>

            <p class="view-notification-content" id="viewNotificationSummary"></p>
            <p class="view-notification-content" id="viewNotificationDetails"></p>
        </div>

        <div class="modal-footer">
            <button type="button" class="cancel-btn" onclick="closeNotificationModal()">Close</button>
        </div>
    </div>
</div>