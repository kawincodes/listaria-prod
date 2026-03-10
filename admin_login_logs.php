<?php
require_once __DIR__ . '/includes/session.php';
require 'includes/db.php';

$activePage = 'login_logs';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$filter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($filter === 'success') {
    $where[] = "l.login_status = 'success'";
} elseif ($filter === 'failed') {
    $where[] = "l.login_status IN ('failed', 'failed_unverified')";
}

if ($search !== '') {
    $where[] = "(l.email LIKE ? OR l.ip_address LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM login_logs l LEFT JOIN users u ON l.user_id = u.id $whereClause");
$countStmt->execute($params);
$totalLogs = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalLogs / $perPage));

$stmt = $pdo->prepare("SELECT l.*, u.full_name, u.account_type, u.is_admin FROM login_logs l LEFT JOIN users u ON l.user_id = u.id $whereClause ORDER BY l.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$totalAll = $pdo->query("SELECT COUNT(*) FROM login_logs")->fetchColumn();
$totalSuccess = $pdo->query("SELECT COUNT(*) FROM login_logs WHERE login_status = 'success'")->fetchColumn();
$totalFailed = $pdo->query("SELECT COUNT(*) FROM login_logs WHERE login_status IN ('failed', 'failed_unverified')")->fetchColumn();
$uniqueIPs = $pdo->query("SELECT COUNT(DISTINCT ip_address) FROM login_logs")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Logs - Listaria Admin</title>
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

        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card {
            background: white; border-radius: 12px; padding: 1.2rem 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #f0f0f0;
            display: flex; align-items: center; gap: 12px;
        }
        .stat-icon {
            width: 42px; height: 42px; border-radius: 10px; display: flex;
            align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;
        }
        .stat-value { font-size: 1.4rem; font-weight: 700; color: #1a1a1a; line-height: 1; }
        .stat-label { font-size: 0.78rem; color: #64748b; margin-top: 2px; }

        .filter-bar {
            display: flex; gap: 0.75rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;
        }
        .filter-bar a {
            padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none;
            font-size: 0.85rem; font-weight: 500; color: #64748b; background: white;
            border: 1px solid #e5e5e5; transition: all 0.2s;
        }
        .filter-bar a:hover { border-color: #6B21A8; color: #6B21A8; }
        .filter-bar a.active { background: #6B21A8; color: white; border-color: #6B21A8; }
        .search-box {
            margin-left: auto; display: flex; gap: 6px;
        }
        .search-box input {
            padding: 0.5rem 0.85rem; border: 1px solid #e5e5e5; border-radius: 8px;
            font-size: 0.85rem; font-family: inherit; width: 220px;
        }
        .search-box input:focus { outline: none; border-color: #6B21A8; }
        .search-box button {
            padding: 0.5rem 1rem; background: #6B21A8; color: white; border: none;
            border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 600;
        }

        .card {
            background: white; border-radius: 16px; padding: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #f0f0f0;
            margin-bottom: 1.5rem; overflow: hidden;
        }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th { background: #f8fafc; text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #64748b; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; }
        td { padding: 0.75rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:hover td { background: #fafbfc; }
        tr:last-child td { border-bottom: none; }

        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: 0.72rem; font-weight: 600; text-transform: uppercase;
        }
        .badge-success { background: rgba(34,197,94,0.1); color: #22c55e; }
        .badge-failed { background: rgba(239,68,68,0.1); color: #ef4444; }
        .badge-warning { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .badge-admin { background: rgba(107,33,168,0.1); color: #6B21A8; }
        .badge-vendor { background: rgba(59,130,246,0.1); color: #3b82f6; }

        .user-agent-cell {
            max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
            font-size: 0.78rem; color: #94a3b8; cursor: help;
        }

        .pagination {
            display: flex; justify-content: center; gap: 4px; padding: 1rem;
            align-items: center; flex-wrap: wrap;
        }
        .pagination a, .pagination span {
            padding: 0.4rem 0.8rem; border-radius: 6px; text-decoration: none;
            font-size: 0.85rem; font-weight: 500; color: #64748b; border: 1px solid #e5e5e5;
        }
        .pagination a:hover { background: #f5f5f5; }
        .pagination .active { background: #6B21A8; color: white; border-color: #6B21A8; }
        .pagination .disabled { opacity: 0.4; pointer-events: none; }

        .empty-state { text-align: center; padding: 3rem 2rem; }
        .empty-state ion-icon { font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; }
        .empty-state h3 { margin: 0 0 0.5rem; color: #334155; }
        .empty-state p { color: #94a3b8; margin: 0; }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; width: 100%; padding: 1.5rem; }
            .search-box { margin-left: 0; width: 100%; }
            .search-box input { width: 100%; }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <div>
                <h1>Login Logs</h1>
                <p style="color:#666; margin-top:0.5rem;">Track all login attempts across the platform</p>
            </div>
            <div>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(107,33,168,0.1); color:#6B21A8;">
                    <ion-icon name="log-in-outline"></ion-icon>
                </div>
                <div>
                    <div class="stat-value"><?php echo number_format($totalAll); ?></div>
                    <div class="stat-label">Total Attempts</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(34,197,94,0.1); color:#22c55e;">
                    <ion-icon name="checkmark-circle-outline"></ion-icon>
                </div>
                <div>
                    <div class="stat-value"><?php echo number_format($totalSuccess); ?></div>
                    <div class="stat-label">Successful</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(239,68,68,0.1); color:#ef4444;">
                    <ion-icon name="close-circle-outline"></ion-icon>
                </div>
                <div>
                    <div class="stat-value"><?php echo number_format($totalFailed); ?></div>
                    <div class="stat-label">Failed</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(59,130,246,0.1); color:#3b82f6;">
                    <ion-icon name="globe-outline"></ion-icon>
                </div>
                <div>
                    <div class="stat-value"><?php echo number_format($uniqueIPs); ?></div>
                    <div class="stat-label">Unique IPs</div>
                </div>
            </div>
        </div>

        <div class="filter-bar">
            <a href="admin_login_logs.php" class="<?php echo $filter === '' ? 'active' : ''; ?>">All</a>
            <a href="admin_login_logs.php?status=success" class="<?php echo $filter === 'success' ? 'active' : ''; ?>">Success</a>
            <a href="admin_login_logs.php?status=failed" class="<?php echo $filter === 'failed' ? 'active' : ''; ?>">Failed</a>
            <form class="search-box" method="GET">
                <?php if ($filter): ?><input type="hidden" name="status" value="<?php echo htmlspecialchars($filter); ?>"><?php endif; ?>
                <input type="text" name="search" placeholder="Search email, IP, or name..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><ion-icon name="search-outline" style="vertical-align:middle;"></ion-icon></button>
            </form>
        </div>

        <div class="card">
            <?php if (empty($logs)): ?>
                <div class="empty-state">
                    <ion-icon name="finger-print-outline"></ion-icon>
                    <h3>No Login Logs Found</h3>
                    <p>Login attempts will appear here once users start logging in.</p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>IP Address</th>
                                <th>Status</th>
                                <th>User Agent</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td style="color:#94a3b8;">#<?php echo $log['id']; ?></td>
                                <td>
                                    <?php if ($log['full_name']): ?>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($log['full_name']); ?></div>
                                        <?php if ($log['is_admin']): ?>
                                            <span class="badge badge-admin">Admin</span>
                                        <?php elseif (($log['account_type'] ?? '') === 'vendor'): ?>
                                            <span class="badge badge-vendor">Vendor</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:#94a3b8;">Unknown</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['email'] ?? '-'); ?></td>
                                <td style="font-family:monospace; font-size:0.82rem;"><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($log['login_status'] === 'success'): ?>
                                        <span class="badge badge-success">Success</span>
                                    <?php elseif ($log['login_status'] === 'failed_unverified'): ?>
                                        <span class="badge badge-warning">Unverified</span>
                                    <?php else: ?>
                                        <span class="badge badge-failed">Failed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="user-agent-cell" title="<?php echo htmlspecialchars($log['user_agent'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($log['user_agent'] ?? '-'); ?>
                                </td>
                                <td style="white-space:nowrap; font-size:0.82rem;"><?php echo date('M j, Y h:i A', strtotime($log['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php
                    $qp = [];
                    if ($filter) $qp['status'] = $filter;
                    if ($search) $qp['search'] = $search;
                    ?>
                    <a href="?<?php echo http_build_query(array_merge($qp, ['page' => 1])); ?>" class="<?php echo $page <= 1 ? 'disabled' : ''; ?>">&laquo;</a>
                    <a href="?<?php echo http_build_query(array_merge($qp, ['page' => max(1, $page - 1)])); ?>" class="<?php echo $page <= 1 ? 'disabled' : ''; ?>">&lsaquo;</a>
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                    <a href="?<?php echo http_build_query(array_merge($qp, ['page' => $i])); ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <a href="?<?php echo http_build_query(array_merge($qp, ['page' => min($totalPages, $page + 1)])); ?>" class="<?php echo $page >= $totalPages ? 'disabled' : ''; ?>">&rsaquo;</a>
                    <a href="?<?php echo http_build_query(array_merge($qp, ['page' => $totalPages])); ?>" class="<?php echo $page >= $totalPages ? 'disabled' : ''; ?>">&raquo;</a>
                    <span style="border:none; color:#94a3b8; font-size:0.8rem;">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
