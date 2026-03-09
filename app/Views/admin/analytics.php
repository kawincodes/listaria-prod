<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="admin-header"><h1>Analytics</h1></div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-value"><?= $totalUsers ?></div><div class="stat-label">Total Users</div></div>
    <div class="stat-card"><div class="stat-value"><?= $totalProducts ?></div><div class="stat-label">Total Products</div></div>
    <div class="stat-card"><div class="stat-value"><?= $totalOrders ?></div><div class="stat-label">Total Orders</div></div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
    <div class="card">
        <h3 style="margin-bottom:1rem;">Monthly Revenue</h3>
        <?php if (empty($monthlyRevenue)): ?>
            <p style="color:var(--text-muted);">No revenue data yet.</p>
        <?php else: ?>
            <?php foreach ($monthlyRevenue as $mr): ?>
                <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border);">
                    <span><?= $mr['month'] ?></span>
                    <span style="font-weight:700; color:var(--primary);">₹<?= number_format($mr['total']) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="card">
        <h3 style="margin-bottom:1rem;">Top Categories</h3>
        <?php foreach ($topCategories as $tc): ?>
            <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border);">
                <span><?= esc($tc['category']) ?></span>
                <span class="badge badge-info"><?= $tc['count'] ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?= $this->endSection() ?>
