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

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
body { background: #F5F2E8 !important; color: #2D2A26 !important; }
:root {
    --bg: #F5F2E8; --card: #FFFFFF; --green: #2D4739;
    --red: #C0533A; --text: #2D2A26; --muted: #8A8279;
    --border: #E0D8C8; --cream: #EDE9DD; --stone: #D4CCBC;
    --warm: #B8A992; --accent: #A67B5B;
}
*, *::before, *::after { box-sizing: border-box; }

/* ── Botanical background decorations ────────────── */
.thrift-page { position: relative; overflow: hidden; }
.thrift-page::before {
    content: '';
    position: fixed; top: 0; right: 0; width: 280px; height: 320px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 240' fill='none' stroke='%23C5B9A5' stroke-width='0.8' opacity='0.35'%3E%3Cpath d='M160 20 C150 40, 140 60, 130 100 C120 140, 115 160, 110 200'/%3E%3Cpath d='M160 20 C170 45, 175 70, 165 95 C155 120, 140 100, 130 100'/%3E%3Cpath d='M160 20 C145 30, 135 50, 130 100'/%3E%3Cellipse cx='150' cy='170' rx='18' ry='25' transform='rotate(-15 150 170)'/%3E%3Cellipse cx='145' cy='170' rx='14' ry='20' transform='rotate(-15 145 170)'/%3E%3Cpath d='M150 195 L148 230'/%3E%3Cpath d='M140 160 C130 150, 120 155, 115 165'/%3E%3Cpath d='M160 175 C170 170, 178 175, 180 185'/%3E%3Cpath d='M80 40 C90 35, 100 30, 110 35 C120 40, 115 50, 105 48 C95 46, 85 45, 80 40Z'/%3E%3Cpath d='M60 70 C70 62, 82 60, 90 65 C98 70, 92 78, 82 76 C72 74, 63 75, 60 70Z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-size: contain; background-position: top right;
    pointer-events: none; z-index: 0;
}
.thrift-page::after {
    content: '';
    position: fixed; bottom: 0; left: 0; width: 220px; height: 260px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 180 220' fill='none' stroke='%23C5B9A5' stroke-width='0.8' opacity='0.3'%3E%3Cpath d='M30 200 C35 180, 40 160, 50 130 C60 100, 65 80, 70 50'/%3E%3Cpath d='M70 50 C60 65, 45 75, 50 130'/%3E%3Cpath d='M70 50 C80 70, 85 90, 50 130'/%3E%3Cpath d='M50 130 C40 125, 25 130, 20 140'/%3E%3Cpath d='M50 130 C55 120, 65 118, 72 125'/%3E%3Cpath d='M30 200 C25 195, 20 185, 22 175'/%3E%3Ccircle cx='100' cy='180' r='15'/%3E%3Ccircle cx='100' cy='180' r='10'/%3E%3Ccircle cx='100' cy='180' r='4'/%3E%3Cline x1='100' y1='165' x2='100' y2='130'/%3E%3Cpath d='M100 130 C95 120, 105 115, 100 130'/%3E%3Cpath d='M120 190 C130 185, 140 190, 145 200'/%3E%3Cpath d='M75 195 C65 200, 55 195, 50 185'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-size: contain; background-position: bottom left;
    pointer-events: none; z-index: 0;
}

/* ── Main wrapper ────────────────────────────────── */
.thrift-wrap { max-width: 900px; margin: 0 auto; padding: 80px 16px 110px; position: relative; z-index: 1; }

/* ── Category pills (pebble/stone style) ─────────── */
.cat-row { display: flex; align-items: center; gap: 8px; margin: 1rem 0 1.5rem; }
.cat-arrow {
    width: 34px; height: 34px; border-radius: 50%;
    background: var(--cream); border: 1.5px solid var(--stone);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; flex-shrink: 0; color: var(--text);
    transition: all 0.2s;
}
.cat-arrow:hover { background: var(--stone); }
.cat-scroll { display: flex; gap: 10px; overflow-x: auto; scroll-behavior: smooth; scrollbar-width: none; padding: 6px 2px; }
.cat-scroll::-webkit-scrollbar { display: none; }

.cat-pill {
    flex-shrink: 0; padding: 10px 22px; font-size: 0.88rem; font-weight: 600;
    text-decoration: none; white-space: nowrap; cursor: pointer;
    font-family: 'Inter', sans-serif; letter-spacing: 0.2px;
    border-radius: 24px;
    background: linear-gradient(145deg, #DDD5C4, #C8BFAE);
    color: var(--text); border: none;
    box-shadow: 1px 2px 4px rgba(0,0,0,0.08), inset 0 1px 1px rgba(255,255,255,0.5);
    transition: all 0.25s ease;
    position: relative;
}
.cat-pill:hover { transform: translateY(-1px); box-shadow: 2px 4px 8px rgba(0,0,0,0.12), inset 0 1px 1px rgba(255,255,255,0.5); }
.cat-pill.active {
    background: var(--green); color: #FFFFFF;
    box-shadow: 2px 3px 8px rgba(45,71,57,0.3), inset 0 1px 1px rgba(255,255,255,0.15);
}
.cat-icon { font-size: 1rem; margin-right: 4px; display: inline-block; vertical-align: middle; }

/* ── Hero text ──────────────────────────────────── */
.thrift-hero-text { margin: 0.5rem 0 1.5rem; padding: 0 4px; }
.thrift-hero-text h1 {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 2.2rem; font-weight: 800; margin: 0 0 2px;
    color: var(--text); display: flex; align-items: baseline;
    gap: 14px; flex-wrap: wrap; letter-spacing: -0.5px;
}
.thrift-hero-text .hero-sub {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1.15rem; font-weight: 400; color: var(--muted);
    display: block; margin-top: 2px; letter-spacing: 0.3px;
}
.sub-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
.sub-tag {
    padding: 7px 16px; border-radius: 20px; font-size: 0.82rem;
    font-weight: 600; font-family: 'Inter', sans-serif;
    background: var(--cream); border: 1px solid var(--stone);
    color: var(--text);
}

/* ── Section headings ───────────────────────────── */
.section-heading {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1.65rem; font-weight: 800; color: var(--text);
    margin: 2.5rem 0 1.25rem; letter-spacing: -0.3px;
}

/* ── Vendor collectives (story-ring style) ────────── */
.collectives-scroll {
    display: flex; gap: 18px; overflow-x: auto;
    scrollbar-width: none; padding: 6px 4px 16px;
}
.collectives-scroll::-webkit-scrollbar { display: none; }
.collective-card {
    flex-shrink: 0; width: 115px; text-align: center;
    text-decoration: none; color: var(--text);
    transition: transform 0.25s ease;
}
.collective-card:hover { transform: translateY(-3px); }
.collective-ring {
    width: 78px; height: 78px; border-radius: 50%;
    background: conic-gradient(from 45deg, #A67B5B, #D4A574, #8B6942, #C9A87C, #A67B5B);
    padding: 3px; margin: 0 auto 10px; position: relative;
}
.collective-ring.first-ring {
    background: conic-gradient(from 45deg, #7B3F2E, #C0533A, #A67B5B, #D4A574, #7B3F2E);
}
.collective-avatar {
    width: 100%; height: 100%; border-radius: 50%;
    border: 3px solid var(--bg);
    overflow: hidden; background: var(--cream);
    display: flex; align-items: center; justify-content: center;
}
.collective-avatar img { width: 100%; height: 100%; object-fit: cover; }
.collective-avatar .avatar-init {
    font-family: 'Playfair Display', serif; font-size: 1.8rem;
    font-weight: 800; color: var(--green);
}
.collective-name {
    font-family: 'Inter', sans-serif; font-weight: 700;
    font-size: 0.78rem; margin: 0 0 1px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    max-width: 110px;
}
.collective-verify {
    display: inline-flex; align-items: center; gap: 2px;
    font-size: 0.72rem; font-weight: 600; margin-bottom: 2px;
}
.collective-count { font-size: 0.68rem; color: var(--muted); font-family: 'Inter', sans-serif; }

/* ── Product cards grid ─────────────────────────── */
.products-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
@media(min-width: 560px) { .products-grid { grid-template-columns: repeat(3, 1fr); gap: 16px; } }
@media(min-width: 800px) { .products-grid { grid-template-columns: repeat(4, 1fr); gap: 18px; } }

.p-card {
    background: var(--card); border-radius: 18px; overflow: hidden;
    text-decoration: none; color: var(--text); display: block;
    border: 1px solid var(--border);
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.p-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.12); }
.p-card.featured { border-color: #9333ea; box-shadow: 0 2px 12px rgba(147,51,234,0.1); }
.p-card.featured:hover { box-shadow: 0 12px 32px rgba(147,51,234,0.18); }

.p-card-img { position: relative; aspect-ratio: 4/5; overflow: hidden; }
.p-card-img img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.4s ease; }
.p-card:hover .p-card-img img { transform: scale(1.06); }

.price-badge {
    position: absolute; top: 10px; left: 10px;
    background: var(--red); color: #FFFFFF;
    padding: 4px 12px; border-radius: 6px;
    font-size: 0.78rem; font-weight: 700; z-index: 3;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 2px 6px rgba(192,83,58,0.35);
}
.sold-badge {
    position: absolute; top: 10px; left: 10px;
    background: rgba(0,0,0,0.72); color: #FFFFFF;
    padding: 4px 12px; border-radius: 6px;
    font-size: 0.72rem; font-weight: 700; z-index: 3;
    letter-spacing: 0.8px; font-family: 'Inter', sans-serif;
}
.featured-badge {
    position: absolute; top: 10px; right: 10px;
    background: linear-gradient(135deg, #6B21A8, #9333ea); color: #FFFFFF;
    padding: 4px 10px; border-radius: 6px;
    font-size: 0.68rem; font-weight: 700; z-index: 3;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 2px 6px rgba(107,33,168,0.3);
}

.p-card-body { padding: 10px 12px 14px; }
.p-card-title {
    font-family: 'Inter', sans-serif; font-weight: 600;
    font-size: 0.85rem; line-height: 1.35;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    margin: 0 0 3px; color: var(--text);
}
.p-card-cond { font-size: 0.72rem; color: var(--muted); font-family: 'Inter', sans-serif; }
.p-card-size { font-size: 0.72rem; color: var(--muted); font-family: 'Inter', sans-serif; }

/* ── Search bar ─────────────────────────────────── */
.search-wrap { position: relative; margin-bottom: 0.5rem; }
.search-wrap input {
    width: 100%; padding: 12px 18px 12px 44px;
    border-radius: 50px; border: 1.5px solid var(--border);
    background: var(--card); font-size: 0.9rem;
    font-family: 'Inter', sans-serif; color: var(--text);
    outline: none; box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    transition: border-color 0.2s, box-shadow 0.2s;
}
.search-wrap input:focus { border-color: var(--green); box-shadow: 0 2px 12px rgba(45,71,57,0.12); }
.search-wrap input::placeholder { color: var(--muted); }
.search-wrap .search-ico { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 1.15rem; pointer-events: none; }
.search-wrap .search-clear { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: var(--muted); cursor: pointer; font-size: 0.8rem; font-weight: 600; text-decoration: none; font-family: 'Inter', sans-serif; }

/* ── Empty state ────────────────────────────────── */
.empty-state { text-align: center; padding: 4rem 2rem; }
.empty-state .e-icon { font-size: 3.5rem; margin-bottom: 1rem; }
.empty-state h3 { font-family: 'Playfair Display', serif; font-size: 1.5rem; margin: 0 0 8px; }
.empty-state p { color: var(--muted); font-size: 0.9rem; font-family: 'Inter', sans-serif; margin: 0 0 1.5rem; }
.btn-green { display: inline-block; background: var(--green); color: white; padding: 12px 28px; border-radius: 50px; font-weight: 700; font-size: 0.88rem; text-decoration: none; font-family: 'Inter', sans-serif; transition: background 0.2s; }
.btn-green:hover { background: #1f3328; }

/* ── Divider ────────────────────────────────────── */
.section-divider { display: flex; align-items: center; gap: 12px; margin: 2.5rem 0 0.25rem; }
.section-divider::before, .section-divider::after { content: ''; flex: 1; height: 1px; background: var(--stone); }
.section-divider span { font-size: 0.7rem; font-weight: 700; color: var(--warm); letter-spacing: 1.5px; text-transform: uppercase; font-family: 'Inter', sans-serif; white-space: nowrap; }

/* ── Search result count ────────────────────────── */
.result-count { color: var(--muted); font-size: 0.84rem; font-family: 'Inter', sans-serif; margin: -0.25rem 0 1.25rem; }

/* ── Mobile adjustments ─────────────────────────── */
@media(max-width: 480px) {
    .thrift-hero-text h1 { font-size: 1.75rem; }
    .thrift-hero-text .hero-sub { font-size: 1rem; }
    .section-heading { font-size: 1.35rem; }
    .cat-pill { padding: 9px 18px; font-size: 0.82rem; }
    .collective-ring { width: 66px; height: 66px; }
    .collective-card { width: 100px; }
}
</style>

<div class="thrift-page">
<div class="thrift-wrap">

    <!-- Search -->
    <form method="GET" action="thrift.php" class="search-wrap">
        <?php if ($category !== 'All'): ?>
        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
        <?php endif; ?>
        <ion-icon name="search-outline" class="search-ico"></ion-icon>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search thrift finds...">
        <?php if (!empty($search)): ?>
        <a href="thrift.php?category=<?php echo urlencode($category); ?>" class="search-clear">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Category Pills (pebble style) -->
    <div class="cat-row">
        <div class="cat-arrow" id="catLeft"><ion-icon name="chevron-back-outline"></ion-icon></div>
        <div class="cat-scroll" id="catScroll">
            <?php
            $cats = ['All', 'Tops', 'Bottoms', 'Jackets', 'Shoes', 'Bags', 'Accessories'];
            $catSvg = [
                'All'         => '',
                'Tops'        => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><path d="M6 2L2 8l4 2v12h12V10l4-2-4-6"/><path d="M12 2c-2 0-3 2-3 2h6s-1-2-3-2z"/></svg>',
                'Bottoms'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><path d="M6 2h12v8l-3 12h-2l-1-8-1 8H9L6 10z"/></svg>',
                'Jackets'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><path d="M6 2L1 8v6l3 1v7h16v-7l3-1V8l-5-6"/><path d="M12 2c-2 0-3 2-3 2h6s-1-2-3-2z"/></svg>',
                'Shoes'       => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><path d="M2 18h20v2H2zM4 18v-4l3-4 5 2 8-4 2 2v8"/></svg>',
                'Bags'        => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><rect x="4" y="8" width="16" height="14" rx="2"/><path d="M8 8V6a4 4 0 018 0v2"/></svg>',
                'Accessories' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="8"/><path d="M12 4v1M12 19v1"/></svg>'
            ];
            foreach ($cats as $cat):
                $active = ($category === $cat) ? 'active' : '';
                $icon   = $catSvg[$cat] ?? '';
                $href   = 'thrift.php?category=' . urlencode($cat);
            ?>
            <a href="<?php echo $href; ?>" class="cat-pill <?php echo $active; ?>"><?php echo $icon; ?><?php echo $cat; ?></a>
            <?php endforeach; ?>
        </div>
        <div class="cat-arrow" id="catRight"><ion-icon name="chevron-forward-outline"></ion-icon></div>
    </div>

    <!-- Hero Text -->
    <div class="thrift-hero-text">
        <h1>Thrift+</h1>
        <span class="hero-sub">Re-Cycled, Sustainable Style</span>
        <div class="sub-tags">
            <span class="sub-tag">Apparel 👕</span>
            <span class="sub-tag">Accessories 💍</span>
            <span class="sub-tag">Vintage Finds 📻</span>
            <span class="sub-tag">Condition: Used</span>
            <?php if (!empty($search)): ?>
            <span class="sub-tag" style="background:#FEF3C7;border-color:#FDE68A;color:#92400E;">🔍 "<?php echo htmlspecialchars($search); ?>"</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($products)): ?>
    <div class="empty-state">
        <div class="e-icon">🧺</div>
        <h3>No thrift finds yet</h3>
        <p>Be the first to list something pre-loved from your closet!</p>
        <a href="sell.php?source=thrift" class="btn-green">List Pre-Loved Item</a>
    </div>
    <?php else: ?>

    <!-- Discover Curated Collectives -->
    <?php if (!empty($vendor_groups)): ?>
    <div class="section-heading">Discover Curated Collectives</div>
    <div class="collectives-scroll">
        <?php $ringIdx = 0; foreach ($vendor_groups as $vid => $group):
            $itemCount = count($group['products']);
            $ringClass = ($ringIdx === 0) ? 'first-ring' : '';
            $ringIdx++;
        ?>
        <a href="vendor.php?id=<?php echo $vid; ?>" class="collective-card">
            <div class="collective-ring <?php echo $ringClass; ?>">
                <div class="collective-avatar">
                    <?php if (!empty($group['photo'])): ?>
                    <img src="<?php echo htmlspecialchars($group['photo']); ?>" alt="<?php echo htmlspecialchars($group['name']); ?>">
                    <?php else: ?>
                    <span class="avatar-init"><?php echo strtoupper(substr($group['name'], 0, 1)); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="collective-name"><?php echo htmlspecialchars($group['name']); ?> <ion-icon name="checkmark-circle" style="color:#27ae60;font-size:0.8rem;vertical-align:middle;"></ion-icon></div>
            <div class="collective-count"><?php echo $itemCount; ?>+ unique finds</div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Community Closet -->
    <div class="section-heading">Community Closet</div>
    <?php if (!empty($search)): ?>
    <p class="result-count"><?php echo count($community_products); ?> result<?php echo count($community_products) !== 1 ? 's' : ''; ?> for "<?php echo htmlspecialchars($search); ?>"</p>
    <?php endif; ?>

    <div class="products-grid">
        <?php foreach ($community_products as $p):
            if (isset($p['status']) && $p['status'] === 'sold' && !empty($p['sold_at_date'])) {
                if (time() - strtotime($p['sold_at_date']) > 86400) continue;
            }
            $images    = json_decode($p['image_paths']);
            $img       = $images[0] ?? 'https://placehold.co/300x375/F5F2E8/8A8279?text=No+Image';
            $price     = number_format($p['price_min'], 0);
            $title     = htmlspecialchars($p['title']);
            $cond      = htmlspecialchars($p['condition_tag'] ?? '');
            $url       = "product_details.php?id={$p['id']}&source=thrift";
            $isSold    = isset($p['status']) && $p['status'] === 'sold';
            $isBoosted = !empty($p['is_featured']) && !empty($p['boosted_until']) && strtotime($p['boosted_until']) > time();
        ?>
        <a href="<?php echo $url; ?>" class="p-card <?php echo $isBoosted ? 'featured' : ''; ?>">
            <div class="p-card-img">
                <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo $title; ?>" loading="lazy">
                <?php if ($isSold): ?>
                <span class="sold-badge">SOLD</span>
                <?php else: ?>
                <span class="price-badge"><?php echo '₹' . $price; ?></span>
                <?php endif; ?>
                <?php if ($isBoosted && !$isSold): ?>
                <span class="featured-badge">⚡ Featured</span>
                <?php endif; ?>
            </div>
            <div class="p-card-body">
                <div class="p-card-title"><?php echo $title; ?></div>
                <?php if ($cond): ?>
                <div class="p-card-cond"><?php echo ucfirst($cond); ?> condition</div>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>
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
    var active = wrap && wrap.querySelector('.cat-pill.active');
    if (active) setTimeout(function(){ active.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' }); }, 100);
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
<div id="successModal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:2000;backdrop-filter:blur(4px);">
    <div style="background:white;padding:2.5rem;border-radius:24px;text-align:center;max-width:340px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <div style="font-size:3.5rem;margin-bottom:1rem;">🌿</div>
        <h2 style="font-family:'Playfair Display',Georgia,serif;margin:0 0 8px;font-size:1.4rem;">Thrifted Successfully!</h2>
        <p style="color:#8A8279;margin:0 0 1.5rem;font-size:0.9rem;font-family:'Inter',sans-serif;">Your sustainable purchase is on its way.</p>
        <button onclick="document.getElementById('successModal').style.display='none'" style="background:#2D4739;color:white;border:none;padding:12px 28px;border-radius:50px;font-weight:700;cursor:pointer;font-size:0.95rem;width:100%;font-family:'Inter',sans-serif;">Keep Thrifting</button>
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
