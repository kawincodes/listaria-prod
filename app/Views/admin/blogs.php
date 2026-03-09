<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="admin-header">
    <h1>Blogs</h1>
    <button onclick="document.getElementById('createBlogForm').style.display='block'" class="btn btn-primary">New Blog Post</button>
</div>

<div id="createBlogForm" class="card" style="display:none;">
    <h3 style="margin-bottom:1rem;">Create Blog Post</h3>
    <form method="POST" action="/admin/blogs/create" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:1rem;"><?= csrf_field() ?>
        <input type="text" name="title" class="form-control" placeholder="Blog Title" required>
        <input type="text" name="category" class="form-control" placeholder="Category">
        <textarea name="content" class="form-control" rows="6" placeholder="Blog content (HTML supported)..." required></textarea>
        <input type="file" name="image" accept="image/*" class="form-control">
        <button class="btn btn-primary">Create</button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead><tr><th>Image</th><th>Title</th><th>Category</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($blogs as $b): ?>
                <tr>
                    <td><img src="/<?= esc($b['image_path']) ?>" style="width:60px; height:40px; object-fit:cover; border-radius:4px;"></td>
                    <td style="font-weight:600;"><?= esc($b['title']) ?></td>
                    <td><span class="badge badge-info"><?= esc($b['category']) ?></span></td>
                    <td><?= date('M d, Y', strtotime($b['created_at'])) ?></td>
                    <td>
                        <form method="POST" action="/admin/blogs/delete" style="display:inline;" onsubmit="return confirm('Delete?')"><?= csrf_field() ?>
                            <input type="hidden" name="blog_id" value="<?= $b['id'] ?>">
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
