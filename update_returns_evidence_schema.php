<?php
// Standalone migration script
$db_file = __DIR__ . '/database.sqlite';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Updating Returns Schema...\n";

    $stmt = $pdo->query("PRAGMA table_info(returns)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'name');

    if (!in_array('evidence_photos', $columnNames)) {
        $pdo->exec("ALTER TABLE returns ADD COLUMN evidence_photos TEXT");
        echo "Column 'evidence_photos' added.\n";
    } else {
        echo "Column 'evidence_photos' already exists.\n";
    }

    if (!in_array('evidence_video', $columnNames)) {
        $pdo->exec("ALTER TABLE returns ADD COLUMN evidence_video TEXT");
        echo "Column 'evidence_video' added.\n";
    } else {
        echo "Column 'evidence_video' already exists.\n";
    }

    echo "Schema updated successfully!\n";
} catch (Exception $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
    exit(1);
}
?>
