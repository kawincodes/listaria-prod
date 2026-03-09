<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="admin-header"><h1>Returns</h1></div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead><tr><th>ID</th><th>Product</th><th>User</th><th>Reason</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($returns as $r): ?>
                <tr>
                    <td>#<?= $r['id'] ?></td>
                    <td style="font-weight:600;"><?= esc($r['product']['title'] ?? 'N/A') ?></td>
                    <td><?= esc($r['user']['full_name'] ?? 'N/A') ?></td>
                    <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis;"><?= esc($r['reason']) ?></td>
                    <td><span class="badge <?= $r['status'] === 'approved' ? 'badge-success' : ($r['status'] === 'rejected' ? 'badge-danger' : 'badge-warning') ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                    <td>
                        <form method="POST" action="/admin/returns/update" style="display:flex; flex-direction:column; gap:4px;"><?= csrf_field() ?>
                            <input type="hidden" name="return_id" value="<?= $r['id'] ?>">
                            <select name="status" class="form-control" style="font-size:0.8rem;">
                                <?php foreach (['pending','approved','rejected','completed'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="admin_comments" class="form-control" placeholder="Comments" value="<?= esc($r['admin_comments'] ?? '') ?>" style="font-size:0.8rem;">
                            <button class="btn btn-primary btn-sm">Update</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
