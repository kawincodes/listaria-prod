<?php
// api/update_listing.php
require_once '../includes/db.php';

// Disable error display to prevent breaking JSON, but log errors
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../php-error.log');

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../includes/session.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$product_id = $_POST['product_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Verify ownership
$stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND user_id = ?");
$stmt->execute([$product_id, $user_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Product not found or access denied']);
    exit;
}

try {
    if ($action === 'update_price') {
        $new_price = $_POST['price'] ?? '';
        
        // Debug logging
        file_put_contents('../debug_price.log', date('Y-m-d H:i:s') . " - Updating ID $product_id to $new_price\n", FILE_APPEND);

        if (!is_numeric($new_price) || $new_price < 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid price']);
            exit;
        }

        // Update both price_min and price_max to keep them in sync
        $updateStmt = $pdo->prepare("UPDATE products SET price_min = ?, price_max = ? WHERE id = ?");
        $result = $updateStmt->execute([$new_price, $new_price, $product_id]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Price updated successfully']);
        } else {
             echo json_encode(['success' => false, 'message' => 'Database update failed']);
        }

    } elseif ($action === 'mark_sold') {
        $pdo->prepare("UPDATE products SET quantity = 0, status = 'sold' WHERE id = ?")->execute([$product_id]);
        
        echo json_encode(['success' => true, 'message' => 'Marked as sold']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
