<?php
require 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle Remove Action
if (isset($_POST['remove_id'])) {
    $remove_id = $_POST['remove_id'];
    $del = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $del->execute([$user_id, $remove_id]);
    $message = '<div class="alert success">Item removed from wishlist.</div>';
}

// Fetch Wishlist Items
$sql = "
    SELECT p.*, w.created_at as saved_at 
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$products = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container" style="padding-top: 100px; min-height: 60vh;">
    <h1 style="margin-bottom: 2rem; font-size: 2rem;">My Wishlist <span style="font-size:1rem; color:#666; font-weight:normal;">(<?php echo count($products); ?>)</span></h1>

    <?php if (isset($message)) echo $message; ?>

    <?php if (count($products) > 0): ?>
        <div class="wishlist-grid">
            <?php foreach ($products as $product): 
                $images = json_decode($product['image_paths']);
                $thumb = $images[0] ?? 'https://via.placeholder.com/150';
            ?>
                <div class="wishlist-item">
                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="img-link">
                        <img src="<?php echo htmlspecialchars($thumb); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                    </a>
                    <div class="wishlist-info">
                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="title"><?php echo htmlspecialchars($product['title']); ?></a>
                        <div class="price">₹<?php echo number_format($product['price_min'], 0); ?></div>
                        <div class="date">Saved on <?php echo date('M j, Y', strtotime($product['saved_at'])); ?></div>
                        
                        <div class="actions">
                            <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn-view">View</a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="remove_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="btn-remove">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <ion-icon name="heart-dislike-outline"></ion-icon>
            <h3>Your wishlist is empty</h3>
            <p>Save items you like to keep track of them.</p>
            <a href="index.php" class="btn-primary">Browse Products</a>
        </div>
    <?php endif; ?>
</div>

<style>
    .wishlist-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 2rem;
    }
    
    .wishlist-item {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #f0f0f0;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
    }
    
    .img-link { display: block; height: 200px; overflow: hidden; }
    .wishlist-item img {
        width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;
    }
    .wishlist-item:hover img { transform: scale(1.05); }
    
    .wishlist-info { padding: 1rem; flex: 1; display: flex; flex-direction: column; }
    
    .title { 
        font-weight: 700; color: #333; text-decoration: none; font-size: 1.1rem; margin-bottom: 0.5rem; display: block;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; 
    }
    .price { color: var(--brand-color); font-weight: 700; font-size: 1.1rem; margin-bottom: 0.5rem; }
    .date { color: #888; font-size: 0.8rem; margin-bottom: 1rem; }
    
    .actions { margin-top: auto; display: flex; gap: 10px; }
    
    .btn-view {
        flex: 1; text-align: center; padding: 0.6rem; border: 1px solid #ddd; border-radius: 6px; 
        text-decoration: none; color: #555; font-weight: 600;
    }
    .btn-remove {
        flex: 1; padding: 0.6rem; border: 1px solid #ffcdd2; background: #ffebee; 
        color: #c62828; border-radius: 6px; cursor: pointer; font-weight: 600;
    }
    
    .empty-state {
        text-align: center; padding: 4rem; background: #f9f9f9; border-radius: 16px;
    }
    .empty-state ion-icon { font-size: 3rem; color: #ccc; margin-bottom: 1rem; }
    .empty-state h3 { margin-bottom: 0.5rem; }
    .empty-state p { color: #666; margin-bottom: 2rem; }
    
    .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
    .alert.success { background-color: #e8f5e9; color: #2e7d32; }
</style>

<?php include 'includes/footer.php'; ?>
