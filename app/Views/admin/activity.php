<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="admin-header"><h1>Activity Logs</h1></div>

<div class="card">
    <?php if (empty($logs)): ?>
        <p style="color:var(--text-muted);">No activity logs recorded.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Admin</th><th>Action</th><th>Details</th><th>IP Address</th><th>Date</th></tr></thead>
                <tbody>
                    <?php foreach ($logs as $l): ?>
                    <tr>
                        <td style="font-weight:600;"><?= esc($l['full_name'] ?? 'Unknown') ?></td>
                        <td><?= esc($l['action'] ?? '') ?></td>
                        <td style="max-width:250px; overflow:hidden; text-overflow:ellipsis;"><?= esc($l['details'] ?? '') ?></td>
                        <td style="font-family:monospace; font-size:0.8rem;"><?= esc($l['ip_address'] ?? '') ?></td>
                        <td><?= date('M d, Y H:i', strtotime($l['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
