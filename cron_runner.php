<?php
/**
 * cron_runner.php — CLI entry point for scheduled cron jobs.
 * Usage: php /path/to/cron_runner.php <job_key>
 * Example: php /home/listaria/public_html/cron_runner.php expire_boosts
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

$jobKey = $argv[1] ?? '';
if (!$jobKey) {
    echo "Usage: php cron_runner.php <job_key>\n";
    echo "Available jobs: expire_boosts, close_old_requests, cleanup_login_logs, cleanup_sent_emails, cleanup_activity_logs\n";
    exit(1);
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Ensure cron_logs table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS cron_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        job_key TEXT NOT NULL,
        status TEXT DEFAULT 'success',
        output TEXT,
        duration_ms INTEGER DEFAULT 0,
        ran_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {}

$start  = microtime(true);
$output = '';
$status = 'success';

echo "[" . date('Y-m-d H:i:s') . "] Running job: $jobKey\n";

try {
    if ($jobKey === 'expire_boosts') {
        $upd = $pdo->exec("UPDATE products SET is_featured = 0 WHERE boosted_until IS NOT NULL AND boosted_until < datetime('now') AND is_featured = 1");
        $output = "Expired $upd boosted product(s).";

    } elseif ($jobKey === 'close_old_requests') {
        $days = 60;
        $upd  = $pdo->prepare("UPDATE product_requests SET status = 'closed' WHERE status = 'open' AND created_at < datetime('now', ? || ' days')");
        $upd->execute(["-$days"]);
        $output = "Closed " . $upd->rowCount() . " stale product request(s) older than $days days.";

    } elseif ($jobKey === 'cleanup_login_logs') {
        $days = 90;
        $del  = $pdo->prepare("DELETE FROM login_logs WHERE created_at < datetime('now', ? || ' days')");
        $del->execute(["-$days"]);
        $output = "Deleted " . $del->rowCount() . " login log entries older than $days days.";

    } elseif ($jobKey === 'cleanup_sent_emails') {
        $days = 60;
        $del  = $pdo->prepare("DELETE FROM sent_emails WHERE created_at < datetime('now', ? || ' days')");
        $del->execute(["-$days"]);
        $output = "Deleted " . $del->rowCount() . " sent email log entries older than $days days.";

    } elseif ($jobKey === 'cleanup_activity_logs') {
        $days = 180;
        $del  = $pdo->prepare("DELETE FROM admin_activity_logs WHERE created_at < datetime('now', ? || ' days')");
        $del->execute(["-$days"]);
        $output = "Deleted " . $del->rowCount() . " activity log entries older than $days days.";

    } else {
        $status = 'error';
        $output = "Unknown job key: $jobKey";
    }
} catch (Exception $e) {
    $status = 'error';
    $output = $e->getMessage();
}

$duration = (int)((microtime(true) - $start) * 1000);

echo "[" . date('Y-m-d H:i:s') . "] $output ({$duration}ms)\n";

try {
    $pdo->prepare("INSERT INTO cron_logs (job_key, status, output, duration_ms) VALUES (?, ?, ?, ?)")
        ->execute([$jobKey, $status, $output, $duration]);
} catch (Exception $e) {
    echo "Warning: could not write to cron_logs: " . $e->getMessage() . "\n";
}

exit($status === 'success' ? 0 : 1);
