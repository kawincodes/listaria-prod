<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="admin-header"><h1>Security</h1></div>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h3>Blacklist</h3>
        <button onclick="document.getElementById('blacklistForm').style.display='block'" class="btn btn-primary">Add Entry</button>
    </div>

    <div id="blacklistForm" style="display:none; margin-bottom:1rem; border:1px solid var(--border); padding:1rem; border-radius:8px;">
        <form method="POST" action="/admin/security/blacklist" style="display:flex; gap:8px;"><?= csrf_field() ?>
            <select name="type" class="form-control" style="width:120px;">
                <option value="email">Email</option>
                <option value="ip">IP Address</option>
            </select>
            <input type="text" name="value" class="form-control" placeholder="Email or IP" required>
            <input type="text" name="reason" class="form-control" placeholder="Reason">
            <button class="btn btn-primary">Add</button>
        </form>
    </div>

    <?php if (empty($blacklist)): ?>
        <p style="color:var(--text-muted);">No blacklisted entries.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Type</th><th>Value</th><th>Reason</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($blacklist as $bl): ?>
                    <tr>
                        <td><span class="badge badge-danger"><?= ucfirst($bl['type']) ?></span></td>
                        <td style="font-family:monospace;"><?= esc($bl['value']) ?></td>
                        <td><?= esc($bl['reason'] ?? '') ?></td>
                        <td><?= date('M d, Y', strtotime($bl['created_at'])) ?></td>
                        <td>
                            <form method="POST" action="/admin/security/remove-blacklist"><?= csrf_field() ?>
                                <input type="hidden" name="blacklist_id" value="<?= $bl['id'] ?>">
                                <button class="btn btn-danger btn-sm">Remove</button>
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
    <h3 style="margin-bottom:1rem;">Active Admin Sessions</h3>
    <?php if (empty($sessions)): ?>
        <p style="color:var(--text-muted);">No active sessions.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Admin</th><th>IP</th><th>Last Activity</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($sessions as $s): ?>
                    <tr>
                        <td><?= esc($s['full_name'] ?? 'Unknown') ?></td>
                        <td style="font-family:monospace;"><?= esc($s['ip_address']) ?></td>
                        <td><?= $s['last_activity'] ? date('M d H:i', strtotime($s['last_activity'])) : 'N/A' ?></td>
                        <td>
                            <form method="POST" action="/admin/security/terminate-session"><?= csrf_field() ?>
                                <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                                <button class="btn btn-danger btn-sm">Terminate</button>
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
