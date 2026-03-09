<?php
session_start();
require 'includes/db.php';

$activePage = 'analytics';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

// Get Commission Rate
$stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'commission_rate'");
$stmt->execute();
$commissionRate = $stmt->fetchColumn() ?: 5; // Default 5%

// Get date range
$range = $_GET['range'] ?? '7';
$startDate = date('Y-m-d', strtotime("-$range days"));

// Fetch analytics data
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$newUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= '$startDate'")->fetchColumn();
$totalListings = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$newListings = $pdo->query("SELECT COUNT(*) FROM products WHERE created_at >= '$startDate'")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$newOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE created_at >= '$startDate'")->fetchColumn();

// Transaction Value (GMV)
$totalTxnValue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM orders WHERE order_status = 'Success'")->fetchColumn();
$newTxnValue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM orders WHERE created_at >= '$startDate' AND order_status = 'Success'")->fetchColumn();

// Net Revenue (Commission)
$totalRevenue = ($totalTxnValue * $commissionRate) / 100;
$newRevenue = ($newTxnValue * $commissionRate) / 100;


// Daily data for charts
$dailyData = [];
for ($i = $range - 1; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dailyData[$date] = [
        'date' => date('M j', strtotime($date)),
        'users' => 0,
        'listings' => 0,
        'orders' => 0,
        'gmv' => 0,
        'revenue' => 0
    ];
}

$userStats = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM users WHERE created_at >= '$startDate' GROUP BY DATE(created_at)")->fetchAll();
foreach ($userStats as $s) {
    if (isset($dailyData[$s['date']])) {
        $dailyData[$s['date']]['users'] = $s['count'];
    }
}

$listingStats = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM products WHERE created_at >= '$startDate' GROUP BY DATE(created_at)")->fetchAll();
foreach ($listingStats as $s) {
    if (isset($dailyData[$s['date']])) {
        $dailyData[$s['date']]['listings'] = $s['count'];
    }
}

$orderStats = $pdo->query("
    SELECT DATE(created_at) as date, 
           COUNT(*) as count, 
           SUM(CASE WHEN order_status = 'Success' THEN amount ELSE 0 END) as gmv 
    FROM orders 
    WHERE created_at >= '$startDate' 
    GROUP BY DATE(created_at)
")->fetchAll();
foreach ($orderStats as $s) {
    if (isset($dailyData[$s['date']])) {
        $dailyData[$s['date']]['orders'] = $s['count'];
        $dailyData[$s['date']]['gmv'] = $s['gmv'];
        $dailyData[$s['date']]['revenue'] = ($s['gmv'] * $commissionRate) / 100;
    }
}

// Top sellers
$topSellers = $pdo->query("
    SELECT u.id, u.full_name, u.email,
           COUNT(DISTINCT p.id) as listings,
           COUNT(DISTINCT o.id) as sales,
           COALESCE(SUM(CASE WHEN o.order_status = 'Success' THEN o.amount ELSE 0 END), 0) as revenue
    FROM users u
    LEFT JOIN products p ON u.id = p.user_id
    LEFT JOIN orders o ON p.id = o.product_id
    GROUP BY u.id
    HAVING listings > 0
    ORDER BY revenue DESC
    LIMIT 5
")->fetchAll();

// Category performance
$categoryStats = $pdo->query("
    SELECT p.category, COUNT(*) as listings, 
           COALESCE(SUM(CASE WHEN o.order_status = 'Success' THEN 1 ELSE 0 END), 0) as sales
    FROM products p
    LEFT JOIN orders o ON p.id = o.product_id
    GROUP BY p.category
    ORDER BY listings DESC
    LIMIT 8
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { 
            --primary: #6B21A8; 
            --bg: #f8f9fa; 
            --sidebar-bg: #1a1a1a;
            --text-light: #a1a1aa;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; display:flex; color: #333; }
        
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; padding: 2rem 1.5rem; color: white; z-index: 100; display: flex; flex-direction: column; }
        .brand { font-size: 1.2rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem; text-decoration: none; }
        
        
        .menu-item ion-icon { font-size: 1.2rem; }
        
        .main-content { margin-left: 260px; padding: 2.5rem 3rem; width: calc(100% - 260px); min-height: 100vh; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }
        
        .range-selector {
            display: flex;
            gap: 0.5rem;
        }
        
        .range-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e5e5;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            color: #666;
            transition: all 0.2s;
        }
        
        .range-btn:hover { border-color: #6B21A8; color: #6B21A8; }
        .range-btn.active { background: #6B21A8; color: white; border-color: #6B21A8; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
        }
        
        .stat-card .icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-card .icon.purple { background: #f3e8ff; color: #6B21A8; }
        .stat-card .icon.green { background: #dcfce7; color: #22c55e; }
        .stat-card .icon.blue { background: #dbeafe; color: #2563eb; }
        .stat-card .icon.orange { background: #fef3c7; color: #d97706; }
        
        .stat-card .label { color: #666; font-size: 0.85rem; margin-bottom: 0.25rem; }
        .stat-card .value { font-size: 2rem; font-weight: 700; color: #1a1a1a; }
        .stat-card .change { font-size: 0.8rem; color: #22c55e; margin-top: 0.5rem; }
        .stat-card .change.negative { color: #ef4444; }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
        }
        
        .chart-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tables-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .table-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
        }
        
        .table-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .seller-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .seller-item:last-child { border-bottom: none; }
        
        .seller-rank {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #f3e8ff;
            color: #6B21A8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
        }
        
        .seller-info { flex: 1; }
        .seller-name { font-weight: 600; color: #1a1a1a; }
        .seller-email { font-size: 0.8rem; color: #999; }
        .seller-stats { text-align: right; }
        .seller-revenue { font-weight: 700; color: #22c55e; }
        .seller-count { font-size: 0.8rem; color: #999; }
        
        .category-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .category-item:last-child { border-bottom: none; }
        
        .category-bar {
            flex: 1;
            height: 8px;
            background: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .category-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #6B21A8, #a855f7);
            border-radius: 4px;
        }
        
        .category-name { width: 100px; font-weight: 500; color: #333; font-size: 0.9rem; }
        .category-count { width: 60px; text-align: right; font-size: 0.85rem; color: #666; }
        
        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .charts-grid { grid-template-columns: 1fr; }
            .tables-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <div>
                <h1>Analytics</h1>
                <p style="color:#666; margin-top:0.5rem;">Performance metrics and insights</p>
            </div>
            <div class="range-selector">
                <a href="?range=7" class="range-btn <?php echo $range == '7' ? 'active' : ''; ?>">7 Days</a>
                <a href="?range=30" class="range-btn <?php echo $range == '30' ? 'active' : ''; ?>">30 Days</a>
                <a href="?range=90" class="range-btn <?php echo $range == '90' ? 'active' : ''; ?>">90 Days</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon purple"><ion-icon name="people-outline"></ion-icon></div>
                <div class="label">Total Users</div>
                <div class="value"><?php echo number_format($totalUsers); ?></div>
                <div class="change">+<?php echo $newUsers; ?> this period</div>
            </div>
            <div class="stat-card">
                <div class="icon blue"><ion-icon name="pricetags-outline"></ion-icon></div>
                <div class="label">Total Listings</div>
                <div class="value"><?php echo number_format($totalListings); ?></div>
                <div class="change">+<?php echo $newListings; ?> this period</div>
            </div>
            <div class="stat-card">
                <div class="icon orange"><ion-icon name="bag-check-outline"></ion-icon></div>
                <div class="label">Transaction Value</div>
                <div class="value">₹<?php echo number_format($totalTxnValue); ?></div>
                <div class="change">+₹<?php echo number_format($newTxnValue); ?> this period</div>
            </div>
            <div class="stat-card">
                <div class="icon green"><ion-icon name="wallet-outline"></ion-icon></div>
                <div class="label">Net Revenue (<?php echo $commissionRate; ?>%)</div>
                <div class="value">₹<?php echo number_format($totalRevenue); ?></div>
                <div class="change">+₹<?php echo number_format($newRevenue); ?> this period</div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-title"><ion-icon name="trending-up-outline" style="color:#6B21A8;"></ion-icon> Revenue & Orders</div>
                <canvas id="revenueChart" height="100"></canvas>
            </div>
            <div class="chart-card">
                <div class="chart-title"><ion-icon name="pie-chart-outline" style="color:#6B21A8;"></ion-icon> Users vs Listings</div>
                <canvas id="growthChart" height="200"></canvas>
            </div>
        </div>

        <div class="tables-grid">
            <div class="table-card">
                <div class="table-title"><ion-icon name="trophy-outline" style="color:#d97706;"></ion-icon> Top Sellers</div>
                <?php if(count($topSellers) > 0): ?>
                    <?php $rank = 1; foreach($topSellers as $seller): ?>
                    <div class="seller-item">
                        <div class="seller-rank"><?php echo $rank++; ?></div>
                        <div class="seller-info">
                            <div class="seller-name"><?php echo htmlspecialchars($seller['full_name']); ?></div>
                            <div class="seller-email"><?php echo htmlspecialchars($seller['email']); ?></div>
                        </div>
                        <div class="seller-stats">
                            <div class="seller-revenue">₹<?php echo number_format($seller['revenue']); ?></div>
                            <div class="seller-count"><?php echo $seller['sales']; ?> sales</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#999; text-align:center; padding:2rem;">No sales data yet</p>
                <?php endif; ?>
            </div>

            <div class="table-card">
                <div class="table-title"><ion-icon name="grid-outline" style="color:#6B21A8;"></ion-icon> Category Performance</div>
                <?php 
                $maxListings = max(array_column($categoryStats, 'listings')) ?: 1;
                foreach($categoryStats as $cat): 
                    $percent = ($cat['listings'] / $maxListings) * 100;
                ?>
                <div class="category-item">
                    <div class="category-name"><?php echo htmlspecialchars($cat['category'] ?? 'Other'); ?></div>
                    <div class="category-bar">
                        <div class="category-bar-fill" style="width: <?php echo $percent; ?>%"></div>
                    </div>
                    <div class="category-count"><?php echo $cat['listings']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
        const dailyData = <?php echo json_encode(array_values($dailyData)); ?>;
        
        // Revenue & Orders Chart
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: dailyData.map(d => d.date),
                datasets: [{
                    label: 'Txn Value (₹)',
                    data: dailyData.map(d => d.gmv),
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Net Revenue (₹)',
                    data: dailyData.map(d => d.revenue),
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderDash: [5, 5]
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // Growth Chart
        new Chart(document.getElementById('growthChart'), {
            type: 'doughnut',
            data: {
                labels: ['Users', 'Listings', 'Orders'],
                datasets: [{
                    data: [<?php echo $totalUsers; ?>, <?php echo $totalListings; ?>, <?php echo $totalOrders; ?>],
                    backgroundColor: ['#6B21A8', '#2563eb', '#22c55e'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    </script>
</body>
</html>
