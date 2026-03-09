<?php
// MySQL Connection (Commented out)
require_once __DIR__ . '/config.php';
// $host = '127.0.0.1';
// $db   = 'listaria_db';
// $user = 'root';
// $pass = '';
// $charset = 'utf8mb4';
// $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// SQLite Connection
// SQLite Connection
// Use __DIR__ to make it portable (works on both Localhost and cPanel)
$db_file = __DIR__ . '/../database.sqlite'; // adjusting for 'includes' folder
$dsn = "sqlite:$db_file";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, null, null, $options);
    // Create function for REGEXP if needed (SQLite doesn't have it by default)
    // $pdo->sqliteCreateFunction('regexp', function ($pattern, $string) {
    //    return preg_match('/' . str_replace('/', '\/', $pattern) . '/', $string);
    // });
    $pdo->exec("CREATE TABLE IF NOT EXISTS custom_pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        content TEXT DEFAULT '',
        meta_description TEXT DEFAULT '',
        is_published INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
