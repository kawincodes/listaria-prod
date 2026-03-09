<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key='founder_1_image'");
$val = $stmt->fetchColumn();
echo "DB_VALUE: " . $val . "\n";

echo "UPLOAD_DIR_EXISTS: " . (is_dir('assets/uploads') ? 'YES' : 'NO') . "\n";
echo "UPLOAD_DIR_WRITABLE: " . (is_writable('assets/uploads') ? 'YES' : 'NO') . "\n";
?>
