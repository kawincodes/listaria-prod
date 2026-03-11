<?php
require 'includes/db.php';

$category = $_GET['category'] ?? 'All';
$search   = $_GET['search'] ?? '';

$valid_categories = ['Tops', 'Bottoms', 'Jackets', 'Shoes', 'Bags', 'Accessories'];

if ($category !== 'All' && in_array($category, $valid_categories)) {
    $thrift_condition = "AND p.category = '$category'";
} else {
    $thrift_condition = "AND p.category IN ('Fashion', 'Tops', 'Bottoms', 'Jackets', 'Shoes', 'Bags', 'Accessories')";
}

if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT p.*, MAX(o.created_at) as sold_at_date, u.account_type, u.business_name, u.full_name, u.profile_image 
                         FROM products p 
                         LEFT JOIN orders o ON p.id = o.product_id 
                         LEFT JOIN users u ON p.user_id = u.id
                         WHERE p.is_published = 1 AND p.approval_status = 'approved'
                         $thrift_condition
                         AND (p.title LIKE ? OR p.condition_tag LIKE ?)
                         GROUP BY p.id 
                         ORDER BY (p.is_featured = 1 AND (p.boosted_until IS NULL OR p.boosted_until > datetime('now'))) DESC, p.created_at DESC");
    $stmt->execute(["%$search%", "%$search%"]);
    $products = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT p.*, MAX(o.created_at) as sold_at_date, u.account_type, u.business_name, u.full_name, u.profile_image 
                         FROM products p 
                         LEFT JOIN orders o ON p.id = o.product_id 
                         LEFT JOIN users u ON p.user_id = u.id
                         WHERE p.is_published = 1 AND p.approval_status = 'approved'
                         $thrift_condition
                         GROUP BY p.id 
                         ORDER BY (p.is_featured = 1 AND (p.boosted_until IS NULL OR p.boosted_until > datetime('now'))) DESC, p.created_at DESC");
    $products = $stmt->fetchAll();
}

$vendor_groups      = [];
$community_products = [];

foreach ($products as $p) {
    $community_products[] = $p;
    if (($p['account_type'] ?? '') === 'vendor') {
        $vid = $p['user_id'];
        if (!isset($vendor_groups[$vid])) {
            $vendor_groups[$vid] = [
                'name'     => !empty($p['business_name']) ? $p['business_name'] : $p['full_name'],
                'photo'    => $p['profile_image'] ?? null,
                'id'       => $vid,
                'products' => []
            ];
        }
        $vendor_groups[$vid]['products'][] = $p;
    }
}

include 'includes/header.php';
?>
<style>
/* ── Thrift+ page theme ─────────────────────────── */
body { background-color: #f5f0e6 !important; color: #1a1a1a !important; }
:root { --bg: #f5f0e6; --card: #ffffff; --green: #2a5c2a; --red: #e53935; --text: #1a1a1a; --muted: #7a7268; --border: #e4ddd0; }

.thrift-wrap { max-width: 900px; margin: 0 auto; padding: 80px 16px 100px; }

/* ── Category pills ─────────────────────────────── */
.cat-row { display: flex; align-items: center; gap: 8px; margin: 1.5rem 0 1.25rem; }
.cat-arrow { width: 36px; height: 36px; border-radius: 50%; background: white; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; color: var(--text); box-shadow: 0 1px 4px rgba(0,0,0,0.07); transition: background 0.15s; }
.cat-arrow:hover { background: #ede8de; }
.cat-scroll { display: flex; gap: 8px; overflow-x: auto; scroll-behavior: smooth; scrollbar-width: none; -ms-overflow-style: none; padding: 4px 2px; }
.cat-scroll::-webkit-scrollbar { display: none; }
.cat-pill { flex-shrink: 0; padding: 8px 20px; border-radius: 50px; font-size: 0.88rem; font-weight: 600; text-decoration: none; border: 1.5px solid var(--border); background: white; color: var(--text); transition: all 0.18s; white-space: nowrap; }
.cat-pill:hover { background: #ede8de; border-color: #c9c1b3; }
.cat-pill.active { background: var(--green); color: white; border-color: var(--green); }

/* ── Hero text ──────────────────────────────────── */
.thrift-hero-text { margin: 1.75rem 0 1.25rem; }
.thrift-hero-text h1 { font-family: Georgia, 'Times New Roman', serif; font-size: 2rem; font-weight: 900; margin: 0 0 4px; letter-spacing: -0.5px; color: var(--text); display: flex; align-items: baseline; gap: 14px; flex-wrap: wrap; }
.thrift-hero-text h1 span.sub { font-size: 1.05rem; font-weight: 400; color: var(--muted); font-family: inherit; }
.sub-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
.sub-tag { padding: 6px 16px; border-radius: 50px; font-size: 0.82rem; font-weight: 600; background: white; border: 1.5px solid var(--border); color: var(--text); }

/* ── Section headings ───────────────────────────── */
.section-heading { font-family: Georgia, 'Times New Roman', serif; font-size: 1.55rem; font-weight: 800; color: var(--text); margin: 2rem 0 1.1rem; }

/* ── Vendor collectives ─────────────────────────── */
.collectives-scroll { display: flex; gap: 14px; overflow-x: auto; scrollbar-width: none; -ms-overflow-style: none; padding: 4px 2px 12px; }
.collectives-scroll::-webkit-scrollbar { display: none; }
.collective-card { flex-shrink: 0; width: 140px; background: white; border-radius: 16px; padding: 16px 10px 14px; text-align: center; text-decoration: none; color: var(--text); box-shadow: 0 2px 12px rgba(0,0,0,0.07); border: 1px solid var(--border); transition: transform 0.2s, box-shadow 0.2s; }
.collective-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
.collective-avatar { width: 68px; height: 68px; border-radius: 50%; margin: 0 auto 10px; border: 2.5px solid #e4b87a; overflow: hidden; background: #f5f0e6; display: flex; align-items: center; justify-content: center; }
.collective-avatar img { width: 100%; height: 100%; object-fit: cover; }
.collective-avatar .avatar-init { font-family: Georgia, serif; font-size: 1.8rem; font-weight: 800; color: var(--green); }
.collective-name { font-weight: 700; font-size: 0.82rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px; margin: 0 auto 2px; }
.collective-count { font-size: 0.72rem; color: var(--muted); }
.collective-check { color: #27ae60; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 3px; margin-top: 4px; font-weight: 600; }

/* ── Product cards ──────────────────────────────── */
.products-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
@media(min-width: 560px) { .products-grid { grid-template-columns: repeat(3, 1fr); gap: 14px; } }
@media(min-width: 780px) { .products-grid { grid-template-columns: repeat(4, 1fr); gap: 16px; } }

.p-card { background: white; border-radius: 14px; overflow: hidden; text-decoration: none; color: var(--text); display: block; box-shadow: 0 2px 10px rgba(0,0,0,0.07); border: 1px solid var(--border); transition: transform 0.2s, box-shadow 0.2s; }
.p-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.13); }
.p-card.featured { border-color: #9333ea; box-shadow: 0 2px 10px rgba(147,51,234,0.12); }
.p-card.featured:hover { box-shadow: 0 8px 24px rgba(147,51,234,0.2); }
.p-card-img { position: relative; aspect-ratio: 1/1; overflow: hidden; }
.p-card-img img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.3s; }
.p-card:hover .p-card-img img { transform: scale(1.04); }
.price-badge { position: absolute; top: 8px; left: 8px; background: var(--red); color: white; padding: 3px 10px; border-radius: 50px; font-size: 0.78rem; font-weight: 700; z-index: 3; }
.sold-badge { position: absolute; top: 8px; left: 8px; background: rgba(0,0,0,0.75); color: white; padding: 3px 10px; border-radius: 50px; font-size: 0.72rem; font-weight: 700; z-index: 3; letter-spacing: 0.5px; }
.featured-badge { position: absolute; top: 8px; right: 8px; background: linear-gradient(135deg,#6B21A8,#9333ea); color: white; padding: 3px 9px; border-radius: 50px; font-size: 0.68rem; font-weight: 700; z-index: 3; letter-spacing: 0.3px; }
.p-card-body { padding: 10px 12px 12px; }
.p-card-title { font-family: Georgia, 'Times New Roman', serif; font-weight: 700; font-size: 0.9rem; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 0 3px; }
.p-card-cond { font-size: 0.72rem; color: var(--muted); }

/* ── Search bar ─────────────────────────────────── */
.search-wrap { position: relative; margin-bottom: 1.25rem; }
.search-wrap input { width: 100%; padding: 11px 16px 11px 42px; border-radius: 50px; border: 1.5px solid var(--border); background: white; font-size: 0.9rem; font-family: inherit; color: var(--text); outline: none; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
.search-wrap input:focus { border-color: var(--green); }
.search-wrap .search-ico { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 1.1rem; pointer-events: none; }
.search-wrap .search-clear { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); cursor: pointer; font-size: 0.8rem; font-weight: 600; text-decoration: none; }

/* ── Empty state ────────────────────────────────── */
.empty-state { text-align: center; padding: 4rem 2rem; }
.empty-state .icon { font-size: 3.5rem; margin-bottom: 1rem; }
.empty-state h3 { font-family: Georgia, serif; font-size: 1.4rem; margin: 0 0 8px; }
.empty-state p { color: var(--muted); font-size: 0.9rem; margin: 0 0 1.5rem; }
.btn-green { display: inline-block; background: var(--green); color: white; padding: 10px 24px; border-radius: 50px; font-weight: 700; font-size: 0.88rem; text-decoration: none; }

/* ── Decorative element ─────────────────────────── */
.deco-bar { display: flex; align-items: center; gap: 10px; margin: 2.25rem 0 0.5rem; }
.deco-bar::before, .deco-bar::after { content: ''; flex: 1; height: 1px; background: var(--border); }
.deco-label { font-size: 0.75rem; font-weight: 700; color: var(--muted); letter-spacing: 1px; text-transform: uppercase; white-space: nowrap; }
</style>

<div class="thrift-wrap">

    <!-- Search -->
    <form method="GET" action="thrift.php" class="search-wrap">
        <?php if ($category !== 'All'): ?>
        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
        <?php endif; ?>
        <ion-icon name="search-outline" class="search-ico"></ion-icon>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search thrift finds...">
        <?php if (!empty($search)): ?>
        <a href="thrift.php?category=<?php echo urlencode($category); ?>" class="search-clear">✕ Clear</a>
        <?php endif; ?>
    </form>

    <!-- Category Pills -->
    <div class="cat-row">
        <div class="cat-arrow" id="catLeft"><ion-icon name="chevron-back-outline"></ion-icon></div>
        <div class="cat-scroll" id="catScroll">
            <?php
            $cats = ['All', 'Tops', 'Bottoms', 'Jackets', 'Shoes', 'Bags', 'Accessories'];
            $catIcons = ['All'=>'', 'Tops'=>'👕 ', 'Bottoms'=>'👖 ', 'Jackets'=>'🧥 ', 'Shoes'=>'👟 ', 'Bags'=>'👜 ', 'Accessories'=>'💍 '];
            foreach ($cats as $cat):
                $active = ($category === $cat) ? 'active' : '';
                $icon   = $catIcons[$cat] ?? '';
                $href   = 'thrift.php?category=' . urlencode($cat);
                echo "<a href='$href' class='cat-pill $active'>{$icon}{$cat}</a>";
            endforeach;
            ?>
        </div>
        <div class="cat-arrow" id="catRight"><ion-icon name="chevron-forward-outline"></ion-icon></div>
    </div>

    <!-- Hero Text -->
    <div class="thrift-hero-text">
        <h1>Thrift+ <span class="sub">Re-Cycled, Sustainable Style</span></h1>
        <div class="sub-tags">
            <span class="sub-tag">Apparel 👕</span>
            <span class="sub-tag">Accessories 💍</span>
            <span class="sub-tag">Vintage Finds 📻</span>
            <span class="sub-tag">Condition: Used</span>
            <?php if (!empty($search)): ?>
            <span class="sub-tag" style="background:#fef3c7;border-color:#fde68a;color:#92400e;">🔍 "<?php echo htmlspecialchars($search); ?>"</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($products)): ?>
    <div class="empty-state">
        <div class="icon">🧺</div>
        <h3>No thrift finds yet</h3>
        <p>Be the first to list something pre-loved from your closet!</p>
        <a href="sell.php?source=thrift" class="btn-green">List Pre-Loved Item</a>
    </div>

    <?php else: ?>

    <!-- Discover Curated Collectives -->
    <?php if (!empty($vendor_groups)): ?>
    <div>
        <div class="section-heading">Discover Curated Collectives</div>
        <div class="collectives-scroll">
            <?php foreach ($vendor_groups as $vid => $group):
                $itemCount = count($group['products']);
                $countLabel = $itemCount >= 1000 ? number_format($itemCount/1000, 1) . 'k+' : $itemCount . '+';
            ?>
            <a href="vendor.php?id=<?php echo $vid; ?>" class="collective-card">
                <div class="collective-avatar">
                    <?php if (!empty($group['photo'])): ?>
                    <img src="<?php echo htmlspecialchars($group['photo']); ?>" alt="<?php echo htmlspecialchars($group['name']); ?>">
                    <?php else: ?>
                    <span class="avatar-init"><?php echo strtoupper(substr($group['name'], 0, 1)); ?></span>
                    <?php endif; ?>
                </div>
                <div class="collective-name"><?php echo htmlspecialchars($group['name']); ?></div>
                <div class="collective-check"><ion-icon name="checkmark-circle" style="color:#27ae60;font-size:0.85rem;"></ion-icon> Verified</div>
                <div class="collective-count"><?php echo $itemCount; ?> unique finds</div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Community Closet -->
    <div class="deco-bar"><span class="deco-label">Community Closet</span></div>
    <div class="section-heading" style="margin-top:0.5rem;">Community Closet</div>

    <?php if (!empty($search)): ?>
    <p style="color:var(--muted);font-size:0.85rem;margin:-0.5rem 0 1rem;">
        <?php echo count($community_products); ?> result<?php echo count($community_products) !== 1 ? 's' : ''; ?> for "<?php echo htmlspecialchars($search); ?>"
    </p>
    <?php endif; ?>

    <div class="products-grid">
        <?php foreach ($community_products as $p):
            // Skip sold items older than 24h
            if (isset($p['status']) && $p['status'] === 'sold' && !empty($p['sold_at_date'])) {
                if (time() - strtotime($p['sold_at_date']) > 86400) continue;
            }
            $images   = json_decode($p['image_paths']);
            $img      = $images[0] ?? 'https://placehold.co/300x300/f5f0e6/7a7268?text=No+Image';
            $price    = number_format($p['price_min'], 0);
            $title    = htmlspecialchars($p['title']);
            $cond     = htmlspecialchars($p['condition_tag'] ?? '');
            $url      = "product_details.php?id={$p['id']}&source=thrift";
            $isSold   = isset($p['status']) && $p['status'] === 'sold';
            $isBoosted = !empty($p['is_featured']) && !empty($p['boosted_until']) && strtotime($p['boosted_until']) > time();
        ?>
        <a href="<?php echo $url; ?>" class="p-card <?php echo $isBoosted ? 'featured' : ''; ?>">
            <div class="p-card-img">
                <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo $title; ?>" loading="lazy">
                <?php if ($isSold): ?>
                <span class="sold-badge">SOLD</span>
                <?php else: ?>
                <span class="price-badge">₹<?php echo $price; ?></span>
                <?php endif; ?>
                <?php if ($isBoosted && !$isSold): ?>
                <span class="featured-badge">⚡ Featured</span>
                <?php endif; ?>
            </div>
            <div class="p-card-body">
                <div class="p-card-title"><?php echo $title; ?></div>
                <div class="p-card-cond"><?php echo $cond ? $cond . ' condition' : ''; ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>

<!-- Category scroll JS -->
<script>
(function(){
    var wrap  = document.getElementById('catScroll');
    var left  = document.getElementById('catLeft');
    var right = document.getElementById('catRight');
    if (wrap && left && right) {
        left.addEventListener('click',  function(){ wrap.scrollBy({ left: -180, behavior: 'smooth' }); });
        right.addEventListener('click', function(){ wrap.scrollBy({ left: 180,  behavior: 'smooth' }); });
    }
    // Scroll active pill into view
    var active = wrap && wrap.querySelector('.cat-pill.active');
    if (active) active.scrollIntoView({ inline: 'center', block: 'nearest' });
})();
</script>

<!-- Success modal -->
<?php
$show_success_modal = false;
if (isset($_GET['order_success']) && $_GET['order_success'] == '1' && isset($_SESSION['user_id'])) {
    $v_stmt = $pdo->prepare("SELECT order_status, created_at FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $v_stmt->execute([$_SESSION['user_id']]);
    $latest_order = $v_stmt->fetch();
    if ($latest_order && $latest_order['order_status'] === 'Success') $show_success_modal = true;
}
if ($show_success_modal): ?>
<div id="successModal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.55);display:flex;align-items:center;justify-content:center;z-index:2000;">
    <div style="background:white;padding:2.5rem;border-radius:20px;text-align:center;max-width:340px;width:90%;">
        <div style="font-size:3.5rem;margin-bottom:1rem;">🌿</div>
        <h2 style="font-family:Georgia,serif;margin:0 0 8px;font-size:1.4rem;">Thrifted Successfully!</h2>
        <p style="color:#7a7268;margin:0 0 1.5rem;font-size:0.9rem;">Your sustainable purchase is on its way.</p>
        <button onclick="document.getElementById('successModal').style.display='none'" style="background:#2a5c2a;color:white;border:none;padding:12px 28px;border-radius:50px;font-weight:700;cursor:pointer;font-size:0.95rem;width:100%;">Keep Thrifting</button>
    </div>
</div>
<script>
if (history.replaceState) {
    var u = new URL(window.location.href);
    u.searchParams.delete('order_success');
    u.searchParams.delete('payment_pending');
    history.replaceState(null, '', u.toString());
}
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
