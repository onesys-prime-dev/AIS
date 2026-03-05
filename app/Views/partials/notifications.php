<div class="w-full lg:w-96 bg-white rounded-2xl shadow-lg p-6 flex flex-col">
    <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg font-semibold text-gray-800">Notifications</h3>
        <button class="text-sm text-indigo-600 hover:text-indigo-800">Clear All</button>
    </div>

    <?php if (empty($notifications)): ?>
        <p class="text-gray-500 text-center py-8">No new notifications</p>
    <?php else: ?>
    <div class="space-y-4 flex-1 overflow-y-auto">
        <?php foreach ($notifications as $n): ?>
        <div class="flex gap-4 p-4 <?= $n['type'] === 'accepted' ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100' ?> rounded-xl border">
            <div class="w-10 h-10 rounded-full <?= $n['type'] === 'accepted' ? 'bg-green-100' : 'bg-red-100' ?> flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-<?= $n['type'] === 'accepted' ? 'check' : 'xmark' ?> <?= $n['type'] === 'accepted' ? 'text-green-600' : 'text-red-600' ?>"></i>
            </div>
            <div>
                <p class="text-sm">
                    Your Overtime Request for <span class="font-medium"><?= esc($n['date']) ?></span> has been 
                    <span class="<?= $n['type'] === 'accepted' ? 'text-green-700' : 'text-red-700' ?> font-medium">
                        <?= $n['type'] === 'accepted' ? 'accepted' : 'denied' ?>
                    </span> by Admin
                </p>
                <p class="text-xs text-gray-500 mt-1"><?= esc($n['time_ago']) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>