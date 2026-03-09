<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="max-width:800px; margin:0 auto; padding:2rem;">
    <h1 style="font-size:2rem; font-weight:700; margin-bottom:2rem;">About Us</h1>
    <div style="line-height:1.8; color:#444;"><?= $content ?: '<p>Welcome to Listaria - India\'s premier luxury recommerce platform.</p>' ?></div>
</div>
<?= $this->endSection() ?>
