<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="admin-header">
    <h1>Roles & Permissions</h1>
    <button onclick="document.getElementById('createRoleForm').style.display='block'" class="btn btn-primary">Create Role</button>
</div>

<div id="createRoleForm" class="card" style="display:none;">
    <form method="POST" action="/admin/roles/create" style="display:flex; flex-direction:column; gap:1rem;"><?= csrf_field() ?>
        <input type="text" name="name" class="form-control" placeholder="Role Name" required>
        <div>
            <label style="font-weight:600;">Permissions:</label>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; margin-top:8px;">
                <?php foreach (['manage_users','manage_listings','manage_orders','manage_support','manage_content','manage_settings'] as $perm): ?>
                    <label><input type="checkbox" name="permissions[]" value="<?= $perm ?>"> <?= ucwords(str_replace('_',' ',$perm)) ?></label>
                <?php endforeach; ?>
            </div>
        </div>
        <button class="btn btn-primary">Create Role</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom:1rem;">Existing Roles</h3>
    <?php if (empty($roles)): ?>
        <p style="color:var(--text-muted);">No custom roles defined.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Name</th><th>Permissions</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                    <tr>
                        <td style="font-weight:600;"><?= esc($role['name']) ?></td>
                        <td><?php $perms = json_decode($role['permissions'] ?? '[]', true); echo implode(', ', array_map(function($p) { return ucwords(str_replace('_',' ',$p)); }, $perms ?: [])); ?></td>
                        <td>
                            <form method="POST" action="/admin/roles/delete" style="display:inline;" onsubmit="return confirm('Delete?')"><?= csrf_field() ?>
                                <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
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

<div class="card">
    <h3 style="margin-bottom:1rem;">Assign Role to Admin</h3>
    <form method="POST" action="/admin/roles/assign" style="display:flex; gap:8px; align-items:end;"><?= csrf_field() ?>
        <div style="flex:1;">
            <label style="font-weight:600; display:block; margin-bottom:4px;">Admin</label>
            <select name="user_id" class="form-control">
                <?php foreach ($admins as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= esc($a['full_name']) ?> (<?= esc($a['email']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="flex:1;">
            <label style="font-weight:600; display:block; margin-bottom:4px;">Role</label>
            <select name="role" class="form-control">
                <option value="admin">Admin</option>
                <option value="super_admin">Super Admin</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= esc($r['name']) ?>"><?= esc($r['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary">Assign</button>
    </form>
</div>
<?= $this->endSection() ?>
