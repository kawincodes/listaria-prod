<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="max-width:700px; margin:2rem auto; padding:0 1.5rem;">
    <h1 style="font-size:1.8rem; font-weight:700; margin-bottom:2rem;">Profile Settings</h1>

    <?php if (session()->getFlashdata('success')): ?>
        <div style="background:#e8f5e9; color:#2e7d32; padding:12px; border-radius:8px; margin-bottom:1rem;"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <form method="POST" action="/profile/settings" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:1.5rem;"><?= csrf_field() ?>
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?= esc($user['full_name']) ?>" required>
        </div>
        <div class="form-group">
            <label>Email (cannot change)</label>
            <input type="email" value="<?= esc($user['email']) ?>" disabled style="background:#f5f5f5;">
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="<?= esc($user['phone'] ?? '') ?>" placeholder="+91 9876543210">
        </div>
        <div class="form-group">
            <label>WhatsApp Number</label>
            <input type="text" name="whatsapp_number" value="<?= esc($user['whatsapp_number'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Address</label>
            <textarea name="address" rows="3" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; font-size:16px; resize:vertical; box-sizing:border-box;"><?= esc($user['address'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Profile Image</label>
            <input type="file" name="profile_image" accept="image/*">
        </div>

        <?php if ($user['account_type'] === 'vendor'): ?>
            <hr style="border:none; border-top:1px solid #eee; margin:1rem 0;">
            <h3>Business Information</h3>
            <div class="form-group">
                <label>Business Name</label>
                <input type="text" name="business_name" value="<?= esc($user['business_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Business Bio</label>
                <textarea name="business_bio" rows="3" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; font-size:16px; resize:vertical; box-sizing:border-box;"><?= esc($user['business_bio'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>GST Number</label>
                <input type="text" name="gst_number" value="<?= esc($user['gst_number'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Business Logo</label>
                <input type="file" name="business_logo" accept="image/*">
            </div>
        <?php endif; ?>

        <button type="submit" class="btn-primary" style="padding:14px; border-radius:12px;">Save Changes</button>
    </form>
</div>

<style>
    .form-group { display:flex; flex-direction:column; }
    .form-group label { font-weight:600; margin-bottom:6px; color:#333; font-size:0.9rem; }
    .form-group input { padding:12px; border:1px solid #ddd; border-radius:8px; font-size:16px; box-sizing:border-box; }
    .form-group input:focus { border-color:#6B21A8; outline:none; }
</style>
<?= $this->endSection() ?>
