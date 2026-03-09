<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="admin-header"><h1>Site Settings</h1></div>

<div class="card">
    <form method="POST" action="/admin/settings/update" style="display:flex; flex-direction:column; gap:1.5rem;"><?= csrf_field() ?>
        <div>
            <label style="font-weight:600; display:block; margin-bottom:6px;">Site Name</label>
            <input type="text" name="site_name" class="form-control" value="<?= esc($settings['site_name'] ?? 'Listaria') ?>">
        </div>
        <div>
            <label style="font-weight:600; display:block; margin-bottom:6px;">Contact Email</label>
            <input type="email" name="contact_email" class="form-control" value="<?= esc($settings['contact_email'] ?? '') ?>">
        </div>
        <div>
            <label style="font-weight:600; display:block; margin-bottom:6px;">Contact Phone</label>
            <input type="text" name="contact_phone" class="form-control" value="<?= esc($settings['contact_phone'] ?? '') ?>">
        </div>
        <div>
            <label style="font-weight:600; display:block; margin-bottom:6px;">Commission Rate (%)</label>
            <input type="number" name="commission_rate" class="form-control" value="<?= esc($settings['commission_rate'] ?? '10') ?>" step="0.1">
        </div>

        <h3 style="margin-top:1rem;">SMTP Settings</h3>
        <div>
            <label style="font-weight:600; display:block; margin-bottom:6px;">SMTP Host</label>
            <input type="text" name="smtp_host" class="form-control" value="<?= esc($settings['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
        </div>
        <div>
            <label style="font-weight:600; display:block; margin-bottom:6px;">SMTP Port</label>
            <input type="number" name="smtp_port" class="form-control" value="<?= esc($settings['smtp_port'] ?? '465') ?>">
        </div>
        <div>
            <label style="font-weight:600; display:block; margin-bottom:6px;">SMTP User</label>
            <input type="text" name="smtp_user" class="form-control" value="<?= esc($settings['smtp_user'] ?? '') ?>">
        </div>
        <div>
            <label style="font-weight:600; display:block; margin-bottom:6px;">SMTP Password</label>
            <input type="password" name="smtp_pass" class="form-control" value="<?= esc($settings['smtp_pass'] ?? '') ?>">
        </div>

        <button class="btn btn-primary" style="align-self:flex-start;">Save Settings</button>
    </form>
</div>
<?= $this->endSection() ?>
