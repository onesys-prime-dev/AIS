<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Authentication') ?></title>
    <link href="<?= base_url('css/main.css') ?>" rel="stylesheet">
    <link href="<?= base_url('css/auth.css') ?>?v=<?= is_file(FCPATH . 'css/auth.css') ? filemtime(FCPATH . 'css/auth.css') : time() ?>" rel="stylesheet">
</head>
<body class="auth-body">

    <?= $this->renderSection('content') ?>

</body>
</html>
