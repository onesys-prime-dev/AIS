<aside class="hidden md:flex md:flex-col w-64 bg-[#212121] text-white flex-shrink-0">
    <div class="p-6 border-b border-[#DAD7D7]">
        <h1 class="text-2xl font-bold tracking-tight">YourCompany</h1>
    </div>
    
    <nav class="flex-1 px-4 py-6 space-y-1">
        <a href="<?= site_url('dashboard') ?>" class="flex items-center px-4 py-3 rounded-lg <?= current_url() === site_url('dashboard') ? 'bg-[#4f46e5]/70' : 'hover:bg-[#4f46e5]/50' ?>">
            <i class="fa-solid fa-house mr-3 w-5"></i>
            <span>Dashboard</span>
        </a>
        <a href="#" class="flex items-center px-4 py-3 rounded-lg hover:bg-[#4f46e5]/50 transition">
            <i class="fa-solid fa-clock-rotate-left mr-3 w-5"></i>
            <span>Timesheet</span>
        </a>
        <a href="<?= site_url('requests') ?>" class="flex items-center px-4 py-3 rounded-lg hover:bg-[#4f46e5]/50 transition">
            <i class="fa-solid fa-file-lines mr-3 w-5"></i>
            <span>Requests</span>
        </a>
        <!-- more menu items -->
    </nav>

    <div class="p-4 border-t border-[#DAD7D7]">
        <a href="<?= site_url('logout') ?>" class="flex items-center px-4 py-3 rounded-lg hover:bg-[#4f46e5]/50 transition">
            <i class="fa-solid fa-arrow-right-from-bracket mr-3 w-5"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>