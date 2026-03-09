<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$images = json_decode($product['image_paths'], true) ?: [];
$firstImage = !empty($images) ? '/' . $images[0] : 'https://via.placeholder.com/600x600';
?>

<div class="product-detail-container" style="max-width:1200px; margin:0 auto; padding:2rem;">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
        <div>
            <div class="main-image" style="border-radius:16px; overflow:hidden; aspect-ratio:1; background:#f5f5f5;">
                <img id="mainImage" src="<?= esc($firstImage) ?>" alt="<?= esc($product['title']) ?>" style="width:100%; height:100%; object-fit:cover;">
            </div>
            <?php if (count($images) > 1): ?>
                <div style="display:flex; gap:8px; margin-top:12px; overflow-x:auto;">
                    <?php foreach ($images as $img): ?>
                        <img src="/<?= esc($img) ?>" onclick="document.getElementById('mainImage').src=this.src" style="width:70px; height:70px; object-fit:cover; border-radius:8px; cursor:pointer; border:2px solid #eee;">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div>
            <span style="font-size:0.8rem; color:#999; text-transform:uppercase;"><?= esc($product['brand']) ?></span>
            <h1 style="font-size:1.8rem; font-weight:700; margin:8px 0;"><?= esc($product['title']) ?></h1>
            <div style="font-size:1.6rem; font-weight:700; color:#6B21A8; margin:12px 0;">
                ₹<?= number_format($product['price_min']) ?><?= $product['price_max'] > $product['price_min'] ? ' - ₹' . number_format($product['price_max']) : '' ?>
            </div>
            <span class="condition-tag" style="display:inline-block; padding:4px 12px; border-radius:6px; background:#f0f0f0; font-size:0.85rem; color:#555;"><?= esc($product['condition_tag']) ?></span>
            <p style="color:#666; margin:1.5rem 0; line-height:1.7;"><?= nl2br(esc($product['description'] ?? '')) ?></p>

            <div style="display:flex; gap:8px; margin:1rem 0; font-size:0.85rem; color:#888;">
                <span><ion-icon name="location-outline"></ion-icon> <?= esc($product['location']) ?></span>
                <span><ion-icon name="eye-outline"></ion-icon> <?= $product['views'] ?> views</span>
            </div>

            <?php if ($seller): ?>
                <div style="background:#f9f9f9; padding:1rem; border-radius:12px; margin:1rem 0;">
                    <strong>Seller:</strong> <?= esc($seller['full_name']) ?>
                    <?php if ($seller['is_verified_vendor']): ?>
                        <ion-icon name="checkmark-circle" style="color:#6B21A8;"></ion-icon>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (session()->get('user_id') && session()->get('user_id') != $product['user_id']): ?>
                <div style="display:flex; gap:12px; margin-top:1.5rem;">
                    <a href="/shipping?product_id=<?= $product['id'] ?>" class="btn-primary" style="flex:1; text-align:center; padding:14px; border-radius:12px; text-decoration:none; font-weight:600;">Buy Now</a>
                </div>
            <?php elseif (!session()->get('user_id')): ?>
                <a href="/login?redirect=<?= urlencode('/product/' . $product['id']) ?>" class="btn-primary" style="display:block; text-align:center; padding:14px; border-radius:12px; text-decoration:none; font-weight:600; margin-top:1.5rem;">Sign in to Buy</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($relatedProducts)): ?>
        <div style="margin-top:3rem;">
            <h2 style="font-size:1.3rem; margin-bottom:1rem;">Related Products</h2>
            <div class="products-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:1rem;">
                <?php foreach ($relatedProducts as $rp): ?>
                    <?php $rImages = json_decode($rp['image_paths'], true) ?: []; ?>
                    <a href="/product/<?= $rp['id'] ?>" class="product-card" style="text-decoration:none; color:inherit; border-radius:12px; overflow:hidden; background:white; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                        <div style="aspect-ratio:1; overflow:hidden;">
                            <img src="/<?= esc($rImages[0] ?? '') ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                        </div>
                        <div style="padding:10px;">
                            <div style="font-weight:600; font-size:0.9rem;"><?= esc($rp['title']) ?></div>
                            <div style="font-weight:700; color:#6B21A8;">₹<?= number_format($rp['price_min']) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    @media (max-width:768px) {
        .product-detail-container > div:first-child { grid-template-columns:1fr !important; }
    }
</style>
<?= $this->endSection() ?>
