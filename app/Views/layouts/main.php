<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Dashboard') ?> | Your Company</title>
    
    <link href="<?= base_url('css/main.css') ?>" rel="stylesheet">
    
    <?= $this->renderSection('styles') ?>
</head>

<body class="bg-[#E7E3E3] min-h-screen font-sans antialiased">

<div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <?= view('partials/sidebar') ?>

    <!-- Main area -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Header -->
        <?= view('partials/header') ?>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto px-4">
            <!-- Flash Toast Notifications -->
            <?php if (session()->getFlashdata('success')): ?>
            <div id="toast-success"
                class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 
                z-[9999] flex items-center gap-3 
                bg-green-100 border border-green-400 text-green-700 
                px-6 py-4 rounded-lg shadow-xl
                opacity-0 scale-95 transition-all duration-300">

                <i class="fa-solid fa-circle-check text-lg"></i>
                <span class="font-medium"><?= session()->getFlashdata('success') ?></span>

            </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
            <div id="toast-error"
                class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 
                z-[9999] flex items-center gap-3 
                bg-red-100 border border-red-400 text-red-700 
                px-6 py-4 rounded-lg shadow-xl
                opacity-0 scale-95 transition-all duration-300">

                <i class="fa-solid fa-circle-exclamation text-lg"></i>
                <span class="font-medium"><?= session()->getFlashdata('error') ?></span>

            </div>
            <?php endif; ?>
            <?= $this->renderSection('content') ?>
        </main>

    </div>

</div>

<?= $this->renderSection('modals') ?>

<?= $this->renderSection('scripts') ?>

</body>
</html>