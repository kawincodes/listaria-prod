<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="admin-header">
    <h1>Banners</h1>
    <button onclick="document.getElementById('uploadForm').style.display='block'" class="btn btn-primary">Upload Banner</button>
</div>

<div id="uploadForm" class="card" style="display:none;">
    <h3 style="margin-bottom:1rem;">Upload New Banner</h3>
    <form method="POST" action="/admin/banners/upload" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:1rem;"><?= csrf_field() ?>
        <input type="file" name="image" accept="image/*" required class="form-control">
        <input type="text" name="title" class="form-control" placeholder="Banner Title">
        <input type="text" name="link_url" class="form-control" placeholder="Link URL">
        <input type="number" name="display_order" class="form-control" placeholder="Display Order" value="0">
        <button class="btn btn-primary">Upload</button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead><tr><th>Preview</th><th>Title</th><th>Order</th><th>Active</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($banners as $b): ?>
                <tr>
                    <td><img src="/<?= esc($b['image_path']) ?>" style="width:120px; height:60px; object-fit:cover; border-radius:4px;"></td>
                    <td><?= esc($b['title'] ?? 'Untitled') ?></td>
                    <td><?= $b['display_order'] ?></td>
                    <td><span class="badge <?= $b['is_active'] ? 'badge-success' : 'badge-danger' ?>"><?= $b['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                    <td>
                        <form method="POST" action="/admin/banners/delete" style="display:inline;" onsubmit="return confirm('Delete?')"><?= csrf_field() ?>
                            <input type="hidden" name="banner_id" value="<?= $b['id'] ?>">
                            <button class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
