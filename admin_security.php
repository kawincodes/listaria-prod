<?php
session_start();
require 'includes/db.php';

$activePage = 'security';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$isSuperAdmin = ($_SESSION['role'] ?? '') === 'super_admin';
if (!$isSuperAdmin) {
    header("Location: admin_dashboard.php?error=unauthorized");
    exit;
}

$msg = '';

// Create sessions table if not exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            session_id VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin_id (admin_id)
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS blacklist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type ENUM('email', 'ip') NOT NULL,
            value VARCHAR(255) NOT NULL,
            reason TEXT,
            created_by INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_entry (type, value)
        )
    ");
} catch(Exception $e) {}

// Handle force logout
if (isset($_POST['force_logout'])) {
    $sessionId = $_POST['session_id'];
    $pdo->prepare("DELETE FROM admin_sessions WHERE id = ?")->execute([$sessionId]);
    $msg = "Session terminated successfully.";
}

// Handle blacklist add
if (isset($_POST['add_blacklist'])) {
    $type = $_POST['bl_type'];
    $value = trim($_POST['bl_value']);
    $reason = trim($_POST['bl_reason']);
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO blacklist (type, value, reason, created_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$type, $value, $reason, $_SESSION['user_id']]);
    $msg = ucfirst($type) . " added to blacklist.";
}

// Handle blacklist remove
if (isset($_POST['remove_blacklist'])) {
    $id = $_POST['bl_id'];
    $pdo->prepare("DELETE FROM blacklist WHERE id = ?")->execute([$id]);
    $msg = "Entry removed from blacklist.";
}

// Fetch active sessions
$sessions = $pdo->query("
    SELECT s.*, u.full_name, u.email, u.role
    FROM admin_sessions s
    JOIN users u ON s.admin_id = u.id
    WHERE s.last_activity > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY s.last_activity DESC
")->fetchAll();

// Fetch blacklist
$blacklist = $pdo->query("
    SELECT b.*, u.full_name as added_by
    FROM blacklist b
    LEFT JOIN users u ON b.created_by = u.id
    ORDER BY b.created_at DESC
")->fetchAll();

// Security stats
$failedLogins = 0; // Would come from login attempts table
$blockedIPs = $pdo->query("SELECT COUNT(*) FROM blacklist WHERE type = 'ip'")->fetchColumn();
$blockedEmails = $pdo->query("SELECT COUNT(*) FROM blacklist WHERE type = 'email'")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Security - Listaria Admin</title>
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
        
        .msg-success {
            background: #f0fdf4;
            color: #22c55e;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .stats-grid {
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
            text-align: center;
        }
        
        .stat-card .icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 0.75rem;
        }
        
        .stat-card .icon.purple { background: #f3e8ff; color: #6B21A8; }
        .stat-card .icon.red { background: #fee2e2; color: #ef4444; }
        .stat-card .icon.green { background: #dcfce7; color: #22c55e; }
        .stat-card .icon.blue { background: #dbeafe; color: #2563eb; }
        
        .stat-card .value { font-size: 1.75rem; font-weight: 700; color: #1a1a1a; }
        .stat-card .label { font-size: 0.8rem; color: #666; margin-top: 0.25rem; }
        
        .section-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
        }
        
        .card-title {
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
        
        .session-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .session-item:last-child { border-bottom: none; }
        
        .session-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #f3e8ff;
            color: #6B21A8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .session-info { flex: 1; }
        .session-name { font-weight: 600; color: #1a1a1a; }
        .session-details { font-size: 0.8rem; color: #999; }
        .session-status { font-size: 0.75rem; }
        .session-status.active { color: #22c55e; }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        
        .btn-danger { background: #fee2e2; color: #ef4444; }
        .btn-danger:hover { background: #fecaca; }
        
        .btn-primary { background: #6B21A8; color: white; }
        .btn-primary:hover { background: #581c87; }
        
        .form-row {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .form-row input,
        .form-row select {
            flex: 1;
            padding: 0.6rem;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        
        .form-row input:focus,
        .form-row select:focus {
            outline: none;
            border-color: #6B21A8;
        }
        
        .blacklist-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem;
            background: #fafafa;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        
        .bl-type {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .bl-type.email { background: #fee2e2; color: #ef4444; }
        .bl-type.ip { background: #fef3c7; color: #d97706; }
        
        .bl-value { flex: 1; font-weight: 500; font-size: 0.9rem; }
        
        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .section-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <div>
                <h1>Security Center</h1>
                <p style="color:#666; margin-top:0.5rem;">Manage sessions, blacklists, and security settings</p>
            </div>
        </div>

        <?php if($msg): ?>
            <div class="msg-success"><ion-icon name="checkmark-circle-outline"></ion-icon> <?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon green"><ion-icon name="shield-checkmark-outline"></ion-icon></div>
                <div class="value"><?php echo count($sessions); ?></div>
                <div class="label">Active Sessions</div>
            </div>
            <div class="stat-card">
                <div class="icon red"><ion-icon name="ban-outline"></ion-icon></div>
                <div class="value"><?php echo $blockedIPs; ?></div>
                <div class="label">Blocked IPs</div>
            </div>
            <div class="stat-card">
                <div class="icon purple"><ion-icon name="mail-outline"></ion-icon></div>
                <div class="value"><?php echo $blockedEmails; ?></div>
                <div class="label">Blocked Emails</div>
            </div>
            <div class="stat-card">
                <div class="icon blue"><ion-icon name="lock-closed-outline"></ion-icon></div>
                <div class="value">2FA</div>
                <div class="label">Security Level</div>
            </div>
        </div>

        <div class="section-grid">
            <div class="card">
                <div class="card-title">
                    <ion-icon name="desktop-outline" style="color:#6B21A8;"></ion-icon>
                    Active Admin Sessions
                </div>
                <?php if(count($sessions) > 0): ?>
                    <?php foreach($sessions as $s): ?>
                    <div class="session-item">
                        <div class="session-icon"><ion-icon name="person-outline"></ion-icon></div>
                        <div class="session-info">
                            <div class="session-name"><?php echo htmlspecialchars($s['full_name']); ?></div>
                            <div class="session-details">
                                <?php echo htmlspecialchars($s['ip_address'] ?? 'Unknown IP'); ?> &bull;
                                <?php echo date('M j, g:i A', strtotime($s['last_activity'])); ?>
                            </div>
                        </div>
                        <div class="session-status active">Active</div>
                        <?php if($s['admin_id'] != $_SESSION['user_id']): ?>
                        <form method="POST">
                            <input type="hidden" name="session_id" value="<?php echo $s['id']; ?>">
                            <button type="submit" name="force_logout" class="btn btn-danger">Logout</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#999; text-align:center; padding:2rem;">No active sessions</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-title">
                    <ion-icon name="ban-outline" style="color:#ef4444;"></ion-icon>
                    Blacklist Management
                </div>
                
                <form method="POST">
                    <div class="form-row">
                        <select name="bl_type" required>
                            <option value="email">Email</option>
                            <option value="ip">IP Address</option>
                        </select>
                        <input type="text" name="bl_value" placeholder="Value to block" required>
                    </div>
                    <div class="form-row">
                        <input type="text" name="bl_reason" placeholder="Reason (optional)">
                        <button type="submit" name="add_blacklist" class="btn btn-primary">Add</button>
                    </div>
                </form>
                
                <div style="margin-top:1rem; max-height: 300px; overflow-y: auto;">
                    <?php foreach($blacklist as $bl): ?>
                    <div class="blacklist-item">
                        <span class="bl-type <?php echo $bl['type']; ?>"><?php echo strtoupper($bl['type']); ?></span>
                        <span class="bl-value"><?php echo htmlspecialchars($bl['value']); ?></span>
                        <form method="POST">
                            <input type="hidden" name="bl_id" value="<?php echo $bl['id']; ?>">
                            <button type="submit" name="remove_blacklist" class="btn btn-danger">Remove</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                    <?php if(count($blacklist) == 0): ?>
                        <p style="color:#999; text-align:center; padding:1rem;">No blacklisted entries</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
