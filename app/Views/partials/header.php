<header class="bg-white border-b shadow-sm px-6 py-3 flex items-center justify-between">
    <div class="flex items-center gap-4">
        <button class="md:hidden text-gray-600">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>
        <div class="relative">
            <input type="text" placeholder="What are you looking for?" 
                   class="pl-10 pr-4 py-2 bg-gray-100 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64 md:w-80">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </div>
    </div>

    <div class="flex items-center gap-4">
        <button class="relative text-gray-600 hover:text-indigo-700">
            <i class="fa-solid fa-bell text-xl"></i>
            <?php if (!empty($notifications) && count($notifications) > 0): ?>
                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                    <?= count($notifications) ?>
                </span>
            <?php endif; ?>
        </button>

        <div class="flex items-center gap-3">
            <?php $user = auth()->user(); ?>
            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-semibold">
                <?= strtoupper(substr($user->username ?? $user->email, 0, 2)) ?>
            </div>
            <div class="hidden sm:block">
                <div class="font-medium"><?= esc($user->username ?? 'User') ?></div>
                <div class="text-xs text-gray-500"><?= esc($user->email) ?></div>
            </div>
        </div>
    </div>
</header>