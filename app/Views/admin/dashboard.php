<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="admin-header">
    <h1>Dashboard</h1>
    <span style="color:var(--text-muted); font-size:0.9rem;">Welcome back, <?= esc(session()->get('full_name')) ?></span>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-value"><?= $totalUsers ?></div><div class="stat-label">Total Users</div></div>
    <div class="stat-card"><div class="stat-value"><?= $totalProducts ?></div><div class="stat-label">Total Products</div></div>
    <div class="stat-card"><div class="stat-value"><?= $totalOrders ?></div><div class="stat-label">Total Orders</div></div>
    <div class="stat-card"><div class="stat-value">₹<?= number_format($totalRevenue) ?></div><div class="stat-label">Total Revenue</div></div>
    <div class="stat-card"><div class="stat-value" style="color:var(--warning);"><?= $pendingApprovals ?></div><div class="stat-label">Pending Approvals</div></div>
    <div class="stat-card"><div class="stat-value" style="color:var(--danger);"><?= $openTickets ?></div><div class="stat-label">Open Tickets</div></div>
</div>

<?php if (!empty($pendingProducts)): ?>
<div class="card">
    <h3 style="margin-bottom:1rem;">Pending Approvals</h3>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Product</th><th>Price</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($pendingProducts as $p): ?>
                <tr>
                    <td style="font-weight:600;"><?= esc($p['title']) ?></td>
                    <td>₹<?= number_format($p['price_min']) ?></td>
                    <td><?= date('M d', strtotime($p['created_at'])) ?></td>
                    <td>
                        <form method="POST" action="/admin/listings/approve" style="display:inline;"><?= csrf_field() ?><input type="hidden" name="product_id" value="<?= $p['id'] ?>"><button class="btn btn-success btn-sm">Approve</button></form>
                        <form method="POST" action="/admin/listings/reject" style="display:inline;"><?= csrf_field() ?><input type="hidden" name="product_id" value="<?= $p['id'] ?>"><button class="btn btn-danger btn-sm">Reject</button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
    <div class="card">
        <h3 style="margin-bottom:1rem;">Recent Orders</h3>
        <?php if (empty($recentOrders)): ?>
            <p style="color:var(--text-muted);">No orders yet.</p>
        <?php else: ?>
            <?php foreach ($recentOrders as $o): ?>
                <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border);">
                    <span style="font-weight:500;"><?= esc($o['product']['title'] ?? 'N/A') ?></span>
                    <span style="color:var(--primary); font-weight:600;">₹<?= number_format($o['amount']) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="card">
        <h3 style="margin-bottom:1rem;">Recent Users</h3>
        <?php foreach ($recentUsers as $u): ?>
            <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border);">
                <span style="font-weight:500;"><?= esc($u['full_name']) ?></span>
                <span style="color:var(--text-muted); font-size:0.85rem;"><?= date('M d', strtotime($u['created_at'])) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?= $this->endSection() ?>
