<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Dashboard') ?> | Your Company</title>

    <?php
        // The favicon follows the company's uploaded logo (Settings > Customization)
        // so branding stays consistent between the sidebar and the browser tab;
        // falls back to the static default when no logo has been uploaded yet.
        $faviconUrl = ! empty($activeCompanyBranding['logo_url'] ?? null)
            ? $activeCompanyBranding['logo_url']
            : base_url('favicon.ico');
        $faviconType = (string) ($activeCompanyBranding['logo_mime_type'] ?? '');
    ?>
    <link rel="icon" href="<?= esc($faviconUrl, 'attr') ?>"<?= $faviconType !== '' ? ' type="' . esc($faviconType, 'attr') . '"' : '' ?>>

    <?php
        $mainCssVer = is_file(FCPATH . 'css/main.css') ? filemtime(FCPATH . 'css/main.css') : time();
        $toastCssVer = is_file(FCPATH . 'css/toast.css') ? filemtime(FCPATH . 'css/toast.css') : time();
        $styleCssVer = is_file(FCPATH . 'css/style.css') ? filemtime(FCPATH . 'css/style.css') : time();
    ?>
    <link href="<?= base_url('css/main.css') ?>?v=<?= $mainCssVer ?>" rel="stylesheet">
    <link href="<?= base_url('css/scrollBar.css') ?>" rel="stylesheet">
    <link href="<?= base_url('css/tailwind.css') ?>" rel="stylesheet">
    <link href="<?= base_url('css/style.css') ?>?v=<?= $styleCssVer ?>" rel="stylesheet">
    <link href="<?= base_url('css/toast.css') ?>?v=<?= $toastCssVer ?>" rel="stylesheet">

    <?= $this->renderSection('styles') ?>
</head>

<body class="min-h-screen font-sans antialiased scroll-smooth" style="background: var(--bg-color, #E7E3E3); font-family: var(--font-family, inherit);">

<?= view('partials/loading_overlay') ?>

<div class="app-shell flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <?= view('partials/sidebar') ?>

    <!-- Main area -->
    <div class="app-main flex-1 flex flex-col overflow-hidden min-w-0">

        <!-- Header -->
        <?php
            helper('notification');

            $headerIdentity = [
                'photo_url' => null,
                'display_name' => 'User',
                'display_email' => '',
            ];

            $notifications = [];
            $headerUser = auth()->user();

            if ($headerUser) {
                $headerIdentity = (new \App\Services\Client\AccountService())->getHeaderIdentity((int) $headerUser->id);
                $notificationService = new \App\Services\Client\NotificationService();
                $notifications = $notificationService->getVisibleNotificationsForUser($headerUser, 10);
            }
        ?>
        <?= view('partials/header', ['headerIdentity' => $headerIdentity, 'notifications' => $notifications]) ?>

        <!-- Page Content -->
        <main class="app-content flex-1 flex flex-col min-h-0 min-w-0">
            <?= $this->renderSection('content') ?>
        </main>

    </div>

</div>

<style>
    html,
    body {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    .app-shell {
        width: 100%;
        max-width: 100vw;
    }

    .app-main,
    .app-content,
    .app-content > * {
        min-width: 0;
    }

    @media (max-width: 900px) {
        .app-shell {
            min-height: 100dvh;
            height: 100dvh;
        }

        .app-content {
            overflow-x: hidden;
            overflow-y: auto;
            overscroll-behavior-y: contain;
        }
    }

    @media (max-width: 900px) and (pointer: coarse) {
        .app-shell input:not([type="checkbox"]):not([type="radio"]),
        .app-shell select,
        .app-shell textarea {
            font-size: 16px !important;
        }
    }
</style>

<script>
window.notificationViewedUrl = <?= json_encode(site_url('client_portal/announcements/viewed')) ?>;
window.notificationDismissedUrl = <?= json_encode(site_url('client_portal/announcements/dismiss')) ?>;
window.requestNotificationViewedUrl = <?= json_encode(site_url('client_portal/request-notifications/viewed')) ?>;
window.requestNotificationDismissedUrl = <?= json_encode(site_url('client_portal/request-notifications/dismiss')) ?>;
window.requestsListUrl = <?= json_encode(site_url('client_portal/requests')) ?>;
window.notificationCsrfTokenName = <?= json_encode(csrf_token()) ?>;
window.notificationCsrfTokenHash = <?= json_encode(csrf_hash()) ?>;
window.dashboardUrl = <?= json_encode(site_url('client_portal/dashboard')) ?>;
window.currentUserId = <?= json_encode(auth()->loggedIn() ? (int) auth()->user()->id : 0) ?>;
window.canEditAnnouncements = <?= json_encode(auth()->loggedIn() && \App\Libraries\PortalAccess::can('announcements', auth()->user())) ?>;
window.announcementEditFetchUrlBase = <?= json_encode(site_url('client_portal/announcements/edit')) ?>;
window.announcementUpdateUrlBase = <?= json_encode(site_url('client_portal/announcements/update')) ?>;
window.announcementAttachmentDeleteUrlBase = <?= json_encode(site_url('client_portal/announcements/attachments/delete')) ?>;

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
    const fontFamily = serverBranding.font_family || localStorage.getItem('fontFamily') || 'default';
    const fontSize = serverBranding.font_size || localStorage.getItem('fontSize') || 'medium';

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
        // Only unstyled text (headings/labels with no color class of their own)
        // actually uses this — anything with its own Tailwind text-color class
        // or inline color keeps that, regardless of the page background.
        const contentText = getContrastColor(bgColor);
        document.documentElement.style.setProperty('--content-text', contentText);
        // Matches today's gray-500 on the default light background exactly;
        // only switches to a lighter gray once the background is dark enough
        // to need it.
        document.documentElement.style.setProperty('--content-text-muted', contentText === '#000000' ? '#6b7280' : '#cbd5e1');
    }

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

    // Multiplier applied to the header/sidebar's own hardcoded px font sizes
    // (font-size: calc(Npx * var(--font-scale, 1))) since they don't use
    // rem units and so don't automatically follow the root font-size below.
    const FONT_SCALE_FACTORS = {
        small: 0.875,
        medium: 1,
        large: 1.125,
        xlarge: 1.25,
    };

    document.documentElement.style.setProperty('--font-family', FONT_FAMILY_STACKS[fontFamily] || FONT_FAMILY_STACKS.default);
    document.documentElement.style.setProperty('--font-scale', FONT_SCALE_FACTORS[fontSize] ?? 1);
    document.documentElement.style.fontSize = FONT_SIZE_SCALES[fontSize] || FONT_SIZE_SCALES.medium;
});

</script>

<?= $this->renderSection('modals') ?>

<!-- Modals placed at end of body to avoid stacking context issues -->
<?= view('client_portal/modals/view_notification') ?>
<?= view('client_portal/modals/view_request_notification') ?>

<?php
    $toastJsPath = FCPATH . 'js/components/toast.js';
    $notifJsPath = FCPATH . 'js/components/notification-modals.js';
    $toastJsVer = is_file($toastJsPath) ? filemtime($toastJsPath) : time();
    $notifJsVer = is_file($notifJsPath) ? filemtime($notifJsPath) : time();
?>
<script src="<?= base_url('js/components/toast.js') ?>?v=<?= $toastJsVer ?>"></script>
<script src="<?= base_url('js/components/notification-modals.js') ?>?v=<?= $notifJsVer ?>"></script>
<?php if (auth()->loggedIn()): ?>
<script src="<?= base_url('js/components/notifications-poll.js') ?>?v=<?= is_file(FCPATH . 'js/components/notifications-poll.js') ? filemtime(FCPATH . 'js/components/notifications-poll.js') : time() ?>"></script>
<?php endif; ?>
<script>
<?php if (session()->getFlashdata('success')): ?>
window.showToast(<?= json_encode(session()->getFlashdata('success')) ?>, 'success');
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
window.showToast(<?= json_encode(session()->getFlashdata('error')) ?>, 'error');
<?php endif; ?>
</script>

<?= $this->renderSection('scripts') ?>

<?php if (auth()->loggedIn()): ?>
<script>
    window.PRESENCE_HEARTBEAT_URL = "<?= site_url('client_portal/presence/heartbeat') ?>";
    window.PRESENCE_STATUSES_URL  = "<?= site_url('client_portal/presence/statuses') ?>";
</script>
<script src="<?= base_url('js/components/presence.js') ?>?v=<?= is_file(FCPATH . 'js/components/presence.js') ? filemtime(FCPATH . 'js/components/presence.js') : time() ?>"></script>
<?php endif; ?>

</body>
</html>
