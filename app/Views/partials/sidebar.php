<aside class="sidebar">
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
        z-index:999;
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
    @media (max-width: 767px){
        .sidebar{
            position:fixed;
            top:0; left:0;
            height:100vh;
            z-index:1000;
            transform:translateX(-100%);
            transition:transform .25s ease;
            box-shadow:4px 0 24px rgba(0,0,0,.3);
        }
        .sidebar.mobile-open{
            transform:translateX(0);
        }
        .sidebar-close-btn{
            display:block;
        }
        .sidebar-backdrop.mobile-open{
            display:block;
        }
    }
</style>

<div id="sidebarBackdrop" class="sidebar-backdrop" onclick="closeMobileSidebar()"></div>

<script>
    function openMobileSidebar() {
        document.querySelector('.sidebar')?.classList.add('mobile-open');
        document.getElementById('sidebarBackdrop')?.classList.add('mobile-open');
        document.body.style.overflow = 'hidden';
    }
    function closeMobileSidebar() {
        document.querySelector('.sidebar')?.classList.remove('mobile-open');
        document.getElementById('sidebarBackdrop')?.classList.remove('mobile-open');
        document.body.style.overflow = '';
    }
    window.openMobileSidebar = openMobileSidebar;
    window.closeMobileSidebar = closeMobileSidebar;
</script>