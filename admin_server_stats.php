<?php
require_once __DIR__ . '/includes/session.php';
require 'includes/db.php';
require_once 'includes/config.php';

$activePage = 'server_stats';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

function getServerUptime() {
    if (PHP_OS_FAMILY === 'Linux' && file_exists('/proc/uptime')) {
        $uptimeSec = (int) file_get_contents('/proc/uptime');
        $days  = intdiv($uptimeSec, 86400);
        $hours = intdiv($uptimeSec % 86400, 3600);
        $mins  = intdiv($uptimeSec % 3600, 60);
        return "{$days}d {$hours}h {$mins}m";
    }
    return 'N/A';
}

function formatBytes($bytes, $precision = 2) {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $pow = floor(log($bytes) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}

function getDirSize($dir) {
    $size = 0;
    if (!is_dir($dir)) return 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isFile()) $size += $file->getSize();
    }
    return $size;
}

$phpVersion = phpversion();
$serverOS = PHP_OS . ' (' . php_uname('r') . ')';
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'N/A';
$sqliteVersion = $pdo->query("SELECT sqlite_version()")->fetchColumn();
$loadedExtensions = get_loaded_extensions();
sort($loadedExtensions);

$diskTotal = @disk_total_space('/');
$diskFree = @disk_free_space('/');
$diskUsed = $diskTotal - $diskFree;
$diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0;

$memoryLimit = ini_get('memory_limit');
$memoryUsage = memory_get_usage(true);
$memoryPeak = memory_get_peak_usage(true);

$uptime = getServerUptime();

$maxUpload = ini_get('upload_max_filesize');
$maxPost = ini_get('post_max_size');
$maxExec = ini_get('max_execution_time');
$maxInput = ini_get('max_input_time');

$dbFile = __DIR__ . '/database.sqlite';
$dbSize = file_exists($dbFile) ? filesize($dbFile) : 0;

$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
$tableCount = count($tables);
$tableStats = [];
$totalRows = 0;
foreach ($tables as $table) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM \"$table\"")->fetchColumn();
        $tableStats[] = ['name' => $table, 'rows' => $count];
        $totalRows += $count;
    } catch (Exception $e) {
        $tableStats[] = ['name' => $table, 'rows' => '?'];
    }
}

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProducts = 0;
try { $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(); } catch (Exception $e) {}
$totalOrders = 0;
try { $totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(); } catch (Exception $e) {}

$uploadsDir = __DIR__ . '/uploads';
$uploadsSize = getDirSize($uploadsDir);

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'php_version' => $phpVersion,
        'server_os' => $serverOS,
        'server_software' => $serverSoftware,
        'sqlite_version' => $sqliteVersion,
        'disk_total' => formatBytes($diskTotal),
        'disk_used' => formatBytes($diskUsed),
        'disk_free' => formatBytes($diskFree),
        'disk_percent' => $diskPercent,
        'memory_usage' => formatBytes($memoryUsage),
        'memory_peak' => formatBytes($memoryPeak),
        'memory_limit' => $memoryLimit,
        'uptime' => $uptime,
        'db_size' => formatBytes($dbSize),
        'table_count' => $tableCount,
        'total_rows' => $totalRows,
        'total_users' => $totalUsers,
        'total_products' => $totalProducts,
        'total_orders' => $totalOrders,
        'uploads_size' => formatBytes($uploadsSize),
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Stats - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root {
            --primary: #6B21A8;
            --primary-dark: #581c87;
            --bg: #f8f9fa;
            --sidebar-bg: #1a1a1a;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); display: flex; color: #333; }

        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; padding: 0.5rem 0; color: white; z-index: 100; }
        .brand { font-size: 1.2rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem; text-decoration: none; }

        .main-content {
            margin-left: 260px;
            padding: 2.5rem 3rem;
            width: calc(100% - 260px);
            min-height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .header h1 { font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }
        .header-badge {
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 20px;
            background: rgba(107,33,168,0.1);
            color: var(--primary);
            font-weight: 600;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.2rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 1.5rem;
            border: 1px solid #f0f0f0;
            transition: all 0.2s;
        }
        .stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); transform: translateY(-2px); }

        .stat-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem;
        }
        .stat-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .stat-card-title {
            font-size: 0.82rem;
            font-weight: 600;
            color: #666;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f8f8f8;
            font-size: 0.85rem;
        }
        .stat-row:last-child { border-bottom: none; }
        .stat-label { color: #888; }
        .stat-value { font-weight: 600; color: #1a1a1a; }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #f0f0f0;
            border-radius: 4px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ext-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .ext-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 600;
            background: #f3e8ff;
            color: var(--primary);
        }

        .table-container {
            background: white;
            border-radius: 14px;
            border: 1px solid #f0f0f0;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        .table-container table {
            width: 100%;
            border-collapse: collapse;
        }
        .table-container th {
            text-align: left;
            padding: 0.8rem 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #999;
            background: #fafafa;
            border-bottom: 1px solid #f0f0f0;
        }
        .table-container td {
            padding: 0.6rem 1rem;
            font-size: 0.85rem;
            border-bottom: 1px solid #f8f8f8;
        }
        .table-container tr:hover td { background: #faf5ff; }

        .refresh-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            color: #999;
        }
        .refresh-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .icon-purple { background: rgba(107,33,168,0.1); color: var(--primary); }
        .icon-blue { background: rgba(59,130,246,0.1); color: #3b82f6; }
        .icon-green { background: rgba(34,197,94,0.1); color: #22c55e; }
        .icon-orange { background: rgba(249,115,22,0.1); color: #f97316; }
        .icon-red { background: rgba(239,68,68,0.1); color: #ef4444; }
        .icon-teal { background: rgba(20,184,166,0.1); color: #14b8a6; }

        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <div>
                <h1><ion-icon name="hardware-chip-outline" style="vertical-align:middle;margin-right:8px;color:var(--primary);"></ion-icon> Server Stats</h1>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="refresh-indicator">
                    <span class="refresh-dot"></span>
                    Auto-refresh: <span id="countdown">30</span>s
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon icon-purple"><ion-icon name="logo-php"></ion-icon></div>
                    <div class="stat-card-title">Server Environment</div>
                </div>
                <div class="stat-row">
                    <span class="stat-label">PHP Version</span>
                    <span class="stat-value" id="stat-php-version"><?php echo htmlspecialchars($phpVersion); ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Server OS</span>
                    <span class="stat-value" id="stat-server-os"><?php echo htmlspecialchars($serverOS); ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Web Server</span>
                    <span class="stat-value" id="stat-server-software"><?php echo htmlspecialchars($serverSoftware); ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">SQLite Version</span>
                    <span class="stat-value" id="stat-sqlite-version"><?php echo htmlspecialchars($sqliteVersion); ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Uptime</span>
                    <span class="stat-value" id="stat-uptime"><?php echo htmlspecialchars($uptime); ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon icon-blue"><ion-icon name="disc-outline"></ion-icon></div>
                    <div class="stat-card-title">Disk Usage</div>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Total</span>
                    <span class="stat-value" id="stat-disk-total"><?php echo formatBytes($diskTotal); ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Used</span>
                    <span class="stat-value" id="stat-disk-used"><?php echo formatBytes($diskUsed); ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Free</span>
                    <span class="stat-value" id="stat-disk-free"><?php echo formatBytes($diskFree); ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="disk-bar" style="width:<?php echo $diskPercent; ?>%;background:<?php echo $diskPercent > 90 ? '#ef4444' : ($diskPercent > 70 ? '#f97316' : '#22c55e'); ?>;"></div>
                </div>
                <div style="text-align:right;margin-top:4px;font-size:0.72rem;color:#999;" id="stat-disk-percent"><?php echo $diskPercent; ?>% used</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon icon-green"><ion-icon name="speedometer-outline"></ion-icon></div>
                    <div class="stat-card-title">Memory</div>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Current Usage</span>
                    <span class="stat-value" id="stat-mem-usage"><?php echo formatBytes($memoryUsage); ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Peak Usage</span>
                    <span class="stat-value" id="stat-mem-peak"><?php echo formatBytes($memoryPeak); ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Memory Limit</span>
                    <span class="stat-value" id="stat-mem-limit"><?php echo htmlspecialchars($memoryLimit); ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon icon-orange"><ion-icon name="cog-outline"></ion-icon></div>
                    <div class="stat-card-title">PHP Configuration</div>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Upload Max Size</span>
                    <span class="stat-value"><?php echo htmlspecialchars($maxUpload); ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Post Max Size</span>
                    <span class="stat-value"><?php echo htmlspecialchars($maxPost); ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Max Execution Time</span>
                    <span class="stat-value"><?php echo htmlspecialchars($maxExec); ?>s</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Max Input Time</span>
                    <span class="stat-value"><?php echo htmlspecialchars($maxInput); ?>s</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon icon-teal"><ion-icon name="server-outline"></ion-icon></div>
                    <div class="stat-card-title">Database</div>
                </div>
                <div class="stat-row">
                    <span class="stat-label">DB File Size</span>
                    <span class="stat-value" id="stat-db-size"><?php echo formatBytes($dbSize); ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Tables</span>
                    <span class="stat-value" id="stat-table-count"><?php echo $tableCount; ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Total Rows</span>
                    <span class="stat-value" id="stat-total-rows"><?php echo number_format($totalRows); ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon icon-red"><ion-icon name="bar-chart-outline"></ion-icon></div>
                    <div class="stat-card-title">Application</div>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Total Users</span>
                    <span class="stat-value" id="stat-total-users"><?php echo number_format($totalUsers); ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Total Products</span>
                    <span class="stat-value" id="stat-total-products"><?php echo number_format($totalProducts); ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Total Orders</span>
                    <span class="stat-value" id="stat-total-orders"><?php echo number_format($totalOrders); ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Uploads Folder</span>
                    <span class="stat-value" id="stat-uploads-size"><?php echo formatBytes($uploadsSize); ?></span>
                </div>
            </div>
        </div>

        <div class="section-title">
            <ion-icon name="server-outline" style="color:var(--primary);"></ion-icon> Database Tables
        </div>
        <div class="table-container" style="margin-bottom:2rem;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Table Name</th>
                        <th>Rows</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tableStats as $i => $ts): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td style="font-weight:500;"><?php echo htmlspecialchars($ts['name']); ?></td>
                        <td><?php echo is_numeric($ts['rows']) ? number_format($ts['rows']) : $ts['rows']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section-title">
            <ion-icon name="extension-puzzle-outline" style="color:var(--primary);"></ion-icon> Loaded PHP Extensions (<?php echo count($loadedExtensions); ?>)
        </div>
        <div class="stat-card" style="margin-bottom:2rem;">
            <div class="ext-grid">
                <?php foreach ($loadedExtensions as $ext): ?>
                    <span class="ext-badge"><?php echo htmlspecialchars($ext); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
        var countdown = 30;
        var countdownEl = document.getElementById('countdown');
        setInterval(function() {
            countdown--;
            if (countdownEl) countdownEl.textContent = countdown;
            if (countdown <= 0) {
                countdown = 30;
                refreshStats();
            }
        }, 1000);

        function refreshStats() {
            fetch('admin_server_stats.php?ajax=1')
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    updateEl('stat-php-version', d.php_version);
                    updateEl('stat-server-os', d.server_os);
                    updateEl('stat-server-software', d.server_software);
                    updateEl('stat-sqlite-version', d.sqlite_version);
                    updateEl('stat-uptime', d.uptime);
                    updateEl('stat-disk-total', d.disk_total);
                    updateEl('stat-disk-used', d.disk_used);
                    updateEl('stat-disk-free', d.disk_free);
                    updateEl('stat-disk-percent', d.disk_percent + '% used');
                    updateEl('stat-mem-usage', d.memory_usage);
                    updateEl('stat-mem-peak', d.memory_peak);
                    updateEl('stat-mem-limit', d.memory_limit);
                    updateEl('stat-db-size', d.db_size);
                    updateEl('stat-table-count', d.table_count);
                    updateEl('stat-total-rows', Number(d.total_rows).toLocaleString());
                    updateEl('stat-total-users', Number(d.total_users).toLocaleString());
                    updateEl('stat-total-products', Number(d.total_products).toLocaleString());
                    updateEl('stat-total-orders', Number(d.total_orders).toLocaleString());
                    updateEl('stat-uploads-size', d.uploads_size);
                    var bar = document.getElementById('disk-bar');
                    if (bar) {
                        bar.style.width = d.disk_percent + '%';
                        bar.style.background = d.disk_percent > 90 ? '#ef4444' : (d.disk_percent > 70 ? '#f97316' : '#22c55e');
                    }
                })
                .catch(function() {});
        }

        function updateEl(id, val) {
            var el = document.getElementById(id);
            if (el) el.textContent = val;
        }
    </script>
</body>
</html>
