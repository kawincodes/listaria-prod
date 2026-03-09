<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? esc($pageTitle) . ' - Listaria' : 'Listaria - Luxury Recommerce' ?></title>
    <?php if (!empty($metaDesc)): ?><meta name="description" content="<?= esc($metaDesc) ?>"><?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css?v=2.0.0">
    <link rel="stylesheet" href="/assets/css/responsive.css?v=2.0.0">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</head>
<body>

<?= $this->include('partials/header') ?>

<main>
    <?= $this->renderSection('content') ?>
</main>

<?= $this->include('partials/footer') ?>

<script src="/assets/js/script.js"></script>
</body>
</html>
