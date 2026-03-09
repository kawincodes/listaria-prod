<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="admin-header"><h1>Pages</h1></div>

<div class="card">
    <h3 style="margin-bottom:1rem;">Built-in Pages</h3>
    <?php
    $builtInPages = [
        ['key' => 'about_content', 'name' => 'About Us'],
        ['key' => 'terms_of_service', 'name' => 'Terms of Service'],
        ['key' => 'privacy_policy', 'name' => 'Privacy Policy'],
    ];
    ?>
    <?php foreach ($builtInPages as $bp): ?>
        <form method="POST" action="/admin/pages/save" style="margin-bottom:1.5rem; border-bottom:1px solid var(--border); padding-bottom:1.5rem;"><?= csrf_field() ?>
            <label style="font-weight:600; display:block; margin-bottom:8px;"><?= $bp['name'] ?></label>
            <input type="hidden" name="setting_key" value="<?= $bp['key'] ?>">
            <textarea name="setting_value" class="form-control" rows="6"><?= esc($settings[$bp['key']] ?? '') ?></textarea>
            <button class="btn btn-primary" style="margin-top:8px;">Save</button>
        </form>
    <?php endforeach; ?>
</div>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h3>Custom Pages</h3>
        <button onclick="document.getElementById('createPageForm').style.display='block'" class="btn btn-primary">New Page</button>
    </div>

    <div id="createPageForm" style="display:none; margin-bottom:1.5rem; border:1px solid var(--border); padding:1rem; border-radius:8px;">
        <form method="POST" action="/admin/pages/create" style="display:flex; flex-direction:column; gap:1rem;"><?= csrf_field() ?>
            <input type="text" name="title" class="form-control" placeholder="Page Title" required>
            <input type="text" name="meta_description" class="form-control" placeholder="Meta Description">
            <textarea name="content" class="form-control" rows="6" placeholder="Page content (HTML supported)..."></textarea>
            <label><input type="checkbox" name="is_published" value="1" checked> Published</label>
            <button class="btn btn-primary">Create Page</button>
        </form>
    </div>

    <?php if (empty($customPages)): ?>
        <p style="color:var(--text-muted);">No custom pages yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Title</th><th>Slug</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($customPages as $cp): ?>
                    <tr>
                        <td style="font-weight:600;"><?= esc($cp['title']) ?></td>
                        <td><a href="/page/<?= esc($cp['slug']) ?>" target="_blank">/page/<?= esc($cp['slug']) ?></a></td>
                        <td><span class="badge <?= $cp['is_published'] ? 'badge-success' : 'badge-warning' ?>"><?= $cp['is_published'] ? 'Published' : 'Draft' ?></span></td>
                        <td><?= date('M d, Y', strtotime($cp['updated_at'])) ?></td>
                        <td>
                            <form method="POST" action="/admin/pages/delete" style="display:inline;" onsubmit="return confirm('Delete this page?')"><?= csrf_field() ?>
                                <input type="hidden" name="page_id" value="<?= $cp['id'] ?>">
                                <button class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
