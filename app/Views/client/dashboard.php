<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="flex flex-col lg:flex-row gap-6">

    <!-- Clock Card -->
    <div class="flex-1 bg-white rounded-2xl shadow-lg p-8 relative overflow-hidden">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Welcome back, <?= esc(auth()->user()->username ?? 'User') ?>!</h2>
        
        <div class="mt-8 flex flex-col items-center">
            <div class="relative w-48 h-48">
                <!-- Simple progress ring (adjust stroke-dashoffset dynamically if needed) -->
                <svg class="w-full h-full -rotate-90" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="54" fill="none" stroke="#e5e7eb" stroke-width="12"/>
                    <circle cx="60" cy="60" r="54" fill="none" stroke="#4f46e5" stroke-width="12"
                            stroke-dasharray="339" stroke-dashoffset="84" stroke-linecap="round"/>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <div class="text-5xl font-bold text-gray-800"><?= esc($currentTime ?? '10:15') ?></div>
                    <div class="text-sm text-gray-500 mt-1"><?= esc($currentDate ?? date('l, F j, Y')) ?></div>
                </div>
            </div>

            <a href="<?= site_url('requests/new') ?>" 
               class="absolute top-6 right-6 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg text-sm font-medium shadow flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> Request Form
            </a>
        </div>
    </div>

    <!-- Notifications -->
    <?= view('partials/notifications', ['notifications' => $notifications ?? []]) ?>

</div>

<?= $this->endSection() ?>