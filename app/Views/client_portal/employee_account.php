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
    $avatarInitials = strtoupper(substr(trim((string) ($identity['first_name'] ?? '')), 0, 1) . substr(trim((string) ($identity['last_name'] ?? '')), 0, 1));
    if ($avatarInitials === '') { $avatarInitials = 'U'; }

    $accountActive  = ($employment['status'] ?? '') === 'Active';
    $onlineStatus   = $employment['online_status'] ?? 'Offline';
    $presenceBadges = ['Online' => 'bg-green-200 text-green-700', 'Away' => 'bg-yellow-200 text-yellow-700', 'Offline' => 'bg-gray-200 text-gray-500'];
    $badgeClass     = $accountActive ? ($presenceBadges[$onlineStatus] ?? $presenceBadges['Offline']) : 'bg-red-200 text-red-600';
    $badgeText      = $accountActive ? $onlineStatus : 'Inactive';
    $employeeId     = $employment['employee_id'] ?? '';
    $presenceAttrs  = $accountActive ? "data-presence-badge=\"{$employeeId}\" data-current-status=\"{$onlineStatus}\"" : '';
?>

<!-- ACCOUNT PAGE -->
<div class="account-container">
    <aside class="profile-rail">
        <div class="profile-rail-person">
            <label class="about-avatar-wrap" for="profile_photo" aria-label="Change profile photo">
                <?php if (! empty($identity['photo_url'])): ?>
                    <div class="about-avatar" id="aboutAvatarPreview" style="background-image:url('<?= esc((string) $identity['photo_url'], 'attr') ?>');"></div>
                <?php else: ?>
                    <div class="about-avatar about-avatar-initials" id="aboutAvatarPreview"><?= esc($avatarInitials) ?></div>
                <?php endif; ?>
                <span class="about-avatar-edit-badge">&#9998;</span>
                <span class="avatar-status-dot <?= $badgeClass ?>" data-presence-dot <?= $presenceAttrs ?> title="<?= esc($badgeText) ?>"></span>
            </label>
            <div class="profile-rail-copy">
                <strong><?= esc(trim(($identity['first_name'] ?? '') . ' ' . ($identity['last_name'] ?? ''))) ?></strong>
                <span><?= esc($employment['position'] ?? 'Employee') ?></span>
                <span class="profile-rail-status <?= $badgeClass ?>" <?= $presenceAttrs ?>>
                    <span class="status-dot"></span><span data-presence-label><?= esc($badgeText) ?></span>
                </span>
            </div>
        </div>

        <form id="photoUploadForm" method="post" action="<?= site_url('client_portal/profile/photo') ?>" enctype="multipart/form-data" class="photo-upload-form">
            <?= csrf_field() ?>
            <input id="profile_photo" type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp" class="photo-input" style="position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;">
            <label for="profile_photo" class="photo-link-btn">Update photo</label>
            <span class="photo-hint">JPG, PNG or WEBP</span>
            <span id="photoUploadError" class="photo-upload-error hidden" aria-live="polite"></span>
        </form>

        <div class="profile-rail-meta">
            <span>Employee ID</span>
            <strong><?= esc($employment['employee_number'] ?? '-') ?></strong>
            <small>Joined <?= esc($joinedLabel) ?></small>
        </div>

        <div class="settings-nav-label">Settings</div>
        <nav class="settings-tabs" aria-label="Profile settings" role="tablist">
            <button type="button" id="profile-tab" class="settings-tab active" data-settings-tab="profile" role="tab" aria-controls="profile-panel" aria-selected="true">Personal info</button>
            <button type="button" id="employment-tab" class="settings-tab" data-settings-tab="employment" role="tab" aria-controls="employment-panel" aria-selected="false" tabindex="-1">Employment &amp; leave</button>
            <button type="button" id="security-tab" class="settings-tab" data-settings-tab="security" role="tab" aria-controls="security-panel" aria-selected="false" tabindex="-1">Login &amp; security</button>
            <button type="button" id="appearance-tab" class="settings-tab" data-settings-tab="appearance" role="tab" aria-controls="appearance-panel" aria-selected="false" tabindex="-1">Appearance</button>
        </nav>
    </aside>

    <main class="settings-main scroll-bar">
        <header class="settings-header">
            <h1>Settings</h1>
            <p>Manage your profile and account information.</p>
        </header>

        <div id="profile-panel" class="settings-panel active" data-settings-panel="profile" role="tabpanel" aria-labelledby="profile-tab">
            <section class="settings-section">
                <div class="section-heading"><h2>Personal information</h2><p>Your name and basic account details.</p></div>
                <form id="identityForm" method="post" action="<?= site_url('client_portal/profile/identity') ?>" class="settings-form">
                    <?= csrf_field() ?>
                    <div class="form-grid two-columns">
                        <div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?= esc($identity['last_name'] ?? '') ?>, <?= esc($identity['first_name'] ?? '') ?> <?= esc($middleInitial) ?>" maxlength="255" placeholder="Dela Cruz, Juan Miguel Santos" title="Use format: Lastname, FirstName(s) MiddleName" required></div>
                        <div class="form-group"><label>Username</label><input type="text" name="username" value="<?= esc($identity['username'] ?? '') ?>" minlength="3" maxlength="50" pattern="[A-Za-z0-9._\-]+" required></div>
                        <div class="form-group"><label>Date of Birth</label><input type="date" name="birthdate" value="<?= esc((string) ($identity['birthdate'] ?? '')) ?>" required></div>
                        <div class="form-group"><label>Gender</label><input type="text" value="Male" disabled></div>
                    </div>
                    <div class="form-actions"><span class="save-hint" aria-live="polite">Change a field to enable saving.</span><button type="submit" id="saveIdentityBtn" class="btn btn-primary" disabled>Save changes</button></div>
                </form>
            </section>

            <section class="settings-section">
                <div class="section-heading"><h2>Contact information</h2><p>Where the company can reach you.</p></div>
                <form method="post" action="<?= site_url('client_portal/profile/contact') ?>" class="settings-form">
                    <?= csrf_field() ?>
                    <div class="form-grid two-columns">
                        <div class="form-group"><label>Primary Email</label><input type="email" value="<?= esc($contact['primary_email'] ?? '') ?>" disabled></div>
                        <div class="form-group"><label>Secondary Email</label><input type="email" name="secondary_email" value="<?= esc($contact['secondary_email'] ?? '') ?>" placeholder="name@company.com"></div>
                        <div class="form-group"><label>Mobile Number</label><input type="text" name="mobile_number" value="<?= esc($contact['mobile_number'] ?? '') ?>" placeholder="09xx-xxx-xxxx"></div>
                        <div class="form-group form-group-wide"><label>Address</label><textarea rows="3" name="address" placeholder="Enter current address"><?= esc($contact['address'] ?? '') ?></textarea></div>
                    </div>
                    <div class="form-actions"><span class="save-hint" aria-live="polite">Change a field to enable saving.</span><button type="submit" id="saveContactBtn" class="btn btn-primary" disabled>Save contact info</button></div>
                </form>
            </section>

            <section class="settings-section">
                <div class="section-heading"><h2>Emergency contact</h2><p>Who we should contact in an emergency.</p></div>
                <form method="post" action="<?= site_url('client_portal/profile/emergency') ?>" class="settings-form">
                    <?= csrf_field() ?>
                    <div class="form-grid two-columns">
                        <div class="form-group"><label>Contact Name</label><input type="text" name="emergency_name" value="<?= esc($emergency['emergency_name'] ?? '') ?>" placeholder="Full name"></div>
                        <div class="form-group"><label>Relationship</label><input type="text" name="emergency_relationship" value="<?= esc($emergency['emergency_relationship'] ?? '') ?>" placeholder="Relationship"></div>
                        <div class="form-group"><label>Primary Phone</label><input type="text" name="emergency_phone_primary" value="<?= esc($emergency['emergency_phone_primary'] ?? '') ?>" placeholder="Primary phone"></div>
                        <div class="form-group"><label>Alternate Phone</label><input type="text" name="emergency_phone_alternate" value="<?= esc($emergency['emergency_phone_alternate'] ?? '') ?>" placeholder="Alternate phone (optional)"></div>
                    </div>
                    <div class="form-actions"><span class="save-hint" aria-live="polite">Change a field to enable saving.</span><button type="submit" id="saveEmergencyBtn" class="btn btn-primary" disabled>Save emergency contact</button></div>
                </form>
            </section>
        </div>

        <div id="employment-panel" class="settings-panel" data-settings-panel="employment" role="tabpanel" aria-labelledby="employment-tab" hidden>
            <section class="settings-section">
                <div class="section-heading"><h2>Employment details</h2><p>This information is managed by your company.</p></div>
                <div class="details-list">
                    <div class="detail-row"><span>Employee ID</span><strong><?= esc($employment['employee_number'] ?? '-') ?></strong></div>
                    <div class="detail-row"><span>Department</span><strong><?= esc($employment['department'] ?? '-') ?></strong></div>
                    <div class="detail-row"><span>Job Title</span><strong><?= esc($employment['position'] ?? '-') ?></strong></div>
                    <div class="detail-row"><span>Employment Type</span><strong><?= esc($employment['employment_type'] ?? '-') ?></strong></div>
                    <div class="detail-row"><span>Hire Date</span><strong><?= esc($hireDateLabel) ?></strong></div>
                    <div class="detail-row detail-row-status">
                        <span>Status</span>
                        <strong class="status-badge-lg <?= $badgeClass ?>" <?= $presenceAttrs ?>>
                            <span class="status-dot"></span><span data-presence-label><?= esc($badgeText) ?></span>
                        </strong>
                    </div>
                    <div class="detail-row"><span>Reports To</span><strong><?= esc($employment['reports_to'] ?? '-') ?></strong></div>
                </div>
            </section>

            <?php
                $currentYear = (int) date('Y');
                $leaveTypes = [
                    'vacation' => ['label' => 'Vacation Leave', 'data' => $vacationBalance ?? null, 'color' => '#7b5cff'],
                    'sick' => ['label' => 'Sick Leave', 'data' => $sickBalance ?? null, 'color' => '#3b82f6'],
                    'emergency' => ['label' => 'Emergency Leave', 'data' => $emergencyBalance ?? null, 'color' => '#f97316'],
                ];
                $hasAnyBalance = array_reduce($leaveTypes, fn($c, $t) => $c || ($t['data'] !== null), false);
            ?>
            <section class="settings-section">
                <div class="section-heading"><h2>Leave balance</h2><p><?= esc((string) $currentYear) ?> entitlement overview.</p></div>
                <?php if ($hasAnyBalance): ?>
                    <div class="leave-list">
                    <?php foreach ($leaveTypes as $typeInfo):
                        $bal = $typeInfo['data']; $total = (float) ($bal['total_entitlement'] ?? 0); $used = (float) ($bal['used'] ?? 0); $left = (float) ($bal['remaining'] ?? 0); $pct = $total > 0 ? round(($left / $total) * 100) : 0;
                    ?>
                        <div class="leave-row">
                            <div><strong><?= esc($typeInfo['label']) ?></strong><span><?= $total > 0 ? esc((string) $used) . ' days used' : 'No entitlement set' ?></span></div>
                            <div class="leave-value"><?= $total > 0 ? esc((string) $left) . ' / ' . esc((string) $total) . ' days' : '-' ?></div>
                            <div class="leave-track"><span style="width:<?= esc((string) $pct) ?>%;background:<?= esc($typeInfo['color']) ?>;"></span></div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-note">No leave balance on record for <?= esc((string) $currentYear) ?>.</p>
                <?php endif; ?>
            </section>
        </div>

        <div id="security-panel" class="settings-panel" data-settings-panel="security" role="tabpanel" aria-labelledby="security-tab" hidden>
            <section class="settings-section">
                <div class="section-heading"><h2>Account password</h2><p>Update the password used to sign in to your account.</p></div>
                <form method="post" action="<?= site_url('client_portal/profile/password') ?>" class="settings-form">
                    <?= csrf_field() ?>
                    <div class="form-grid three-columns">
                        <div class="form-group"><label>Current Password</label><input type="password" name="current_password" placeholder="Enter current password" required></div>
                        <div class="form-group"><label>New Password</label><input type="password" name="new_password" placeholder="Enter new password" required></div>
                        <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" placeholder="Confirm new password" required></div>
                    </div>
                    <div class="form-actions"><button type="submit" class="btn btn-primary">Update password</button></div>
                </form>
            </section>

            <section class="settings-section">
                <div class="section-heading"><h2>Payslip password</h2><p>Protects the PDF attachment sent to your email. The default is Employee Number + Birthdate (YYYYMMDD).</p></div>
                <form id="payslipPasswordForm" method="post" action="<?= site_url('client_portal/profile/payslip-password') ?>" class="settings-form">
                    <?= csrf_field() ?>
                    <div class="form-grid three-columns">
                        <div class="form-group"><label>Current Payslip Password</label><input type="password" name="current_payslip_password" placeholder="Enter current payslip password" required></div>
                        <div class="form-group"><label>New Payslip Password</label><input type="password" name="new_payslip_password" placeholder="Enter new payslip password" required></div>
                        <div class="form-group"><label>Confirm New Payslip Password</label><input type="password" name="confirm_payslip_password" placeholder="Confirm new payslip password" required></div>
                    </div>
                    <div class="form-actions"><button type="button" class="btn btn-danger" onclick="document.getElementById('resetPayslipModal').style.display='flex'">Reset to default</button><button type="submit" class="btn btn-primary">Update payslip password</button></div>
                </form>
            </section>
        </div>

        <div id="appearance-panel" class="settings-panel" data-settings-panel="appearance" role="tabpanel" aria-labelledby="appearance-tab" hidden>
            <?= view('client_portal/partials/appearance_panel', [
                'branding'      => $appearanceBranding ?? [],
                'logoUrl'       => $appearanceLogoUrl ?? null,
                'canManageLogo' => $canManageAppearanceLogo ?? false,
            ]) ?>
        </div>

        <div class="profile-audit">Last updated <?= esc($auditLabel) ?> by <?= esc($audit['updated_by'] ?? 'System') ?></div>
    </main>
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

        <form id="resetPayslipForm" method="post" action="<?= site_url('client_portal/profile/payslip-password/reset') ?>">
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

    /* SETTINGS LAYOUT */
    .account-container{
        height:100%;
        min-height:0;
        display:grid;
        grid-template-columns:230px minmax(0,1fr);
        overflow:hidden;
        color:#17223b;
        background:#fff;
        font-family:Segoe UI,Arial,sans-serif;
    }
    .profile-rail{
        min-width:0;
        padding:30px 20px;
        border-right:1px solid #e3e6eb;
        background:#fff;
    }
    .profile-rail-person{ display:flex; align-items:center; gap:13px; }
    .profile-rail-copy{ min-width:0; }
    .profile-rail-copy strong,.profile-rail-copy > span{ display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .profile-rail-copy strong{ color:#17223b; font-size:16px; line-height:1.35; }
    .profile-rail-copy > span:not(.profile-rail-status){ margin-top:3px; color:#5f6877; font-size:13px; line-height:1.4; }
    .about-avatar,.about-avatar-wrap{ width:58px; height:58px; flex:0 0 58px; border-radius:50%; }
    .about-avatar{ background:#eef0f3 center/cover no-repeat; box-shadow:0 0 0 1px #d7dbe2; }
    .about-avatar-initials{ display:flex; align-items:center; justify-content:center; color:#536078; font-size:18px; font-weight:700; }
    .about-avatar-wrap{ position:relative; display:block; cursor:pointer; }
    .about-avatar-edit-badge{
        position:absolute;
        right:-1px;
        bottom:-1px;
        display:flex;
        align-items:center;
        justify-content:center;
        width:20px;
        height:20px;
        border:2px solid #fff;
        border-radius:50%;
        color:#fff;
        background:#7b5cff;
        font-size:9px;
    }
    .avatar-status-dot{
        position:absolute;
        left:-1px;
        bottom:-1px;
        width:14px;
        height:14px;
        
        border:2px solid #fff;
        border-radius:50%;
        background:currentColor;
        /* Pure color dot: the presence poller must never show text in it. */
        font-size:0;
        line-height:0;
        overflow:hidden;
    }
    span.profile-rail-status{
        display:inline-flex;
        align-items:center;
        gap:5px;
        margin-top:6px;
        padding:3px 9px;
        border-radius:999px;
        font-size:11px;
        font-weight:700;
        letter-spacing:0.02em;
        text-transform:uppercase;
        width:fit-content;
    }
    .status-dot{
        width:7px;
        height:7px;
        flex:0 0 7px;
        border-radius:50%;
        background:currentColor;
    }
    .status-badge-lg{
        display:inline-flex;
        align-items:center;
        gap:7px;
        padding:6px 14px;
        border-radius:999px;
        font-size:13px;
        font-weight:700;
    }
    .status-badge-lg .status-dot{ width:8px; height:8px; flex-basis:8px; }
    .detail-row-status strong{ font-size:14px; }
    .photo-upload-form{ margin:12px 0 18px 71px; }
    .photo-link-btn{ color:#6650cc; font-size:13px; font-weight:600; cursor:pointer; }
    .photo-link-btn:hover{ text-decoration:underline; }
    .photo-hint{ display:block; margin-top:3px; color:#9aa1ad; font-size:10px; }
    .photo-upload-error{ display:block; margin-top:4px; color:#dc2626; font-size:11px; }
    .photo-upload-error.hidden,.hidden{ display:none; }
    .profile-rail-meta{ margin-top:18px; padding:15px 0 0; border-top:1px solid #eceef1; }
    .profile-rail-meta span,.profile-rail-meta strong,.profile-rail-meta small{ display:block; }
    .profile-rail-meta span{ color:#737c8b; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.05em; }
    .profile-rail-meta strong{ margin-top:4px; color:#344054; font-size:14px; }
    .profile-rail-meta small{ margin-top:5px; color:#737c8b; font-size:12px; }

    .settings-main{ min-width:0; overflow-y:auto; padding:30px 36px 28px; }
    .settings-header{ width:100%; }
    .settings-header h1{ margin:0; font-size:28px; line-height:1.2; font-weight:700; }
    .settings-header p{ margin:7px 0 0; color:#667085; font-size:14px; line-height:1.5; }
    .settings-nav-label{ margin-top:26px; color:#737c8b; font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
    .settings-tabs{
        display:flex;
        flex-direction:column;
        gap:4px;
        width:100%;
        margin-top:9px;
    }
    .settings-tab{
        position:relative;
        width:100%;
        padding:10px 12px;
        border:0;
        border-radius:6px;
        color:#344054;
        background:transparent;
        font-size:14px;
        font-weight:600;
        line-height:1.4;
        text-align:left;
        white-space:nowrap;
        cursor:pointer;
    }
    .settings-tab:hover{ background:#f5f4fa; }
    .settings-tab.active{ color:#5d46c5; background:#f1eeff; }
    .settings-panel{ width:100%; }
    .settings-panel[hidden]{ display:none; }
    .settings-section{ padding:27px 0; border-bottom:1px solid #e3e6eb; }
    .section-heading{ margin-bottom:18px; }
    .section-heading h2{ margin:0; color:#17223b; font-size:17px; font-weight:650; line-height:1.35; }
    .section-heading p{ margin:5px 0 0; color:#667085; font-size:13px; line-height:1.5; }
    .settings-form{ width:100%; }
    .form-grid{ display:grid; gap:0 16px; }
    .two-columns{ grid-template-columns:repeat(2,minmax(0,1fr)); }
    .three-columns{ grid-template-columns:repeat(3,minmax(0,1fr)); }
    .form-group-wide{ grid-column:1 / -1; }
    .form-group{ margin-bottom:14px; }
    .form-group label{ display:block; margin-bottom:7px; color:#344054; font-size:13px; font-weight:600; }
    .form-group input,.form-group textarea,.form-group select{
        width:100%;
        min-height:39px;
        box-sizing:border-box;
        padding:8px 10px;
        border:1px solid #cfd4dc;
        border-radius:5px;
        color:#17223b;
        background:#fff;
        font:inherit;
        font-size:14px;
        line-height:1.4;
        outline:none;
    }
    .form-group textarea{ min-height:70px; resize:vertical; }
    .form-group input:focus,.form-group textarea:focus,.form-group select:focus{ border-color:#7b5cff; box-shadow:0 0 0 2px rgba(123,92,255,.1); }
    .form-group input:disabled{ color:#667085; background:#f5f6f7; cursor:not-allowed; }
    .form-actions{ display:flex; align-items:center; justify-content:flex-end; gap:9px; min-height:37px; margin-top:2px; }
    .save-hint{ margin-right:auto; color:#737c8b; font-size:12px; line-height:1.4; }
    .btn{ min-height:38px; padding:8px 14px; border:1px solid transparent; border-radius:5px; font-size:13px; font-weight:650; cursor:pointer; }
    .btn-primary{ color:#fff; background:#7b5cff; }
    .btn-primary:hover:not(:disabled){ background:#6749e0; }
    .btn-danger{ border-color:#e2e5e9; color:#b42318; background:#fff; }
    .btn-danger:hover{ background:#fff5f4; }
    .btn-secondary{ border-color:#d5d9df; color:#344054; background:#fff; }
    .btn:disabled{ color:#9aa1ad; background:#e8eaed; cursor:not-allowed; }

    .details-list{ width:100%; }
    .detail-row{ display:grid; grid-template-columns:190px minmax(0,1fr); align-items:center; min-height:54px; border-bottom:1px solid #eceef1; }
    .detail-row:last-child{ border-bottom:0; }
    .detail-row span{ color:#5f6877; font-size:13px; }
    .detail-row strong:not(.status-badge-lg){ color:#344054; font-size:14px; font-weight:600; }
    .leave-list{ width:100%; }
    .leave-row{ position:relative; display:grid; grid-template-columns:minmax(0,1fr) auto; gap:4px 20px; padding:13px 0 17px; border-bottom:1px solid #eceef1; }
    .leave-row > div:first-child strong,.leave-row > div:first-child span{ display:block; }
    .leave-row > div:first-child strong{ font-size:14px; }
    .leave-row > div:first-child span{ margin-top:3px; color:#737c8b; font-size:12px; }
    .leave-value{ color:#4b5563; font-size:13px; font-weight:600; }
    .leave-track{ grid-column:1 / -1; height:4px; overflow:hidden; border-radius:99px; background:#e8eaee; }
    .leave-track span{ display:block; height:100%; border-radius:99px; }
    .empty-note{ color:#737c8b; font-size:14px; }
    .profile-audit{ width:100%; padding:18px 0 0; color:#737c8b; font-size:12px; line-height:1.5; }

    @media (max-width:1050px){
        .account-container{ grid-template-columns:210px minmax(0,1fr); }
        .profile-rail{ padding:26px 18px; }
        .settings-main{ padding:32px 32px 28px; }
        .three-columns{ grid-template-columns:1fr; }
    }
    @media (max-width:760px){
        .account-container{ display:block; overflow-y:auto; }
        .profile-rail{ padding:18px 16px; border-right:0; border-bottom:1px solid #e3e6eb; }
        .profile-rail-person{ max-width:100%; }
        .about-avatar,.about-avatar-wrap{ width:50px; height:50px; flex-basis:50px; }
        .photo-upload-form{ margin:8px 0 0 63px; }
        .photo-hint,.profile-rail-meta{ display:none; }
        .settings-nav-label{ margin-top:20px; }
        .settings-tabs{
            flex-direction:row;
            gap:22px;
            margin-top:10px;
            border-bottom:1px solid #dfe2e7;
            overflow-x:auto;
            overscroll-behavior-inline:contain;
            scrollbar-width:none;
        }
        .settings-tabs::-webkit-scrollbar{ display:none; }
        .settings-tab{ width:auto; padding:0 0 10px; border-radius:0; background:transparent; }
        .settings-tab:hover,.settings-tab.active{ background:transparent; }
        .settings-tab.active::after{ content:''; position:absolute; right:0; bottom:-1px; left:0; height:2px; background:#7b5cff; }
        .settings-main{ overflow:visible; padding:24px 16px 30px; }
        .settings-header h1{ font-size:23px; }
        .settings-panel{ max-width:none; }
        .settings-section{ padding:22px 0; }
        .two-columns,.three-columns{ grid-template-columns:1fr; }
        .form-group-wide{ grid-column:auto; }
        .form-actions{ align-items:stretch; flex-direction:column; }
        .save-hint{ margin-right:0; }
        .form-actions .btn{ width:100%; }
        .detail-row{ grid-template-columns:130px minmax(0,1fr); }
    }
</style>

<script>
    // Switch between the profile settings sections without leaving the page.
    (function () {
        const tabButtons = Array.from(document.querySelectorAll('[data-settings-tab]'));
        const panels = Array.from(document.querySelectorAll('[data-settings-panel]'));

        function showSettingsPanel(name) {
            panels.forEach((panel) => {
                const active = panel.dataset.settingsPanel === name;
                panel.hidden = !active;
                panel.classList.toggle('active', active);
            });
            tabButtons.forEach((button) => {
                const active = button.dataset.settingsTab === name;
                button.classList.toggle('active', active);
                button.setAttribute('aria-selected', active ? 'true' : 'false');
                button.tabIndex = active ? 0 : -1;
            });
        }

        tabButtons.forEach((button) => button.addEventListener('click', () => showSettingsPanel(button.dataset.settingsTab)));
        tabButtons.forEach((button, index) => button.addEventListener('keydown', (event) => {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
            event.preventDefault();
            const direction = event.key === 'ArrowRight' ? 1 : -1;
            const nextButton = tabButtons[(index + direction + tabButtons.length) % tabButtons.length];
            nextButton.focus();
            nextButton.click();
        }));

        const requestedTab = new URLSearchParams(window.location.search).get('tab');
        if (requestedTab && panels.some((panel) => panel.dataset.settingsPanel === requestedTab)) {
            showSettingsPanel(requestedTab);
        }
    })();

    // Profile photo: pick -> auto crop/resize/compress in the browser -> upload -> update in place.
    const PHOTO_MAX_BYTES = 2 * 1024 * 1024;
    const PHOTO_SOURCE_MAX_BYTES = 30 * 1024 * 1024;
    const PHOTO_OUTPUT_SIZE = 512;

    function photoStatus(message, type) {
        const el = document.getElementById('photoUploadError');
        if (el) {
            el.textContent = message || '';
            el.classList.toggle('hidden', !message);
            el.style.color = type === 'error' ? '#dc2626' : '#6b7280';
        }
        if (message && type && window.showToast) window.showToast(message, type);
    }

    function formatBytes(bytes) {
        return bytes >= 1024 * 1024
            ? (bytes / (1024 * 1024)).toFixed(1) + ' MB'
            : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    function loadImageFile(file) {
        return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => { URL.revokeObjectURL(url); resolve(img); };
            img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('This image could not be opened. Try a JPG, PNG, or WEBP file.')); };
            img.src = url;
        });
    }

    // Center-crop to a square, then lower JPEG quality until it fits the size limit.
    async function makeAvatarBlob(img) {
        const side = Math.min(img.naturalWidth, img.naturalHeight);
        const sx = (img.naturalWidth - side) / 2;
        const sy = (img.naturalHeight - side) / 2;
        const canvas = document.createElement('canvas');
        canvas.width = PHOTO_OUTPUT_SIZE;
        canvas.height = PHOTO_OUTPUT_SIZE;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, PHOTO_OUTPUT_SIZE, PHOTO_OUTPUT_SIZE);
        ctx.drawImage(img, sx, sy, side, side, 0, 0, PHOTO_OUTPUT_SIZE, PHOTO_OUTPUT_SIZE);

        for (const quality of [0.9, 0.8, 0.7, 0.55, 0.4, 0.3]) {
            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
            if (!blob) throw new Error('Your browser could not process this image.');
            if (blob.size <= PHOTO_MAX_BYTES) return blob;
        }
        throw new Error('Image is still too large after compression. Try a smaller photo.');
    }

    document.getElementById('profile_photo').addEventListener('change', async function (e) {
        const input = e.target;
        const file = input.files && input.files[0];
        if (!file) return;

        try {
            if (!/^image\//.test(file.type) && !/\.(jpe?g|png|webp)$/i.test(file.name)) {
                throw new Error('Only JPG, PNG, or WEBP files are allowed.');
            }
            if (file.size > PHOTO_SOURCE_MAX_BYTES) {
                throw new Error('Image is too large (30MB max).');
            }

            photoStatus('Optimizing photo (' + formatBytes(file.size) + ')…');
            const img = await loadImageFile(file);
            const blob = await makeAvatarBlob(img);

            photoStatus('Uploading ' + formatBytes(blob.size) + ' (compressed from ' + formatBytes(file.size) + ')…');
            const form = document.getElementById('photoUploadForm');
            const fd = new FormData(form);
            fd.set('profile_photo', blob, 'profile.jpg');

            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            });
            let data;
            try { data = await res.json(); } catch (_) { throw new Error('Upload failed (server error ' + res.status + '). Please try again.'); }
            if (!data.success) throw new Error(data.message || 'Upload failed.');

            const preview = document.getElementById('aboutAvatarPreview');
            if (preview) {
                preview.style.backgroundImage = "url('" + URL.createObjectURL(blob) + "')";
                preview.textContent = '';
                preview.classList.remove('about-avatar-initials');
            }
            photoStatus('Profile photo updated.', 'success');
            setTimeout(() => window.location.reload(), 900);
        } catch (err) {
            photoStatus(err.message || 'Something went wrong uploading the photo.', 'error');
        } finally {
            input.value = '';
        }
    });

    // Change detection for Identity Form
    (function () {
        const form = document.getElementById('identityForm');
        const saveBtn = document.getElementById('saveIdentityBtn');
        const saveHint = form?.querySelector('.save-hint');

        if (!form || !saveBtn) return;

        form.addEventListener('submit', function (e) {
            const fullNameInput = form.querySelector('input[name="full_name"]');
            const fullName = (fullNameInput?.value || '').trim();
            const nameFormat = /^[^,]+,\s*[^,]+$/;

            if (!nameFormat.test(fullName)) {
                e.preventDefault();
                showToast('Full Name must follow this format: Lastname, FirstName(s) MiddleName', 'warning');
                fullNameInput?.focus();
                return;
            }

            const [lastName, given] = fullName.split(',', 2).map((part) => part.trim());
            if (!lastName || !given) {
                e.preventDefault();
                showToast('Please provide both last name and first name(s).', 'warning');
                fullNameInput?.focus();
            }
        });

        const fields = Array.from(form.querySelectorAll('input[name]'));
        const initialValues = new Map(fields.map((field) => [field.name, (field.value || '').trim()]));

        function syncSaveState() {
            const hasChanged = fields.some((field) => {
                const initial = initialValues.get(field.name) ?? '';
                const current = (field.value || '').trim();

                return current !== initial;
            });

            saveBtn.disabled = !hasChanged;
            if (saveHint) saveHint.textContent = hasChanged ? 'You have unsaved changes.' : 'Change a field to enable saving.';
        }

        fields.forEach((field) => {
            field.addEventListener('input', syncSaveState);
            field.addEventListener('change', syncSaveState);
        });

        syncSaveState();
    })();

    // Change detection for Contact Form
    (function () {
        const saveBtn = document.getElementById('saveContactBtn');
        if (!saveBtn) return;

        const form = saveBtn.closest('form');
        if (!form) return;
        const saveHint = form.querySelector('.save-hint');

        const fields = Array.from(form.querySelectorAll('input[name], textarea[name]'));
        const initialValues = new Map(fields.map((field) => [field.name, (field.value || '').trim()]));

        function syncSaveState() {
            const hasChanged = fields.some((field) => {
                const initial = initialValues.get(field.name) ?? '';
                const current = (field.value || '').trim();

                return current !== initial;
            });

            saveBtn.disabled = !hasChanged;
            if (saveHint) saveHint.textContent = hasChanged ? 'You have unsaved changes.' : 'Change a field to enable saving.';
        }

        fields.forEach((field) => {
            field.addEventListener('input', syncSaveState);
            field.addEventListener('change', syncSaveState);
        });

        syncSaveState();
    })();

    // AJAX submit for the Payslip Password forms (Update + Reset), so success/failure
    // is confirmed via a toast immediately instead of depending on a full page reload.
    (function () {
        function bindAjaxForm(form, onSuccess) {
            if (!form) return;

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.textContent : null;
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Please wait…';
                }

                fetch(form.getAttribute('action'), {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then((res) => res.json().catch(() => ({
                        // A non-JSON response here means something upstream (e.g. an
                        // expired-session redirect to /login, followed silently by
                        // fetch) intercepted the request before it reached respond() —
                        // never treat that as success just because the final response
                        // itself was a 200.
                        ok: false,
                        message: res.redirected ? 'Your session expired. Please log in again.' : 'Something went wrong. Please try again.',
                    })))
                    .then((data) => {
                        window.showToast(data.message || (data.ok ? 'Done.' : 'Something went wrong.'), data.ok ? 'success' : 'error');
                        if (data.ok) {
                            form.reset();
                            if (onSuccess) onSuccess();
                        }
                    })
                    .catch(() => {
                        window.showToast('Network error. Please try again.', 'error');
                    })
                    .finally(() => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        }
                    });
            });
        }

        bindAjaxForm(document.getElementById('payslipPasswordForm'));
        bindAjaxForm(document.getElementById('resetPayslipForm'), function () {
            const modal = document.getElementById('resetPayslipModal');
            if (modal) modal.style.display = 'none';
        });
    })();

    // Change detection for Emergency Contact Form
    (function () {
        const saveBtn = document.getElementById('saveEmergencyBtn');
        if (!saveBtn) return;

        const form = saveBtn.closest('form');
        if (!form) return;
        const saveHint = form.querySelector('.save-hint');

        const fields = Array.from(form.querySelectorAll('input[name]'));
        const initialValues = new Map(fields.map((field) => [field.name, (field.value || '').trim()]));

        function syncSaveState() {
            const hasChanged = fields.some((field) => {
                const initial = initialValues.get(field.name) ?? '';
                const current = (field.value || '').trim();

                return current !== initial;
            });

            saveBtn.disabled = !hasChanged;
            if (saveHint) saveHint.textContent = hasChanged ? 'You have unsaved changes.' : 'Change a field to enable saving.';
        }

        fields.forEach((field) => {
            field.addEventListener('input', syncSaveState);
            field.addEventListener('change', syncSaveState);
        });

        syncSaveState();
    })();
</script>

<?= $this->endSection() ?>
