<?php
session_start();
require 'includes/db.php';

$activePage = 'banners';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$msg = '';
$error = '';

// Handle Main Banner Upload/Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_banner'])) {
    $target_dir = "uploads/banners/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file = $_FILES['banner_image'];
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
    
    if (!in_array($fileType, $allowedTypes)) {
        $error = "Only JPG, JPEG, PNG, and WEBP files are allowed.";
    } else {
        $filename = "banner_" . time() . "." . $fileType;
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $stmt = $pdo->prepare("INSERT INTO banners (image_path, title, link_url, start_time, end_time, display_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $target_file,
                $_POST['title'] ?? '',
                $_POST['link_url'] ?? '',
                !empty($_POST['start_time']) ? $_POST['start_time'] : null,
                !empty($_POST['end_time']) ? $_POST['end_time'] : null,
                (int)($_POST['display_order'] ?? 0)
            ]);
            $msg = "New banner added successfully!";
        } else {
            $error = "Failed to upload image.";
        }
    }
}

// Handle Delete Banner
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT image_path FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $banner = $stmt->fetch();
    if ($banner) {
        if (file_exists($banner['image_path'])) {
            unlink($banner['image_path']);
        }
        $pdo->prepare("DELETE FROM banners WHERE id = ?")->execute([$id]);
        $msg = "Banner deleted successfully!";
    }
}

// Handle Thrift Banner Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['thrift_banner_image'])) {
    $target_dir = "assets/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $file = $_FILES['thrift_banner_image'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $target_file = $target_dir . "thrift_banner.png";
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $msg = "Thrift Banner updated successfully!";
        }
    }
}

// Fetch Banners
$banners = $pdo->query("SELECT * FROM banners ORDER BY display_order ASC, created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Banners - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root { --primary: #6B21A8; --primary-hover: #581c87; --bg: #f8f9fa; --sidebar-bg: #1a1a1a; --text-light: #a1a1aa; }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; display:flex; color: #1f2937; }
        /* Sidebar Styles */
        .sidebar { 
            width: 260px; 
            background: var(--sidebar-bg); 
            height: 100vh; 
            position: fixed; 
            padding: 2rem 1.5rem; 
            color: white;
            display: flex;
            flex-direction: column;
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
            background: #6B21A8; 
            color: white; 
        }

        .main-content { margin-left: 260px; padding: 2rem 3rem; width: calc(100% - 260px); min-height: 100vh; }
        
        .header { margin-bottom: 2rem; }
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1e293b; }
        .header p { color: #64748b; margin-top: 0.5rem; }
        
        .card { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); margin-bottom: 2rem; }
        .section-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem; color: #334155; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group label { font-size: 0.85rem; font-weight: 600; color: #4b5563; }
        .form-control { padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem; }

        .banner-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .banner-item { border: 1px solid #f1f5f9; border-radius: 12px; overflow: hidden; position: relative; }
        .banner-preview { width: 100%; aspect-ratio: 16/7; object-fit: cover; }
        .banner-info { padding: 15px; background: #fff; }
        .banner-actions { display: flex; gap: 10px; margin-top: 10px; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-scheduled { background: #fef9c3; color: #854d0e; }

        .btn { padding: 10px 15px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; transition: 0.2s; font-size: 0.9rem; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-danger { background: #fee2e2; color: #b91c1c; }
        
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-success { background: #ecfdf5; color: #047857; }
        .alert-error { background: #fef2f2; color: #b91c1c; }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <h1>Manage Home Banners</h1>
            <p>Add and schedule banners for your carousel.</p>
        </div>

        <?php if($msg): ?>
            <div class="alert alert-success"><?php echo $msg; ?></div>
        <?php endif; ?>

        <!-- Add Banner Form -->
        <div class="card">
            <h2 class="section-title">Add New Banner</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_banner" value="1">
                <div class="form-grid">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Banner Image (Recommended 1920x600)</label>
                        <input type="file" name="banner_image" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Title (Internal reference)</label>
                        <input type="text" name="title" class="form-control" placeholder="Summer Sale">
                    </div>
                    <div class="form-group">
                        <label>Link URL (Action when clicked)</label>
                        <input type="text" name="link_url" class="form-control" placeholder="index.php?category=Phones">
                    </div>
                    <div class="form-group">
                        <label>Start Showing (Optional)</label>
                        <input type="datetime-local" name="start_time" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>End Showing (Optional)</label>
                        <input type="datetime-local" name="end_time" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" name="display_order" class="form-control" value="0">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:20px;">Add Banner</button>
            </form>
        </div>

        <!-- Banners List -->
        <div class="header">
            <h2>Current Banners</h2>
        </div>
        <div class="banner-list">
            <?php foreach ($banners as $b): ?>
                <div class="banner-item">
                    <img src="<?php echo htmlspecialchars($b['image_path']); ?>" class="banner-preview">
                    <div class="banner-info">
                        <strong><?php echo htmlspecialchars($b['title'] ?: 'Untitled Banner'); ?></strong>
                        <div style="font-size:0.8rem; color:#6b7280; margin-top:5px;">
                            <?php if($b['start_time']): ?>From: <?php echo $b['start_time']; ?><br><?php endif; ?>
                            <?php if($b['end_time']): ?>Until: <?php echo $b['end_time']; ?><?php endif; ?>
                        </div>
                        <div class="banner-actions">
                            <a href="?delete=<?php echo $b['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this banner?')">Delete</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Thrift Banner -->
        <div class="card" style="margin-top:40px;">
            <h2 class="section-title">Static Thrift Banner</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <input type="file" name="thrift_banner_image" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:10px;">Update Thrift Banner</button>
            </form>
        </div>
    </main>
</body>
</html>
