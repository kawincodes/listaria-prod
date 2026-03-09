<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $dbPath = __DIR__ . '/database.sqlite';
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if expected_return_date exists
    $columns = $pdo->query("PRAGMA table_info(returns)")->fetchAll(PDO::FETCH_ASSOC);
    $hasCol = false;
    foreach ($columns as $col) {
        if ($col['name'] === 'expected_return_date') {
            $hasCol = true;
            break;
        }
    }

    if (!$hasCol) {
        $pdo->exec("ALTER TABLE returns ADD COLUMN expected_return_date TEXT");
        echo "Column 'expected_return_date' added successfully.";
    } else {
        echo "Column 'expected_return_date' already exists.";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
