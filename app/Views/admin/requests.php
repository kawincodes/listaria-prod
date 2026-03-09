<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="admin-header"><h1>Product Requests</h1></div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead><tr><th>Title</th><th>User</th><th>Description</th><th>Budget</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td style="font-weight:600;"><?= esc($r['title']) ?></td>
                    <td><?= esc($r['user']['full_name'] ?? 'N/A') ?></td>
                    <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis;"><?= esc($r['description']) ?></td>
                    <td><?= $r['budget'] ? '₹' . number_format($r['budget']) : 'N/A' ?></td>
                    <td><span class="badge <?= $r['status'] === 'open' ? 'badge-success' : ($r['status'] === 'closed' ? 'badge-danger' : 'badge-info') ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                    <td>
                        <form method="POST" action="/admin/requests/update" style="display:flex; gap:4px;"><?= csrf_field() ?>
                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                            <select name="status" class="form-control" style="width:100px; font-size:0.8rem;">
                                <option value="open" <?= $r['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                <option value="fulfilled" <?= $r['status'] === 'fulfilled' ? 'selected' : '' ?>>Fulfilled</option>
                                <option value="closed" <?= $r['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                            <button class="btn btn-primary btn-sm">Save</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
