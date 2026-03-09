<?php
try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='users';");
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
    $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='products';");
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
    $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='wishlists';");
    if($stmt) print_r($stmt->fetch(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
