<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Dashboard') ?> | Your Company</title>
    
    <link href="<?= base_url('css/main.css') ?>" rel="stylesheet">
    
    <?= $this->renderSection('styles') ?>
</head>

<body class="bg-gray-50 min-h-screen font-sans antialiased">

<div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <?= view('partials/sidebar') ?>

    <!-- Main area -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Header -->
        <?= view('partials/header') ?>

        <!-- Page Content -->
        <main class="flex-1 overflow-y-auto p-6">
            <?= $this->renderSection('content') ?>
        </main>

    </div>

</div>

<!-- 👇 IMPORTANT: MODALS RENDER HERE -->
<?= $this->renderSection('modals') ?>

<?= $this->renderSection('scripts') ?>

</body>
</html>