<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="customization-page">

    <h1>Customization</h1>

    <div class="custom-card">

        <!-- LOGO -->
        <div class="form-group">
            <label>Company Logo</label>
            <div class="logo-upload">
                <button class="upload-btn">
                    <i class="fa-solid fa-upload"></i>
                    Upload PNG, JPG, or SVG
                </button>
                <div class="logo-preview"></div>
            </div>
        </div>

        <!-- DIVIDER -->
        <hr>

        <!-- HEADER COLOR -->
        <div class="form-group">
            <label>Topbar Color</label>
            <div class="color-row">
                <input type="color" id="headerColorPicker">
                <span id="headerColorValue">#ffffff</span>
            </div>
        </div>

        <!-- SIDEBAR COLOR -->
        <div class="form-group">
            <label>Topbar Color</label>
            <div class="color-row">
                <input type="color" id="sidebarColorPicker">
                <span id="sidebarColorValue">#ffffff</span>
            </div>
        </div>

    </div>

    <!-- ACTION BUTTONS -->
    <div class="actions">
        <button class="discard-btn">Discard Changes</button>
        <button class="save-btn">Apply & Save</button>
    </div>

</div>

<style>
.customization-page {
    padding: 40px;
    font-family: 'Segoe UI', sans-serif;
}

.customization-page h1 {
    font-size: 28px;
    margin-bottom: 30px;
}

/* CARD */
.custom-card {
    background: #f9fafb;
    border-radius: 12px;
    padding: 30px;
    max-width: 600px;
}

/* FORM GROUP */
.form-group {
    margin-bottom: 25px;
}

label {
    display: block;
    font-weight: 600;
    margin-bottom: 10px;
}

/* LOGO */
.logo-upload {
    display: flex;
    align-items: center;
    gap: 15px;
}

.upload-btn {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
    color: white;
    border: none;
    padding: 12px 18px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
}

.logo-preview {
    width: 50px;
    height: 50px;
    background: #e5e7eb;
    border-radius: 8px;
}

/* COLOR PICKER */
.color-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

input[type="color"] {
    width: 40px;
    height: 40px;
    border: none;
    background: none;
    cursor: pointer;
}

#headerColorValue,
#sidebarColorValue {  
    background: #e5e7eb;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 13px;
}

/* ACTIONS */
.actions {
    margin-top: 40px;
    display: flex;
    justify-content: flex-end;
    gap: 20px;
}

.discard-btn {
    background: none;
    border: none;
    color: #7c3aed;
    font-weight: 500;
    cursor: pointer;
}

.save-btn {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
    color: white;
    border: none;
    padding: 12px 22px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 6px 18px rgba(124,58,237,0.3);
}
</style>

<script>
// ELEMENTS
const headerPicker = document.getElementById('headerColorPicker');
const headerColorValue = document.getElementById('headerColorValue');

const sidebarPicker = document.getElementById('sidebarColorPicker');
const sidebarColorValue = document.getElementById('sidebarColorValue');

// LOAD SAVED VALUES
let savedHeaderColor = localStorage.getItem('headerColor') || '#ffffff';
let savedSidebarColor = localStorage.getItem('sidebarColor') || '#1e1e1e';

// TEMP VALUES
let tempHeaderColor = savedHeaderColor;
let tempSidebarColor = savedSidebarColor;

// FUNCTION: AUTO TEXT COLOR
function getContrastColor(hex) {
    hex = hex.replace('#', '');

    const r = parseInt(hex.substring(0,2), 16);
    const g = parseInt(hex.substring(2,4), 16);
    const b = parseInt(hex.substring(4,6), 16);

    const brightness = (r * 299 + g * 587 + b * 114) / 1000;

    return brightness > 155 ? '#000000' : '#ffffff';
}

// FUNCTION: INVERT ICON COLOR
function getIconFilter(hex) {
    hex = hex.replace('#', '');

    const r = parseInt(hex.substring(0,2), 16);
    const g = parseInt(hex.substring(2,4), 16);
    const b = parseInt(hex.substring(4,6), 16);

    const brightness = (r * 299 + g * 587 + b * 114) / 1000;
    
    return brightness > 155 ? 'invert(1)' : 'invert(0)';
}

// FUNCTION: ADJUST COLOR BASED OFF SIDEBAR COLOR
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

// INITIAL APPLY
document.documentElement.style.setProperty('--header-bg', savedHeaderColor);
document.documentElement.style.setProperty('--sidebar-bg', savedSidebarColor);

document.documentElement.style.setProperty('--header-text', getContrastColor(savedHeaderColor));
document.documentElement.style.setProperty('--sidebar-text', getContrastColor(savedSidebarColor));
document.documentElement.style.setProperty('--sidebar-icon-filter', getIconFilter(savedSidebarColor));


// SET PICKERS
headerPicker.value = savedHeaderColor;
headerColorValue.textContent = savedHeaderColor;

sidebarPicker.value = savedSidebarColor;
sidebarColorValue.textContent = savedSidebarColor;

// LIVE PREVIEW (HEADER)
headerPicker.addEventListener('input', function() {
    tempHeaderColor = this.value;

    document.documentElement.style.setProperty('--header-bg', tempHeaderColor);
    document.documentElement.style.setProperty('--header-text', getContrastColor(tempHeaderColor));

    headerColorValue.textContent = tempHeaderColor;
});

// LIVE PREVIEW (SIDEBAR)
sidebarPicker.addEventListener('input', function() {
    tempSidebarColor = this.value;

    document.documentElement.style.setProperty('--sidebar-bg', tempSidebarColor);
    document.documentElement.style.setProperty('--sidebar-text', getContrastColor(tempSidebarColor));
    document.documentElement.style.setProperty('--sidebar-icon-filter', getIconFilter(tempSidebarColor));

    // Hover = slightly lighter
    document.documentElement.style.setProperty('--sidebar-hover', adjustColor(tempSidebarColor, 0.15));

    // Active = stronger contrast
    document.documentElement.style.setProperty('--sidebar-active', adjustColor(tempSidebarColor, 0.3));

    // Panel background = slightly darker
    document.documentElement.style.setProperty('--panel-bg', adjustColor(tempSidebarColor, -0.2));

    // Panel text + icons
    document.documentElement.style.setProperty('--panel-text', getContrastColor(adjustColor(tempSidebarColor, -0.2)));
    document.documentElement.style.setProperty('--panel-icon-filter', getIconFilter(adjustColor(tempSidebarColor, -0.2)));
    sidebarColorValue.textContent = tempSidebarColor;
});

// SAVE
document.querySelector('.save-btn').addEventListener('click', () => {
    localStorage.setItem('headerColor', tempHeaderColor);
    localStorage.setItem('sidebarColor', tempSidebarColor);

    savedHeaderColor = tempHeaderColor;
    savedSidebarColor = tempSidebarColor;

    alert('Changes saved!');
});

// DISCARD
document.querySelector('.discard-btn').addEventListener('click', () => {
    tempHeaderColor = savedHeaderColor;
    tempSidebarColor = savedSidebarColor;

    document.documentElement.style.setProperty('--header-bg', savedHeaderColor);
    document.documentElement.style.setProperty('--sidebar-bg', savedSidebarColor);

    document.documentElement.style.setProperty('--header-text', getContrastColor(savedHeaderColor));
    document.documentElement.style.setProperty('--sidebar-text', getContrastColor(savedSidebarColor));
    document.documentElement.style.setProperty('--sidebar-icon-filter', getIconFilter(savedSidebarColor));

    headerPicker.value = savedHeaderColor;
    headerColorValue.textContent = savedHeaderColor;

    sidebarPicker.value = savedSidebarColor;
    sidebarColorValue.textContent = savedSidebarColor;
});
</script>

<?= $this->endSection() ?>