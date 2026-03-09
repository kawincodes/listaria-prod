<?php
session_start();
require 'includes/db.php';

$activePage = 'support';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$msg = '';

// Create/update support tickets table with priority and assignment
// Create/update support tickets table with priority and assignment
try {
    $pdo->exec("ALTER TABLE support_tickets ADD COLUMN priority VARCHAR(20) DEFAULT 'medium'");
} catch(Exception $e) {}

try {
    $pdo->exec("ALTER TABLE support_tickets ADD COLUMN assigned_to INTEGER DEFAULT NULL");
} catch(Exception $e) {}

try {
    $pdo->exec("ALTER TABLE support_tickets ADD COLUMN category VARCHAR(50) DEFAULT 'general'");
} catch(Exception $e) {}

try {
    $pdo->exec("ALTER TABLE support_tickets ADD COLUMN admin_reply TEXT DEFAULT NULL");
} catch(Exception $e) {}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $ticketId = $_POST['ticket_id'];
        $status = $_POST['status'];
        $pdo->prepare("UPDATE support_tickets SET status = ? WHERE id = ?")->execute([$status, $ticketId]);
        $msg = "Ticket status updated.";
    }
    
    if (isset($_POST['update_priority'])) {
        $ticketId = $_POST['ticket_id'];
        $priority = $_POST['priority'];
        $pdo->prepare("UPDATE support_tickets SET priority = ? WHERE id = ?")->execute([$priority, $ticketId]);
        $msg = "Ticket priority updated.";
    }
    
    if (isset($_POST['assign_ticket'])) {
        $ticketId = $_POST['ticket_id'];
        $assignTo = $_POST['assign_to'];
        $pdo->prepare("UPDATE support_tickets SET assigned_to = ? WHERE id = ?")->execute([$assignTo ?: null, $ticketId]);
        $msg = "Ticket assigned.";
    }
    
    if (isset($_POST['delete_ticket'])) {
        $ticketId = $_POST['ticket_id'];
        $pdo->prepare("DELETE FROM support_tickets WHERE id = ?")->execute([$ticketId]);
        $msg = "Ticket deleted.";
    }
    
    if (isset($_POST['reply_ticket'])) {
        $ticketId = $_POST['ticket_id'];
        $reply = trim($_POST['reply']);
        
        // Fetch ticket details for email
        $stmt = $pdo->prepare("SELECT email, name FROM support_tickets WHERE id = ?");
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch();

        if ($ticket && $ticket['email']) {
            $to = $ticket['email'];
            $subject = "Reply to your Support Request #$ticketId - Listaria";
            $message = "Hi " . $ticket['name'] . ",\n\n" . $reply . "\n\nBest Regards,\nListaria Support Team";
            $headers = "From: no-reply@listaria.com\r\n" .
                       "Reply-To: support@listaria.com\r\n" .
                       "X-Mailer: PHP/" . phpversion();

            // Try sending email (might require SMTP config on Windows/Localhost)
            try {
                if(@mail($to, $subject, $message, $headers)) {
                    $msg = "Reply sent and email dispatched to $to.";
                } else {
                     $msg = "Reply saved, but failed to send email (check server config).";
                }
            } catch (Exception $e) {
                $msg = "Reply saved. Email error: " . $e->getMessage();
            }
        } else {
            $msg = "Reply saved (No email found for this ticket).";
        }

        // Update DB
        $pdo->prepare("UPDATE support_tickets SET admin_reply = ?, status = 'in_progress' WHERE id = ?")
            ->execute([$reply, $ticketId]);
    }
}

// Filters
$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT t.*, u.full_name as assigned_name FROM support_tickets t LEFT JOIN users u ON t.assigned_to = u.id WHERE 1=1";

if ($statusFilter) {
    $sql .= " AND t.status = '$statusFilter'";
}
if ($priorityFilter) {
    $sql .= " AND t.priority = '$priorityFilter'";
}
if ($search) {
    $sql .= " AND (t.name LIKE '%$search%' OR t.email LIKE '%$search%' OR t.message LIKE '%$search%')";
}

$sql .= " ORDER BY CASE t.priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END, t.created_at DESC";
$tickets = $pdo->query($sql)->fetchAll();

// Stats
$totalTickets = $pdo->query("SELECT COUNT(*) FROM support_tickets")->fetchColumn();
$openTickets = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open' OR status IS NULL")->fetchColumn();
$urgentTickets = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE priority = 'urgent'")->fetchColumn();
$resolvedTickets = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'resolved'")->fetchColumn();

// Get admin users for assignment
$admins = $pdo->query("SELECT id, full_name FROM users WHERE is_admin = 1")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Support Tickets - Listaria Admin</title>
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
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
        
        .stat-card { background: white; padding: 1.25rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #f0f0f0; }
        .stat-card .icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 0.75rem; }
        .stat-card .icon.purple { background: #f3e8ff; color: #6B21A8; }
        .stat-card .icon.blue { background: #dbeafe; color: #2563eb; }
        .stat-card .icon.red { background: #fee2e2; color: #ef4444; }
        .stat-card .icon.green { background: #dcfce7; color: #22c55e; }
        .stat-card .value { font-size: 1.75rem; font-weight: 700; color: #1a1a1a; }
        .stat-card .label { font-size: 0.8rem; color: #666; margin-top: 0.25rem; }
        
        .filters { display: flex; gap: 0.75rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center; }
        
        .filter-btn { padding: 0.6rem 1rem; border: 1px solid #e5e5e5; background: white; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 500; color: #666; transition: all 0.2s; text-decoration: none; }
        .filter-btn:hover { border-color: #6B21A8; color: #6B21A8; }
        .filter-btn.active { background: #6B21A8; color: white; border-color: #6B21A8; }
        
        .search-box { display: flex; gap: 0.5rem; margin-left: auto; }
        .search-box input { padding: 0.6rem 1rem; border: 1px solid #e5e5e5; border-radius: 8px; font-size: 0.9rem; width: 200px; }
        .search-box input:focus { outline: none; border-color: #6B21A8; }
        
        .btn { padding: 0.6rem 1rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: #6B21A8; color: white; }
        .btn-primary:hover { background: #581c87; }
        .btn-dark { background: #1a1a1a; color: white; }
        .btn-sm { padding: 0.4rem 0.75rem; font-size: 0.75rem; }
        .btn-danger { background: #fee2e2; color: #ef4444; }
        
        .tickets-list { display: flex; flex-direction: column; gap: 1rem; }
        
        .ticket-card { background: white; border-radius: 16px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #f0f0f0; }
        
        .ticket-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
        
        .ticket-id { font-size: 0.8rem; color: #999; margin-bottom: 0.25rem; }
        .ticket-subject { font-weight: 700; color: #1a1a1a; font-size: 1.1rem; }
        
        .ticket-badges { display: flex; gap: 0.5rem; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; }
        .badge-open { background: #dbeafe; color: #2563eb; }
        .badge-in_progress { background: #fef3c7; color: #d97706; }
        .badge-resolved { background: #dcfce7; color: #22c55e; }
        .badge-closed { background: #f0f0f0; color: #666; }
        
        .priority-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
        .priority-low { background: #f0f0f0; color: #666; }
        .priority-medium { background: #dbeafe; color: #2563eb; }
        .priority-high { background: #fef3c7; color: #d97706; }
        .priority-urgent { background: #fee2e2; color: #ef4444; }
        
        .ticket-message { color: #666; font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem; }
        
        .ticket-meta { display: flex; gap: 1.5rem; font-size: 0.85rem; color: #999; margin-bottom: 1rem; }
        .ticket-meta ion-icon { vertical-align: middle; margin-right: 4px; }
        
        .ticket-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; padding-top: 1rem; border-top: 1px solid #f0f0f0; }
        
        .action-select { padding: 0.5rem; border: 1px solid #e5e5e5; border-radius: 6px; font-size: 0.85rem; }
        
        .reply-box { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f0f0f0; }
        .reply-box textarea { width: 100%; padding: 0.75rem; border: 1px solid #e5e5e5; border-radius: 8px; font-size: 0.9rem; resize: vertical; min-height: 80px; }
        .reply-box textarea:focus { outline: none; border-color: #6B21A8; }
        
        .admin-reply { background: #f3e8ff; padding: 1rem; border-radius: 8px; margin-top: 1rem; }
        .admin-reply-label { font-size: 0.75rem; color: #6B21A8; font-weight: 600; margin-bottom: 0.5rem; }
        
        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <div>
                <h1>Support Tickets</h1>
                <p style="color:#666; margin-top:0.5rem;">Manage customer support requests</p>
            </div>
        </div>

        <?php if($msg): ?>
            <div class="msg-success"><ion-icon name="checkmark-circle-outline"></ion-icon> <?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon purple"><ion-icon name="ticket-outline"></ion-icon></div>
                <div class="value"><?php echo $totalTickets; ?></div>
                <div class="label">Total Tickets</div>
            </div>
            <div class="stat-card">
                <div class="icon blue"><ion-icon name="mail-open-outline"></ion-icon></div>
                <div class="value"><?php echo $openTickets; ?></div>
                <div class="label">Open Tickets</div>
            </div>
            <div class="stat-card">
                <div class="icon red"><ion-icon name="alert-circle-outline"></ion-icon></div>
                <div class="value"><?php echo $urgentTickets; ?></div>
                <div class="label">Urgent</div>
            </div>
            <div class="stat-card">
                <div class="icon green"><ion-icon name="checkmark-done-outline"></ion-icon></div>
                <div class="value"><?php echo $resolvedTickets; ?></div>
                <div class="label">Resolved</div>
            </div>
        </div>

        <div class="filters">
            <a href="?" class="filter-btn <?php echo !$statusFilter && !$priorityFilter ? 'active' : ''; ?>">All</a>
            <a href="?status=open" class="filter-btn <?php echo $statusFilter === 'open' ? 'active' : ''; ?>">Open</a>
            <a href="?status=in_progress" class="filter-btn <?php echo $statusFilter === 'in_progress' ? 'active' : ''; ?>">In Progress</a>
            <a href="?status=resolved" class="filter-btn <?php echo $statusFilter === 'resolved' ? 'active' : ''; ?>">Resolved</a>
            <a href="?priority=urgent" class="filter-btn <?php echo $priorityFilter === 'urgent' ? 'active' : ''; ?>" style="border-color:#ef4444; color:#ef4444;">Urgent</a>
            
            <form method="GET" class="search-box">
                <input type="text" name="search" placeholder="Search tickets..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary"><ion-icon name="search-outline"></ion-icon></button>
            </form>
        </div>

        <div class="tickets-list">
            <?php if(count($tickets) === 0): ?>
                <div style="text-align:center; padding:4rem; color:#999;">
                    <ion-icon name="ticket-outline" style="font-size:4rem; color:#ddd;"></ion-icon>
                    <h3>No tickets found</h3>
                </div>
            <?php endif; ?>
            
            <?php foreach($tickets as $ticket): 
                $status = $ticket['status'] ?? 'open';
                $priority = $ticket['priority'] ?? 'medium';
            ?>
            <div class="ticket-card">
                <div class="ticket-header">
                    <div>
                        <div class="ticket-id">#<?php echo $ticket['id']; ?></div>
                        <div class="ticket-subject">Support Request from <?php echo htmlspecialchars($ticket['name']); ?></div>
                    </div>
                    <div class="ticket-badges">
                        <span class="badge badge-<?php echo $status; ?>"><?php echo str_replace('_', ' ', $status); ?></span>
                        <span class="priority-badge priority-<?php echo $priority; ?>"><?php echo ucfirst($priority); ?></span>
                    </div>
                </div>
                
                <div class="ticket-message"><?php echo nl2br(htmlspecialchars($ticket['message'])); ?></div>
                
                <div class="ticket-meta">
                    <span><ion-icon name="mail-outline"></ion-icon> <?php echo htmlspecialchars($ticket['email']); ?></span>
                    <span><ion-icon name="calendar-outline"></ion-icon> <?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?></span>
                    <?php if($ticket['assigned_name']): ?>
                    <span><ion-icon name="person-outline"></ion-icon> Assigned to <?php echo htmlspecialchars($ticket['assigned_name']); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if(!empty($ticket['admin_reply'])): ?>
                <div class="admin-reply">
                    <div class="admin-reply-label">Admin Reply</div>
                    <?php echo nl2br(htmlspecialchars($ticket['admin_reply'])); ?>
                </div>
                <?php endif; ?>
                
                <div class="ticket-actions">
                    <form method="POST" style="display:flex; gap:0.5rem; align-items:center;">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <select name="status" class="action-select">
                            <option value="open" <?php echo $status === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-dark btn-sm">Update</button>
                    </form>
                    
                    <form method="POST" style="display:flex; gap:0.5rem; align-items:center;">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <select name="priority" class="action-select">
                            <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="urgent" <?php echo $priority === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                        </select>
                        <button type="submit" name="update_priority" class="btn btn-dark btn-sm">Set</button>
                    </form>
                    
                    <form method="POST" style="display:flex; gap:0.5rem; align-items:center;">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <select name="assign_to" class="action-select">
                            <option value="">Assign to...</option>
                            <?php foreach($admins as $admin): ?>
                            <option value="<?php echo $admin['id']; ?>" <?php echo $ticket['assigned_to'] == $admin['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($admin['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="assign_ticket" class="btn btn-dark btn-sm">Assign</button>
                    </form>
                    
                    <form method="POST" onsubmit="return confirm('Delete this ticket?');">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <button type="submit" name="delete_ticket" class="btn btn-danger btn-sm">
                            <ion-icon name="trash-outline"></ion-icon>
                        </button>
                    </form>
                </div>
                
                <div class="reply-box">
                    <form method="POST">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <textarea name="reply" placeholder="Type your reply..."><?php echo htmlspecialchars($ticket['admin_reply'] ?? ''); ?></textarea>
                        <button type="submit" name="reply_ticket" class="btn btn-primary" style="margin-top:0.5rem;">
                            <ion-icon name="send-outline"></ion-icon> Send Reply
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>
