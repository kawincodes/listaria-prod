<?php
require_once __DIR__ . '/includes/session.php';
require 'includes/db.php';
require_once 'includes/config.php';

$activePage = 'logs';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$activeTab = $_GET['tab'] ?? 'error';
if (!in_array($activeTab, ['error', 'activity', 'login', 'user_ips'])) {
    $activeTab = 'error';
}

$errorLogLines = [];
if ($activeTab === 'error') {
    $logPaths = [
        ini_get('error_log'),
        __DIR__ . '/error.log',
        __DIR__ . '/php_errors.log',
        '/var/log/php_errors.log',
        '/var/log/apache2/error.log',
        '/var/log/nginx/error.log',
        sys_get_temp_dir() . '/php_errors.log',
    ];

    $logFile = null;
    foreach ($logPaths as $path) {
        if ($path && file_exists($path) && is_readable($path)) {
            $logFile = $path;
            break;
        }
    }

    $maxLines = 200;
    if ($logFile) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            $errorLogLines = array_slice($lines, -$maxLines);
            $errorLogLines = array_reverse($errorLogLines);
        }
    }
    $logFilePath = $logFile ?: 'Not found';
}

$userIpRows = [];
$userIpSearch = '';
if ($activeTab === 'user_ips') {
    $userIpSearch = trim($_GET['search'] ?? '');
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                ip_address VARCHAR(45),
                user_agent TEXT,
                status VARCHAR(20) DEFAULT 'success',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    } catch (Exception $e) {}

    try {
        $whereIp = '';
        $ipParams = [];
        if ($userIpSearch !== '') {
            $whereIp = "WHERE (u.full_name LIKE ? OR u.email LIKE ? OR ll.ip_address LIKE ?)";
            $ipParams = ["%$userIpSearch%", "%$userIpSearch%", "%$userIpSearch%"];
        }
        $stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.full_name,
                u.email,
                u.role,
                u.is_admin,
                ll.ip_address AS last_ip,
                ll.last_login,
                ll.login_count,
                ll.failed_count
            FROM users u
            LEFT JOIN (
                SELECT 
                    user_id,
                    ip_address,
                    MAX(created_at) AS last_login,
                    SUM(CASE WHEN status = 'success' OR status IS NULL THEN 1 ELSE 0 END) AS login_count,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_count
                FROM login_logs
                GROUP BY user_id, ip_address
            ) ll ON u.id = ll.user_id
            $whereIp
            ORDER BY ll.last_login DESC NULLS LAST, u.id ASC
        ");
        $stmt->execute($ipParams);
        $userIpRows = $stmt->fetchAll();
    } catch (Exception $e) {
        $userIpRows = [];
    }
}

$activityLogs = [];
$activityPage = 1;
$activityTotalPages = 1;
if ($activeTab === 'activity') {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_id INTEGER NOT NULL,
                action VARCHAR(100) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    } catch (Exception $e) {}

    $perPage = 50;
    $activityPage = max(1, intval($_GET['page'] ?? 1));
    $offset = ($activityPage - 1) * $perPage;
    $search = trim($_GET['search'] ?? '');

    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = "(u.full_name LIKE ? OR al.action LIKE ? OR al.details LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    try {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM admin_activity_logs al LEFT JOIN users u ON al.admin_id = u.id $whereClause");
        $countStmt->execute($params);
        $totalLogs = $countStmt->fetchColumn();
        $activityTotalPages = max(1, ceil($totalLogs / $perPage));

        $stmt = $pdo->prepare("SELECT al.*, u.full_name, u.email FROM admin_activity_logs al LEFT JOIN users u ON al.admin_id = u.id $whereClause ORDER BY al.created_at DESC LIMIT $perPage OFFSET $offset");
        $stmt->execute($params);
        $activityLogs = $stmt->fetchAll();
    } catch (Exception $e) {
        $activityLogs = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root {
            --primary: #6B21A8;
            --primary-dark: #581c87;
            --bg: #f8f9fa;
            --sidebar-bg: #1a1a1a;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); display: flex; color: #333; }

        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; padding: 0.5rem 0; color: white; z-index: 100; }
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
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .header h1 { font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }

        .tabs {
            display: flex;
            gap: 4px;
            background: white;
            border-radius: 12px;
            padding: 4px;
            border: 1px solid #f0f0f0;
            margin-bottom: 1.5rem;
        }
        .tab-btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 0.88rem;
            font-weight: 500;
            cursor: pointer;
            color: #64748b;
            background: transparent;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .tab-btn:hover {
            color: var(--primary);
            background: #faf5ff;
        }
        .tab-btn.active {
            background: var(--primary);
            color: white;
            font-weight: 600;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        .card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .card-header h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .log-container {
            max-height: 70vh;
            overflow-y: auto;
            padding: 0;
            scrollbar-width: thin;
        }
        .log-container::-webkit-scrollbar { width: 6px; }
        .log-container::-webkit-scrollbar-thumb { background: #ddd; border-radius: 3px; }

        .log-entry {
            padding: 0.6rem 1.5rem;
            font-family: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace;
            font-size: 0.78rem;
            line-height: 1.5;
            border-bottom: 1px solid #f8f8f8;
            word-break: break-all;
            color: #334155;
        }
        .log-entry:hover {
            background: #fafbfc;
        }
        .log-entry.log-error {
            background: #fef2f2;
            border-left: 3px solid #ef4444;
        }
        .log-entry.log-warning {
            background: #fffbeb;
            border-left: 3px solid #f59e0b;
        }
        .log-entry.log-notice {
            background: #eff6ff;
            border-left: 3px solid #3b82f6;
        }
        .log-entry.log-fatal {
            background: #fef2f2;
            border-left: 3px solid #dc2626;
            color: #dc2626;
            font-weight: 600;
        }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th {
            background: #f8fafc;
            text-align: left;
            padding: 0.75rem 1rem;
            font-weight: 600;
            color: #64748b;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }
        td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        tr:hover td { background: #fafbfc; }
        tr:last-child td { border-bottom: none; }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-action { background: rgba(107,33,168,0.1); color: #6B21A8; }

        .search-box {
            display: flex;
            gap: 6px;
        }
        .search-box input {
            padding: 0.5rem 0.85rem;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: inherit;
            width: 220px;
        }
        .search-box input:focus { outline: none; border-color: #6B21A8; }
        .search-box button {
            padding: 0.5rem 1rem;
            background: #6B21A8;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
        }
        .empty-state ion-icon { font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; }
        .empty-state h3 { margin: 0 0 0.5rem; color: #334155; }
        .empty-state p { color: #94a3b8; margin: 0; }

        .info-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .info-tag {
            background: white;
            border: 1px solid #f0f0f0;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.82rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .info-tag strong { color: #1a1a1a; }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 4px;
            padding: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .pagination a, .pagination span {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            color: #64748b;
            border: 1px solid #e5e5e5;
        }
        .pagination a:hover { background: #f5f5f5; }
        .pagination .active { background: #6B21A8; color: white; border-color: #6B21A8; }
        .pagination .disabled { opacity: 0.4; pointer-events: none; }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.82rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }
        .btn-outline { background: white; color: #555; border: 1px solid #e5e5e5; }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; width: 100%; padding: 1.5rem; padding-top: 72px; }
            .tabs { flex-wrap: wrap; }
            .search-box { width: 100%; }
            .search-box input { width: 100%; }
        }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <div>
                <h1><ion-icon name="terminal-outline" style="vertical-align:middle;margin-right:8px;color:var(--primary);"></ion-icon> Logs</h1>
                <p style="color:#666; margin-top:0.5rem;">View server error logs and activity logs</p>
            </div>
        </div>

        <div class="tabs">
            <a href="admin_logs.php?tab=error" class="tab-btn <?php echo $activeTab === 'error' ? 'active' : ''; ?>">
                <ion-icon name="bug-outline"></ion-icon> Error Log
            </a>
            <a href="admin_logs.php?tab=activity" class="tab-btn <?php echo $activeTab === 'activity' ? 'active' : ''; ?>">
                <ion-icon name="time-outline"></ion-icon> Activity Log
            </a>
            <a href="admin_login_logs.php" class="tab-btn">
                <ion-icon name="finger-print-outline"></ion-icon> Login Log
            </a>
            <a href="admin_logs.php?tab=user_ips" class="tab-btn <?php echo $activeTab === 'user_ips' ? 'active' : ''; ?>">
                <ion-icon name="location-outline"></ion-icon> User IPs
            </a>
        </div>

        <?php if ($activeTab === 'error'): ?>

            <div class="info-bar">
                <div class="info-tag">
                    <ion-icon name="document-outline"></ion-icon>
                    Log File: <strong><?php echo htmlspecialchars($logFilePath); ?></strong>
                </div>
                <div class="info-tag">
                    <ion-icon name="list-outline"></ion-icon>
                    Showing: <strong><?php echo count($errorLogLines); ?></strong> recent entries
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><ion-icon name="bug-outline"></ion-icon> PHP Error Log</h3>
                    <a href="admin_logs.php?tab=error" class="btn btn-outline">
                        <ion-icon name="refresh-outline"></ion-icon> Refresh
                    </a>
                </div>
                <?php if (empty($errorLogLines)): ?>
                    <div class="empty-state">
                        <ion-icon name="checkmark-circle-outline"></ion-icon>
                        <h3>No Error Logs Found</h3>
                        <p>No PHP error log file was found or it is empty. This is a good sign!</p>
                    </div>
                <?php else: ?>
                    <div class="log-container">
                        <?php foreach ($errorLogLines as $line): ?>
                            <?php
                                $logClass = '';
                                $lineLower = strtolower($line);
                                if (strpos($lineLower, 'fatal') !== false) {
                                    $logClass = 'log-fatal';
                                } elseif (strpos($lineLower, 'error') !== false || strpos($lineLower, 'exception') !== false) {
                                    $logClass = 'log-error';
                                } elseif (strpos($lineLower, 'warning') !== false || strpos($lineLower, 'deprecated') !== false) {
                                    $logClass = 'log-warning';
                                } elseif (strpos($lineLower, 'notice') !== false || strpos($lineLower, 'info') !== false) {
                                    $logClass = 'log-notice';
                                }
                            ?>
                            <div class="log-entry <?php echo $logClass; ?>"><?php echo htmlspecialchars($line); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($activeTab === 'activity'): ?>

            <?php $search = trim($_GET['search'] ?? ''); ?>
            <div style="margin-bottom:1rem;">
                <form class="search-box" method="GET">
                    <input type="hidden" name="tab" value="activity">
                    <input type="text" name="search" placeholder="Search action, user, or details..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><ion-icon name="search-outline" style="vertical-align:middle;"></ion-icon> Search</button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><ion-icon name="time-outline"></ion-icon> Admin Activity Log</h3>
                    <a href="admin_logs.php?tab=activity" class="btn btn-outline">
                        <ion-icon name="refresh-outline"></ion-icon> Refresh
                    </a>
                </div>
                <?php if (empty($activityLogs)): ?>
                    <div class="empty-state">
                        <ion-icon name="time-outline"></ion-icon>
                        <h3>No Activity Logs Found</h3>
                        <p>Admin activity will appear here as actions are performed.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Admin</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>IP Address</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activityLogs as $log): ?>
                                <tr>
                                    <td style="color:#94a3b8;">#<?php echo $log['id']; ?></td>
                                    <td>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($log['full_name'] ?? 'Unknown'); ?></div>
                                        <div style="font-size:0.75rem;color:#94a3b8;"><?php echo htmlspecialchars($log['email'] ?? ''); ?></div>
                                    </td>
                                    <td><span class="badge badge-action"><?php echo htmlspecialchars($log['action']); ?></span></td>
                                    <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.82rem;color:#64748b;" title="<?php echo htmlspecialchars($log['details'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($log['details'] ?? '-'); ?>
                                    </td>
                                    <td style="font-family:monospace;font-size:0.82rem;"><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                                    <td style="white-space:nowrap;font-size:0.82rem;"><?php echo date('M j, Y h:i A', strtotime($log['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($activityTotalPages > 1): ?>
                    <div class="pagination">
                        <?php
                        $qp = ['tab' => 'activity'];
                        if ($search) $qp['search'] = $search;
                        ?>
                        <a href="?<?php echo http_build_query(array_merge($qp, ['page' => 1])); ?>" class="<?php echo $activityPage <= 1 ? 'disabled' : ''; ?>">&laquo;</a>
                        <a href="?<?php echo http_build_query(array_merge($qp, ['page' => max(1, $activityPage - 1)])); ?>" class="<?php echo $activityPage <= 1 ? 'disabled' : ''; ?>">&lsaquo;</a>
                        <?php
                        $start = max(1, $activityPage - 2);
                        $end = min($activityTotalPages, $activityPage + 2);
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                        <a href="?<?php echo http_build_query(array_merge($qp, ['page' => $i])); ?>" class="<?php echo $i === $activityPage ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <a href="?<?php echo http_build_query(array_merge($qp, ['page' => min($activityTotalPages, $activityPage + 1)])); ?>" class="<?php echo $activityPage >= $activityTotalPages ? 'disabled' : ''; ?>">&rsaquo;</a>
                        <a href="?<?php echo http_build_query(array_merge($qp, ['page' => $activityTotalPages])); ?>" class="<?php echo $activityPage >= $activityTotalPages ? 'disabled' : ''; ?>">&raquo;</a>
                        <span style="border:none;color:#94a3b8;font-size:0.8rem;">Page <?php echo $activityPage; ?> of <?php echo $activityTotalPages; ?></span>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        <?php elseif ($activeTab === 'user_ips'): ?>

            <div style="margin-bottom:1rem;">
                <form class="search-box" method="GET">
                    <input type="hidden" name="tab" value="user_ips">
                    <input type="text" name="search" placeholder="Search by name, email, or IP address..." value="<?php echo htmlspecialchars($userIpSearch); ?>">
                    <button type="submit"><ion-icon name="search-outline" style="vertical-align:middle;"></ion-icon> Search</button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><ion-icon name="location-outline"></ion-icon> User IP Log</h3>
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <span style="font-size:0.82rem;color:#94a3b8;"><?php echo count($userIpRows); ?> users</span>
                        <a href="admin_logs.php?tab=user_ips" class="btn btn-outline">
                            <ion-icon name="refresh-outline"></ion-icon> Refresh
                        </a>
                    </div>
                </div>
                <?php if (empty($userIpRows)): ?>
                    <div class="empty-state">
                        <ion-icon name="location-outline"></ion-icon>
                        <h3>No IP Data Found</h3>
                        <p>User IP addresses are captured when users log in. No login data is available yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Last Known IP</th>
                                    <th>Successful Logins</th>
                                    <th>Failed Attempts</th>
                                    <th>Last Login</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userIpRows as $row): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($row['full_name'] ?? 'Unknown'); ?></div>
                                        <div style="font-size:0.75rem;color:#94a3b8;"><?php echo htmlspecialchars($row['email'] ?? ''); ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['is_admin'])): ?>
                                            <span class="badge" style="background:#fef3c7;color:#92400e;"><?php echo htmlspecialchars(ucfirst($row['role'] ?? 'admin')); ?></span>
                                        <?php else: ?>
                                            <span class="badge" style="background:#f0f9ff;color:#0369a1;">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['last_ip'])): ?>
                                            <span style="font-family:monospace;font-size:0.85rem;background:#f8fafc;padding:3px 8px;border-radius:6px;border:1px solid #e2e8f0;">
                                                <?php echo htmlspecialchars($row['last_ip']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#94a3b8;font-size:0.82rem;">No login recorded</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['login_count'] > 0): ?>
                                            <span class="badge" style="background:#f0fdf4;color:#166534;"><?php echo intval($row['login_count']); ?></span>
                                        <?php else: ?>
                                            <span style="color:#94a3b8;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['failed_count'] > 0): ?>
                                            <span class="badge" style="background:#fef2f2;color:#991b1b;"><?php echo intval($row['failed_count']); ?></span>
                                        <?php else: ?>
                                            <span style="color:#94a3b8;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="white-space:nowrap;font-size:0.82rem;">
                                        <?php echo !empty($row['last_login']) ? date('M j, Y h:i A', strtotime($row['last_login'])) : '<span style="color:#94a3b8;">Never</span>'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </main>
</body>
</html>
