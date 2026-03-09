<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="max-width:900px; margin:0 auto; padding:2rem;">
    <h1 style="font-size:2rem; font-weight:700; text-align:center; margin-bottom:2rem;">Our Founders</h1>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
        <div style="background:white; border-radius:16px; padding:2rem; box-shadow:0 2px 8px rgba(0,0,0,0.06); text-align:center;">
            <?php if ($founder_1_image): ?>
                <img src="/<?= esc($founder_1_image) ?>" style="width:150px; height:150px; border-radius:50%; object-fit:cover; margin-bottom:1rem;">
            <?php endif; ?>
            <p style="color:#555; line-height:1.7;"><?= $founder_1_note ?: 'Founder 1' ?></p>
        </div>
        <div style="background:white; border-radius:16px; padding:2rem; box-shadow:0 2px 8px rgba(0,0,0,0.06); text-align:center;">
            <?php if ($founder_2_image): ?>
                <img src="/<?= esc($founder_2_image) ?>" style="width:150px; height:150px; border-radius:50%; object-fit:cover; margin-bottom:1rem;">
            <?php endif; ?>
            <p style="color:#555; line-height:1.7;"><?= $founder_2_note ?: 'Founder 2' ?></p>
        </div>
    </div>
</div>
<style>
    @media (max-width:600px) { .founders-grid { grid-template-columns:1fr !important; } }
</style>
<?= $this->endSection() ?>
