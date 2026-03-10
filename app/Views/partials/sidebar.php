<aside class="hidden md:flex md:flex-col w-64 bg-[#212121] text-white flex-shrink-0">
    <div class="p-6 border-b border-[#DAD7D7]">
        <h1 class="text-2xl font-bold tracking-tight">YourCompany</h1>
    </div>
    
    <nav class="flex-1 px-4 py-6 space-y-1">

        <!-- Dashboard -->
        <a href="<?= site_url('client_portal/dashboard') ?>" 
        class="flex items-center px-4 py-3 rounded-lg <?= current_url() === site_url('client_portal/dashboard') ? 'bg-[#4f46e5]/70' : 'hover:bg-[#4f46e5]/50' ?>">
            <i class="fa-solid fa-house mr-3 w-5"></i>
            <span>Dashboard</span>
        </a>

        <!-- Timesheets (both roles) -->
        <a href="<?= site_url('client_portal/timesheets') ?>" 
        class="flex items-center px-4 py-3 rounded-lg hover:bg-[#4f46e5]/50">
            <i class="fa-solid fa-clock-rotate-left mr-3 w-5"></i>
            <span>Timesheets</span>
        </a>

        <!-- Client Payroll Staff ONLY -->
        <?php if(auth()->user()->inGroup('client_payroll_staff')): ?>

            <a href="<?= site_url('client_portal/payroll') ?>" 
            class="flex items-center px-4 py-3 rounded-lg hover:bg-[#4f46e5]/50">
                <i class="fa-solid fa-users mr-3 w-5"></i>
                <span>Payroll</span>
            </a>

            <a href="<?= site_url('client_portal/employees') ?>" 
            class="flex items-center px-4 py-3 rounded-lg hover:bg-[#4f46e5]/50">
                <i class="fa-solid fa-users mr-3 w-5"></i>
                <span>Employee Management</span>
            </a>

            <a href="<?= site_url('client_portal/requests') ?>" 
            class="flex items-center px-4 py-3 rounded-lg hover:bg-[#4f46e5]/50">
                <i class="fa-solid fa-file-lines mr-3 w-5"></i>
                <span>Request Management</span>
            </a>

        <?php endif; ?>

    </nav>

    <div class="p-4 border-t border-[#DAD7D7]">
        <a href="<?= site_url('settings') ?>" class="flex items-center px-4 py-3 rounded-lg hover:bg-[#4f46e5]/50 transition">
            <i class="fa-solid fa-arrow-right-from-bracket mr-3 w-5"></i>
            <span>Settings</span>
        </a>
        <a href="<?= site_url('information') ?>" class="flex items-center px-4 py-3 rounded-lg hover:bg-[#4f46e5]/50 transition">
            <i class="fa-solid fa-arrow-right-from-bracket mr-3 w-5"></i>
            <span>Information</span>
        </a>
        <a href="<?= site_url('logout') ?>" class="flex items-center px-4 py-3 rounded-lg hover:bg-[#4f46e5]/50 transition">
            <i class="fa-solid fa-arrow-right-from-bracket mr-3 w-5"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>