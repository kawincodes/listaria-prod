<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT id, title, price_min FROM products");
$rows = $stmt->fetchAll();
$deleted = 0;
foreach ($rows as $row) {
    if (!is_numeric($row['price_min'])) {
        $pdo->exec("DELETE FROM products WHERE id = " . (int)$row['id']);
        $deleted++;
    }
}
echo "Cleaned up $deleted invalid products.";
?>
