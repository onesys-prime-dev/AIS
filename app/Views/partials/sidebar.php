<aside id="appSidebar" class="sidebar" aria-label="Primary navigation" aria-hidden="false">
    <div class="sidebar-inner">

        <!-- Mobile-only close button — the backdrop (tap-outside) also
             closes it, but an explicit close affordance is expected. -->
        <button type="button" class="sidebar-close-btn" aria-label="Close menu" onclick="closeMobileSidebar()">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <!-- LOGO -->
        <div class="logo-box">
            <?php $logoUrl = $activeCompanyBranding['logo_url'] ?? null; ?>
            <div class="logo<?= !empty($logoUrl) ? ' has-image' : '' ?>"<?= !empty($logoUrl) ? ' style="background-image:url(\'' . esc((string) $logoUrl, 'attr') . '\');"' : '' ?>></div>
        </div>

        <!-- MAIN MENU -->
        <nav class="menu">
            <a href="<?= site_url('client_portal/dashboard') ?>"
                class="menu-item <?= current_url() === site_url('client_portal/dashboard') ? 'active' : '' ?>">
                <img src="<?= base_url('icons/dashboard.png') ?>" class="icon">
                <span>Dashboard</span>
            </a>

            <a href="<?= site_url('client_portal/timesheet') ?>"
                class="menu-item <?= str_contains(current_url(), 'client_portal/timesheet') ? 'active' : '' ?>">
                <img src="<?= base_url('icons/timesheet.png') ?>" class="icon">
                <span>Timesheets</span>
            </a>

            <a href="<?= site_url('client_portal/requests') ?>"
                    class="menu-item <?= str_contains(current_url(), 'client_portal/requests') ? 'active' : '' ?>">
                    <img src="<?= base_url('icons/Requests.png') ?>" class="icon">
                    <span>Requests</span>
                </a>

            <!-- Client Payroll Staff ONLY -->
            <?php if(\App\Libraries\PortalAccess::can('payroll')): ?>
                <a href="<?= site_url('client_portal/payroll') ?>"
                    class="menu-item <?= str_contains(current_url(), 'client_portal/payroll') ? 'active' : '' ?>">
                    <img src="<?= base_url('icons/payroll.png') ?>" class="icon">
                    <span>Payroll</span>
                </a>

            <?php endif; ?>
            <?php if (\App\Libraries\PortalAccess::can('employees')): ?>
                <a href="<?= site_url('client_portal/employees') ?>"
                    class="menu-item <?= str_contains(current_url(), 'client_portal/employees') ? 'active' : '' ?>">
                    <img src="<?= base_url('icons/employee.png') ?>" class="icon">
                    <span>Employee</span>
                </a>

            <?php endif; ?>
        </nav>

        <!-- BOTTOM MENU -->
        <div class="bottom-menu">
            <a href="<?= site_url('client_portal/help') ?>"
                class="menu-item <?= str_contains(current_url(), 'client_portal/help') ? 'active' : '' ?>">
                <img src="<?= base_url('icons/help.png') ?>" class="icon">
                <span>Help</span>
            </a>
        </div>
    </div>
</aside>


<style>

    :root {
        --sidebar-bg: #1e1e1e;
        --sidebar-text: #cfcfcf;
        --sidebar-icon-filter: invert(0);
        --sidebar-divider: #2c2c2c;

        --sidebar-hover: #333;
        --sidebar-active: #555;
    }

    /* SIDEBAR */
    .sidebar{
        width: 120px;
        background: var(--sidebar-bg);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        font-family: Segoe UI, Arial;
        flex-shrink: 0;
    }

    /* INNER */
    .sidebar-inner{
        display: flex;
        flex-direction: column;
        height: 100%;
        width: 100%;
        align-items: center;
    }

    /* LOGO */
    .logo-box{
        padding: 15px 0px;
        border-bottom: 1px solid var(--sidebar-divider);
        width: 100%;
        display: flex;
        justify-content: center;
    }

    .logo{
        width: 70px;
        height: 70px;
        background: #d9d9d9;
        border-radius: 4px;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    .logo.has-image {
        background-color: transparent;
    }

    /* MAIN MENU */
    .menu{
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 15px 0px;
        align-items: center;
        width: 100%;
    }

    /* MENU ITEM */
    .menu-item{
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        width: 72px;
        height: 72px;
        border-radius: 16px;
        text-decoration: none;
        color: var(--sidebar-text);
        font-size: calc(11px * var(--font-scale, 1));
        transition: background-color 0.2s, color 0.2s, transform 0.2s, box-shadow 0.2s;
    }

    /* ACTIVE ITEM */
    .menu-item.active{
        background: var(--sidebar-active);
        color:white;
    }

    /* HOVER */
    .menu-item:hover{
        background: var(--sidebar-hover);
        color: #ffffff;
        transform: translateX(2px);
    }

    /* ICON */
    .icon{
        width:28px;
        height:28px;
        filter: var(--sidebar-icon-filter);
        transition: 0.2s;
    }

    /* slightly brighter icon when active */
    .menu-item.active .icon{
        filter: brightness(1.2) var(--sidebar-icon-filter);
    }

    .menu-item:hover .icon{
        filter: brightness(1.12) var(--sidebar-icon-filter);
    }

    /* BOTTOM MENU */
    .bottom-menu{
        margin-top:auto;
        padding-bottom:15px;
        display:flex;
        flex-direction:column;
        gap:10px;
        align-items:center;
        width:100%;
    }

    /* MOBILE — the sidebar is otherwise a fixed 120px column with no way to
       hide it, which eats ~30% of a phone screen permanently. Below 768px it
       becomes an off-canvas panel: out of normal flow (so the content area
       gets its full width back), slid in via a class toggled by the header's
       hamburger button (see partials/header.php). */
    .sidebar-backdrop{
        display:none;
        position:fixed;
        inset:0;
        background:rgba(0,0,0,.45);
        z-index:1001;
    }
    .sidebar-close-btn{
        display:none;
        position:absolute;
        top:10px;
        right:10px;
        background:none;
        border:none;
        color:var(--sidebar-text);
        font-size:18px;
        cursor:pointer;
        padding:6px;
        z-index:1;
    }
    @media (max-width: 900px){
        .sidebar{
            position:fixed;
            top:0; left:0;
            width:min(82vw, 300px);
            height:100dvh;
            z-index:1002;
            transform:translateX(-100%);
            transition:transform .25s ease, visibility 0s linear .25s;
            box-shadow:4px 0 24px rgba(0,0,0,.3);
            align-items:stretch;
            visibility:hidden;
        }
        .sidebar.mobile-open{
            transform:translateX(0);
            visibility:visible;
            transition-delay:0s;
        }
        .sidebar-inner{
            align-items:stretch;
            overflow-y:auto;
            overscroll-behavior:contain;
        }
        .logo-box{
            min-height:76px;
            padding:10px 20px;
            justify-content:flex-start;
        }
        .logo{
            width:54px;
            height:54px;
        }
        .menu{
            align-items:stretch;
            gap:6px;
            padding:14px 12px;
        }
        .menu-item{
            width:100%;
            height:52px;
            min-height:52px;
            flex-direction:row;
            justify-content:flex-start;
            gap:14px;
            padding:0 16px;
            border-radius:12px;
            font-size:calc(14px * var(--font-scale, 1));
        }
        .menu-item:hover{
            transform:none;
        }
        .menu-item .icon{
            width:24px;
            height:24px;
            flex:0 0 24px;
        }
        .bottom-menu{
            align-items:stretch;
            padding:12px;
        }
        .sidebar-close-btn{
            display:block;
            width:44px;
            height:44px;
            padding:0;
        }
        .sidebar-backdrop{
            display:block;
            opacity:0;
            visibility:hidden;
            pointer-events:none;
            transition:opacity .2s ease, visibility 0s linear .2s;
        }
        .sidebar-backdrop.mobile-open{
            display:block;
            opacity:1;
            visibility:visible;
            pointer-events:auto;
            transition-delay:0s;
        }
    }
</style>

<div id="sidebarBackdrop" class="sidebar-backdrop" onclick="closeMobileSidebar()"></div>

<script>
    function openMobileSidebar() {
        // The header's notification/user dropdowns (notification-modals.js)
        // don't know about this sidebar and vice versa, so nothing closed
        // the other one — both could end up open at once on a phone, stacked
        // as two overlapping overlays. Only one of the three should ever be
        // open at a time.
        document.getElementById('notifDropdown')?.style.setProperty('display', 'none');
        document.getElementById('userDropdown')?.style.setProperty('display', 'none');
        const sidebar = document.getElementById('appSidebar');
        const toggle = document.getElementById('sidebarToggleBtn');
        sidebar?.classList.add('mobile-open');
        sidebar?.setAttribute('aria-hidden', 'false');
        toggle?.setAttribute('aria-expanded', 'true');
        document.getElementById('sidebarBackdrop')?.classList.add('mobile-open');
        document.body.style.overflow = 'hidden';
        window.setTimeout(() => sidebar?.querySelector('.sidebar-close-btn')?.focus(), 50);
    }
    function closeMobileSidebar() {
        const sidebar = document.getElementById('appSidebar');
        const toggle = document.getElementById('sidebarToggleBtn');
        const wasOpen = sidebar?.classList.contains('mobile-open');
        sidebar?.classList.remove('mobile-open');
        if (window.matchMedia('(max-width: 900px)').matches) {
            sidebar?.setAttribute('aria-hidden', 'true');
        }
        toggle?.setAttribute('aria-expanded', 'false');
        document.getElementById('sidebarBackdrop')?.classList.remove('mobile-open');
        document.body.style.overflow = '';
        if (wasOpen) toggle?.focus();
    }
    document.querySelectorAll('#appSidebar a').forEach(link => {
        link.addEventListener('click', closeMobileSidebar);
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeMobileSidebar();
    });
    window.addEventListener('resize', () => {
        const sidebar = document.getElementById('appSidebar');
        if (window.innerWidth > 900) {
            sidebar?.classList.remove('mobile-open');
            sidebar?.setAttribute('aria-hidden', 'false');
            document.getElementById('sidebarBackdrop')?.classList.remove('mobile-open');
            document.getElementById('sidebarToggleBtn')?.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        } else if (!sidebar?.classList.contains('mobile-open')) {
            sidebar?.setAttribute('aria-hidden', 'true');
        }
    });
    if (window.matchMedia('(max-width: 900px)').matches) {
        document.getElementById('appSidebar')?.setAttribute('aria-hidden', 'true');
    }
    window.openMobileSidebar = openMobileSidebar;
    window.closeMobileSidebar = closeMobileSidebar;
</script>
