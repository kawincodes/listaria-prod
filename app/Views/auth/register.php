<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="auth-container">
    <div class="auth-card">
        <h2>Join Listaria</h2>
        <p>Create an account to start selling and buying luxury.</p>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?= esc($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert success"><?= esc($success) ?></div>
        <?php else: ?>

        <form method="POST" action="/register"><?= csrf_field() ?>
            <input type="hidden" name="redirect" value="<?= esc($redirect ?? '/') ?>">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required placeholder="John Doe">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="you@example.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required placeholder="••••••••">
            </div>
            <div class="form-group">
                <label>Account Type</label>
                <div style="display:flex; gap:20px; margin-top:10px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:normal;">
                        <input type="radio" name="account_type" value="customer" checked style="width:auto;"> Customer
                    </label>
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:normal;">
                        <input type="radio" name="account_type" value="vendor" style="width:auto;"> Vendor
                    </label>
                </div>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;">Create Account</button>
        </form>
        <?php endif; ?>
        <p class="auth-footer">Already have an account? <a href="/login">Sign in</a></p>
    </div>
</div>

<style>
    .auth-container { display:flex; justify-content:center; align-items:center; min-height:80vh; background-color:#f9f9f9; }
    .auth-card { background:white; padding:40px; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,0.05); width:100%; max-width:400px; text-align:center; }
    .auth-card h2 { margin-bottom:10px; color:#333; }
    .auth-card p { color:#666; margin-bottom:30px; }
    .auth-card .form-group { text-align:left; margin-bottom:20px; }
    .auth-card label { display:block; font-weight:500; margin-bottom:8px; color:#333; }
    .auth-card input { width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; font-size:16px; box-sizing:border-box; }
    .auth-card input:focus { border-color:#6B21A8; outline:none; }
    .alert { padding:12px; border-radius:8px; margin-bottom:20px; font-size:14px; }
    .alert.error { background-color:#ffebee; color:#c62828; }
    .alert.success { background-color:#e8f5e9; color:#2e7d32; }
    .auth-footer { margin-top:20px; font-size:14px; }
    .auth-footer a { color:#6B21A8; font-weight:600; text-decoration:none; }
</style>
<?= $this->endSection() ?>
