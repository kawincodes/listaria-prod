<?php
session_start();
require 'includes/db.php';
$activePage = 'returns';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnId = $_POST['return_id'] ?? '';
    $newStatus = $_POST['new_status'] ?? '';
    $pickupDate = $_POST['pickup_date'] ?? null;
    $expectedDate = $_POST['expected_return_date'] ?? null;
    $success = false;

    if ($returnId && $newStatus) {
        $query = "UPDATE returns SET status = ?, updated_at = CURRENT_TIMESTAMP";
        $params = [$newStatus];

        if ($pickupDate !== null) {
             $query .= ", pickup_date = ?";
             $params[] = $pickupDate ?: null;
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
            $message = '<div class="alert success"><ion-icon name="checkmark-circle"></ion-icon> Status updated to: ' . ucfirst(str_replace('_', ' ', $newStatus)) . '</div>';
            
            if ($newStatus == 'approved') {
                $stmt = $pdo->prepare("SELECT order_id, product_id FROM returns WHERE id = ?");
                $stmt->execute([$returnId]);
                $rDetails = $stmt->fetch();
                if($rDetails) {
                    $pdo->prepare("UPDATE orders SET order_status = 'Return Approved' WHERE id = ?")->execute([$rDetails['order_id']]);
                    $pdo->prepare("UPDATE products SET status = 'active', approval_status = 'approved' WHERE id = ?")->execute([$rDetails['product_id']]);
                }
            }
            if ($newStatus == 'refunded') {
                $stmt = $pdo->prepare("SELECT order_id, product_id FROM returns WHERE id = ?");
                $stmt->execute([$returnId]);
                $rDetails = $stmt->fetch();
                if($rDetails) {
                    $pdo->prepare("UPDATE orders SET order_status = 'Refunded' WHERE id = ?")->execute([$rDetails['order_id']]);
                    $pdo->prepare("UPDATE products SET status = 'active', approval_status = 'approved' WHERE id = ?")->execute([$rDetails['product_id']]);
                }
            }
        } else {
            $message = '<div class="alert error"><ion-icon name="alert-circle"></ion-icon> Failed to update status.</div>';
        }
    }
}

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
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; display: flex; color: #333; }
        
        .sidebar { 
            width: 260px; 
            background: var(--sidebar-bg); 
            height: 100vh; 
            position: fixed; 
            padding: 2rem 1.5rem; 
            color: white;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }
        .brand { font-size: 1.2rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem; text-decoration: none; }
        
        
        .menu-item ion-icon { font-size: 1.2rem; }

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

        .alert { 
            padding: 1rem; 
            margin-bottom: 1.5rem; 
            border-radius: 10px; 
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert.success { background: #f0fdf4; color: #22c55e; }
        .alert.error { background: #fef2f2; color: #ef4444; }

        .table-container { 
            background: white; 
            border-radius: 16px; 
            overflow-x: auto; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); 
            border: 1px solid #f0f0f0;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            min-width: 900px;
        }

        th, td { 
            padding: 1rem 1.2rem; 
            text-align: left; 
            border-bottom: 1px solid #f0f0f0; 
            vertical-align: top;
            font-size: 0.88rem;
        }

        th { 
            background: #fafafa; 
            color: #888; 
            font-weight: 600; 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }

        .buyer-info strong { display: block; font-size: 0.88rem; color: #1a1a1a; }
        .buyer-info small { display: block; color: #888; font-size: 0.78rem; margin-top: 2px; }

        .reason-cell strong { display: block; font-size: 0.85rem; color: #1a1a1a; margin-bottom: 2px; }
        .reason-cell small { color: #888; font-size: 0.78rem; line-height: 1.4; }

        .status-pill {
            display: inline-block; 
            padding: 4px 10px; 
            border-radius: 20px; 
            font-size: 0.72rem; 
            font-weight: 700; 
            text-transform: uppercase;
            white-space: nowrap;
        }

        .actions-cell { min-width: 180px; }

        .actions-cell form {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-select { 
            padding: 7px 10px; 
            border: 1px solid #e5e5e5; 
            border-radius: 8px; 
            width: 100%; 
            font-size: 0.82rem; 
            font-family: 'Inter', sans-serif;
            background: white;
        }
        .form-select:focus { outline: none; border-color: #6B21A8; }

        .form-input { 
            padding: 7px 10px; 
            border: 1px solid #e5e5e5; 
            border-radius: 8px; 
            width: 100%; 
            font-size: 0.82rem; 
            font-family: 'Inter', sans-serif;
        }
        .form-input:focus { outline: none; border-color: #6B21A8; }

        .date-label { 
            font-size: 0.72rem; 
            color: #888; 
            margin-bottom: 2px; 
            display: block; 
        }

        .dynamic-input { 
            animation: fadeIn 0.3s ease; 
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

        .btn-update {
            border: none;
            padding: 7px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.82rem;
            background: #6B21A8;
            color: white;
            width: 100%;
            transition: background 0.2s;
            font-family: 'Inter', sans-serif;
        }
        .btn-update:hover { background: #581c87; }

        .btn-evidence {
            border: none;
            padding: 5px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.75rem;
            background: #f0fdf4;
            color: #22c55e;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.2s;
        }
        .btn-evidence:hover { background: #dcfce7; }

        .exp-date { 
            font-size: 0.75rem; 
            color: #888; 
            margin-top: 6px; 
        }
        .exp-date strong { color: #6B21A8; }

        .no-evidence { color: #ccc; font-size: 0.8rem; }

        .empty-row td { 
            text-align: center; 
            padding: 3rem; 
            color: #ccc; 
            font-size: 0.9rem; 
        }

        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7); z-index: 1000;
            display: flex; align-items: center; justify-content: center;
            backdrop-filter: blur(5px);
        }
        .modal-content {
            background: white; border-radius: 16px; width: 90%; max-width: 600px;
            overflow: hidden; animation: zoomIn 0.3s ease;
        }
        @keyframes zoomIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        .modal-header { 
            padding: 15px 20px; 
            background: #fafafa; 
            border-bottom: 1px solid #f0f0f0; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .modal-header h3 { margin: 0; font-size: 1.1rem; color: #1a1a1a; }
        .close-modal { cursor: pointer; font-size: 1.5rem; color: #999; transition: color 0.2s; }
        .close-modal:hover { color: #333; }
        .evidence-img { width: 100%; height: 120px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 1px solid #f0f0f0; }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <h1>Returns & Refunds</h1>
            <div>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
        </div>

        <?php echo $message; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Buyer</th>
                        <th>Evidence</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($returns) > 0): ?>
                        <?php foreach($returns as $r): 
                            $st = $r['status'];
                            $bg = '#f3f4f6'; $col = '#333';
                            if($st=='pending') { $bg='#fef9c3'; $col='#854d0e'; }
                            if($st=='approved') { $bg='#dcfce7'; $col='#166534'; }
                            if($st=='rejected') { $bg='#fee2e2'; $col='#991b1b'; }
                            if($st=='pickup_scheduled') { $bg='#f3e8ff'; $col='#6b21a8'; }
                            if($st=='collected') { $bg='#ede9fe'; $col='#5b21b6'; }
                            if($st=='refunded') { $bg='#dcfce7'; $col='#166534'; }
                        ?>
                        <tr>
                            <td>#<?php echo $r['id']; ?></td>
                            <td><?php echo htmlspecialchars($r['product_title']); ?></td>
                            <td class="buyer-info">
                                <strong><?php echo htmlspecialchars($r['buyer_name']); ?></strong>
                                <small><?php echo htmlspecialchars($r['buyer_email']); ?></small>
                                <small>Phone: <?php echo htmlspecialchars($r['buyer_phone'] ?? 'N/A'); ?></small>
                            </td>
                            <td>
                                <?php if($r['evidence_photos']): ?>
                                    <button class="btn-evidence" 
                                            onclick='openEvidenceModal(<?php echo json_encode($r["evidence_photos"]); ?>, <?php echo json_encode($r["evidence_video"]); ?>)'>
                                        <ion-icon name="images-outline"></ion-icon> View
                                    </button>
                                <?php else: ?>
                                    <span class="no-evidence">No Evidence</span>
                                <?php endif; ?>
                            </td>
                            <td class="reason-cell">
                                <strong><?php echo htmlspecialchars($r['reason']); ?></strong>
                                <small><?php echo htmlspecialchars($r['details']); ?></small>
                            </td>
                            <td>
                                <span class="status-pill" style="background:<?php echo $bg; ?>; color:<?php echo $col; ?>;">
                                    <?php echo ucfirst(str_replace('_', ' ', $st)); ?>
                                </span>
                            </td>
                            <td class="actions-cell">
                                <form method="POST">
                                    <input type="hidden" name="return_id" value="<?php echo $r['id']; ?>">
                                    
                                    <select name="new_status" class="form-select action-select" onchange="toggleInputs(this)" required>
                                        <option value="pending" <?php echo $st=='pending'?'selected':''; ?>>Pending</option>
                                        <option value="approved" <?php echo $st=='approved'?'selected':''; ?>>Approved</option>
                                        <option value="rejected" <?php echo $st=='rejected'?'selected':''; ?>>Rejected</option>
                                        <option value="pickup_scheduled" <?php echo $st=='pickup_scheduled'?'selected':''; ?>>Pickup Scheduled</option>
                                        <option value="collected" <?php echo $st=='collected'?'selected':''; ?>>Collected</option>
                                        <option value="refunded" <?php echo $st=='refunded'?'selected':''; ?>>Refunded</option>
                                    </select>

                                    <div class="dynamic-input pickup-input" style="display:<?php echo ($st=='pickup_scheduled' || $st=='collected') ? 'block' : 'none'; ?>;">
                                        <span class="date-label">Pickup Date:</span>
                                        <input type="date" name="pickup_date" class="form-input" value="<?php echo $r['pickup_date'] ?? ''; ?>">
                                    </div>
                                    
                                    <div class="dynamic-input expected-input" style="display:<?php echo in_array($st, ['approved','pickup_scheduled','collected']) ? 'block' : 'none'; ?>;">
                                        <span class="date-label">Expected Return:</span>
                                        <input type="date" name="expected_return_date" class="form-input" 
                                            value="<?php echo $r['expected_return_date'] ?? ''; ?>">
                                    </div>

                                    <button type="submit" class="btn-update">Update Status</button>
                                </form>
                                <?php if($r['expected_return_date']): ?>
                                    <div class="exp-date">
                                        Exp: <strong><?php echo date('M j', strtotime($r['expected_return_date'])); ?></strong>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="empty-row"><td colspan="7">No return requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="evidenceModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Return Evidence</h3>
                <span class="close-modal" onclick="closeEvidenceModal()">&times;</span>
            </div>
            <div class="modal-body" style="padding:20px; max-height: 70vh; overflow-y:auto;">
                <div id="photosContainer" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap:10px; margin-bottom:20px;"></div>
                <div id="videoContainer"></div>
            </div>
        </div>
    </div>

    <script>
    function toggleInputs(select) {
        const form = select.closest('form');
        const pickupInput = form.querySelector('.pickup-input');
        const expectedInput = form.querySelector('.expected-input');
        const val = select.value;
        
        const showPickup = (val === 'pickup_scheduled' || val === 'collected');
        const showExpected = (val === 'approved' || val === 'pickup_scheduled' || val === 'collected');

        if(pickupInput) pickupInput.style.display = showPickup ? 'block' : 'none';
        if(expectedInput) expectedInput.style.display = showExpected ? 'block' : 'none';
    }

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
        document.getElementById('videoContainer').innerHTML = '';
    }
    </script>
</body>
</html>
