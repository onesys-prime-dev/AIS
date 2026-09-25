<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
    $savedAppearance = [
        'headerColor'      => (string) ($branding['header_color'] ?? '#ffffff'),
        'sidebarColor'     => (string) ($branding['sidebar_color'] ?? '#1e1e1e'),
        'bgColor'          => (string) ($branding['background_color'] ?? '#f5f6fa'),
        'sidebarIconMode'  => (string) ($branding['sidebar_icon_mode'] ?? 'auto'),
        'fontFamily'       => (string) ($branding['font_family'] ?? 'default'),
        'fontSize'         => (string) ($branding['font_size'] ?? 'medium'),
    ];

    $oldHex = static function (string $field, string $fallback): string {
        $value = old($field);
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? $value : $fallback;
    };

    $oldChoice = static function (string $field, string $fallback, array $allowed): string {
        $value = old($field);
        return is_string($value) && in_array($value, $allowed, true) ? $value : $fallback;
    };

    $initialAppearance = [
        'headerColor'     => $oldHex('header_color', $savedAppearance['headerColor']),
        'sidebarColor'    => $oldHex('sidebar_color', $savedAppearance['sidebarColor']),
        'bgColor'         => $oldHex('background_color', $savedAppearance['bgColor']),
        'sidebarIconMode' => $oldChoice('sidebar_icon_mode', $savedAppearance['sidebarIconMode'], ['auto', 'light', 'dark', 'original']),
        'fontFamily'      => $oldChoice('font_family', $savedAppearance['fontFamily'], ['default', 'classic', 'serif', 'rounded', 'mono']),
        'fontSize'        => $oldChoice('font_size', $savedAppearance['fontSize'], ['small', 'medium', 'large', 'xlarge']),
    ];
?>

<div class="customization-page">

    <div class="tabs" id="settingsTabs">
        <a href="<?= site_url('settings/customization') ?>" class="tab active">Customization</a>
    </div>

    <form class="customization-grid <?= $canManageLogo ? 'has-logo' : 'no-logo' ?>" method="post" action="<?= site_url('settings/customization/save') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="redirect_to" id="customizationRedirectTo" value="">

        <div class="customization-intro card-full">
            <div>
                <span class="eyebrow">Workspace appearance</span>
                <h1>Make AIS feel like your own</h1>
                <p>Changes appear instantly while you edit. Save when everything looks right.</p>
            </div>
            <span class="change-status" id="changeStatus" role="status" aria-live="polite">
                <span class="status-dot" aria-hidden="true"></span>
                <span id="changeStatusText">All changes saved</span>
            </span>
        </div>

        <!-- LOGO — full width on top, only for client_payroll_staff -->
        <?php if ($canManageLogo): ?>
        <div class="card logo-card">
            <div class="setting-card-header">
                <div>
                    <h3>Company Logo</h3>
                    <p>Used in the sidebar, browser tab, and company documents.</p>
                </div>
                <span class="scope-chip">Company-wide</span>
            </div>

            <div class="upload-box" id="logoDropzone">
                <div class="upload-inner">
                    <div class="logo-circle" id="logoPreview" style="<?= !empty($logoUrl) ? 'background-image:url(\'' . esc($logoUrl, 'attr') . '\');' : '' ?>">
                        <?= empty($logoUrl) ? 'Logo' : '' ?>
                    </div>

                    <p class="drag-text">Drag and drop your file</p>
                    <small id="fileName" aria-live="polite"><?= !empty($branding['logo_original_name']) ? esc((string) $branding['logo_original_name']) : 'No file selected' ?></small>

                    <div class="logo-actions">
                        <label class="upload-btn" tabindex="0" role="button">
                            Upload Files
                            <input type="file" id="logoInput" name="company_logo" hidden accept=".png,.jpg,.jpeg,.svg,image/png,image/jpeg,image/svg+xml">
                        </label>
                        <?php if (! empty($logoUrl)): ?>
                            <button type="button" class="remove-logo-btn" id="removeLogoBtn">Remove Logo</button>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="remove_company_logo" id="removeLogoInput" value="0">

                    <span class="format">PNG, JPG, or SVG · Maximum 3 MB</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <section class="card live-preview-card" aria-labelledby="previewTitle">
            <div class="card-heading">
                <div>
                    <h3 id="previewTitle">Live Preview</h3>
                    <p>See how your colors and typography work together.</p>
                </div>
                <span class="preview-badge">Preview</span>
            </div>
            <div class="appearance-preview" aria-hidden="true">
                <div class="preview-sidebar">
                    <div class="preview-brand"></div>
                    <span class="preview-nav-item is-active"></span>
                    <span class="preview-nav-item"></span>
                    <span class="preview-nav-item short"></span>
                </div>
                <div class="preview-workspace">
                    <div class="preview-topbar">
                        <span class="preview-title-line"></span>
                        <span class="preview-avatar"></span>
                    </div>
                    <div class="preview-content">
                        <span class="preview-heading-line"></span>
                        <div class="preview-metrics">
                            <span></span><span></span><span></span>
                        </div>
                        <div class="preview-table-lines">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="preview-summary" aria-live="polite">
                <div class="preview-summary-item">
                    <span>Sidebar</span>
                    <strong id="previewSidebarValue">#1E1E1E</strong>
                </div>
                <div class="preview-summary-item">
                    <span>Topbar</span>
                    <strong id="previewHeaderValue">#FFFFFF</strong>
                </div>
                <div class="preview-summary-item">
                    <span>Background</span>
                    <strong id="previewBackgroundValue">#F5F6FA</strong>
                </div>
                <div class="preview-summary-item preview-summary-wide">
                    <span>Typography</span>
                    <strong id="previewTypographyValue">System · Medium</strong>
                </div>
            </div>
        </section>

        <!-- BELOW — Company Colors and Typography side by side -->
        <div class="card colors-card">
            <div class="setting-card-header">
                <div>
                    <h3>Interface Colors</h3>
                    <p>Saved to your account and applied across AIS.</p>
                </div>
                <span class="scope-chip">Personal</span>
            </div>

            <div class="theme-presets" aria-labelledby="themePresetsLabel">
                <div class="preset-heading">
                    <span id="themePresetsLabel">Quick themes</span>
                    <small>Start with a coordinated palette</small>
                </div>
                <div class="preset-list">
                    <button type="button" class="theme-preset" title="AIS Default theme" data-theme="default" data-sidebar="#1E1E1E" data-header="#FFFFFF" data-background="#F5F6FA">
                        <span class="preset-colors" aria-hidden="true"><i style="--swatch:#1E1E1E"></i><i style="--swatch:#FFFFFF"></i><i style="--swatch:#F5F6FA"></i></span>
                        <span class="preset-name">AIS Default</span>
                    </button>
                    <button type="button" class="theme-preset" title="Ocean theme" data-theme="ocean" data-sidebar="#12324A" data-header="#EAF4FF" data-background="#F5F9FC">
                        <span class="preset-colors" aria-hidden="true"><i style="--swatch:#12324A"></i><i style="--swatch:#EAF4FF"></i><i style="--swatch:#F5F9FC"></i></span>
                        <span class="preset-name">Ocean</span>
                    </button>
                    <button type="button" class="theme-preset" title="Forest theme" data-theme="forest" data-sidebar="#183B2B" data-header="#F4FBF7" data-background="#EFF6F1">
                        <span class="preset-colors" aria-hidden="true"><i style="--swatch:#183B2B"></i><i style="--swatch:#F4FBF7"></i><i style="--swatch:#EFF6F1"></i></span>
                        <span class="preset-name">Forest</span>
                    </button>
                    <button type="button" class="theme-preset" title="Midnight theme" data-theme="midnight" data-sidebar="#111827" data-header="#1F2937" data-background="#0F172A">
                        <span class="preset-colors" aria-hidden="true"><i style="--swatch:#111827"></i><i style="--swatch:#1F2937"></i><i style="--swatch:#0F172A"></i></span>
                        <span class="preset-name">Midnight</span>
                    </button>
                    <button type="button" class="theme-preset" title="Executive Navy theme" data-theme="executive" data-sidebar="#172554" data-header="#FFFFFF" data-background="#F1F5F9">
                        <span class="preset-colors" aria-hidden="true"><i style="--swatch:#172554"></i><i style="--swatch:#FFFFFF"></i><i style="--swatch:#F1F5F9"></i></span>
                        <span class="preset-name">Executive Navy</span>
                    </button>
                    <button type="button" class="theme-preset" title="Graphite theme" data-theme="graphite" data-sidebar="#27272A" data-header="#FAFAFA" data-background="#F4F4F5">
                        <span class="preset-colors" aria-hidden="true"><i style="--swatch:#27272A"></i><i style="--swatch:#FAFAFA"></i><i style="--swatch:#F4F4F5"></i></span>
                        <span class="preset-name">Graphite</span>
                    </button>
                    <button type="button" class="theme-preset is-extra" title="Professional Teal theme" data-theme="teal" data-sidebar="#134E4A" data-header="#F0FDFA" data-background="#F0F7F6">
                        <span class="preset-colors" aria-hidden="true"><i style="--swatch:#134E4A"></i><i style="--swatch:#F0FDFA"></i><i style="--swatch:#F0F7F6"></i></span>
                        <span class="preset-name">Professional Teal</span>
                    </button>
                    <button type="button" class="theme-preset is-extra" title="Corporate Indigo theme" data-theme="indigo" data-sidebar="#312E81" data-header="#EEF2FF" data-background="#F5F7FF">
                        <span class="preset-colors" aria-hidden="true"><i style="--swatch:#312E81"></i><i style="--swatch:#EEF2FF"></i><i style="--swatch:#F5F7FF"></i></span>
                        <span class="preset-name">Corporate Indigo</span>
                    </button>
                    <button type="button" class="theme-preset is-extra" title="Burgundy theme" data-theme="burgundy" data-sidebar="#4C0519" data-header="#FFF7F8" data-background="#F8F1F3">
                        <span class="preset-colors" aria-hidden="true"><i style="--swatch:#4C0519"></i><i style="--swatch:#FFF7F8"></i><i style="--swatch:#F8F1F3"></i></span>
                        <span class="preset-name">Burgundy</span>
                    </button>
                    <button type="button" class="theme-preset is-extra" title="Warm Neutral theme" data-theme="warm-neutral" data-sidebar="#44403C" data-header="#FFFBEB" data-background="#F5F5F4">
                        <span class="preset-colors" aria-hidden="true"><i style="--swatch:#44403C"></i><i style="--swatch:#FFFBEB"></i><i style="--swatch:#F5F5F4"></i></span>
                        <span class="preset-name">Warm Neutral</span>
                    </button>
                </div>
                <button type="button" class="preset-toggle" id="presetToggle" aria-expanded="false">Show 4 more themes</button>
            </div>

            <div class="color-settings-grid">
                <div class="color-box">
                    <label for="sidebarColorValue">Sidebar Color</label>
                    <div class="color-field">
                        <input type="color" id="sidebarColorPicker" name="sidebar_color" aria-label="Choose sidebar color">
                        <input type="text" id="sidebarColorValue" maxlength="7" placeholder="#000000" inputmode="text" spellcheck="false" aria-describedby="sidebarColorHelp">
                    </div>
                    <small class="color-help" id="sidebarColorHelp">Use a 6-digit hex color.</small>
                </div>

                <div class="color-box">
                    <label for="headerColorValue">Topbar Color</label>
                    <div class="color-field">
                        <input type="color" id="headerColorPicker" name="header_color" aria-label="Choose topbar color">
                        <input type="text" id="headerColorValue" maxlength="7" placeholder="#ffffff" inputmode="text" spellcheck="false" aria-describedby="headerColorHelp">
                    </div>
                    <small class="color-help" id="headerColorHelp">Use a 6-digit hex color.</small>
                </div>

                <div class="color-box">
                    <label for="bgColorValue">Background Color</label>
                    <div class="color-field">
                        <input type="color" id="bgColorPicker" name="background_color" aria-label="Choose background color">
                        <input type="text" id="bgColorValue" maxlength="7" placeholder="#f5f6fa" inputmode="text" spellcheck="false" aria-describedby="bgColorHelp">
                    </div>
                    <small class="color-help" id="bgColorHelp">Use a 6-digit hex color.</small>
                </div>

                <div class="color-box">
                    <label for="sidebarIconMode">Sidebar Icon Mode</label>
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
        </div>

        <div class="card typography-card">
            <div class="setting-card-header">
                <div>
                    <h3>Typography</h3>
                    <p>Choose the type style and scale that feels most readable.</p>
                </div>
                <span class="scope-chip">Personal</span>
            </div>

            <div class="typography-settings-grid">
                <div class="color-box">
                    <label for="fontFamilySelect">Font Family</label>
                    <div class="mode-field">
                        <select id="fontFamilySelect" name="font_family">
                            <option value="default">Default (System)</option>
                            <option value="classic">Classic Sans (Arial)</option>
                            <option value="serif">Serif (Georgia)</option>
                            <option value="rounded">Rounded (Verdana)</option>
                            <option value="mono">Monospace (Consolas)</option>
                        </select>
                    </div>
                </div>

                <div class="color-box">
                    <label for="fontSizeSelect">Font Size</label>
                    <div class="mode-field">
                        <select id="fontSizeSelect" name="font_size">
                            <option value="small">Small</option>
                            <option value="medium">Medium (Default)</option>
                            <option value="large">Large</option>
                            <option value="xlarge">Extra Large</option>
                        </select>
                    </div>
                </div>
            </div>

            <p class="font-preview" id="fontPreviewText">The quick brown fox jumps over the lazy dog.</p>
        </div>

        <div class="actions">
            <button type="button" class="defaults-btn">Restore AIS Defaults</button>
            <button type="button" class="discard-btn">Discard Changes</button>
            <button type="submit" class="save-btn">Apply & Save</button>
        </div>
    </form>
</div>

<div class="confirm-overlay" id="confirmOverlay" aria-hidden="true">
    <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle" tabindex="-1">
        <h4 id="confirmModalTitle">Are you sure?</h4>
        <p id="confirmModalMessage">Please confirm this action.</p>
        <div class="confirm-actions">
            <button type="button" class="confirm-cancel-btn" id="confirmCancelBtn">Cancel</button>
            <button type="button" class="confirm-ok-btn" id="confirmOkBtn">Confirm</button>
        </div>
    </div>
</div>

<div class="confirm-overlay" id="unsavedOverlay" aria-hidden="true">
    <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="unsavedModalTitle" tabindex="-1">
        <h4 id="unsavedModalTitle">Unsaved Changes</h4>
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
    font-family: var(--font-family, 'Segoe UI', Arial, sans-serif);
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-height: 0;
    background-color: var(--bg-color, #e5e7eb);
}

.customization-page h1 {
    font-size: clamp(1.35rem, 2.4vw, 1.7rem);
    line-height: 1.2;
    margin: 3px 0 6px;
    color: #111827;
}

.customization-grid {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-areas:
        "intro intro"
        "logo preview"
        "colors preview"
        "colors typography"
        "actions actions";
    column-gap: 20px;
    row-gap: 16px;
    padding: 24px;
    align-content: start;
}

.customization-grid.no-logo {
    grid-template-areas:
        "intro intro"
        "colors preview"
        "colors typography"
        "actions actions";
}

.customization-intro { grid-area: intro; }
.logo-card { grid-area: logo; }
.colors-card { grid-area: colors; }
.typography-card { grid-area: typography; }
.live-preview-card { grid-area: preview; }
.customization-page .actions { grid-area: actions; }

.customization-intro {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 2px 0;
}

.customization-intro p,
.customization-page .card-heading p {
    margin: 0;
    color: #64748b;
    line-height: 1.5;
}

.eyebrow {
    color: #7b5cff;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.change-status {
    display: inline-flex;
    align-items: center;
    flex: 0 0 auto;
    gap: 8px;
    padding: 9px 12px;
    border: 1px solid #dbe4ef;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.86);
    color: #475569;
    font-size: 0.8rem;
    font-weight: 700;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #22c55e;
}

.change-status.is-dirty {
    color: #6249e0;
    border-color: #d8d0ff;
    background: #f5f3ff;
}

.change-status.is-dirty .status-dot {
    background: #7b5cff;
}

/* CARD - sizes to its own content; the outer .grid scrolls the whole page */
.customization-page .card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    align-self: start;
}

.live-preview-card {
    align-self: start;
    display: flex;
    flex-direction: column;
}

.customization-page .card-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 18px;
}

.setting-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
}

.setting-card-header h3 { margin-bottom: 4px; }

.setting-card-header p {
    margin: 0;
    color: #6b7280;
    font-size: 0.82rem;
    line-height: 1.45;
}

.scope-chip {
    flex: 0 0 auto;
    padding: 5px 9px;
    border: 1px solid #e5e7eb;
    border-radius: 999px;
    background: #f9fafb;
    color: #6b7280;
    font-size: 0.7rem;
    font-weight: 700;
}

.customization-page .card-heading h3 {
    margin-bottom: 4px;
}

.preview-badge {
    padding: 6px 10px;
    border-radius: 999px;
    background: #ede9fe;
    color: #6249e0;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.appearance-preview {
    display: grid;
    grid-template-columns: minmax(82px, 18%) 1fr;
    flex: 1;
    min-height: 300px;
    overflow: hidden;
    border: 1px solid #dbe4ef;
    border-radius: 10px;
    background: var(--bg-color, #f5f6fa);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,0.45);
}

.preview-sidebar {
    display: flex;
    flex-direction: column;
    gap: 13px;
    padding: 18px 12px;
    background: var(--sidebar-bg, #1e1e1e);
}

.preview-brand {
    width: 34px;
    height: 34px;
    margin: 0 auto 10px;
    border-radius: 10px;
    background: var(--sidebar-text, #fff);
    opacity: 0.9;
}

.preview-nav-item {
    display: block;
    width: 82%;
    height: 8px;
    margin-inline: auto;
    border-radius: 999px;
    background: var(--sidebar-text, #fff);
    opacity: 0.42;
}

.preview-nav-item.is-active {
    width: 100%;
    height: 28px;
    background: var(--sidebar-active, #4b4b4b);
    opacity: 1;
}

.preview-nav-item.short { width: 62%; }

.preview-workspace {
    display: flex;
    min-width: 0;
    flex-direction: column;
}

.preview-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 48px;
    padding: 0 16px;
    background: var(--header-bg, #fff);
    border-bottom: 1px solid var(--header-divider, #e5e7eb);
}

.preview-title-line,
.preview-heading-line {
    display: block;
    width: 28%;
    height: 8px;
    border-radius: 999px;
    background: var(--header-text, #111827);
    opacity: 0.7;
}

.preview-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--header-text, #111827);
    opacity: 0.22;
}

.preview-content {
    flex: 1;
    padding: 20px;
}

.preview-heading-line {
    width: 36%;
    height: 10px;
    margin-bottom: 18px;
    background: #334155;
}

.preview-metrics {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 14px;
}

.preview-metrics span,
.preview-table-lines {
    border: 1px solid rgba(148, 163, 184, 0.24);
    border-radius: 10px;
    background: rgba(255,255,255,0.88);
}

.preview-metrics span { height: 54px; }

.preview-table-lines {
    display: grid;
    gap: 12px;
    padding: 15px;
}

.preview-table-lines span {
    height: 7px;
    border-radius: 999px;
    background: #e2e8f0;
}

.preview-table-lines span:nth-child(2) { width: 82%; }
.preview-table-lines span:nth-child(3) { width: 68%; }

.preview-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
    margin-top: 14px;
}

.preview-summary-item {
    min-width: 0;
    padding: 10px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f9fafb;
}

.preview-summary-item span,
.preview-summary-item strong {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.preview-summary-item span {
    margin-bottom: 3px;
    color: #9ca3af;
    font-size: 0.68rem;
}

.preview-summary-item strong {
    color: #374151;
    font-size: 0.76rem;
}

.preview-summary-wide { grid-column: 1 / -1; }

/* Company Logo sits full-width on top of the grid */
.customization-page .card-full {
    grid-column: 1 / -1;
}

.customization-page .card h3 {
    margin-bottom: 20px;
}

.customization-page .card .setting-card-header h3,
.customization-page .card .card-heading h3 {
    margin-bottom: 4px;
}

.font-preview {
    margin-top: 16px;
    padding: 14px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: #f9fafb;
    color: #374151;
}

/* UPLOAD BOX */
.upload-box {
    border: 2px dashed #d1d5db;
    border-radius: 10px;
    padding: 18px;
    text-align: left;
    transition: border-color 0.15s ease, background-color 0.15s ease;
}

.upload-box.is-dragover {
    border-color: #7b5cff;
    background: #f5f3ff;
}

.logo-circle {
    width: 70px;
    height: 70px;
    background: #e5e7eb;
    border-radius: 50%;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background-size: cover;
    background-position: center;
}

.drag-text {
    font-weight: 500;
    margin: 0;
}

.upload-inner {
    display: grid;
    grid-template-columns: 70px minmax(0, 1fr) auto;
    grid-template-rows: auto auto auto;
    align-items: center;
    column-gap: 16px;
    row-gap: 3px;
}

.upload-inner .logo-circle {
    grid-column: 1;
    grid-row: 1 / 4;
}

.upload-inner .drag-text {
    grid-column: 2;
    grid-row: 1;
}

.upload-inner #fileName {
    grid-column: 2;
    grid-row: 2;
    min-width: 0;
    overflow: hidden;
    color: #6b7280;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.upload-inner .format {
    grid-column: 2;
    grid-row: 3;
    margin-top: 0;
}

.upload-inner .logo-actions {
    grid-column: 3;
    grid-row: 1 / 4;
    margin-top: 0;
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
    background: #7b5cff;
    color: white;
    padding: 10px 18px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
}

.upload-btn:hover { background: #6249e0; }

.upload-btn:focus-visible,
.remove-logo-btn:focus-visible,
.defaults-btn:focus-visible,
.discard-btn:focus-visible,
.save-btn:focus-visible {
    outline: 3px solid rgba(123, 92, 255, 0.22);
    outline-offset: 2px;
}

.logo-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 12px;
}

.logo-actions .upload-btn { margin-top: 0; }

.remove-logo-btn {
    min-height: 40px;
    padding: 9px 14px;
    border: 1px solid #fecaca;
    border-radius: 8px;
    background: #fff1f2;
    color: #b91c1c;
    cursor: pointer;
    font: inherit;
    font-weight: 700;
}

.remove-logo-btn.is-pending {
    border-color: #cbd5e1;
    background: #f8fafc;
    color: #475569;
}

/* COLOR */
.theme-presets {
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e5e7eb;
}

.preset-heading {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}

.preset-heading > span {
    color: #374151;
    font-weight: 700;
}

.preset-heading small {
    color: #94a3b8;
}

.preset-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 9px;
}

.theme-preset {
    position: relative;
    display: flex;
    align-items: center;
    gap: 9px;
    min-width: 0;
    min-height: 44px;
    padding: 9px 10px;
    border: 1px solid #dbe4ef;
    border-radius: 10px;
    background: #fff;
    color: #334155;
    cursor: pointer;
    font: inherit;
    font-size: 0.82rem;
    font-weight: 700;
    text-align: left;
    transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
}

.theme-preset:hover {
    border-color: #a78bfa;
    transform: translateY(-1px);
}

.theme-preset:focus-visible {
    outline: 3px solid rgba(139, 92, 246, 0.2);
    outline-offset: 1px;
}

.theme-preset.is-active {
    border-color: #7b5cff;
    background: #faf9ff;
    box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.12);
}

.theme-preset::after {
    content: '\2713';
    flex: 0 0 auto;
    margin-left: auto;
    color: #7b5cff;
    font-size: 0.82rem;
    font-weight: 800;
    opacity: 0;
    transform: scale(0.75);
    transition: opacity 0.15s ease, transform 0.15s ease;
}

.theme-preset.is-active::after {
    opacity: 1;
    transform: scale(1);
}

.theme-preset.is-extra { display: none; }
.preset-list.show-all .theme-preset.is-extra { display: flex; }

.preset-name {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.preset-colors {
    display: flex;
    flex: 0 0 auto;
}

.preset-colors i {
    display: block;
    width: 16px;
    height: 16px;
    margin-left: -4px;
    border: 1px solid rgba(15, 23, 42, 0.14);
    border-radius: 50%;
    background: var(--swatch);
}

.preset-colors i:first-child { margin-left: 0; }

.preset-toggle {
    display: block;
    margin: 10px auto 0;
    padding: 5px 8px;
    border: 0;
    background: transparent;
    color: #6249e0;
    cursor: pointer;
    font: inherit;
    font-size: 0.76rem;
    font-weight: 700;
}

.preset-toggle:hover { text-decoration: underline; }

.preset-toggle:focus-visible {
    outline: 3px solid rgba(123, 92, 255, 0.2);
    outline-offset: 2px;
    border-radius: 6px;
}

.color-box {
    background: #f9fafb;
    border: 1px solid #edf0f4;
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 0;
}

.color-settings-grid,
.typography-settings-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.typography-settings-grid + .font-preview { margin-top: 12px; }

.color-field {
    display: flex;
    gap: 10px;
    margin-top: 8px;
}

.color-field input[type="color"] {
    width: 46px;
    height: 46px;
    flex: 0 0 46px;
    padding: 3px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    background: #fff;
    cursor: pointer;
}

.color-field input[type="text"] {
    flex: 1;
    min-width: 0;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    background: #ffffff;
    font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
    font-size: 1rem;
    text-transform: uppercase;
}

.color-field input[type="text"]:focus,
.mode-field select:focus {
    outline: 3px solid rgba(139, 92, 246, 0.18);
    border-color: #7b5cff;
}

.color-field input[type="text"].is-invalid {
    border-color: #ef4444;
    background: #fff1f2;
}

.color-help {
    display: block;
    margin-top: 6px;
    color: #94a3b8;
    font-size: 0.72rem;
}

.color-box.has-error .color-help {
    color: #dc2626;
    font-weight: 600;
}

.mode-field {
    margin-top: 8px;
}

.mode-field select {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    min-height: 44px;
    border: 1px solid #d1d5db;
    background: #ffffff;
}

/* ACTIONS */
.customization-page .actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 15px;
    padding: 1rem 0;
    justify-self: end;
    width: 100%;
}

.defaults-btn {
    margin-right: auto;
    min-height: 44px;
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: #ffffff;
    color: #475569;
    cursor: pointer;
    font: inherit;
    font-weight: 700;
}

.defaults-btn:hover { background: #f3f4f6; }

.defaults-btn:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.discard-btn {
    background: none;
    border: none;
    color: #6249e0;
    cursor: pointer;
    min-height: 44px;
    padding: 10px 14px;
    font-weight: 700;
}

.save-btn {
    background: #7b5cff;
    color: white;
    border: none;
    padding: 12px 22px;
    border-radius: 10px;
    cursor: pointer;
    min-height: 44px;
    font-weight: 700;
}

.save-btn:hover:not(:disabled) { background: #6249e0; }

.discard-btn:disabled,
.save-btn:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.save-btn:disabled {
    cursor: not-allowed;
    opacity: 0.55;
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

body.customization-modal-open { overflow: hidden; }

.confirm-modal {
    background: #ffffff;
    width: 100%;
    max-width: 380px;
    border-radius: 10px;
    padding: 24px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    max-height: calc(100dvh - 32px);
    overflow-y: auto;
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
    background: #7b5cff;
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
    .customization-page .tabs{
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

    .customization-page .tab{
        cursor:pointer;
        opacity:0.8;
    }

    .customization-page .tab.active{
        font-weight:600;
        opacity:1;
        position:relative;
    }

    .customization-page .tab.active::after{
        content:'';
        position:absolute;
        bottom:-24px;
        left:0;
        width:100%;
        height:3px;
        background:white;
    }

@media (max-width: 900px) {
    .customization-page {
        height: auto;
        min-height: 100%;
        overflow: visible;
    }

    .customization-page .tabs {
        min-height: 54px;
        padding: 10px 16px;
        gap: 18px;
        overflow-x: auto;
        scrollbar-width: none;
    }

    .customization-page .tabs::-webkit-scrollbar { display: none; }

    .customization-page .tab {
        flex: 0 0 auto;
        white-space: nowrap;
    }

    .customization-page .tab.active::after { bottom: -15px; }

    .customization-grid {
        flex: none;
        grid-template-columns: minmax(0, 1fr);
        grid-template-areas:
            "intro"
            "logo"
            "preview"
            "colors"
            "typography"
            "actions";
        overflow: visible;
        gap: 18px;
        padding: 20px;
    }

    .customization-grid.no-logo {
        grid-template-areas:
            "intro"
            "preview"
            "colors"
            "typography"
            "actions";
    }

    .customization-grid > * { grid-column: 1; }

    .customization-page .card { padding: 20px; }

    .appearance-preview { min-height: 280px; }

    .customization-page .actions {
        position: sticky;
        bottom: 0;
        z-index: 8;
        width: calc(100% + 40px);
        margin: 0 -20px -20px;
        padding: 14px 20px calc(14px + env(safe-area-inset-bottom));
        border-top: 1px solid #e2e8f0;
        background: rgba(255,255,255,0.96);
        backdrop-filter: blur(12px);
        justify-self: stretch;
    }
}

@media (max-width: 520px) {
    .customization-grid {
        gap: 14px;
        padding: 16px;
    }

    .customization-intro {
        align-items: flex-start;
        flex-direction: column;
        gap: 12px;
    }

    .customization-page h1 { font-size: 1.45rem; }

    .customization-page .card { padding: 16px; border-radius: 10px; }

    .upload-box { padding: 18px 14px; }

    .upload-inner {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        text-align: center;
    }

    .upload-inner .logo-circle { margin: 0 auto 8px; }
    .upload-inner .logo-actions { margin-top: 10px; }
    .upload-inner .format { margin-top: 5px; }

    .appearance-preview {
        grid-template-columns: 62px 1fr;
        min-height: 220px;
    }

    .preview-sidebar { padding: 14px 8px; }
    .preview-content { padding: 14px; }
    .preview-metrics { grid-template-columns: 1fr 1fr; }
    .preview-metrics span:last-child { display: none; }

    .color-box { padding: 13px; }

    .color-settings-grid,
    .typography-settings-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .setting-card-header {
        align-items: flex-start;
        flex-direction: column;
        gap: 8px;
    }

    .preset-heading {
        align-items: flex-start;
        flex-direction: column;
        gap: 2px;
    }

    .customization-page .actions {
        width: calc(100% + 32px);
        margin: 0 -16px -16px;
        padding-inline: 16px;
        flex-wrap: wrap;
    }

    .customization-page .actions > button { flex: 1 1 0; }

    .customization-page .actions .defaults-btn {
        flex-basis: 100%;
        margin-right: 0;
    }

    .confirm-actions { flex-direction: column-reverse; }
    .confirm-actions button { width: 100%; min-height: 44px; }

    #unsavedOverlay .confirm-actions { flex-direction: column-reverse; }
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
const fontFamilySelect = $('fontFamilySelect');
const fontSizeSelect = $('fontSizeSelect');
const fontPreviewText = $('fontPreviewText');
const previewSidebarValue = $('previewSidebarValue');
const previewHeaderValue = $('previewHeaderValue');
const previewBackgroundValue = $('previewBackgroundValue');
const previewTypographyValue = $('previewTypographyValue');
const changeStatus = $('changeStatus');
const changeStatusText = $('changeStatusText');
const redirectToInput = $('customizationRedirectTo');
const themePresetButtons = document.querySelectorAll('.theme-preset');
const presetList = document.querySelector('.preset-list');
const presetToggle = $('presetToggle');

const logoInput = $('logoInput');
const logoPreview = $('logoPreview');
const fileName = $('fileName');
const uploadButton = document.querySelector('.upload-btn');
const removeLogoBtn = $('removeLogoBtn');
const removeLogoInput = $('removeLogoInput');
const customizationForm = document.querySelector('.customization-grid');
const saveButton = document.querySelector('.save-btn');
const discardButton = document.querySelector('.discard-btn');
const defaultsButton = document.querySelector('.defaults-btn');
let logoRemovalPending = false;
let logoPreviewVersion = 0;
let isSubmitting = false;

const serverBranding = {
    headerColor: '<?= esc($savedAppearance['headerColor'], 'js') ?>',
    sidebarColor: '<?= esc($savedAppearance['sidebarColor'], 'js') ?>',
    bgColor: '<?= esc($savedAppearance['bgColor'], 'js') ?>',
    sidebarIconMode: '<?= esc($savedAppearance['sidebarIconMode'], 'js') ?>',
    fontFamily: '<?= esc($savedAppearance['fontFamily'], 'js') ?>',
    fontSize: '<?= esc($savedAppearance['fontSize'], 'js') ?>',
    logoUrl: '<?= esc((string) ($logoUrl ?? ''), 'js') ?>',
    logoName: '<?= esc((string) ($branding['logo_original_name'] ?? ''), 'js') ?>',
};

const initialAppearance = {
    headerColor: '<?= esc($initialAppearance['headerColor'], 'js') ?>',
    sidebarColor: '<?= esc($initialAppearance['sidebarColor'], 'js') ?>',
    bgColor: '<?= esc($initialAppearance['bgColor'], 'js') ?>',
    sidebarIconMode: '<?= esc($initialAppearance['sidebarIconMode'], 'js') ?>',
    fontFamily: '<?= esc($initialAppearance['fontFamily'], 'js') ?>',
    fontSize: '<?= esc($initialAppearance['fontSize'], 'js') ?>',
};

// LOAD CURRENT
let headerColor = initialAppearance.headerColor || '#ffffff';
let sidebarColor = initialAppearance.sidebarColor || '#1e1e1e';
let bgColor = initialAppearance.bgColor || '#f5f6fa';
let sidebarIconMode = initialAppearance.sidebarIconMode || 'auto';
let fontFamily = initialAppearance.fontFamily || 'default';
let fontSize = initialAppearance.fontSize || 'medium';

function normalizeHex(value) {
    return String(value || '').toUpperCase();
}

function updatePresetSelection() {
    let hasActiveExtraPreset = false;

    themePresetButtons.forEach((button) => {
        const isMatch = normalizeHex(button.dataset.sidebar) === normalizeHex(sidebarColor)
            && normalizeHex(button.dataset.header) === normalizeHex(headerColor)
            && normalizeHex(button.dataset.background) === normalizeHex(bgColor);
        button.classList.toggle('is-active', isMatch);
        button.setAttribute('aria-pressed', String(isMatch));
        if (isMatch && button.classList.contains('is-extra')) hasActiveExtraPreset = true;
    });

    if (hasActiveExtraPreset) presetList?.classList.add('show-all');
    updatePresetToggle();
}

function updatePresetToggle() {
    if (!presetList || !presetToggle) return;
    const isExpanded = presetList.classList.contains('show-all');
    presetToggle.setAttribute('aria-expanded', String(isExpanded));
    presetToggle.textContent = isExpanded ? 'Show fewer themes' : 'Show 4 more themes';
}

// FONT FAMILY / SIZE LOOKUPS — shared with layouts/main.php, keep in sync
const FONT_FAMILY_STACKS = {
    default: "ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif",
    classic: "Arial, Helvetica, sans-serif",
    serif: "Georgia, 'Times New Roman', serif",
    rounded: "Verdana, Geneva, sans-serif",
    mono: "Consolas, 'Courier New', monospace",
};

const FONT_SIZE_SCALES = {
    small: '14px',
    medium: '16px',
    large: '18px',
    xlarge: '20px',
};

// Multiplier for the header/sidebar's hardcoded px font sizes (they use
// calc(Npx * var(--font-scale, 1)) since they don't use rem units).
const FONT_SCALE_FACTORS = {
    small: 0.875,
    medium: 1,
    large: 1.125,
    xlarge: 1.25,
};

const FONT_FAMILY_LABELS = {
    default: 'System',
    classic: 'Arial',
    serif: 'Georgia',
    rounded: 'Verdana',
    mono: 'Consolas',
};

const FONT_SIZE_LABELS = {
    small: 'Small',
    medium: 'Medium',
    large: 'Large',
    xlarge: 'Extra Large',
};

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

    // TYPOGRAPHY
    document.documentElement.style.setProperty('--font-family', FONT_FAMILY_STACKS[fontFamily] || FONT_FAMILY_STACKS.default);
    document.documentElement.style.setProperty('--font-scale', FONT_SCALE_FACTORS[fontSize] ?? 1);
    document.documentElement.style.fontSize = FONT_SIZE_SCALES[fontSize] || FONT_SIZE_SCALES.medium;

    if (fontPreviewText) {
        fontPreviewText.style.fontFamily = FONT_FAMILY_STACKS[fontFamily] || FONT_FAMILY_STACKS.default;
        fontPreviewText.style.fontSize = FONT_SIZE_SCALES[fontSize] || FONT_SIZE_SCALES.medium;
    }

    if (previewSidebarValue) previewSidebarValue.textContent = normalizeHex(sidebarColor);
    if (previewHeaderValue) previewHeaderValue.textContent = normalizeHex(headerColor);
    if (previewBackgroundValue) previewBackgroundValue.textContent = normalizeHex(bgColor);
    if (previewTypographyValue) {
        previewTypographyValue.textContent = `${FONT_FAMILY_LABELS[fontFamily] || 'System'} · ${FONT_SIZE_LABELS[fontSize] || 'Medium'}`;
    }

    updatePresetSelection();
}

// INITIAL LOAD
if (headerPicker) headerPicker.value = headerColor;
if (sidebarPicker) sidebarPicker.value = sidebarColor;
if (bgPicker) bgPicker.value = bgColor;

if (headerVal) headerVal.value = headerColor;
if (sidebarVal) sidebarVal.value = sidebarColor;
if (bgVal) bgVal.value = bgColor;
if (sidebarIconModeSelect) sidebarIconModeSelect.value = sidebarIconMode;
if (fontFamilySelect) fontFamilySelect.value = fontFamily;
if (fontSizeSelect) fontSizeSelect.value = fontSize;

applyColors();

const HEX_COLOR_PATTERN = /^#[0-9A-Fa-f]{6}$/;

function setHexValidity(textInput, isValid) {
    if (!textInput) return;
    textInput.classList.toggle('is-invalid', !isValid);
    textInput.closest('.color-box')?.classList.toggle('has-error', !isValid);
    textInput.setAttribute('aria-invalid', String(!isValid));
}

// LIVE UPDATE
headerPicker?.addEventListener('input', () => {
    headerColor = headerPicker.value;
    headerVal.value = headerColor;
    setHexValidity(headerVal, true);
    applyColors();
});

sidebarPicker?.addEventListener('input', () => {
    sidebarColor = sidebarPicker.value;
    sidebarVal.value = sidebarColor;
    setHexValidity(sidebarVal, true);
    applyColors();
});

bgPicker?.addEventListener('input', () => {
    bgColor = bgPicker.value;
    bgVal.value = bgColor;
    setHexValidity(bgVal, true);
    applyColors();
});

sidebarIconModeSelect?.addEventListener('change', () => {
    sidebarIconMode = sidebarIconModeSelect.value;
    applyColors();
});

fontFamilySelect?.addEventListener('change', () => {
    fontFamily = fontFamilySelect.value;
    applyColors();
});

fontSizeSelect?.addEventListener('change', () => {
    fontSize = fontSizeSelect.value;
    applyColors();
});

themePresetButtons.forEach((button) => {
    button.addEventListener('click', () => {
        sidebarColor = normalizeHex(button.dataset.sidebar);
        headerColor = normalizeHex(button.dataset.header);
        bgColor = normalizeHex(button.dataset.background);

        if (sidebarPicker) sidebarPicker.value = sidebarColor;
        if (headerPicker) headerPicker.value = headerColor;
        if (bgPicker) bgPicker.value = bgColor;
        if (sidebarVal) sidebarVal.value = sidebarColor;
        if (headerVal) headerVal.value = headerColor;
        if (bgVal) bgVal.value = bgColor;
        setHexValidity(sidebarVal, true);
        setHexValidity(headerVal, true);
        setHexValidity(bgVal, true);

        applyColors();
        markFormDirty();
    });
});

presetToggle?.addEventListener('click', () => {
    presetList?.classList.toggle('show-all');
    updatePresetToggle();
});

defaultsButton?.addEventListener('click', () => {
    sidebarColor = '#1E1E1E';
    headerColor = '#FFFFFF';
    bgColor = '#F5F6FA';
    sidebarIconMode = 'auto';
    fontFamily = 'default';
    fontSize = 'medium';

    if (sidebarPicker) sidebarPicker.value = sidebarColor;
    if (headerPicker) headerPicker.value = headerColor;
    if (bgPicker) bgPicker.value = bgColor;
    if (sidebarVal) sidebarVal.value = sidebarColor;
    if (headerVal) headerVal.value = headerColor;
    if (bgVal) bgVal.value = bgColor;
    setHexValidity(sidebarVal, true);
    setHexValidity(headerVal, true);
    setHexValidity(bgVal, true);
    if (sidebarIconModeSelect) sidebarIconModeSelect.value = sidebarIconMode;
    if (fontFamilySelect) fontFamilySelect.value = fontFamily;
    if (fontSizeSelect) fontSizeSelect.value = fontSize;

    applyColors();
    markFormDirty();
    window.showToast?.('AIS defaults loaded in the preview. Save to apply them.', 'info');
});

// TYPE A HEX CODE DIRECTLY
function bindHexTextInput(textInput, picker, onValid) {
    if (!textInput || !picker) return;

    textInput.addEventListener('input', () => {
        let value = textInput.value.trim();
        if (value !== '' && value[0] !== '#') value = '#' + value;

        const isValid = HEX_COLOR_PATTERN.test(value);
        setHexValidity(textInput, isValid);

        if (!isValid) return;

        const normalizedValue = value.toUpperCase();
        picker.value = normalizedValue;
        onValid(normalizedValue);
        applyColors();
    });

    // Keep invalid text visible so the user can correct it. Restoring the old
    // value here made Save/Discard disable during the click that caused blur.
    textInput.addEventListener('blur', () => {
        let value = textInput.value.trim();
        if (value !== '' && value[0] !== '#') value = '#' + value;
        const isValid = HEX_COLOR_PATTERN.test(value);
        setHexValidity(textInput, isValid);
        if (isValid) textInput.value = value.toUpperCase();
        markFormDirty();
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

function syncModalState() {
    const hasOpenModal = confirmOverlay?.classList.contains('active') || unsavedOverlay?.classList.contains('active');
    document.body.classList.toggle('customization-modal-open', Boolean(hasOpenModal));
}

function openConfirmModal(title, message, okLabel, onConfirm) {
    if (!confirmOverlay) { onConfirm(); return; }

    confirmModalTitle.textContent = title;
    confirmModalMessage.textContent = message;
    confirmOkBtn.textContent = okLabel;
    confirmAction = onConfirm;
    confirmOverlay.classList.add('active');
    confirmOverlay.setAttribute('aria-hidden', 'false');
    syncModalState();
    confirmCancelBtn?.focus();
}

function closeConfirmModal() {
    confirmOverlay?.classList.remove('active');
    confirmOverlay?.setAttribute('aria-hidden', 'true');
    confirmAction = null;
    syncModalState();
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

function markFormDirty() {
    const hasInvalidColor = Boolean(document.querySelector('.color-field input.is-invalid'));
    const hasLogoFile = Boolean(logoInput?.files?.length);
    formIsDirty = hasInvalidColor
        || hasLogoFile
        || logoRemovalPending
        || normalizeHex(headerColor) !== normalizeHex(serverBranding.headerColor)
        || normalizeHex(sidebarColor) !== normalizeHex(serverBranding.sidebarColor)
        || normalizeHex(bgColor) !== normalizeHex(serverBranding.bgColor)
        || sidebarIconMode !== serverBranding.sidebarIconMode
        || fontFamily !== serverBranding.fontFamily
        || fontSize !== serverBranding.fontSize;

    if (changeStatus) {
        changeStatus.classList.toggle('is-dirty', formIsDirty);
        if (changeStatusText) {
            changeStatusText.textContent = formIsDirty ? 'Unsaved changes' : 'All changes saved';
        }
    }

    if (saveButton) saveButton.disabled = !formIsDirty;
    if (discardButton) discardButton.disabled = !formIsDirty;
}

[headerPicker, sidebarPicker, bgPicker, headerVal, sidebarVal, bgVal, sidebarIconModeSelect, fontFamilySelect, fontSizeSelect].forEach((el) => {
    el?.addEventListener('input', markFormDirty);
    el?.addEventListener('change', markFormDirty);
});

markFormDirty();

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
    unsavedOverlay?.setAttribute('aria-hidden', 'true');
    pendingNavigationHref = null;
    syncModalState();
}

function focusInvalidColor() {
    const invalidInput = document.querySelector('.color-field input.is-invalid');
    if (!invalidInput) return false;

    window.showToast?.('Enter a valid 6-digit hex color before saving.', 'error');
    invalidInput.focus();
    return true;
}

function validateAppearanceForm() {
    if (focusInvalidColor()) return false;
    if (!customizationForm || customizationForm.checkValidity()) return true;

    customizationForm.reportValidity();
    return false;
}

function persistCustomization() {
    if (isSubmitting || !customizationForm) return;
    isSubmitting = true;
    allowNavigation = true;
    if (saveButton) {
        saveButton.disabled = true;
        saveButton.textContent = 'Saving...';
    }
    if (discardButton) discardButton.disabled = true;
    if (defaultsButton) defaultsButton.disabled = true;
    localStorage.setItem('headerColor', headerColor);
    localStorage.setItem('sidebarColor', sidebarColor);
    localStorage.setItem('bgColor', bgColor);
    localStorage.setItem('sidebarIconMode', sidebarIconMode);
    localStorage.setItem('fontFamily', fontFamily);
    localStorage.setItem('fontSize', fontSize);
    customizationForm.submit();
}

function getSaveConfirmationMessage() {
    const logoWillChange = logoRemovalPending || Boolean(logoInput?.files?.length);
    return logoWillChange
        ? 'Your personal appearance preferences will be saved. The company logo change will apply to everyone.'
        : 'Your appearance preferences will be saved to your account.';
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
    unsavedOverlay?.setAttribute('aria-hidden', 'false');
    syncModalState();
    unsavedStayBtn?.focus();
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
    if (!validateAppearanceForm()) {
        closeUnsavedModal();
        return;
    }

    const destination = pendingNavigationHref;
    if (redirectToInput && destination) {
        const destinationUrl = new URL(destination, window.location.href);
        redirectToInput.value = destinationUrl.origin === window.location.origin
            ? destinationUrl.pathname + destinationUrl.search + destinationUrl.hash
            : '';
    }
    closeUnsavedModal();
    persistCustomization();
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    if (confirmOverlay?.classList.contains('active')) closeConfirmModal();
    else if (unsavedOverlay?.classList.contains('active')) closeUnsavedModal();
});

// SAVE - handle the form submission so clicking the button and pressing Enter
// follow the same confirmation and persistence path.
customizationForm?.addEventListener('submit', (event) => {
    if (allowNavigation) return;
    event.preventDefault();
    if (!formIsDirty || isSubmitting) return;
    if (!validateAppearanceForm()) return;
    if (redirectToInput) redirectToInput.value = '';

    openConfirmModal(
        'Apply these changes?',
        getSaveConfirmationMessage(),
        'Apply & Save',
        () => {
            // Keep localStorage in sync for immediate UI paint while navigating.
            persistCustomization();
        }
    );
});

// DISCARD
document.querySelector('.discard-btn')?.addEventListener('click', () => {
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

uploadButton?.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' && event.key !== ' ') return;
    event.preventDefault();
    logoInput?.click();
});

// LOGO PREVIEW
const ACCEPTED_LOGO_TYPES = ['image/png', 'image/jpeg', 'image/svg+xml'];
const MAX_LOGO_SIZE = 3 * 1024 * 1024;

function isValidLogo(file) {
    if (!ACCEPTED_LOGO_TYPES.includes(file.type)) {
        window.showToast?.('Logo must be PNG, JPG, or SVG.', 'error');
        return false;
    }

    if (file.size > MAX_LOGO_SIZE) {
        window.showToast?.('Logo must be 3 MB or below.', 'error');
        return false;
    }

    return true;
}

function restoreSavedLogoPreview() {
    if (logoPreview) {
        logoPreview.style.backgroundImage = serverBranding.logoUrl
            ? `url(${serverBranding.logoUrl})`
            : 'none';
        logoPreview.textContent = serverBranding.logoUrl ? '' : 'Logo';
    }
    if (fileName) fileName.textContent = serverBranding.logoName || 'No file selected';
}

function setLogoRemovalPending(shouldRemove) {
    if (!removeLogoInput || !removeLogoBtn) return;

    logoRemovalPending = shouldRemove;
    logoPreviewVersion += 1;
    removeLogoInput.value = shouldRemove ? '1' : '0';
    removeLogoBtn.classList.toggle('is-pending', shouldRemove);
    removeLogoBtn.textContent = shouldRemove ? 'Undo Removal' : 'Remove Logo';

    if (shouldRemove) {
        if (logoInput) logoInput.value = '';
        if (logoPreview) {
            logoPreview.style.backgroundImage = 'none';
            logoPreview.textContent = 'Logo';
        }
        if (fileName) fileName.textContent = 'Logo will be removed after saving';
    } else {
        restoreSavedLogoPreview();
    }

    markFormDirty();
}

removeLogoBtn?.addEventListener('click', () => {
    if (logoInput?.files?.length) {
        logoInput.value = '';
        logoPreviewVersion += 1;
        restoreSavedLogoPreview();
        removeLogoBtn.textContent = 'Remove Logo';
        markFormDirty();
        return;
    }

    setLogoRemovalPending(!logoRemovalPending);
});

logoInput?.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    if (!isValidLogo(file)) {
        this.value = '';
        logoPreviewVersion += 1;
        if (!logoRemovalPending) {
            restoreSavedLogoPreview();
            if (removeLogoBtn) removeLogoBtn.textContent = 'Remove Logo';
        }
        markFormDirty();
        return;
    }

    if (removeLogoInput) removeLogoInput.value = '0';
    logoRemovalPending = false;
    removeLogoBtn?.classList.remove('is-pending');
    if (removeLogoBtn) removeLogoBtn.textContent = 'Cancel New Logo';

    fileName.textContent = file.name;
    markFormDirty();

    const previewVersion = ++logoPreviewVersion;
    const reader = new FileReader();
    reader.onload = function (e) {
        if (previewVersion !== logoPreviewVersion) return;
        logoPreview.style.backgroundImage = `url(${e.target.result})`;
        logoPreview.textContent = '';
    };
    reader.readAsDataURL(file);
});

// DRAG AND DROP — the dropzone only ever showed the text; it never had
// listeners wired up, so dropping a file silently did nothing.
const logoDropzone = $('logoDropzone');

['dragenter', 'dragover'].forEach((eventName) => {
    logoDropzone?.addEventListener(eventName, (event) => {
        event.preventDefault();
        event.stopPropagation();
        logoDropzone.classList.add('is-dragover');
    });
});

['dragleave', 'dragend'].forEach((eventName) => {
    logoDropzone?.addEventListener(eventName, (event) => {
        event.preventDefault();
        event.stopPropagation();
        logoDropzone.classList.remove('is-dragover');
    });
});

logoDropzone?.addEventListener('drop', (event) => {
    event.preventDefault();
    event.stopPropagation();
    logoDropzone.classList.remove('is-dragover');

    const file = event.dataTransfer?.files?.[0];
    if (!file || !logoInput) return;

    if (!isValidLogo(file)) return;

    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(file);
    logoInput.files = dataTransfer.files;
    logoInput.dispatchEvent(new Event('change', { bubbles: true }));
});
</script>

<?= $this->endSection() ?>
