<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="max-width:600px; margin:2rem auto; padding:0 1.5rem; text-align:center;">
    <h1 style="font-size:1.5rem; font-weight:700; margin-bottom:2rem;">Payment</h1>
    <p style="color:#666;">Processing payment for <?= esc($product['title']) ?>...</p>
    <div style="font-size:2rem; font-weight:700; color:#6B21A8; margin:2rem 0;">₹<?= number_format($product['price_min']) ?></div>
    <form method="POST" action="/place-order"><?= csrf_field() ?>
        <input type="hidden" name="payment_method" value="Online">
        <input type="hidden" name="amount" value="<?= $product['price_min'] ?>">
        <button type="submit" class="btn-primary" style="padding:14px 40px; border-radius:12px;">Complete Payment</button>
    </form>
</div>
<?= $this->endSection() ?>
