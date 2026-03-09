<?php
require 'includes/db.php';
session_start();
$activePage = 'blogs';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$message = '';

if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM blogs WHERE id = ?");
    if ($stmt->execute([$_POST['delete_id']])) {
        $message = "Blog deleted successfully.";
    } else {
        $message = "Error deleting blog.";
    }
}

if (isset($_POST['add_blog'])) {
    $title = $_POST['title'];
    $category = $_POST['category'];
    $content = $_POST['content'];
    $image_path = 'https://via.placeholder.com/600x400';

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
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
        $message = "Blog added successfully.";
    } else {
        $message = "Error adding blog.";
    }
}

$blogs = $pdo->query("SELECT * FROM blogs ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Blogs - Listaria Admin</title>
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

        .msg-success {
            background: #f0fdf4;
            color: #22c55e;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .blogs-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title ion-icon { color: #6B21A8; }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
            font-size: 0.9rem;
        }

        .form-group input[type="text"],
        .form-group input[type="file"],
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #6B21A8;
        }

        .btn-primary {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            background: #6B21A8;
            color: white;
            width: 100%;
            transition: background 0.2s;
        }
        .btn-primary:hover { background: #581c87; }

        .blog-item {
            display: flex;
            gap: 1rem;
            border: 1px solid #f0f0f0;
            padding: 1rem;
            border-radius: 12px;
            align-items: center;
            margin-bottom: 0.75rem;
            transition: box-shadow 0.2s;
        }
        .blog-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .blog-item:last-child { margin-bottom: 0; }

        .blog-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .blog-item-info { flex: 1; }
        .blog-item-title { font-weight: 700; color: #1a1a1a; font-size: 0.9rem; }
        .blog-item-category { font-size: 0.8rem; color: #999; margin-top: 0.25rem; }

        .btn-delete {
            background: #fef2f2;
            color: #ef4444;
            border: none;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.8rem;
            transition: background 0.2s;
        }
        .btn-delete:hover { background: #fee2e2; }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #999;
        }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <h1>Manage Blogs</h1>
            <div>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
        </div>

        <?php if ($message): ?>
            <div class="msg-success">
                <ion-icon name="checkmark-circle"></ion-icon>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="blogs-grid">
            <div class="card">
                <div class="card-title">
                    <ion-icon name="add-circle"></ion-icon>
                    Add New Blog
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" required placeholder="Blog title">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category" required placeholder="e.g. Sustainability">
                    </div>
                    <div class="form-group">
                        <label>Image</label>
                        <input type="file" name="image">
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <textarea name="content" rows="6" required placeholder="Write your blog content..."></textarea>
                    </div>
                    <button type="submit" name="add_blog" class="btn-primary">Publish Blog</button>
                </form>
            </div>

            <div class="card">
                <div class="card-title">
                    <ion-icon name="document-text"></ion-icon>
                    Existing Blogs (<?php echo count($blogs); ?>)
                </div>
                <?php if (count($blogs) > 0): ?>
                    <?php foreach ($blogs as $blog): ?>
                        <div class="blog-item">
                            <img src="<?php echo htmlspecialchars($blog['image_path']); ?>" alt="">
                            <div class="blog-item-info">
                                <div class="blog-item-title"><?php echo htmlspecialchars($blog['title']); ?></div>
                                <div class="blog-item-category"><?php echo htmlspecialchars($blog['category']); ?></div>
                            </div>
                            <form method="POST" onsubmit="return confirm('Delete this blog?');">
                                <input type="hidden" name="delete_id" value="<?php echo $blog['id']; ?>">
                                <button type="submit" class="btn-delete">
                                    <ion-icon name="trash-outline"></ion-icon> Delete
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <ion-icon name="document-text-outline" style="font-size: 2rem; color: #ddd;"></ion-icon>
                        <p>No blogs found. Add your first blog post.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

</body>
</html>
