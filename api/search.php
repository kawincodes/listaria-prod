<?php
header('Content-Type: application/json');
require '../includes/db.php';

$query = $_GET['q'] ?? '';
$query = trim($query);

if (empty($query) || strlen($query) < 2) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

try {
    // Search products by title, category, or brand
    $stmt = $pdo->prepare("SELECT id, title, price_min, image_paths 
                          FROM products 
                          WHERE is_published = 1 
                          AND approval_status = 'approved'
                          AND (title LIKE ? OR category LIKE ? OR brand LIKE ?)
                          LIMIT 10");
    
    $searchTerm = "$query%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $products = $stmt->fetchAll();

    $results = [];
    foreach ($products as $p) {
        $images = json_decode($p['image_paths']);
        $img = $images[0] ?? 'https://via.placeholder.com/100';
        
        $results[] = [
            'id' => $p['id'],
            'title' => $p['title'],
            'price' => number_format($p['price_min'], 0),
            'image' => $img
        ];
    }

    echo json_encode(['success' => true, 'results' => $results]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
