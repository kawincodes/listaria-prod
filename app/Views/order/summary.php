<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="max-width:600px; margin:2rem auto; padding:0 1.5rem; text-align:center;">
    <div style="font-size:3rem; color:#2e7d32; margin-bottom:1rem;"><ion-icon name="checkmark-circle"></ion-icon></div>
    <h1 style="font-size:1.8rem; font-weight:700; margin-bottom:0.5rem;">Order Placed!</h1>
    <p style="color:#666; margin-bottom:2rem;">Order #<?= $order['id'] ?> has been placed successfully.</p>

    <div style="background:white; padding:1.5rem; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); text-align:left;">
        <?php if ($order['product']): ?>
            <?php $imgs = json_decode($order['product']['image_paths'], true) ?: []; ?>
            <div style="display:flex; gap:1rem; margin-bottom:1rem;">
                <img src="/<?= esc($imgs[0] ?? '') ?>" style="width:80px; height:80px; object-fit:cover; border-radius:8px;">
                <div>
                    <div style="font-weight:600;"><?= esc($order['product']['title']) ?></div>
                    <div style="color:#6B21A8; font-weight:700; font-size:1.2rem;">₹<?= number_format($order['amount']) ?></div>
                </div>
            </div>
        <?php endif; ?>
        <div style="border-top:1px solid #eee; padding-top:1rem; display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:0.9rem;">
            <div><strong>Payment:</strong> <?= esc($order['payment_method']) ?></div>
            <div><strong>Status:</strong> <?= esc($order['order_status']) ?></div>
            <div><strong>Date:</strong> <?= date('M d, Y', strtotime($order['created_at'])) ?></div>
        </div>
    </div>

    <a href="/" style="display:inline-block; margin-top:2rem; color:#6B21A8; text-decoration:none; font-weight:600;">Continue Shopping</a>
</div>
<?= $this->endSection() ?>
