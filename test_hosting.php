<?php
// Upload this file to your public_html folder and visit listaria.in/test_hosting.php

echo "<h1>Hosting Diagnostic</h1>";

// 1. Check PHP Version
echo "<h2>1. PHP Version</h2>";
echo "Current PHP Version: " . phpversion() . "<br>";
if (version_compare(phpversion(), '7.4.0', '<')) {
    echo "<strong style='color:red'>WARNING: PHP version is too old. Please upgrade to 7.4 or higher.</strong>";
} else {
    echo "<strong style='color:green'>PHP Version OK.</strong>";
}

// 2. Check SQLite Extension
echo "<h2>2. SQLite Extension</h2>";
if (extension_loaded('pdo_sqlite')) {
    echo "<strong style='color:green'>PDO SQLite extension is loaded.</strong>";
} else {
    echo "<strong style='color:red'>ERROR: pdo_sqlite extension is NOT loaded. Enable it in cPanel > Select PHP Version.</strong>";
}

// 3. Check Database File
echo "<h2>3. Database File</h2>";
$db_path = __DIR__ . '/database.sqlite';
echo "Looking for database at: $db_path<br>";

if (file_exists($db_path)) {
    echo "<strong style='color:green'>Database file found.</strong><br>";
    
    // Check Permissions
    $perms = substr(sprintf('%o', fileperms($db_path)), -4);
    echo "Permissions: $perms (Should be 0644)<br>";
    
    if (!is_writable($db_path)) {
        echo "<strong style='color:red'>ERROR: Database file is NOT writable. Change permissions to 644.</strong><br>";
    }
    
    // Check Directory Permissions
    $dir_perms = substr(sprintf('%o', fileperms(__DIR__)), -4);
    echo "Folder Permissions: $dir_perms (Should be 0755)<br>";
    if (!is_writable(__DIR__)) {
        echo "<strong style='color:red'>ERROR: Folder is NOT writable. SQLite needs to write temporary files. Change folder permissions to 755.</strong>";
    }

} else {
    echo "<strong style='color:red'>ERROR: database.sqlite not found in the same folder as this script. Did you upload it?</strong>";
}

// 4. Test Connection
echo "<h2>4. Connection Test</h2>";
try {
    $pdo = new PDO("sqlite:$db_path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT count(*) FROM sqlite_master");
    echo "<strong style='color:green'>SUCCESS: Connected to database! found tables.</strong>";
} catch (PDOException $e) {
    echo "<strong style='color:red'>Connection Failed: " . $e->getMessage() . "</strong>";
}
?>
