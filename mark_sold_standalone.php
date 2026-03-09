<?php
try {
    $db_file = 'c:/Users/hemur/OneDrive/Documents/Desktop/extracted/database.sqlite';
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("UPDATE products SET status = 'sold', approval_status = 'approved', is_published = 1 WHERE id = 17");
    $stmt->execute();
    echo "Product 17 marked as sold.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
