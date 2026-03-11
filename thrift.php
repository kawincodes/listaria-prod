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
body { background: #f3ebdc !important; color: #111 !important; }
:root {
    --bg: #f3ebdc; --card: #FFFFFF; --green: #294631;
    --red: #d65252; --text: #111; --muted: #555;
    --border: #e0d6c6; --cream: #e6d8c4; --stone: #d4c2a8;
    --warm: #B8A992; --wood-dark: #5C3D2E; --wood-mid: #8B6242;
    --wood-light: #C9A87C;
}
*, *::before, *::after { box-sizing: border-box; }

.navbar {
    background: linear-gradient(180deg, #8B6B4A 0%, #7A5A3A 30%, #6B4930 60%, #5C3D2E 100%) !important;
    border-bottom: none !important;
    box-shadow: none !important;
    position: sticky !important; top: 0 !important; z-index: 1000 !important;
    margin: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding: 0.7rem max(2rem, calc((100% - 1344px) / 2 + 2rem)) !important;
}
.navbar::before {
    content: '';
    position: absolute; inset: 0;
    background:
        repeating-linear-gradient(90deg, transparent 0px, rgba(0,0,0,0.025) 1px, transparent 2px, transparent 7px),
        repeating-linear-gradient(90deg, transparent 0px, rgba(255,255,255,0.03) 3px, transparent 4px, transparent 12px);
    pointer-events: none; z-index: 0;
}
.navbar::after {
    content: '';
    position: absolute; inset: 0;
    background:
        radial-gradient(ellipse 150px 5px at 25% 25%, rgba(255,255,255,0.06), transparent),
        radial-gradient(ellipse 100px 3px at 65% 65%, rgba(0,0,0,0.05), transparent),
        radial-gradient(ellipse 250px 2px at 50% 45%, rgba(255,255,255,0.04), transparent);
    pointer-events: none; z-index: 0;
}
.navbar > * { position: relative; z-index: 1; }

.navbar .brand { color: #f5ede0 !important; text-shadow: 0 1px 3px rgba(0,0,0,0.3); }
.navbar .brand-location { color: #d4c2a8 !important; }

.navbar .search-container { position: relative; z-index: 1; }
.navbar .search-input {
    background: rgba(255,255,255,0.12) !important;
    border: 1px solid rgba(255,255,255,0.15) !important;
    color: #f5ede0 !important;
    backdrop-filter: blur(4px);
}
.navbar .search-input::placeholder { color: rgba(240,230,214,0.55) !important; }
.navbar .search-input:focus {
    background: rgba(255,255,255,0.18) !important;
    border-color: rgba(255,255,255,0.3) !important;
    box-shadow: 0 0 12px rgba(255,255,255,0.08) !important;
}
.navbar .search-filter-icon { color: #d4c2a8 !important; }

.navbar .nav-link { color: #e6d8c4 !important; transition: color 0.2s; font-size: 0.88rem; }
.navbar .nav-link:hover { color: #fff !important; }
.navbar .nav-link.profile-link { color: #f0e6d6 !important; }

.navbar .btn-signin {
    background: rgba(255,255,255,0.12) !important;
    color: #f5ede0 !important;
    border: 1px solid rgba(255,255,255,0.2) !important;
}
.navbar .btn-signin:hover {
    background: rgba(255,255,255,0.2) !important;
}

.navbar .btn-thrift {
    background: linear-gradient(135deg, #294631, #3a6148) !important;
    border: 1px solid rgba(255,255,255,0.15) !important;
    box-shadow: 0 3px 10px rgba(0,0,0,0.2) !important;
    color: #fff !important;
}
.navbar .btn-thrift:hover { background: linear-gradient(135deg, #345c40, #4a7a5e) !important; }

.navbar .btn-sell {
    background: linear-gradient(135deg, #f0e6d6, #e6d8c4) !important;
    color: #3d2b1f !important;
    border: none !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15) !important;
}
.navbar .btn-sell:hover { background: linear-gradient(135deg, #f5ede0, #ecdcc8) !important; }

.navbar .hamburger ion-icon { color: #f0e6d6 !important; }
.navbar .hamburger:hover { background: rgba(255,255,255,0.1) !important; border-radius: 8px; }

.announcement-bar {
    background: linear-gradient(90deg, #5C3D2E, #7A5A3A, #5C3D2E) !important;
}

.thrift-page { position: relative; overflow: hidden; margin-top: 0; padding-top: 0; }

.thrift-page::before {
    content: '';
    position: fixed; top: -40px; right: -20px; width: 320px; height: 400px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 260 360' fill='none' stroke='%23C5B9A5' stroke-width='0.7' opacity='0.3'%3E%3Cpath d='M200 30 C190 60, 175 100, 160 150 C145 200, 135 240, 125 300'/%3E%3Cpath d='M200 30 C210 55, 215 85, 205 115 C195 145, 175 130, 160 150'/%3E%3Cpath d='M200 30 C185 50, 170 80, 160 150'/%3E%3Cpath d='M160 150 C148 140, 130 145, 125 160' stroke-width='0.6'/%3E%3Cpath d='M160 150 C170 142, 180 148, 182 162' stroke-width='0.6'/%3E%3Cellipse cx='180' cy='220' rx='22' ry='30' transform='rotate(-12 180 220)' stroke-width='0.5'/%3E%3Cellipse cx='175' cy='220' rx='16' ry='22' transform='rotate(-12 175 220)' stroke-width='0.5'/%3E%3Cpath d='M178 250 L175 290' stroke-width='0.5'/%3E%3Cpath d='M100 50 C112 44, 126 40, 136 46 C146 52, 138 62, 126 60 C114 58, 104 56, 100 50Z' stroke-width='0.5'/%3E%3Cpath d='M75 90 C88 82, 102 78, 112 85 C122 92, 114 100, 102 98 C90 96, 78 96, 75 90Z' stroke-width='0.5'/%3E%3Cpath d='M60 130 C68 122, 78 120, 85 126 C92 132, 86 140, 78 138 C70 136, 62 135, 60 130Z' stroke-width='0.4'/%3E%3Ccircle cx='220' cy='300' r='6' stroke-width='0.4'/%3E%3Ccircle cx='220' cy='300' r='3' stroke-width='0.4'/%3E%3Cpath d='M220 294 L220 270' stroke-width='0.4'/%3E%3Cpath d='M220 270 C215 260, 225 255, 220 270' stroke-width='0.4'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-size: contain; background-position: top right;
    pointer-events: none; z-index: 0;
}
.thrift-page::after {
    content: '';
    position: fixed; bottom: -30px; left: -20px; width: 260px; height: 340px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 220 300' fill='none' stroke='%23C5B9A5' stroke-width='0.7' opacity='0.25'%3E%3Cpath d='M40 270 C45 240, 55 200, 65 160 C75 120, 80 90, 90 50'/%3E%3Cpath d='M90 50 C78 70, 62 85, 65 160'/%3E%3Cpath d='M90 50 C100 75, 105 100, 65 160'/%3E%3Cpath d='M65 160 C52 154, 35 158, 30 170' stroke-width='0.5'/%3E%3Cpath d='M65 160 C72 152, 82 150, 88 158' stroke-width='0.5'/%3E%3Ccircle cx='130' cy='230' r='18' stroke-width='0.5'/%3E%3Ccircle cx='130' cy='230' r='12' stroke-width='0.5'/%3E%3Ccircle cx='130' cy='230' r='5' stroke-width='0.5'/%3E%3Cpath d='M130 212 L130 175' stroke-width='0.5'/%3E%3Cpath d='M130 175 C125 165, 135 160, 130 175' stroke-width='0.5'/%3E%3Cpath d='M155 250 C168 245, 178 252, 182 264' stroke-width='0.4'/%3E%3Cpath d='M105 255 C92 260, 82 254, 78 242' stroke-width='0.4'/%3E%3Cpath d='M40 270 C35 264, 28 254, 30 244' stroke-width='0.4'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-size: contain; background-position: bottom left;
    pointer-events: none; z-index: 0;
}

.thrift-wrap { max-width: 1344px; margin: 0 auto; padding: 32px 2rem 100px; position: relative; z-index: 1; }

.cat-row {
    display: flex; align-items: center; gap: 0;
    margin: 0; width: 100%;
    background: linear-gradient(180deg, #A07850 0%, #8B6242 15%, #7A5438 40%, #6B4930 60%, #8B6242 80%, #A07850 100%);
    border-radius: 0; padding: 8px 2rem;
    box-shadow: 0 4px 16px rgba(92,61,46,0.25), inset 0 1px 0 rgba(255,255,255,0.15), inset 0 -1px 0 rgba(0,0,0,0.2);
    position: relative; overflow: hidden;
}
.cat-row::before {
    content: '';
    position: absolute; inset: 0;
    background: repeating-linear-gradient(
        90deg,
        transparent 0px,
        rgba(0,0,0,0.03) 1px,
        transparent 2px,
        transparent 8px
    ),
    repeating-linear-gradient(
        90deg,
        transparent 0px,
        rgba(255,255,255,0.04) 3px,
        transparent 4px,
        transparent 14px
    );
    pointer-events: none; z-index: 1;
}
.cat-row::after {
    content: '';
    position: absolute; inset: 0;
    background:
        radial-gradient(ellipse 120px 6px at 30% 20%, rgba(255,255,255,0.08), transparent),
        radial-gradient(ellipse 80px 4px at 70% 70%, rgba(0,0,0,0.06), transparent),
        radial-gradient(ellipse 200px 3px at 50% 40%, rgba(255,255,255,0.05), transparent);
    pointer-events: none; z-index: 1;
}
.cat-arrow {
    width: 42px; height: 42px; border-radius: 50%;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.1);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; flex-shrink: 0; color: #f0e6d6;
    transition: all 0.2s; position: relative; z-index: 2;
}
.cat-arrow:hover { background: rgba(255,255,255,0.2); }
.cat-scroll {
    display: flex; gap: 10px; overflow-x: auto; scroll-behavior: smooth;
    scrollbar-width: none; padding: 4px 12px;
    position: relative; z-index: 2; flex: 1;
    align-items: center;
}
.cat-scroll::-webkit-scrollbar { display: none; }

.cat-pill {
    flex-shrink: 0; padding: 10px 28px; font-size: 0.92rem; font-weight: 600;
    text-decoration: none; white-space: nowrap; cursor: pointer;
    font-family: 'Inter', Arial, sans-serif;
    border-radius: 999px;
    background: linear-gradient(145deg, #f0e6d6, #e6d8c4);
    color: #3d2b1f; border: none;
    box-shadow: 2px 2px 6px rgba(0,0,0,0.15), -1px -1px 3px rgba(255,255,255,0.1);
    transition: all 0.25s ease;
    display: inline-flex; align-items: center; gap: 10px;
}
.cat-pill:hover { transform: translateY(-1px); box-shadow: 2px 4px 10px rgba(0,0,0,0.2), -1px -1px 3px rgba(255,255,255,0.1); background: linear-gradient(145deg, #f5ede0, #ecdcc8); }
.cat-pill.active {
    background: var(--green); color: #FFFFFF;
    box-shadow: inset 2px 2px 6px rgba(0,0,0,0.25), 0 0 8px rgba(41,70,49,0.3);
}
.cat-icon { font-size: 1rem; display: inline-flex; align-items: center; }

.thrift-hero-text { margin: 0 0 24px; max-width: 800px; }
.thrift-hero-text h1 {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 4rem; font-weight: 700; margin: 0;
    color: var(--text); line-height: 1.1;
}
.thrift-hero-text .hero-sub {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 4rem; font-weight: 400; color: var(--text);
    display: block; margin-top: 0; line-height: 1.1;
}
.sub-tags { display: flex; flex-wrap: wrap; gap: 16px; margin-top: 24px; }
.sub-tag {
    padding: 10px 20px; border-radius: 8px; font-size: 0.94rem;
    font-weight: 500; font-family: 'Inter', sans-serif;
    background: var(--cream); border: none;
    color: var(--text);
    box-shadow: 2px 2px 5px rgba(0,0,0,0.05);
    display: inline-flex; align-items: center; gap: 8px;
}

.section-heading {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 2.25rem; font-weight: 700; color: var(--text);
    margin: 0 0 1.5rem; line-height: 1.2;
}

.collectives-scroll {
    display: flex; gap: 24px; overflow-x: auto;
    scrollbar-width: none; padding: 0 0 16px;
}
.collectives-scroll::-webkit-scrollbar { display: none; }
.collective-card {
    flex-shrink: 0; width: 318px; text-align: center;
    text-decoration: none; color: var(--text);
    background: #FFFFFF; border-radius: 16px;
    padding: 32px 16px 24px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.03);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.collective-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.06); }
.collective-ring {
    width: 120px; height: 120px; border-radius: 50%;
    background: conic-gradient(from 45deg, #A67B5B, #D4A574, #8B6942, #C9A87C, #A67B5B);
    padding: 4px; margin: 0 auto 16px; position: relative;
}
.collective-ring.first-ring {
    background: conic-gradient(from 45deg, #7B3F2E, #C0533A, #A67B5B, #D4A574, #7B3F2E);
}
.collective-avatar {
    width: 100%; height: 100%; border-radius: 50%;
    border: 4px solid #FFFFFF;
    overflow: hidden; background: #f0f0f0;
    display: flex; align-items: center; justify-content: center;
}
.collective-avatar img { width: 100%; height: 100%; object-fit: cover; }
.collective-avatar .avatar-init {
    font-family: 'Playfair Display', serif; font-size: 2.2rem;
    font-weight: 700; color: var(--green);
}
.collective-name {
    font-family: 'Inter', sans-serif; font-weight: 600;
    font-size: 1rem; margin: 0 0 4px;
    display: inline-flex; align-items: center; gap: 6px;
}
.collective-count { font-size: 0.88rem; color: var(--muted); font-family: 'Inter', sans-serif; }

.products-grid {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px;
}
@media(min-width: 768px) { .products-grid { grid-template-columns: repeat(3, 1fr); gap: 28px; } }
@media(min-width: 1024px) { .products-grid { grid-template-columns: repeat(4, 1fr); gap: 32px; } }

.p-card {
    background: #FFFFFF; border-radius: 16px;
    overflow: hidden;
    text-decoration: none; color: var(--text);
    border: none;
    box-shadow: 0 8px 24px rgba(0,0,0,0.03);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    padding: 16px;
    display: flex; flex-direction: column;
    position: relative;
}
.p-card:hover { transform: translateY(-6px); box-shadow: 0 16px 40px rgba(0,0,0,0.08); }
.p-card.featured { box-shadow: 0 8px 24px rgba(147,51,234,0.08); }
.p-card.featured:hover { box-shadow: 0 16px 40px rgba(147,51,234,0.12); }

.p-card-img { position: relative; aspect-ratio: 1/1; overflow: hidden; border-radius: 8px; background: #f0f0f0; }
.p-card-img img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.5s ease; border-radius: 8px; }
.p-card:hover .p-card-img img { transform: scale(1.05); }

.price-badge {
    position: absolute; top: 0; right: 12px;
    background: linear-gradient(180deg, #c0392b 0%, #d65252 100%);
    color: #FFFFFF;
    padding: 6px 14px 8px;
    font-size: 0.9rem; font-weight: 800; z-index: 3;
    font-family: 'Inter', sans-serif;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 12px rgba(214,82,82,0.35);
    letter-spacing: 0.3px;
}
.sold-badge {
    position: absolute; top: 0; left: 12px;
    background: linear-gradient(180deg, #111, #333);
    color: #FFFFFF;
    padding: 6px 14px 8px; border-radius: 0 0 8px 8px;
    font-size: 0.75rem; font-weight: 700; z-index: 3;
    letter-spacing: 1px; font-family: 'Inter', sans-serif;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.featured-badge {
    position: absolute; top: 0; left: 12px;
    background: linear-gradient(180deg, #581c87, #9333ea);
    color: #FFFFFF;
    padding: 6px 14px 8px; border-radius: 0 0 8px 8px;
    font-size: 0.72rem; font-weight: 700; z-index: 3;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 4px 12px rgba(147,51,234,0.3);
    letter-spacing: 0.5px;
}

.p-card-body { padding: 12px 0 0; }
.p-card-title {
    font-family: 'Inter', sans-serif; font-weight: 600;
    font-size: 1.05rem; line-height: 1.3;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    margin: 0 0 4px; color: var(--text);
}
.p-card-cond { font-size: 0.88rem; color: var(--muted); font-family: 'Inter', sans-serif; margin: 0; }

.btn-claim {
    display: block; width: 100%; margin-top: 10px;
    padding: 11px 16px; text-align: center;
    background: linear-gradient(145deg, #2a4a34, #1e3627);
    color: #FFFFFF;
    font-family: 'Inter', sans-serif; font-weight: 600;
    font-size: 0.82rem; letter-spacing: 1.8px; text-transform: uppercase;
    border: none; border-radius: 10px; cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 2px 8px rgba(41,70,49,0.2);
}
.p-card:hover .btn-claim { background: linear-gradient(145deg, #345c40, #264230); box-shadow: 0 4px 12px rgba(41,70,49,0.3); }

.search-wrap { position: relative; margin-bottom: 0.5rem; }
.search-wrap input {
    width: 100%; padding: 14px 20px 14px 48px;
    border-radius: 50px; border: 1.5px solid var(--border);
    background: var(--card); font-size: 0.95rem;
    font-family: 'Inter', sans-serif; color: var(--text);
    outline: none; box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: border-color 0.2s, box-shadow 0.2s;
}
.search-wrap input:focus { border-color: var(--green); box-shadow: 0 2px 12px rgba(41,70,49,0.1); }
.search-wrap input::placeholder { color: #999; }
.search-wrap .search-ico { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #999; font-size: 1.2rem; pointer-events: none; }
.search-wrap .search-clear { position: absolute; right: 18px; top: 50%; transform: translateY(-50%); color: var(--muted); cursor: pointer; font-size: 0.85rem; font-weight: 600; text-decoration: none; font-family: 'Inter', sans-serif; }

.empty-state { text-align: center; padding: 5rem 2rem; }
.empty-state .e-icon { font-size: 3.5rem; margin-bottom: 1rem; }
.empty-state h3 { font-family: 'Playfair Display', serif; font-size: 1.5rem; margin: 0 0 8px; }
.empty-state p { color: var(--muted); font-size: 0.95rem; font-family: 'Inter', sans-serif; margin: 0 0 1.5rem; }
.btn-green { display: inline-block; background: var(--green); color: white; padding: 12px 28px; border-radius: 50px; font-weight: 700; font-size: 0.9rem; text-decoration: none; font-family: 'Inter', sans-serif; transition: background 0.2s; }
.btn-green:hover { background: #1f3328; }

.result-count { color: var(--muted); font-size: 0.9rem; font-family: 'Inter', sans-serif; margin: -0.5rem 0 1.5rem; }

.section-gap { margin-top: 3rem; }


@media(max-width: 768px) {
    .mobile-bottom-nav { display: block; }
    .thrift-wrap { padding: 24px 16px 100px; }
    .navbar { padding: 0.7rem 12px !important; gap: 8px !important; }
    .cat-row { padding: 6px 8px; }
    .cat-arrow { width: 32px; height: 32px; }
    .thrift-hero-text h1 { font-size: 2.5rem; }
    .thrift-hero-text .hero-sub { font-size: 2.5rem; }
    .section-heading { font-size: 1.75rem; }
    .cat-pill { padding: 9px 18px; font-size: 0.85rem; }
    .collective-card { width: 260px; padding: 24px 12px 20px; }
    .collective-ring { width: 100px; height: 100px; }
    .products-grid { gap: 16px; }
    .p-card { padding: 12px; }
    .price-badge { padding: 6px 10px 8px; font-size: 0.82rem; right: 10px; }
    .sold-badge, .featured-badge { padding: 6px 10px 8px; font-size: 0.7rem; left: 10px; }
}
@media(max-width: 480px) {
    .thrift-hero-text h1 { font-size: 2rem; }
    .thrift-hero-text .hero-sub { font-size: 2rem; }
    .section-heading { font-size: 1.4rem; }
    .cat-pill { padding: 8px 16px; font-size: 0.8rem; gap: 6px; }
    .cat-row { padding: 4px 8px; gap: 0; }
    .cat-arrow { width: 32px; height: 32px; }
    .cat-scroll { gap: 6px; padding: 2px 4px; }
    .collective-card { width: 220px; }
    .collective-ring { width: 80px; height: 80px; }
    .products-grid { gap: 12px; }
    .p-card { padding: 10px; border-radius: 14px; }
    .price-badge { padding: 5px 8px 6px; font-size: 0.75rem; right: 8px; }
    .sold-badge, .featured-badge { padding: 5px 8px 6px; font-size: 0.65rem; left: 8px; }
    .btn-claim { padding: 9px 12px; font-size: 0.75rem; letter-spacing: 1.2px; }
    .sub-tags { gap: 10px; }
    .sub-tag { padding: 8px 14px; font-size: 0.82rem; }
}
</style>

<div class="thrift-page">

    <!-- Category Pills (Wood Bar) — full width, outside thrift-wrap -->
    <div class="cat-row">
        <div class="cat-arrow" id="catLeft"><ion-icon name="chevron-back-outline"></ion-icon></div>
        <div class="cat-scroll" id="catScroll">
            <?php
            $cats = ['All', 'Tops', 'Bottoms', 'Jackets', 'Shoes', 'Bags', 'Accessories'];
            $catSvg = [
                'All'         => '',
                'Tops'        => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L2 8l4 2v12h12V10l4-2-4-6"/><path d="M12 2c-2 0-3 2-3 2h6s-1-2-3-2z"/></svg>',
                'Bottoms'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h12v8l-3 12h-2l-1-8-1 8H9L6 10z"/></svg>',
                'Jackets'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L1 8v6l3 1v7h16v-7l3-1V8l-5-6"/><path d="M12 2c-2 0-3 2-3 2h6s-1-2-3-2z"/></svg>',
                'Shoes'       => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 18h20v2H2zM4 18v-4l3-4 5 2 8-4 2 2v8"/></svg>',
                'Bags'        => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="8" width="16" height="14" rx="2"/><path d="M8 8V6a4 4 0 018 0v2"/></svg>',
                'Accessories' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="8"/><path d="M12 4v1M12 19v1"/></svg>'
            ];
            foreach ($cats as $cat):
                $active = ($category === $cat) ? 'active' : '';
                $icon   = $catSvg[$cat] ?? '';
                $href   = 'thrift.php?category=' . urlencode($cat);
            ?>
            <a href="<?php echo $href; ?>" class="cat-pill <?php echo $active; ?>"><?php if ($icon): ?><span class="cat-icon"><?php echo $icon; ?></span><?php endif; ?><?php echo $cat; ?></a>
            <?php endforeach; ?>
        </div>
        <div class="cat-arrow" id="catRight"><ion-icon name="chevron-forward-outline"></ion-icon></div>
    </div>

<div class="thrift-wrap">

    <form method="GET" action="thrift.php" class="search-wrap" style="margin-bottom:1.5rem;">
        <?php if ($category !== 'All'): ?>
        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
        <?php endif; ?>
        <ion-icon name="search-outline" class="search-ico"></ion-icon>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search thrift finds...">
        <?php if (!empty($search)): ?>
        <a href="thrift.php?category=<?php echo urlencode($category); ?>" class="search-clear">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Hero Text -->
    <div class="thrift-hero-text">
        <h1>Thrift+</h1>
        <span class="hero-sub">Re-Cycled, Sustainable Style</span>
        <div class="sub-tags">
            <span class="sub-tag">Apparel <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L2 8l4 2v12h12V10l4-2-4-6"/></svg></span>
            <span class="sub-tag">Accessories <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="8"/></svg></span>
            <span class="sub-tag">Vintage Finds <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M7 20h10M12 16v4"/></svg></span>
            <span class="sub-tag">Condition: Used</span>
            <?php if (!empty($search)): ?>
            <span class="sub-tag" style="background:#FEF3C7;color:#92400E;">Search: "<?php echo htmlspecialchars($search); ?>"</span>
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
    <div class="section-gap">
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
                <div class="collective-name"><?php echo htmlspecialchars($group['name']); ?> <ion-icon name="checkmark-circle" style="color:#27ae60;font-size:0.9rem;vertical-align:middle;"></ion-icon></div>
                <div class="collective-count"><?php echo $itemCount; ?>+ unique finds</div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Community Closet -->
    <div class="section-gap">
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
                $img       = $images[0] ?? 'https://placehold.co/300x375/f3ebdc/999?text=No+Image';
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
                    <?php if ($isBoosted && !$isSold): ?>
                    <span class="featured-badge">Featured</span>
                    <?php endif; ?>
                    <?php if ($isSold): ?>
                    <span class="sold-badge">SOLD</span>
                    <?php else: ?>
                    <span class="price-badge">₹<?php echo $price; ?></span>
                    <?php endif; ?>
                </div>
                <div class="p-card-body">
                    <div class="p-card-title"><?php echo $title; ?></div>
                    <?php if ($cond): ?>
                    <div class="p-card-cond"><?php echo ucfirst($cond); ?></div>
                    <?php endif; ?>
                    <?php if (!$isSold): ?>
                    <div class="btn-claim">Claim Piece</div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php endif; ?>
</div>
</div>

<!-- Mobile Bottom Nav -->
<nav class="mobile-bottom-nav">
    <div class="mobile-bottom-nav-inner">
        <a href="index.php" class="mob-nav-item">
            <ion-icon name="home-outline"></ion-icon>
            <span>Home</span>
        </a>
        <a href="thrift.php" class="mob-nav-item active">
            <ion-icon name="leaf-outline"></ion-icon>
            <span>Thrift+</span>
        </a>
        <a href="sell.php?source=thrift" class="mob-nav-item">
            <ion-icon name="add-circle-outline"></ion-icon>
            <span>Sell</span>
        </a>
        <a href="#search" class="mob-nav-item" id="mobSearchBtn">
            <ion-icon name="search-outline"></ion-icon>
            <span>Search</span>
        </a>
        <a href="<?php echo isset($_SESSION['user_id']) ? 'profile.php' : 'login.php'; ?>" class="mob-nav-item">
            <ion-icon name="person-outline"></ion-icon>
            <span>Profile</span>
        </a>
    </div>
</nav>

<!-- Category scroll JS -->
<script>
(function(){
    var wrap  = document.getElementById('catScroll');
    var left  = document.getElementById('catLeft');
    var right = document.getElementById('catRight');
    if (wrap && left && right) {
        left.addEventListener('click',  function(){ wrap.scrollBy({ left: -200, behavior: 'smooth' }); });
        right.addEventListener('click', function(){ wrap.scrollBy({ left: 200,  behavior: 'smooth' }); });
    }
    var active = wrap && wrap.querySelector('.cat-pill.active');
    if (active) setTimeout(function(){ active.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' }); }, 100);

    var mobSearch = document.getElementById('mobSearchBtn');
    if (mobSearch) {
        mobSearch.addEventListener('click', function(e) {
            e.preventDefault();
            var searchWrap = document.querySelector('.search-wrap');
            var searchInput = searchWrap ? searchWrap.querySelector('input[name="search"]') : null;
            if (searchInput) {
                searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(function(){ searchInput.focus(); }, 400);
            }
        });
    }
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
        <p style="color:#555;margin:0 0 1.5rem;font-size:0.9rem;font-family:'Inter',sans-serif;">Your sustainable purchase is on its way.</p>
        <button onclick="document.getElementById('successModal').style.display='none'" style="background:#294631;color:white;border:none;padding:12px 28px;border-radius:50px;font-weight:700;cursor:pointer;font-size:0.95rem;width:100%;font-family:'Inter',sans-serif;">Keep Thrifting</button>
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
