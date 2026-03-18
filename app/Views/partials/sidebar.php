<aside class="sidebar">
    <div class="sidebar-inner">

        <!-- LOGO -->
        <div class="logo-box">
            <div class="logo"></div>
        </div>

        <!-- MAIN MENU -->
        <nav class="menu">
            <a href="<?= site_url('client_portal/dashboard') ?>"
                class="menu-item <?= current_url() === site_url('client_portal/dashboard') ? 'active' : '' ?>">
                <img src="<?= base_url('icons/dashboard.png') ?>" class="icon">
                <span>Dashboard</span>
            </a>

            <a href="<?= site_url('client_portal/timesheet') ?>" class="menu-item">
                <img src="<?= base_url('icons/timesheet.png') ?>" class="icon">
                <span>Timesheets</span>
            </a>

            <?php if(auth()->user()->inGroup('employee')): ?>
                <!-- Payslip -->
                <a href="<?= site_url('client_portal/payslip') ?>" 
                    class="flex items-center px-4 py-3 rounded-lg hover:bg-[#4f46e5]/50">
                    <i class="fa-solid fa-file-invoice-dollar mr-3 w-5"></i>
                        <span>Payslip</span>
                </a>
            <?php endif; ?>

                <!-- Client Payroll Staff ONLY -->
            <?php if(auth()->user()->inGroup('client_payroll_staff')): ?>
                <a href="<?= site_url('client_portal/payroll') ?>" class="menu-item">
                    <img src="<?= base_url('icons/payroll.png') ?>" class="icon">
                    <span>Payroll</span>
                </a>

                <a href="<?= site_url('client_portal/employees') ?>" class="menu-item">
                    <img src="<?= base_url('icons/employee.png') ?>" class="icon">
                    <span>Employee</span>
                </a>

            <?php endif; ?>

            <!-- Requests -->
            <a href="<?= site_url('client_portal/requests') ?>" class="menu-item">
                <img src="<?= base_url('icons/Requests.png') ?>" class="icon">
                <span>Requests</span>
            </a>

        </nav>

        <!-- BOTTOM MENU -->
        <div class="bottom-menu">
            <a href="<?= site_url('help') ?>" class="menu-item">
                <img src="<?= base_url('icons/help.png') ?>" class="icon">
                <span>Help</span>
            </a>

            <a href="#" id="settings-toggle" class="menu-item">
                <img src="<?= base_url('icons/settings.png') ?>" class="icon">
                <span>Settings</span>
            </a>

            <a href="<?= site_url('logout') ?>" class="menu-item">
                <img src="<?= base_url('icons/logout.png') ?>" class="icon">
                <span>Log-out</span>
            </a>
        </div>
    </div>
</aside>

<div id="settings-panel" class="settings-panel">
    <div class="panel-header">Settings</div>

    <a href="<?= site_url('settings/customization') ?>" class="panel-item">
        <img src="<?= base_url('icons/customization.png') ?>" class="panel-icon">
        Customization
    </a>
</div>

<div id="overlay" class="overlay"></div>

<style>

    /* SIDEBAR */

    .sidebar{
        width:95px;
        background:#1e1e1e;
        min-height:100vh;
        display:flex;
        flex-direction:column;
        align-items:center;
        border-right:1px solid #2c2c2c;
        font-family:Segoe UI, Arial;
        flex-shrink:0;
        position: relative;
        z-index: 1100;
    }

    /* INNER */

    .sidebar-inner{
        display:flex;
        flex-direction:column;
        height:100%;
        width:100%;
        align-items:center;
        overflow-y: auto;
    }

    .sidebar-inner::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar-inner::-webkit-scrollbar-thumb {
        background: #444;
        border-radius: 10px;
    }

    /* LOGO */

    .logo-box{
        padding:25px 0;
        border-bottom:1px solid #2c2c2c;
        width:100%;
        display:flex;
        justify-content:center;
    }

    .logo{
        width:50px;
        height:50px;
        background:#d9d9d9;
        border-radius:4px;
    }

    /* MAIN MENU */

    .menu{
        display:flex;
        flex-direction:column;
        gap:10px;
        padding-top:15px;
        align-items:center;
        width:100%;
    }

    /* MENU ITEM */

    .menu-item{
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        gap:6px;
        width:72px;
        height:72px;
        border-radius:16px;
        text-decoration:none;
        color:#cfcfcf;
        font-size:11px;
        transition:0.2s;
    }



    /* ACTIVE ITEM */

    .menu-item.active{
    background:#555;
    color:white;
    }



    /* HOVER */

    .menu-item:hover{
    background:#333;
    }



    /* ICON */

    .icon{
    width:28px;
    height:28px;
    }



    /* BOTTOM MENU */

    .bottom-menu{
    margin-top:auto;
    padding-bottom:20px;
    display:flex;
    flex-direction:column;
    gap:10px;
    align-items:center;
    width:100%;
    }

    /* PANEL */
    .settings-panel {
        position: fixed;
        top: 0;
        left: 95px; /* same as sidebar width */
        width: 220px;
        height: 100vh;
        background: #2a2a2a;
        border-right: 1px solid #3a3a3a;
        padding-top: 20px;

        transform: translateX(-100%);
        transition: transform 0.25s ease;
        z-index: 1000;
    }

    /* ACTIVE STATE */
    .settings-panel.open {
        transform: translateX(0);
    }

    .panel-header {
        font-size: 18px;
        font-weight: 600;
        color: #fff;
        padding: 12px 20px;
        margin-bottom: 10px;
    }

    /* PANEL ITEM */
    .panel-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        color: #ddd;
        text-decoration: none;
        font-size: 14px;
    }

    .panel-icon {
        width: 18px;
        height: 18px;
    }

    .panel-item:hover {
        background: #3a3a3a;
    }

    /* OVERLAY (click outside) */
    .overlay {
        position: fixed;
        top: 0;
        left: 95px; /* start AFTER sidebar */
        width: calc(100% - 95px);
        height: 100vh;

        background: transparent;
        display: none;
        z-index: 999;
    }

    .overlay.show {
        display: block;
        background: rgba(0,0,0,0.2);
    }

</style>

<script>
    const toggleBtn = document.getElementById('settings-toggle');
    const panel = document.getElementById('settings-panel');
    const overlay = document.getElementById('overlay');

    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();

        panel.classList.toggle('open');
        overlay.classList.toggle('show');

        toggleBtn.classList.toggle('active');
    });

    overlay.addEventListener('click', function() {
        panel.classList.remove('open');
        overlay.classList.remove('show');
        
        toggleBtn.classList.remove('active');

    });

</script>