<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="max-width:900px; margin:2rem auto; padding:0 1.5rem;">
    <h1 style="font-size:1.8rem; font-weight:700; margin-bottom:2rem;">My Orders</h1>
    <?php if (empty($orders)): ?>
        <p style="color:#999; text-align:center; padding:3rem;">No orders yet.</p>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:1rem;">
            <?php foreach ($orders as $order): ?>
                <a href="/order-summary/<?= $order['id'] ?>" style="display:flex; gap:1rem; align-items:center; background:white; padding:1rem; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); text-decoration:none; color:inherit;">
                    <?php if ($order['product']): ?>
                        <?php $imgs = json_decode($order['product']['image_paths'], true) ?: []; ?>
                        <img src="/<?= esc($imgs[0] ?? '') ?>" style="width:80px; height:80px; object-fit:cover; border-radius:8px;">
                        <div style="flex:1;">
                            <div style="font-weight:600;"><?= esc($order['product']['title']) ?></div>
                            <div style="color:#6B21A8; font-weight:700;">₹<?= number_format($order['amount']) ?></div>
                            <div style="font-size:0.8rem; color:#666;"><?= date('M d, Y', strtotime($order['created_at'])) ?></div>
                        </div>
                        <span style="padding:4px 12px; border-radius:6px; background:#f3f4f6; font-size:0.85rem; font-weight:500;"><?= esc($order['order_status'] ?? 'Processing') ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
