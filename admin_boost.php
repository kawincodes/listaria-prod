<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';

$activePage = 'boost';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php"); exit;
}

function getSetting($pdo, $key, $default = '') {
    try { $s = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key=?"); $s->execute([$key]); $v = $s->fetchColumn(); return $v !== false ? $v : $default; } catch(Exception $e) { return $default; }
}
function saveSetting($pdo, $key, $val) {
    $pdo->prepare("INSERT INTO site_settings (setting_key,setting_value) VALUES (?,?) ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value,updated_at=CURRENT_TIMESTAMP")->execute([$key, $val]);
}

$currency = getSetting($pdo, 'currency_symbol', '₹');
$msg = ''; $msgType = 'success';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Approve manual boost
    if (isset($_POST['approve_boost'])) {
        $boostId = (int)$_POST['boost_id'];
        $row = $pdo->prepare("SELECT * FROM boost_orders WHERE id=? AND status='pending'");
        $row->execute([$boostId]); $boost = $row->fetch();
        if ($boost) {
            $from  = date('Y-m-d H:i:s');
            $until = date('Y-m-d H:i:s', strtotime("+{$boost['plan_days']} days"));
            $pdo->prepare("UPDATE boost_orders SET status='active',boosted_from=?,boosted_until=? WHERE id=?")->execute([$from,$until,$boostId]);
            $pdo->prepare("UPDATE products SET is_featured=1,boosted_until=? WHERE id=?")->execute([$until,$boost['product_id']]);
            $msg = "Boost #$boostId approved and activated.";
        }
    }

    // Reject/cancel boost
    if (isset($_POST['cancel_boost'])) {
        $boostId = (int)$_POST['boost_id'];
        $pdo->prepare("UPDATE boost_orders SET status='cancelled',admin_note=? WHERE id=?")->execute([$_POST['cancel_note'] ?? '',$boostId]);
        $msg = "Boost #$boostId cancelled.";
    }

    // Manually add boost to any product
    if (isset($_POST['manual_boost'])) {
        $productId = (int)$_POST['product_id'];
        $planDays  = (int)$_POST['plan_days'];
        $note      = trim($_POST['admin_note'] ?? '');
        if ($productId && in_array($planDays, [7,14,30])) {
            $until = date('Y-m-d H:i:s', strtotime("+$planDays days"));
            $pdo->prepare("UPDATE products SET is_featured=1,boosted_until=? WHERE id=?")->execute([$until,$productId]);
            $pdo->prepare("INSERT INTO boost_orders (product_id,user_id,plan_days,amount,payment_method,status,boosted_from,boosted_until,admin_note) VALUES (?,0,?,0,'admin','active',datetime('now'),?,?)")
                ->execute([$productId,$planDays,$until,$note]);
            $msg = "Manual boost applied for $planDays days.";
        }
    }

    // Remove boost
    if (isset($_POST['remove_boost'])) {
        $productId = (int)$_POST['product_id'];
        $pdo->prepare("UPDATE products SET is_featured=0,boosted_until=NULL WHERE id=?")->execute([$productId]);
        $pdo->prepare("UPDATE boost_orders SET status='expired' WHERE product_id=? AND status='active'")->execute([$productId]);
        $msg = "Boost removed from product #$productId.";
    }

    // Save pricing
    if (isset($_POST['save_pricing'])) {
        saveSetting($pdo, 'boost_price_7',  $_POST['price_7'] ?? '99');
        saveSetting($pdo, 'boost_price_14', $_POST['price_14'] ?? '179');
        saveSetting($pdo, 'boost_price_30', $_POST['price_30'] ?? '299');
        saveSetting($pdo, 'bank_details',   trim($_POST['bank_details'] ?? ''));
        $msg = "Boost pricing saved.";
    }
}

// Stats
$totalBoosts   = (int)$pdo->query("SELECT COUNT(*) FROM boost_orders")->fetchColumn();
$activeBoosts  = (int)$pdo->query("SELECT COUNT(*) FROM boost_orders WHERE status='active'")->fetchColumn();
$pendingBoosts = (int)$pdo->query("SELECT COUNT(*) FROM boost_orders WHERE status='pending'")->fetchColumn();
$totalRevenue  = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM boost_orders WHERE status IN ('active','expired')")->fetchColumn();

// Pending approvals
$pending = $pdo->query("SELECT bo.*, p.title as product_title, u.full_name, u.email FROM boost_orders bo JOIN products p ON bo.product_id=p.id JOIN users u ON bo.user_id=u.id WHERE bo.status='pending' ORDER BY bo.created_at DESC")->fetchAll();

// Active boosts
$active = $pdo->query("SELECT bo.*, p.title as product_title, u.full_name FROM boost_orders bo JOIN products p ON bo.product_id=p.id LEFT JOIN users u ON bo.user_id=u.id WHERE bo.status='active' ORDER BY bo.boosted_until ASC")->fetchAll();

// All products (for manual boost)
$allProducts = $pdo->query("SELECT id, title FROM products WHERE approval_status='approved' ORDER BY title ASC")->fetchAll();

// Pricing
$price7  = getSetting($pdo, 'boost_price_7', '99');
$price14 = getSetting($pdo, 'boost_price_14', '179');
$price30 = getSetting($pdo, 'boost_price_30', '299');
$bankDetails = getSetting($pdo, 'bank_details', '');

// History
$history = $pdo->query("SELECT bo.*, p.title as product_title, u.full_name FROM boost_orders bo JOIN products p ON bo.product_id=p.id LEFT JOIN users u ON bo.user_id=u.id ORDER BY bo.created_at DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boost Management - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root { --primary: #6B21A8; --bg: #f8f9fa; }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; display: flex; color: #333; }
        .sidebar { width: 260px; background: #1a1a1a; height: 100vh; position: fixed; padding: 0.5rem 0; color: white; z-index: 100; }
        .main-content { margin-left: 260px; padding: 2.5rem 3rem; width: calc(100% - 260px); min-height: 100vh; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }
        .header p { margin: 4px 0 0; color: #666; font-size: 0.9rem; }

        .alert { padding: 1rem 1.25rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: #f0fdf4; color: #166534; border-left: 4px solid #22c55e; }
        .alert-error   { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; }

        .stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: white; border-radius: 14px; padding: 1.25rem 1.5rem; border: 1px solid #f0f0f0; }
        .stat-label { font-size: 0.78rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 2rem; font-weight: 800; color: #1a1a1a; margin: 4px 0; }
        .stat-sub { font-size: 0.8rem; color: #94a3b8; }

        .card { background: white; border-radius: 16px; padding: 1.75rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #f0f0f0; margin-bottom: 2rem; }
        .card-title { font-size: 1.1rem; font-weight: 700; color: #1a1a1a; margin: 0 0 1.5rem; display: flex; align-items: center; gap: 10px; }
        .card-title ion-icon { color: #6B21A8; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 10px 12px; font-size: 0.78rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; border-bottom: 2px solid #f0f0f0; }
        td { padding: 12px; font-size: 0.85rem; border-bottom: 1px solid #f5f5f5; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }

        .badge { padding: 3px 10px; border-radius: 50px; font-size: 0.72rem; font-weight: 700; }
        .badge-active  { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-expired { background: #f3f4f6; color: #6b7280; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }

        .btn { padding: 6px 14px; border-radius: 8px; border: none; font-weight: 600; font-size: 0.8rem; cursor: pointer; transition: all 0.2s; }
        .btn-approve { background: #dcfce7; color: #166534; }
        .btn-approve:hover { background: #22c55e; color: white; }
        .btn-cancel  { background: #fee2e2; color: #991b1b; }
        .btn-cancel:hover  { background: #ef4444; color: white; }
        .btn-remove  { background: #fee2e2; color: #991b1b; }
        .btn-remove:hover  { background: #ef4444; color: white; }
        .btn-primary { background: #6B21A8; color: white; padding: 10px 20px; border-radius: 8px; }
        .btn-primary:hover { background: #581c87; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 600; font-size: 0.82rem; color: #333; margin-bottom: 0.4rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.88rem; font-family: inherit; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #6B21A8; }
        .countdown { font-size: 0.75rem; color: #f59e0b; font-weight: 600; }
        .countdown.expired { color: #ef4444; }
        @media(max-width:900px) { .main-content { margin-left:0; width:100%; padding:1.5rem; } .stats-grid { grid-template-columns:repeat(2,1fr); } .form-row { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<?php include 'includes/admin_sidebar.php'; ?>
<div class="main-content">
    <div class="header">
        <div>
            <h1>🚀 Boost Management</h1>
            <p>Manage featured listings, approve boost payments, and set pricing</p>
        </div>
        <a href="boost.php" target="_blank" style="padding:10px 18px;background:#6B21A8;color:white;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.85rem;">View Vendor Page ↗</a>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msgType; ?>">
        <ion-icon name="checkmark-circle-outline"></ion-icon> <?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Active Boosts</div>
            <div class="stat-value" style="color:#6B21A8;"><?php echo $activeBoosts; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pending Approval</div>
            <div class="stat-value" style="color:#f59e0b;"><?php echo $pendingBoosts; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Orders</div>
            <div class="stat-value"><?php echo $totalBoosts; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value" style="color:#22c55e;"><?php echo $currency; ?><?php echo number_format($totalRevenue); ?></div>
        </div>
    </div>

    <!-- Pending Approvals -->
    <?php if (!empty($pending)): ?>
    <div class="card" style="border-left:4px solid #f59e0b;">
        <h2 class="card-title"><ion-icon name="time-outline"></ion-icon> Pending Approval (<?php echo count($pending); ?>)</h2>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>#</th><th>Listing</th><th>User</th><th>Plan</th><th>Amount</th><th>Method</th><th>Requested</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($pending as $b): ?>
            <tr>
                <td style="color:#94a3b8;">#<?php echo $b['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($b['product_title']); ?></strong></td>
                <td><?php echo htmlspecialchars($b['full_name']); ?><br><span style="font-size:0.75rem;color:#94a3b8;"><?php echo htmlspecialchars($b['email']); ?></span></td>
                <td><?php echo $b['plan_days']; ?> days</td>
                <td><?php echo $currency; ?><?php echo number_format($b['amount']); ?></td>
                <td><?php echo ucfirst($b['payment_method']); ?></td>
                <td style="white-space:nowrap;font-size:0.8rem;color:#64748b;"><?php echo date('M j, H:i', strtotime($b['created_at'])); ?></td>
                <td style="white-space:nowrap;">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="boost_id" value="<?php echo $b['id']; ?>">
                        <button name="approve_boost" class="btn btn-approve" onclick="return confirm('Approve and activate this boost?')">✓ Approve</button>
                    </form>
                    <button class="btn btn-cancel" onclick="showCancelModal(<?php echo $b['id']; ?>)">✗ Reject</button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Active Boosts -->
    <div class="card">
        <h2 class="card-title"><ion-icon name="rocket-outline"></ion-icon> Active Boosts</h2>
        <?php if (empty($active)): ?>
        <div style="text-align:center;padding:2rem;color:#94a3b8;">No active boosts right now.</div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>#</th><th>Listing</th><th>Seller</th><th>Plan</th><th>Expires</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($active as $b):
                $expiry    = strtotime($b['boosted_until'] ?? '');
                $remaining = $expiry - time();
                $days      = floor($remaining / 86400);
                $hrs       = floor(($remaining % 86400) / 3600);
                $expired   = $remaining <= 0;
                $countdownClass = ($remaining < 86400 && !$expired) ? 'countdown' : ($expired ? 'countdown expired' : '');
                $countdownText  = $expired ? 'Expired' : ($days > 0 ? "{$days}d {$hrs}h left" : "{$hrs}h left");
            ?>
            <tr>
                <td style="color:#94a3b8;">#<?php echo $b['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($b['product_title']); ?></strong></td>
                <td><?php echo htmlspecialchars($b['full_name'] ?? 'Admin'); ?></td>
                <td><?php echo $b['plan_days']; ?> days</td>
                <td>
                    <?php echo date('M j, Y', $expiry); ?>
                    <?php if ($countdownClass): ?><br><span class="<?php echo $countdownClass; ?>"><?php echo $countdownText; ?></span><?php endif; ?>
                </td>
                <td>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Remove boost from this listing?')">
                        <input type="hidden" name="product_id" value="<?php echo $b['product_id']; ?>">
                        <button name="remove_boost" class="btn btn-remove">Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Manual Boost -->
    <div class="card">
        <h2 class="card-title"><ion-icon name="flash-outline"></ion-icon> Add Manual Boost</h2>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Product</label>
                    <select name="product_id" required>
                        <option value="">-- Select product --</option>
                        <?php foreach ($allProducts as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Boost Duration</label>
                    <select name="plan_days" required>
                        <option value="7">7 Days</option>
                        <option value="14">14 Days</option>
                        <option value="30">30 Days</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Admin Note (optional)</label>
                    <input type="text" name="admin_note" placeholder="e.g. Complimentary boost">
                </div>
            </div>
            <button type="submit" name="manual_boost" class="btn btn-primary">Apply Boost</button>
        </form>
    </div>

    <!-- Pricing Settings -->
    <div class="card">
        <h2 class="card-title"><ion-icon name="pricetag-outline"></ion-icon> Boost Pricing & Settings</h2>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>7-Day Boost Price (<?php echo $currency; ?>)</label>
                    <input type="number" name="price_7" value="<?php echo htmlspecialchars($price7); ?>" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>14-Day Boost Price (<?php echo $currency; ?>)</label>
                    <input type="number" name="price_14" value="<?php echo htmlspecialchars($price14); ?>" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>30-Day Boost Price (<?php echo $currency; ?>)</label>
                    <input type="number" name="price_30" value="<?php echo htmlspecialchars($price30); ?>" min="0" step="0.01" required>
                </div>
            </div>
            <div class="form-group">
                <label>Bank Transfer Details (shown on vendor boost page)</label>
                <textarea name="bank_details" rows="4" placeholder="Account Name: Listaria&#10;Account No: XXXXXXXXXX&#10;IFSC: XXXXXXXXXXX&#10;Bank: XYZ Bank"><?php echo htmlspecialchars($bankDetails); ?></textarea>
            </div>
            <button type="submit" name="save_pricing" class="btn btn-primary">Save Settings</button>
        </form>
    </div>

    <!-- Full History -->
    <div class="card">
        <h2 class="card-title"><ion-icon name="time-outline"></ion-icon> All Boost Orders</h2>
        <?php if (empty($history)): ?>
        <div style="text-align:center;padding:2rem;color:#94a3b8;">No boost orders yet.</div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>#</th><th>Listing</th><th>Seller</th><th>Plan</th><th>Amount</th><th>Method</th><th>Status</th><th>Ordered</th></tr></thead>
            <tbody>
            <?php foreach ($history as $b): ?>
            <tr>
                <td style="color:#94a3b8;">#<?php echo $b['id']; ?></td>
                <td><?php echo htmlspecialchars($b['product_title']); ?></td>
                <td><?php echo htmlspecialchars($b['full_name'] ?? '—'); ?></td>
                <td><?php echo $b['plan_days']; ?> days</td>
                <td><?php echo $b['amount'] > 0 ? $currency . number_format($b['amount']) : '<span style="color:#94a3b8;">Free</span>'; ?></td>
                <td><?php echo ucfirst($b['payment_method']); ?></td>
                <td><span class="badge badge-<?php echo $b['status']; ?>"><?php echo ucfirst($b['status']); ?></span></td>
                <td style="white-space:nowrap;font-size:0.8rem;color:#64748b;"><?php echo date('M j, Y', strtotime($b['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Cancel Modal -->
<div id="cancelModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:16px;padding:1.75rem;width:100%;max-width:440px;margin:1rem;">
        <h3 style="margin:0 0 1rem;font-size:1.1rem;">Reject Boost Request</h3>
        <form method="POST">
            <input type="hidden" name="boost_id" id="cancelBoostId">
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-weight:600;font-size:0.85rem;margin-bottom:0.4rem;">Reason (optional, shown to vendor)</label>
                <textarea name="cancel_note" rows="3" style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:0.85rem;" placeholder="e.g. Payment not received"></textarea>
            </div>
            <div style="display:flex;gap:0.6rem;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('cancelModal').style.display='none'" style="padding:9px 18px;border:1px solid #e2e8f0;border-radius:8px;background:white;cursor:pointer;font-weight:600;">Cancel</button>
                <button type="submit" name="cancel_boost" style="padding:9px 18px;border:none;border-radius:8px;background:#ef4444;color:white;cursor:pointer;font-weight:600;">Reject Boost</button>
            </div>
        </form>
    </div>
</div>
<script>
function showCancelModal(id) {
    document.getElementById('cancelBoostId').value = id;
    document.getElementById('cancelModal').style.display = 'flex';
}
document.getElementById('cancelModal').addEventListener('click', function(e){ if(e.target===this) this.style.display='none'; });
</script>
</body>
</html>
