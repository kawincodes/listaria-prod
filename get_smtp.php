<?php
require 'includes/db.php';
$stmt = $pdo->query('SELECT setting_key FROM site_settings');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['setting_key'] . "\n";
}
?>
