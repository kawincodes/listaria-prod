<?php
require 'includes/db.php';
require_once __DIR__ . '/includes/session.php';
$activePage = 'blogs';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$message = '';
$messageType = 'success';

function validateCsrf() {
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

if (isset($_POST['delete_id'])) {
    if (!validateCsrf()) { $message = "Invalid security token."; $messageType = 'error'; }
    else {
        $stmt = $pdo->prepare("SELECT image_path FROM blogs WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $blog = $stmt->fetch();
        if ($blog && $blog['image_path'] && strpos($blog['image_path'], 'uploads/') === 0 && file_exists($blog['image_path'])) {
            unlink($blog['image_path']);
        }
        $stmt = $pdo->prepare("DELETE FROM blogs WHERE id = ?");
        if ($stmt->execute([$_POST['delete_id']])) {
            $message = "Blog deleted successfully.";
        } else {
            $message = "Error deleting blog.";
            $messageType = 'error';
        }
    }
}

if (isset($_POST['update_blog'])) {
    if (!validateCsrf()) { $message = "Invalid security token."; $messageType = 'error'; }
    else {
        $id = $_POST['blog_id'];
        $title = trim($_POST['title']);
        $category = trim($_POST['category']);
        $content = trim($_POST['content']);
        $newImagePath = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $new_name = uniqid() . '.' . $ext;
                $upload_dir = 'uploads/blogs/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_name)) {
                    $newImagePath = $upload_dir . $new_name;
                } else {
                    $message = "Failed to upload image. Text fields were not updated.";
                    $messageType = 'error';
                }
            } else {
                $message = "Invalid image format. Allowed: jpg, jpeg, png, webp.";
                $messageType = 'error';
            }
        }

        if ($messageType !== 'error') {
            if ($newImagePath) {
                $oldStmt = $pdo->prepare("SELECT image_path FROM blogs WHERE id = ?");
                $oldStmt->execute([$id]);
                $oldBlog = $oldStmt->fetch();
                $stmt = $pdo->prepare("UPDATE blogs SET title=?, category=?, content=?, image_path=? WHERE id=?");
                $ok = $stmt->execute([$title, $category, $content, $newImagePath, $id]);
                if ($ok && $oldBlog && $oldBlog['image_path'] && strpos($oldBlog['image_path'], 'uploads/') === 0 && file_exists($oldBlog['image_path'])) {
                    unlink($oldBlog['image_path']);
                }
            } else {
                $stmt = $pdo->prepare("UPDATE blogs SET title=?, category=?, content=? WHERE id=?");
                $ok = $stmt->execute([$title, $category, $content, $id]);
            }
            if ($ok) {
                $message = "Blog updated successfully.";
            } else {
                $message = "Error updating blog.";
                $messageType = 'error';
            }
        }
    }
}

if (isset($_POST['add_blog'])) {
    if (!validateCsrf()) { $message = "Invalid security token."; $messageType = 'error'; }
    else {
        $title = trim($_POST['title']);
        $category = trim($_POST['category']);
        $content = trim($_POST['content']);
        $image_path = 'https://via.placeholder.com/600x400';

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $new_name = uniqid() . '.' . $ext;
                $upload_dir = 'uploads/blogs/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_name)) {
                    $image_path = $upload_dir . $new_name;
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO blogs (title, category, content, image_path) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$title, $category, $content, $image_path])) {
            $message = "Blog published successfully.";
        } else {
            $message = "Error adding blog.";
            $messageType = 'error';
        }
    }
}

$blogs = $pdo->query("SELECT * FROM blogs ORDER BY created_at DESC")->fetchAll();
$totalBlogs = count($blogs);
$categories = [];
foreach ($blogs as $b) {
    $cat = $b['category'] ?? 'Uncategorized';
    $categories[$cat] = ($categories[$cat] ?? 0) + 1;
}
$totalCategories = count($categories);

$editBlog = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editBlog = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Blogs - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root { 
            --primary: #6B21A8; 
            --primary-dark: #581c87;
            --primary-light: #a855f7;
            --primary-bg: #faf5ff;
            --success: #22c55e;
            --danger: #ef4444;
            --bg: #f8f9fa; 
            --sidebar-bg: #1a1a1a;
            --text-light: #a1a1aa;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; display:flex; color: #333; }
        
        .sidebar { 
            width: 260px; 
            background: var(--sidebar-bg); 
            height: 100vh; 
            position: fixed; 
            padding: 0.5rem 0; 
            color: white;
            z-index: 100;
        }
        .brand { font-size: 1.2rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem; text-decoration: none; }

        .main-content { 
            margin-left: 260px; 
            padding: 2rem 2.5rem; 
            width: calc(100% - 260px); 
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .page-header h1 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 800;
            color: #1a1a1a;
        }
        .page-header-sub {
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 2px;
        }
        .header-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .btn-ghost { background: white; color: #333; border: 1px solid #e5e5e5; }
        .btn-ghost:hover { border-color: var(--primary); color: var(--primary); }
        .btn-danger { background: #fef2f2; color: var(--danger); }
        .btn-danger:hover { background: #fee2e2; }
        .btn-sm { padding: 0.45rem 0.8rem; font-size: 0.78rem; border-radius: 8px; }

        .toast {
            padding: 0.85rem 1.2rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideDown 0.3s ease;
        }
        .toast-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .toast-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        @keyframes slideDown { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 1.2rem 1.5rem;
            border: 1px solid #f0f0f0;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
        }
        .stat-card:nth-child(2)::before { background: linear-gradient(90deg, #1a1a1a, #525252); }
        .stat-card:nth-child(3)::before { background: linear-gradient(90deg, var(--success), #4ade80); }
        .stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            margin-bottom: 0.75rem;
        }
        .stat-icon.purple { background: var(--primary-bg); color: var(--primary); }
        .stat-icon.dark { background: #f5f5f5; color: #333; }
        .stat-icon.green { background: #f0fdf4; color: var(--success); }
        .stat-label { font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        .stat-value { font-size: 1.6rem; font-weight: 800; color: #1e293b; margin-top: 2px; }

        .content-grid {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 1.5rem;
            align-items: start;
        }

        .card {
            background: white;
            border-radius: 16px;
            border: 1px solid #f0f0f0;
            overflow: hidden;
        }
        .card-header {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-header-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-header-title ion-icon { color: var(--primary); font-size: 1.1rem; }
        .card-body { padding: 1.5rem; }

        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: #374151;
            font-size: 0.82rem;
        }
        .form-control {
            width: 100%;
            padding: 0.65rem 0.9rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.88rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            background: #fafafa;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(107,33,168,0.08);
        }
        textarea.form-control { resize: vertical; min-height: 120px; }
        .file-input-wrap {
            position: relative;
            border: 2px dashed #e5e7eb;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #fafafa;
        }
        .file-input-wrap:hover { border-color: var(--primary-light); background: var(--primary-bg); }
        .file-input-wrap input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }
        .file-input-wrap ion-icon { font-size: 1.5rem; color: #94a3b8; display: block; margin: 0 auto 0.4rem; }
        .file-input-wrap span { font-size: 0.8rem; color: #94a3b8; }

        .search-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f5f5f5;
            border-radius: 10px;
            padding: 0.5rem 1rem;
            border: 1px solid transparent;
            transition: all 0.2s;
        }
        .search-bar:focus-within { background: white; border-color: #e5e5e5; }
        .search-bar ion-icon { color: #94a3b8; font-size: 1.1rem; flex-shrink: 0; }
        .search-bar input {
            border: none;
            background: none;
            outline: none;
            font-size: 0.85rem;
            font-family: 'Inter', sans-serif;
            width: 100%;
            color: #333;
        }

        .blog-list { max-height: 600px; overflow-y: auto; }
        .blog-list::-webkit-scrollbar { width: 4px; }
        .blog-list::-webkit-scrollbar-thumb { background: #e5e5e5; border-radius: 4px; }

        .blog-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f5f5f5;
            transition: background 0.15s;
        }
        .blog-row:last-child { border-bottom: none; }
        .blog-row:hover { background: #fafafa; }

        .blog-thumb {
            width: 56px;
            height: 56px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            background: #f0f0f0;
        }
        .blog-info { flex: 1; min-width: 0; }
        .blog-title {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 0.88rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .blog-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 4px;
            font-size: 0.75rem;
            color: #94a3b8;
        }
        .blog-meta-item {
            display: flex;
            align-items: center;
            gap: 3px;
        }
        .blog-category-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            background: var(--primary-bg);
            color: var(--primary);
        }
        .blog-actions {
            display: flex;
            gap: 0.4rem;
            flex-shrink: 0;
        }
        .icon-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: all 0.2s;
            text-decoration: none;
        }
        .icon-btn-edit { background: #f0fdf4; color: #22c55e; }
        .icon-btn-edit:hover { background: #dcfce7; }
        .icon-btn-delete { background: #fef2f2; color: #ef4444; }
        .icon-btn-delete:hover { background: #fee2e2; }
        .icon-btn-view { background: #f0f4ff; color: #3b82f6; }
        .icon-btn-view:hover { background: #dbeafe; }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #94a3b8;
        }
        .empty-state ion-icon { font-size: 2.5rem; color: #e5e5e5; margin-bottom: 0.75rem; }
        .empty-state p { margin: 0; font-size: 0.88rem; }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 520px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalIn 0.2s ease;
        }
        @keyframes modalIn { from { opacity:0; transform:scale(0.95); } to { opacity:1; transform:scale(1); } }
        .modal-header {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-header h3 { margin: 0; font-size: 1rem; font-weight: 700; }
        .modal-close {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.3rem;
            color: #94a3b8;
            padding: 0;
        }
        .modal-body { padding: 1.5rem; }
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #f0f0f0;
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .editor-wrap { border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; background: #fafafa; }
        .editor-wrap:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(107,33,168,0.08); }
        .editor-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 2px;
            padding: 6px 8px;
            background: #f5f5f5;
            border-bottom: 1px solid #e5e7eb;
        }
        .editor-toolbar button {
            width: 30px;
            height: 28px;
            border: none;
            background: none;
            cursor: pointer;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            color: #555;
            transition: all 0.15s;
            font-weight: 600;
            font-family: 'Inter', serif;
        }
        .editor-toolbar button:hover { background: #e5e5e5; color: #1a1a1a; }
        .editor-toolbar button.active { background: var(--primary-bg); color: var(--primary); }
        .toolbar-sep { width: 1px; background: #ddd; margin: 2px 4px; }
        .editor-area {
            min-height: 200px;
            max-height: 400px;
            overflow-y: auto;
            padding: 12px;
            outline: none;
            font-size: 0.88rem;
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #333;
            background: white;
        }
        .editor-area:empty::before {
            content: attr(data-placeholder);
            color: #aaa;
        }
        .editor-area h1, .editor-area h2, .editor-area h3 { margin: 0.5em 0 0.3em; }
        .editor-area blockquote {
            border-left: 3px solid var(--primary-light);
            margin: 0.5em 0;
            padding: 0.5em 1em;
            background: var(--primary-bg);
            border-radius: 0 6px 6px 0;
        }
        .editor-area a { color: var(--primary); }
        .editor-area ul, .editor-area ol { padding-left: 1.5em; }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1>Blogs</h1>
                <div class="page-header-sub">Create and manage your blog posts</div>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('active')">
                    <ion-icon name="add-outline"></ion-icon> New Blog
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="toast toast-<?php echo $messageType; ?>">
                <ion-icon name="<?php echo $messageType === 'success' ? 'checkmark-circle' : 'alert-circle'; ?>"></ion-icon>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon purple"><ion-icon name="newspaper-outline"></ion-icon></div>
                <div class="stat-label">Total Blogs</div>
                <div class="stat-value"><?php echo $totalBlogs; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon dark"><ion-icon name="folder-outline"></ion-icon></div>
                <div class="stat-label">Categories</div>
                <div class="stat-value"><?php echo $totalCategories; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><ion-icon name="trending-up-outline"></ion-icon></div>
                <div class="stat-label">Latest Post</div>
                <div class="stat-value" style="font-size:0.95rem; font-weight:600;">
                    <?php echo $totalBlogs > 0 ? htmlspecialchars(mb_strimwidth($blogs[0]['title'], 0, 28, '...')) : 'None'; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-header-title">
                    <ion-icon name="document-text-outline"></ion-icon>
                    All Blog Posts
                </div>
                <div class="search-bar" style="width:240px;">
                    <ion-icon name="search-outline"></ion-icon>
                    <input type="text" id="blogSearch" placeholder="Search blogs..." oninput="filterBlogs()">
                </div>
            </div>
            <div class="blog-list" id="blogList">
                <?php if ($totalBlogs > 0): ?>
                    <?php foreach ($blogs as $blog): ?>
                        <div class="blog-row" data-title="<?php echo strtolower(htmlspecialchars($blog['title'])); ?>" data-category="<?php echo strtolower(htmlspecialchars($blog['category'] ?? '')); ?>">
                            <img class="blog-thumb" src="<?php echo htmlspecialchars($blog['image_path']); ?>" alt="" onerror="this.src='https://via.placeholder.com/56x56?text=Blog'">
                            <div class="blog-info">
                                <div class="blog-title"><?php echo htmlspecialchars($blog['title']); ?></div>
                                <div class="blog-meta">
                                    <span class="blog-category-badge"><?php echo htmlspecialchars($blog['category'] ?? 'General'); ?></span>
                                    <span class="blog-meta-item">
                                        <ion-icon name="calendar-outline"></ion-icon>
                                        <?php echo date('M d, Y', strtotime($blog['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="blog-actions">
                                <a href="blog_details.php?id=<?php echo $blog['id']; ?>" class="icon-btn icon-btn-view" title="View" target="_blank">
                                    <ion-icon name="eye-outline"></ion-icon>
                                </a>
                                <button class="icon-btn icon-btn-edit" title="Edit" onclick="openEdit(<?php echo htmlspecialchars(json_encode($blog)); ?>)">
                                    <ion-icon name="create-outline"></ion-icon>
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this blog post?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                    <input type="hidden" name="delete_id" value="<?php echo $blog['id']; ?>">
                                    <button type="submit" class="icon-btn icon-btn-delete" title="Delete">
                                        <ion-icon name="trash-outline"></ion-icon>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <ion-icon name="newspaper-outline"></ion-icon>
                        <p>No blog posts yet. Click "New Blog" to create your first post.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div class="modal-overlay" id="addModal">
        <div class="modal">
            <div class="modal-header">
                <h3><ion-icon name="add-circle-outline" style="vertical-align:middle;margin-right:6px;color:var(--primary);"></ion-icon> New Blog Post</h3>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="Enter blog title">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category" class="form-control" required placeholder="e.g. Sustainability, Tech, Lifestyle">
                    </div>
                    <div class="form-group">
                        <label>Cover Image</label>
                        <div class="file-input-wrap">
                            <ion-icon name="cloud-upload-outline"></ion-icon>
                            <span>Click to upload image</span>
                            <input type="file" name="image" accept="image/*">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <div class="editor-wrap">
                            <div class="editor-toolbar" id="addToolbar">
                                <button type="button" onclick="execCmd('bold')" title="Bold"><b>B</b></button>
                                <button type="button" onclick="execCmd('italic')" title="Italic"><i>I</i></button>
                                <button type="button" onclick="execCmd('underline')" title="Underline"><u>U</u></button>
                                <button type="button" onclick="execCmd('strikeThrough')" title="Strikethrough"><s>S</s></button>
                                <div class="toolbar-sep"></div>
                                <button type="button" onclick="execCmd('formatBlock','<h2>')" title="Heading 2" style="font-size:0.9rem;">H2</button>
                                <button type="button" onclick="execCmd('formatBlock','<h3>')" title="Heading 3" style="font-size:0.8rem;">H3</button>
                                <button type="button" onclick="execCmd('formatBlock','<p>')" title="Paragraph" style="font-size:0.78rem;">P</button>
                                <div class="toolbar-sep"></div>
                                <button type="button" onclick="execCmd('insertUnorderedList')" title="Bullet List"><ion-icon name="list-outline"></ion-icon></button>
                                <button type="button" onclick="execCmd('insertOrderedList')" title="Numbered List"><ion-icon name="reorder-four-outline"></ion-icon></button>
                                <button type="button" onclick="execCmd('formatBlock','<blockquote>')" title="Quote"><ion-icon name="chatbox-outline"></ion-icon></button>
                                <div class="toolbar-sep"></div>
                                <button type="button" onclick="insertLink()" title="Insert Link"><ion-icon name="link-outline"></ion-icon></button>
                                <button type="button" onclick="execCmd('removeFormat')" title="Clear Formatting"><ion-icon name="close-circle-outline"></ion-icon></button>
                                <div class="toolbar-sep"></div>
                                <button type="button" onclick="execCmd('justifyLeft')" title="Align Left"><ion-icon name="reorder-two-outline" style="transform:scaleX(-1)"></ion-icon></button>
                                <button type="button" onclick="execCmd('justifyCenter')" title="Align Center"><ion-icon name="reorder-three-outline"></ion-icon></button>
                            </div>
                            <div class="editor-area" id="addEditor" contenteditable="true" data-placeholder="Write your blog content here..."></div>
                        </div>
                        <textarea name="content" id="addContentHidden" style="display:none;" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" name="add_blog" class="btn btn-primary" onclick="syncEditor('addEditor','addContentHidden')">
                        <ion-icon name="checkmark-outline"></ion-icon> Publish
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-header">
                <h3><ion-icon name="create-outline" style="vertical-align:middle;margin-right:6px;color:var(--primary);"></ion-icon> Edit Blog Post</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="blog_id" id="editBlogId">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" id="editTitle" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category" id="editCategory" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Cover Image (leave empty to keep current)</label>
                        <div class="file-input-wrap">
                            <ion-icon name="cloud-upload-outline"></ion-icon>
                            <span>Click to upload new image</span>
                            <input type="file" name="image" accept="image/*">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <div class="editor-wrap">
                            <div class="editor-toolbar" id="editToolbar">
                                <button type="button" onclick="execCmd('bold')" title="Bold"><b>B</b></button>
                                <button type="button" onclick="execCmd('italic')" title="Italic"><i>I</i></button>
                                <button type="button" onclick="execCmd('underline')" title="Underline"><u>U</u></button>
                                <button type="button" onclick="execCmd('strikeThrough')" title="Strikethrough"><s>S</s></button>
                                <div class="toolbar-sep"></div>
                                <button type="button" onclick="execCmd('formatBlock','<h2>')" title="Heading 2" style="font-size:0.9rem;">H2</button>
                                <button type="button" onclick="execCmd('formatBlock','<h3>')" title="Heading 3" style="font-size:0.8rem;">H3</button>
                                <button type="button" onclick="execCmd('formatBlock','<p>')" title="Paragraph" style="font-size:0.78rem;">P</button>
                                <div class="toolbar-sep"></div>
                                <button type="button" onclick="execCmd('insertUnorderedList')" title="Bullet List"><ion-icon name="list-outline"></ion-icon></button>
                                <button type="button" onclick="execCmd('insertOrderedList')" title="Numbered List"><ion-icon name="reorder-four-outline"></ion-icon></button>
                                <button type="button" onclick="execCmd('formatBlock','<blockquote>')" title="Quote"><ion-icon name="chatbox-outline"></ion-icon></button>
                                <div class="toolbar-sep"></div>
                                <button type="button" onclick="insertLink()" title="Insert Link"><ion-icon name="link-outline"></ion-icon></button>
                                <button type="button" onclick="execCmd('removeFormat')" title="Clear Formatting"><ion-icon name="close-circle-outline"></ion-icon></button>
                                <div class="toolbar-sep"></div>
                                <button type="button" onclick="execCmd('justifyLeft')" title="Align Left"><ion-icon name="reorder-two-outline" style="transform:scaleX(-1)"></ion-icon></button>
                                <button type="button" onclick="execCmd('justifyCenter')" title="Align Center"><ion-icon name="reorder-three-outline"></ion-icon></button>
                            </div>
                            <div class="editor-area" id="editEditor" contenteditable="true" data-placeholder="Edit your blog content..."></div>
                        </div>
                        <textarea name="content" id="editContentHidden" style="display:none;" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" name="update_blog" class="btn btn-primary" onclick="syncEditor('editEditor','editContentHidden')">
                        <ion-icon name="checkmark-outline"></ion-icon> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    function openEdit(blog) {
        document.getElementById('editBlogId').value = blog.id;
        document.getElementById('editTitle').value = blog.title;
        document.getElementById('editCategory').value = blog.category || '';
        document.getElementById('editEditor').innerHTML = blog.content || '';
        document.getElementById('editContentHidden').value = blog.content || '';
        document.getElementById('editModal').classList.add('active');
    }

    function filterBlogs() {
        var q = document.getElementById('blogSearch').value.toLowerCase();
        var rows = document.querySelectorAll('.blog-row');
        rows.forEach(function(row) {
            var title = row.getAttribute('data-title') || '';
            var cat = row.getAttribute('data-category') || '';
            row.style.display = (title.includes(q) || cat.includes(q)) ? 'flex' : 'none';
        });
    }

    function execCmd(cmd, val) {
        document.execCommand(cmd, false, val || null);
    }

    function insertLink() {
        var url = prompt('Enter URL:', 'https://');
        if (url) document.execCommand('createLink', false, url);
    }

    function syncEditor(editorId, hiddenId) {
        var editor = document.getElementById(editorId);
        var hidden = document.getElementById(hiddenId);
        hidden.value = editor.innerHTML;
    }

    document.getElementById('addEditor').addEventListener('input', function() {
        document.getElementById('addContentHidden').value = this.innerHTML;
    });
    document.getElementById('editEditor').addEventListener('input', function() {
        document.getElementById('editContentHidden').value = this.innerHTML;
    });

    document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) overlay.classList.remove('active');
        });
    });
    </script>

</body>
</html>
