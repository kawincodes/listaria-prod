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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $msg = "Invalid request. Please try again.";
        $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? 'seo';

        if ($action === 'seo') {
            $fields = [
                'seo_meta_title', 'seo_meta_description', 'seo_meta_keywords',
                'seo_og_title', 'seo_og_description', 'seo_og_image',
                'seo_google_analytics_id', 'seo_gtm_id', 'seo_search_console',
                'seo_canonical_base', 'seo_robots_default'
            ];
            foreach ($fields as $f) {
                saveSeoSetting($pdo, $f, trim($_POST[$f] ?? ''));
            }
            $msg = "SEO settings saved successfully.";

        } elseif ($action === 'robots') {
            saveSeoSetting($pdo, 'robots_txt_content', $_POST['robots_txt_content'] ?? '');
            $msg = "robots.txt content saved successfully.";

        } elseif ($action === 'generate_sitemap') {
            $baseUrl = rtrim(getSeoSetting($pdo, 'seo_canonical_base', 'https://listaria.in'), '/');

            $urls = [];
            $staticPages = [
                ['loc' => '/', 'priority' => '1.0', 'freq' => 'daily'],
                ['loc' => '/stores.php', 'priority' => '0.8', 'freq' => 'weekly'],
                ['loc' => '/blogs.php', 'priority' => '0.8', 'freq' => 'weekly'],
                ['loc' => '/thrift.php', 'priority' => '0.7', 'freq' => 'weekly'],
                ['loc' => '/founders.php', 'priority' => '0.6', 'freq' => 'monthly'],
                ['loc' => '/about.php', 'priority' => '0.6', 'freq' => 'monthly'],
                ['loc' => '/help_support.php', 'priority' => '0.5', 'freq' => 'monthly'],
                ['loc' => '/terms.php', 'priority' => '0.4', 'freq' => 'yearly'],
                ['loc' => '/privacy.php', 'priority' => '0.4', 'freq' => 'yearly'],
                ['loc' => '/refund.php', 'priority' => '0.4', 'freq' => 'yearly'],
            ];
            foreach ($staticPages as $p) {
                $urls[] = ['loc' => $baseUrl . $p['loc'], 'priority' => $p['priority'], 'freq' => $p['freq'], 'lastmod' => date('Y-m-d')];
            }

            $products = $pdo->query("SELECT id, updated_at FROM products WHERE approval_status = 'approved' AND is_published = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($products as $p) {
                $lastmod = $p['updated_at'] ? date('Y-m-d', strtotime($p['updated_at'])) : date('Y-m-d');
                $urls[] = ['loc' => $baseUrl . '/product_details.php?id=' . $p['id'], 'priority' => '0.9', 'freq' => 'weekly', 'lastmod' => $lastmod];
            }

            try {
                $blogs = $pdo->query("SELECT id, created_at FROM blogs WHERE status = 'published' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($blogs as $b) {
                    $lastmod = $b['created_at'] ? date('Y-m-d', strtotime($b['created_at'])) : date('Y-m-d');
                    $urls[] = ['loc' => $baseUrl . '/blog_details.php?id=' . $b['id'], 'priority' => '0.7', 'freq' => 'monthly', 'lastmod' => $lastmod];
                }
            } catch (Exception $e) {}

            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
            $xml .= '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
            $xml .= '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . "\n";
            $xml .= '        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";
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
            $msg = "Sitemap generated with " . count($urls) . " URLs and saved to sitemap.xml.";
        }
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$siteName = getSeoSetting($pdo, 'site_name', 'Listaria');
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
try { $blogCount = $pdo->query("SELECT COUNT(*) FROM blogs WHERE status='published'")->fetchColumn(); } catch(Exception $e) { $blogCount = 0; }
$staticCount = 10;

$darkMode = getSeoSetting($pdo, 'admin_dark_mode', '0') === '1';
?>
<!DOCTYPE html>
<html lang="en" <?php echo $darkMode ? 'data-theme="dark"' : ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO & Sitemap — <?php echo htmlspecialchars($siteName); ?> Admin</title>
    <link rel="stylesheet" href="assets/admin.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        .seo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        @media(max-width:768px){ .seo-grid { grid-template-columns: 1fr; } }
        .seo-card {
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: 14px;
            padding: 1.5rem;
        }
        .seo-card.full { grid-column: 1 / -1; }
        .seo-card h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary, #111);
            margin: 0 0 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .seo-card h3 ion-icon { color: #7c3aed; font-size: 1.2rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary, #6b7280);
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 0.6rem 0.85rem;
            border: 1.5px solid var(--border-color, #e5e7eb);
            border-radius: 8px;
            font-size: 0.9rem;
            background: var(--input-bg, #f9fafb);
            color: var(--text-primary, #111);
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: #7c3aed;
            background: var(--card-bg, #fff);
        }
        .form-group textarea { resize: vertical; min-height: 80px; font-family: monospace; }
        .char-hint { font-size: 0.72rem; color: var(--text-muted, #9ca3af); margin-top: 3px; }
        .char-hint.warn { color: #f59e0b; }
        .char-hint.over { color: #ef4444; }
        .save-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 0.65rem 1.4rem;
            background: linear-gradient(135deg, #6B21A8, #9333EA);
            color: #fff;
            border: none;
            border-radius: 9px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .save-btn:hover { opacity: 0.88; }
        .sitemap-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        @media(max-width:640px){ .sitemap-stats { grid-template-columns: repeat(2,1fr); } }
        .stat-chip {
            background: var(--input-bg, #f9fafb);
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
        }
        .stat-chip .stat-num {
            font-size: 1.6rem;
            font-weight: 800;
            color: #7c3aed;
            line-height: 1;
        }
        .stat-chip .stat-label {
            font-size: 0.72rem;
            color: var(--text-secondary, #6b7280);
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .sitemap-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .sitemap-link {
            font-size: 0.85rem;
            color: #7c3aed;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .sitemap-link:hover { text-decoration: underline; }
        .last-gen {
            font-size: 0.8rem;
            color: var(--text-muted, #9ca3af);
        }
        .msg-bar {
            padding: 0.8rem 1.2rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .msg-bar.success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .msg-bar.error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-size: 1.5rem; font-weight: 800; margin: 0; color: var(--text-primary,#111); }
        .page-header p { font-size: 0.9rem; color: var(--text-secondary,#6b7280); margin: 4px 0 0; }
        .tab-nav {
            display: flex;
            gap: 0.4rem;
            border-bottom: 2px solid var(--border-color, #e5e7eb);
            margin-bottom: 1.8rem;
        }
        .tab-btn {
            padding: 0.55rem 1.1rem;
            border: none;
            background: none;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-secondary, #6b7280);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.15s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .tab-btn.active { color: #7c3aed; border-bottom-color: #7c3aed; }
        .tab-btn:hover:not(.active) { color: var(--text-primary, #111); }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
        .preview-box {
            background: var(--input-bg, #f1f5f9);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 0.8rem;
        }
        .preview-box .preview-title {
            font-size: 1rem;
            color: #1a0dab;
            font-weight: 600;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .preview-box .preview-url { font-size: 0.78rem; color: #006621; margin-bottom: 4px; }
        .preview-box .preview-desc { font-size: 0.82rem; color: #545454; line-height: 1.5; }
        .preview-label { font-size: 0.72rem; color: var(--text-muted,#9ca3af); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; font-weight: 600; }
        .robots-textarea { min-height: 180px !important; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="page-header">
            <h1><ion-icon name="search-outline" style="vertical-align:middle;margin-right:8px;color:#7c3aed;"></ion-icon>SEO & Sitemap</h1>
            <p>Manage meta tags, search engine settings, robots.txt, and sitemap generation.</p>
        </div>

        <?php if ($msg): ?>
        <div class="msg-bar <?php echo $msgType; ?>">
            <ion-icon name="<?php echo $msgType==='success'?'checkmark-circle-outline':'alert-circle-outline'; ?>"></ion-icon>
            <?php echo htmlspecialchars($msg); ?>
        </div>
        <?php endif; ?>

        <div class="tab-nav">
            <button class="tab-btn active" onclick="switchTab('meta', this)">
                <ion-icon name="code-slash-outline"></ion-icon> Meta & Open Graph
            </button>
            <button class="tab-btn" onclick="switchTab('tracking', this)">
                <ion-icon name="bar-chart-outline"></ion-icon> Tracking & Verification
            </button>
            <button class="tab-btn" onclick="switchTab('sitemap', this)">
                <ion-icon name="map-outline"></ion-icon> Sitemap
            </button>
            <button class="tab-btn" onclick="switchTab('robots', this)">
                <ion-icon name="document-text-outline"></ion-icon> robots.txt
            </button>
        </div>

        <!-- META & OG TAB -->
        <div id="tab-meta" class="tab-panel active">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="seo">
                <div class="seo-grid">
                    <div class="seo-card">
                        <h3><ion-icon name="globe-outline"></ion-icon> Default Meta Tags</h3>
                        <div class="form-group">
                            <label>Site / Home Page Title</label>
                            <input type="text" name="seo_meta_title" id="meta_title" maxlength="70"
                                value="<?php echo htmlspecialchars($seoMetaTitle); ?>"
                                oninput="updateCount('meta_title','title_count',60,70)">
                            <div class="char-hint" id="title_count"></div>
                        </div>
                        <div class="form-group">
                            <label>Meta Description</label>
                            <textarea name="seo_meta_description" id="meta_desc" maxlength="165"
                                oninput="updateCount('meta_desc','desc_count',140,165)"><?php echo htmlspecialchars($seoMetaDesc); ?></textarea>
                            <div class="char-hint" id="desc_count"></div>
                        </div>
                        <div class="form-group">
                            <label>Meta Keywords <span style="font-weight:400;text-transform:none">(comma-separated)</span></label>
                            <input type="text" name="seo_meta_keywords" value="<?php echo htmlspecialchars($seoMetaKw); ?>" placeholder="luxury, fashion, marketplace, pre-owned">
                        </div>
                        <div class="form-group">
                            <label>Default Robots Directive</label>
                            <select name="seo_robots_default">
                                <?php foreach(['index, follow','noindex, follow','index, nofollow','noindex, nofollow'] as $opt): ?>
                                <option value="<?php echo $opt; ?>" <?php echo $seoRobots===$opt?'selected':''; ?>><?php echo $opt; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Canonical Base URL</label>
                            <input type="url" name="seo_canonical_base" value="<?php echo htmlspecialchars($seoCanonical); ?>" placeholder="https://listaria.in">
                        </div>
                    </div>

                    <div class="seo-card">
                        <h3><ion-icon name="share-social-outline"></ion-icon> Open Graph / Social</h3>
                        <div class="form-group">
                            <label>OG Title</label>
                            <input type="text" name="seo_og_title" value="<?php echo htmlspecialchars($seoOgTitle); ?>" placeholder="Same as meta title if blank">
                        </div>
                        <div class="form-group">
                            <label>OG Description</label>
                            <textarea name="seo_og_description" style="min-height:70px"><?php echo htmlspecialchars($seoOgDesc); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>OG Image URL <span style="font-weight:400;text-transform:none">(1200×630 recommended)</span></label>
                            <input type="url" name="seo_og_image" value="<?php echo htmlspecialchars($seoOgImage); ?>" placeholder="https://listaria.in/assets/og-image.jpg">
                        </div>
                        <div class="preview-label" style="margin-top:1rem">Search Preview</div>
                        <div class="preview-box">
                            <div class="preview-url" id="prev-url"><?php echo htmlspecialchars($seoCanonical ?: 'https://listaria.in'); ?>/</div>
                            <div class="preview-title" id="prev-title"><?php echo htmlspecialchars($seoMetaTitle ?: $siteName); ?></div>
                            <div class="preview-desc" id="prev-desc"><?php echo htmlspecialchars($seoMetaDesc ?: 'Your meta description will appear here.'); ?></div>
                        </div>
                    </div>
                </div>
                <div style="margin-top:1.5rem;">
                    <button type="submit" class="save-btn"><ion-icon name="save-outline"></ion-icon> Save SEO Settings</button>
                </div>
            </form>
        </div>

        <!-- TRACKING TAB -->
        <div id="tab-tracking" class="tab-panel">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="seo">
                <input type="hidden" name="seo_meta_title" value="<?php echo htmlspecialchars($seoMetaTitle); ?>">
                <input type="hidden" name="seo_meta_description" value="<?php echo htmlspecialchars($seoMetaDesc); ?>">
                <input type="hidden" name="seo_meta_keywords" value="<?php echo htmlspecialchars($seoMetaKw); ?>">
                <input type="hidden" name="seo_og_title" value="<?php echo htmlspecialchars($seoOgTitle); ?>">
                <input type="hidden" name="seo_og_description" value="<?php echo htmlspecialchars($seoOgDesc); ?>">
                <input type="hidden" name="seo_og_image" value="<?php echo htmlspecialchars($seoOgImage); ?>">
                <input type="hidden" name="seo_canonical_base" value="<?php echo htmlspecialchars($seoCanonical); ?>">
                <input type="hidden" name="seo_robots_default" value="<?php echo htmlspecialchars($seoRobots); ?>">
                <div class="seo-grid">
                    <div class="seo-card">
                        <h3><ion-icon name="logo-google"></ion-icon> Google Analytics</h3>
                        <div class="form-group">
                            <label>Measurement ID (GA4)</label>
                            <input type="text" name="seo_google_analytics_id" value="<?php echo htmlspecialchars($seoGaId); ?>" placeholder="G-XXXXXXXXXX">
                        </div>
                        <p style="font-size:0.82rem;color:var(--text-secondary,#6b7280);margin:0;">
                            The GA4 tracking snippet will be injected into every page's <code>&lt;head&gt;</code> automatically when this ID is set.
                        </p>
                    </div>
                    <div class="seo-card">
                        <h3><ion-icon name="analytics-outline"></ion-icon> Google Tag Manager</h3>
                        <div class="form-group">
                            <label>GTM Container ID</label>
                            <input type="text" name="seo_gtm_id" value="<?php echo htmlspecialchars($seoGtmId); ?>" placeholder="GTM-XXXXXXX">
                        </div>
                        <p style="font-size:0.82rem;color:var(--text-secondary,#6b7280);margin:0;">
                            Leave blank to disable. GTM will override GA4 if both are set — use only one.
                        </p>
                    </div>
                    <div class="seo-card full">
                        <h3><ion-icon name="shield-checkmark-outline"></ion-icon> Google Search Console Verification</h3>
                        <div class="form-group">
                            <label>Verification Meta Content Value</label>
                            <input type="text" name="seo_search_console" value="<?php echo htmlspecialchars($seoGsc); ?>" placeholder="abc123def456...">
                        </div>
                        <p style="font-size:0.82rem;color:var(--text-secondary,#6b7280);margin:0;">
                            Paste only the <code>content="…"</code> value from the Google Search Console HTML tag verification method.<br>
                            The tag <code>&lt;meta name="google-site-verification" content="…"&gt;</code> will be added to every page automatically.
                        </p>
                    </div>
                </div>
                <div style="margin-top:1.5rem;">
                    <button type="submit" class="save-btn"><ion-icon name="save-outline"></ion-icon> Save Tracking Settings</button>
                </div>
            </form>
        </div>

        <!-- SITEMAP TAB -->
        <div id="tab-sitemap" class="tab-panel">
            <div class="seo-card full" style="margin-bottom:1.5rem;">
                <h3><ion-icon name="map-outline"></ion-icon> Sitemap Generator</h3>
                <div class="sitemap-stats">
                    <div class="stat-chip">
                        <div class="stat-num"><?php echo $staticCount; ?></div>
                        <div class="stat-label">Static Pages</div>
                    </div>
                    <div class="stat-chip">
                        <div class="stat-num"><?php echo $productCount; ?></div>
                        <div class="stat-label">Products</div>
                    </div>
                    <div class="stat-chip">
                        <div class="stat-num"><?php echo $blogCount; ?></div>
                        <div class="stat-label">Blog Posts</div>
                    </div>
                    <div class="stat-chip">
                        <div class="stat-num" style="font-size:1.2rem;"><?php echo $sitemapCount ?: ($staticCount + $productCount + $blogCount); ?></div>
                        <div class="stat-label">Total URLs</div>
                    </div>
                </div>

                <?php if ($sitemapLast): ?>
                <p class="last-gen">
                    <ion-icon name="time-outline" style="vertical-align:middle;margin-right:4px;"></ion-icon>
                    Last generated: <strong><?php echo htmlspecialchars($sitemapLast); ?></strong>
                </p>
                <?php else: ?>
                <p class="last-gen">Sitemap has not been generated yet.</p>
                <?php endif; ?>

                <div class="sitemap-actions" style="margin-top:1rem;">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="generate_sitemap">
                        <button type="submit" class="save-btn">
                            <ion-icon name="refresh-outline"></ion-icon> Generate Sitemap Now
                        </button>
                    </form>
                    <?php if (file_exists(__DIR__ . '/sitemap.xml')): ?>
                    <a href="/sitemap.xml" target="_blank" class="sitemap-link">
                        <ion-icon name="open-outline"></ion-icon> View sitemap.xml
                    </a>
                    <?php endif; ?>
                </div>

                <div style="margin-top:1.5rem;padding-top:1.2rem;border-top:1px solid var(--border-color,#e5e7eb);">
                    <p style="font-size:0.85rem;color:var(--text-secondary,#6b7280);margin:0 0 0.5rem;">
                        <strong>What's included:</strong> Homepage, Stores, Blogs listing, Thrift, Founders, About, Help, Terms, Privacy, Refund Policy, all approved & published product pages, and all published blog posts.
                    </p>
                    <p style="font-size:0.85rem;color:var(--text-secondary,#6b7280);margin:0;">
                        <ion-icon name="information-circle-outline" style="vertical-align:middle;color:#7c3aed;"></ion-icon>
                        After generating, submit your sitemap URL to <a href="https://search.google.com/search-console" target="_blank" style="color:#7c3aed;">Google Search Console</a> as <code><?php echo htmlspecialchars(rtrim($seoCanonical,'/')); ?>/sitemap.xml</code>.
                    </p>
                </div>
            </div>
        </div>

        <!-- ROBOTS.TXT TAB -->
        <div id="tab-robots" class="tab-panel">
            <div class="seo-card full">
                <h3><ion-icon name="document-text-outline"></ion-icon> robots.txt Editor</h3>
                <p style="font-size:0.85rem;color:var(--text-secondary,#6b7280);margin:0 0 1rem;">
                    This content is served at <a href="/robots.txt" target="_blank" style="color:#7c3aed;">/robots.txt</a>.
                    Search engines read this file to understand crawl rules for your site.
                </p>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="robots">
                    <div class="form-group">
                        <textarea name="robots_txt_content" class="robots-textarea"><?php echo htmlspecialchars($robotsTxt); ?></textarea>
                    </div>
                    <button type="submit" class="save-btn"><ion-icon name="save-outline"></ion-icon> Save robots.txt</button>
                    <a href="/robots.txt" target="_blank" class="sitemap-link" style="margin-left:1rem;">
                        <ion-icon name="open-outline"></ion-icon> Preview live robots.txt
                    </a>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
function switchTab(id, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    btn.classList.add('active');
}

function updateCount(inputId, countId, warn, max) {
    const el = document.getElementById(inputId);
    const counter = document.getElementById(countId);
    const len = el.value.length;
    counter.textContent = len + ' / ' + max + ' characters';
    counter.className = 'char-hint' + (len > max ? ' over' : len > warn ? ' warn' : '');
}

(function() {
    updateCount('meta_title', 'title_count', 60, 70);
    updateCount('meta_desc', 'desc_count', 140, 165);

    const titleEl = document.getElementById('meta_title');
    const descEl  = document.getElementById('meta_desc');
    const prevTitle = document.getElementById('prev-title');
    const prevDesc  = document.getElementById('prev-desc');

    if (titleEl) titleEl.addEventListener('input', () => { prevTitle.textContent = titleEl.value || 'Your page title'; });
    if (descEl)  descEl.addEventListener('input',  () => { prevDesc.textContent  = descEl.value  || 'Your meta description will appear here.'; });
})();
</script>
</body>
</html>
