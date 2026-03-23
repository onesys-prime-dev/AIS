<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="customization-page">

    <h1>Customization</h1>

    <div class="grid">

        <!-- LOGO -->
        <div class="card">
            <h3>Company Logo</h3>

            <div class="upload-box">
                <div class="upload-inner">
                    <div class="logo-circle" id="logoPreview">Logo</div>

                    <p class="drag-text">Drag and drop your file</p>
                    <small id="fileName">No file selected</small>

                    <label class="upload-btn">
                        Upload Files
                        <input type="file" id="logoInput" hidden accept="image/*">
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
                    <input type="color" id="sidebarColorPicker">
                    <input type="text" id="sidebarColorValue" readonly>
                </div>
            </div>

            <div class="color-box">
                <label>Topbar Color</label>
                <div class="color-field">
                    <input type="color" id="headerColorPicker">
                    <input type="text" id="headerColorValue" readonly>
                </div>
            </div>

            <div class="color-box">
                <label>Background Color</label>
                <div class="color-field">
                    <input type="color" id="bgColorPicker">
                    <input type="text" id="bgColorValue" readonly>
                </div>
            </div>
        </div>

    </div>

    <!-- ACTIONS -->
    <div class="actions">
        <button class="discard-btn">Discard Changes</button>
        <button class="save-btn">Apply & Save</button>
    </div>

</div>

<style>
body {
    background: var(--bg-color, #f5f6fa);
}

/* PAGE */
.customization-page {
    padding: 40px;
    font-family: 'Segoe UI', sans-serif;
}

h1 {
    font-size: 28px;
    margin-bottom: 25px;
}

/* GRID */
.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}

/* CARD */
.card {
    background: #ffffff;
    border-radius: 18px;
    padding: 25px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
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

/* ACTIONS */
.actions {
    margin-top: 30px;
    display: flex;
    justify-content: flex-end;
    gap: 15px;
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

const logoInput = $('logoInput');
const logoPreview = $('logoPreview');
const fileName = $('fileName');

// LOAD SAVED
let headerColor = localStorage.getItem('headerColor') || '#ffffff';
let sidebarColor = localStorage.getItem('sidebarColor') || '#1e1e1e';
let bgColor = localStorage.getItem('bgColor') || '#f5f6fa';

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

    return brightness > 155 ? 'invert(0)' : 'invert(1)';
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

// 🎨 APPLY COLORS
function applyColors() {

    // BASE
    document.documentElement.style.setProperty('--header-bg', headerColor);
    document.documentElement.style.setProperty('--sidebar-bg', sidebarColor);
    document.documentElement.style.setProperty('--bg-color', bgColor);

    // AUTO TEXT
    document.documentElement.style.setProperty('--header-text', getContrastColor(headerColor));
    document.documentElement.style.setProperty('--sidebar-text', getContrastColor(sidebarColor));

    // ICONS
    document.documentElement.style.setProperty('--sidebar-icon-filter', getIconFilter(sidebarColor));

    // SIDEBAR STATES
    document.documentElement.style.setProperty('--sidebar-hover', adjustColor(sidebarColor, 0.15));
    document.documentElement.style.setProperty('--sidebar-active', adjustColor(sidebarColor, 0.3));
}

// INITIAL LOAD
if (headerPicker) headerPicker.value = headerColor;
if (sidebarPicker) sidebarPicker.value = sidebarColor;
if (bgPicker) bgPicker.value = bgColor;

if (headerVal) headerVal.value = headerColor;
if (sidebarVal) sidebarVal.value = sidebarColor;
if (bgVal) bgVal.value = bgColor;

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

// SAVE
document.querySelector('.save-btn').addEventListener('click', () => {
    localStorage.setItem('headerColor', headerColor);
    localStorage.setItem('sidebarColor', sidebarColor);
    localStorage.setItem('bgColor', bgColor);

    alert('Changes saved!');
});

// DISCARD
document.querySelector('.discard-btn').addEventListener('click', () => {
    location.reload();
});

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