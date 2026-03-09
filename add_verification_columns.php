<?php
try {
    require 'includes/db.php';
} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage() . "\n");
}

try {
    // Add verification columns to users table
    // Check if columns exist first to avoid error
    $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
    $has_email_verified = false;
    $has_verification_token = false;

    foreach ($cols as $col) {
        if ($col['name'] == 'email_verified') $has_email_verified = true;
        if ($col['name'] == 'verification_token') $has_verification_token = true;
    }

    if (!$has_email_verified) {
        $pdo->exec("ALTER TABLE users ADD COLUMN email_verified INTEGER DEFAULT 0");
        echo "Added email_verified column.\n";
    } else {
        echo "email_verified column already exists.\n";
    }

    if (!$has_verification_token) {
        $pdo->exec("ALTER TABLE users ADD COLUMN verification_token TEXT");
        echo "Added verification_token column.\n";
    } else {
        echo "verification_token column already exists.\n";
    }

} catch (PDOException $e) {
    echo "Error modifying table: " . $e->getMessage() . "\n";
}
?>
