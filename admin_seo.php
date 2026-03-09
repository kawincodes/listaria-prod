<?php
session_start();
require 'includes/db.php';

$activePage = 'seo';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

function getSeoSetting($pdo, $key, $default = '') {
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetchColumn();
    return $result !== false ? $result : $default;
}

function saveSeoSetting($pdo, $key, $value) {
    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value, updated_at)
        VALUES (?, ?, CURRENT_TIMESTAMP)
        ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value, updated_at = CURRENT_TIMESTAMP");
    $stmt->execute([$key, $value]);
}

$msg = '';
$msgType = 'success';
$openSection = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $msg = "Invalid request. Please try again.";
        $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save_meta') {
            $openSection = 'meta';
            foreach (['seo_meta_title','seo_meta_description','seo_meta_keywords','seo_robots_default','seo_canonical_base'] as $f) {
                saveSeoSetting($pdo, $f, trim($_POST[$f] ?? ''));
            }
            $msg = "Meta settings saved.";

        } elseif ($action === 'save_og') {
            $openSection = 'og';
            foreach (['seo_og_title','seo_og_description','seo_og_image'] as $f) {
                saveSeoSetting($pdo, $f, trim($_POST[$f] ?? ''));
            }
            $msg = "Open Graph settings saved.";

        } elseif ($action === 'save_tracking') {
            $openSection = 'tracking';
            foreach (['seo_google_analytics_id','seo_gtm_id','seo_search_console'] as $f) {
                saveSeoSetting($pdo, $f, trim($_POST[$f] ?? ''));
            }
            $msg = "Tracking & verification settings saved.";

        } elseif ($action === 'robots') {
            $openSection = 'robots';
            saveSeoSetting($pdo, 'robots_txt_content', $_POST['robots_txt_content'] ?? '');
            $msg = "robots.txt saved.";

        } elseif ($action === 'generate_sitemap') {
            $openSection = 'sitemap';
            $baseUrl = rtrim(getSeoSetting($pdo, 'seo_canonical_base', 'https://listaria.in'), '/');
            $urls = [];
            $staticPages = [
                ['loc' => '/',                'priority' => '1.0', 'freq' => 'daily'],
                ['loc' => '/stores.php',      'priority' => '0.8', 'freq' => 'weekly'],
                ['loc' => '/blogs.php',       'priority' => '0.8', 'freq' => 'weekly'],
                ['loc' => '/thrift.php',      'priority' => '0.7', 'freq' => 'weekly'],
                ['loc' => '/founders.php',    'priority' => '0.6', 'freq' => 'monthly'],
                ['loc' => '/about.php',       'priority' => '0.6', 'freq' => 'monthly'],
                ['loc' => '/help_support.php','priority' => '0.5', 'freq' => 'monthly'],
                ['loc' => '/terms.php',       'priority' => '0.4', 'freq' => 'yearly'],
                ['loc' => '/privacy.php',     'priority' => '0.4', 'freq' => 'yearly'],
                ['loc' => '/refund.php',      'priority' => '0.4', 'freq' => 'yearly'],
            ];
            foreach ($staticPages as $p) {
                $urls[] = ['loc' => $baseUrl . $p['loc'], 'priority' => $p['priority'], 'freq' => $p['freq'], 'lastmod' => date('Y-m-d')];
            }
            $products = $pdo->query("SELECT id, created_at FROM products WHERE approval_status='approved' AND is_published=1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($products as $p) {
                $urls[] = ['loc' => $baseUrl . '/product_details.php?id=' . $p['id'], 'priority' => '0.9', 'freq' => 'weekly', 'lastmod' => $p['created_at'] ? date('Y-m-d', strtotime($p['created_at'])) : date('Y-m-d')];
            }
            try {
                $blogs = $pdo->query("SELECT id, created_at FROM blogs ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($blogs as $b) {
                    $urls[] = ['loc' => $baseUrl . '/blog_details.php?id=' . $b['id'], 'priority' => '0.7', 'freq' => 'monthly', 'lastmod' => $b['created_at'] ? date('Y-m-d', strtotime($b['created_at'])) : date('Y-m-d')];
                }
            } catch (Exception $e) {}

            $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            foreach ($urls as $u) {
                $xml .= "  <url>\n";
                $xml .= "    <loc>" . htmlspecialchars($u['loc']) . "</loc>\n";
                $xml .= "    <lastmod>" . $u['lastmod'] . "</lastmod>\n";
                $xml .= "    <changefreq>" . $u['freq'] . "</changefreq>\n";
                $xml .= "    <priority>" . $u['priority'] . "</priority>\n";
                $xml .= "  </url>\n";
            }
            $xml .= '</urlset>';
            file_put_contents(__DIR__ . '/sitemap.xml', $xml);
            saveSeoSetting($pdo, 'sitemap_last_generated', date('Y-m-d H:i:s'));
            saveSeoSetting($pdo, 'sitemap_url_count', count($urls));
            $msg = "Sitemap generated — " . count($urls) . " URLs written to sitemap.xml.";
        }
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$siteName     = getSeoSetting($pdo, 'site_name', 'Listaria');
$seoMetaTitle = getSeoSetting($pdo, 'seo_meta_title', $siteName . ' — Luxury Marketplace');
$seoMetaDesc  = getSeoSetting($pdo, 'seo_meta_description');
$seoMetaKw    = getSeoSetting($pdo, 'seo_meta_keywords');
$seoOgTitle   = getSeoSetting($pdo, 'seo_og_title');
$seoOgDesc    = getSeoSetting($pdo, 'seo_og_description');
$seoOgImage   = getSeoSetting($pdo, 'seo_og_image');
$seoGaId      = getSeoSetting($pdo, 'seo_google_analytics_id');
$seoGtmId     = getSeoSetting($pdo, 'seo_gtm_id');
$seoGsc       = getSeoSetting($pdo, 'seo_search_console');
$seoCanonical = getSeoSetting($pdo, 'seo_canonical_base', 'https://listaria.in');
$seoRobots    = getSeoSetting($pdo, 'seo_robots_default', 'index, follow');
$robotsTxt    = getSeoSetting($pdo, 'robots_txt_content', "User-agent: *\nAllow: /\nDisallow: /admin_\nDisallow: /api/\nSitemap: https://listaria.in/sitemap.xml");
$sitemapLast  = getSeoSetting($pdo, 'sitemap_last_generated');
$sitemapCount = getSeoSetting($pdo, 'sitemap_url_count', '0');

$productCount = $pdo->query("SELECT COUNT(*) FROM products WHERE approval_status='approved' AND is_published=1")->fetchColumn();
try { $blogCount = $pdo->query("SELECT COUNT(*) FROM blogs")->fetchColumn(); } catch(Exception $e) { $blogCount = 0; }
$staticCount = 10;
$totalExpected = $staticCount + $productCount + $blogCount;

$darkMode = getSeoSetting($pdo, 'admin_dark_mode', '0') === '1';
$csrf = htmlspecialchars($_SESSION['csrf_token']);
?>
<!DOCTYPE html>
<html lang="en" <?php echo $darkMode ? 'data-theme="dark"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO & Sitemap — <?php echo htmlspecialchars($siteName); ?> Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        /* ── Base layout (matches all admin pages) ────── */
        :root {
            --primary: #6B21A8;
            --bg: #f8f9fa;
            --sidebar-bg: #1a1a1a;
            --card-bg: #fff;
            --border-color: #e5e7eb;
            --input-bg: #f9fafb;
            --text-primary: #111;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; display: flex; color: #333; }
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; padding: 0.5rem 0; color: white; z-index: 100; overflow-y: auto; }
        .brand { font-size: 1.2rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem; text-decoration: none; }
        .main-content { margin-left: 260px; padding: 2.5rem 3rem; width: calc(100% - 260px); min-height: 100vh; }
        @media(max-width:768px) { .sidebar { width: 0; } .main-content { margin-left: 0; width: 100%; padding: 1.5rem; } }

        /* ── Layout ───────────────────────────────────── */
        .seo-wrap { max-width: 860px; }

        /* ── Page header ──────────────────────────────── */
        .page-hd { margin-bottom: 2rem; }
        .page-hd h1 {
            font-size: 1.55rem; font-weight: 800; margin: 0;
            color: var(--text-primary, #111);
            display: flex; align-items: center; gap: 10px;
        }
        .page-hd h1 ion-icon { color: #7c3aed; font-size: 1.5rem; }
        .page-hd p { font-size: 0.875rem; color: var(--text-secondary, #6b7280); margin: 5px 0 0; }

        /* ── Alert banner ────────────────────────────── */
        .alert {
            display: flex; align-items: center; gap: 10px;
            padding: 0.85rem 1.2rem;
            border-radius: 12px;
            font-size: 0.88rem; font-weight: 500;
            margin-bottom: 1.5rem;
            animation: slideDown 0.25s ease;
        }
        @keyframes slideDown { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:none; } }
        .alert.success { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
        .alert.error   { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        .alert ion-icon { font-size:1.1rem; flex-shrink:0; }

        /* ── Accordion ───────────────────────────────── */
        .accordion { display: flex; flex-direction: column; gap: 0.75rem; }

        .acc-item {
            background: var(--card-bg, #fff);
            border: 1.5px solid var(--border-color, #e5e7eb);
            border-radius: 16px;
            overflow: hidden;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .acc-item.open {
            border-color: rgba(124,58,237,0.35);
            box-shadow: 0 4px 20px rgba(124,58,237,0.08);
        }

        .acc-head {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 1.1rem 1.4rem;
            cursor: pointer;
            user-select: none;
            -webkit-user-select: none;
        }
        .acc-head:hover { background: rgba(124,58,237,0.03); }

        .acc-icon-wrap {
            width: 40px; height: 40px; flex-shrink: 0;
            border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem;
            background: linear-gradient(135deg, rgba(107,33,168,0.12), rgba(147,51,234,0.08));
            color: #7c3aed;
            transition: background 0.2s;
        }
        .acc-item.open .acc-icon-wrap {
            background: linear-gradient(135deg, #6B21A8, #9333EA);
            color: #fff;
        }

        .acc-meta { flex: 1; min-width: 0; }
        .acc-title {
            font-size: 0.95rem; font-weight: 700;
            color: var(--text-primary, #111);
            margin: 0 0 2px;
        }
        .acc-subtitle {
            font-size: 0.78rem; color: var(--text-secondary, #6b7280);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        .acc-badges { display: flex; gap: 6px; flex-shrink: 0; margin-right: 4px; }
        .badge {
            font-size: 0.68rem; font-weight: 700;
            padding: 3px 9px; border-radius: 20px;
            letter-spacing: 0.3px;
        }
        .badge-ok  { background: #d1fae5; color: #065f46; }
        .badge-na  { background: var(--input-bg, #f3f4f6); color: var(--text-muted, #9ca3af); }
        .badge-warn{ background: #fef3c7; color: #92400e; }

        .acc-chevron {
            flex-shrink: 0;
            color: var(--text-muted, #9ca3af);
            font-size: 1.1rem;
            transition: transform 0.25s;
        }
        .acc-item.open .acc-chevron { transform: rotate(180deg); color: #7c3aed; }

        /* ── Accordion body ──────────────────────────── */
        .acc-body {
            display: none;
            padding: 0 1.4rem 1.4rem;
            border-top: 1px solid var(--border-color, #e5e7eb);
        }
        .acc-item.open .acc-body { display: block; }
        .acc-body-inner { padding-top: 1.3rem; }

        /* ── Two-column grid inside accordion ────────── */
        .field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media(max-width:640px) { .field-grid { grid-template-columns: 1fr; } }
        .col-full { grid-column: 1 / -1; }

        /* ── Form elements ───────────────────────────── */
        .fg { margin-bottom: 0; }
        .fg label {
            display: block;
            font-size: 0.73rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.6px;
            color: var(--text-secondary, #6b7280);
            margin-bottom: 5px;
        }
        .fg label span { font-weight: 400; text-transform: none; letter-spacing: 0; }
        .fg input, .fg textarea, .fg select {
            width: 100%; box-sizing: border-box;
            padding: 0.6rem 0.85rem;
            border: 1.5px solid var(--border-color, #e5e7eb);
            border-radius: 9px;
            font-size: 0.875rem;
            background: var(--input-bg, #f9fafb);
            color: var(--text-primary, #111);
            transition: border-color 0.15s, background 0.15s;
        }
        .fg input:focus, .fg textarea:focus, .fg select:focus {
            outline: none;
            border-color: #7c3aed;
            background: var(--card-bg, #fff);
        }
        .fg textarea { resize: vertical; min-height: 82px; font-family: monospace; font-size: 0.82rem; }
        .fg .hint { font-size: 0.71rem; color: var(--text-muted, #9ca3af); margin-top: 4px; }
        .fg .hint.warn { color: #f59e0b; }
        .fg .hint.over { color: #ef4444; }

        /* ── Section divider ─────────────────────────── */
        .sec-divider {
            font-size: 0.7rem; font-weight: 700; letter-spacing: 1px;
            text-transform: uppercase; color: var(--text-muted, #9ca3af);
            margin: 1.3rem 0 0.8rem;
            display: flex; align-items: center; gap: 8px;
        }
        .sec-divider::after { content:''; flex:1; height:1px; background:var(--border-color,#e5e7eb); }

        /* ── Action bar ──────────────────────────────── */
        .action-bar {
            display: flex; align-items: center; gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1.3rem;
            padding-top: 1.1rem;
            border-top: 1px solid var(--border-color, #e5e7eb);
        }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 0.6rem 1.35rem;
            background: linear-gradient(135deg, #6B21A8, #9333EA);
            color: #fff; border: none; border-radius: 10px;
            font-size: 0.875rem; font-weight: 700;
            cursor: pointer; transition: opacity 0.15s, transform 0.1s;
        }
        .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-primary:active { transform: none; opacity: 1; }
        .btn-primary ion-icon { font-size: 1rem; }

        .btn-generate {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 0.6rem 1.35rem;
            background: linear-gradient(135deg, #0f766e, #14b8a6);
            color: #fff; border: none; border-radius: 10px;
            font-size: 0.875rem; font-weight: 700;
            cursor: pointer; transition: opacity 0.15s, transform 0.1s;
        }
        .btn-generate:hover { opacity: 0.9; transform: translateY(-1px); }

        .ghost-link {
            font-size: 0.82rem; color: #7c3aed;
            text-decoration: none;
            display: inline-flex; align-items: center; gap: 5px;
        }
        .ghost-link:hover { text-decoration: underline; }

        /* ── Search preview ──────────────────────────── */
        .preview-wrap {
            background: var(--input-bg, #f8fafc);
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: 10px;
            padding: 1rem 1.1rem;
            margin-top: 0.5rem;
        }
        .preview-label-sm {
            font-size: 0.68rem; font-weight: 700; letter-spacing: 0.8px;
            text-transform: uppercase; color: var(--text-muted, #9ca3af);
            margin-bottom: 8px;
        }
        .prev-favicon {
            width: 16px; height: 16px;
            background: #e5e7eb; border-radius: 50%;
            display: inline-block; margin-right: 6px;
            vertical-align: middle;
        }
        .prev-url-row { font-size: 0.78rem; color: #3c4043; margin-bottom: 5px; }
        .prev-title-text {
            font-size: 1rem; font-weight: 600; color: #1a0dab;
            margin-bottom: 3px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .prev-desc-text { font-size: 0.8rem; color: #4d5156; line-height: 1.5; }

        /* ── Sitemap stats ───────────────────────────── */
        .stat-row {
            display: grid; grid-template-columns: repeat(4,1fr);
            gap: 0.75rem; margin-bottom: 1.3rem;
        }
        @media(max-width:560px) { .stat-row { grid-template-columns: repeat(2,1fr); } }
        .stat-box {
            background: var(--input-bg, #f9fafb);
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: 12px;
            padding: 0.9rem 0.6rem;
            text-align: center;
        }
        .stat-box .sn {
            font-size: 1.7rem; font-weight: 800;
            background: linear-gradient(135deg, #6B21A8, #9333EA);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }
        .stat-box .sl {
            font-size: 0.68rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.5px;
            color: var(--text-secondary, #6b7280);
            margin-top: 5px;
        }

        /* ── Info note ───────────────────────────────── */
        .info-note {
            display: flex; gap: 10px;
            font-size: 0.82rem; color: var(--text-secondary, #6b7280);
            background: var(--input-bg, #f9fafb);
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: 10px;
            padding: 0.85rem 1rem;
            margin-top: 1rem;
            line-height: 1.55;
        }
        .info-note ion-icon { color: #7c3aed; font-size: 1.1rem; flex-shrink: 0; margin-top: 1px; }
        .info-note a { color: #7c3aed; }

        /* ── Robots textarea override ────────────────── */
        .robots-ta { min-height: 180px !important; }

        /* ── Helper text ─────────────────────────────── */
        .helper-text { font-size: 0.8rem; color: var(--text-secondary,#6b7280); margin: 0.7rem 0 0; line-height: 1.5; }
        .helper-text code {
            background: var(--input-bg,#f3f4f6);
            padding: 1px 5px; border-radius: 4px;
            font-size: 0.78rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="seo-wrap">

            <!-- Page header -->
            <div class="page-hd">
                <h1><ion-icon name="search-outline"></ion-icon> SEO &amp; Sitemap</h1>
                <p>Configure meta tags, Open Graph, tracking codes, robots.txt, and your XML sitemap — all in one place.</p>
            </div>

            <?php if ($msg): ?>
            <div class="alert <?php echo $msgType; ?>">
                <ion-icon name="<?php echo $msgType==='success' ? 'checkmark-circle-outline' : 'alert-circle-outline'; ?>"></ion-icon>
                <?php echo htmlspecialchars($msg); ?>
            </div>
            <?php endif; ?>

            <div class="accordion">

                <!-- ① META TAGS -->
                <div class="acc-item <?php echo $openSection==='meta'||$openSection==='' ? 'open' : ''; ?>" id="acc-meta">
                    <div class="acc-head" onclick="toggleAcc('acc-meta')">
                        <div class="acc-icon-wrap"><ion-icon name="code-slash-outline"></ion-icon></div>
                        <div class="acc-meta">
                            <div class="acc-title">Meta Tags</div>
                            <div class="acc-subtitle"><?php echo $seoMetaTitle ? htmlspecialchars($seoMetaTitle) : 'Title, description, keywords, robots directive'; ?></div>
                        </div>
                        <div class="acc-badges">
                            <span class="badge <?php echo $seoMetaDesc ? 'badge-ok' : 'badge-warn'; ?>">
                                <?php echo $seoMetaDesc ? 'Description set' : 'No description'; ?>
                            </span>
                        </div>
                        <ion-icon name="chevron-down-outline" class="acc-chevron"></ion-icon>
                    </div>
                    <div class="acc-body">
                        <div class="acc-body-inner">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <input type="hidden" name="action" value="save_meta">
                                <div class="field-grid">
                                    <div class="fg col-full">
                                        <label>Default Page Title <span>(shown in browser tab &amp; search results)</span></label>
                                        <input type="text" name="seo_meta_title" id="meta_title" maxlength="70"
                                            value="<?php echo htmlspecialchars($seoMetaTitle); ?>"
                                            oninput="charCount('meta_title','tc',60,70)">
                                        <div class="hint" id="tc"></div>
                                    </div>
                                    <div class="fg col-full">
                                        <label>Meta Description <span>(aim for 120–160 characters)</span></label>
                                        <textarea name="seo_meta_description" id="meta_desc" maxlength="165"
                                            oninput="charCount('meta_desc','dc',130,165)"><?php echo htmlspecialchars($seoMetaDesc); ?></textarea>
                                        <div class="hint" id="dc"></div>
                                    </div>
                                    <div class="fg col-full">
                                        <label>Meta Keywords <span>(comma-separated, optional)</span></label>
                                        <input type="text" name="seo_meta_keywords"
                                            value="<?php echo htmlspecialchars($seoMetaKw); ?>"
                                            placeholder="luxury, fashion, marketplace, pre-owned">
                                    </div>
                                    <div class="fg">
                                        <label>Default Robots Directive</label>
                                        <select name="seo_robots_default">
                                            <?php foreach(['index, follow','noindex, follow','index, nofollow','noindex, nofollow'] as $opt): ?>
                                            <option value="<?php echo $opt; ?>" <?php echo $seoRobots===$opt?'selected':''; ?>><?php echo $opt; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="fg">
                                        <label>Canonical Base URL</label>
                                        <input type="url" name="seo_canonical_base"
                                            value="<?php echo htmlspecialchars($seoCanonical); ?>"
                                            placeholder="https://listaria.in">
                                    </div>
                                </div>

                                <div class="sec-divider">Search preview</div>
                                <div class="preview-wrap">
                                    <div class="preview-label-sm">Google Search Result</div>
                                    <div class="prev-url-row">
                                        <span class="prev-favicon"></span>
                                        <span id="prev-url"><?php echo htmlspecialchars(rtrim($seoCanonical,'/')); ?> › ...</span>
                                    </div>
                                    <div class="prev-title-text" id="prev-title"><?php echo htmlspecialchars($seoMetaTitle ?: $siteName); ?></div>
                                    <div class="prev-desc-text" id="prev-desc"><?php echo htmlspecialchars($seoMetaDesc ?: 'Your meta description will appear here once you fill it in.'); ?></div>
                                </div>

                                <div class="action-bar">
                                    <button type="submit" class="btn-primary"><ion-icon name="save-outline"></ion-icon> Save Meta Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ② OPEN GRAPH -->
                <div class="acc-item <?php echo $openSection==='og' ? 'open' : ''; ?>" id="acc-og">
                    <div class="acc-head" onclick="toggleAcc('acc-og')">
                        <div class="acc-icon-wrap"><ion-icon name="share-social-outline"></ion-icon></div>
                        <div class="acc-meta">
                            <div class="acc-title">Open Graph &amp; Social Cards</div>
                            <div class="acc-subtitle">Controls how links look when shared on WhatsApp, Facebook, Twitter, etc.</div>
                        </div>
                        <div class="acc-badges">
                            <span class="badge <?php echo $seoOgImage ? 'badge-ok' : 'badge-na'; ?>">
                                <?php echo $seoOgImage ? 'Image set' : 'No OG image'; ?>
                            </span>
                        </div>
                        <ion-icon name="chevron-down-outline" class="acc-chevron"></ion-icon>
                    </div>
                    <div class="acc-body">
                        <div class="acc-body-inner">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <input type="hidden" name="action" value="save_og">
                                <div class="field-grid">
                                    <div class="fg col-full">
                                        <label>OG Title <span>(defaults to meta title if blank)</span></label>
                                        <input type="text" name="seo_og_title" value="<?php echo htmlspecialchars($seoOgTitle); ?>" placeholder="Leave blank to inherit meta title">
                                    </div>
                                    <div class="fg col-full">
                                        <label>OG Description <span>(defaults to meta description if blank)</span></label>
                                        <textarea name="seo_og_description"><?php echo htmlspecialchars($seoOgDesc); ?></textarea>
                                    </div>
                                    <div class="fg col-full">
                                        <label>OG Image URL <span>(recommended: 1200 × 630 px)</span></label>
                                        <input type="url" name="seo_og_image"
                                            value="<?php echo htmlspecialchars($seoOgImage); ?>"
                                            placeholder="https://listaria.in/assets/og-image.jpg">
                                        <div class="hint">Used by WhatsApp, Facebook, Twitter, and Telegram link previews.</div>
                                    </div>
                                    <?php if ($seoOgImage): ?>
                                    <div class="fg col-full">
                                        <div class="preview-label-sm">Current OG Image</div>
                                        <img src="<?php echo htmlspecialchars($seoOgImage); ?>" alt="OG preview"
                                            style="max-width:100%;max-height:200px;border-radius:10px;object-fit:cover;border:1px solid var(--border-color,#e5e7eb);">
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="action-bar">
                                    <button type="submit" class="btn-primary"><ion-icon name="save-outline"></ion-icon> Save Open Graph Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ③ TRACKING & VERIFICATION -->
                <div class="acc-item <?php echo $openSection==='tracking' ? 'open' : ''; ?>" id="acc-tracking">
                    <div class="acc-head" onclick="toggleAcc('acc-tracking')">
                        <div class="acc-icon-wrap"><ion-icon name="bar-chart-outline"></ion-icon></div>
                        <div class="acc-meta">
                            <div class="acc-title">Tracking &amp; Verification</div>
                            <div class="acc-subtitle">Google Analytics, Tag Manager, and Search Console verification</div>
                        </div>
                        <div class="acc-badges">
                            <?php if ($seoGtmId): ?>
                                <span class="badge badge-ok">GTM active</span>
                            <?php elseif ($seoGaId): ?>
                                <span class="badge badge-ok">GA4 active</span>
                            <?php else: ?>
                                <span class="badge badge-na">No tracking</span>
                            <?php endif; ?>
                            <?php if ($seoGsc): ?><span class="badge badge-ok">GSC verified</span><?php endif; ?>
                        </div>
                        <ion-icon name="chevron-down-outline" class="acc-chevron"></ion-icon>
                    </div>
                    <div class="acc-body">
                        <div class="acc-body-inner">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <input type="hidden" name="action" value="save_tracking">
                                <div class="field-grid">
                                    <div class="fg">
                                        <label>Google Analytics 4 ID</label>
                                        <input type="text" name="seo_google_analytics_id"
                                            value="<?php echo htmlspecialchars($seoGaId); ?>"
                                            placeholder="G-XXXXXXXXXX">
                                        <div class="hint">Auto-injected into every page &lt;head&gt;. Leave blank to disable.</div>
                                    </div>
                                    <div class="fg">
                                        <label>Google Tag Manager ID</label>
                                        <input type="text" name="seo_gtm_id"
                                            value="<?php echo htmlspecialchars($seoGtmId); ?>"
                                            placeholder="GTM-XXXXXXX">
                                        <div class="hint">GTM overrides GA4 if both are set. Use only one.</div>
                                    </div>
                                    <div class="fg col-full">
                                        <label>Google Search Console — Verification Code</label>
                                        <input type="text" name="seo_search_console"
                                            value="<?php echo htmlspecialchars($seoGsc); ?>"
                                            placeholder="Paste only the content=&quot;…&quot; value from Google">
                                    </div>
                                </div>
                                <p class="helper-text">
                                    In Search Console, choose <strong>HTML tag</strong> verification and paste just the <code>content="…"</code> value above.<br>
                                    The full <code>&lt;meta name="google-site-verification" content="…"&gt;</code> tag will be added to every page automatically.
                                </p>
                                <div class="action-bar">
                                    <button type="submit" class="btn-primary"><ion-icon name="save-outline"></ion-icon> Save Tracking Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ④ SITEMAP -->
                <div class="acc-item <?php echo $openSection==='sitemap' ? 'open' : ''; ?>" id="acc-sitemap">
                    <div class="acc-head" onclick="toggleAcc('acc-sitemap')">
                        <div class="acc-icon-wrap"><ion-icon name="map-outline"></ion-icon></div>
                        <div class="acc-meta">
                            <div class="acc-title">XML Sitemap</div>
                            <div class="acc-subtitle">
                                <?php if ($sitemapLast): ?>
                                    Last generated <?php echo htmlspecialchars($sitemapLast); ?> — <?php echo $sitemapCount; ?> URLs
                                <?php else: ?>
                                    Not generated yet — <?php echo $totalExpected; ?> URLs ready to export
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="acc-badges">
                            <span class="badge <?php echo file_exists(__DIR__.'/sitemap.xml') ? 'badge-ok' : 'badge-warn'; ?>">
                                <?php echo file_exists(__DIR__.'/sitemap.xml') ? 'sitemap.xml exists' : 'Not generated'; ?>
                            </span>
                        </div>
                        <ion-icon name="chevron-down-outline" class="acc-chevron"></ion-icon>
                    </div>
                    <div class="acc-body">
                        <div class="acc-body-inner">
                            <div class="stat-row">
                                <div class="stat-box">
                                    <div class="sn"><?php echo $staticCount; ?></div>
                                    <div class="sl">Static pages</div>
                                </div>
                                <div class="stat-box">
                                    <div class="sn"><?php echo $productCount; ?></div>
                                    <div class="sl">Products</div>
                                </div>
                                <div class="stat-box">
                                    <div class="sn"><?php echo $blogCount; ?></div>
                                    <div class="sl">Blog posts</div>
                                </div>
                                <div class="stat-box">
                                    <div class="sn"><?php echo $sitemapCount ?: $totalExpected; ?></div>
                                    <div class="sl">Total URLs</div>
                                </div>
                            </div>

                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <input type="hidden" name="action" value="generate_sitemap">
                                <div class="action-bar" style="padding-top:0;border-top:none;margin-top:0;">
                                    <button type="submit" class="btn-generate">
                                        <ion-icon name="refresh-outline"></ion-icon> Generate sitemap.xml Now
                                    </button>
                                    <?php if (file_exists(__DIR__ . '/sitemap.xml')): ?>
                                    <a href="/sitemap.xml" target="_blank" class="ghost-link">
                                        <ion-icon name="open-outline"></ion-icon> View sitemap.xml
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </form>

                            <div class="info-note">
                                <ion-icon name="information-circle-outline"></ion-icon>
                                <div>
                                    After generating, submit your sitemap to
                                    <a href="https://search.google.com/search-console" target="_blank">Google Search Console</a>
                                    as <code><?php echo htmlspecialchars(rtrim($seoCanonical,'/')); ?>/sitemap.xml</code>.
                                    The sitemap includes all approved &amp; published products, published blog posts, and 10 static pages.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ⑤ ROBOTS.TXT -->
                <div class="acc-item <?php echo $openSection==='robots' ? 'open' : ''; ?>" id="acc-robots">
                    <div class="acc-head" onclick="toggleAcc('acc-robots')">
                        <div class="acc-icon-wrap"><ion-icon name="document-text-outline"></ion-icon></div>
                        <div class="acc-meta">
                            <div class="acc-title">robots.txt</div>
                            <div class="acc-subtitle">Controls which pages search engine crawlers are allowed to visit</div>
                        </div>
                        <div class="acc-badges">
                            <span class="badge badge-ok">Live at /robots.txt</span>
                        </div>
                        <ion-icon name="chevron-down-outline" class="acc-chevron"></ion-icon>
                    </div>
                    <div class="acc-body">
                        <div class="acc-body-inner">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                <input type="hidden" name="action" value="robots">
                                <div class="fg">
                                    <label>robots.txt Content</label>
                                    <textarea name="robots_txt_content" class="robots-ta"><?php echo htmlspecialchars($robotsTxt); ?></textarea>
                                </div>
                                <div class="action-bar">
                                    <button type="submit" class="btn-primary"><ion-icon name="save-outline"></ion-icon> Save robots.txt</button>
                                    <a href="/robots.txt" target="_blank" class="ghost-link">
                                        <ion-icon name="open-outline"></ion-icon> Preview live file
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div><!-- /accordion -->
        </div><!-- /seo-wrap -->
    </main>

<script>
function toggleAcc(id) {
    const item = document.getElementById(id);
    const isOpen = item.classList.contains('open');
    document.querySelectorAll('.acc-item').forEach(el => el.classList.remove('open'));
    if (!isOpen) item.classList.add('open');
}

function charCount(inputId, hintId, warn, max) {
    const el = document.getElementById(inputId);
    const hint = document.getElementById(hintId);
    if (!el || !hint) return;
    const len = el.value.length;
    hint.textContent = len + ' / ' + max + ' characters';
    hint.className = 'hint' + (len > max ? ' over' : len > warn ? ' warn' : '');
}

(function init() {
    charCount('meta_title', 'tc', 60, 70);
    charCount('meta_desc', 'dc', 130, 165);

    const titleEl = document.getElementById('meta_title');
    const descEl  = document.getElementById('meta_desc');
    const prevTitle = document.getElementById('prev-title');
    const prevDesc  = document.getElementById('prev-desc');

    if (titleEl && prevTitle) titleEl.addEventListener('input', () => { prevTitle.textContent = titleEl.value || 'Your page title'; });
    if (descEl && prevDesc)   descEl.addEventListener('input',  () => { prevDesc.textContent  = descEl.value  || 'Your meta description will appear here once you fill it in.'; });
})();
</script>
</body>
</html>
