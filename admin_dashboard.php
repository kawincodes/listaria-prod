<?php
require_once __DIR__ . '/includes/session.php';
require 'includes/db.php';
$activePage = 'dashboard';

// Check Admin Access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

// Handle Approve Product (Dashboard Quick Action)
if (isset($_POST['approve_product_id'])) {
    $pid = $_POST['approve_product_id'];
    $stmt = $pdo->prepare("UPDATE products SET approval_status = 'approved' WHERE id = ?");
    $stmt->execute([$pid]);
    $msg = "Product approved successfully.";
}

// Handle Reject Product (Dashboard Quick Action)
if (isset($_POST['reject_product_id'])) {
    $pid = $_POST['reject_product_id'];
    $stmt = $pdo->prepare("UPDATE products SET approval_status = 'rejected' WHERE id = ?");
    $stmt->execute([$pid]);
    $msg = "Product rejected.";
}

// Handle Banner Upload
if(isset($_FILES['banner_upload']) && $_FILES['banner_upload']['error'] === UPLOAD_ERR_OK) {
    if (!is_dir('assets')) mkdir('assets', 0755, true);
    move_uploaded_file($_FILES['banner_upload']['tmp_name'], 'assets/banner.jpg');
    $msg = "Banner updated successfully.";
}

// Handle Banner Removal
if(isset($_POST['remove_banner'])) {
    if(file_exists('assets/banner.jpg')) {
        unlink('assets/banner.jpg');
        $msg = "Banner removed successfully.";
    }
}

// Stats Queries
// Get Commission Rate
$stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'commission_rate'");
$stmt->execute();
$commissionRate = $stmt->fetchColumn() ?: 5; // Default 5%

// Stats Queries
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// Transaction Value (GMV) - Total "Success" Amount
$paramValue = $pdo->query("SELECT SUM(amount) FROM orders WHERE order_status = 'Success'")->fetchColumn(); 
$totalTxnValue = $paramValue ?: 0;

// Net Revenue (Commission)
$totalRevenue = ($totalTxnValue * $commissionRate) / 100;

// Fetch Pending Products
$pendingStmt = $pdo->query("
    SELECT p.*, u.full_name as seller_name 
    FROM products p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.approval_status = 'pending'
    ORDER BY p.created_at ASC
");
$pendingProducts = $pendingStmt->fetchAll();

// Daily Stats Logic
$dailyActivity = [];

// 1. Daily Users
$dUsers = $pdo->query("SELECT date(created_at) as date, COUNT(*) as count FROM users GROUP BY date")->fetchAll(PDO::FETCH_KEY_PAIR);
foreach($dUsers as $date => $count) {
    if(!$date) continue;
    $dailyActivity[$date]['users'] = $count;
}

// 2. Daily Listings
$dProducts = $pdo->query("SELECT date(created_at) as date, COUNT(*) as count FROM products GROUP BY date")->fetchAll(PDO::FETCH_KEY_PAIR);
foreach($dProducts as $date => $count) {
    if(!$date) continue;
    $dailyActivity[$date]['products'] = $count;
}

// 3. Daily Orders & Revenue
$dOrders = $pdo->query("
    SELECT date(created_at) as date, 
           COUNT(*) as count, 
           SUM(CASE WHEN order_status = 'Success' THEN amount ELSE 0 END) as gmv 
    FROM orders 
    GROUP BY date
")->fetchAll();
foreach($dOrders as $row) {
    $date = $row['date'];
    if(!$date) continue;
    $dailyActivity[$date]['orders'] = $row['count'];
    $dailyActivity[$date]['gmv'] = $row['gmv'];
    $dailyActivity[$date]['revenue'] = ($row['gmv'] * $commissionRate) / 100;
}

// Sort by Date DESC
krsort($dailyActivity);

// Today's Revenue Helper
$todayDate = date('Y-m-d');
$todayTxnValue = $dailyActivity[$todayDate]['gmv'] ?? 0;
$todayRevenue = $dailyActivity[$todayDate]['revenue'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Listaria</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root { 
            --primary: #6B21A8; 
            --primary-dark: #581c87;
            --accent: #6B21A8; 
            --success: #22c55e;
            --bg: #f8f9fa; 
            --sidebar-bg: #1a1a1a;
            --text-light: #a1a1aa;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; display:flex; color: #333; }
        
        .sidebar { 
            width: 260px; 
            background: var(--sidebar-bg); 
            height: 100vh; 
            position: fixed; 
            padding: 0.5rem 0; 
            color: white;
            z-index: 100;
        }
        .brand { 
            font-size: 1.2rem; 
            font-weight: 700; 
            color: white; 
            display:flex; 
            align-items: center; 
            gap: 10px;
            margin-bottom: 0.5rem; 
            text-decoration:none;
        }
        
        /* Main Content */
        .main-content { 
            margin-left: 260px; 
            padding: 2.5rem 3rem; 
            width: calc(100% - 260px); 
            min-height: 100vh;
        }
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 2.5rem; 
        }
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1e293b; }
        
        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 1.5rem; margin-bottom: 3rem; }
        .stat-card { 
            background: white; 
            padding: 1.5rem; 
            border-radius: 16px; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); 
            transition: transform 0.2s;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #6B21A8, #a855f7);
        }
        .stat-card:nth-child(2)::before { background: linear-gradient(90deg, #6B21A8, #c084fc); }
        .stat-card:nth-child(3)::before { background: linear-gradient(90deg, #1a1a1a, #525252); }
        .stat-card:nth-child(4)::before { background: linear-gradient(90deg, #1a1a1a, #404040); }
        .stat-card:nth-child(5)::before { background: linear-gradient(90deg, #22c55e, #4ade80); }
        
        .stat-label { font-size: 0.8rem; color: #64748b; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        .stat-value { font-size: 1.8rem; font-weight: 800; color: #1e293b; }
        
        /* Data Tables */
        .section-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 1.2rem; color: #334155; display: flex; align-items: center; gap: 8px;}
        .table-container { 
            background: white; 
            border-radius: 16px; 
            overflow: hidden; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); 
            margin-bottom: 3rem; 
            border: 1px solid #f1f5f9;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1.2rem 1.5rem; text-align: left; font-size: 0.9rem; }
        th { 
            background: #f8fafc; 
            color: #64748b; 
            font-weight: 600; 
            text-transform: uppercase; 
            font-size: 0.75rem; 
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }
        td { color: #334155; border-bottom: 1px solid #f1f5f9; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px;}
        .badge-avail { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef9c3; color: #854d0e; }
        
        .btn-approve {
            border: none; background: #dcfce7; color: #166534; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600; margin-right: 5px;
        }
        .btn-reject {
            border: none; background: #fee2e2; color: #991b1b; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600;
        }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <div>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Users</div>
                <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Listings</div>
                <div class="stat-value"><?php echo number_format($totalProducts); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?php echo number_format($totalOrders); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Transaction Value</div>
                <div class="stat-value" style="color:#2563eb;">₹<?php echo number_format($totalTxnValue); ?></div>
                <div class="stat-label" style="font-size:0.7rem; margin-top:2px;">Today: ₹<?php echo number_format($todayTxnValue); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Net Revenue (<?php echo $commissionRate; ?>%)</div>
                <div class="stat-value" style="color:#22c55e;">₹<?php echo number_format($totalRevenue); ?></div>
                <div class="stat-label" style="font-size:0.7rem; margin-top:2px;">Today: ₹<?php echo number_format($todayRevenue); ?></div>
            </div>
        </div>

        <!-- Pending Approvals (Kept for priority visibility) -->
        <?php if(count($pendingProducts) > 0): ?>
        <div class="section-title" style="color:#d35400;"><ion-icon name="alert-circle-outline"></ion-icon> Pending Approvals</div>
        <div class="table-container" style="border-left: 4px solid #f39c12;">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Seller</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pendingProducts as $p): ?>
                    <tr>
                        <td style="display:flex; align-items:center; gap:10px;">
                            <?php $img = json_decode($p['image_paths'])[0] ?? ''; ?>
                            <img src="<?php echo $img; ?>" style="width:40px; height:40px; border-radius:4px; object-fit:cover;">
                            <?php echo htmlspecialchars($p['title']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($p['seller_name']); ?></td>
                        <td>₹<?php echo number_format($p['price_min']); ?></td>
                        <td>
                            <div style="display:flex;">
                                <form method="POST" style="margin-right:5px;">
                                    <input type="hidden" name="approve_product_id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="btn-approve">Approve</button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Reject this product?');">
                                    <input type="hidden" name="reject_product_id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="btn-reject">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Site Settings (Banner Upload) - Moved to its own page 'admin_settings.php' usually, but asked for Listings/Transactions/Chats specifically. 
             If 'Site Settings' link in sidebar points to `admin_settings.php`, I should probably move this block there or leave it here if that file is not fully robust.
             Checking file list: `admin_settings.php` DOES exist. 
             So I should REMOVE banner upload from here to clean up dashboard, as user asked for "Separation".
             Wait, I will leave it for now or check if admin_settings.php has it.
             Checking sidebar: `admin_settings.php` is linked.
             It's safer to leave Banner Upload off the dashboard if we want "Clean".
             But let's keep "Daily Activity" as it is "Stats".
        -->

        <!-- Quick Actions -->
        <div class="section-title"><ion-icon name="flash-outline"></ion-icon> Quick Actions</div>
        <div class="quick-actions-grid">
            <a href="admin_users.php" class="quick-action-card">
                <ion-icon name="people-outline"></ion-icon>
                <span>Manage Users</span>
            </a>
            <a href="admin_listings.php" class="quick-action-card">
                <ion-icon name="pricetags-outline"></ion-icon>
                <span>View Listings</span>
            </a>
            <a href="admin_transactions.php" class="quick-action-card">
                <ion-icon name="receipt-outline"></ion-icon>
                <span>Transactions</span>
            </a>
            <a href="admin_settings.php" class="quick-action-card">
                <ion-icon name="settings-outline"></ion-icon>
                <span>Site Settings</span>
            </a>
        </div>

        <div class="dashboard-grid">
            <!-- Recent Orders -->
            <div class="dashboard-card">
                <div class="section-title"><ion-icon name="bag-handle-outline"></ion-icon> Recent Orders</div>
                <?php
                $recentOrders = $pdo->query("
                    SELECT o.*, p.title, u.full_name as buyer_name
                    FROM orders o
                    JOIN products p ON o.product_id = p.id
                    JOIN users u ON o.user_id = u.id
                    ORDER BY o.created_at DESC
                    LIMIT 5
                ")->fetchAll();
                ?>
                <?php if(count($recentOrders) > 0): ?>
                    <div class="recent-list">
                        <?php foreach($recentOrders as $order): ?>
                        <div class="recent-item">
                            <div class="recent-icon" style="background: #f0fdf4; color: #22c55e;">
                                <ion-icon name="bag-check-outline"></ion-icon>
                            </div>
                            <div class="recent-info">
                                <div class="recent-title"><?php echo htmlspecialchars($order['title']); ?></div>
                                <div class="recent-meta"><?php echo htmlspecialchars($order['buyer_name']); ?></div>
                            </div>
                            <div class="recent-amount">₹<?php echo number_format($order['amount']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color:#999; text-align:center; padding:2rem;">No orders yet</p>
                <?php endif; ?>
            </div>

            <!-- Recent Users -->
            <div class="dashboard-card">
                <div class="section-title"><ion-icon name="person-add-outline"></ion-icon> New Users</div>
                <?php
                $recentUsers = $pdo->query("
                    SELECT * FROM users 
                    WHERE is_admin = 0
                    ORDER BY created_at DESC
                    LIMIT 5
                ")->fetchAll();
                ?>
                <?php if(count($recentUsers) > 0): ?>
                    <div class="recent-list">
                        <?php foreach($recentUsers as $user): ?>
                        <div class="recent-item">
                            <div class="user-avatar-small"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></div>
                            <div class="recent-info">
                                <div class="recent-title"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                <div class="recent-meta"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                            <div class="recent-date"><?php echo date('M j', strtotime($user['created_at'])); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color:#999; text-align:center; padding:2rem;">No users yet</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Daily Activity -->
        <div class="section-title"><ion-icon name="analytics-outline"></ion-icon> Daily Activity Monitor</div>
        <div class="table-container">
            <table>
                <thead>
                        <tr>
                            <th>Date</th>
                            <th>New Users</th>
                            <th>New Listings</th>
                            <th>Orders</th>
                            <th>Txn Value</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(count($dailyActivity) > 0): ?>
                        <?php foreach(array_slice($dailyActivity, 0, 10, true) as $date => $stats): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($date)); ?></td>
                            <td><?php echo $stats['users'] ?? 0; ?></td>
                            <td><?php echo $stats['products'] ?? 0; ?></td>
                            <td><?php echo $stats['orders'] ?? 0; ?></td>
                            <td style="color:#2563eb;">₹<?php echo number_format($stats['gmv'] ?? 0); ?></td>
                            <td style="color:#22c55e; font-weight:600;">₹<?php echo number_format($stats['revenue'] ?? 0); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                         <tr><td colspan="5" style="text-align:center; color:#999; padding:2rem;">No activity recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
    
    <style>
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .quick-action-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
        }
        
        .quick-action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(107, 33, 168, 0.15);
            border-color: #6B21A8;
        }
        
        .quick-action-card ion-icon {
            font-size: 2rem;
            color: #6B21A8;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .quick-action-card span {
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .dashboard-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
        }
        
        .dashboard-card .section-title {
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .recent-list {
            display: flex;
            flex-direction: column;
        }
        
        .recent-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .recent-item:last-child {
            border-bottom: none;
        }
        
        .recent-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .user-avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6B21A8, #a855f7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .recent-info {
            flex: 1;
        }
        
        .recent-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: #1a1a1a;
        }
        
        .recent-meta {
            font-size: 0.8rem;
            color: #999;
        }
        
        .recent-amount {
            font-weight: 700;
            color: #22c55e;
        }
        
        .recent-date {
            font-size: 0.8rem;
            color: #999;
        }
        
        @media (max-width: 1024px) {
            .quick-actions-grid { grid-template-columns: repeat(2, 1fr); }
            .dashboard-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
</body>
</html>
