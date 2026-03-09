<?php
try {
    $db_file = __DIR__ . '/database.sqlite';
    $dsn = "sqlite:$db_file";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, null, null, $options);
    
    // Verify the column exists
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $has_profile_img = false;
    foreach ($columns as $col) {
        if ($col['name'] === 'profile_image') {
            $has_profile_img = true;
            break;
        }
    }

    if (!$has_profile_img) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_image TEXT DEFAULT NULL");
        echo "Added profile_image column.\n";
    } else {
        echo "profile_image column already exists.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
