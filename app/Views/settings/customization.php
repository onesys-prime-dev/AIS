<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="customization-page">

    <div class="tabs">
        <div class="tab active">Customization</div>
    </div>
    <!-- <h1>Customization</h1> -->

    <form class="grid" method="post" action="<?= site_url('settings/customization/save') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <!-- LOGO -->
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

        <!-- COLORS -->
        <div class="card">
            <h3>Company Colors</h3>

            <div class="color-box">
                <label>Sidebar Color</label>
                <div class="color-field">
                    <input type="color" id="sidebarColorPicker" name="sidebar_color">
                    <input type="text" id="sidebarColorValue" readonly>
                </div>
            </div>

            <div class="color-box">
                <label>Topbar Color</label>
                <div class="color-field">
                    <input type="color" id="headerColorPicker" name="header_color">
                    <input type="text" id="headerColorValue" readonly>
                </div>
            </div>

            <div class="color-box">
                <label>Background Color</label>
                <div class="color-field">
                    <input type="color" id="bgColorPicker" name="background_color">
                    <input type="text" id="bgColorValue" readonly>
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

        <div class="actions">
            <button type="button" class="discard-btn">Discard Changes</button>
            <button type="submit" class="save-btn">Apply & Save</button>
        </div>
    </form>
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
    background-color: #e5e7eb;
}

h1 {
    font-size: 28px;
    margin-bottom: 25px;
}

/* GRID */
.grid {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:25px;
    padding: 2rem;
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
    height: auto;
    gap: 15px;
    padding: 1rem 0px;
    grid-column: 2 / 3;
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

// SAVE
customizationForm?.addEventListener('submit', () => {
    // Keep localStorage in sync for immediate UI paint while navigating.
    localStorage.setItem('headerColor', headerColor);
    localStorage.setItem('sidebarColor', sidebarColor);
    localStorage.setItem('bgColor', bgColor);
    localStorage.setItem('sidebarIconMode', sidebarIconMode);
});

// DISCARD
document.querySelector('.discard-btn').addEventListener('click', () => {
    location.reload();
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