<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="admin-header">
    <h1>Users</h1>
    <form method="GET" action="/admin/users" style="display:flex; gap:8px;">
        <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?= esc($search ?? '') ?>" style="width:250px;">
        <select name="filter" class="form-control" style="width:150px;" onchange="this.form.submit()">
            <option value="">All Users</option>
            <option value="admin" <?= ($filter ?? '') === 'admin' ? 'selected' : '' ?>>Admins</option>
            <option value="vendor" <?= ($filter ?? '') === 'vendor' ? 'selected' : '' ?>>Vendors</option>
            <option value="banned" <?= ($filter ?? '') === 'banned' ? 'selected' : '' ?>>Banned</option>
        </select>
        <button class="btn btn-primary">Search</button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Type</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td style="font-weight:600;"><?= esc($u['full_name']) ?></td>
                    <td><?= esc($u['email']) ?></td>
                    <td><span class="badge <?= $u['is_admin'] ? 'badge-danger' : ($u['account_type'] === 'vendor' ? 'badge-info' : 'badge-success') ?>"><?= $u['is_admin'] ? 'Admin' : ucfirst($u['account_type']) ?></span></td>
                    <td><span class="badge <?= ($u['status'] ?? 'active') === 'active' ? 'badge-success' : 'badge-danger' ?>"><?= ucfirst($u['status'] ?? 'active') ?></span></td>
                    <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if (!$u['is_admin']): ?>
                            <form method="POST" action="/admin/users/update" style="display:inline;"><?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="action" value="<?= ($u['status'] ?? 'active') === 'active' ? 'ban' : 'unban' ?>">
                                <button class="btn btn-sm <?= ($u['status'] ?? 'active') === 'active' ? 'btn-warning' : 'btn-success' ?>"><?= ($u['status'] ?? 'active') === 'active' ? 'Ban' : 'Unban' ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
