<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="max-width:1200px; margin:0 auto; padding:2rem;">
    <h1 style="font-size:1.8rem; font-weight:700; margin-bottom:2rem;">Stores</h1>
    <?php if (empty($stores)): ?>
        <p style="color:#999; text-align:center; padding:3rem;">No stores available yet.</p>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:1.5rem;">
            <?php foreach ($stores as $store): ?>
                <div style="background:white; border-radius:12px; padding:1.5rem; box-shadow:0 2px 8px rgba(0,0,0,0.06); text-align:center;">
                    <?php if ($store['business_logo']): ?>
                        <img src="/<?= esc($store['business_logo']) ?>" style="width:80px; height:80px; border-radius:50%; object-fit:cover; margin-bottom:1rem;">
                    <?php else: ?>
                        <div style="width:80px; height:80px; border-radius:50%; background:#f3f4f6; margin:0 auto 1rem; display:flex; align-items:center; justify-content:center;">
                            <ion-icon name="storefront-outline" style="font-size:2rem; color:#999;"></ion-icon>
                        </div>
                    <?php endif; ?>
                    <h3 style="font-weight:600; margin-bottom:4px;"><?= esc($store['business_name'] ?: $store['full_name']) ?></h3>
                    <p style="color:#666; font-size:0.9rem;"><?= esc($store['business_bio'] ?? '') ?></p>
                    <?php if ($store['is_verified_vendor']): ?>
                        <span style="color:#6B21A8; font-size:0.8rem; font-weight:600;"><ion-icon name="checkmark-circle"></ion-icon> Verified</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
