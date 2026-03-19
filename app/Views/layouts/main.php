<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Dashboard') ?> | Your Company</title>
    
    <link href="<?= base_url('css/main.css') ?>" rel="stylesheet">
    <link href="<?= base_url('css/scrollBar.css') ?>" rel="stylesheet">
    
    <?= $this->renderSection('styles') ?>
</head>

<body class="bg-[#E7E3E3] min-h-screen font-sans antialiased">

<div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <?= view('partials/sidebar') ?>

    <!-- Main area -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Header -->
        <?= view('partials/header') ?>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto px-4">
            <!-- Flash Toast Notifications -->
            <?php if (session()->getFlashdata('success')): ?>
            <div id="toast-success"
                class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 
                z-[9999] flex items-center gap-3 
                bg-green-100 border border-green-400 text-green-700 
                px-6 py-4 rounded-lg shadow-xl
                opacity-0 scale-95 transition-all duration-300">

                <i class="fa-solid fa-circle-check text-lg"></i>
                <span class="font-medium"><?= session()->getFlashdata('success') ?></span>

            </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
            <div id="toast-error"
                class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 
                z-[9999] flex items-center gap-3 
                bg-red-100 border border-red-400 text-red-700 
                px-6 py-4 rounded-lg shadow-xl
                opacity-0 scale-95 transition-all duration-300">

                <i class="fa-solid fa-circle-exclamation text-lg"></i>
                <span class="font-medium"><?= session()->getFlashdata('error') ?></span>

            </div>
            <?php endif; ?>
            <?= $this->renderSection('content') ?>
        </main>

    </div>

</div>

<script>
function getContrastColor(hex) {
    hex = hex.replace('#', '');

    const r = parseInt(hex.substring(0,2), 16);
    const g = parseInt(hex.substring(2,4), 16);
    const b = parseInt(hex.substring(4,6), 16);

    const brightness = (r * 299 + g * 587 + b * 114) / 1000;

    return brightness > 155 ? '#000000' : '#ffffff';
}

function getIconFilter(hex) {
    hex = hex.replace('#', '');

    const r = parseInt(hex.substring(0,2), 16);
    const g = parseInt(hex.substring(2,4), 16);
    const b = parseInt(hex.substring(4,6), 16);

    const brightness = (r * 299 + g * 587 + b * 114) / 1000;

    return brightness > 155 ? 'invert(1)' : 'invert(0)';
}

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

document.addEventListener("DOMContentLoaded", function () {
    const headerColor = localStorage.getItem('headerColor');
    const sidebarColor = localStorage.getItem('sidebarColor');

    if (headerColor) {
        document.documentElement.style.setProperty('--header-bg', headerColor);
        document.documentElement.style.setProperty('--header-text', getContrastColor(headerColor));
    }

    if (sidebarColor) {
    document.documentElement.style.setProperty('--sidebar-bg', sidebarColor);
    document.documentElement.style.setProperty('--sidebar-text', getContrastColor(sidebarColor));
    document.documentElement.style.setProperty('--sidebar-icon-filter', getIconFilter(sidebarColor));

    document.documentElement.style.setProperty('--sidebar-hover', adjustColor(sidebarColor, 0.15));
    document.documentElement.style.setProperty('--sidebar-active', adjustColor(sidebarColor, 0.3));

    const panelBg = adjustColor(sidebarColor, -0.2);
    document.documentElement.style.setProperty('--panel-bg', panelBg);
    document.documentElement.style.setProperty('--panel-text', getContrastColor(panelBg));
    document.documentElement.style.setProperty('--panel-icon-filter', getIconFilter(panelBg));
}
});
</script>

<?= $this->renderSection('modals') ?>

<?= $this->renderSection('scripts') ?>

</body>
</html>