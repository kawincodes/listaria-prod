<?php
require 'includes/db.php';

try {
    $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'founder_2_image'");
    $stmt->execute(['assets/img/founder2.jpg']);
    echo "Founder 2 image updated successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
