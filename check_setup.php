<?php
// Quick cPanel deployment diagnostic - DELETE this file after checking
ob_start();

$checks = [];

// 1. PHP version
$phpVer = PHP_VERSION;
$phpOk  = version_compare($phpVer, '8.0', '>=');
$checks[] = [$phpOk, 'PHP Version', $phpVer . ($phpOk ? '' : ' (need 8.0+)')];

// 2. PDO SQLite
$sqliteOk = extension_loaded('pdo_sqlite');
$checks[] = [$sqliteOk, 'pdo_sqlite extension', $sqliteOk ? 'Enabled' : 'MISSING — go to cPanel > Select PHP Version > Extensions and enable pdo_sqlite'];

// 3. Database file
$dbFile = __DIR__ . '/database.sqlite';
$dbExists   = file_exists($dbFile);
$dbReadable = $dbExists && is_readable($dbFile);
$dbWritable = $dbExists && is_writable($dbFile);
$dbMsg = $dbExists
    ? ($dbWritable ? 'Found and writable' : 'Found but NOT writable — run: chmod 664 database.sqlite')
    : 'NOT FOUND at: ' . $dbFile . ' — upload database.sqlite to your public_html folder';
$checks[] = [$dbExists && $dbWritable, 'database.sqlite', $dbMsg];

// 4. Sessions directory
$sessDir   = __DIR__ . '/sessions';
$sessExists = is_dir($sessDir);
$sessWritable = $sessExists && is_writable($sessDir);
if (!$sessExists) @mkdir($sessDir, 0750, true);
$sessCheck = is_dir($sessDir) && is_writable($sessDir);
$checks[] = [$sessCheck, 'sessions/ directory', $sessCheck ? 'Exists and writable' : 'Cannot create — check folder permissions'];

// 5. Uploads directory
$uploadsDir = __DIR__ . '/uploads';
$uploadsOk  = is_dir($uploadsDir) && is_writable($uploadsDir);
$checks[] = [$uploadsOk, 'uploads/ directory', $uploadsOk ? 'Writable' : 'NOT writable — run: chmod 755 uploads/'];

// 6. Try DB connection
$dbConnOk = false;
$dbConnMsg = 'Skipped (file missing)';
if ($dbExists && $sqliteOk) {
    try {
        $pdo = new PDO('sqlite:' . $dbFile, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $row = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $dbConnMsg = 'Connected OK — ' . $row . ' user(s) found';
        $dbConnOk = true;
    } catch (Exception $e) {
        $dbConnMsg = 'FAILED: ' . $e->getMessage();
    }
}
$checks[] = [$dbConnOk, 'Database connection', $dbConnMsg];

// 7. Output buffering
$obOk = (int)ini_get('output_buffering') > 0 || ob_get_level() > 0;
$checks[] = [$obOk, 'Output buffering', $obOk ? 'Enabled (level ' . ob_get_level() . ')' : 'Off — add php_value output_buffering 4096 to .htaccess'];

// 8. Session cookie settings
$cookieHttpOnly = (bool)ini_get('session.cookie_httponly');
$checks[] = [$cookieHttpOnly, 'session.cookie_httponly', $cookieHttpOnly ? 'On' : 'Off — add php_flag session.cookie_httponly On to .htaccess'];

// 9. cURL
$curlOk = function_exists('curl_init');
$checks[] = [$curlOk, 'cURL extension', $curlOk ? 'Enabled' : 'MISSING — needed for CAPTCHA and payments'];

// 10. GD (for image handling)
$gdOk = extension_loaded('gd');
$checks[] = [$gdOk, 'GD image extension', $gdOk ? 'Enabled' : 'MISSING — needed for image uploads'];

$pass = count(array_filter($checks, fn($c) => $c[0]));
$total = count($checks);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Listaria — cPanel Setup Check</title>
<style>
body{font-family:Arial,sans-serif;max-width:750px;margin:40px auto;padding:0 20px;background:#f5f5f5;}
h1{color:#6B21A8;}
.card{background:#fff;border-radius:10px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.1);margin-bottom:20px;}
table{width:100%;border-collapse:collapse;}
tr{border-bottom:1px solid #eee;}
td{padding:10px 8px;font-size:14px;}
.ok{color:#16a34a;font-weight:bold;}
.fail{color:#dc2626;font-weight:bold;}
.label{font-weight:600;width:200px;}
.summary{font-size:1.2em;margin-bottom:8px;}
.warn{background:#fffbeb;border:1px solid #fbbf24;border-radius:8px;padding:12px;margin-top:16px;font-size:13px;}
</style>
</head>
<body>
<h1>Listaria — cPanel Setup Check</h1>
<div class="card">
    <div class="summary"><?= $pass ?>/<?= $total ?> checks passed <?= $pass === $total ? '✅ All good!' : '⚠️ Action required' ?></div>
    <table>
    <?php foreach ($checks as [$ok, $label, $detail]): ?>
    <tr>
        <td class="label"><?= htmlspecialchars($label) ?></td>
        <td class="<?= $ok ? 'ok' : 'fail' ?>"><?= $ok ? '✓ Pass' : '✗ Fail' ?></td>
        <td style="color:#555;font-size:13px"><?= htmlspecialchars($detail) ?></td>
    </tr>
    <?php endforeach; ?>
    </table>
    <div class="warn">⚠️ <strong>Delete this file after checking</strong> — it exposes server information.</div>
</div>
</body>
</html>
