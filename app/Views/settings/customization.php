<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="customization-page">

    <div class="tabs" id="settingsTabs">
        <a href="<?= site_url('settings/customization') ?>" class="tab active">Customization</a>
        <a href="<?= site_url('settings/face-recognition') ?>" class="tab">Face Recognition</a>
    </div>

    <form class="grid" method="post" action="<?= site_url('settings/customization/save') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <!-- COLORS — always column 1 -->
        <div class="card">
            <h3>Company Colors</h3>

            <div class="color-box">
                <label>Sidebar Color</label>
                <div class="color-field">
                    <input type="color" id="sidebarColorPicker" name="sidebar_color">
                    <input type="text" id="sidebarColorValue" maxlength="7" placeholder="#000000">
                </div>
            </div>

            <div class="color-box">
                <label>Topbar Color</label>
                <div class="color-field">
                    <input type="color" id="headerColorPicker" name="header_color">
                    <input type="text" id="headerColorValue" maxlength="7" placeholder="#ffffff">
                </div>
            </div>

            <div class="color-box">
                <label>Background Color</label>
                <div class="color-field">
                    <input type="color" id="bgColorPicker" name="background_color">
                    <input type="text" id="bgColorValue" maxlength="7" placeholder="#f5f6fa">
                </div>
            </div>

            <div class="color-box">
                <label>Sidebar Icon Mode</label>
                <div class="mode-field">
                    <select id="sidebarIconMode" name="sidebar_icon_mode">
                        <option value="auto">Auto</option>
                        <option value="light">Light Icons</option>
                        <option value="dark">Dark Icons</option>
                        <option value="original">Original Icon Colors</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- LOGO — column 2, only for client_payroll_staff -->
        <?php if ($canManageLogo): ?>
        <div class="card">
            <h3>Company Logo</h3>

            <div class="upload-box">
                <div class="upload-inner">
                    <div class="logo-circle" id="logoPreview" style="<?= !empty($logoUrl) ? 'background-image:url(\'' . esc($logoUrl, 'attr') . '\');' : '' ?>">
                        <?= empty($logoUrl) ? 'Logo' : '' ?>
                    </div>

                    <p class="drag-text">Drag and drop your file</p>
                    <small id="fileName"><?= !empty($branding['logo_original_name']) ? esc((string) $branding['logo_original_name']) : 'No file selected' ?></small>

                    <label class="upload-btn">
                        Upload Files
                        <input type="file" id="logoInput" name="company_logo" hidden accept=".png,.jpg,.jpeg,.svg,image/png,image/jpeg,image/svg+xml">
                    </label>

                    <span class="format">Formats: PNG, JPG, or SVG Only</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="actions">
            <button type="button" class="discard-btn">Discard Changes</button>
            <button type="button" class="save-btn">Apply & Save</button>
        </div>
    </form>
</div>

<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-modal">
        <h4 id="confirmModalTitle">Are you sure?</h4>
        <p id="confirmModalMessage">Please confirm this action.</p>
        <div class="confirm-actions">
            <button type="button" class="confirm-cancel-btn" id="confirmCancelBtn">Cancel</button>
            <button type="button" class="confirm-ok-btn" id="confirmOkBtn">Confirm</button>
        </div>
    </div>
</div>

<div class="confirm-overlay" id="unsavedOverlay">
    <div class="confirm-modal">
        <h4>Unsaved Changes</h4>
        <p>You have edits that haven't been saved yet. Save them before you leave, or discard them?</p>
        <div class="confirm-actions">
            <button type="button" class="confirm-cancel-btn" id="unsavedStayBtn">Cancel</button>
            <button type="button" class="confirm-leave-btn" id="unsavedLeaveBtn">Discard Changes</button>
            <button type="button" class="confirm-ok-btn" id="unsavedSaveBtn">Apply & Save</button>
        </div>
    </div>
</div>

<style>
body {
    background: var(--bg-color, #f5f6fa);
}

/* PAGE */
.customization-page {
    font-family: 'Segoe UI', sans-serif;
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-height: 0;
    background-color: var(--bg-color, #e5e7eb);
}

h1 {
    font-size: 28px;
    margin-bottom: 25px;
}

/* GRID - always two columns */
.grid {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    padding: 2rem;
    align-content: start;
}

/* CARD - with scroll and reduced max-height */
.card {
    background: #ffffff;
    border-radius: 18px;
    padding: 25px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    overflow-y: auto;
    max-height: auto;   /* reduced from 500px to bring buttons up */
}

.card h3 {
    margin-bottom: 20px;
}

/* UPLOAD BOX */
.upload-box {
    border: 2px dashed #d1d5db;
    border-radius: 16px;
    padding: 40px 20px;
    text-align: center;
}

.logo-circle {
    width: 70px;
    height: 70px;
    background: #e5e7eb;
    border-radius: 50%;
    margin: 0 auto 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background-size: cover;
    background-position: center;
}

.drag-text {
    font-weight: 500;
}

.format {
    display: block;
    margin-top: 10px;
    font-size: 12px;
    color: #9ca3af;
}

.upload-btn {
    display: inline-block;
    margin-top: 15px;
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
    color: white;
    padding: 10px 18px;
    border-radius: 8px;
    cursor: pointer;
}

/* COLOR */
.color-box {
    background: #f9fafb;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 15px;
}

.color-field {
    display: flex;
    gap: 10px;
    margin-top: 8px;
}

.color-field input[type="color"] {
    width: 35px;
    height: 35px;
    border: none;
}

.color-field input[type="text"] {
    flex: 1;
    padding: 8px;
    border-radius: 8px;
    border: none;
    background: #e5e7eb;
}

.mode-field {
    margin-top: 8px;
}

.mode-field select {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: none;
    background: #e5e7eb;
}

/* ACTIONS */
.actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 15px;
    padding: 1rem 0;
    grid-column: 1 / -1;
    justify-self: end;
}

.discard-btn {
    background: none;
    border: none;
    color: #7c3aed;
    cursor: pointer;
}

.save-btn {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
    color: white;
    border: none;
    padding: 12px 22px;
    border-radius: 10px;
    cursor: pointer;
}

/* CONFIRMATION MODAL */
.confirm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 100000;
    padding: 16px;
}

.confirm-overlay.active {
    display: flex;
}

.confirm-modal {
    background: #ffffff;
    width: 100%;
    max-width: 380px;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
}

.confirm-modal h4 {
    margin: 0 0 10px;
    font-size: 18px;
    font-weight: 700;
    color: #111827;
}

.confirm-modal p {
    margin: 0 0 20px;
    font-size: 14px;
    color: #6b7280;
    line-height: 1.5;
}

.confirm-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.confirm-cancel-btn {
    background: #e5e7eb;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.confirm-ok-btn {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.confirm-leave-btn {
    background: #fee2e2;
    color: #b91c1c;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

/* The unsaved-changes prompt has 3 actions instead of 2, so it gets a bit
   more room and lets its buttons wrap on narrow screens instead of cramping. */
#unsavedOverlay .confirm-modal {
    max-width: 440px;
}

#unsavedOverlay .confirm-actions {
    flex-wrap: wrap;
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
</style>

<script>
// SAFE GET
function $(id) {
    return document.getElementById(id);
}

// ELEMENTS
const headerPicker = $('headerColorPicker');
const sidebarPicker = $('sidebarColorPicker');
const bgPicker = $('bgColorPicker');

const headerVal = $('headerColorValue');
const sidebarVal = $('sidebarColorValue');
const bgVal = $('bgColorValue');
const sidebarIconModeSelect = $('sidebarIconMode');

const logoInput = $('logoInput');
const logoPreview = $('logoPreview');
const fileName = $('fileName');
const customizationForm = document.querySelector('.grid');

const serverBranding = {
    headerColor: '<?= esc((string) ($branding['header_color'] ?? '#ffffff'), 'js') ?>',
    sidebarColor: '<?= esc((string) ($branding['sidebar_color'] ?? '#1e1e1e'), 'js') ?>',
    bgColor: '<?= esc((string) ($branding['background_color'] ?? '#f5f6fa'), 'js') ?>',
    sidebarIconMode: '<?= esc((string) ($branding['sidebar_icon_mode'] ?? 'auto'), 'js') ?>',
    logoUrl: '<?= esc((string) ($logoUrl ?? ''), 'js') ?>',
};

// LOAD CURRENT
let headerColor = serverBranding.headerColor || '#ffffff';
let sidebarColor = serverBranding.sidebarColor || '#1e1e1e';
let bgColor = serverBranding.bgColor || '#f5f6fa';
let sidebarIconMode = serverBranding.sidebarIconMode || 'auto';

// TEXT CONTRAST
function getContrastColor(hex) {
    hex = hex.replace('#', '');

    const r = parseInt(hex.substring(0,2), 16);
    const g = parseInt(hex.substring(2,4), 16);
    const b = parseInt(hex.substring(4,6), 16);

    const brightness = (r * 299 + g * 587 + b * 114) / 1000;

    return brightness > 155 ? '#000000' : '#ffffff';
}

// ICON FILTER
function getIconFilter(hex) {
    hex = hex.replace('#', '');

    const r = parseInt(hex.substring(0,2), 16);
    const g = parseInt(hex.substring(2,4), 16);
    const b = parseInt(hex.substring(4,6), 16);

    const brightness = (r * 299 + g * 587 + b * 114) / 1000;

    return brightness > 155 ? 'invert(1)' : 'invert(0)';
}

function getSidebarIconFilter(mode, sidebarHex) {
    if (mode === 'light') return 'invert(0)';
    if (mode === 'dark') return 'invert(1)';
    if (mode === 'original') return 'none';
    return getIconFilter(sidebarHex);
}

// ADJUST COLOR (hover/active)
function adjustColor(hex, percent) {
    hex = hex.replace('#', '');

    let r = parseInt(hex.substring(0,2), 16);
    let g = parseInt(hex.substring(2,4), 16);
    let b = parseInt(hex.substring(4,6), 16);

    r = Math.min(255, Math.max(0, r + (r * percent)));
    g = Math.min(255, Math.max(0, g + (g * percent)));
    b = Math.min(255, Math.max(0, b + (b * percent)));

    return "#" + 
        Math.round(r).toString(16).padStart(2,'0') +
        Math.round(g).toString(16).padStart(2,'0') +
        Math.round(b).toString(16).padStart(2,'0');
}

function tintColor(hex, amount) {
    hex = hex.replace('#', '');

    let r = parseInt(hex.substring(0, 2), 16);
    let g = parseInt(hex.substring(2, 4), 16);
    let b = parseInt(hex.substring(4, 6), 16);

    r = Math.round(r + (255 - r) * amount);
    g = Math.round(g + (255 - g) * amount);
    b = Math.round(b + (255 - b) * amount);

    return "#" +
        r.toString(16).padStart(2, '0') +
        g.toString(16).padStart(2, '0') +
        b.toString(16).padStart(2, '0');
}

function getHeaderDividerColor(hex) {
    const baseHex = hex.startsWith('#') ? hex : `#${hex}`;
    const cleanHex = baseHex.replace('#', '');

    const r = parseInt(cleanHex.substring(0,2), 16);
    const g = parseInt(cleanHex.substring(2,4), 16);
    const b = parseInt(cleanHex.substring(4,6), 16);

    const brightness = (r * 299 + g * 587 + b * 114) / 1000;

    return brightness > 155 ? adjustColor(baseHex, -0.22) : tintColor(baseHex, 0.22);
}

// 🎨 APPLY COLORS
function applyColors() {

    // BASE
    document.documentElement.style.setProperty('--header-bg', headerColor);
    document.documentElement.style.setProperty('--sidebar-bg', sidebarColor);
    document.documentElement.style.setProperty('--bg-color', bgColor);

    // AUTO TEXT
    document.documentElement.style.setProperty('--header-text', getContrastColor(headerColor));
    document.documentElement.style.setProperty('--header-divider', getHeaderDividerColor(headerColor));
    document.documentElement.style.setProperty('--sidebar-text', getContrastColor(sidebarColor));

    // ICONS
    document.documentElement.style.setProperty('--sidebar-icon-filter', getSidebarIconFilter(sidebarIconMode, sidebarColor));

    // SIDEBAR STATES
    document.documentElement.style.setProperty('--sidebar-divider', tintColor(sidebarColor, 0.18));
    document.documentElement.style.setProperty('--sidebar-hover', tintColor(sidebarColor, 0.14));
    document.documentElement.style.setProperty('--sidebar-active', tintColor(sidebarColor, 0.25));
}

// INITIAL LOAD
if (headerPicker) headerPicker.value = headerColor;
if (sidebarPicker) sidebarPicker.value = sidebarColor;
if (bgPicker) bgPicker.value = bgColor;

if (headerVal) headerVal.value = headerColor;
if (sidebarVal) sidebarVal.value = sidebarColor;
if (bgVal) bgVal.value = bgColor;
if (sidebarIconModeSelect) sidebarIconModeSelect.value = sidebarIconMode;

applyColors();

// LIVE UPDATE
headerPicker?.addEventListener('input', () => {
    headerColor = headerPicker.value;
    headerVal.value = headerColor;
    applyColors();
});

sidebarPicker?.addEventListener('input', () => {
    sidebarColor = sidebarPicker.value;
    sidebarVal.value = sidebarColor;
    applyColors();
});

bgPicker?.addEventListener('input', () => {
    bgColor = bgPicker.value;
    bgVal.value = bgColor;
    applyColors();
});

sidebarIconModeSelect?.addEventListener('change', () => {
    sidebarIconMode = sidebarIconModeSelect.value;
    applyColors();
});

// TYPE A HEX CODE DIRECTLY
const HEX_COLOR_PATTERN = /^#[0-9A-Fa-f]{6}$/;

function bindHexTextInput(textInput, picker, onValid) {
    if (!textInput || !picker) return;

    textInput.addEventListener('input', () => {
        let value = textInput.value.trim();
        if (value !== '' && value[0] !== '#') value = '#' + value;

        if (!HEX_COLOR_PATTERN.test(value)) return;

        picker.value = value;
        onValid(value);
        applyColors();
    });

    // Normalize/restore a valid-looking-but-incomplete value on blur so the
    // field doesn't get stuck showing something that never got applied.
    textInput.addEventListener('blur', () => {
        textInput.value = picker.value;
    });
}

bindHexTextInput(sidebarVal, sidebarPicker, (value) => { sidebarColor = value; });
bindHexTextInput(headerVal, headerPicker, (value) => { headerColor = value; });
bindHexTextInput(bgVal, bgPicker, (value) => { bgColor = value; });

// CONFIRMATION MODAL (shared by Apply & Save and Discard Changes)
const confirmOverlay = $('confirmOverlay');
const confirmModalTitle = $('confirmModalTitle');
const confirmModalMessage = $('confirmModalMessage');
const confirmOkBtn = $('confirmOkBtn');
const confirmCancelBtn = $('confirmCancelBtn');
let confirmAction = null;

function openConfirmModal(title, message, okLabel, onConfirm) {
    if (!confirmOverlay) { onConfirm(); return; }

    confirmModalTitle.textContent = title;
    confirmModalMessage.textContent = message;
    confirmOkBtn.textContent = okLabel;
    confirmAction = onConfirm;
    confirmOverlay.classList.add('active');
}

function closeConfirmModal() {
    confirmOverlay?.classList.remove('active');
    confirmAction = null;
}

confirmOkBtn?.addEventListener('click', () => {
    const action = confirmAction;
    closeConfirmModal();
    action?.();
});

confirmCancelBtn?.addEventListener('click', closeConfirmModal);

confirmOverlay?.addEventListener('click', (event) => {
    if (event.target === confirmOverlay) closeConfirmModal();
});

// UNSAVED CHANGES TRACKING — warn before leaving this page (via the settings
// tabs, or an actual browser navigation/close) with unapplied color/logo edits.
let formIsDirty = false;
let allowNavigation = false;

function markFormDirty() { formIsDirty = true; }

[headerPicker, sidebarPicker, bgPicker, headerVal, sidebarVal, bgVal, sidebarIconModeSelect, logoInput].forEach((el) => {
    el?.addEventListener('input', markFormDirty);
    el?.addEventListener('change', markFormDirty);
});

window.addEventListener('beforeunload', (event) => {
    if (!formIsDirty || allowNavigation) return;
    event.preventDefault();
    event.returnValue = '';
});

const unsavedOverlay = $('unsavedOverlay');
const unsavedStayBtn = $('unsavedStayBtn');
const unsavedLeaveBtn = $('unsavedLeaveBtn');
const unsavedSaveBtn = $('unsavedSaveBtn');
let pendingNavigationHref = null;

function closeUnsavedModal() {
    unsavedOverlay?.classList.remove('active');
    pendingNavigationHref = null;
}

// Catch any in-app navigation away from this page while dirty — the settings
// tabs, the sidebar, the header user menu — so it gets our own styled modal
// instead of the browser's unstyleable native "Leave site?" prompt.
document.addEventListener('click', (event) => {
    if (!formIsDirty) return;

    const link = event.target.closest('a[href]');
    if (!link) return;

    const href = link.getAttribute('href');
    if (!href || href === '#' || href.startsWith('javascript:')) return;
    if (link.target === '_blank') return;
    if (link.href.split('#')[0] === window.location.href.split('#')[0]) return;

    event.preventDefault();
    pendingNavigationHref = link.href;
    unsavedOverlay?.classList.add('active');
});

unsavedStayBtn?.addEventListener('click', closeUnsavedModal);

unsavedOverlay?.addEventListener('click', (event) => {
    if (event.target === unsavedOverlay) closeUnsavedModal();
});

unsavedLeaveBtn?.addEventListener('click', () => {
    allowNavigation = true;
    const href = pendingNavigationHref;
    closeUnsavedModal();
    if (href) window.location.href = href;
});

unsavedSaveBtn?.addEventListener('click', () => {
    allowNavigation = true;
    closeUnsavedModal();
    localStorage.setItem('headerColor', headerColor);
    localStorage.setItem('sidebarColor', sidebarColor);
    localStorage.setItem('bgColor', bgColor);
    localStorage.setItem('sidebarIconMode', sidebarIconMode);
    customizationForm?.submit();
});

// SAVE
document.querySelector('.save-btn')?.addEventListener('click', () => {
    openConfirmModal(
        'Apply these changes?',
        'Your color and logo settings will be updated for your account.',
        'Apply & Save',
        () => {
            allowNavigation = true;
            // Keep localStorage in sync for immediate UI paint while navigating.
            localStorage.setItem('headerColor', headerColor);
            localStorage.setItem('sidebarColor', sidebarColor);
            localStorage.setItem('bgColor', bgColor);
            localStorage.setItem('sidebarIconMode', sidebarIconMode);
            customizationForm?.submit();
        }
    );
});

// DISCARD
document.querySelector('.discard-btn').addEventListener('click', () => {
    openConfirmModal(
        'Discard changes?',
        'Any unsaved color or logo changes will be lost.',
        'Discard Changes',
        () => {
            allowNavigation = true;
            location.reload();
        }
    );
});

if (serverBranding.logoUrl && logoPreview) {
    logoPreview.style.backgroundImage = `url(${serverBranding.logoUrl})`;
    logoPreview.textContent = '';
}

// LOGO PREVIEW
logoInput?.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    fileName.textContent = file.name;

    const reader = new FileReader();
    reader.onload = function (e) {
        logoPreview.style.backgroundImage = `url(${e.target.result})`;
        logoPreview.textContent = '';
    };
    reader.readAsDataURL(file);
});
</script>

<?= $this->endSection() ?>
