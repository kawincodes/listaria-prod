<?php
session_start();
require 'includes/db.php';

$activePage = 'chats';

// Check Admin Access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

// Fetch All Negotiations
$chatsStmt = $pdo->query("
    SELECT n.*, p.title as product_title, s.full_name as seller_name, b.full_name as buyer_name 
    FROM negotiations n
    JOIN products p ON n.product_id = p.id
    JOIN users s ON n.seller_id = s.id
    JOIN users b ON n.buyer_id = b.id
    ORDER BY n.created_at DESC
");
$allChats = $chatsStmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Chats - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root { 
            --primary: #2c3e50; 
            --accent: #3498db; 
            --success: #2ecc71;
            --bg: #f8f9fa; 
            --sidebar-bg: #1e293b;
            --text-light: #94a3b8;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; display:flex; color: #333; }
        
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
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px;}
        .badge-avail { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef9c3; color: #854d0e; }

        .btn-approve {
            border: none;
            background: #dcfce7;
            color: #166534;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        /* Sidebar styles check */
        .sidebar { 
            width: 260px; 
            background: var(--sidebar-bg); 
            height: 100vh; 
            position: fixed; 
            padding: 2rem 1.5rem; 
            color: white;
            box-shadow: 4px 0 15px rgba(0,0,0,0.05);
            z-index: 100;
        }
        .brand { 
            font-size: 1.4rem; 
            font-weight: 800; 
            color: white; 
            display:flex; 
            align-items: center; 
            gap: 10px;
            margin-bottom: 3rem; 
            text-decoration:none;
            letter-spacing: -0.5px;
        }
        .menu-item { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 1rem; 
            color: var(--text-light); 
            text-decoration: none; 
            border-radius: 12px; 
            margin-bottom: 0.5rem; 
            transition: all 0.3s ease; 
            font-weight: 500;
        }
        .menu-item:hover, .menu-item.active { 
            background: rgba(255,255,255,0.1); 
            color: white; 
            transform: translateX(5px);
        }

        /* Chat Modal for Admin */
        .chat-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .chat-content {
            background-color: white;
            width: 90%;
            max-width: 500px;
            height: 600px;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .chat-header {
            background: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background: #fdfdfd;
        }
        .close-chat { font-size: 1.5rem; cursor: pointer; }
        .message-bubble {
            max-width: 80%; margin-bottom: 10px; padding: 10px 14px; border-radius: 12px; font-size: 0.9rem; line-height: 1.4;
        }
        .msg-sender { background: #e3f2fd; color: #333; margin-right: auto; border-bottom-left-radius: 2px; }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <h1>All Chats</h1>
            <div>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Buyer</th>
                        <th>Seller</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($allChats) > 0): ?>
                        <?php foreach($allChats as $chat): ?>
                        <tr>
                            <td>#<?php echo $chat['id']; ?></td>
                            <td><?php echo htmlspecialchars($chat['product_title']); ?></td>
                            <td><?php echo htmlspecialchars($chat['buyer_name']); ?></td>
                            <td><?php echo htmlspecialchars($chat['seller_name']); ?></td>
                            <td>
                                <?php if($chat['final_price']): ?>
                                    <span class="badge badge-avail">OFFER ACCEPTED (₹<?php echo number_format($chat['final_price']); ?>)</span>
                                <?php else: ?>
                                    <span class="badge badge-pending">ACTIVE</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn-approve" onclick="openAdminChat(<?php echo $chat['id']; ?>)">View Chat</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">No chats available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Admin Chat Modal -->
    <div id="adminChatModal" class="chat-modal">
        <div class="chat-content">
            <div class="chat-header">
                <h3>Chat History</h3>
                <span class="close-chat" onclick="closeAdminChat()">&times;</span>
            </div>
            <div class="chat-messages" id="adminChatMessages">
                <!-- Messages load here -->
            </div>
        </div>
    </div>

    <script>
    let currentChatId = null;

    async function openAdminChat(id) {
        currentChatId = id;
        document.getElementById('adminChatModal').style.display = 'flex';
        
        const formData = new FormData();
        formData.append('action', 'get_messages');
        formData.append('negotiation_id', currentChatId);
        
        const res = await fetch('api/chat.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        const container = document.getElementById('adminChatMessages');
        container.innerHTML = '';
        
        if (data.success) {
            if(data.messages.length === 0) {
                container.innerHTML = '<div style="text-align:center; padding:2rem; color:#999;">No messages yet.</div>';
            } else {
                data.messages.forEach(msg => {
                    const el = document.createElement('div');
                    el.className = 'message-bubble msg-sender';
                    el.innerHTML = `<strong>User ID ${msg.sender_id}:</strong><br>${msg.message}<div style='font-size:0.75rem; color:#888; margin-top:4px;'>${msg.created_at}</div>`;
                    container.appendChild(el);
                });
                container.scrollTop = container.scrollHeight;
            }
        }
    }

    function closeAdminChat() {
        document.getElementById('adminChatModal').style.display = 'none';
        currentChatId = null;
    }
    </script>

</body>
</html>
