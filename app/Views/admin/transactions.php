<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="admin-header"><h1>Transactions</h1></div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead><tr><th>Order ID</th><th>Product</th><th>Buyer</th><th>Amount</th><th>Payment</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td>#<?= $o['id'] ?></td>
                    <td style="font-weight:600;"><?= esc($o['product']['title'] ?? 'N/A') ?></td>
                    <td><?= esc($o['user']['full_name'] ?? 'N/A') ?></td>
                    <td style="font-weight:600; color:var(--primary);">₹<?= number_format($o['amount']) ?></td>
                    <td><?= esc($o['payment_method']) ?></td>
                    <td><span class="badge badge-info"><?= esc($o['order_status'] ?? 'Processing') ?></span></td>
                    <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                    <td>
                        <form method="POST" action="/admin/transactions/update" style="display:flex; gap:4px;"><?= csrf_field() ?>
                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                            <select name="order_status" class="form-control" style="width:130px; font-size:0.8rem;">
                                <?php foreach (['Item Collected','Processing','Shipped','Delivered','Cancelled'] as $s): ?>
                                    <option value="<?= $s ?>" <?= ($o['order_status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
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
