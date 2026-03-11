<?php
require_once __DIR__ . '/includes/session.php';
require 'includes/db.php';

$activePage = 'roles';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

// Check if user is super admin
$isSuperAdmin = ($_SESSION['role'] ?? '') === 'super_admin';

// Only super admins can access this page
if (!$isSuperAdmin) {
    header("Location: admin_dashboard.php?error=unauthorized");
    exit;
}

$msg = '';
$msgType = '';

// Handle Create Role
if (isset($_POST['create_role'])) {
    $roleName = trim($_POST['role_name']);
    $permissions = isset($_POST['permissions']) ? json_encode($_POST['permissions']) : '[]';
    
    $stmt = $pdo->prepare("INSERT INTO roles (name, permissions, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$roleName, $permissions]);
    $msg = "Role '$roleName' created successfully.";
    $msgType = 'success';
}

// Handle Update Role
if (isset($_POST['update_role'])) {
    $roleId = $_POST['role_id'];
    $permissions = isset($_POST['permissions']) ? json_encode($_POST['permissions']) : '[]';
    
    $stmt = $pdo->prepare("UPDATE roles SET permissions = ? WHERE id = ?");
    $stmt->execute([$permissions, $roleId]);
    $msg = "Role permissions updated successfully.";
    $msgType = 'success';
}

// Handle Delete Role
if (isset($_POST['delete_role'])) {
    $roleId = $_POST['role_id'];
    $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ? AND name NOT IN ('super_admin', 'admin')");
    $stmt->execute([$roleId]);
    $msg = "Role deleted.";
    $msgType = 'success';
}

// Handle Assign Role to User
if (isset($_POST['assign_role'])) {
    $userId = $_POST['user_id'];
    $role = $_POST['role'];
    
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$role, $userId]);
    $msg = "User role updated successfully.";
    $msgType = 'success';
}

// Handle Promote to Super Admin
if (isset($_POST['make_super_admin'])) {
    $userId = $_POST['user_id'];
    $stmt = $pdo->prepare("UPDATE users SET role = 'super_admin', is_admin = 1 WHERE id = ?");
    $stmt->execute([$userId]);
    $msg = "User promoted to Super Admin.";
    $msgType = 'success';
}

// Fetch all roles
try {
    $roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();
} catch(Exception $e) {
    // Create roles table if doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            permissions TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Insert default roles
    $pdo->exec("INSERT OR IGNORE INTO roles (name, permissions) VALUES 
        ('super_admin', '[\"all\"]'),
        ('admin', '[\"manage_listings\",\"manage_users\",\"manage_orders\",\"view_analytics\"]'),
        ('moderator', '[\"manage_listings\",\"view_analytics\"]'),
        ('support', '[\"manage_support\",\"view_chats\"]')
    ");
    
    $roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();
}

// Fetch all admin users
$adminUsers = $pdo->query("
    SELECT * FROM users 
    WHERE is_admin = 1 
    ORDER BY 
        CASE WHEN role = 'super_admin' THEN 0 
             WHEN role = 'admin' THEN 1 
             ELSE 2 END,
        created_at DESC
")->fetchAll();

// Available permissions
$allPermissions = [
    'manage_listings' => 'Manage Listings',
    'manage_users' => 'Manage Users',
    'manage_orders' => 'Manage Orders',
    'manage_support' => 'Manage Support Tickets',
    'manage_blogs' => 'Manage Blogs',
    'manage_settings' => 'Site Settings',
    'view_analytics' => 'View Analytics',
    'view_chats' => 'View Chats',
    'delete_users' => 'Delete Users',
    'manage_roles' => 'Manage Roles',
    'manage_banners' => 'Manage Banners',
    'manage_coupons' => 'Manage Coupons',
    'manage_pages' => 'Manage Pages',
    'manage_email_templates' => 'Manage Email Templates',
    'manage_vendors' => 'Manage Vendors',
    'view_logs' => 'View Logs',
    'manage_files' => 'Manage Files',
    'manage_returns' => 'Manage Returns',
    'view_reports' => 'View Reports',
    'manage_kyc' => 'Manage KYC Verifications',
    'manage_transactions' => 'Manage Transactions',
    'manage_wallet' => 'Manage Wallets & Payouts',
    'manage_negotiations' => 'Manage Negotiations / Offers',
    'manage_marquee' => 'Manage Announcement Bar',
    'manage_founders' => 'Manage Founders Page',
    'view_server_stats' => 'View Server Statistics',
    'manage_email_sender' => 'Custom Email Sender',
    'view_user_ips' => 'View User IP Logs',
    'export_data' => 'Export Data',
    'manage_categories' => 'Manage Categories',
    'manage_seo' => 'Manage SEO Settings'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Role Management - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root { 
            --primary: #6B21A8; 
            --accent: #6B21A8; 
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg: #f8f9fa; 
            --sidebar-bg: #1a1a1a;
            --text-light: #a1a1aa;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; display:flex; color: #333; }
        
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; padding: 0.5rem 0; color: white; z-index: 100; }
        .brand { font-size: 1.2rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem; text-decoration: none; }
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
        }
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }
        
        .super-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #6B21A8, #9333ea);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .msg {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .msg-success { background: #f0fdf4; color: #22c55e; }
        .msg-error { background: #fef2f2; color: #ef4444; }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
            font-size: 0.9rem;
        }
        
        .form-group input[type="text"],
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #6B21A8;
        }
        
        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
        }
        
        .permission-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0.5rem;
            background: #fafafa;
            border-radius: 6px;
            font-size: 0.85rem;
        }
        
        .permission-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #6B21A8;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
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
        
        .btn-primary {
            background: #6B21A8;
            color: white;
        }
        .btn-primary:hover { background: #581c87; }
        
        .btn-dark {
            background: #1a1a1a;
            color: white;
        }
        .btn-dark:hover { background: #333; }
        
        .btn-danger {
            background: #fef2f2;
            color: #ef4444;
        }
        .btn-danger:hover { background: #fee2e2; }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }
        
        .roles-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .role-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: #fafafa;
            border-radius: 10px;
            border: 1px solid #f0f0f0;
        }
        
        .role-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .role-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .role-icon.super { background: linear-gradient(135deg, #6B21A8, #9333ea); color: white; }
        .role-icon.admin { background: #dbeafe; color: #2563eb; }
        .role-icon.mod { background: #fef3c7; color: #d97706; }
        .role-icon.support { background: #dcfce7; color: #22c55e; }
        
        .role-name { font-weight: 600; color: #1a1a1a; }
        .role-perms { font-size: 0.8rem; color: #999; }
        
        .table-container { 
            background: white; 
            border-radius: 16px; 
            overflow: hidden; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); 
            border: 1px solid #f0f0f0;
        }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.5rem; text-align: left; font-size: 0.9rem; }
        th { 
            background: #fafafa; 
            color: #666; 
            font-weight: 600; 
            text-transform: uppercase; 
            font-size: 0.75rem; 
            border-bottom: 1px solid #f0f0f0;
        }
        td { color: #333; border-bottom: 1px solid #f5f5f5; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
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
        .user-avatar.mod { background: #d97706; }
        .user-avatar.support { background: #22c55e; }
        
        .badge { 
            padding: 4px 10px; 
            border-radius: 20px; 
            font-size: 0.7rem; 
            font-weight: 600; 
        }
        .badge-super { background: linear-gradient(135deg, #6B21A8, #9333ea); color: white; }
        .badge-admin { background: #dbeafe; color: #2563eb; }
        .badge-mod { background: #fef3c7; color: #d97706; }
        .badge-support { background: #dcfce7; color: #22c55e; }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        @media (max-width: 1024px) {
            .grid-2 { grid-template-columns: 1fr; }
            .permissions-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <div>
                <h1>Role Management</h1>
                <p style="color:#666; margin-top:0.5rem;">Manage admin roles and permissions</p>
            </div>
            <div class="super-badge">
                <ion-icon name="shield-checkmark"></ion-icon>
                Super Admin Access
            </div>
        </div>

        <?php if($msg): ?>
            <div class="msg msg-<?php echo $msgType; ?>">
                <ion-icon name="<?php echo $msgType === 'success' ? 'checkmark-circle' : 'alert-circle'; ?>-outline"></ion-icon>
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="grid-2">
            <!-- Create New Role -->
            <div class="card">
                <div class="card-title">
                    <ion-icon name="add-circle-outline" style="color:#6B21A8;"></ion-icon>
                    Create New Role
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label>Role Name</label>
                        <input type="text" name="role_name" placeholder="e.g. Content Manager" required>
                    </div>
                    <div class="form-group">
                        <label>Permissions</label>
                        <div class="permissions-grid">
                            <?php foreach($allPermissions as $key => $label): ?>
                            <label class="permission-item">
                                <input type="checkbox" name="permissions[]" value="<?php echo $key; ?>">
                                <?php echo $label; ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" name="create_role" class="btn btn-primary">
                        <ion-icon name="add-outline"></ion-icon>
                        Create Role
                    </button>
                </form>
            </div>

            <!-- Existing Roles -->
            <div class="card">
                <div class="card-title">
                    <ion-icon name="shield-outline" style="color:#6B21A8;"></ion-icon>
                    Existing Roles
                </div>
                <div class="roles-list">
                    <?php foreach($roles as $role): 
                        $perms = json_decode($role['permissions'], true) ?: [];
                        $permCount = in_array('all', $perms) ? 'All permissions' : count($perms) . ' permissions';
                        $iconClass = $role['name'] === 'super_admin' ? 'super' : ($role['name'] === 'admin' ? 'admin' : ($role['name'] === 'moderator' ? 'mod' : 'support'));
                    ?>
                    <div class="role-item">
                        <div class="role-info">
                            <div class="role-icon <?php echo $iconClass; ?>">
                                <ion-icon name="<?php echo $role['name'] === 'super_admin' ? 'shield-checkmark' : 'shield-half'; ?>-outline"></ion-icon>
                            </div>
                            <div>
                                <div class="role-name"><?php echo ucwords(str_replace('_', ' ', $role['name'])); ?></div>
                                <div class="role-perms"><?php echo $permCount; ?></div>
                            </div>
                        </div>
                        <?php if(!in_array($role['name'], ['super_admin', 'admin'])): ?>
                        <form method="POST" onsubmit="return confirm('Delete this role?');">
                            <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                            <button type="submit" name="delete_role" class="btn btn-danger btn-sm">
                                <ion-icon name="trash-outline"></ion-icon>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Admin Users Table -->
        <div class="section-title">
            <ion-icon name="people-outline"></ion-icon>
            Admin Team Members
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Current Role</th>
                        <th>Assigned</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($adminUsers as $user): 
                        $userRole = $user['role'] ?? 'admin';
                        $avatarClass = $userRole === 'super_admin' ? 'super' : ($userRole === 'admin' ? 'admin' : ($userRole === 'moderator' ? 'mod' : 'support'));
                        $badgeClass = $userRole === 'super_admin' ? 'super' : ($userRole === 'admin' ? 'admin' : ($userRole === 'moderator' ? 'mod' : 'support'));
                    ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar <?php echo $avatarClass; ?>">
                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div style="font-weight:600;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                    <div style="font-size:0.8rem; color:#999;"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $badgeClass; ?>">
                                <?php echo strtoupper(str_replace('_', ' ', $userRole)); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if($user['id'] != $_SESSION['user_id'] && $userRole !== 'super_admin'): ?>
                            <div style="display:flex; gap:8px; align-items:center;">
                                <form method="POST" style="display:flex; gap:8px;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <select name="role" style="padding:6px 10px; border:1px solid #e5e5e5; border-radius:6px; font-size:0.8rem;">
                                        <?php foreach($roles as $r): ?>
                                            <option value="<?php echo $r['name']; ?>" <?php echo $userRole === $r['name'] ? 'selected' : ''; ?>>
                                                <?php echo ucwords(str_replace('_', ' ', $r['name'])); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="assign_role" class="btn btn-dark btn-sm">Update</button>
                                </form>
                            </div>
                            <?php else: ?>
                                <span style="color:#999; font-size:0.85rem;">
                                    <?php echo $user['id'] == $_SESSION['user_id'] ? 'Current user' : 'Protected'; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Promote Regular User -->
        <div style="margin-top:2rem;">
            <div class="card">
                <div class="card-title">
                    <ion-icon name="person-add-outline" style="color:#6B21A8;"></ion-icon>
                    Promote User to Admin
                </div>
                <form method="POST" style="display:flex; gap:1rem; align-items:flex-end;">
                    <div class="form-group" style="flex:1; margin-bottom:0;">
                        <label>Select User</label>
                        <select name="user_id" required style="width:100%; padding:0.75rem; border:1px solid #e5e5e5; border-radius:8px;">
                            <option value="">Choose a user...</option>
                            <?php 
                            $nonAdmins = $pdo->query("SELECT * FROM users WHERE is_admin = 0 ORDER BY full_name")->fetchAll();
                            foreach($nonAdmins as $u): 
                            ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name'] . ' (' . $u['email'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Assign Role</label>
                        <select name="role" required style="padding:0.75rem; border:1px solid #e5e5e5; border-radius:8px;">
                            <?php foreach($roles as $r): if($r['name'] !== 'super_admin'): ?>
                                <option value="<?php echo $r['name']; ?>"><?php echo ucwords(str_replace('_', ' ', $r['name'])); ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="assign_role" class="btn btn-primary">
                        <ion-icon name="arrow-up-circle-outline"></ion-icon>
                        Promote to Admin
                    </button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
