<?php
require_once __DIR__ . '/includes/session.php';
require_once 'includes/db.php';
require_once 'includes/config.php';

$activePage = 'cronjobs';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

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

// Handle manual run
$runMsg = '';
$runType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_job'])) {
    $jobKey = $_POST['job_key'] ?? '';
    $start  = microtime(true);
    $output = '';
    $status = 'success';

    try {
        if ($jobKey === 'cleanup_login_logs') {
            $days = 90;
            $del = $pdo->prepare("DELETE FROM login_logs WHERE created_at < datetime('now', ? || ' days')");
            $del->execute(["-$days"]);
            $output = "Deleted " . $del->rowCount() . " login log entries older than $days days.";

        } elseif ($jobKey === 'cleanup_sent_emails') {
            $days = 60;
            $del = $pdo->prepare("DELETE FROM sent_emails WHERE created_at < datetime('now', ? || ' days')");
            $del->execute(["-$days"]);
            $output = "Deleted " . $del->rowCount() . " sent email log entries older than $days days.";

        } elseif ($jobKey === 'expire_boosts') {
            $upd = $pdo->exec("UPDATE products SET is_featured = 0 WHERE boosted_until IS NOT NULL AND boosted_until < datetime('now') AND is_featured = 1");
            $output = "Expired $upd boosted product(s).";

        } elseif ($jobKey === 'close_old_requests') {
            $days = 60;
            $upd = $pdo->prepare("UPDATE product_requests SET status = 'closed' WHERE status = 'open' AND created_at < datetime('now', ? || ' days')");
            $upd->execute(["-$days"]);
            $output = "Closed " . $upd->rowCount() . " stale product request(s) older than $days days.";

        } elseif ($jobKey === 'cleanup_activity_logs') {
            $days = 180;
            $del = $pdo->prepare("DELETE FROM admin_activity_logs WHERE created_at < datetime('now', ? || ' days')");
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
    try {
        $pdo->prepare("INSERT INTO cron_logs (job_key, status, output, duration_ms) VALUES (?, ?, ?, ?)")
            ->execute([$jobKey, $status, $output, $duration]);
    } catch (Exception $e) {}

    $runMsg  = $output;
    $runType = $status;
}

// Fetch recent cron logs (last 50)
$cronLogs = [];
try {
    $cronLogs = $pdo->query("SELECT * FROM cron_logs ORDER BY ran_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Last run times per job
$lastRun = [];
try {
    $rows = $pdo->query("SELECT job_key, MAX(ran_at) as last_ran, status FROM cron_logs GROUP BY job_key")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) $lastRun[$r['job_key']] = $r;
} catch (Exception $e) {}

$siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'yourdomain.com');

$jobs = [
    [
        'key'         => 'expire_boosts',
        'name'        => 'Expire Product Boosts',
        'description' => 'Removes featured status from products whose boost period has ended.',
        'schedule'    => 'Every hour',
        'cron'        => '0 * * * *',
        'icon'        => 'rocket-outline',
        'color'       => '#f59e0b',
    ],
    [
        'key'         => 'close_old_requests',
        'name'        => 'Auto-Close Old Product Requests',
        'description' => 'Closes product requests that have been open for more than 60 days.',
        'schedule'    => 'Daily at midnight',
        'cron'        => '0 0 * * *',
        'icon'        => 'archive-outline',
        'color'       => '#6B21A8',
    ],
    [
        'key'         => 'cleanup_login_logs',
        'name'        => 'Clean Up Login Logs',
        'description' => 'Deletes login log entries older than 90 days to keep the database lean.',
        'schedule'    => 'Weekly (Sunday midnight)',
        'cron'        => '0 0 * * 0',
        'icon'        => 'key-outline',
        'color'       => '#3b82f6',
    ],
    [
        'key'         => 'cleanup_sent_emails',
        'name'        => 'Clean Up Email Logs',
        'description' => 'Removes sent email records older than 60 days.',
        'schedule'    => 'Weekly (Sunday midnight)',
        'cron'        => '0 2 * * 0',
        'icon'        => 'mail-outline',
        'color'       => '#10b981',
    ],
    [
        'key'         => 'cleanup_activity_logs',
        'name'        => 'Clean Up Admin Activity Logs',
        'description' => 'Deletes admin activity log entries older than 180 days.',
        'schedule'    => 'Monthly (1st of month)',
        'cron'        => '0 3 1 * *',
        'icon'        => 'list-outline',
        'color'       => '#ef4444',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cron Jobs - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root { --primary: #6B21A8; --bg: #f8f9fa; }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; display: flex; color: #333; }
        .sidebar { width: 260px; background: #1a1a1a; height: 100vh; position: fixed; padding: 0.5rem 0; color: white; z-index: 100; }
        .main-content { margin-left: 260px; padding: 2.5rem 3rem; width: calc(100% - 260px); min-height: 100vh; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }
        .header p { margin: 4px 0 0; color: #666; font-size: 0.9rem; }

        .alert { padding: 1rem 1.25rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: flex-start; gap: 10px; font-size: 0.9rem; }
        .alert-success { background: #f0fdf4; color: #166534; border-left: 4px solid #22c55e; }
        .alert-error { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; }

        .card { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #f0f0f0; margin-bottom: 2rem; }
        .card-title { font-size: 1.1rem; font-weight: 700; color: #1a1a1a; margin: 0 0 1.5rem; display: flex; align-items: center; gap: 10px; }
        .card-title ion-icon { color: #6B21A8; font-size: 1.3rem; }

        .jobs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1.25rem; }
        .job-card {
            border: 1px solid #f0f0f0; border-radius: 14px; padding: 1.25rem;
            background: white; position: relative; overflow: hidden;
            transition: box-shadow 0.2s;
        }
        .job-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .job-card-accent { position: absolute; top: 0; left: 0; width: 4px; height: 100%; border-radius: 14px 0 0 14px; }
        .job-header { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px; }
        .job-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
        .job-title { font-weight: 700; font-size: 0.95rem; color: #1a1a1a; margin-bottom: 3px; }
        .job-desc { font-size: 0.8rem; color: #64748b; line-height: 1.5; }
        .job-meta { display: flex; align-items: center; gap: 8px; margin: 10px 0; flex-wrap: wrap; }
        .badge { padding: 3px 10px; border-radius: 50px; font-size: 0.73rem; font-weight: 600; }
        .badge-schedule { background: #f3e8ff; color: #6B21A8; }
        .badge-last-run { font-size: 0.73rem; color: #94a3b8; }
        .badge-last-run.ok { color: #22c55e; }
        .badge-last-run.err { color: #ef4444; }
        .cron-cmd { background: #1e1e2e; color: #cdd6f4; font-family: monospace; font-size: 0.78rem; padding: 8px 12px; border-radius: 8px; margin: 10px 0; display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .cron-cmd code { flex: 1; word-break: break-all; }
        .copy-btn { background: #6B21A8; color: white; border: none; padding: 4px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 600; cursor: pointer; white-space: nowrap; flex-shrink: 0; }
        .copy-btn:hover { background: #581c87; }
        .run-btn { width: 100%; padding: 9px; border: none; border-radius: 8px; background: #f3e8ff; color: #6B21A8; font-weight: 700; font-size: 0.82rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 10px; }
        .run-btn:hover { background: #6B21A8; color: white; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 10px 12px; font-size: 0.78rem; font-weight: 600; color: #999; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #f0f0f0; }
        td { padding: 11px 12px; font-size: 0.85rem; border-bottom: 1px solid #f5f5f5; color: #333; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        .tag-success { background: #dcfce7; color: #166534; padding: 2px 10px; border-radius: 50px; font-weight: 600; font-size: 0.75rem; }
        .tag-error   { background: #fee2e2; color: #991b1b; padding: 2px 10px; border-radius: 50px; font-weight: 600; font-size: 0.75rem; }

        .section-tabs { display: flex; gap: 0; border-bottom: 2px solid #f0f0f0; margin-bottom: 1.5rem; }
        .tab-btn { padding: 10px 20px; background: none; border: none; font-weight: 600; font-size: 0.88rem; color: #999; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
        .tab-btn.active { color: #6B21A8; border-bottom-color: #6B21A8; }

        .setup-box { background: #1e1e2e; color: #cdd6f4; border-radius: 12px; padding: 1.5rem; font-family: monospace; font-size: 0.82rem; line-height: 2; overflow-x: auto; }
        .setup-box .comment { color: #6c7086; }
        .steps ol { padding-left: 1.5rem; line-height: 2; color: #475569; font-size: 0.9rem; }
        .steps li { margin-bottom: 6px; }
        .steps code { background: #f3e8ff; color: #6B21A8; padding: 2px 8px; border-radius: 4px; font-family: monospace; font-size: 0.85rem; }

        @media (max-width: 900px) { .main-content { margin-left: 0; width: 100%; padding: 1.5rem; } .jobs-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<?php include 'includes/admin_sidebar.php'; ?>
<div class="main-content">
    <div class="header">
        <div>
            <h1><ion-icon name="timer-outline" style="vertical-align:middle;color:#6B21A8;"></ion-icon> Cron Jobs</h1>
            <p>Automated scheduled tasks — run manually or schedule via cPanel</p>
        </div>
    </div>

    <?php if ($runMsg): ?>
        <div class="alert alert-<?php echo $runType; ?>">
            <ion-icon name="<?php echo $runType === 'success' ? 'checkmark-circle-outline' : 'alert-circle-outline'; ?>"></ion-icon>
            <span><?php echo htmlspecialchars($runMsg); ?></span>
        </div>
    <?php endif; ?>

    <!-- Jobs Grid -->
    <div class="card">
        <h2 class="card-title"><ion-icon name="cog-outline"></ion-icon> Scheduled Tasks</h2>
        <div class="jobs-grid">
            <?php foreach ($jobs as $job):
                $lr   = $lastRun[$job['key']] ?? null;
                $cpanelUrl = $siteUrl . '/admin_cronjobs.php?run_key=' . $job['key'];
                $phpCmd = 'php ' . __DIR__ . '/admin_cronjobs.php run=' . $job['key'];
            ?>
            <div class="job-card">
                <div class="job-card-accent" style="background:<?php echo $job['color']; ?>;"></div>
                <div style="padding-left:8px;">
                    <div class="job-header">
                        <div class="job-icon" style="background:<?php echo $job['color']; ?>18;color:<?php echo $job['color']; ?>;">
                            <ion-icon name="<?php echo $job['icon']; ?>"></ion-icon>
                        </div>
                        <div>
                            <div class="job-title"><?php echo htmlspecialchars($job['name']); ?></div>
                            <div class="job-desc"><?php echo htmlspecialchars($job['description']); ?></div>
                        </div>
                    </div>

                    <div class="job-meta">
                        <span class="badge badge-schedule"><?php echo $job['cron']; ?> — <?php echo $job['schedule']; ?></span>
                        <?php if ($lr): ?>
                            <span class="badge-last-run <?php echo $lr['status'] === 'success' ? 'ok' : 'err'; ?>">
                                Last: <?php echo date('M j, H:i', strtotime($lr['last_ran'])); ?>
                                <?php echo $lr['status'] === 'success' ? '✓' : '✗'; ?>
                            </span>
                        <?php else: ?>
                            <span class="badge-last-run">Never run</span>
                        <?php endif; ?>
                    </div>

                    <div style="font-size:0.75rem;color:#94a3b8;margin-bottom:4px;">cPanel Cron Command:</div>
                    <div class="cron-cmd">
                        <code id="cmd-<?php echo $job['key']; ?>">/usr/local/bin/php <?php echo htmlspecialchars(__DIR__); ?>/cron_runner.php <?php echo $job['key']; ?></code>
                        <button class="copy-btn" onclick="copyCmd('cmd-<?php echo $job['key']; ?>', this)">Copy</button>
                    </div>

                    <form method="POST" onsubmit="return confirm('Run this job now?')">
                        <input type="hidden" name="run_job" value="1">
                        <input type="hidden" name="job_key" value="<?php echo $job['key']; ?>">
                        <button type="submit" class="run-btn">
                            <ion-icon name="play-circle-outline"></ion-icon> Run Now
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Setup Instructions -->
    <div class="card">
        <h2 class="card-title"><ion-icon name="information-circle-outline"></ion-icon> cPanel Setup Instructions</h2>
        <div class="steps">
            <ol>
                <li>Log in to <strong>cPanel</strong> → go to <strong>Cron Jobs</strong></li>
                <li>Set the frequency using the cron expression shown on each job card above</li>
                <li>Paste the <strong>cPanel Cron Command</strong> shown on the job card into the <em>Command</em> field</li>
                <li>Click <strong>Add New Cron Job</strong> and you're done</li>
                <li>Cron results will appear in the <em>Recent Runs</em> log below once the script runs</li>
            </ol>
        </div>
        <div style="margin-top:1.25rem;">
            <div style="font-size:0.82rem;color:#64748b;margin-bottom:8px;font-weight:600;">Example cron entries for cPanel:</div>
            <div class="setup-box">
                <span class="comment"># Expire boosts — every hour</span><br>
                0 * * * * /usr/local/bin/php <?php echo htmlspecialchars(__DIR__); ?>/cron_runner.php expire_boosts<br><br>
                <span class="comment"># Cleanup login logs — weekly Sunday midnight</span><br>
                0 0 * * 0 /usr/local/bin/php <?php echo htmlspecialchars(__DIR__); ?>/cron_runner.php cleanup_login_logs
            </div>
        </div>
    </div>

    <!-- Recent Runs -->
    <div class="card">
        <h2 class="card-title"><ion-icon name="time-outline"></ion-icon> Recent Runs <span style="font-size:0.8rem;font-weight:400;color:#999;margin-left:auto;"><?php echo count($cronLogs); ?> entries</span></h2>
        <?php if (empty($cronLogs)): ?>
            <div style="text-align:center;padding:2.5rem;color:#999;">
                <ion-icon name="hourglass-outline" style="font-size:2.5rem;"></ion-icon>
                <p>No runs yet. Click <strong>Run Now</strong> on any job or wait for the cron schedule.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Job</th>
                            <th>Status</th>
                            <th>Output</th>
                            <th>Duration</th>
                            <th>Ran At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cronLogs as $log): ?>
                        <tr>
                            <td><code style="background:#f3e8ff;color:#6B21A8;padding:2px 8px;border-radius:4px;font-size:0.78rem;"><?php echo htmlspecialchars($log['job_key']); ?></code></td>
                            <td><span class="tag-<?php echo $log['status']; ?>"><?php echo ucfirst($log['status']); ?></span></td>
                            <td style="max-width:320px;font-size:0.82rem;color:#475569;"><?php echo htmlspecialchars($log['output'] ?? ''); ?></td>
                            <td style="color:#94a3b8;font-size:0.82rem;"><?php echo $log['duration_ms']; ?>ms</td>
                            <td style="white-space:nowrap;font-size:0.82rem;color:#666;"><?php echo $log['ran_at']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function copyCmd(elementId, btn) {
    var text = document.getElementById(elementId).textContent.trim();
    navigator.clipboard.writeText(text).then(function() {
        var orig = btn.textContent;
        btn.textContent = 'Copied!';
        btn.style.background = '#22c55e';
        setTimeout(function() { btn.textContent = orig; btn.style.background = ''; }, 2000);
    }).catch(function() {
        var el = document.getElementById(elementId);
        var range = document.createRange();
        range.selectNode(el);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
        document.execCommand('copy');
        window.getSelection().removeAllRanges();
        btn.textContent = 'Copied!';
        setTimeout(function() { btn.textContent = 'Copy'; }, 2000);
    });
}
</script>
</body>
</html>
