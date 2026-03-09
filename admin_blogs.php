<?php
require 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';

// Handle Delete
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM blogs WHERE id = ?");
    if ($stmt->execute([$_POST['delete_id']])) {
        $message = "Blog deleted successfully.";
    } else {
        $message = "Error deleting blog.";
    }
}

// Handle Add
if (isset($_POST['add_blog'])) {
    $title = $_POST['title'];
    $category = $_POST['category'];
    $content = $_POST['content'];
    $image_path = 'https://via.placeholder.com/600x400'; // Default

    // Basic Image Upload Logic
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

// Fetch Blogs
$blogs = $pdo->query("SELECT * FROM blogs ORDER BY created_at DESC")->fetchAll();

include 'includes/header.php';
?>

<div class="container" style="padding-top: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>Manage Blogs</h1>
        <a href="admin_dashboard.php" class="btn-primary" style="text-decoration: none; padding: 0.5rem 1rem;">Back to Dashboard</a>
    </div>

    <?php if ($message): ?>
        <div style="background: #e8f5e9; color: #1b5e20; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        
        <!-- Add Blog Form -->
        <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <h2 style="margin-top: 0;">Add New Blog</h2>
            <form method="POST" enctype="multipart/form-data">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Title</label>
                    <input type="text" name="title" required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Category</label>
                    <input type="text" name="category" required placeholder="e.g. Sustainability" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Image</label>
                    <input type="file" name="image" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Content</label>
                    <textarea name="content" rows="6" required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px;"></textarea>
                </div>
                <button type="submit" name="add_blog" class="btn-primary" style="width: 100%;">Publish Blog</button>
            </form>
        </div>

        <!-- Existing Blogs List -->
        <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <h2 style="margin-top: 0;">Existing Blogs</h2>
            <?php if (count($blogs) > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($blogs as $blog): ?>
                        <div style="display: flex; gap: 1rem; border: 1px solid #eee; padding: 1rem; border-radius: 8px; align-items: center;">
                            <img src="<?php echo htmlspecialchars($blog['image_path']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                            <div style="flex: 1;">
                                <div style="font-weight: 700;"><?php echo htmlspecialchars($blog['title']); ?></div>
                                <div style="font-size: 0.8rem; color: #666;"><?php echo htmlspecialchars($blog['category']); ?></div>
                            </div>
                            <form method="POST" onsubmit="return confirm('Delete this blog?');">
                                <input type="hidden" name="delete_id" value="<?php echo $blog['id']; ?>">
                                <button type="submit" style="background: #ffebee; color: #c62828; border: none; padding: 0.5rem; border-radius: 4px; cursor: pointer;">Delete</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No blogs found.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
