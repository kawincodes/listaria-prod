<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="max-width:1200px; margin:0 auto; padding:2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
        <div>
            <h1 style="font-size:1.8rem; font-weight:700;">My Dashboard</h1>
            <p style="color:#666;">Welcome back, <?= esc($user['full_name']) ?></p>
        </div>
        <a href="/profile/settings" style="background:#6B21A8; color:white; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:600;">Edit Profile</a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div style="background:#e8f5e9; color:#2e7d32; padding:12px; border-radius:8px; margin-bottom:1rem;"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:2rem;">
        <div style="background:white; padding:1.5rem; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); text-align:center;">
            <div style="font-size:2rem; font-weight:700; color:#6B21A8;"><?= count($listings) ?></div>
            <div style="color:#666; font-size:0.9rem;">My Listings</div>
        </div>
        <div style="background:white; padding:1.5rem; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); text-align:center;">
            <div style="font-size:2rem; font-weight:700; color:#6B21A8;"><?= count($orders) ?></div>
            <div style="color:#666; font-size:0.9rem;">My Orders</div>
        </div>
        <div style="background:white; padding:1.5rem; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); text-align:center;">
            <div style="font-size:2rem; font-weight:700; color:#6B21A8;"><?= count($sellerNegotiations) ?></div>
            <div style="color:#666; font-size:0.9rem;">Incoming Chats</div>
        </div>
    </div>

    <div style="margin-bottom:2rem;">
        <h2 style="font-size:1.3rem; margin-bottom:1rem;">My Listings</h2>
        <?php if (empty($listings)): ?>
            <p style="color:#999;">No listings yet. <a href="/sell" style="color:#6B21A8;">Sell your first item</a></p>
        <?php else: ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:1rem;">
                <?php foreach ($listings as $listing): ?>
                    <?php $imgs = json_decode($listing['image_paths'], true) ?: []; ?>
                    <div style="background:white; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                        <div style="aspect-ratio:1; overflow:hidden;">
                            <img src="/<?= esc($imgs[0] ?? '') ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                        </div>
                        <div style="padding:10px;">
                            <div style="font-weight:600; font-size:0.9rem;"><?= esc($listing['title']) ?></div>
                            <div style="color:#6B21A8; font-weight:700;">₹<?= number_format($listing['price_min']) ?></div>
                            <span style="font-size:0.75rem; padding:2px 8px; border-radius:4px; background:<?= $listing['approval_status'] === 'approved' ? '#e8f5e9' : ($listing['approval_status'] === 'rejected' ? '#ffebee' : '#fff3e0') ?>; color:<?= $listing['approval_status'] === 'approved' ? '#2e7d32' : ($listing['approval_status'] === 'rejected' ? '#c62828' : '#e65100') ?>;"><?= ucfirst($listing['approval_status']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <h2 style="font-size:1.3rem; margin-bottom:1rem;">My Orders</h2>
        <?php if (empty($orders)): ?>
            <p style="color:#999;">No orders yet.</p>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:1rem;">
                <?php foreach ($orders as $order): ?>
                    <a href="/order-summary/<?= $order['id'] ?>" style="display:flex; gap:1rem; align-items:center; background:white; padding:1rem; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); text-decoration:none; color:inherit;">
                        <?php if ($order['product']): ?>
                            <?php $oImgs = json_decode($order['product']['image_paths'], true) ?: []; ?>
                            <img src="/<?= esc($oImgs[0] ?? '') ?>" style="width:60px; height:60px; object-fit:cover; border-radius:8px;">
                            <div>
                                <div style="font-weight:600;"><?= esc($order['product']['title']) ?></div>
                                <div style="color:#6B21A8; font-weight:700;">₹<?= number_format($order['amount']) ?></div>
                                <span style="font-size:0.8rem; color:#666;"><?= esc($order['order_status'] ?? 'Processing') ?></span>
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
