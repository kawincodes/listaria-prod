<?php
// listaria.in/test_write.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Write Test</h1>";

require 'includes/db.php';

$db_path = __DIR__ . '/database.sqlite';
$dir_path = __DIR__;

echo "Database Path: $db_path<br>";
echo "Folder Path: $dir_path<br>";

// 1. Check Folder Writability
if (is_writable($dir_path)) {
    echo "<span style='color:green'>✔ Folder is Writable.</span><br>";
} else {
    echo "<span style='color:red'>✘ Folder is NOT Writable. (Required for SQLite WAL/Journal files).</span><br>";
    echo "Actions needed: CHMOD folder to 755.<br>";
}

// 2. Check File Writability
if (is_writable($db_path)) {
    echo "<span style='color:green'>✔ Database File is Writable.</span><br>";
} else {
    echo "<span style='color:red'>✘ Database File is NOT Writable.</span><br>";
    echo "Actions needed: CHMOD database.sqlite to 644.<br>";
}

// 3. Try Real Write
echo "<h3>Attempting Write...</h3>";
try {
    $stmt = $pdo->prepare("CREATE TABLE IF NOT EXISTS test_write (id INTEGER PRIMARY KEY, created_at TEXT)");
    $stmt->execute();
    
    $stmt = $pdo->prepare("INSERT INTO test_write (created_at) VALUES (?)");
    $stmt->execute([date('Y-m-d H:i:s')]);
    
    echo "<h2 style='color:green'>SUCCESS: Write operation worked.</h2>";
} catch (PDOException $e) {
    echo "<h2 style='color:red'>FAILED: " . $e->getMessage() . "</h2>";
    echo "If you see 'attempt to write a readonly database', you MUST fix the permissions of the <b>FOLDER</b> and the <b>FILE</b>.";
}
?>
