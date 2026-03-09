<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="max-width:700px; margin:2rem auto; padding:0 1.5rem;">
    <h1 style="font-size:1.5rem; font-weight:700; margin-bottom:2rem;">Order #<?= $order['id'] ?></h1>
    <div style="background:white; padding:1.5rem; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <?php if ($order['product']): ?>
            <?php $imgs = json_decode($order['product']['image_paths'], true) ?: []; ?>
            <div style="display:flex; gap:1rem; margin-bottom:1rem;">
                <img src="/<?= esc($imgs[0] ?? '') ?>" style="width:100px; height:100px; object-fit:cover; border-radius:8px;">
                <div>
                    <div style="font-weight:600; font-size:1.1rem;"><?= esc($order['product']['title']) ?></div>
                    <div style="color:#6B21A8; font-weight:700; font-size:1.2rem;">₹<?= number_format($order['amount']) ?></div>
                </div>
            </div>
        <?php endif; ?>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; border-top:1px solid #eee; padding-top:1rem;">
            <div><strong>Status:</strong> <?= esc($order['order_status'] ?? 'Processing') ?></div>
            <div><strong>Payment:</strong> <?= esc($order['payment_method']) ?></div>
            <div><strong>Date:</strong> <?= date('M d, Y H:i', strtotime($order['created_at'])) ?></div>
            <div><strong>Transaction ID:</strong> <?= esc($order['transaction_id'] ?? 'N/A') ?></div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
