<?php
require 'includes/db.php';
$settings = $pdo->query("SELECT * FROM site_settings WHERE setting_key LIKE 'founder_%_image'")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($settings);
echo "</pre>";
?>
