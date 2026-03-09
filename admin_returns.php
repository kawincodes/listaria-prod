<?php
session_start();
require 'includes/db.php';
$activePage = 'returns';

// Check Admin Access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$message = '';

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnId = $_POST['return_id'] ?? '';
    $newStatus = $_POST['new_status'] ?? '';
    $pickupDate = $_POST['pickup_date'] ?? null;
    $expectedDate = $_POST['expected_return_date'] ?? null;
    $success = false;

    if ($returnId && $newStatus) {
        // Build Query
        $query = "UPDATE returns SET status = ?, updated_at = CURRENT_TIMESTAMP";
        $params = [$newStatus];

        // Always update dates if provided, or keep existing if not (logic depends on needs, 
        // but here we likely want to save them if they are in the form)
        // Actually, better to only update if not empty, OR if we want to allow clearing, we need logic.
        // For simplicity: If provided in POST, update it.
        
        if ($pickupDate !== null) { // Input exists in form
             $query .= ", pickup_date = ?";
             $params[] = $pickupDate ?: null; // Handle empty string as null
        }
        if ($expectedDate !== null) {
             $query .= ", expected_return_date = ?";
             $params[] = $expectedDate ?: null;
        }

        $query .= " WHERE id = ?";
        $params[] = $returnId;

        $stmt = $pdo->prepare($query);
        $success = $stmt->execute($params);

        if ($success) {
            $message = '<div class="alert success">Status updated to: ' . ucfirst(str_replace('_', ' ', $newStatus)) . '</div>';
            
            // Order Status Sync
            if ($newStatus == 'approved') {
                $stmt = $pdo->prepare("SELECT order_id, product_id FROM returns WHERE id = ?");
                $stmt->execute([$returnId]);
                $rDetails = $stmt->fetch();
                if($rDetails) {
                    $pdo->prepare("UPDATE orders SET order_status = 'Return Approved' WHERE id = ?")->execute([$rDetails['order_id']]);
                    // Relist product: set status to active and approval_status to approved
                    $pdo->prepare("UPDATE products SET status = 'active', approval_status = 'approved' WHERE id = ?")->execute([$rDetails['product_id']]);
                }
            }
            if ($newStatus == 'refunded') {
                $stmt = $pdo->prepare("SELECT order_id, product_id FROM returns WHERE id = ?");
                $stmt->execute([$returnId]);
                $rDetails = $stmt->fetch();
                if($rDetails) {
                    $pdo->prepare("UPDATE orders SET order_status = 'Refunded' WHERE id = ?")->execute([$rDetails['order_id']]);
                    // Relist product: set status to active and approval_status to approved
                    $pdo->prepare("UPDATE products SET status = 'active', approval_status = 'approved' WHERE id = ?")->execute([$rDetails['product_id']]);
                }
            }
        } else {
            $message = '<div class="alert error">Failed to update status.</div>';
        }
    }
}

// Fetch Returns
$stmt = $pdo->query("
    SELECT r.*, u.full_name as buyer_name, u.email as buyer_email, u.phone as buyer_phone, p.title as product_title, o.amount
    FROM returns r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    JOIN orders o ON r.order_id = o.id
    ORDER BY r.created_at DESC
");
$returns = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Returns - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root { --primary: #6B21A8; --bg: #f8f9fa; --sidebar-bg: #1a1a1a; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; display:flex; color: #333; }
        
        /* Sidebar Styling */
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
            color: #a1a1aa; 
            text-decoration: none; 
            border-radius: 12px; 
            margin-bottom: 0.5rem; 
            transition: all 0.3s ease; 
            font-weight: 500;
        }
        .menu-item:hover, .menu-item.active { 
            background: #6B21A8; 
            color: white; 
        }

        /* Main Content */
        .main-content { margin-left: 260px; padding: 2.5rem; width: calc(100% - 260px); min-height: 100vh; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1e293b; }
        
        /* Table Styles */
        .table-container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1.2rem; text-align: left; border-bottom: 1px solid #f1f5f9; }
        th { background: #f8fafc; color: #64748b; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; }
        
        .btn-action { border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; margin-right: 5px; font-size: 0.8rem; }
        .btn-approve { background: #dcfce7; color: #166534; }
        .btn-reject { background: #fee2e2; color: #991b1b; }
        .btn-primary { background: #dbeafe; color: #1e40af; }
        .btn-warning { background: #fef9c3; color: #854d0e; }
        .btn-success { background: #22c55e; color: white; }
        
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 6px; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
        
        .status-pill {
            display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
        }
        .form-select, .form-input { padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; width: 100%; font-size: 0.85rem; }
        .dynamic-input { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-5px); } to { opacity:1; transform:translateY(0); } }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="header"><h1>Returns</h1></div>
        <?php echo $message; ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr><th>ID</th><th>Product</th><th>Buyer</th><th>Evidence</th><th>Reason</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if(count($returns) > 0): ?>
                        <?php foreach($returns as $r): 
                            $st = $r['status'];
                            $bg = '#f3f4f6'; $col = '#111';
                            if($st=='pending') { $bg='#fef9c3'; $col='#854d0e'; }
                            if($st=='approved') { $bg='#dcfce7'; $col='#166534'; }
                            if($st=='rejected') { $bg='#fee2e2'; $col='#991b1b'; }
                            if($st=='pickup_scheduled') { $bg='#e0f2fe'; $col='#075985'; }
                            if($st=='collected') { $bg='#f3e8ff'; $col='#6b21a8'; }
                            if($st=='refunded') { $bg='#22c55e'; $col='#fff'; }
                        ?>
                        <tr>
                            <td>#<?php echo $r['id']; ?></td>
                            <td><?php echo htmlspecialchars($r['product_title']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($r['buyer_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($r['buyer_email']); ?></small><br>
                                <small style="color:#666;">Phone: <?php echo htmlspecialchars($r['buyer_phone'] ?? 'N/A'); ?></small>
                            </td>
                            <td>
                                <?php if($r['evidence_photos']): ?>
                                    <button class="btn-action btn-success" style="padding: 4px 8px; font-size: 0.7rem;" 
                                            onclick='openEvidenceModal(<?php echo json_encode($r['evidence_photos']); ?>, <?php echo json_encode($r['evidence_video']); ?>)'>
                                        <ion-icon name="images-outline"></ion-icon> View
                                    </button>
                                <?php else: ?>
                                    <small style="color:#999;">No Evidence</small>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($r['reason']); ?></strong><br><small><?php echo htmlspecialchars($r['details']); ?></small></td>
                            <td>
                                <span class="status-pill" style="background:<?php echo $bg; ?>; color:<?php echo $col; ?>;">
                                    <?php echo ucfirst(str_replace('_', ' ', $st)); ?>
                                </span>
                            </td>
                            </td>
                            <td>
                                <!-- Status Control Form -->
                                <form method="POST" style="display:flex; flex-direction:column; gap:8px;">
                                    <input type="hidden" name="return_id" value="<?php echo $r['id']; ?>">
                                    
                                    <select name="new_status" class="form-select action-select" onchange="toggleInputs(this)" required>
                                        <option value="pending" <?php echo $st=='pending'?'selected':''; ?>>Pending</option>
                                        <option value="approved" <?php echo $st=='approved'?'selected':''; ?>>Approved</option>
                                        <option value="rejected" <?php echo $st=='rejected'?'selected':''; ?>>Rejected</option>
                                        <option value="pickup_scheduled" <?php echo $st=='pickup_scheduled'?'selected':''; ?>>Pickup Scheduled</option>
                                        <option value="collected" <?php echo $st=='collected'?'selected':''; ?>>Collected</option>
                                        <option value="refunded" <?php echo $st=='refunded'?'selected':''; ?>>Refunded</option>
                                    </select>

                                    <!-- Pickup Date Input -->
                                    <div class="dynamic-input pickup-input" style="display:<?php echo ($st=='pickup_scheduled' || $st=='collected') ? 'block' : 'none'; ?>;">
                                        <label style="font-size:0.75rem; color:#666;">Pickup Date:</label>
                                        <input type="date" name="pickup_date" class="form-input" value="<?php echo $r['pickup_date'] ?? ''; ?>">
                                    </div>
                                    
                                    <!-- Expected Return Date Input -->
                                    <div class="dynamic-input expected-input" style="display:<?php echo in_array($st, ['approved','pickup_scheduled','collected']) ? 'block' : 'none'; ?>;">
                                        <label style="font-size:0.75rem; color:#666;">Expected Return:</label>
                                        <input type="date" name="expected_return_date" class="form-input" 
                                            value="<?php echo $r['expected_return_date'] ?? ''; ?>">
                                    </div>

                                    <button class="btn-action btn-primary" style="margin-top:5px; width:100%;">Update Status</button>
                                </form>
                                 <?php if($r['expected_return_date']): ?>
                                    <div style="font-size:0.75rem; color:#555; margin-top:8px;">
                                        Exp: <strong><?php echo date('M j', strtotime($r['expected_return_date'])); ?></strong>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:2rem; color:#999;">No return requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    </main>
    <script>
    function toggleInputs(select) {
        const form = select.closest('form');
        const pickupInput = form.querySelector('.pickup-input');
        const expectedInput = form.querySelector('.expected-input');
        const val = select.value;
        
        // Logic for showing inputs
        const showPickup = (val === 'pickup_scheduled' || val === 'collected');
        const showExpected = (val === 'approved' || val === 'pickup_scheduled' || val === 'collected');

        if(pickupInput) pickupInput.style.display = showPickup ? 'block' : 'none';
        if(expectedInput) expectedInput.style.display = showExpected ? 'block' : 'none';
    }
    </script>

    <!-- Evidence Modal -->
    <div id="evidenceModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Return Evidence</h3>
                <span class="close-modal" onclick="closeEvidenceModal()">&times;</span>
            </div>
            <div class="modal-body" style="padding:20px; max-height: 70vh; overflow-y:auto;">
                <div id="photosContainer" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap:10px; margin-bottom:20px;">
                    <!-- Photos will load here -->
                </div>
                <div id="videoContainer">
                    <!-- Video will load here -->
                </div>
            </div>
        </div>
    </div>

    <style>
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7); z-index: 1000;
            display: flex; align-items: center; justify-content: center;
            backdrop-filter: blur(5px);
        }
        .modal-content {
            background: white; border-radius: 12px; width: 90%; max-width: 600px;
            overflow: hidden; animation: zoomIn 0.3s ease;
        }
        @keyframes zoomIn { from { opacity:0; transform: scale(0.9); } to { opacity:1; transform: scale(1); } }
        .modal-header { padding: 15px 20px; background: #f8fafc; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin:0; font-size: 1.1rem; }
        .close-modal { cursor: pointer; font-size: 1.5rem; color: #999; }
        .evidence-img { width: 100%; height: 120px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 1px solid #eee; }
    </style>

    <script>
    function openEvidenceModal(photos, video) {
        const photosDiv = document.getElementById('photosContainer');
        const videoDiv = document.getElementById('videoContainer');
        photosDiv.innerHTML = '';
        videoDiv.innerHTML = '';

        if (photos) {
            try {
                const photoPaths = JSON.parse(photos);
                photoPaths.forEach(path => {
                    const img = document.createElement('img');
                    img.src = path;
                    img.className = 'evidence-img';
                    img.onclick = () => window.open(path, '_blank');
                    photosDiv.appendChild(img);
                });
            } catch (e) {
                console.error("Error parsing photos:", e);
            }
        }

        if (video) {
            const vid = document.createElement('video');
            vid.src = video;
            vid.controls = true;
            vid.style.width = '100%';
            vid.style.borderRadius = '8px';
            videoDiv.appendChild(vid);
        }

        document.getElementById('evidenceModal').style.display = 'flex';
    }

    function closeEvidenceModal() {
        document.getElementById('evidenceModal').style.display = 'none';
        const videoDiv = document.getElementById('videoContainer');
        videoDiv.innerHTML = ''; // Stop video
    }
    </script>
</body>
</html>
