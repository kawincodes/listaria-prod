<?php
require 'includes/db.php';
require_once __DIR__ . '/includes/session.php';

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM custom_pages WHERE slug = ? AND is_published = 1");
$stmt->execute([$slug]);
$page = $stmt->fetch();

if (!$page) {
    http_response_code(404);
    include 'includes/header.php';
    echo '<div class="container" style="padding:5rem 1rem; text-align:center;">';
    echo '<h1 style="font-size:2rem; color:#1a1a1a; margin-bottom:1rem;">Page Not Found</h1>';
    echo '<p style="color:#888;">The page you are looking for does not exist or has been unpublished.</p>';
    echo '<a href="index.php" style="display:inline-block; margin-top:2rem; padding:0.75rem 1.5rem; background:#6B21A8; color:white; border-radius:8px; text-decoration:none; font-weight:600;">Go Home</a>';
    echo '</div>';
    include 'includes/footer.php';
    exit;
}

$pageTitle = htmlspecialchars($page['title']);
$metaDesc = htmlspecialchars($page['meta_description'] ?? '');

include 'includes/header.php';
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 5rem; max-width: 900px;">
    <h1 style="font-size: 2rem; font-weight: 800; color: #1a1a1a; margin-bottom: 2rem; text-align: center;">
        <?php echo $pageTitle; ?>
    </h1>
    <div class="page-content" style="line-height: 1.8; color: #333;">
        <?php echo $page['content']; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
