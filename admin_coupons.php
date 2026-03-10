<?php
require_once __DIR__ . '/includes/session.php';
require 'includes/db.php';

$activePage = 'coupons';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS coupons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code VARCHAR(50) NOT NULL UNIQUE,
            type VARCHAR(20) NOT NULL DEFAULT 'percentage',
            value REAL NOT NULL DEFAULT 0,
            min_order_amount REAL DEFAULT 0,
            max_discount_amount REAL DEFAULT 0,
            usage_limit INTEGER DEFAULT 0,
            used_count INTEGER DEFAULT 0,
            per_user_limit INTEGER DEFAULT 1,
            start_date DATETIME,
            end_date DATETIME,
            is_active INTEGER DEFAULT 1,
            created_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS coupon_usage (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            coupon_code VARCHAR(50) NOT NULL,
            order_id INTEGER,
            discount_amount REAL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    try {
        $pdo->exec("ALTER TABLE coupons ADD COLUMN per_user_limit INTEGER DEFAULT 1");
    } catch (Exception $ignored) {}
} catch (Exception $e) {}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $msg = 'Invalid security token. Please try again.';
        $msgType = 'error';
    } else {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                case 'update':
                    $code = strtoupper(trim($_POST['code'] ?? ''));
                    $type = in_array($_POST['type'] ?? '', ['percentage', 'flat']) ? $_POST['type'] : 'percentage';
                    $value = floatval($_POST['value'] ?? 0);
                    $min_order = floatval($_POST['min_order_amount'] ?? 0);
                    $max_discount = floatval($_POST['max_discount_amount'] ?? 0);
                    $usage_limit = intval($_POST['usage_limit'] ?? 0);
                    $per_user_limit = intval($_POST['per_user_limit'] ?? 1);
                    $start_date = $_POST['start_date'] ?? null;
                    $end_date = $_POST['end_date'] ?? null;
                    $is_active = isset($_POST['is_active']) ? 1 : 0;

                    if (empty($code)) {
                        $msg = 'Coupon code is required.';
                        $msgType = 'error';
                        break;
                    }
                    if ($value <= 0) {
                        $msg = 'Discount value must be greater than 0.';
                        $msgType = 'error';
                        break;
                    }
                    if ($type === 'percentage' && $value > 100) {
                        $msg = 'Percentage discount cannot exceed 100%.';
                        $msgType = 'error';
                        break;
                    }

                    if ($_POST['action'] === 'create') {
                        $check = $pdo->prepare("SELECT COUNT(*) FROM coupons WHERE code = ?");
                        $check->execute([$code]);
                        if ($check->fetchColumn() > 0) {
                            $msg = 'A coupon with this code already exists.';
                            $msgType = 'error';
                            break;
                        }
                        $stmt = $pdo->prepare("INSERT INTO coupons (code, type, value, min_order_amount, max_discount_amount, usage_limit, per_user_limit, start_date, end_date, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$code, $type, $value, $min_order, $max_discount, $usage_limit, $per_user_limit, $start_date ?: null, $end_date ?: null, $is_active, $_SESSION['user_id']]);
                        $msg = "Coupon \"$code\" created successfully!";
                        $msgType = 'success';
                    } else {
                        $id = intval($_POST['coupon_id'] ?? 0);
                        $check = $pdo->prepare("SELECT COUNT(*) FROM coupons WHERE code = ? AND id != ?");
                        $check->execute([$code, $id]);
                        if ($check->fetchColumn() > 0) {
                            $msg = 'Another coupon with this code already exists.';
                            $msgType = 'error';
                            break;
                        }
                        $stmt = $pdo->prepare("UPDATE coupons SET code = ?, type = ?, value = ?, min_order_amount = ?, max_discount_amount = ?, usage_limit = ?, per_user_limit = ?, start_date = ?, end_date = ?, is_active = ? WHERE id = ?");
                        $stmt->execute([$code, $type, $value, $min_order, $max_discount, $usage_limit, $per_user_limit, $start_date ?: null, $end_date ?: null, $is_active, $id]);
                        $msg = "Coupon \"$code\" updated successfully!";
                        $msgType = 'success';
                    }
                    break;

                case 'toggle':
                    $id = intval($_POST['coupon_id'] ?? 0);
                    $stmt = $pdo->prepare("UPDATE coupons SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?");
                    $stmt->execute([$id]);
                    $msg = 'Coupon status toggled.';
                    $msgType = 'success';
                    break;

                case 'delete':
                    $id = intval($_POST['coupon_id'] ?? 0);
                    $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
                    $stmt->execute([$id]);
                    $msg = 'Coupon deleted.';
                    $msgType = 'success';
                    break;
            }
        }
    }
}

$coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();

$editCoupon = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([$editId]);
    $editCoupon = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Coupon Management - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root { 
            --primary: #6B21A8; 
            --primary-dark: #581c87;
            --bg: #f8f9fa; 
            --sidebar-bg: #1a1a1a;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; display: flex; color: #333; }
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; padding: 0.5rem 0; color: white; z-index: 100; }
        .main-content { margin-left: 260px; padding: 2.5rem 3rem; width: calc(100% - 260px); min-height: 100vh; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }

        .msg-success {
            background: #f0fdf4; color: #22c55e; padding: 1rem; border-radius: 10px;
            margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 8px;
            border: 1px solid #bbf7d0;
        }
        .msg-error {
            background: #fef2f2; color: #ef4444; padding: 1rem; border-radius: 10px;
            margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 8px;
            border: 1px solid #fecaca;
        }

        .card {
            background: white; border-radius: 16px; padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #f0f0f0;
            margin-bottom: 1.5rem;
        }
        .card-title {
            font-size: 1rem; font-weight: 700; color: #1a1a1a; margin-bottom: 1.25rem;
            padding-bottom: 0.75rem; border-bottom: 1px solid #f0f0f0;
            display: flex; align-items: center; gap: 8px;
        }
        .card-title ion-icon { color: #6B21A8; }

        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .form-group { margin-bottom: 0.75rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.4rem; color: #333; font-size: 0.85rem; }
        .form-group input, .form-group select {
            width: 100%; padding: 0.65rem 0.85rem; border: 1px solid #e5e5e5;
            border-radius: 8px; font-size: 0.9rem; transition: border-color 0.2s; font-family: inherit;
        }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #6B21A8; }
        .form-group small { display: block; margin-top: 0.25rem; color: #999; font-size: 0.78rem; }

        .toggle-row { display: flex; align-items: center; gap: 10px; margin-bottom: 1rem; }
        .toggle-switch { position: relative; display: inline-block; width: 44px; min-width: 44px; height: 24px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; position: absolute; }
        .toggle-slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #e5e5e5; transition: 0.3s; border-radius: 24px;
        }
        .toggle-slider:before {
            position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px;
            background-color: white; transition: 0.3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        input:checked + .toggle-slider { background-color: #6B21A8; }
        input:checked + .toggle-slider:before { transform: translateX(20px); }

        .btn {
            padding: 0.65rem 1.3rem; border: none; border-radius: 8px; cursor: pointer;
            font-weight: 600; font-size: 0.88rem; transition: all 0.2s;
            display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
        }
        .btn-primary { background: #6B21A8; color: white; }
        .btn-primary:hover { background: #581c87; }
        .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.8rem; }
        .btn-outline { background: transparent; border: 1px solid #e5e5e5; color: #333; }
        .btn-outline:hover { background: #f5f5f5; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-success { background: #22c55e; color: white; }
        .btn-success:hover { background: #16a34a; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-warning:hover { background: #d97706; }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th { background: #f8fafc; text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #64748b; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; }
        td { padding: 0.75rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:hover td { background: #fafbfc; }

        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: 0.72rem; font-weight: 600; text-transform: uppercase;
        }
        .badge-active { background: rgba(34,197,94,0.1); color: #22c55e; }
        .badge-inactive { background: rgba(239,68,68,0.1); color: #ef4444; }
        .badge-expired { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .badge-percentage { background: rgba(107,33,168,0.1); color: #6B21A8; }
        .badge-flat { background: rgba(59,130,246,0.1); color: #3b82f6; }

        .usage-bar { background: #f1f5f9; border-radius: 20px; height: 8px; overflow: hidden; width: 80px; display: inline-block; vertical-align: middle; margin-right: 6px; }
        .usage-fill { height: 100%; border-radius: 20px; background: #6B21A8; transition: width 0.3s; }

        .actions-cell { display: flex; gap: 6px; flex-wrap: wrap; }

        .empty-state {
            text-align: center; padding: 3rem 2rem;
        }
        .empty-state ion-icon { font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; }
        .empty-state h3 { margin: 0 0 0.5rem; color: #334155; }
        .empty-state p { color: #94a3b8; margin: 0; }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; width: 100%; padding: 1.5rem; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <div>
                <h1>Coupon Management</h1>
                <p style="color:#666; margin-top:0.5rem;">Create and manage discount coupons</p>
            </div>
            <?php if (!$editCoupon): ?>
            <a href="#coupon-form" class="btn btn-primary" onclick="document.getElementById('coupon-form').scrollIntoView({behavior:'smooth'})">
                <ion-icon name="add-circle-outline"></ion-icon> New Coupon
            </a>
            <?php endif; ?>
        </div>

        <?php if ($msg): ?>
            <div class="msg-<?php echo $msgType; ?>">
                <ion-icon name="<?php echo $msgType === 'success' ? 'checkmark-circle-outline' : 'alert-circle-outline'; ?>"></ion-icon>
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div class="card" id="coupon-form">
            <div class="card-title">
                <ion-icon name="<?php echo $editCoupon ? 'create-outline' : 'add-circle-outline'; ?>"></ion-icon>
                <?php echo $editCoupon ? 'Edit Coupon' : 'Create New Coupon'; ?>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="<?php echo $editCoupon ? 'update' : 'create'; ?>">
                <?php if ($editCoupon): ?>
                <input type="hidden" name="coupon_id" value="<?php echo $editCoupon['id']; ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label>Coupon Code</label>
                        <input type="text" name="code" value="<?php echo htmlspecialchars($editCoupon['code'] ?? ''); ?>" required placeholder="e.g. SAVE20" style="text-transform: uppercase;">
                        <small>Unique code customers will enter</small>
                    </div>
                    <div class="form-group">
                        <label>Discount Type</label>
                        <select name="type">
                            <option value="percentage" <?php echo ($editCoupon['type'] ?? 'percentage') === 'percentage' ? 'selected' : ''; ?>>Percentage (%)</option>
                            <option value="flat" <?php echo ($editCoupon['type'] ?? '') === 'flat' ? 'selected' : ''; ?>>Flat Amount (₹)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Discount Value</label>
                        <input type="number" name="value" value="<?php echo htmlspecialchars($editCoupon['value'] ?? ''); ?>" required min="0.01" step="0.01" placeholder="e.g. 10">
                        <small>Percentage (1-100) or flat amount</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Minimum Order Amount (₹)</label>
                        <input type="number" name="min_order_amount" value="<?php echo htmlspecialchars($editCoupon['min_order_amount'] ?? '0'); ?>" min="0" step="0.01">
                        <small>0 = no minimum</small>
                    </div>
                    <div class="form-group">
                        <label>Max Discount Amount (₹)</label>
                        <input type="number" name="max_discount_amount" value="<?php echo htmlspecialchars($editCoupon['max_discount_amount'] ?? '0'); ?>" min="0" step="0.01">
                        <small>0 = no cap (for percentage type)</small>
                    </div>
                    <div class="form-group">
                        <label>Total Usage Limit</label>
                        <input type="number" name="usage_limit" value="<?php echo htmlspecialchars($editCoupon['usage_limit'] ?? '0'); ?>" min="0">
                        <small>0 = unlimited</small>
                    </div>
                    <div class="form-group">
                        <label>Per User Limit</label>
                        <input type="number" name="per_user_limit" value="<?php echo htmlspecialchars($editCoupon['per_user_limit'] ?? '1'); ?>" min="0">
                        <small>0 = unlimited per user</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="datetime-local" name="start_date" value="<?php echo $editCoupon && $editCoupon['start_date'] ? date('Y-m-d\TH:i', strtotime($editCoupon['start_date'])) : ''; ?>">
                        <small>Leave empty for immediate</small>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="datetime-local" name="end_date" value="<?php echo $editCoupon && $editCoupon['end_date'] ? date('Y-m-d\TH:i', strtotime($editCoupon['end_date'])) : ''; ?>">
                        <small>Leave empty for no expiry</small>
                    </div>
                </div>

                <div class="toggle-row">
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_active" <?php echo ($editCoupon ? $editCoupon['is_active'] : 1) ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <span style="font-weight:600; font-size:0.9rem;">Active</span>
                </div>

                <div style="display:flex; gap:0.75rem; margin-top:0.5rem;">
                    <button type="submit" class="btn btn-primary">
                        <ion-icon name="<?php echo $editCoupon ? 'save-outline' : 'add-outline'; ?>"></ion-icon>
                        <?php echo $editCoupon ? 'Update Coupon' : 'Create Coupon'; ?>
                    </button>
                    <?php if ($editCoupon): ?>
                    <a href="admin_coupons.php" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-title">
                <ion-icon name="list-outline"></ion-icon>
                All Coupons
                <span style="margin-left:auto; font-size:0.8rem; color:#64748b; font-weight:400;"><?php echo count($coupons); ?> total</span>
            </div>

            <?php if (empty($coupons)): ?>
                <div class="empty-state">
                    <ion-icon name="pricetag-outline"></ion-icon>
                    <h3>No Coupons Yet</h3>
                    <p>Create your first coupon using the form above.</p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Min Order</th>
                                <th>Max Discount</th>
                                <th>Usage</th>
                                <th>Dates</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coupons as $coupon):
                                $now = time();
                                $isExpired = $coupon['end_date'] && strtotime($coupon['end_date']) < $now;
                                $notStarted = $coupon['start_date'] && strtotime($coupon['start_date']) > $now;
                                $limitReached = $coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit'];
                                $usagePct = ($coupon['usage_limit'] > 0) ? min(100, round(($coupon['used_count'] / $coupon['usage_limit']) * 100)) : 0;
                            ?>
                            <tr>
                                <td><strong style="font-family:monospace; font-size:0.9rem; color:#1a1a1a;"><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                                <td><span class="badge <?php echo $coupon['type'] === 'percentage' ? 'badge-percentage' : 'badge-flat'; ?>"><?php echo $coupon['type'] === 'percentage' ? 'Percentage' : 'Flat'; ?></span></td>
                                <td style="font-weight:600;"><?php echo $coupon['type'] === 'percentage' ? $coupon['value'] . '%' : '₹' . number_format($coupon['value'], 2); ?></td>
                                <td><?php echo $coupon['min_order_amount'] > 0 ? '₹' . number_format($coupon['min_order_amount'], 2) : '—'; ?></td>
                                <td><?php echo $coupon['max_discount_amount'] > 0 ? '₹' . number_format($coupon['max_discount_amount'], 2) : '—'; ?></td>
                                <td>
                                    <?php if ($coupon['usage_limit'] > 0): ?>
                                        <div class="usage-bar"><div class="usage-fill" style="width:<?php echo $usagePct; ?>%"></div></div>
                                        <span style="font-size:0.8rem;"><?php echo $coupon['used_count']; ?>/<?php echo $coupon['usage_limit']; ?></span>
                                    <?php else: ?>
                                        <span style="font-size:0.8rem;"><?php echo $coupon['used_count']; ?> used</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:0.8rem; color:#64748b;">
                                    <?php if ($coupon['start_date']): ?>
                                        <?php echo date('M j, Y', strtotime($coupon['start_date'])); ?>
                                    <?php else: ?>
                                        Immediate
                                    <?php endif; ?>
                                    <br>
                                    <?php if ($coupon['end_date']): ?>
                                        → <?php echo date('M j, Y', strtotime($coupon['end_date'])); ?>
                                    <?php else: ?>
                                        → No expiry
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isExpired): ?>
                                        <span class="badge badge-expired">Expired</span>
                                    <?php elseif ($limitReached): ?>
                                        <span class="badge badge-expired">Limit Reached</span>
                                    <?php elseif (!$coupon['is_active']): ?>
                                        <span class="badge badge-inactive">Inactive</span>
                                    <?php elseif ($notStarted): ?>
                                        <span class="badge badge-expired">Scheduled</span>
                                    <?php else: ?>
                                        <span class="badge badge-active">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions-cell">
                                        <a href="admin_coupons.php?edit=<?php echo $coupon['id']; ?>#coupon-form" class="btn btn-sm btn-outline">
                                            <ion-icon name="create-outline"></ion-icon>
                                        </a>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $coupon['is_active'] ? 'btn-warning' : 'btn-success'; ?>" title="<?php echo $coupon['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                <ion-icon name="<?php echo $coupon['is_active'] ? 'pause-outline' : 'play-outline'; ?>"></ion-icon>
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this coupon?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <ion-icon name="trash-outline"></ion-icon>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
