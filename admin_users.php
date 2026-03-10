<?php
require_once __DIR__ . '/includes/session.php';
require 'includes/db.php';
require_once 'includes/email_templates.php';

$activePage = 'users';
if (isset($_GET['filter']) && $_GET['filter'] === 'vendor_apps') $activePage = 'vendor_apps';
elseif (isset($_GET['filter']) && $_GET['filter'] === 'verified_vendors') $activePage = 'verified_vendors';
elseif (isset($_GET['kyc']) && $_GET['kyc'] === 'pending') $activePage = 'kyc_pending';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$msg = '';
$msgType = 'success';

// Handle actions
if (isset($_POST['action'])) {
    $userId = $_POST['user_id'];
    $action = $_POST['action'];
    
    switch($action) {
        case 'verify_kyc':
            $pdo->prepare("UPDATE users SET kyc_status = 'verified' WHERE id = ?")->execute([$userId]);
            $msg = "User KYC verified.";
            break;
        case 'reject_kyc':
            $pdo->prepare("UPDATE users SET kyc_status = 'rejected' WHERE id = ?")->execute([$userId]);
            $msg = "User KYC rejected.";
            break;
        case 'block':
            $pdo->prepare("UPDATE users SET status = 'blocked' WHERE id = ?")->execute([$userId]);
            $msg = "User blocked.";
            break;
        case 'suspend':
            $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?")->execute([$userId]);
            $msg = "User suspended.";
            break;
        case 'activate':
            $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$userId]);
            $msg = "User activated.";
            break;
        case 'make_admin':
            $pdo->prepare("UPDATE users SET is_admin = 1, role = 'admin' WHERE id = ?")->execute([$userId]);
            $msg = "User promoted to admin.";
            break;
        case 'remove_admin':
            $pdo->prepare("UPDATE users SET is_admin = 0, role = NULL WHERE id = ?")->execute([$userId]);
            $msg = "Admin privileges removed.";
            break;
        case 'delete':
            $pdo->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0")->execute([$userId]);
            $msg = "User deleted.";
            break;
        case 'credit_wallet':
            $amount = floatval($_POST['amount']);
            $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")->execute([$amount, $userId]);
            $msg = "₹$amount credited to wallet.";
            break;
        case 'debit_wallet':
            $amount = floatval($_POST['amount']);
            $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?")->execute([$amount, $userId]);
            $msg = "₹$amount debited from wallet.";
            break;
        case 'approve_vendor':
            $pdo->prepare("UPDATE users SET vendor_status = 'approved', is_verified_vendor = 1, account_type = 'vendor' WHERE id = ?")->execute([$userId]);
            $uRow = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
            $uRow->execute([$userId]);
            $uData = $uRow->fetch();
            if ($uData) {
                sendTemplateMail($pdo, 'vendor_approved', $uData['email'], [
                    'user_name'     => $uData['full_name'],
                    'dashboard_url' => 'https://listaria.in/profile.php',
                ], $uData['full_name']);
            }
            $msg = "Vendor application approved.";
            break;
        case 'reject_vendor':
            $reason = trim($_POST['rejection_reason'] ?? 'Did not meet vendor criteria.');
            $pdo->prepare("UPDATE users SET vendor_status = 'rejected', rejection_reason = ? WHERE id = ?")->execute([$reason, $userId]);
            $uRow2 = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
            $uRow2->execute([$userId]);
            $uData2 = $uRow2->fetch();
            if ($uData2) {
                sendTemplateMail($pdo, 'vendor_rejected', $uData2['email'], [
                    'user_name'        => $uData2['full_name'],
                    'rejection_reason' => $reason,
                    'support_url'      => 'https://listaria.in/help_support.php',
                ], $uData2['full_name']);
            }
            $msg = "Vendor application rejected.";
            break;
    }
    
    // Log activity
    try {
        $pdo->prepare("INSERT INTO admin_activity_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)")
            ->execute([$_SESSION['user_id'], "User $action", "User ID: $userId", $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch(Exception $e) {}
}

// Handle bulk actions
if (isset($_POST['bulk_action']) && !empty($_POST['selected_users'])) {
    $action = $_POST['bulk_action'];
    $userIds = $_POST['selected_users'];
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    
    switch($action) {
        case 'block':
            $pdo->prepare("UPDATE users SET status = 'blocked' WHERE id IN ($placeholders)")->execute($userIds);
            $msg = count($userIds) . " users blocked.";
            break;
        case 'activate':
            $pdo->prepare("UPDATE users SET status = 'active' WHERE id IN ($placeholders)")->execute($userIds);
            $msg = count($userIds) . " users activated.";
            break;
        case 'delete':
            $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders) AND is_admin = 0")->execute($userIds);
            $msg = count($userIds) . " users deleted.";
            break;
    }
}

// Filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$kycFilter = $_GET['kyc'] ?? '';

$sql = "
    SELECT u.*, 
           (SELECT COUNT(*) FROM products WHERE user_id = u.id) as product_count,
           (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
           (SELECT SUM(amount) FROM orders WHERE user_id = u.id) as total_spent
    FROM users u 
    WHERE 1=1
";

if ($filter === 'admins') {
    $sql .= " AND u.is_admin = 1";
} elseif ($filter === 'blocked') {
    $sql .= " AND u.status = 'blocked'";
} elseif ($filter === 'suspended') {
    $sql .= " AND u.status = 'suspended'";
} elseif ($filter === 'vendor_apps') {
    $sql .= " AND u.vendor_status = 'pending'";
} elseif ($filter === 'verified_vendors') {
    $sql .= " AND u.is_verified_vendor = 1";
}

if ($kycFilter) {
    $sql .= " AND u.kyc_status = '$kycFilter'";
}

if ($search) {
    $sql .= " AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%')";
}

$sql .= " ORDER BY u.created_at DESC";
$allUsers = $pdo->query($sql)->fetchAll();

// Stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active' OR status IS NULL")->fetchColumn();
$blockedUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'blocked'")->fetchColumn();
$pendingKYC = $pdo->query("SELECT COUNT(*) FROM users WHERE kyc_status = 'pending'")->fetchColumn();
$pendingVendors = $pdo->query("SELECT COUNT(*) FROM users WHERE vendor_status = 'pending'")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - Listaria Admin</title>
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
        
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; padding: 0.5rem 0; color: white; z-index: 100; }
        .brand { font-size: 1.2rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem; text-decoration: none; }
        .main-content { margin-left: 260px; padding: 2rem 2.5rem; width: calc(100% - 260px); min-height: 100vh; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }
        
        .msg { padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 8px; }
        .msg-success { background: #f0fdf4; color: #22c55e; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.25rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
        }
        
        .stat-card .icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 0.75rem;
        }
        
        .stat-card .icon.purple { background: #f3e8ff; color: #6B21A8; }
        .stat-card .icon.green { background: #dcfce7; color: #22c55e; }
        .stat-card .icon.red { background: #fee2e2; color: #ef4444; }
        .stat-card .icon.orange { background: #fef3c7; color: #d97706; }
        
        .stat-card .value { font-size: 1.75rem; font-weight: 700; color: #1a1a1a; }
        .stat-card .label { font-size: 0.8rem; color: #666; margin-top: 0.25rem; }
        
        .filters {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-btn {
            padding: 0.6rem 1rem;
            border: 1px solid #e5e5e5;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            color: #666;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .filter-btn:hover { border-color: #6B21A8; color: #6B21A8; }
        .filter-btn.active { background: #6B21A8; color: white; border-color: #6B21A8; }
        
        .search-box {
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
        }
        
        .search-box input {
            padding: 0.6rem 1rem;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            font-size: 0.9rem;
            width: 250px;
        }
        
        .search-box input:focus { outline: none; border-color: #6B21A8; }
        
        .btn {
            padding: 0.6rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-primary { background: #6B21A8; color: white; }
        .btn-primary:hover { background: #581c87; }
        .btn-dark { background: #1a1a1a; color: white; }
        .btn-dark:hover { background: #333; }
        .btn-success { background: #dcfce7; color: #22c55e; }
        .btn-success:hover { background: #bbf7d0; }
        .btn-danger { background: #fee2e2; color: #ef4444; }
        .btn-danger:hover { background: #fecaca; }
        .btn-warning { background: #fef3c7; color: #d97706; }
        .btn-warning:hover { background: #fde68a; }
        .btn-sm { padding: 0.4rem 0.75rem; font-size: 0.75rem; }
        
        .table-container { 
            background: white; 
            border-radius: 16px; 
            overflow: visible; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); 
            border: 1px solid #f0f0f0;
        }
        
        .table-header {
            padding: 1rem 1.5rem;
            background: #fafafa;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-radius: 16px 16px 0 0;
        }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.25rem; text-align: left; font-size: 0.85rem; }
        th { 
            background: #fafafa; 
            color: #666; 
            font-weight: 600; 
            text-transform: uppercase; 
            font-size: 0.7rem; 
            letter-spacing: 0.5px;
            border-bottom: 1px solid #f0f0f0;
        }
        td { color: #333; border-bottom: 1px solid #f5f5f5; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }
        
        .user-info { display: flex; align-items: center; gap: 12px; }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .user-avatar.super { background: linear-gradient(135deg, #6B21A8, #9333ea); }
        .user-avatar.admin { background: #2563eb; }
        .user-avatar.default { background: linear-gradient(135deg, #64748b, #94a3b8); }
        
        .user-name { font-weight: 600; color: #1a1a1a; }
        .user-email { font-size: 0.8rem; color: #999; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 600; text-transform: uppercase; }
        .badge-active { background: #dcfce7; color: #22c55e; }
        .badge-blocked { background: #fee2e2; color: #ef4444; }
        .badge-suspended { background: #fef3c7; color: #d97706; }
        .badge-admin { background: #f3e8ff; color: #6B21A8; }
        .badge-super { background: linear-gradient(135deg, #6B21A8, #9333ea); color: white; }
        
        .kyc-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 600; }
        .kyc-verified { background: #dcfce7; color: #22c55e; }
        .kyc-pending { background: #fef3c7; color: #d97706; }
        .kyc-rejected { background: #fee2e2; color: #ef4444; }
        .kyc-none { background: #f0f0f0; color: #999; }
        
        .actions-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background: white;
            min-width: 180px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            border-radius: 10px;
            z-index: 10;
            padding: 0.5rem;
        }
        
        .actions-dropdown:hover .dropdown-content { display: block; }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0.6rem 0.75rem;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-size: 0.85rem;
            border-radius: 6px;
            color: #333;
        }
        
        .dropdown-item:hover { background: #f5f5f5; }
        .dropdown-item.danger { color: #ef4444; }
        .dropdown-item.danger:hover { background: #fef2f2; }
        
        .wallet-badge {
            background: #f0f0f0;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #333;
        }
        
        .checkbox-cell { width: 40px; }
        .checkbox-cell input { width: 16px; height: 16px; accent-color: #6B21A8; }
        
        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        .modal {
            display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; backdrop-filter: blur(4px);
        }
        .modal-content {
            background-color: #fff; width: 90%; max-width: 500px; border-radius: 16px; padding: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); position:relative;
        }
        .close-modal {
            position: absolute; top: 16px; right: 20px; font-size: 1.5rem; color: #999; cursor: pointer;
        }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <div>
                <h1>User Management</h1>
                <p style="color:#666; margin-top:0.5rem;">Manage all users, KYC, and wallets</p>
            </div>
            <a href="?export=csv" class="btn btn-dark">
                <ion-icon name="download-outline"></ion-icon>
                Export CSV
            </a>
        </div>

        <?php if($msg): ?>
            <div class="msg msg-<?php echo $msgType; ?>">
                <ion-icon name="checkmark-circle-outline"></ion-icon>
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon purple"><ion-icon name="people-outline"></ion-icon></div>
                <div class="value"><?php echo number_format($totalUsers); ?></div>
                <div class="label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="icon green"><ion-icon name="checkmark-circle-outline"></ion-icon></div>
                <div class="value"><?php echo number_format($activeUsers); ?></div>
                <div class="label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="icon red"><ion-icon name="ban-outline"></ion-icon></div>
                <div class="value"><?php echo number_format($blockedUsers); ?></div>
                <div class="label">Blocked Users</div>
            </div>
            <div class="stat-card">
                <div class="icon orange"><ion-icon name="document-text-outline"></ion-icon></div>
                <div class="value"><?php echo number_format($pendingKYC); ?></div>
                <div class="label">Pending KYC</div>
            </div>
        </div>

        <div class="filters">
            <a href="?" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All Users</a>
            <a href="?filter=admins" class="filter-btn <?php echo $filter === 'admins' ? 'active' : ''; ?>">Admins</a>
            <a href="?filter=blocked" class="filter-btn <?php echo $filter === 'blocked' ? 'active' : ''; ?>">Blocked</a>
            <a href="?filter=suspended" class="filter-btn <?php echo $filter === 'suspended' ? 'active' : ''; ?>">Suspended</a>
            <a href="?filter=vendor_apps" class="filter-btn <?php echo $filter === 'vendor_apps' ? 'active' : ''; ?>">
                Vendor Apps <?php if($pendingVendors > 0) echo "<span style='background:red; color:white; padding:2px 6px; border-radius:10px; font-size:0.75rem; margin-left:4px;'>$pendingVendors</span>"; ?>
            </a>
            <a href="?filter=verified_vendors" class="filter-btn <?php echo $filter === 'verified_vendors' ? 'active' : ''; ?>">Verified Vendors</a>
            <a href="?kyc=pending" class="filter-btn <?php echo $kycFilter === 'pending' ? 'active' : ''; ?>">KYC Pending</a>
            
            <form method="GET" class="search-box">
                <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary"><ion-icon name="search-outline"></ion-icon></button>
            </form>
        </div>

        <form method="POST" id="bulkForm">
        <div class="table-container">
            <div class="table-header">
                <select name="bulk_action" style="padding:0.5rem; border:1px solid #e5e5e5; border-radius:6px;">
                    <option value="">Bulk Actions</option>
                    <option value="activate">Activate</option>
                    <option value="block">Block</option>
                    <option value="delete">Delete</option>
                </select>
                <button type="submit" class="btn btn-dark btn-sm">Apply</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th class="checkbox-cell"><input type="checkbox" id="selectAll"></th>
                        <th>User</th>
                        <th>Status</th>
                        <th>Vendor Status</th>
                        <th>KYC</th>
                        <th>Wallet</th>
                        <th>Listings</th>
                        <th>Orders</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($allUsers as $u): 
                        $status = $u['status'] ?? 'active';
                        $kycStatus = $u['kyc_status'] ?? 'none';
                        $role = $u['role'] ?? '';
                        $avatarClass = $role === 'super_admin' ? 'super' : ($u['is_admin'] ? 'admin' : 'default');
                    ?>
                    <tr>
                        <td class="checkbox-cell">
                            <?php if(!$u['is_admin']): ?>
                            <input type="checkbox" name="selected_users[]" value="<?php echo $u['id']; ?>">
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar <?php echo $avatarClass; ?>">
                                    <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="user-name"><?php echo htmlspecialchars($u['full_name']); ?></div>
                                    <div class="user-email"><?php echo htmlspecialchars($u['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if($role === 'super_admin'): ?>
                                <span class="badge badge-super">SUPER ADMIN</span>
                            <?php elseif($u['is_admin']): ?>
                                <span class="badge badge-admin">ADMIN</span>
                            <?php elseif($status === 'blocked'): ?>
                                <span class="badge badge-blocked">BLOCKED</span>
                            <?php elseif($status === 'suspended'): ?>
                                <span class="badge badge-suspended">SUSPENDED</span>
                            <?php else: ?>
                                <span class="badge badge-active">ACTIVE</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                                $vStatus = $u['vendor_status'] ?? 'none';
                                if($vStatus === 'pending') echo '<span class="kyc-badge kyc-pending">PENDING APP</span>';
                                elseif($vStatus === 'approved') echo '<span class="kyc-badge kyc-verified">VERIFIED VENDOR</span>';
                                elseif($vStatus === 'rejected') echo '<span class="kyc-badge kyc-rejected">REJECTED APP</span>';
                                else echo '<span class="kyc-badge kyc-none">NONE</span>';
                            ?>
                        </td>
                        <td>
                            <span class="kyc-badge kyc-<?php echo $kycStatus; ?>">
                                <?php echo strtoupper($kycStatus); ?>
                            </span>
                        </td>
                        <td>
                            <span class="wallet-badge">₹<?php echo number_format($u['wallet_balance'] ?? 0); ?></span>
                        </td>
                        <td><?php echo $u['product_count']; ?></td>
                        <td><?php echo $u['order_count']; ?></td>
                        <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <?php if($u['id'] != $_SESSION['user_id']): ?>
                            <div class="actions-dropdown">
                                <button type="button" class="btn btn-dark btn-sm">
                                    <ion-icon name="ellipsis-vertical-outline"></ion-icon>
                                </button>
                                <div class="dropdown-content">
                                    <?php if($kycStatus === 'pending'): ?>
                                    <button type="submit" name="action" value="verify_kyc" class="dropdown-item">
                                        <ion-icon name="checkmark-circle-outline"></ion-icon> Verify KYC
                                    </button>
                                    <button type="submit" name="action" value="reject_kyc" class="dropdown-item">
                                        <ion-icon name="close-circle-outline"></ion-icon> Reject KYC
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if($status !== 'active'): ?>
                                    <button type="submit" name="action" value="activate" class="dropdown-item">
                                        <ion-icon name="checkmark-outline"></ion-icon> Activate
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if($status !== 'blocked' && !$u['is_admin']): ?>
                                    <button type="submit" name="action" value="block" class="dropdown-item">
                                        <ion-icon name="ban-outline"></ion-icon> Block
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if($status !== 'suspended' && !$u['is_admin']): ?>
                                    <button type="submit" name="action" value="suspend" class="dropdown-item">
                                        <ion-icon name="pause-outline"></ion-icon> Suspend
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if(!$u['is_admin']): ?>
                                    <button type="submit" name="action" value="make_admin" class="dropdown-item">
                                        <ion-icon name="shield-outline"></ion-icon> Make Admin
                                    </button>
                                    <?php elseif($role !== 'super_admin'): ?>
                                    <button type="submit" name="action" value="remove_admin" class="dropdown-item">
                                        <ion-icon name="shield-outline"></ion-icon> Remove Admin
                                    </button>
                                    <?php endif; ?>

                                    <?php if(($u['vendor_status'] ?? 'none') !== 'none'): 
                                        $bName = htmlspecialchars($u['business_name'] ?? '');
                                        $bBio = htmlspecialchars($u['business_bio'] ?? '');
                                        $bWa = htmlspecialchars($u['whatsapp_number'] ?? '');
                                        $vApplied = $u['vendor_applied_at'] ? date('M j, Y H:i', strtotime($u['vendor_applied_at'])) : 'N/A';
                                        $vStatus = ucfirst($u['vendor_status']);
                                    ?>
                                    <hr style="margin: 5px 0; border-top: 1px solid #eee;">
                                    <button type="button" class="dropdown-item" onclick="viewVendorDetails('<?php echo addslashes($bName); ?>', '<?php echo addslashes($bBio); ?>', '<?php echo addslashes($bWa); ?>', '<?php echo $vApplied; ?>', '<?php echo $vStatus; ?>')">
                                        <ion-icon name="information-circle-outline"></ion-icon> View Vendor Details
                                    </button>
                                    <?php endif; ?>

                                    <?php if(($u['vendor_status'] ?? '') === 'pending'): ?>
                                    <hr style="margin: 5px 0; border-top: 1px solid #eee;">
                                    <button type="submit" name="action" value="approve_vendor" class="dropdown-item" style="color: #22c55e;">
                                        <ion-icon name="checkmark-done-circle-outline"></ion-icon> Approve Vendor App
                                    </button>
                                    <button type="button" class="dropdown-item" style="color: #ef4444;" onclick="rejectVendorApp(<?php echo $u['id']; ?>)">
                                        <ion-icon name="close-circle-outline"></ion-icon> Reject Vendor App
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if(!$u['is_admin']): ?>
                                    <button type="submit" name="action" value="delete" class="dropdown-item danger" onclick="return confirm('Delete this user?')">
                                        <ion-icon name="trash-outline"></ion-icon> Delete
                                    </button>
                                    <?php endif; ?>
                                    
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                </div>
                            </div>
                            <?php else: ?>
                                <span style="color:#999; font-size:0.8rem;">Current</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </form>
    </main>

    <!-- Vendor Details Modal -->
    <div id="vendorModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeVendorDetails()">&times;</span>
            <h2 style="margin-top:0; font-size:1.4rem; margin-bottom: 20px;">Vendor Details</h2>
            <div style="margin-bottom: 12px;"><strong>Status:</strong> <span id="vmStatus" class="badge"></span></div>
            <div style="margin-bottom: 12px;"><strong>Business Name:</strong> <div id="vmName" style="background:#f9f9f9; padding:8px; border-radius:6px; margin-top:4px;"></div></div>
            <div style="margin-bottom: 12px;"><strong>WhatsApp:</strong> <div id="vmWa" style="background:#f9f9f9; padding:8px; border-radius:6px; margin-top:4px;"></div></div>
            <div style="margin-bottom: 12px;"><strong>Bio:</strong> <div id="vmBio" style="background:#f9f9f9; padding:8px; border-radius:6px; margin-top:4px; min-height:60px;"></div></div>
            <div style="margin-bottom: 12px; font-size:0.85rem; color:#666;"><strong>Applied On:</strong> <span id="vmApplied"></span></div>
        </div>
    </div>

    <script>
        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('input[name="selected_users[]"]').forEach(cb => cb.checked = this.checked);
        });

        function viewVendorDetails(name, bio, wa, applied, status) {
            document.getElementById('vmName').innerText = name || 'N/A';
            document.getElementById('vmBio').innerText = bio || 'N/A';
            document.getElementById('vmWa').innerText = wa || 'N/A';
            document.getElementById('vmApplied').innerText = applied;
            
            const statusEl = document.getElementById('vmStatus');
            statusEl.innerText = status;
            statusEl.className = 'badge';
            if(status.toLowerCase() === 'approved') statusEl.classList.add('badge-active');
            else if(status.toLowerCase() === 'rejected') statusEl.classList.add('badge-blocked');
            else statusEl.classList.add('badge-suspended');

            document.getElementById('vendorModal').style.display = 'flex';
        }

        function closeVendorDetails() {
            document.getElementById('vendorModal').style.display = 'none';
        }

        function rejectVendorApp(userId) {
            const reason = prompt("Enter reason for vendor application rejection:");
            if (reason !== null) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="reject_vendor">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="rejection_reason" value="${reason.replace(/"/g, '&quot;')}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
