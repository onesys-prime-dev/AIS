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
                <span id="colorValue">#ffffff</span>
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

#colorValue {
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
const picker = document.getElementById('headerColorPicker');
const colorValue = document.getElementById('colorValue');

let savedColor = localStorage.getItem('headerColor') || '#ffffff';
let tempColor = savedColor;

// INITIAL LOAD → apply saved color
document.documentElement.style.setProperty('--header-bg', savedColor);
picker.value = savedColor;
colorValue.textContent = savedColor;

// 🎨 LIVE PREVIEW ONLY (no saving)
picker.addEventListener('input', function() {
    tempColor = this.value;

    document.documentElement.style.setProperty('--header-bg', tempColor);
    colorValue.textContent = tempColor;
});

// 💾 SAVE BUTTON
document.querySelector('.save-btn').addEventListener('click', () => {
    localStorage.setItem('headerColor', tempColor);
    savedColor = tempColor;

    alert('Changes saved!');
});

// ❌ DISCARD BUTTON
document.querySelector('.discard-btn').addEventListener('click', () => {
    tempColor = savedColor;

    document.documentElement.style.setProperty('--header-bg', savedColor);
    picker.value = savedColor;
    colorValue.textContent = savedColor;
});
</script>

<?= $this->endSection() ?>