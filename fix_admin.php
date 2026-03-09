<?php
require 'includes/db.php';

// 1. Update Admin Email to what the user is typing
$stmt = $pdo->prepare("UPDATE users SET email = 'admin@mail.com' WHERE is_admin = 1");
$stmt->execute();

echo "Admin email updated to admin@mail.com\n";
?>
