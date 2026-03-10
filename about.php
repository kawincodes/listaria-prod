<?php
require 'includes/db.php';
require_once __DIR__ . '/includes/session.php';

$stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'about_content'");
$stmt->execute();
$aboutContent = $stmt->fetchColumn();

if (!$aboutContent) {
    $aboutContent = "<div style='text-align:center; padding:3rem;'><h1>About Listaria</h1><p>Content coming soon...</p></div>";
}

include 'includes/header.php';
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 5rem; max-width: 800px;">
    <?php echo $aboutContent; ?>
</div>

<?php include 'includes/footer.php'; ?>
