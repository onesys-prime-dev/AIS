<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Authentication') ?></title>
    <link href="<?= base_url('css/main.css') ?>" rel="stylesheet">
</head>
<body class="h-screen flex">

    <?= $this->renderSection('content') ?>

</body>
</html>