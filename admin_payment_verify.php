<?php
require_once __DIR__ . '/includes/session.php';
require 'includes/db.php';
require_once 'includes/email_templates.php';

$activePage = 'payment_verify';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfValid = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $msg = 'Invalid security token. Please try again.';
        $csrfValid = false;
    }
}

if ($csrfValid && isset($_POST['verify_payment_id'])) {
    $oid = $_POST['verify_payment_id'];
    $verifyStmt = $pdo->prepare("UPDATE orders SET order_status = 'Success' WHERE id = ? AND order_status IN ('Pending', 'Verification Pending')");
    $verifyStmt->execute([$oid]);

    if ($verifyStmt->rowCount() > 0) {
        $msg = "Payment verified for Order #$oid. Status set to Success.";

        $vRow = $pdo->prepare("SELECT o.*, u.email, u.full_name, p.title as product_title, sel.email as seller_email, sel.full_name as seller_name FROM orders o JOIN users u ON o.user_id = u.id JOIN products p ON o.product_id = p.id JOIN users sel ON p.user_id = sel.id WHERE o.id = ?");
        $vRow->execute([$oid]);
        $vData = $vRow->fetch();
        if ($vData) {
            $profileUrl = 'https://listaria.in/profile.php';
            sendTemplateMail($pdo, 'order_confirmation', $vData['email'], [
                'customer_name'  => $vData['full_name'],
                'order_id'       => $oid,
                'product_title'  => $vData['product_title'],
                'order_amount'   => '₹' . number_format((float)$vData['amount'], 2),
                'order_date'     => date('M j, Y'),
                'payment_method' => ucfirst($vData['payment_method']),
                'profile_url'    => $profileUrl,
            ], $vData['full_name']);
            sendTemplateMail($pdo, 'new_sale_notification', $vData['seller_email'], [
                'seller_name'   => $vData['seller_name'],
                'order_id'      => $oid,
                'product_title' => $vData['product_title'],
                'order_amount'  => '₹' . number_format((float)$vData['amount'], 2),
                'buyer_name'    => $vData['full_name'],
                'order_date'    => date('M j, Y'),
                'profile_url'   => $profileUrl,
            ], $vData['seller_name']);
        }
    } else {
        $msg = "Order #$oid was already verified or is not in Pending state.";
    }
}

if ($csrfValid && isset($_POST['reject_payment_id'])) {
    $oid = $_POST['reject_payment_id'];
    $rejReason = trim($_POST['rejection_reason'] ?? '');
    $stmt = $pdo->prepare("UPDATE orders SET order_status = 'Payment Failed', rejection_reason = ? WHERE id = ? AND order_status IN ('Pending', 'Verification Pending')");
    $stmt->execute([$rejReason ?: null, $oid]);

    if ($stmt->rowCount() > 0) {
        $p_stmt = $pdo->prepare("SELECT o.product_id, u.email, u.full_name, p.title as product_title FROM orders o JOIN users u ON o.user_id = u.id JOIN products p ON o.product_id = p.id WHERE o.id = ?");
        $p_stmt->execute([$oid]);
        $prod = $p_stmt->fetch();
        if ($prod) {
            $pdo->prepare("UPDATE products SET quantity = COALESCE(quantity,0) + 1, status = 'available' WHERE id = ?")->execute([$prod['product_id']]);
            try {
                sendTemplateMail($pdo, 'payment_rejected', $prod['email'], [
                    'customer_name'    => $prod['full_name'],
                    'order_id'         => $oid,
                    'product_title'    => $prod['product_title'],
                    'rejection_reason' => $rejReason ?: 'Payment could not be verified.',
                    'profile_url'      => 'https://listaria.in/profile.php',
                ], $prod['full_name']);
            } catch (Exception $e) {}
        }
        $msg = "Payment rejected for Order #$oid. Status marked as 'Payment Failed'. Stock restored.";
    } else {
        $msg = "Order #$oid was already processed.";
    }
}

$ordersStmt = $pdo->query("
    SELECT o.*, 
           u.full_name as buyer_name, u.email as buyer_email, u.address as buyer_address, u.phone as buyer_phone,
           p.title as product_title, p.price_min, p.image_paths,
           sel.full_name as seller_name, sel.email as seller_email, sel.phone as seller_phone
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN products p ON o.product_id = p.id 
    JOIN users sel ON p.user_id = sel.id
    WHERE o.order_status IN ('Pending', 'Verification Pending') 
      AND LOWER(o.payment_method) != 'cod'
    ORDER BY o.created_at DESC
");
$orders = $ordersStmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Verification - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root { 
            --primary: #6B21A8; 
            --primary-dark: #581c87;
            --accent: #6B21A8; 
            --success: #22c55e;
            --bg: #f8f9fa; 
            --sidebar-bg: #1a1a1a;
            --text-light: #a1a1aa;
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
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }
        .header-badge {
            background: rgba(107,33,168,0.1);
            color: #6B21A8;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
        }
        .empty-state ion-icon {
            font-size: 3rem;
            color: #22c55e;
            margin-bottom: 1rem;
        }
        .empty-state h3 {
            margin: 0 0 0.5rem;
            color: #334155;
        }
        .empty-state p {
            color: #94a3b8;
            margin: 0;
        }

        .order-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            border: 1px solid #f1f5f9;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        .order-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 1.5rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .order-id {
            font-weight: 700;
            font-size: 1rem;
            color: #1a1a1a;
        }
        .order-date {
            font-size: 0.8rem;
            color: #64748b;
        }
        .order-card-body {
            padding: 1.5rem;
        }
        .order-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 1.5rem;
        }
        .order-section h4 {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin: 0 0 0.6rem;
            font-weight: 600;
        }
        .order-section .info-line {
            font-size: 0.88rem;
            color: #334155;
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .order-section .info-line ion-icon {
            font-size: 0.9rem;
            color: #94a3b8;
            flex-shrink: 0;
        }
        .order-section .info-label {
            font-weight: 600;
            color: #1a1a1a;
        }
        .amount-display {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1a1a1a;
        }
        .payment-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            background: rgba(107,33,168,0.1);
            color: #6B21A8;
        }
        .txn-id {
            font-family: monospace;
            font-size: 0.8rem;
            color: #555;
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .order-card-actions {
            display: flex;
            gap: 0.8rem;
            padding: 1rem 1.5rem;
            border-top: 1px solid #f1f5f9;
            background: #fafbfc;
        }
        .btn-action {
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .btn-action ion-icon {
            font-size: 1rem;
        }
        .btn-verify {
            background: #6B21A8;
            color: white;
            box-shadow: 0 2px 8px rgba(107, 33, 168, 0.3);
        }
        .btn-verify:hover {
            background: #581c87;
        }
        .btn-reject {
            background: #e74c3c;
            color: white;
            box-shadow: 0 2px 8px rgba(231,76,60,0.3);
        }
        .btn-reject:hover {
            background: #c0392b;
        }
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; padding: 0.5rem 0; color: white; z-index: 100; }
        .brand { font-size: 1.2rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem; text-decoration: none; }

        .msg-success {
            margin-bottom: 20px;
            color: #22c55e;
            font-weight: 600;
            padding: 1rem;
            background: #f0fdf4;
            border-radius: 10px;
            border: 1px solid #bbf7d0;
        }
        .msg-error {
            margin-bottom: 20px;
            color: #ef4444;
            font-weight: 600;
            padding: 1rem;
            background: #fef2f2;
            border-radius: 10px;
            border: 1px solid #fecaca;
        }

        @media (max-width: 1100px) {
            .order-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 700px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 1.5rem;
            }
            .order-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <div>
                <h1>Payment Verification</h1>
            </div>
            <div style="display:flex; align-items:center; gap:1rem;">
                <span class="header-badge"><?php echo count($orders); ?> pending</span>
                <div>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            </div>
        </div>

        <?php if(isset($msg)): ?>
            <?php if(strpos($msg, 'rejected') !== false || strpos($msg, 'Failed') !== false): ?>
                <div class="msg-error"><?php echo htmlspecialchars($msg); ?></div>
            <?php else: ?>
                <div class="msg-success"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if(count($orders) === 0): ?>
            <div class="empty-state">
                <ion-icon name="checkmark-circle-outline"></ion-icon>
                <h3>All Caught Up!</h3>
                <p>No pending payments to verify right now.</p>
            </div>
        <?php else: ?>
            <?php foreach($orders as $order): ?>
            <div class="order-card">
                <div class="order-card-header">
                    <div>
                        <span class="order-id">Order #<?php echo $order['id']; ?></span>
                        <span style="margin-left:10px; font-size:0.8rem; color:#d97706; font-weight:600; background:rgba(217,119,6,0.1); padding:2px 8px; border-radius:4px;">Pending Verification</span>
                    </div>
                    <span class="order-date"><?php echo date('M j, Y h:i A', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="order-card-body">
                    <div class="order-grid">
                        <div class="order-section">
                            <h4>Product</h4>
                            <div class="info-line info-label"><?php echo htmlspecialchars($order['product_title']); ?></div>
                            <div class="amount-display" style="margin-top:0.5rem;">₹<?php echo number_format($order['amount']); ?></div>
                        </div>
                        <div class="order-section">
                            <h4>Buyer</h4>
                            <div class="info-line">
                                <ion-icon name="person-outline"></ion-icon>
                                <?php echo htmlspecialchars($order['buyer_name']); ?>
                            </div>
                            <div class="info-line">
                                <ion-icon name="mail-outline"></ion-icon>
                                <?php echo htmlspecialchars($order['buyer_email']); ?>
                            </div>
                            <div class="info-line">
                                <ion-icon name="call-outline"></ion-icon>
                                <?php echo htmlspecialchars($order['buyer_phone'] ?? 'N/A'); ?>
                            </div>
                            <div class="info-line">
                                <ion-icon name="location-outline"></ion-icon>
                                <?php echo htmlspecialchars($order['buyer_address'] ?? 'N/A'); ?>
                            </div>
                        </div>
                        <div class="order-section">
                            <h4>Seller</h4>
                            <div class="info-line">
                                <ion-icon name="person-outline"></ion-icon>
                                <?php echo htmlspecialchars($order['seller_name']); ?>
                            </div>
                            <div class="info-line">
                                <ion-icon name="mail-outline"></ion-icon>
                                <?php echo htmlspecialchars($order['seller_email']); ?>
                            </div>
                            <div class="info-line">
                                <ion-icon name="call-outline"></ion-icon>
                                <?php echo htmlspecialchars($order['seller_phone'] ?? 'N/A'); ?>
                            </div>
                        </div>
                        <div class="order-section">
                            <h4>Payment Details</h4>
                            <div class="info-line">
                                <span class="payment-badge"><?php echo htmlspecialchars(strtoupper($order['payment_method'])); ?></span>
                            </div>
                            <?php if(!empty($order['transaction_id'])): ?>
                            <div class="info-line" style="margin-top:0.4rem;">
                                <ion-icon name="receipt-outline"></ion-icon>
                                <span class="txn-id"><?php echo htmlspecialchars($order['transaction_id']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if(isset($order['payment_status']) && !empty($order['payment_status'])): ?>
                            <div class="info-line" style="margin-top:0.3rem;">
                                <ion-icon name="information-circle-outline"></ion-icon>
                                <?php echo htmlspecialchars($order['payment_status']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="order-card-actions">
                    <form method="POST" onsubmit="return confirm('Verify this payment? This will mark Order #<?php echo $order['id']; ?> as Success and notify the buyer and seller.');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="verify_payment_id" value="<?php echo $order['id']; ?>">
                        <button type="submit" class="btn-action btn-verify">
                            <ion-icon name="checkmark-circle-outline"></ion-icon> Verify Payment
                        </button>
                    </form>
                    <button type="button" class="btn-action btn-reject" onclick="openPayRejectModal(<?php echo $order['id']; ?>, '<?php echo addslashes(htmlspecialchars($order['product_title'])); ?>')">
                        <ion-icon name="close-circle-outline"></ion-icon> Reject Payment
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <!-- Payment Rejection Modal -->
    <div id="payRejectModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
        <div style="background:white;border-radius:16px;padding:1.75rem;width:100%;max-width:480px;margin:1rem;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
            <h3 style="margin:0 0 4px;font-size:1.15rem;color:#1a1a1a;">Reject Payment</h3>
            <p style="font-size:0.82rem;color:#64748b;margin:0 0 1.25rem;" id="prModalTitle"></p>
            <form method="POST" id="prForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="reject_payment_id" id="prOrderId">
                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-weight:600;font-size:0.85rem;color:#333;margin-bottom:0.4rem;">Rejection Reason <span style="color:#ef4444;">*</span></label>
                    <textarea name="rejection_reason" id="prReason" rows="3" required
                        style="width:100%;padding:0.7rem;border:1px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:0.85rem;resize:vertical;outline:none;box-sizing:border-box;"
                        onfocus="this.style.borderColor='#6B21A8'" onblur="this.style.borderColor='#e2e8f0'"
                        placeholder="e.g. Payment slip unclear, incorrect amount, fake transaction ID..."></textarea>
                </div>
                <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:10px 12px;margin-bottom:1rem;font-size:0.8rem;color:#92400e;">
                    <strong>Note:</strong> The buyer will be notified by email and the reason will be shown on their dashboard. Stock will be restored automatically.
                </div>
                <div style="display:flex;gap:0.6rem;justify-content:flex-end;">
                    <button type="button" onclick="closePRModal()" style="padding:0.55rem 1rem;border:1px solid #e2e8f0;border-radius:8px;background:white;color:#555;font-weight:600;cursor:pointer;font-size:0.85rem;">Cancel</button>
                    <button type="submit" style="padding:0.55rem 1.2rem;border:none;border-radius:8px;background:#ef4444;color:white;font-weight:600;cursor:pointer;font-size:0.85rem;display:inline-flex;align-items:center;gap:5px;">
                        <ion-icon name="close-circle-outline"></ion-icon> Reject Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openPayRejectModal(oid, title) {
            document.getElementById('prOrderId').value = oid;
            document.getElementById('prModalTitle').textContent = 'Order #' + oid + ' — ' + title;
            document.getElementById('prReason').value = '';
            const m = document.getElementById('payRejectModal');
            m.style.display = 'flex';
            setTimeout(() => document.getElementById('prReason').focus(), 100);
        }
        function closePRModal() { document.getElementById('payRejectModal').style.display = 'none'; }
        document.getElementById('payRejectModal').addEventListener('click', function(e) { if (e.target === this) closePRModal(); });
    </script>
</body>
</html>
