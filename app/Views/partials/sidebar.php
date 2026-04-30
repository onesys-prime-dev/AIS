<aside class="sidebar">
    <div class="sidebar-inner">

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
            <?php if(auth()->user()->inGroup('client_payroll_staff')): ?>
                <a href="<?= site_url('client_portal/payroll') ?>"
                    class="menu-item <?= str_contains(current_url(), 'client_portal/payroll') ? 'active' : '' ?>">
                    <img src="<?= base_url('icons/payroll.png') ?>" class="icon">
                    <span>Payroll</span>
                </a>

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
        font-size: 11px;
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

</style>