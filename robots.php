<?php
require 'includes/db.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'robots_txt_content'");
$stmt->execute();
$content = $stmt->fetchColumn();

if ($content === false || trim($content) === '') {
    $base = 'https://listaria.in';
    $baseStmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'seo_canonical_base'");
    $baseStmt->execute();
    $baseVal = $baseStmt->fetchColumn();
    if ($baseVal) $base = rtrim($baseVal, '/');

    echo "User-agent: *\n";
    echo "Allow: /\n";
    echo "Disallow: /admin_\n";
    echo "Disallow: /api/\n";
    echo "Sitemap: $base/sitemap.xml\n";
} else {
    echo $content;
}
