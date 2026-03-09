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
    $message = '<div class="alert success">Item removed from Thrift Cart.</div>';
}

// Fetch Thrift Wishlist Items (Filtered by thrift categories)
$thrift_categories = "('Fashion', 'Tops', 'Bottoms', 'Jackets', 'Shoes', 'Bags', 'Accessories')";
$sql = "
    SELECT p.*, w.created_at as saved_at 
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id = ? AND p.category IN $thrift_categories
    ORDER BY w.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$products = $stmt->fetchAll();

include 'includes/header.php';
?>

<style>
    /* Thrift+ Theme Overrides for Cart */
    body { 
        background-color: #fdfcf8 !important; 
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif !important;
    }
    .thrift-cart-container {
        padding: 100px 20px 120px;
        max-width: 800px;
        margin: 0 auto;
        min-height: 80vh;
    }
    .thrift-cart-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 2rem;
    }
    .thrift-cart-header h1 {
        font-family: 'Times New Roman', serif;
        font-size: 2.2rem;
        font-weight: 900;
        margin: 0;
        color: #1a1a1a;
    }
    .thrift-badge-mini {
        background: linear-gradient(90deg, #6B21A8 0%, #76a07b 100%);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .cart-item-card {
        background: #fff;
        border: 2px solid #1a1a1a;
        box-shadow: 6px 6px 0px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        display: flex;
        padding: 15px;
        gap: 20px;
        position: relative;
    }
    .cart-item-img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border: 1px solid #eee;
    }
    .cart-item-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .cart-item-title {
        font-family: 'Times New Roman', serif;
        font-weight: 900;
        font-size: 1.2rem;
        color: #1a1a1a;
        text-decoration: none;
        margin-bottom: 4px;
        display: block;
    }
    .cart-item-price {
        font-weight: 800;
        font-size: 1.1rem;
        color: #304c3e;
    }
    .cart-item-meta {
        font-size: 0.8rem;
        color: #666;
    }
    .cart-actions {
        display: flex;
        gap: 15px;
        margin-top: 10px;
    }
    .btn-checkout-mini {
        background: #1a1a1a;
        color: #fff;
        text-decoration: none;
        padding: 8px 16px;
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        border: 1px solid #1a1a1a;
    }
    .btn-remove-mini {
        background: transparent;
        color: #ef4444;
        border: none;
        font-size: 0.85rem;
        font-weight: 700;
        cursor: pointer;
        padding: 0;
    }

    .empty-thrift-cart {
        text-align: center;
        padding: 60px 20px;
        border: 2px dashed #ccc;
        border-radius: 20px;
        background: #fdfcf8;
    }
    .empty-thrift-cart ion-icon {
        font-size: 4rem;
        color: #ddd;
        margin-bottom: 20px;
    }
    .btn-shop-thrift {
        display: inline-block;
        margin-top: 20px;
        background: #304c3e;
        color: #fff;
        padding: 12px 30px;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 800;
    }
</style>

<div class="thrift-cart-container">
    <div class="thrift-cart-header">
        <h1>Thrift Cart</h1>
        <span class="thrift-badge-mini">Thrift+</span>
    </div>

    <?php if (isset($message)) echo $message; ?>

    <?php if (count($products) > 0): ?>
        <div class="thrift-cart-list">
            <?php foreach ($products as $product): 
                $images = json_decode($product['image_paths']);
                $thumb = $images[0] ?? 'https://via.placeholder.com/150';
                $url = "product_details.php?id={$product['id']}&source=thrift";
            ?>
                <div class="cart-item-card">
                    <a href="<?php echo $url; ?>">
                        <img src="<?php echo htmlspecialchars($thumb); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" class="cart-item-img">
                    </a>
                    <div class="cart-item-info">
                        <div>
                            <a href="<?php echo $url; ?>" class="cart-item-title"><?php echo htmlspecialchars($product['title']); ?></a>
                            <div class="cart-item-price">₹<?php echo number_format($product['price_min'], 0); ?></div>
                        </div>
                        <div class="cart-item-meta">
                            Condition: <?php echo htmlspecialchars($product['condition_tag']); ?>
                        </div>
                        <div class="cart-actions">
                            <a href="payment.php?id=<?php echo $product['id']; ?>&source=thrift" class="btn-checkout-mini">Checkout</a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="remove_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="btn-remove-mini">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-thrift-cart">
            <ion-icon name="bag-handle-outline"></ion-icon>
            <h3 style="font-family: 'Times New Roman', serif; font-size: 1.8rem; font-weight: 900;">Your Thrift Cart is Empty</h3>
            <p>Ready to give something a new life?</p>
            <a href="thrift.php" class="btn-shop-thrift">Browse Thrift+</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
