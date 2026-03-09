<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="admin-header">
    <h1>Listings</h1>
    <form method="GET" action="/admin/listings" style="display:flex; gap:8px;">
        <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= esc($search ?? '') ?>" style="width:200px;">
        <select name="filter" class="form-control" style="width:150px;" onchange="this.form.submit()">
            <option value="">All</option>
            <option value="pending" <?= ($filter ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="approved" <?= ($filter ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
            <option value="rejected" <?= ($filter ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>
        <button class="btn btn-primary">Search</button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead><tr><th>Image</th><th>Title</th><th>Price</th><th>Seller</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <?php $imgs = json_decode($p['image_paths'], true) ?: []; ?>
                <tr>
                    <td><img src="/<?= esc($imgs[0] ?? '') ?>" style="width:50px; height:50px; object-fit:cover; border-radius:6px;"></td>
                    <td style="font-weight:600;"><?= esc($p['title']) ?></td>
                    <td>₹<?= number_format($p['price_min']) ?></td>
                    <td><?= esc($p['seller']['full_name'] ?? 'N/A') ?></td>
                    <td>
                        <span class="badge <?= $p['approval_status'] === 'approved' ? 'badge-success' : ($p['approval_status'] === 'rejected' ? 'badge-danger' : 'badge-warning') ?>"><?= ucfirst($p['approval_status']) ?></span>
                    </td>
                    <td style="white-space:nowrap;">
                        <?php if ($p['approval_status'] === 'pending'): ?>
                            <form method="POST" action="/admin/listings/approve" style="display:inline;"><?= csrf_field() ?><input type="hidden" name="product_id" value="<?= $p['id'] ?>"><button class="btn btn-success btn-sm">Approve</button></form>
                            <form method="POST" action="/admin/listings/reject" style="display:inline;"><?= csrf_field() ?><input type="hidden" name="product_id" value="<?= $p['id'] ?>"><button class="btn btn-danger btn-sm">Reject</button></form>
                        <?php endif; ?>
                        <form method="POST" action="/admin/listings/delete" style="display:inline;" onsubmit="return confirm('Delete this listing?')"><?= csrf_field() ?><input type="hidden" name="product_id" value="<?= $p['id'] ?>"><button class="btn btn-danger btn-sm">Delete</button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
