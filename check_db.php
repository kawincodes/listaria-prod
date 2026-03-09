<?php
require 'includes/db.php';
$stmt = $pdo->query('SELECT id, title, image_paths, approval_status FROM products ORDER BY id DESC LIMIT 5');
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
