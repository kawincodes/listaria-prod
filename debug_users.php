<?php
require 'includes/db.php';

try {
    $stmt = $pdo->query("SELECT id, full_name, email, address, phone FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Total Users: " . count($users) . "\n\n";

    foreach ($users as $user) {
        echo "ID: " . $user['id'] . "\n";
        echo "Name: " . $user['full_name'] . "\n";
        echo "Address: " . ($user['address'] ? $user['address'] : 'NULL') . "\n";
        echo "Phone: " . ($user['phone'] ? $user['phone'] : 'NULL') . "\n";
        echo "--------------------------\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
