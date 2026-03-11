<?php
require_once __DIR__ . '/includes/session.php';
require 'includes/db.php';

$activePage = 'listings';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$msg = '';
$msgType = 'success';
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!$productId) {
    header("Location: admin_listings.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $msg = 'Invalid security token. Please try again.';
        $msgType = 'error';
    } else {
    $title = trim($_POST['title'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price_min = floatval($_POST['price_min'] ?? 0);
    $price_max = floatval($_POST['price_max'] ?? 0);
    $allowed_conditions = ['Brand New', 'Lightly Used', 'Regularly Used'];
    $condition_tag = trim($_POST['condition_tag'] ?? '');
    if (!in_array($condition_tag, $allowed_conditions)) $condition_tag = 'Brand New';
    $quantity = (int)($_POST['quantity'] ?? 1);
    $status = trim($_POST['status'] ?? 'available');
    $approval_status = trim($_POST['approval_status'] ?? 'pending');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    if (empty($title)) {
        $msg = 'Product title is required.';
        $msgType = 'error';
    } else {
        $stmt = $pdo->prepare("UPDATE products SET title = ?, brand = ?, description = ?, category = ?, price_min = ?, price_max = ?, condition_tag = ?, quantity = ?, status = ?, approval_status = ?, is_featured = ? WHERE id = ?");
        $stmt->execute([$title, $brand, $description, $category, $price_min, $price_max, $condition_tag, $quantity, $status, $approval_status, $is_featured, $productId]);
        $msg = 'Product updated successfully.';

        try {
            $pdo->prepare("INSERT INTO admin_activity_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)")
                ->execute([$_SESSION['user_id'], "Product Edit", "Updated product #$productId: $title", $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch(Exception $e) {}
    }
    }
}

$stmt = $pdo->prepare("SELECT p.*, u.full_name as seller_name, u.email as seller_email FROM products p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: admin_listings.php");
    exit;
}

$images = json_decode($product['image_paths'] ?? '[]', true);
if (!is_array($images)) $images = [];

$categories = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root { --primary: #6B21A8; --bg: #f8f9fa; --sidebar-bg: #1a1a1a; --text-light: #a1a1aa; }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; display:flex; color: #333; }
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; padding: 0.5rem 0; color: white; z-index: 100; }
        .main-content { margin-left: 260px; padding: 2rem 2.5rem; width: calc(100% - 260px); min-height: 100vh; }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }

        .breadcrumb { display: flex; align-items: center; gap: 8px; margin-bottom: 1.5rem; font-size: 0.85rem; color: #666; }
        .breadcrumb a { color: var(--primary); text-decoration: none; font-weight: 500; }
        .breadcrumb a:hover { text-decoration: underline; }

        .msg { padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 8px; }
        .msg-success { background: #f0fdf4; color: #22c55e; }
        .msg-error { background: #fef2f2; color: #ef4444; }

        .edit-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }

        .card { background: white; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #f0f0f0; padding: 1.5rem; margin-bottom: 1.5rem; }
        .card h3 { margin: 0 0 1.25rem; font-size: 1rem; color: #1a1a1a; font-weight: 700; display: flex; align-items: center; gap: 8px; }

        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-size: 0.8rem; font-weight: 600; color: #555; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 0.7rem 1rem; border: 1px solid #e5e5e5; border-radius: 10px;
            font-size: 0.9rem; font-family: 'Inter', sans-serif; transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(107,33,168,0.08);
        }
        .form-group textarea { min-height: 140px; resize: vertical; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        .btn { padding: 0.7rem 1.25rem; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #581c87; }
        .btn-outline { background: white; color: #666; border: 1px solid #e5e5e5; }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }

        .image-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 0.75rem; }
        .image-item { position: relative; border-radius: 10px; overflow: hidden; aspect-ratio: 1; background: #f5f5f5; }
        .image-item img { width: 100%; height: 100%; object-fit: cover; cursor: pointer; transition: transform 0.2s; }
        .image-item img:hover { transform: scale(1.05); }

        .seller-info { display: flex; align-items: center; gap: 12px; padding: 1rem; background: #fafafa; border-radius: 10px; }
        .seller-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #6B21A8, #9333EA); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.1rem; flex-shrink: 0; }
        .seller-details { flex: 1; }
        .seller-details .name { font-weight: 600; color: #1a1a1a; }
        .seller-details .email { font-size: 0.8rem; color: #999; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 600; text-transform: uppercase; }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-approved { background: #dcfce7; color: #22c55e; }
        .badge-rejected { background: #fee2e2; color: #ef4444; }
        .badge-available { background: #dbeafe; color: #2563eb; }
        .badge-sold { background: #000; color: #fff; }

        .meta-row { display: flex; justify-content: space-between; padding: 0.6rem 0; border-bottom: 1px solid #f5f5f5; font-size: 0.85rem; }
        .meta-row:last-child { border-bottom: none; }
        .meta-label { color: #999; }
        .meta-value { font-weight: 600; color: #333; }

        .toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #e5e5e5; border-radius: 24px; transition: 0.3s; }
        .toggle-slider::before { content: ''; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s; }
        .toggle-switch input:checked + .toggle-slider { background: var(--primary); }
        .toggle-switch input:checked + .toggle-slider::before { transform: translateX(20px); }

        .lightbox { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 9999; align-items: center; justify-content: center; }
        .lightbox.active { display: flex; }
        .lightbox img { max-width: 90%; max-height: 90%; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
        .lightbox-close { position: absolute; top: 20px; right: 30px; color: white; font-size: 2rem; cursor: pointer; background: none; border: none; }

        .actions-bar { display: flex; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid #f0f0f0; margin-top: 0.5rem; }

        @media (max-width: 1024px) {
            .edit-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="breadcrumb">
            <a href="admin_dashboard.php">Dashboard</a>
            <ion-icon name="chevron-forward-outline"></ion-icon>
            <a href="admin_listings.php">Listings</a>
            <ion-icon name="chevron-forward-outline"></ion-icon>
            <span>Edit Product #<?php echo $productId; ?></span>
        </div>

        <div class="header">
            <div>
                <h1>Edit Product</h1>
                <p style="color:#666; margin-top:0.5rem;"><?php echo htmlspecialchars($product['title']); ?></p>
            </div>
            <div style="display:flex;gap:0.75rem;">
                <a href="product_details.php?id=<?php echo $productId; ?>" target="_blank" class="btn btn-outline">
                    <ion-icon name="eye-outline"></ion-icon> View Live
                </a>
                <a href="admin_listings.php" class="btn btn-outline">
                    <ion-icon name="arrow-back-outline"></ion-icon> Back
                </a>
            </div>
        </div>

        <?php if($msg): ?>
            <div class="msg msg-<?php echo $msgType; ?>">
                <ion-icon name="<?php echo $msgType === 'success' ? 'checkmark-circle-outline' : 'alert-circle-outline'; ?>"></ion-icon>
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="save" value="1">
            <div class="edit-grid">
                <div>
                    <div class="card">
                        <h3><ion-icon name="create-outline"></ion-icon> Basic Information</h3>
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($product['title']); ?>" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Brand</label>
                                <input type="text" name="brand" value="<?php echo htmlspecialchars($product['brand'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <input type="text" name="category" value="<?php echo htmlspecialchars($product['category'] ?? ''); ?>" list="category-list">
                                <datalist id="category-list">
                                    <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="card">
                        <h3><ion-icon name="cash-outline"></ion-icon> Pricing & Stock</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Price Min (₹)</label>
                                <input type="number" step="0.01" name="price_min" value="<?php echo $product['price_min']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Price Max (₹)</label>
                                <input type="number" step="0.01" name="price_max" value="<?php echo $product['price_max']; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Condition</label>
                                <select name="condition_tag">
                                    <?php
                                    $conditions = ['Brand New', 'Lightly Used', 'Regularly Used'];
                                    foreach($conditions as $c):
                                    ?>
                                    <option value="<?php echo $c; ?>" <?php echo ($product['condition_tag'] ?? '') === $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Quantity</label>
                                <input type="number" name="quantity" value="<?php echo intval($product['quantity'] ?? 1); ?>" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h3><ion-icon name="images-outline"></ion-icon> Product Images</h3>
                        <?php if(!empty($images)): ?>
                        <div class="image-grid">
                            <?php foreach($images as $i => $img): ?>
                            <div class="image-item">
                                <img src="<?php echo htmlspecialchars($img); ?>" alt="Product image <?php echo $i+1; ?>" onclick="openLightbox('<?php echo htmlspecialchars($img); ?>')">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p style="color:#999; text-align:center; padding:2rem 0;">No images uploaded for this product.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <div class="card">
                        <h3><ion-icon name="toggle-outline"></ion-icon> Status & Visibility</h3>
                        <div class="form-group">
                            <label>Product Status</label>
                            <select name="status">
                                <option value="available" <?php echo $product['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="sold" <?php echo $product['status'] === 'sold' ? 'selected' : ''; ?>>Sold</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Approval Status</label>
                            <select name="approval_status">
                                <option value="pending" <?php echo ($product['approval_status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo ($product['approval_status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo ($product['approval_status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; justify-content:space-between;">
                            <label style="margin:0;">Featured</label>
                            <label class="toggle-switch">
                                <input type="checkbox" name="is_featured" <?php echo ($product['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="card">
                        <h3><ion-icon name="person-outline"></ion-icon> Seller</h3>
                        <div class="seller-info">
                            <div class="seller-avatar"><?php echo strtoupper(substr($product['seller_name'], 0, 1)); ?></div>
                            <div class="seller-details">
                                <div class="name"><?php echo htmlspecialchars($product['seller_name']); ?></div>
                                <div class="email"><?php echo htmlspecialchars($product['seller_email']); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <h3><ion-icon name="information-circle-outline"></ion-icon> Meta</h3>
                        <div class="meta-row">
                            <span class="meta-label">Product ID</span>
                            <span class="meta-value">#<?php echo $product['id']; ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Created</span>
                            <span class="meta-value"><?php echo date('M j, Y g:i A', strtotime($product['created_at'])); ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Views</span>
                            <span class="meta-value"><?php echo number_format($product['views'] ?? 0); ?></span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Location</span>
                            <span class="meta-value"><?php echo htmlspecialchars($product['location'] ?? 'N/A'); ?></span>
                        </div>
                        <?php if(!empty($product['boosted_until']) && strtotime($product['boosted_until']) > time()): ?>
                        <div class="meta-row">
                            <span class="meta-label">Boosted Until</span>
                            <span class="meta-value" style="color:var(--primary);"><?php echo date('M j, Y', strtotime($product['boosted_until'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="actions-bar">
                        <button type="submit" class="btn btn-primary" style="flex:1;">
                            <ion-icon name="save-outline"></ion-icon> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </main>

    <div class="lightbox" id="lightbox" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
        <img id="lightbox-img" src="" alt="Product image">
    </div>

    <script>
        function openLightbox(src) {
            document.getElementById('lightbox-img').src = src;
            document.getElementById('lightbox').classList.add('active');
        }
        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('active');
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeLightbox();
        });
    </script>
</body>
</html>
