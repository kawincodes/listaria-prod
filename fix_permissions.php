<?php
// Upload this file to public_html and run it: listaria.in/fix_permissions.php
ini_set('display_errors', 1);

echo "<h1>Auto-Fixer</h1>";

$db_file = __DIR__ . '/database.sqlite';
$directory = __DIR__;

echo "Target Database: $db_file<br>";

// 1. Try to CHMOD the directory
echo "<h3>1. Fixing Folder Permissions...</h3>";
if (@chmod($directory, 0755)) {
    echo "<span style='color:green'>✔ Folder permissions changed to 0755.</span><br>";
} else {
    echo "<span style='color:orange'>⚠ Could not change folder permissions via PHP. (This is normal on some hosts).</span><br>";
    echo "Current folder perms: " . substr(sprintf('%o', fileperms($directory)), -4) . "<br>";
}

// 2. Try to CHMOD the file
echo "<h3>2. Fixing File Permissions...</h3>";
if (file_exists($db_file)) {
    if (@chmod($db_file, 0644)) {
        echo "<span style='color:green'>✔ Database file permissions changed to 0644.</span><br>";
    } else {
        echo "<span style='color:orange'>⚠ Could not change file permissions.</span><br>";
    }
} else {
    echo "<span style='color:red'>✘ Database file NOT FOUND at: $db_file</span><br>";
    echo "Please upload database.sqlite to this folder!<br>";
    exit;
}

// 3. Test Connection
echo "<h3>3. Testing Connection...</h3>";
try {
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->query("SELECT 1");
    echo "<h1 style='color:green'>SUCCESS! Database is working.</h1>";
    echo "You can now delete this file and visit your site.";
} catch (PDOException $e) {
    echo "<h1 style='color:red'>FAILED: " . $e->getMessage() . "</h1>";
    echo "<p>If it still fails, the issue is definitely the <strong>Folder Permissions</strong>.</p>";
    echo "<p>Please ensure the folder <code>" . __DIR__ . "</code> is set to <strong>0755</strong> in cPanel File Manager.</p>";
}
?>
