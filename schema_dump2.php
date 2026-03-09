<?php
$db_file = __DIR__ . '/database.sqlite';
$dsn = "sqlite:$db_file";
try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name IN ('products', 'users')");
    $schemas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($schemas as $schema) {
        echo $schema . "\n\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
