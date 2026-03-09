<?php
session_start();
require 'includes/db.php';

function sendApprovalEmail($pdo, $productId) {
    try {
        $stmt = $pdo->prepare("SELECT p.title, u.email, u.full_name FROM products p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
        $stmt->execute([$productId]);
        $data = $stmt->fetch();
        
        if (!$data) return;

        $smtp = createSmtp($pdo);
        
        $subject = "Your Listing has been Approved! - Listaria";
        $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                <h2 style='color: #6B21A8;'>Congratulations " . htmlspecialchars($data['full_name']) . "!</h2>
                <p>We are happy to inform you that your product listing <strong>\"" . htmlspecialchars($data['title']) . "\"</strong> has been approved by our team.</p>
                <p>It is now live on the platform and visible to potential buyers.</p>
                <p>You can manage your listings from your <a href='https://listaria.in/profile.php' style='color: #6B21A8; text-decoration: none; font-weight: bold;'>Dashboard</a>.</p>
                <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 12px; color: #666;'>This is an automated message, please do not reply to this email.</p>
            </div>
        ";
        
        $smtp->send($data['email'], $subject, $body, 'Listaria Team');
    } catch (Exception $e) {
        error_log("Failed to send approval email for product $productId: " . $e->getMessage());
    }
}

$activePage = 'listings';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$msg = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = $_POST['product_id'] ?? null;
    
    if (isset($_POST['approve'])) {
        $pdo->prepare("UPDATE products SET approval_status = 'approved' WHERE id = ?")->execute([$pid]);
        sendApprovalEmail($pdo, $pid);
        $msg = "Listing approved.";
    }
    
    if (isset($_POST['reject'])) {
        $pdo->prepare("UPDATE products SET approval_status = 'rejected' WHERE id = ?")->execute([$pid]);
        $msg = "Listing rejected.";
    }
    
    if (isset($_POST['feature'])) {
        $pdo->prepare("UPDATE products SET is_featured = 1 WHERE id = ?")->execute([$pid]);
        $msg = "Listing featured.";
    }
    
    if (isset($_POST['unfeature'])) {
        $pdo->prepare("UPDATE products SET is_featured = 0 WHERE id = ?")->execute([$pid]);
        $msg = "Listing unfeatured.";
    }
    
    if (isset($_POST['boost'])) {
        $boostDays = (int)$_POST['boost_days'];
        $boostUntil = date('Y-m-d H:i:s', strtotime("+$boostDays days"));
        $pdo->prepare("UPDATE products SET boosted_until = ? WHERE id = ?")->execute([$boostUntil, $pid]);
        $msg = "Listing boosted for $boostDays days.";
    }
    
    if (isset($_POST['delete'])) {
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$pid]);
        $msg = "Listing deleted.";
    }
    
    if (isset($_POST['bulk_action']) && !empty($_POST['selected'])) {
        $action = $_POST['bulk_action'];
        $ids = $_POST['selected'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        switch($action) {
            case 'approve':
                $pdo->prepare("UPDATE products SET approval_status = 'approved' WHERE id IN ($placeholders)")->execute($ids);
                foreach ($ids as $id) {
                    sendApprovalEmail($pdo, $id);
                }
                $msg = count($ids) . " listings approved.";
                break;
            case 'reject':
                $pdo->prepare("UPDATE products SET approval_status = 'rejected' WHERE id IN ($placeholders)")->execute($ids);
                $msg = count($ids) . " listings rejected.";
                break;
            case 'delete':
                $pdo->prepare("DELETE FROM products WHERE id IN ($placeholders)")->execute($ids);
                $msg = count($ids) . " listings deleted.";
                break;
        }
    }
    
    // Log activity
    try {
        $pdo->prepare("INSERT INTO admin_activity_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)")
            ->execute([$_SESSION['user_id'], "Listing action", $msg, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch(Exception $e) {}
}

// Filters
$filter = $_GET['filter'] ?? 'all';
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "
    SELECT p.*, u.full_name as seller_name, u.email as seller_email,
           (SELECT COUNT(*) FROM orders WHERE product_id = p.id) as order_count
    FROM products p
    JOIN users u ON p.user_id = u.id
    WHERE 1=1
";

if ($filter === 'pending') {
    $sql .= " AND (p.approval_status = 'pending' OR p.approval_status IS NULL)";
} elseif ($filter === 'approved') {
    $sql .= " AND p.approval_status = 'approved'";
} elseif ($filter === 'rejected') {
    $sql .= " AND p.approval_status = 'rejected'";
} elseif ($filter === 'featured') {
    $sql .= " AND p.is_featured = 1";
} elseif ($filter === 'boosted') {
    $sql .= " AND p.boosted_until > datetime('now')";
}

if ($category) {
    $sql .= " AND p.category = '$category'";
}

if ($search) {
    $sql .= " AND (p.title LIKE '%$search%' OR p.description LIKE '%$search%' OR u.full_name LIKE '%$search%')";
}

$sql .= " ORDER BY p.created_at DESC";
$products = $pdo->query($sql)->fetchAll();

// Stats
$totalListings = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$pendingListings = $pdo->query("SELECT COUNT(*) FROM products WHERE approval_status = 'pending' OR approval_status IS NULL")->fetchColumn();
$featuredListings = $pdo->query("SELECT COUNT(*) FROM products WHERE is_featured = 1")->fetchColumn();
$boostedListings = $pdo->query("SELECT COUNT(*) FROM products WHERE boosted_until > datetime('now')")->fetchColumn();

// Categories
$categories = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Listings - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root { --primary: #6B21A8; --bg: #f8f9fa; --sidebar-bg: #1a1a1a; --text-light: #a1a1aa; }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; display:flex; color: #333; }
        
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; padding: 2rem 1.5rem; color: white; z-index: 100; display: flex; flex-direction: column; }
        .brand { font-size: 1.4rem; font-weight: 800; color: white; display:flex; align-items: center; gap: 10px; margin-bottom: 3rem; text-decoration:none; }
        .menu-item { display: flex; align-items: center; gap: 12px; padding: 1rem; color: var(--text-light); text-decoration: none; border-radius: 12px; margin-bottom: 0.5rem; transition: all 0.2s; font-weight: 500; }
        .menu-item:hover, .menu-item.active { background: #6B21A8; color: white; }
        .menu-item ion-icon { font-size: 1.2rem; }
        
        .main-content { margin-left: 260px; padding: 2rem 2.5rem; width: calc(100% - 260px); min-height: 100vh; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }
        
        .msg-success { background: #f0fdf4; color: #22c55e; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 500; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
        
        .stat-card { background: white; padding: 1.25rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #f0f0f0; }
        .stat-card .icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 0.75rem; }
        .stat-card .icon.purple { background: #f3e8ff; color: #6B21A8; }
        .stat-card .icon.orange { background: #fef3c7; color: #d97706; }
        .stat-card .icon.blue { background: #dbeafe; color: #2563eb; }
        .stat-card .icon.green { background: #dcfce7; color: #22c55e; }
        .stat-card .value { font-size: 1.75rem; font-weight: 700; color: #1a1a1a; }
        .stat-card .label { font-size: 0.8rem; color: #666; margin-top: 0.25rem; }
        
        .filters { display: flex; gap: 0.75rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center; }
        
        .filter-btn { padding: 0.6rem 1rem; border: 1px solid #e5e5e5; background: white; border-radius: 8px; cursor: pointer; font-size: 0.85rem; font-weight: 500; color: #666; transition: all 0.2s; text-decoration: none; }
        .filter-btn:hover { border-color: #6B21A8; color: #6B21A8; }
        .filter-btn.active { background: #6B21A8; color: white; border-color: #6B21A8; }
        
        .search-box { display: flex; gap: 0.5rem; margin-left: auto; }
        .search-box input, .search-box select { padding: 0.6rem 1rem; border: 1px solid #e5e5e5; border-radius: 8px; font-size: 0.9rem; }
        .search-box input:focus, .search-box select:focus { outline: none; border-color: #6B21A8; }
        
        .btn { padding: 0.6rem 1rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: #6B21A8; color: white; }
        .btn-primary:hover { background: #581c87; }
        .btn-dark { background: #1a1a1a; color: white; }
        .btn-success { background: #dcfce7; color: #22c55e; }
        .btn-warning { background: #fef3c7; color: #d97706; }
        .btn-danger { background: #fee2e2; color: #ef4444; }
        .btn-sm { padding: 0.4rem 0.75rem; font-size: 0.75rem; }
        
        .table-container { background: white; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #f0f0f0; margin-bottom: 3rem; }
        
        .table-header { padding: 1rem 1.5rem; background: #fafafa; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 1rem; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.25rem; text-align: left; font-size: 0.85rem; }
        th { background: #fafafa; color: #666; font-weight: 600; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.5px; border-bottom: 1px solid #f0f0f0; }
        td { color: #333; border-bottom: 1px solid #f5f5f5; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }
        
        .product-info { display: flex; align-items: center; gap: 12px; }
        
        .product-thumb { width: 60px; height: 60px; border-radius: 8px; object-fit: cover; background: #f0f0f0; }
        
        .product-name { font-weight: 600; color: #1a1a1a; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .product-seller { font-size: 0.8rem; color: #999; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 600; text-transform: uppercase; }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-approved { background: #dcfce7; color: #22c55e; }
        .badge-rejected { background: #fee2e2; color: #ef4444; }
        .badge-sold { background: #000000; color: #ffffff; }
        .badge-featured { background: #f3e8ff; color: #6B21A8; }
        .badge-boosted { background: #dbeafe; color: #2563eb; }
        
        .price { font-weight: 700; color: #22c55e; }
        
        .checkbox-cell { width: 40px; }
        .checkbox-cell input { width: 16px; height: 16px; accent-color: #6B21A8; }
        
        .actions-dropdown { position: relative; display: inline-block; }
        .dropdown-content { display: none; position: absolute; right: 0; background: white; min-width: 160px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); border-radius: 10px; z-index: 100; padding: 0.5rem; }
        .actions-dropdown:hover .dropdown-content { display: block; }
        .dropdown-item { display: flex; align-items: center; gap: 8px; padding: 0.6rem 0.75rem; border: none; background: none; width: 100%; text-align: left; cursor: pointer; font-size: 0.85rem; border-radius: 6px; color: #333; }
        .dropdown-item:hover { background: #f5f5f5; }
        .dropdown-item.danger { color: #ef4444; }
        .dropdown-item.danger:hover { background: #fef2f2; }
        
        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        
        /* Sidebar Drawer Styles */
        .drawer-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4); z-index: 998; 
            display: none; opacity: 0; transition: opacity 0.3s;
        }
        .drawer-overlay.open { display: block; opacity: 1; }
        
        .right-drawer {
            position: fixed; top: 0; right: -420px; width: 420px; height: 100vh;
            background: white; box-shadow: -4px 0 24px rgba(0,0,0,0.15); z-index: 999;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 0; display: flex; flex-direction: column;
            transform: translateX(0);
        }
        .right-drawer.open { transform: translateX(-420px); }
        
        .drawer-toggle {
            position: fixed; top: 50%; right: 0; transform: translateY(-50%);
            background: white; color: #6B21A8; border: 1px solid #e5e5e5; border-right: none;
            padding: 1.5rem 0.4rem; border-radius: 12px 0 0 12px; cursor: pointer;
            z-index: 997; box-shadow: -4px 0 12px rgba(0,0,0,0.05);
            transition: all 0.2s;
            display: flex; align-items: center; justify-content: center;
        }
        .drawer-toggle:hover { background: #f9f9f9; width: 40px; }
        .drawer-toggle ion-icon { font-size: 1.5rem; }
        
        .drawer-header {
            padding: 1.5rem; border-bottom: 1px solid #f0f0f0;
            display: flex; justify-content: space-between; align-items: center;
        }
        .drawer-header h3 { margin: 0; font-size: 1.1rem; color: #1a1a1a; }
        .close-drawer {
            background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666;
            padding: 0.25rem; border-radius: 50%; transition: background 0.2s; display: flex;
        }
        .close-drawer:hover { background: #f0f0f0; color: #333; }
        
        .drawer-content { padding: 1.5rem; flex: 1; overflow-y: auto; }
        
        .drawer-placeholder {
            text-align: center; color: #999; margin-top: 50%; transform: translateY(-50%);
        }
        .drawer-placeholder ion-icon { font-size: 3rem; margin-bottom: 1rem; color: #e5e5e5; }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <div>
                <h1>Listings</h1>
                <p style="color:#666; margin-top:0.5rem;">Manage all product listings</p>
            </div>
        </div>

        <?php if($msg): ?>
            <div class="msg-success"><ion-icon name="checkmark-circle-outline"></ion-icon> <?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon purple"><ion-icon name="pricetags-outline"></ion-icon></div>
                <div class="value"><?php echo number_format($totalListings); ?></div>
                <div class="label">Total Listings</div>
            </div>
            <div class="stat-card">
                <div class="icon orange"><ion-icon name="time-outline"></ion-icon></div>
                <div class="value"><?php echo number_format($pendingListings); ?></div>
                <div class="label">Pending Approval</div>
            </div>
            <div class="stat-card">
                <div class="icon blue"><ion-icon name="star-outline"></ion-icon></div>
                <div class="value"><?php echo number_format($featuredListings); ?></div>
                <div class="label">Featured</div>
            </div>
            <div class="stat-card">
                <div class="icon green"><ion-icon name="rocket-outline"></ion-icon></div>
                <div class="value"><?php echo number_format($boostedListings); ?></div>
                <div class="label">Boosted</div>
            </div>
        </div>

        <div class="filters">
            <a href="?" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
            <a href="?filter=pending" class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="?filter=approved" class="filter-btn <?php echo $filter === 'approved' ? 'active' : ''; ?>">Approved</a>
            <a href="?filter=rejected" class="filter-btn <?php echo $filter === 'rejected' ? 'active' : ''; ?>">Rejected</a>
            <a href="?filter=featured" class="filter-btn <?php echo $filter === 'featured' ? 'active' : ''; ?>">Featured</a>
            <a href="?filter=boosted" class="filter-btn <?php echo $filter === 'boosted' ? 'active' : ''; ?>">Boosted</a>
            
            <form method="GET" class="search-box">
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary"><ion-icon name="search-outline"></ion-icon></button>
            </form>
        </div>

        <form method="POST" id="bulkForm"></form>
        <div class="table-container">
            <div class="table-header">
                <select name="bulk_action" form="bulkForm" style="padding:0.5rem; border:1px solid #e5e5e5; border-radius:6px;">
                    <option value="">Bulk Actions</option>
                    <option value="approve">Approve</option>
                    <option value="reject">Reject</option>
                    <option value="delete">Delete</option>
                </select>
                <button type="submit" form="bulkForm" class="btn btn-dark btn-sm">Apply</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th class="checkbox-cell"><input type="checkbox" id="selectAll"></th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Orders</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($products as $p): 
                        $status = $p['approval_status'] ?? 'pending';
                        $isSold = isset($p['status']) && $p['status'] === 'sold';
                        $isFeatured = $p['is_featured'] ?? 0;
                        $isBoosted = !empty($p['boosted_until']) && strtotime($p['boosted_until']) > time();
                        $images = json_decode($p['image_paths'] ?? '[]');
                        $image = !empty($images) ? $images[0] : 'uploads/placeholder.jpg';
                    ?>
                    <tr>
                        <td class="checkbox-cell">
                            <input type="checkbox" name="selected[]" form="bulkForm" value="<?php echo $p['id']; ?>">
                        </td>
                        <td>
                            <div class="product-info">
                                <img src="<?php echo htmlspecialchars($image); ?>" class="product-thumb" alt="">
                                <div>
                                    <div class="product-name"><?php echo htmlspecialchars($p['title']); ?></div>
                                    <div class="product-seller">by <?php echo htmlspecialchars($p['seller_name']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($p['category'] ?? 'Uncategorized'); ?></td>
                        <td><span class="price">₹<?php echo number_format($p['price_min']); ?></span></td>
                        <td>
                            <?php if($isSold): ?>
                                <span class="badge badge-sold">Sold</span>
                            <?php else: ?>
                                <span class="badge badge-<?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                            <?php endif; ?>
                            <?php if($isFeatured): ?><span class="badge badge-featured">Featured</span><?php endif; ?>
                            <?php if($isBoosted): ?><span class="badge badge-boosted">Boosted</span><?php endif; ?>
                        </td>
                        <td><?php echo $p['order_count']; ?></td>
                        <td><?php echo date('M j, Y', strtotime($p['created_at'])); ?></td>
                        <td>
                            <div class="actions-dropdown">
                                <button type="button" class="btn btn-dark btn-sm">
                                    <ion-icon name="ellipsis-vertical-outline"></ion-icon>
                                </button>
                                <div class="dropdown-content">
                                    <form method="POST">
                                        <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                        
                                        <?php if($status !== 'approved'): ?>
                                        <button type="submit" name="approve" class="dropdown-item">
                                            <ion-icon name="checkmark-circle-outline"></ion-icon> Approve
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if($status !== 'rejected'): ?>
                                        <button type="submit" name="reject" class="dropdown-item">
                                            <ion-icon name="close-circle-outline"></ion-icon> Reject
                                        </button>
                                        <?php endif; ?>
                                        
                                        <?php if(!$isFeatured): ?>
                                        <button type="submit" name="feature" class="dropdown-item">
                                            <ion-icon name="star-outline"></ion-icon> Feature
                                        </button>
                                        <?php else: ?>
                                        <button type="submit" name="unfeature" class="dropdown-item">
                                            <ion-icon name="star-outline"></ion-icon> Unfeature
                                        </button>
                                        <?php endif; ?>
                                        
                                        <div style="padding: 0.5rem; border-top: 1px solid #f0f0f0; margin-top: 0.5rem;">
                                            <div style="font-size:0.75rem; color:#999; margin-bottom:0.25rem;">Boost for:</div>
                                            <select name="boost_days" style="width:100%; padding:0.4rem; border:1px solid #e5e5e5; border-radius:4px; font-size:0.8rem;">
                                                <option value="7">7 days</option>
                                                <option value="14">14 days</option>
                                                <option value="30">30 days</option>
                                            </select>
                                            <button type="submit" name="boost" class="btn btn-primary btn-sm" style="width:100%; margin-top:0.25rem;">Boost</button>
                                        </div>
                                        
                                        <button type="submit" name="delete" class="dropdown-item danger" onclick="return confirm('Delete this listing?')">
                                            <ion-icon name="trash-outline"></ion-icon> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </div>
    </main>

    <div class="drawer-overlay" id="drawerOverlay"></div>
    
    <button class="drawer-toggle" id="drawerToggle" title="Open Sidebar">
        <ion-icon name="chevron-back-outline"></ion-icon>
    </button>
    
    <aside class="right-drawer" id="rightDrawer">
        <div class="drawer-header">
            <h3>Quick Details</h3>
            <button class="close-drawer" id="closeDrawer"><ion-icon name="close-outline"></ion-icon></button>
        </div>
        <div class="drawer-content" id="drawerContent">
            <div class="drawer-placeholder">
                <ion-icon name="information-circle-outline"></ion-icon>
                <p>Select a listing to view details</p>
                <p style="font-size:0.8rem; margin-top:0.5rem;">Click '...' on a row to see actions</p>
            </div>
        </div>
    </aside>

    <script>
        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('input[name="selected[]"]').forEach(cb => cb.checked = this.checked);
        });
        
        // Sidebar Logic
        const drawer = document.getElementById('rightDrawer');
        const overlay = document.getElementById('drawerOverlay');
        const toggle = document.getElementById('drawerToggle');
        const closeDir = document.getElementById('closeDrawer');
        
        function openSidebar() {
            drawer.classList.add('open');
            overlay.classList.add('open');
            toggle.style.display = 'none'; // Hide toggle when open
        }
        
        function closeSidebar() {
            drawer.classList.remove('open');
            overlay.classList.remove('open');
            setTimeout(() => { toggle.style.display = 'flex'; }, 300); // Show toggle after transition
        }
        
        toggle.addEventListener('click', openSidebar);
        overlay.addEventListener('click', closeSidebar);
        closeDir.addEventListener('click', closeSidebar);
    </script>
</body>
</html>
