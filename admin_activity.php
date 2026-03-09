<?php
session_start();
require 'includes/db.php';

$activePage = 'activity';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$isSuperAdmin = ($_SESSION['role'] ?? '') === 'super_admin';
if (!$isSuperAdmin) {
    header("Location: admin_dashboard.php?error=unauthorized");
    exit;
}

// Create activity logs table if not exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin_id (admin_id),
            INDEX idx_created_at (created_at)
        )
    ");
} catch(Exception $e) {}

// Fetch activity logs
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "
    SELECT al.*, u.full_name, u.email, u.role 
    FROM admin_activity_logs al
    JOIN users u ON al.admin_id = u.id
    WHERE 1=1
";

if ($filter !== 'all') {
    $sql .= " AND al.action LIKE :filter";
}
if ($search) {
    $sql .= " AND (u.full_name LIKE :search OR al.action LIKE :search OR al.details LIKE :search)";
}

$sql .= " ORDER BY al.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
if ($filter !== 'all') {
    $stmt->bindValue(':filter', "%$filter%");
}
if ($search) {
    $stmt->bindValue(':search', "%$search%");
}
$stmt->execute();
$logs = $stmt->fetchAll();

// Get action types for filter
$actionTypes = $pdo->query("SELECT DISTINCT action FROM admin_activity_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Logs - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
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
        
        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 0.6rem 1rem;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            font-size: 0.9rem;
            background: white;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #6B21A8;
        }
        
        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-primary { background: #6B21A8; color: white; }
        .btn-primary:hover { background: #581c87; }
        
        .timeline {
            position: relative;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e5e5e5;
        }
        
        .log-item {
            display: flex;
            gap: 1.5rem;
            padding: 1rem 0;
            position: relative;
        }
        
        .log-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            z-index: 1;
            flex-shrink: 0;
        }
        
        .log-icon.login { background: #dbeafe; color: #2563eb; }
        .log-icon.user { background: #f3e8ff; color: #6B21A8; }
        .log-icon.listing { background: #dcfce7; color: #22c55e; }
        .log-icon.delete { background: #fee2e2; color: #ef4444; }
        .log-icon.settings { background: #fef3c7; color: #d97706; }
        .log-icon.default { background: #f0f0f0; color: #666; }
        
        .log-content {
            flex: 1;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
        }
        
        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .log-action {
            font-weight: 600;
            color: #1a1a1a;
        }
        
        .log-time {
            font-size: 0.8rem;
            color: #999;
        }
        
        .log-admin {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .log-admin span {
            background: #f3e8ff;
            color: #6B21A8;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 6px;
        }
        
        .log-details {
            font-size: 0.85rem;
            color: #666;
            background: #fafafa;
            padding: 0.75rem;
            border-radius: 6px;
            margin-top: 0.5rem;
        }
        
        .log-meta {
            display: flex;
            gap: 1rem;
            margin-top: 0.75rem;
            font-size: 0.8rem;
            color: #999;
        }
        
        .log-meta ion-icon {
            vertical-align: middle;
            margin-right: 4px;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #999;
        }
        
        .empty-state ion-icon {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.25rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
        }
        
        .stat-card .label { color: #666; font-size: 0.8rem; margin-bottom: 0.25rem; }
        .stat-card .value { font-size: 1.5rem; font-weight: 700; color: #1a1a1a; }
        
        @media (max-width: 1024px) {
            .stats-row { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <div>
                <h1>Activity Logs</h1>
                <p style="color:#666; margin-top:0.5rem;">Track all admin actions and changes</p>
            </div>
        </div>

        <?php
        $todayLogs = $pdo->query("SELECT COUNT(*) FROM admin_activity_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        $weekLogs = $pdo->query("SELECT COUNT(*) FROM admin_activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
        $loginCount = $pdo->query("SELECT COUNT(*) FROM admin_activity_logs WHERE action LIKE '%login%'")->fetchColumn();
        $totalAdmins = $pdo->query("SELECT COUNT(DISTINCT admin_id) FROM admin_activity_logs")->fetchColumn();
        ?>
        
        <div class="stats-row">
            <div class="stat-card">
                <div class="label">Today's Actions</div>
                <div class="value"><?php echo number_format($todayLogs); ?></div>
            </div>
            <div class="stat-card">
                <div class="label">This Week</div>
                <div class="value"><?php echo number_format($weekLogs); ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Logins</div>
                <div class="value"><?php echo number_format($loginCount); ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Active Admins</div>
                <div class="value"><?php echo number_format($totalAdmins); ?></div>
            </div>
        </div>

        <form method="GET" class="filters">
            <div class="filter-group">
                <ion-icon name="funnel-outline"></ion-icon>
                <select name="filter">
                    <option value="all">All Actions</option>
                    <option value="login" <?php echo $filter === 'login' ? 'selected' : ''; ?>>Logins</option>
                    <option value="user" <?php echo $filter === 'user' ? 'selected' : ''; ?>>User Actions</option>
                    <option value="listing" <?php echo $filter === 'listing' ? 'selected' : ''; ?>>Listing Actions</option>
                    <option value="settings" <?php echo $filter === 'settings' ? 'selected' : ''; ?>>Settings</option>
                    <option value="delete" <?php echo $filter === 'delete' ? 'selected' : ''; ?>>Deletions</option>
                </select>
            </div>
            <div class="filter-group">
                <ion-icon name="search-outline"></ion-icon>
                <input type="text" name="search" placeholder="Search logs..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <button type="submit" class="btn btn-primary">
                <ion-icon name="filter-outline"></ion-icon>
                Filter
            </button>
        </form>

        <?php if(count($logs) > 0): ?>
        <div class="timeline">
            <?php foreach($logs as $log): 
                $iconClass = 'default';
                $iconName = 'ellipse';
                
                if (stripos($log['action'], 'login') !== false) {
                    $iconClass = 'login';
                    $iconName = 'log-in';
                } elseif (stripos($log['action'], 'user') !== false || stripos($log['action'], 'admin') !== false) {
                    $iconClass = 'user';
                    $iconName = 'person';
                } elseif (stripos($log['action'], 'listing') !== false || stripos($log['action'], 'product') !== false) {
                    $iconClass = 'listing';
                    $iconName = 'pricetag';
                } elseif (stripos($log['action'], 'delete') !== false || stripos($log['action'], 'remove') !== false) {
                    $iconClass = 'delete';
                    $iconName = 'trash';
                } elseif (stripos($log['action'], 'setting') !== false) {
                    $iconClass = 'settings';
                    $iconName = 'settings';
                }
            ?>
            <div class="log-item">
                <div class="log-icon <?php echo $iconClass; ?>">
                    <ion-icon name="<?php echo $iconName; ?>-outline"></ion-icon>
                </div>
                <div class="log-content">
                    <div class="log-header">
                        <div class="log-action"><?php echo htmlspecialchars($log['action']); ?></div>
                        <div class="log-time"><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></div>
                    </div>
                    <div class="log-admin">
                        <?php echo htmlspecialchars($log['full_name']); ?>
                        <span><?php echo strtoupper(str_replace('_', ' ', $log['role'] ?? 'admin')); ?></span>
                    </div>
                    <?php if($log['details']): ?>
                    <div class="log-details"><?php echo htmlspecialchars($log['details']); ?></div>
                    <?php endif; ?>
                    <div class="log-meta">
                        <span><ion-icon name="globe-outline"></ion-icon> <?php echo htmlspecialchars($log['ip_address'] ?? 'Unknown'); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <ion-icon name="document-text-outline"></ion-icon>
            <h3>No Activity Logs</h3>
            <p>Admin actions will appear here when they occur.</p>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
