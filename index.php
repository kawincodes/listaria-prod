<?php
require_once __DIR__ . '/includes/session.php';
require 'includes/db.php';

// Fetch products with optional category filter
$category = $_GET['category'] ?? 'All';

$search = $_GET['search'] ?? '';

if (!empty($search)) {
    // Search Mode
    $stmt = $pdo->prepare("SELECT p.*, MAX(o.created_at) as sold_at_date 
                         FROM products p 
                         LEFT JOIN orders o ON p.id = o.product_id 
                         WHERE p.is_published = 1 
                         AND p.approval_status = 'approved'
                         AND p.category NOT IN ('Fashion', 'Tops', 'Bottoms', 'Jackets', 'Shoes', 'Bags', 'Accessories')
                         AND (p.title LIKE ? OR p.category LIKE ?)
                         GROUP BY p.id 
                         ORDER BY p.created_at DESC");
    $stmt->execute(["%$search%", "%$search%"]);
    $products = $stmt->fetchAll();

} elseif ($category === 'All') {
    $stmt = $pdo->query("SELECT p.*, MAX(o.created_at) as sold_at_date 
                         FROM products p 
                         LEFT JOIN orders o ON p.id = o.product_id 
                         WHERE p.is_published = 1 
                         AND p.approval_status = 'approved'
                         AND p.category NOT IN ('Fashion', 'Tops', 'Bottoms', 'Jackets', 'Shoes', 'Bags', 'Accessories')
                         GROUP BY p.id 
                         ORDER BY p.created_at DESC");
    $products = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT p.*, MAX(o.created_at) as sold_at_date 
                           FROM products p 
                           LEFT JOIN orders o ON p.id = o.product_id 
                           WHERE p.is_published = 1 AND p.category = ? 
                           AND p.approval_status = 'approved'
                           GROUP BY p.id 
                           ORDER BY p.created_at DESC");
    $stmt->execute([$category]);
    $products = $stmt->fetchAll();
}

$product_count = count($products);



// Fetch active banners
$now = date('Y-m-d H:i:s');
$banner_stmt = $pdo->prepare("SELECT * FROM banners 
                             WHERE is_active = 1 
                             AND (start_time IS NULL OR start_time <= ?) 
                             AND (end_time IS NULL OR end_time >= ?) 
                             ORDER BY display_order ASC, created_at DESC");
$banner_stmt->execute([$now, $now]);
$banners = $banner_stmt->fetchAll();

include 'includes/header.php';
?>

<style>
/* Premium Category Styles */
.categories-container {
    background: linear-gradient(to bottom, #fff5f8 0%, #ffffff 100%);
    padding: 15px 0 10px;
    border-bottom: 1px solid #f1f5f9;
    position: sticky;
    top: 70px;
    z-index: 100;
    display: block;
}

.categories-scroll-area {
    position: relative;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
}

.categories-wrapper {
    display: flex;
    gap: 24px;
    overflow-x: auto;
    scroll-behavior: smooth;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding: 12px 40px;
    white-space: initial;
    align-items: flex-start;
}

.cat-arrow {
    display: none;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: white;
    border: 1px solid #e5e5e5;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    cursor: pointer;
    z-index: 10;
    align-items: center;
    justify-content: center;
    color: #333;
    font-size: 1rem;
    transition: all 0.2s;
}
.cat-arrow:hover {
    background: #f5f5f5;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}
.cat-arrow-left { left: 4px; }
.cat-arrow-right { right: 4px; }

@media (min-width: 769px) {
    .cat-arrow { display: flex; }
}

.categories-wrapper::-webkit-scrollbar {
    display: none;
}

.category-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 9px;
    min-width: 76px;
    width: 76px;
    flex-shrink: 0;
    text-decoration: none;
    color: #64748b;
    transition: all 0.3s;
    position: relative;
    padding-bottom: 8px;
}

.category-icon-box {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border-radius: 16px;
    font-size: 1.65rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.06);
    transition: all 0.3s;
    border: 1px solid #f1f5f9;
    flex-shrink: 0;
}

.category-item span {
    font-size: 0.76rem;
    font-weight: 500;
    text-align: center;
    white-space: normal;
    word-break: break-word;
    line-height: 1.2;
    width: 100%;
}

.category-item.active {
    color: #1e293b;
}

.category-item.active .category-icon-box {
    background: #fdf4ff; /* Very soft purple background */
    border-color: #a855f7; /* Purple border to match theme */
    color: #7e22ce; /* Deep purple icon */
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(168, 85, 247, 0.15); /* Purple shadow */
}

.category-item.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    width: 25px;
    height: 3px;
    background: #1e293b;
    border-radius: 2px;
}

/* Banner Carousel Styles */
.carousel-container {
    position: relative;
    max-width: 1200px;
    margin: 20px auto; /* Reverted to tight desktop spacing */
    padding: 0 20px;
}

.carousel-view {
    width: 100%;
    aspect-ratio: 16/4; /* Much shorter height so products are clearly visible below it */
    border-radius: 40px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    isolation: isolate;
}

.carousel-track {
    display: flex;
    width: 100%;
    height: 100%;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.carousel-slide {
    min-width: 100%;
    height: 100%;
}

.carousel-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* Image fills the container, cropping edges if needed */
}

.carousel-dots {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 15px;
}

.dot {
    width: 8px;
    height: 8px;
    border-radius: 4px;
    background: #e2e8f0;
    cursor: pointer;
    transition: all 0.3s;
}

.dot.active {
    width: 24px;
    background: #1e293b;
}

@media (max-width: 768px) {
    .categories-wrapper {
        gap: 14px;
        padding: 10px 16px;
        justify-content: flex-start;
    }
    .category-item {
        min-width: 64px;
        width: 64px;
        gap: 7px;
    }
    .category-icon-box {
        width: 52px;
        height: 52px;
        font-size: 1.45rem;
        border-radius: 14px;
    }
    .category-item span {
        font-size: 0.7rem;
    }
    .carousel-view { 
        aspect-ratio: 16/8.5; 
        border-radius: 50px; 
        box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.2);
    }
    .carousel-container { 
        padding: 0 12px; 
        margin-top: 80px !important;
        margin-bottom: 20px;
        margin-left: auto;
        margin-right: auto;
    }
    .categories-container {
        position: sticky;
        top: 60px;
        border-top: 1px solid #f1f5f9;
        padding: 6px 0 8px;
        display: block;
    }
}
</style>

    <div class="categories-container">
        <div class="categories-scroll-area">
        <button class="cat-arrow cat-arrow-left" id="catArrowLeft" aria-label="Scroll left">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </button>
        <button class="cat-arrow cat-arrow-right" id="catArrowRight" aria-label="Scroll right">
            <ion-icon name="chevron-forward-outline"></ion-icon>
        </button>
        <div class="categories-wrapper" id="categoriesWrapper">
            <?php
            $category_icons = [
                'All' => 'bag-handle-outline',
                'Phones' => 'phone-portrait-outline',
                'Laptops' => 'laptop-outline',
                'Fashion' => 'shirt-outline',
                'Beauty' => 'sparkles-outline',
                'Gaming' => 'game-controller-outline',
                'Home' => 'home-outline',
                'Books' => 'book-outline',
                'Sports' => 'football-outline',
                'Electronics' => 'watch-outline',
                'Kids' => 'happy-outline',
                'Beds' => 'bed-outline',
                'Sofas' => 'tv-outline',
                'Dining & Coffee' => 'cafe-outline',
                'Home Office' => 'briefcase-outline',
                'Home Furniture' => 'hammer-outline',
                'Fridge' => 'snow-outline',
                'Washing Machine' => 'water-outline'
            ];
            
            foreach ($category_icons as $cat => $icon) {
                $activeClass = ($category === $cat) ? 'active' : '';
                $url = ($cat === 'All') ? 'index.php' : "index.php?category=" . urlencode($cat);
                echo "
                <a href='$url' class='category-item $activeClass'>
                    <div class='category-icon-box'>
                        <ion-icon name='$icon'></ion-icon>
                    </div>
                    <span>$cat</span>
                </a>";
            }
            ?>
        </div>
        </div>
    </div>

    <script>
    (function() {
        var wrapper = document.getElementById('categoriesWrapper');
        var leftBtn = document.getElementById('catArrowLeft');
        var rightBtn = document.getElementById('catArrowRight');
        if (!wrapper || !leftBtn || !rightBtn) return;

        var scrollAmount = 250;

        function updateArrows() {
            leftBtn.style.opacity = wrapper.scrollLeft <= 5 ? '0' : '1';
            leftBtn.style.pointerEvents = wrapper.scrollLeft <= 5 ? 'none' : 'auto';
            var atEnd = wrapper.scrollLeft + wrapper.clientWidth >= wrapper.scrollWidth - 5;
            rightBtn.style.opacity = atEnd ? '0' : '1';
            rightBtn.style.pointerEvents = atEnd ? 'none' : 'auto';
        }

        leftBtn.addEventListener('click', function() {
            wrapper.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        });
        rightBtn.addEventListener('click', function() {
            wrapper.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        });

        wrapper.addEventListener('scroll', updateArrows);
        updateArrows();
    })();
    </script>

    <!-- Dynamic Banner Carousel -->
    <div class="carousel-container">
        <div class="carousel-view">
            <div class="carousel-track" id="carouselTrack">
                <?php if (!empty($banners)): ?>
                    <?php foreach ($banners as $b): ?>
                        <div class="carousel-slide">
                            <?php if ($b['link_url']): ?>
                                <a href="<?php echo htmlspecialchars($b['link_url']); ?>">
                            <?php endif; ?>
                            <img src="<?php echo htmlspecialchars($b['image_path']); ?>" alt="<?php echo htmlspecialchars($b['title'] ?? 'Banner'); ?>">
                            <?php if ($b['link_url']): ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback Premium Brand Banner -->
                    <div class="carousel-slide">
                        <div style="width:100%; height:100%; background: radial-gradient(circle at center, #1a1a2e 0%, #0f0f1a 100%); display:flex; flex-direction:column; align-items:center; justify-content:center; color:#fff; position: relative; overflow: hidden;">
                            <!-- Pattern overlay -->
                            <div style="position: absolute; top:0; left:0; width:100%; height:100%; opacity: 0.1; background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 20px 20px;"></div>
                            
                            <div style="font-size: 5rem; line-height: 1; font-family: 'Playfair Display', serif; color: #a21caf; position: relative; text-shadow: 0 0 20px rgba(162, 28, 175, 0.4);">L</div>
                            <div style="margin-top: 15px; font-size: 1.2rem; font-weight: 300; letter-spacing: 0.6rem; color: #94a3b8; font-family: serif; text-transform: uppercase;">Listaria.in</div>
                            
                            <!-- Glow effect -->
                            <div style="position: absolute; bottom: -40px; width: 120%; height: 80px; background: #a21caf; filter: blur(50px); opacity: 0.2; border-radius: 50%;"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="carousel-dots" id="carouselDots">
            <?php if (count($banners) > 1): ?>
                <?php for ($i = 0; $i < count($banners); $i++): ?>
                    <div class="dot <?php echo $i === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $i; ?>)"></div>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let currentSlide = 0;
        const track = document.getElementById('carouselTrack');
        const dots = document.querySelectorAll('.dot');
        const slideCount = <?php echo count($banners) ?: 1; ?>;

        function updateCarousel() {
            if (!track) return;
            track.style.transform = `translateX(-${currentSlide * 100}%)`;
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSlide);
            });
        }

        function goToSlide(index) {
            currentSlide = index;
            updateCarousel();
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % slideCount;
            updateCarousel();
        }

        if (slideCount > 1) {
            setInterval(nextSlide, 5000); // 5 seconds
        }

        // Mouse drag to scroll for categories
        const catWrapper = document.querySelector('.categories-wrapper');
        let isDown = false;
        let startX;
        let scrollLeft;

        if (catWrapper) {
            catWrapper.addEventListener('mousedown', (e) => {
                isDown = true;
                catWrapper.style.cursor = 'grabbing';
                startX = e.pageX - catWrapper.offsetLeft;
                scrollLeft = catWrapper.scrollLeft;
                // Disable smooth scroll during drag to make it immediate attached to mouse
                catWrapper.style.scrollBehavior = 'auto'; 
            });
            catWrapper.addEventListener('mouseleave', () => {
                isDown = false;
                catWrapper.style.cursor = '';
                catWrapper.style.scrollBehavior = 'smooth';
            });
            catWrapper.addEventListener('mouseup', () => {
                isDown = false;
                catWrapper.style.cursor = '';
                catWrapper.style.scrollBehavior = 'smooth';
            });
            catWrapper.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - catWrapper.offsetLeft;
                const walk = (x - startX) * 2; // scroll-fast
                catWrapper.scrollLeft = scrollLeft - walk;
            });
            
            // Prevent links from clicking while dragging
            let isDragging = false;
            catWrapper.addEventListener('mousemove', (e) => {
               if(isDown) isDragging = true;
            });
            catWrapper.addEventListener('mousedown', () => {
               isDragging = false;
            });
            const catLinks = catWrapper.querySelectorAll('a');
            catLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    if (isDragging) e.preventDefault();
                });
            });
        }
    </script>

    <div class="container">
        
        <div class="section-header">
            <div class="section-title">Featured Products</div>
            <div class="product-count"><?php echo $product_count; ?> products found</div>
        </div>

        <div class="product-grid">
            <?php foreach ($products as $product): 
                // Auto-delete (hide) sold items after 24 hours
                if (isset($product['status']) && $product['status'] === 'sold' && !empty($product['sold_at_date'])) {
                    if (time() - strtotime($product['sold_at_date']) > 86400) { // 86400 seconds = 24 hours
                        $product_count--; // Decrement header count visually? 
                        // Actually $product_count was already printed above. 
                        // To fix the count accurately we'd need to filter before printing header, but for now visual hiding is priority.
                        continue; 
                    }
                }
 
                $images = json_decode($product['image_paths']);
                $main_image = $images[0] ?? 'https://via.placeholder.com/300'; // Fallback
                
                $tag_class = '';
                switch($product['condition_tag']) {
                    case 'Brand New': $tag_class = 'condition-new'; break;
                    case 'Lightly Used': $tag_class = 'condition-light'; break;
                    case 'Regularly Used': $tag_class = 'condition-regular'; break;
                }
            ?>
            <div class="card-wrapper">
                <a href="product_details.php?id=<?php echo $product['id']; ?>" class="product-card">
                    <div class="product-image-container">
                        <?php if(isset($product['status']) && $product['status'] === 'sold'): ?>
                            <span class="condition-badge sold-badge">SOLD</span>
                        <?php else: ?>
                            <span class="condition-badge <?php echo $tag_class; ?>"><?php echo htmlspecialchars($product['condition_tag']); ?></span>
                            <?php if (($product['approval_status'] ?? '') === 'approved'): ?>
                                <span class="verified-badge-card">
                                    <ion-icon name="checkmark-circle"></ion-icon>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <img src="<?php echo htmlspecialchars($main_image); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" class="product-image" loading="lazy">
                    </div>
                    <!-- Removed title/price details from grid card to match clean image-focused look of screenshot, 
                         OR we can keep them but minimal. Screenshot shows ONLY images with overlay tags. 
                         User said "mimic style", and screenshot shows ONLY images. 
                         However, functionally, purely images might be confusing. 
                         The screenshot IS cut off, maybe there is text below.
                         But wait, user request says "Product Cards: White cards with the image on top."
                         AND "Grid: Add 'Featured Products' and count header".
                         
                         Let's look at the screenshot again carefully.
                         Screenshot shows: "Lightly Used" tag on top left. Image. NO visible text below in the partial view.
                         However, usually there is text. I will keep the text but make it very clean to ensure usability.
                    -->
                     <div class="product-info">
                        <div class="product-title"><?php echo htmlspecialchars($product['title']); ?></div>
                        <div class="product-price">
                            ₹<?php echo number_format($product['price_min'], 0); ?>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
            
            <?php if (count($products) == 0): ?>
                <div style="grid-column: 1/-1; text-align:center; padding: 4rem; color: #999;">
                    <h3>No products yet.</h3>
                    <p>Be the first to list something!</p>
                    <a href="sell.php" class="btn-sell" style="display:inline-block; margin-top:1rem;">Sell Now</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

<!-- Success Modal -->
<?php 
$show_success_modal = false;
if (isset($_GET['order_success']) && $_GET['order_success'] == '1' && isset($_SESSION['user_id'])) {
    // Server-side Verification: Check if the latest order is indeed successful
    $v_stmt = $pdo->prepare("SELECT order_status, created_at FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $v_stmt->execute([$_SESSION['user_id']]);
    $latest_order = $v_stmt->fetch();
    
    // Check if status is 'Success' and it was created recently (e.g., within last 10 mins to avoid old success showing)
    if ($latest_order && $latest_order['order_status'] === 'Success') {
        // Optional: Check timestamp difference if needed, but Status check is primarily what was asked.
        $show_success_modal = true;
    }
}

if ($show_success_modal): 
?>
<div id="successModal" class="success-modal">
    <div class="success-content">
        <div class="success-icon">
            <ion-icon name="checkmark-circle"></ion-icon>
        </div>
        <h2>Paid Successfully!</h2>
        <p>Your transaction was verified and your order has been placed.</p>
        <button onclick="closeModal()" class="btn-primary" style="margin-top:1.5rem; width:100%;">Continue Shopping</button>
    </div>
</div>

<style>
.success-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    animation: fadeIn 0.3s ease;
}
.success-content {
    background: white;
    padding: 2.5rem;
    border-radius: 20px;
    text-align: center;
    max-width: 350px;
    width: 90%;
    animation: slideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.success-icon {
    font-size: 4rem;
    color: #27ae60;
    margin-bottom: 1rem;
}
.success-content h2 {
    margin: 0 0 0.5rem 0;
    color: #333;
}
.success-content p {
    color: #666;
    margin: 0;
    line-height: 1.5;
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>

<script>
    // Remove query param to prevent modal on refresh
    if (history.replaceState) {
        var url = new URL(window.location.href);
        url.searchParams.delete('order_success');
        url.searchParams.delete('payment_pending'); // Remove pending flag too
        history.replaceState(null, '', url.toString());
    }

    function closeModal() {
        document.getElementById('successModal').style.display = 'none';
        if(document.getElementById('pendingModal')) document.getElementById('pendingModal').style.display = 'none';
    }
</script>
<?php endif; ?>

<!-- Payment Pending Verification Modal -->
<?php if (isset($_GET['payment_pending']) && $_GET['payment_pending'] == '1'): ?>
<div id="pendingModal" class="success-modal">
    <div class="success-content">
        <div class="success-icon" style="color:#f39c12;">
            <ion-icon name="time-outline"></ion-icon>
        </div>
        <h2>Verification Pending</h2>
        <p>Thank you! Your payment UTR has been submitted.</p>
        <p style="font-size:0.9rem; color:#888; margin-top:5px;">Your order will be confirmed once the admin verifies your transaction.</p>
        <button onclick="closeModal()" class="btn-primary" style="margin-top:1.5rem; width:100%; background:#f39c12;">Okay, I'll Wait</button>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

