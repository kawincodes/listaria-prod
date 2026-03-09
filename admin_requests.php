<?php
session_start();
require 'includes/db.php';

$activePage = 'requests';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$msg = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $req_id = $_POST['request_id'] ?? null;
    
    if (isset($_POST['close_request'])) {
        $pdo->prepare("UPDATE product_requests SET status = 'closed' WHERE id = ?")->execute([$req_id]);
        $msg = "Request closed successfully.";
    }
    
    if (isset($_POST['open_request'])) {
        $pdo->prepare("UPDATE product_requests SET status = 'open' WHERE id = ?")->execute([$req_id]);
        $msg = "Request re-opened successfully.";
    }
    
    if (isset($_POST['delete'])) {
        $pdo->prepare("DELETE FROM product_requests WHERE id = ?")->execute([$req_id]);
        $msg = "Request deleted successfully.";
    }
    
    if (isset($_POST['bulk_action']) && !empty($_POST['selected'])) {
        $action = $_POST['bulk_action'];
        $ids = $_POST['selected'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        switch($action) {
            case 'close':
                $pdo->prepare("UPDATE product_requests SET status = 'closed' WHERE id IN ($placeholders)")->execute($ids);
                $msg = count($ids) . " requests closed.";
                break;
            case 'open':
                $pdo->prepare("UPDATE product_requests SET status = 'open' WHERE id IN ($placeholders)")->execute($ids);
                $msg = count($ids) . " requests opened.";
                break;
            case 'delete':
                $pdo->prepare("DELETE FROM product_requests WHERE id IN ($placeholders)")->execute($ids);
                $msg = count($ids) . " requests deleted.";
                break;
        }
    }
    
    // Log activity
    try {
        $pdo->prepare("INSERT INTO admin_activity_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)")
            ->execute([$_SESSION['user_id'], "Request action", $msg, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch(Exception $e) {}
}

// Filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "
    SELECT r.*, u.full_name as requester_name, u.email as requester_email
    FROM product_requests r
    JOIN users u ON r.user_id = u.id
    WHERE 1=1
";

if ($filter === 'open') {
    $sql .= " AND r.status = 'open'";
} elseif ($filter === 'closed') {
    $sql .= " AND r.status = 'closed'";
}

if ($search) {
    $sql .= " AND (r.title LIKE '%$search%' OR r.description LIKE '%$search%' OR u.full_name LIKE '%$search%')";
}

$sql .= " ORDER BY r.created_at DESC";
$requests = $pdo->query($sql)->fetchAll();

// Stats
$totalRequests = $pdo->query("SELECT COUNT(*) FROM product_requests")->fetchColumn();
$openRequests = $pdo->query("SELECT COUNT(*) FROM product_requests WHERE status = 'open'")->fetchColumn();
$closedRequests = $pdo->query("SELECT COUNT(*) FROM product_requests WHERE status = 'closed'")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Requests - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root { --primary: #6B21A8; --bg: #f8f9fa; --sidebar-bg: #1a1a1a; --text-light: #a1a1aa; }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; display:flex; color: #333; }
        
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; padding: 0.5rem 0; color: white; z-index: 100; }
        .brand { font-size: 1.2rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem; text-decoration: none; }
        .main-content { margin-left: 260px; padding: 2rem 2.5rem; width: calc(100% - 260px); min-height: 100vh; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }
        
        .msg-success { background: #f0fdf4; color: #22c55e; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 500; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; max-width: 800px; }
        
        .stat-card { background: white; padding: 1.25rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #f0f0f0; }
        .stat-card .icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 0.75rem; }
        .stat-card .icon.purple { background: #f3e8ff; color: #6B21A8; }
        .stat-card .icon.orange { background: #fef3c7; color: #d97706; }
        .stat-card .icon.green { background: #dcfce7; color: #22c55e; }
        .stat-card .value { font-size: 1.75rem; font-weight: 700; color: #1a1a1a; }
        .stat-card .label { font-size: 0.8rem; color: #666; margin-top: 0.25rem; }
        
        .filters { display: flex; gap: 0.75rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center; }
        
        .filter-btn { padding: 0.6rem 1rem; border: 1px solid #e5e5e5; background: white; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 500; color: #666; transition: all 0.2s; text-decoration: none; }
        .filter-btn:hover { border-color: #6B21A8; color: #6B21A8; }
        .filter-btn.active { background: #6B21A8; color: white; border-color: #6B21A8; }
        
        .search-box { display: flex; gap: 0.5rem; margin-left: auto; }
        .search-box input { padding: 0.6rem 1rem; border: 1px solid #e5e5e5; border-radius: 8px; font-size: 0.9rem; }
        .search-box input:focus { outline: none; border-color: #6B21A8; }
        
        .btn { padding: 0.6rem 1rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: #6B21A8; color: white; }
        .btn-primary:hover { background: #581c87; }
        .btn-dark { background: #1a1a1a; color: white; }
        .btn-sm { padding: 0.4rem 0.75rem; font-size: 0.75rem; }
        
        .table-container { background: white; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #f0f0f0; margin-bottom: 3rem; }
        
        .table-header { padding: 1rem 1.5rem; background: #fafafa; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 1rem; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.25rem; text-align: left; font-size: 0.85rem; }
        th { background: #fafafa; color: #666; font-weight: 600; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.5px; border-bottom: 1px solid #f0f0f0; }
        td { color: #333; border-bottom: 1px solid #f5f5f5; vertical-align: top;}
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 600; text-transform: uppercase; }
        .badge-open { background: #fef3c7; color: #d97706; }
        .badge-closed { background: #f3f4f6; color: #4b5563; }
        
        .checkbox-cell { width: 40px; }
        .checkbox-cell input { width: 16px; height: 16px; accent-color: #6B21A8; }
        
        .actions-dropdown { position: relative; display: inline-block; }
        .dropdown-content { display: none; position: absolute; right: 0; background: white; min-width: 160px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); border-radius: 10px; z-index: 100; padding: 0.5rem; }
        .actions-dropdown:hover .dropdown-content { display: block; }
        .dropdown-item { display: flex; align-items: center; gap: 8px; padding: 0.6rem 0.75rem; border: none; background: none; width: 100%; text-align: left; cursor: pointer; font-size: 0.85rem; border-radius: 6px; color: #333; }
        .dropdown-item:hover { background: #f5f5f5; }
        .dropdown-item.danger { color: #ef4444; }
        .dropdown-item.danger:hover { background: #fef2f2; }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <div>
                <h1>Product Requests</h1>
                <p style="color:#666; margin-top:0.5rem;">Manage community product requests</p>
            </div>
        </div>

        <?php if($msg): ?>
            <div class="msg-success"><ion-icon name="checkmark-circle-outline"></ion-icon> <?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon purple"><ion-icon name="megaphone-outline"></ion-icon></div>
                <div class="value"><?php echo number_format($totalRequests); ?></div>
                <div class="label">Total Requests</div>
            </div>
            <div class="stat-card">
                <div class="icon orange"><ion-icon name="time-outline"></ion-icon></div>
                <div class="value"><?php echo number_format($openRequests); ?></div>
                <div class="label">Open Requests</div>
            </div>
            <div class="stat-card">
                <div class="icon green"><ion-icon name="checkmark-done-outline"></ion-icon></div>
                <div class="value"><?php echo number_format($closedRequests); ?></div>
                <div class="label">Closed Requests</div>
            </div>
        </div>

        <div class="filters">
            <a href="?" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
            <a href="?filter=open" class="filter-btn <?php echo $filter === 'open' ? 'active' : ''; ?>">Open</a>
            <a href="?filter=closed" class="filter-btn <?php echo $filter === 'closed' ? 'active' : ''; ?>">Closed</a>
            
            <form method="GET" class="search-box">
                <input type="text" name="search" placeholder="Search title or user..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary"><ion-icon name="search-outline"></ion-icon></button>
            </form>
        </div>

        <form method="POST" id="bulkForm"></form>
        <div class="table-container">
            <div class="table-header">
                <select name="bulk_action" form="bulkForm" style="padding:0.5rem; border:1px solid #e5e5e5; border-radius:6px;">
                    <option value="">Bulk Actions</option>
                    <option value="close">Mark Closed</option>
                    <option value="open">Mark Open</option>
                    <option value="delete">Delete</option>
                </select>
                <button type="submit" form="bulkForm" class="btn btn-dark btn-sm">Apply</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th class="checkbox-cell"><input type="checkbox" id="selectAll"></th>
                        <th style="width: 30%;">Request Detail</th>
                        <th>Requester</th>
                        <th>Budget</th>
                        <th>Status</th>
                        <th>Date Posted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($requests) > 0): ?>
                        <?php foreach($requests as $req): 
                            $status = $req['status'] ?? 'open';
                        ?>
                        <tr>
                            <td class="checkbox-cell">
                                <input type="checkbox" name="selected[]" form="bulkForm" value="<?php echo $req['id']; ?>">
                            </td>
                            <td>
                                <div>
                                    <div style="font-weight: 600; color: #1a1a1a; margin-bottom: 5px;"><?php echo htmlspecialchars($req['title']); ?></div>
                                    <div style="font-size: 0.8rem; color: #666; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?php echo htmlspecialchars($req['description']); ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($req['requester_name']); ?></div>
                                <div style="font-size: 0.8rem; color: #888;"><?php echo htmlspecialchars($req['requester_email']); ?></div>
                            </td>
                            <td>
                                <?php if($req['budget']): ?>
                                    <span style="font-weight: 600; color: #166534; background: #dcfce7; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;">
                                        ₹<?php echo number_format($req['budget']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 0.8rem;">Not specified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($status); ?>"><?php echo ucfirst($status); ?></span>
                            </td>
                            <td><?php echo date('M j, Y <b\r> H:i', strtotime($req['created_at'])); ?></td>
                            <td>
                                <div class="actions-dropdown">
                                    <button type="button" class="btn btn-dark btn-sm">
                                        <ion-icon name="ellipsis-vertical-outline"></ion-icon>
                                    </button>
                                    <div class="dropdown-content">
                                        <form method="POST">
                                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                            
                                            <?php if($status !== 'closed'): ?>
                                            <button type="submit" name="close_request" class="dropdown-item">
                                                <ion-icon name="checkmark-done-outline"></ion-icon> Mark Closed
                                            </button>
                                            <?php else: ?>
                                            <button type="submit" name="open_request" class="dropdown-item">
                                                <ion-icon name="reload-outline"></ion-icon> Re-open
                                            </button>
                                            <?php endif; ?>
                                            
                                            <button type="submit" name="delete" class="dropdown-item danger" onclick="return confirm('Delete this request?')">
                                                <ion-icon name="trash-outline"></ion-icon> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; padding: 2rem; color: #888;">No product requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>
    </main>

    <script>
        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('input[name="selected[]"]').forEach(cb => cb.checked = this.checked);
        });
    </script>
</body>
</html>
