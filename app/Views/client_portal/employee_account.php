<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
    $identity = $identity ?? [];
    $employment = $employment ?? [];
    $contact = $contact ?? [];
    $emergency = $emergency ?? [];
    $audit = $audit ?? [];

    $joinedLabel = ! empty($identity['joined']) ? date('Y', strtotime((string) $identity['joined'])) : 'N/A';
    $birthdateLabel = ! empty($identity['birthdate']) ? date('F j, Y', strtotime((string) $identity['birthdate'])) : '-';
    $hireDateLabel = ! empty($employment['hire_date']) ? date('F j, Y', strtotime((string) $employment['hire_date'])) : '-';
    $auditLabel = ! empty($audit['updated_at']) ? date('F j, Y g:i A', strtotime((string) $audit['updated_at'])) : '-';
    $middleInitial = ! empty($identity['middle_name']) ? strtoupper(substr(trim((string) $identity['middle_name']), 0, 1)) . '.' : '';
?>

<!-- ACCOUNT PAGE -->
<div class="account-container">

    <!-- TOP TABS -->
    <div class="tabs">
        <div class="tab active">Account</div>
    </div>

    <!-- GRID -->
    <div class="account-grid scroll-bar">

        <!-- PERSONAL IDENTITY (READ-ONLY MOSTLY) -->
        <div class="card">
            <h2>Personal Identity</h2>

            <div class="about-content">
                <div class="about-avatar" style="<?= ! empty($identity['photo_url']) ? 'background-image:url(' . esc($identity['photo_url']) . ');background-size:cover;background-position:center;' : '' ?>"></div>

                <div class="about-info">
                    <div class="name"><?= esc($identity['last_name'] ?? '') ?>, <?= esc($identity['first_name'] ?? '') ?> <?= esc($identity['middle_name'] ?? '') ?></div>
                    <div class="joined">Joined in <?= esc($joinedLabel) ?></div>

                    <form id="photoUploadForm" method="post" action="<?= site_url('client_portal/profile/photo') ?>" enctype="multipart/form-data" class="photo-upload-form">
                        <?= csrf_field() ?>
                        <input id="profile_photo" type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp" class="photo-input" hidden>
                        <button type="button" class="photo-link-btn" onclick="document.getElementById('profile_photo').click()">Update photo</button>
                    </form>
                </div>
            </div>

            <div class="info-list">
                <form id="identityForm" method="post" action="<?= site_url('client_portal/profile/identity') ?>" class="identity-form">
                    <?= csrf_field() ?>

                    <div class="identity-grid">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?= esc($identity['last_name'] ?? '') ?>, <?= esc($identity['first_name'] ?? '') ?> <?= esc($middleInitial) ?>" maxlength="255" placeholder="Dela Cruz, Juan Miguel Santos" title="Use format: Lastname, FirstName(s) MiddleName" required>
                        </div>

                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?= esc($identity['username'] ?? '') ?>" minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" required>
                        </div>

                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="birthdate" value="<?= esc((string) ($identity['birthdate'] ?? '')) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Gender</label>
                            <input type="text" value="Male" disabled>
                        </div>
                    </div>

                    <div class="form-actions identity-actions">
                        <button type="submit" id="saveIdentityBtn" class="btn btn-primary hidden">Save Identity Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- EMPLOYMENT DETAILS (READ-ONLY) -->
        <div class="card">
            <h2>Employment Details</h2>

            <div class="info-list">
                <div class="info-row"><span>Employee ID</span><strong><?= esc($employment['employee_number'] ?? '-') ?></strong></div>
                <div class="info-row"><span>Department</span><strong><?= esc($employment['department'] ?? '-') ?></strong></div>
                <div class="info-row"><span>Job Title</span><strong><?= esc($employment['position'] ?? '-') ?></strong></div>
                <div class="info-row"><span>Employment Type</span><strong><?= esc($employment['employment_type'] ?? '-') ?></strong></div>
                <div class="info-row"><span>Hire Date</span><strong><?= esc($hireDateLabel) ?></strong></div>
                <div class="info-row">
                    <span>Status</span>
                    <?php
                        $accountActive  = ($employment['status'] ?? '') === 'Active';
                        $onlineStatus   = $employment['online_status'] ?? 'Offline';
                        $presenceBadges = [
                            'Online'  => 'bg-green-200 text-green-700',
                            'Away'    => 'bg-yellow-200 text-yellow-700',
                            'Offline' => 'bg-gray-200 text-gray-500',
                        ];
                        $badgeClass = $accountActive
                            ? ($presenceBadges[$onlineStatus] ?? $presenceBadges['Offline'])
                            : 'bg-red-200 text-red-600';
                        $badgeText  = $accountActive ? $onlineStatus : 'Inactive';
                        $employeeId = $employment['employee_id'] ?? '';
                    ?>
                    <strong
                        class="<?= $badgeClass ?> px-3 py-1 rounded-full text-xs font-semibold"
                        <?= $accountActive ? "data-presence-badge=\"{$employeeId}\" data-current-status=\"{$onlineStatus}\"" : '' ?>>
                        <?= esc($badgeText) ?>
                    </strong>
                </div>
                <div class="info-row"><span>Reports To</span><strong><?= esc($employment['reports_to'] ?? '-') ?></strong></div>
            </div>
        </div>

        <!-- CONTACT INFORMATION (EDITABLE) -->
        <div class="card">
            <h2>Contact Information</h2>

            <form method="post" action="<?= site_url('client_portal/profile/contact') ?>">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Primary Email (CI4 Shield)</label>
                    <input type="email" value="<?= esc($contact['primary_email'] ?? '') ?>" disabled>
                </div>

                <div class="form-group">
                    <label>Secondary Email</label>
                    <input type="email" name="secondary_email" value="<?= esc($contact['secondary_email'] ?? '') ?>" placeholder="name@company.com">
                </div>

                <div class="form-group">
                    <label>Mobile Number</label>
                    <input type="text" name="mobile_number" value="<?= esc($contact['mobile_number'] ?? '') ?>" placeholder="09xx-xxx-xxxx">
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <textarea rows="3" name="address" placeholder="Enter current address"><?= esc($contact['address'] ?? '') ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" id="saveContactBtn" class="btn btn-primary hidden">Save Contact Info</button>
                </div>
            </form>
        </div>

        <!-- EMERGENCY CONTACT (EDITABLE) -->
        <div class="card">
            <h2>Emergency Contact</h2>

            <form method="post" action="<?= site_url('client_portal/profile/emergency') ?>">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Contact Name</label>
                    <input type="text" name="emergency_name" value="<?= esc($emergency['emergency_name'] ?? '') ?>" placeholder="Full name">
                </div>

                <div class="form-group">
                    <label>Relationship</label>
                    <input type="text" name="emergency_relationship" value="<?= esc($emergency['emergency_relationship'] ?? '') ?>" placeholder="Relationship">
                </div>

                <div class="form-group">
                    <label>Primary Phone</label>
                    <input type="text" name="emergency_phone_primary" value="<?= esc($emergency['emergency_phone_primary'] ?? '') ?>" placeholder="Primary phone">
                </div>

                <div class="form-group">
                    <label>Alternate Phone</label>
                    <input type="text" name="emergency_phone_alternate" value="<?= esc($emergency['emergency_phone_alternate'] ?? '') ?>" placeholder="Alternate phone (optional)">
                </div>

                <div class="form-actions">
                    <button type="submit" id="saveEmergencyBtn" class="btn btn-primary hidden">Save Emergency Contact</button>
                </div>
            </form>
        </div>

        <!-- SECURITY (PASSWORD UPDATE ACTION) -->
        <div class="card span-2">
            <h2>Security</h2>

            <form method="post" action="<?= site_url('client_portal/profile/password') ?>">
                <?= csrf_field() ?>
                <div class="security-grid">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" placeholder="Enter current password" required>
                    </div>

                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" placeholder="Enter new password" required>
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>

        <!-- PAYSLIP PASSWORD -->
        <div class="card span-2">
            <h2>Payslip Password</h2>
            <p class="text-muted" style="margin-bottom: 1rem; font-size: 0.875rem;">
                This password protects the PDF attachment sent to your email.
                If not set, the default is your <strong>Employee Number + Birthdate (YYYYMMDD)</strong>.
            </p>

            <form method="post" action="<?= site_url('client_portal/profile/payslip-password') ?>">
                <?= csrf_field() ?>
                <div class="security-grid">
                    <div class="form-group">
                        <label>Current Payslip Password</label>
                        <input type="password" name="current_payslip_password"
                            placeholder="Enter current payslip password" required>
                    </div>
                    <div class="form-group">
                        <label>New Payslip Password</label>
                        <input type="password" name="new_payslip_password"
                            placeholder="Enter new payslip password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Payslip Password</label>
                        <input type="password" name="confirm_payslip_password"
                            placeholder="Confirm new payslip password" required>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Payslip Password</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('resetPayslipModal').style.display='flex'">
                        Reset to Default
                    </button>
                </div>

                
            </form>
        </div>

        <!-- SMALL AUDIT LINE -->
        <div class="card span-2 audit-card">
            <span>Last updated: <?= esc($auditLabel) ?> by <?= esc($audit['updated_by'] ?? 'System') ?></span>
        </div>

    </div>
</div>

<!-- RESET PAYSLIP PASSWORD MODAL -->
<div id="resetPayslipModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:8px; padding:2rem; max-width:420px; width:90%; box-shadow:0 8px 32px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0;">Reset Payslip Password</h3>
        <p style="color:#555; font-size:0.9rem;">
            This will remove your custom payslip password. Future payslips will be locked with the default password
            (<strong>Employee Number + Birthdate YYYYMMDD</strong>).<br><br>
            Enter your <strong>account password</strong> to confirm.
        </p>

        <form method="post" action="<?= site_url('client_portal/profile/payslip-password/reset') ?>">
            <?= csrf_field() ?>
            <div class="form-group" style="margin-bottom:1rem;">
                <label>Account Password</label>
                <input type="password" name="account_password" placeholder="Enter your account password" required
                       style="width:100%; padding:0.5rem; border:1px solid #ccc; border-radius:4px;">
            </div>
            <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
                <button type="button" class="btn btn-secondary"
                        onclick="document.getElementById('resetPayslipModal').style.display='none'">
                    Cancel
                </button>
                <button type="submit" class="btn btn-danger">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<!-- PHOTO EDITOR MODAL -->
<div id="photoEditorModal" class="modal-overlay hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Profile Photo</h3>
            <button type="button" class="modal-close" onclick="closePhotoModal()">&times;</button>
        </div>
        
        <div class="modal-body">
            <div class="editor-canvas-wrapper">
                <canvas id="photoCanvas"></canvas>
            </div>
            
            <div class="editor-controls">
                <div class="control-group">
                    <label>Zoom</label>
                    <div class="control-row">
                        <input type="range" id="zoomSlider" min="1" max="3" step="0.1" value="1" class="slider" oninput="setZoom(this.value)">
                        <span id="zoomValue" class="value">100%</span>
                    </div>
                </div>
                
                <div class="control-group">
                    <label>Rotate</label>
                    <div class="control-row">
                        <button type="button" class="control-btn" onclick="rotateImage(-90)">← Rotate</button>
                        <span id="rotateValue" class="value">0°</span>
                        <button type="button" class="control-btn" onclick="rotateImage(90)">Rotate →</button>
                    </div>
                </div>
                
                <div class="control-group">
                    <label>Position</label>
                    <div class="control-row">
                        <button type="button" class="control-btn" onclick="resetPosition()">Reset</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-cancel" onclick="closePhotoModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="confirmPhotoEdit()">Confirm & Upload</button>
        </div>
    </div>
</div>

<style>
    /* MODAL OVERLAY */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        font-family: Segoe UI, Arial;
    }

    .modal-overlay.hidden {
        display: none;
    }

    /* MODAL CONTENT */
    .modal-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 25px;
        border-bottom: 1px solid #e5e7eb;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 28px;
        cursor: pointer;
        color: #6b7280;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-close:hover {
        color: #000;
    }

    /* MODAL BODY */
    .modal-body {
        flex: 1;
        padding: 20px 25px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .editor-canvas-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        background: #f3f4f6;
        border-radius: 8px;
        padding: 15px;
        min-height: 300px;
        max-height: 400px;
        overflow: auto;
    }

    #photoCanvas {
        max-width: 100%;
        max-height: 100%;
        border-radius: 6px;
        cursor: grab;
    }

    #photoCanvas:active {
        cursor: grabbing;
    }

    /* EDITOR CONTROLS */
    .editor-controls {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .control-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .control-group label {
        font-weight: 600;
        font-size: 13px;
        color: #374151;
    }

    .control-row {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .slider {
        flex: 1;
        height: 6px;
        border-radius: 3px;
        background: #e5e7eb;
        outline: none;
        appearance: none;
        -webkit-appearance: none;
    }

    .slider::-webkit-slider-thumb {
        appearance: none;
        -webkit-appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #2563eb;
        cursor: pointer;
    }

    .slider::-moz-range-thumb {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #2563eb;
        cursor: pointer;
        border: none;
    }

    .value {
        font-size: 12px;
        color: #6b7280;
        min-width: 45px;
        text-align: center;
    }

    .control-btn {
        padding: 6px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #f9fafb;
        cursor: pointer;
        font-size: 12px;
        font-weight: 500;
        transition: all 0.2s;
    }

    .control-btn:hover {
        background: #e5e7eb;
        border-color: #9ca3af;
    }

    /* MODAL FOOTER */
    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        padding: 15px 25px;
        border-top: 1px solid #e5e7eb;
        background: #f9fafb;
    }

    .btn-cancel {
        background: #e5e7eb;
        color: #374151;
    }

    .btn-cancel:hover {
        background: #d1d5db;
    }

    /* CONTAINER */
    .account-container{
        font-family:Segoe UI, Arial;
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        min-height: 0;
        background-color: #e5e7eb;
    }

    /* TABS */
    .tabs{
        display:flex;
        align-items:center;
        gap:30px;
        background:#2E2E2E;
        color:white;
        padding:15px 25px;
        min-height:70px;
        position: sticky;
        top: 0;
        z-index: 10;
        flex-shrink: 0;
    }

    .tab{
        cursor:pointer;
        opacity:0.8;
    }

    .tab.active{
        font-weight:600;
        opacity:1;
        position:relative;
    }

    .tab.active::after{
        content:'';
        position:absolute;
        bottom:-24px;
        left:0;
        width:100%;
        height:3px;
        background:white;
    }

    /* GRID */
    .account-grid{
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:25px;
        padding: 2rem;
    }

    /* CARD */
    .card{
        background:white;
        border-radius:10px;
        padding:25px;
        box-shadow:0 4px 15px rgba(0,0,0,0.05);
    }

    .card h2{
        font-size:20px;
        margin-bottom:20px;
        font-weight:500;
    }

    .span-2{
        grid-column: span 2;
    }

    /* HEADER WITH PLUS */
    .card-header{
        display:flex;
        justify-content:space-between;
        align-items:center;
    }

    .plus{
        font-size:20px;
        cursor:pointer;
    }

    /* ABOUT */
    .about-content{
        display:flex;
        gap:20px;
    }

    .about-avatar{
        width:80px;
        height:80px;
        border-radius:50%;
        background:#e5e7eb;
    }

    .about-info{
        flex:1;
    }

    .name{
        font-weight:600;
    }

    .joined{
        font-size:12px;
        color:#9ca3af;
    }

    .name-row{
        display:flex;
        justify-content:space-between;
        align-items:center;
    }

    .paypal-link{
        margin-top:10px;
        font-size:14px;
        border-bottom:1px solid #ddd;
        padding-bottom:5px;
    }

    .subtext{
        font-size:12px;
        color:#9ca3af;
        margin-top:5px;
    }

    .photo-link{
        display:inline-block;
        margin-top:10px;
        font-size:12px;
        color:#1f8dbd;
    }

    .photo-upload-form{
        margin-top:10px;
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    }

    .photo-input{
        max-width:220px;
        font-size:12px;
    }

    .photo-link-btn{
        border:none;
        background:transparent;
        color:#1f8dbd;
        font-size:12px;
        cursor:pointer;
        padding:0;
        text-decoration:underline;
    }

    /* ITEMS */
    .item{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        padding:10px 0;
        border-bottom:1px solid #eee;
    }

    .item:last-child{
        border-bottom:none;
    }

    .actions{
        font-size:13px;
        color:#1f8dbd;
        cursor:pointer;
    }

    /* FORM */
    .form-group{
        margin-bottom:15px;
    }

    .form-group label{
        display:block;
        font-size:13px;
        margin-bottom:5px;
    }

    .form-group select{
        width:100%;
        padding:8px;
        border-radius:6px;
        border:1px solid #ddd;
    }

    .row{
        display:flex;
        justify-content:space-between;
        align-items:center;
    }

    .info-list{
        margin-top:15px;
        display:flex;
        flex-direction:column;
        gap:10px;
    }

    .info-row{
        display:flex;
        justify-content:space-between;
        gap:20px;
        border-bottom:1px solid #f1f5f9;
        padding-bottom:10px;
    }

    .identity-form {
        width: 100%;
    }

    .identity-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .identity-actions {
        margin-top: 6px;
        min-height: 42px;
    }

    .hidden {
        display: none;
    }

    .info-row span{
        color:#6b7280;
        font-size:13px;
    }

    .info-row strong{
        font-size:14px;
    }

    .form-group input,
    .form-group textarea,
    .form-group select{
        width:100%;
        padding:8px;
        border-radius:6px;
        border:1px solid #ddd;
    }

    .form-actions{
        margin-top:12px;
        display:flex;
        justify-content:flex-end;
    }

    .btn{
        border:none;
        border-radius:8px;
        padding:10px 14px;
        cursor:pointer;
        font-size:13px;
    }

    .btn-primary{
        background:#2563eb;
        color:#fff;
    }

    .security-grid{
        display:grid;
        grid-template-columns:repeat(3, minmax(0, 1fr));
        gap:14px;
    }

    .audit-card{
        font-size:13px;
        color:#475569;
        padding-top:14px;
        padding-bottom:14px;
        background:#f8fafc;
        border:1px solid #e2e8f0;
    }

    /* LINKS */
    .link{
        display:block;
        margin-top:10px;
        font-size:13px;
        color:#1f8dbd;
    }

    .link.danger{
        color:#ef4444;
    }

    /* MUTED */
    .muted{
        font-size:12px;
        color:#9ca3af;
    }

    @media (max-width: 1024px){
        .account-grid{
            grid-template-columns:1fr;
        }

        .span-2{
            grid-column: span 1;
        }

        .security-grid{
            grid-template-columns:1fr;
        }

        .identity-grid {
            grid-template-columns: 1fr;
        }

        .identity-actions {
            justify-content: flex-start;
        }
    }
</style>

<script>
    let editorState = {
        image: null,
        zoom: 1,
        rotate: 0,
        offsetX: 0,
        offsetY: 0,
        isDragging: false,
        dragStartX: 0,
        dragStartY: 0,
        originalFile: null,
        dragListenersBound: false
    };

    // Handle file input change
    document.getElementById('profile_photo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            editorState.originalFile = file;
            const reader = new FileReader();
            reader.onload = function(event) {
                const img = new Image();
                img.onload = function() {
                    editorState.image = img;
                    editorState.zoom = 1;
                    editorState.rotate = 0;
                    editorState.offsetX = 0;
                    editorState.offsetY = 0;
                    document.getElementById('zoomSlider').value = '1';
                    document.getElementById('zoomValue').textContent = '100%';
                    document.getElementById('rotateValue').textContent = '0°';
                    updatePreview();
                    openPhotoModal();
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    function openPhotoModal() {
        document.getElementById('photoEditorModal').classList.remove('hidden');
        setupCanvasDragListener();
    }

    function setZoom(value) {
        editorState.zoom = Number.parseFloat(value) || 1;
        updatePreview();
    }

    function closePhotoModal() {
        document.getElementById('photoEditorModal').classList.add('hidden');
        document.getElementById('profile_photo').value = '';
        editorState.originalFile = null;
    }

    function updatePreview() {
        const canvas = document.getElementById('photoCanvas');
        const ctx = canvas.getContext('2d');
        
        if (!editorState.image) return;

        // Update zoom display
        document.getElementById('zoomValue').textContent = Math.round(editorState.zoom * 100) + '%';

        // Set canvas size
        canvas.width = 400;
        canvas.height = 400;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Save context state
        ctx.save();
        
        // Apply transformations
        ctx.translate(canvas.width / 2, canvas.height / 2);
        ctx.rotate((editorState.rotate * Math.PI) / 180);
        ctx.scale(editorState.zoom, editorState.zoom);
        ctx.translate(editorState.offsetX, editorState.offsetY);
        
        // Draw image centered
        const imgWidth = editorState.image.width;
        const imgHeight = editorState.image.height;
        const scale = Math.min(canvas.width / imgWidth, canvas.height / imgHeight);
        ctx.drawImage(
            editorState.image,
            -(imgWidth * scale) / 2,
            -(imgHeight * scale) / 2,
            imgWidth * scale,
            imgHeight * scale
        );

        ctx.restore();
    }

    function rotateImage(degrees) {
        editorState.rotate = (editorState.rotate + degrees) % 360;
        document.getElementById('rotateValue').textContent = editorState.rotate + '°';
        updatePreview();
    }

    function resetPosition() {
        editorState.offsetX = 0;
        editorState.offsetY = 0;
        updatePreview();
    }

    function setupCanvasDragListener() {
        if (editorState.dragListenersBound) {
            return;
        }

        editorState.dragListenersBound = true;
        const canvas = document.getElementById('photoCanvas');
        
        canvas.addEventListener('mousedown', (e) => {
            editorState.isDragging = true;
            editorState.dragStartX = e.clientX;
            editorState.dragStartY = e.clientY;
        });

        document.addEventListener('mousemove', (e) => {
            if (editorState.isDragging && editorState.image) {
                const dx = (e.clientX - editorState.dragStartX) / editorState.zoom;
                const dy = (e.clientY - editorState.dragStartY) / editorState.zoom;
                editorState.offsetX += dx;
                editorState.offsetY += dy;
                editorState.dragStartX = e.clientX;
                editorState.dragStartY = e.clientY;
                updatePreview();
            }
        });

        document.addEventListener('mouseup', () => {
            editorState.isDragging = false;
        });
    }

    function confirmPhotoEdit() {
        const canvas = document.getElementById('photoCanvas');
        canvas.toBlob((blob) => {
            const dataTransfer = new DataTransfer();
            const file = new File([blob], editorState.originalFile.name, { type: 'image/png' });
            dataTransfer.items.add(file);
            document.getElementById('profile_photo').files = dataTransfer.files;
            
            // Submit the form
            document.getElementById('photoUploadForm').submit();
            closePhotoModal();
        }, 'image/png');
    }

    // Change detection for Identity Form
    (function () {
        const form = document.getElementById('identityForm');
        const saveBtn = document.getElementById('saveIdentityBtn');

        if (!form || !saveBtn) return;

        form.addEventListener('submit', function (e) {
            const fullNameInput = form.querySelector('input[name="full_name"]');
            const fullName = (fullNameInput?.value || '').trim();
            const nameFormat = /^[^,]+,\s*[^,]+$/;

            if (!nameFormat.test(fullName)) {
                e.preventDefault();
                alert('Full Name must follow this format: Lastname, FirstName(s) MiddleName');
                fullNameInput?.focus();
                return;
            }

            const [lastName, given] = fullName.split(',', 2).map((part) => part.trim());
            if (!lastName || !given) {
                e.preventDefault();
                alert('Please provide both last name and first name(s).');
                fullNameInput?.focus();
            }
        });

        const fields = Array.from(form.querySelectorAll('input[name]'));
        const initialValues = new Map(fields.map((field) => [field.name, (field.value || '').trim()]));

        function syncSaveVisibility() {
            const hasChanged = fields.some((field) => {
                const initial = initialValues.get(field.name) ?? '';
                const current = (field.value || '').trim();

                return current !== initial;
            });

            saveBtn.classList.toggle('hidden', !hasChanged);
        }

        fields.forEach((field) => {
            field.addEventListener('input', syncSaveVisibility);
            field.addEventListener('change', syncSaveVisibility);
        });

        syncSaveVisibility();
    })();

    // Change detection for Contact Form
    (function () {
        const saveBtn = document.getElementById('saveContactBtn');
        if (!saveBtn) return;

        const form = saveBtn.closest('form');
        if (!form) return;

        const fields = Array.from(form.querySelectorAll('input[name], textarea[name]'));
        const initialValues = new Map(fields.map((field) => [field.name, (field.value || '').trim()]));

        function syncSaveVisibility() {
            const hasChanged = fields.some((field) => {
                const initial = initialValues.get(field.name) ?? '';
                const current = (field.value || '').trim();

                return current !== initial;
            });

            saveBtn.classList.toggle('hidden', !hasChanged);
        }

        fields.forEach((field) => {
            field.addEventListener('input', syncSaveVisibility);
            field.addEventListener('change', syncSaveVisibility);
        });

        syncSaveVisibility();
    })();

    // Change detection for Emergency Contact Form
    (function () {
        const saveBtn = document.getElementById('saveEmergencyBtn');
        if (!saveBtn) return;

        const form = saveBtn.closest('form');
        if (!form) return;

        const fields = Array.from(form.querySelectorAll('input[name]'));
        const initialValues = new Map(fields.map((field) => [field.name, (field.value || '').trim()]));

        function syncSaveVisibility() {
            const hasChanged = fields.some((field) => {
                const initial = initialValues.get(field.name) ?? '';
                const current = (field.value || '').trim();

                return current !== initial;
            });

            saveBtn.classList.toggle('hidden', !hasChanged);
        }

        fields.forEach((field) => {
            field.addEventListener('input', syncSaveVisibility);
            field.addEventListener('change', syncSaveVisibility);
        });

        syncSaveVisibility();
    })();
</script>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/components/flash-notifications.js') ?>"></script>
<?= $this->endSection() ?>