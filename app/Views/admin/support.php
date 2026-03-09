<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="admin-header">
    <h1>Support Tickets</h1>
    <div style="display:flex; gap:8px;">
        <?php foreach (['all','open','replied','closed'] as $f): ?>
            <a href="/admin/support?filter=<?= $f ?>" class="btn <?= ($filter ?? 'all') === $f ? 'btn-primary' : '' ?>" style="<?= ($filter ?? 'all') !== $f ? 'background:#e5e5e5; color:#333;' : '' ?>"><?= ucfirst($f) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Message</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                <tr>
                    <td>#<?= $t['id'] ?></td>
                    <td style="font-weight:600;"><?= esc($t['name'] ?? ($t['user']['full_name'] ?? 'N/A')) ?></td>
                    <td><?= esc($t['email'] ?? '') ?></td>
                    <td style="max-width:250px; overflow:hidden; text-overflow:ellipsis;"><?= esc($t['message']) ?></td>
                    <td><span class="badge <?= $t['status'] === 'open' ? 'badge-warning' : ($t['status'] === 'replied' ? 'badge-info' : 'badge-success') ?>"><?= ucfirst($t['status']) ?></span></td>
                    <td><?= date('M d', strtotime($t['created_at'])) ?></td>
                    <td>
                        <form method="POST" action="/admin/support/reply" style="display:flex; flex-direction:column; gap:4px;"><?= csrf_field() ?>
                            <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                            <textarea name="admin_reply" class="form-control" rows="2" placeholder="Reply..." style="font-size:0.8rem;"><?= esc($t['admin_reply'] ?? '') ?></textarea>
                            <button class="btn btn-primary btn-sm">Reply</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
