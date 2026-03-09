<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="max-width:1200px; margin:0 auto; padding:2rem;">
    <h1 style="font-size:1.8rem; font-weight:700; margin-bottom:2rem;">My Wishlist</h1>
    <?php if (empty($products)): ?>
        <div style="text-align:center; padding:3rem; color:#999;">
            <ion-icon name="heart-outline" style="font-size:3rem;"></ion-icon>
            <p>Your wishlist is empty.</p>
            <a href="/" style="color:#6B21A8; text-decoration:none; font-weight:600;">Browse Products</a>
        </div>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:1.5rem;">
            <?php foreach ($products as $product): ?>
                <?php $imgs = json_decode($product['image_paths'], true) ?: []; ?>
                <a href="/product/<?= $product['id'] ?>" style="text-decoration:none; color:inherit; border-radius:12px; overflow:hidden; background:white; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <div style="aspect-ratio:1; overflow:hidden;">
                        <img src="/<?= esc($imgs[0] ?? '') ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                    <div style="padding:12px;">
                        <div style="font-size:0.75rem; color:#999; text-transform:uppercase;"><?= esc($product['brand']) ?></div>
                        <div style="font-weight:600; font-size:0.95rem; margin:4px 0;"><?= esc($product['title']) ?></div>
                        <div style="font-weight:700; color:#6B21A8;">₹<?= number_format($product['price_min']) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
