<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="max-width:800px; margin:0 auto; padding:2rem;">
    <img src="/<?= esc($blog['image_path']) ?>" alt="" style="width:100%; height:400px; object-fit:cover; border-radius:16px; margin-bottom:2rem;">
    <span style="font-size:0.8rem; color:#6B21A8; text-transform:uppercase; font-weight:600;"><?= esc($blog['category']) ?></span>
    <h1 style="font-size:2rem; font-weight:700; margin:0.5rem 0 1rem;"><?= esc($blog['title']) ?></h1>
    <span style="font-size:0.85rem; color:#999;"><?= date('F d, Y', strtotime($blog['created_at'])) ?></span>
    <div style="margin-top:2rem; line-height:1.8; color:#444;"><?= $blog['content'] ?></div>
</div>
<?= $this->endSection() ?>
