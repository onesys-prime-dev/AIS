<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Dashboard') ?> | Your Company</title>
    
    <link href="<?= base_url('css/main.css') ?>" rel="stylesheet">
    <link href="<?= base_url('css/scrollBar.css') ?>" rel="stylesheet">
    <link href="<?= base_url('css/tailwind.css') ?>" rel="stylesheet">
    <link href="<?= base_url('css/style.css') ?>" rel="stylesheet">
    
    <?= $this->renderSection('styles') ?>
</head>

<body class="bg-[#E7E3E3] min-h-screen font-sans antialiased scroll-smooth">

<div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <?= view('partials/sidebar') ?>

    <!-- Main area -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Header -->
        <?php
            $headerIdentity = [
                'photo_url' => null,
                'display_name' => 'User',
                'display_email' => '',
            ];

            $notifications = [];
            $headerUser = auth()->user();

            if ($headerUser) {
                $headerIdentity = (new \App\Services\Client\AccountService())->getHeaderIdentity((int) $headerUser->id);
                $announcementService = new \App\Services\Client\AnnouncementService();
                $announcementRows = $announcementService->getVisibleAnnouncementsForUser($headerUser, 10);

                $notifications = array_map(static function (array $row): array {
                    return [
                        'id' => (int) ($row['id'] ?? 0),
                        'title' => (string) ($row['title'] ?? 'Announcement'),
                        'summary' => (string) ($row['summary'] ?? $row['details'] ?? ''),
                        'details' => (string) ($row['details'] ?? $row['summary'] ?? ''),
                        'publish_at' => (string) ($row['publish_at'] ?? ''),
                        'category' => (string) ($row['category'] ?? 'General'),
                        'priority' => (string) ($row['priority'] ?? 'Normal'),
                        'audience' => (string) ($row['audience'] ?? 'All Employees'),
                        'time_ago' => (string) ($row['time_ago'] ?? ''),
                        'is_viewed' => (int) ($row['is_viewed'] ?? 0),
                    ];
                }, $announcementRows);
            }
        ?>
        <?= view('partials/header', ['headerIdentity' => $headerIdentity, 'notifications' => $notifications]) ?>

        <!-- Page Content -->
        <main class="flex-1 flex flex-col min-h-0">
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
window.notificationViewedUrl = <?= json_encode(site_url('client_portal/announcements/viewed')) ?>;
window.notificationDismissedUrl = <?= json_encode(site_url('client_portal/announcements/dismiss')) ?>;
window.notificationCsrfTokenName = <?= json_encode(csrf_token()) ?>;
window.notificationCsrfTokenHash = <?= json_encode(csrf_hash()) ?>;

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

function getSidebarIconFilter(mode, sidebarHex) {
    if (mode === 'light') return 'invert(0)';
    if (mode === 'dark') return 'invert(1)';
    if (mode === 'original') return 'none';
    return getIconFilter(sidebarHex);
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

function tintColor(hex, amount) {
    hex = hex.replace('#', '');

    const r = parseInt(hex.substring(0,2), 16);
    const g = parseInt(hex.substring(2,4), 16);
    const b = parseInt(hex.substring(4,6), 16);

    const nr = Math.round(r + (255 - r) * amount);
    const ng = Math.round(g + (255 - g) * amount);
    const nb = Math.round(b + (255 - b) * amount);

    return "#" +
        nr.toString(16).padStart(2, '0') +
        ng.toString(16).padStart(2, '0') +
        nb.toString(16).padStart(2, '0');
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

document.addEventListener("DOMContentLoaded", function () {
    const serverBranding = <?= json_encode($activeCompanyBranding ?? []) ?>;

    const headerColor = serverBranding.header_color || localStorage.getItem('headerColor');
    const sidebarColor = serverBranding.sidebar_color || localStorage.getItem('sidebarColor');
    const sidebarIconMode = serverBranding.sidebar_icon_mode || localStorage.getItem('sidebarIconMode') || 'auto';
    const bgColor = serverBranding.background_color || localStorage.getItem('bgColor');

    if (headerColor) {
        document.documentElement.style.setProperty('--header-bg', headerColor);
        document.documentElement.style.setProperty('--header-text', getContrastColor(headerColor));
        document.documentElement.style.setProperty('--header-divider', getHeaderDividerColor(headerColor));
    }

    if (sidebarColor) {
        document.documentElement.style.setProperty('--sidebar-bg', sidebarColor);
        document.documentElement.style.setProperty('--sidebar-text', getContrastColor(sidebarColor));
        document.documentElement.style.setProperty('--sidebar-icon-filter', getSidebarIconFilter(sidebarIconMode, sidebarColor));
        document.documentElement.style.setProperty('--sidebar-divider', tintColor(sidebarColor, 0.18));

        document.documentElement.style.setProperty('--sidebar-hover', tintColor(sidebarColor, 0.14));
        document.documentElement.style.setProperty('--sidebar-active', tintColor(sidebarColor, 0.25));

        const panelBg = adjustColor(sidebarColor, -0.2);
        document.documentElement.style.setProperty('--panel-bg', panelBg);
        document.documentElement.style.setProperty('--panel-text', getContrastColor(panelBg));
        document.documentElement.style.setProperty('--panel-icon-filter', getIconFilter(panelBg));
    }

    if (bgColor) {
        document.documentElement.style.setProperty('--bg-color', bgColor);
    }
});

</script>

<?= $this->renderSection('modals') ?>

<!-- Modals placed at end of body to avoid stacking context issues -->
<?= view('client_portal/modals/view_notification') ?>

<script src="<?= base_url('js/components/flash-notifications.js') ?>"></script>

<?= $this->renderSection('scripts') ?>

<?php if (auth()->loggedIn()): ?>
<script>
    window.PRESENCE_HEARTBEAT_URL = "<?= site_url('client_portal/presence/heartbeat') ?>";
    window.PRESENCE_STATUSES_URL  = "<?= site_url('client_portal/presence/statuses') ?>";
</script>
<script src="<?= base_url('js/components/presence.js') ?>"></script>
<?php endif; ?>

</body>
</html>