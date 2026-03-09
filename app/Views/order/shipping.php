<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="max-width:600px; margin:2rem auto; padding:0 1.5rem;">
    <h1 style="font-size:1.5rem; font-weight:700; margin-bottom:2rem;">Shipping Information</h1>
    <form method="POST" action="/shipping" style="display:flex; flex-direction:column; gap:1.5rem;"><?= csrf_field() ?>
        <div class="form-group"><label>Full Name</label><input type="text" name="name" required value="<?= esc(session()->get('full_name')) ?>"></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone" required placeholder="+91 9876543210"></div>
        <div class="form-group"><label>Address</label><textarea name="address" required rows="3" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;"></textarea></div>
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem;">
            <div class="form-group"><label>City</label><input type="text" name="city" required></div>
            <div class="form-group"><label>State</label><input type="text" name="state" required></div>
            <div class="form-group"><label>Pincode</label><input type="text" name="pincode" required></div>
        </div>
        <button type="submit" class="btn-primary" style="padding:14px; border-radius:12px;">Continue to Payment</button>
    </form>
</div>
<style>
    .form-group { display:flex; flex-direction:column; }
    .form-group label { font-weight:600; margin-bottom:6px; color:#333; font-size:0.9rem; }
    .form-group input { padding:12px; border:1px solid #ddd; border-radius:8px; font-size:16px; box-sizing:border-box; }
</style>
<?= $this->endSection() ?>
