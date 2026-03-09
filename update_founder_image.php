<?php
require 'includes/db.php';

try {
    $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'founder_1_image'");
    $stmt->execute(['assets/img/founder1.jpg']);
    echo "Founder 1 image updated successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
