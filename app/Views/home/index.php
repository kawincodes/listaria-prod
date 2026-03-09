<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php if (!empty($banners)): ?>
<div class="banner-slider" id="bannerSlider">
    <?php foreach ($banners as $banner): ?>
        <div class="banner-slide">
            <?php if (!empty($banner['link_url'])): ?>
                <a href="<?= esc($banner['link_url']) ?>">
            <?php endif; ?>
            <img src="/<?= esc($banner['image_path']) ?>" alt="<?= esc($banner['title'] ?? 'Banner') ?>" style="width:100%; height:auto; border-radius:12px;">
            <?php if (!empty($banner['link_url'])): ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="filter-section" style="padding: 1rem 2rem;">
    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <a href="/" class="filter-tag <?= empty($category) ? 'active' : '' ?>">All</a>
        <?php foreach ($categories as $cat): ?>
            <a href="/?category=<?= urlencode($cat) ?>" class="filter-tag <?= ($category ?? '') === $cat ? 'active' : '' ?>"><?= esc($cat) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="products-grid" style="padding: 0 2rem 2rem;">
    <?php if (empty($products)): ?>
        <div style="text-align:center; padding:3rem; color:#999; grid-column:1/-1;">
            <ion-icon name="search-outline" style="font-size:3rem;"></ion-icon>
            <p>No products found<?= $search ? ' for "' . esc($search) . '"' : '' ?>.</p>
        </div>
    <?php else: ?>
        <?php foreach ($products as $product): ?>
            <?php
            $images = json_decode($product['image_paths'], true) ?: [];
            $firstImage = !empty($images) ? '/' . $images[0] : 'https://via.placeholder.com/300x300';
            ?>
            <a href="/product/<?= $product['id'] ?>" class="product-card" style="text-decoration:none; color:inherit;">
                <div class="product-image">
                    <img src="<?= esc($firstImage) ?>" alt="<?= esc($product['title']) ?>" loading="lazy">
                    <?php if ($product['is_featured']): ?>
                        <span style="position:absolute; top:8px; left:8px; background:#6B21A8; color:white; padding:2px 8px; border-radius:4px; font-size:0.7rem; font-weight:600;">Featured</span>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <div class="product-brand"><?= esc($product['brand']) ?></div>
                    <div class="product-title"><?= esc($product['title']) ?></div>
                    <div class="product-price">₹<?= number_format($product['price_min']) ?><?= $product['price_max'] > $product['price_min'] ? ' - ₹' . number_format($product['price_max']) : '' ?></div>
                    <span class="condition-tag"><?= esc($product['condition_tag']) ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
    .banner-slider { padding:1rem 2rem; overflow:hidden; }
    .banner-slide { border-radius:12px; overflow:hidden; }
    .filter-tag { padding:6px 16px; border-radius:20px; background:#f3f4f6; color:#333; text-decoration:none; font-size:0.85rem; font-weight:500; transition:all 0.2s; white-space:nowrap; }
    .filter-tag.active, .filter-tag:hover { background:#6B21A8; color:white; }
    .products-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:1.5rem; }
    .product-card { border-radius:12px; overflow:hidden; background:white; box-shadow:0 2px 8px rgba(0,0,0,0.06); transition:transform 0.2s, box-shadow 0.2s; }
    .product-card:hover { transform:translateY(-4px); box-shadow:0 8px 25px rgba(0,0,0,0.1); }
    .product-image { position:relative; aspect-ratio:1; overflow:hidden; }
    .product-image img { width:100%; height:100%; object-fit:cover; }
    .product-info { padding:12px; }
    .product-brand { font-size:0.75rem; color:#999; text-transform:uppercase; letter-spacing:0.5px; }
    .product-title { font-weight:600; font-size:0.95rem; margin:4px 0; color:#333; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .product-price { font-weight:700; color:#6B21A8; font-size:1rem; }
    .condition-tag { display:inline-block; font-size:0.7rem; padding:2px 8px; border-radius:4px; background:#f0f0f0; color:#666; margin-top:6px; }
    @media (max-width:600px) { .products-grid { grid-template-columns:repeat(2, 1fr); gap:0.75rem; padding:0 1rem 1rem; } .banner-slider, .filter-section { padding:0.5rem 1rem; } }
</style>
<?= $this->endSection() ?>
