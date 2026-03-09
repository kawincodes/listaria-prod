<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="admin-header">
    <h1>Email Templates</h1>
    <button onclick="document.getElementById('createTemplateForm').style.display='block'" class="btn btn-primary">New Template</button>
</div>

<div id="createTemplateForm" class="card" style="display:none;">
    <h3 style="margin-bottom:1rem;">Create Email Template</h3>
    <form method="POST" action="/admin/email-templates/create" style="display:flex; flex-direction:column; gap:1rem;"><?= csrf_field() ?>
        <input type="text" name="template_key" class="form-control" placeholder="Template Key (e.g., welcome_email)" required>
        <input type="text" name="name" class="form-control" placeholder="Display Name" required>
        <input type="text" name="subject" class="form-control" placeholder="Email Subject" required>
        <textarea name="body" class="form-control" rows="8" placeholder="Email body (HTML). Use {{variable_name}} for placeholders." required></textarea>
        <input type="text" name="variables" class="form-control" placeholder="Available variables (comma-separated)">
        <button class="btn btn-primary">Create Template</button>
    </form>
</div>

<div class="card">
    <?php if (empty($templates)): ?>
        <p style="color:var(--text-muted);">No email templates yet.</p>
    <?php else: ?>
        <?php foreach ($templates as $t): ?>
            <div style="border:1px solid var(--border); border-radius:8px; padding:1rem; margin-bottom:1rem;">
                <form method="POST" action="/admin/email-templates/update" style="display:flex; flex-direction:column; gap:0.75rem;"><?= csrf_field() ?>
                    <input type="hidden" name="template_id" value="<?= $t['id'] ?>">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong><?= esc($t['name']) ?></strong>
                            <span style="font-family:monospace; font-size:0.8rem; color:var(--text-muted); margin-left:8px;"><?= esc($t['template_key']) ?></span>
                        </div>
                        <div style="display:flex; gap:4px;">
                            <span class="badge <?= $t['is_active'] ? 'badge-success' : 'badge-danger' ?>"><?= $t['is_active'] ? 'Active' : 'Inactive' ?></span>
                            <form method="POST" action="/admin/email-templates/toggle" style="display:inline;"><?= csrf_field() ?>
                                <input type="hidden" name="template_id" value="<?= $t['id'] ?>">
                                <button class="btn btn-sm" style="background:var(--border);"><?= $t['is_active'] ? 'Disable' : 'Enable' ?></button>
                            </form>
                        </div>
                    </div>
                    <input type="text" name="name" class="form-control" value="<?= esc($t['name']) ?>">
                    <input type="text" name="subject" class="form-control" value="<?= esc($t['subject']) ?>">
                    <textarea name="body" class="form-control" rows="5"><?= esc($t['body']) ?></textarea>
                    <input type="text" name="variables" class="form-control" value="<?= esc($t['variables'] ?? '') ?>" placeholder="Variables">
                    <button class="btn btn-primary btn-sm" style="align-self:flex-start;">Save Changes</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
