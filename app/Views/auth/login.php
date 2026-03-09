<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="auth-container">
    <div class="auth-card">
        <h2>Welcome Back</h2>
        <p>Sign in to continue to Listaria.</p>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?= esc($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert success"><?= esc($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($verified)): ?>
            <div class="alert success" style="background-color:#e8f5e9; color:#2e7d32;">Email verified successfully! You can now login.</div>
        <?php endif; ?>

        <form method="POST" action="/login"><?= csrf_field() ?>
            <input type="hidden" name="redirect" value="<?= esc($redirect ?? '/') ?>">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="you@example.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn-primary" style="width:100%;">Sign In</button>
        </form>
        <p class="auth-footer">New to Listaria? <a href="/register">Create an account</a></p>
    </div>
</div>

<style>
    .auth-container { display:flex; justify-content:center; align-items:center; min-height:80vh; background-color:#f9f9f9; }
    .auth-card { background:white; padding:40px; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,0.05); width:100%; max-width:400px; text-align:center; }
    .auth-card h2 { margin-bottom:10px; color:#333; }
    .auth-card p { color:#666; margin-bottom:30px; }
    .auth-card .form-group { text-align:left; margin-bottom:20px; }
    .auth-card label { display:block; font-weight:500; margin-bottom:8px; color:#333; }
    .auth-card input { width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; font-size:16px; transition:border-color 0.3s; box-sizing:border-box; }
    .auth-card input:focus { border-color:#6B21A8; outline:none; }
    .alert { padding:12px; border-radius:8px; margin-bottom:20px; font-size:14px; }
    .alert.error { background-color:#ffebee; color:#c62828; }
    .alert.success { background-color:#e8f5e9; color:#2e7d32; }
    .auth-footer { margin-top:20px; font-size:14px; }
    .auth-footer a { color:#6B21A8; font-weight:600; text-decoration:none; }
</style>
<?= $this->endSection() ?>
