<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="max-width:600px; margin:2rem auto; padding:0 1.5rem;">
    <h1 style="font-size:1.5rem; font-weight:700; margin-bottom:2rem;">Select Payment Method</h1>
    <form method="POST" action="/place-order" style="display:flex; flex-direction:column; gap:1rem;"><?= csrf_field() ?>
        <input type="hidden" name="amount" value="<?= esc($product['price_min']) ?>">
        <label style="display:flex; align-items:center; gap:12px; padding:1rem; background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); cursor:pointer;">
            <input type="radio" name="payment_method" value="COD" checked style="width:auto;">
            <div>
                <strong>Cash on Delivery</strong>
                <div style="font-size:0.85rem; color:#666;">Pay when you receive the item</div>
            </div>
        </label>
        <label style="display:flex; align-items:center; gap:12px; padding:1rem; background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); cursor:pointer;">
            <input type="radio" name="payment_method" value="UPI" style="width:auto;">
            <div>
                <strong>UPI Payment</strong>
                <div style="font-size:0.85rem; color:#666;">Pay via UPI apps</div>
            </div>
        </label>
        <div style="background:white; padding:1rem; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-top:1rem;">
            <strong>Order Summary</strong>
            <div style="display:flex; justify-content:space-between; margin-top:8px;">
                <span><?= esc($product['title']) ?></span>
                <span style="font-weight:700; color:#6B21A8;">₹<?= number_format($product['price_min']) ?></span>
            </div>
        </div>
        <button type="submit" class="btn-primary" style="padding:14px; border-radius:12px; margin-top:1rem;">Place Order</button>
    </form>
</div>
<?= $this->endSection() ?>
