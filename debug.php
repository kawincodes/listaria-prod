<?php
// listaria.in/debug.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Configuration Check</h1>";

$path = __DIR__ . '/includes/db.php';
if (file_exists($path)) {
    require $path;
    echo "<b>Includes Path:</b> " . $path . "<br>";
    echo "<b>Resolved DB Path (\$db_file):</b> " . htmlspecialchars($db_file) . "<br>";
    
    if (strpos($db_file, 'c:') !== false || strpos($db_file, 'C:') !== false) {
        echo "<h2 style='color:red'>CRITICAL ERROR DETECTED</h2>";
        echo "<p style='color:red; font-size:18px;'>Your <code>includes/db.php</code> still has the WINDOWS path inside it!</p>";
        echo "<p>This is why it fails. The server thinks 'C:/Users/...' is a filename, so it creates different files in different folders.</p>";
        echo "<h3>SOLUTION:</h3>";
        echo "1. Open <code>includes/db.php</code> on your computer.<br>";
        echo "2. Change the \$db_file line to: <code>\$db_file = __DIR__ . '/../database.sqlite';</code><br>";
        echo "3. Re-upload <code>includes/db.php</code> to the server.<br>";
        echo "4. Run <a href='update_db_schema.php'>update_db_schema.php</a> again.";
    } else {
        echo "<span style='color:green'>✔ Database path looks correct (Relative).</span><br>";
        
        // Check for tables
        try {
            $pdo = new PDO("sqlite:$db_file");
            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
            echo "<h3>Tables in this database:</h3>";
            echo "<ul>";
            foreach ($tables as $t) {
                echo "<li>$t</li>";
            }
            echo "</ul>";
            
            if (!in_array('negotiations', $tables)) {
                echo "<strong style='color:red'>MISSING table 'negotiations'. Run update_db_schema.php!</strong>";
            } else {
                echo "<strong style='color:green'>Table 'negotiations' exists.</strong>";
            }
        } catch (Exception $e) {
            echo "Error connecting: " . $e->getMessage();
        }
    }
} else {
    echo "Could not find includes/db.php";
}
?>
