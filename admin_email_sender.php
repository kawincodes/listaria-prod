<?php
require_once __DIR__ . '/includes/session.php';
require 'includes/db.php';
require 'includes/config.php';

$activePage = 'email_sender';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$msg = '';
$msgType = 'success';

$users = [];
try {
    $users = $pdo->query("SELECT id, username, email, account_type, is_verified_vendor FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $sendTo = $_POST['send_to'] ?? '';
    $customEmail = trim($_POST['custom_email'] ?? '');
    $ccField = trim($_POST['cc'] ?? '');
    $bccField = trim($_POST['bcc'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body = $_POST['body'] ?? '';

    if (empty($subject) || empty($body)) {
        $msg = "Subject and body are required.";
        $msgType = 'error';
    } else {
        $recipients = [];

        if ($sendTo === 'custom' && !empty($customEmail)) {
            $recipients = array_map('trim', explode(',', $customEmail));
        } elseif ($sendTo === 'all_users') {
            foreach ($users as $u) {
                if (!empty($u['email'])) $recipients[] = $u['email'];
            }
        } elseif ($sendTo === 'all_vendors') {
            foreach ($users as $u) {
                if (!empty($u['email']) && ($u['is_verified_vendor'] == 1 || $u['account_type'] === 'vendor')) {
                    $recipients[] = $u['email'];
                }
            }
        } elseif ($sendTo === 'all_customers') {
            foreach ($users as $u) {
                if (!empty($u['email']) && $u['account_type'] !== 'vendor' && $u['is_verified_vendor'] != 1) {
                    $recipients[] = $u['email'];
                }
            }
        } elseif (is_numeric($sendTo)) {
            foreach ($users as $u) {
                if ($u['id'] == $sendTo && !empty($u['email'])) {
                    $recipients[] = $u['email'];
                    break;
                }
            }
        }

        $recipients = array_unique(array_filter($recipients));

        if (empty($recipients)) {
            $msg = "No valid recipients found.";
            $msgType = 'error';
        } else {
            try {
                $smtp = createSmtp($pdo);
                $smtpCfg = getSmtpConfig($pdo);
                $fromEmail = $smtpCfg['user'];
                $sentCount = 0;
                $failCount = 0;

                $logStmt = $pdo->prepare("INSERT INTO sent_emails (from_email, to_email, subject, body, status, sent_by, created_at) VALUES (?, ?, ?, ?, ?, ?, datetime('now'))");

                foreach ($recipients as $to) {
                    try {
                        $smtp = createSmtp($pdo);
                        $result = $smtp->send($to, $subject, $body, 'Listaria');
                        $status = $result ? 'sent' : 'failed';
                        if ($result) $sentCount++; else $failCount++;
                    } catch (Exception $e) {
                        $status = 'failed';
                        $failCount++;
                    }
                    $logStmt->execute([$fromEmail, $to, $subject, $body, $status, $_SESSION['user_id']]);
                }

                if ($failCount === 0) {
                    $msg = "Email sent successfully to $sentCount recipient(s).";
                } else {
                    $msg = "Sent to $sentCount, failed for $failCount recipient(s).";
                    $msgType = $sentCount > 0 ? 'success' : 'error';
                }

                try {
                    $pdo->prepare("INSERT INTO admin_activity_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)")
                        ->execute([$_SESSION['user_id'], "Custom email sent", "Subject: $subject | Recipients: $sentCount sent, $failCount failed", $_SERVER['REMOTE_ADDR'] ?? '']);
                } catch (Exception $e) {}

            } catch (Exception $e) {
                $msg = "SMTP Error: " . $e->getMessage();
                $msgType = 'error';
            }
        }
    }
}

if (isset($_POST['delete_email'])) {
    $deleteId = (int)($_POST['delete_id'] ?? 0);
    if ($deleteId > 0) {
        try {
            $pdo->prepare("DELETE FROM sent_emails WHERE id = ?")->execute([$deleteId]);
            $msg = "Email log entry deleted.";
        } catch (Exception $e) {
            $msg = "Error deleting entry.";
            $msgType = 'error';
        }
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$totalEmails = 0;
$sentEmails = [];
try {
    $totalEmails = (int)$pdo->query("SELECT COUNT(*) FROM sent_emails")->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM sent_emails ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$perPage, $offset]);
    $sentEmails = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$totalPages = max(1, ceil($totalEmails / $perPage));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Sender - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <style>
        :root {
            --primary: #6B21A8;
            --bg: #f8f9fa;
            --sidebar-bg: #1a1a1a;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; display: flex; color: #333; }
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; padding: 0.5rem 0; color: white; z-index: 100; }
        .main-content { margin-left: 260px; padding: 2.5rem 3rem; width: calc(100% - 260px); min-height: 100vh; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }
        .header p { margin: 4px 0 0; color: #666; font-size: 0.9rem; }

        .msg-success {
            background: #f0fdf4; color: #22c55e; padding: 1rem; border-radius: 10px;
            margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 8px;
        }
        .msg-error {
            background: #fef2f2; color: #ef4444; padding: 1rem; border-radius: 10px;
            margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 8px;
        }

        .card {
            background: white; border-radius: 16px; padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #f0f0f0;
            margin-bottom: 2rem;
        }
        .card-title {
            font-size: 1.2rem; font-weight: 700; color: #1a1a1a; margin: 0 0 1.5rem;
            display: flex; align-items: center; gap: 10px;
        }
        .card-title ion-icon { color: #6B21A8; }

        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #333; font-size: 0.9rem; }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group select,
        .form-group textarea {
            width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px;
            font-size: 0.9rem; font-family: 'Inter', sans-serif; transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #6B21A8; outline: none;
        }
        .form-group .help-text { font-size: 0.78rem; color: #999; margin-top: 0.35rem; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        .btn-send {
            padding: 12px 28px; border-radius: 8px; font-size: 0.95rem;
            font-weight: 600; cursor: pointer; border: none; background: #6B21A8;
            color: white; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-send:hover { background: #581c87; }

        .ql-toolbar.ql-snow { border-radius: 8px 8px 0 0; border-color: #ddd; background: #fafafa; }
        .ql-container.ql-snow { border-radius: 0 0 8px 8px; border-color: #ddd; font-size: 14px; min-height: 250px; }
        .ql-editor { min-height: 250px; line-height: 1.6; }
        #quillEditor { background: white; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 10px 12px; font-size: 0.78rem; font-weight: 600; color: #999; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #f0f0f0; }
        td { padding: 12px; font-size: 0.88rem; border-bottom: 1px solid #f5f5f5; color: #333; vertical-align: top; }
        tr:hover td { background: #faf8ff; }

        .status-sent { color: #22c55e; font-weight: 600; }
        .status-failed { color: #ef4444; font-weight: 600; }

        .btn-view-body {
            background: #f3e8ff; color: #6B21A8; padding: 4px 10px; border-radius: 6px;
            font-size: 0.75rem; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s;
        }
        .btn-view-body:hover { background: #6B21A8; color: white; }

        .btn-delete {
            background: #fef2f2; color: #ef4444; padding: 4px 10px; border-radius: 6px;
            font-size: 0.75rem; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s;
        }
        .btn-delete:hover { background: #ef4444; color: white; }

        .pagination { display: flex; gap: 6px; justify-content: center; margin-top: 1.5rem; }
        .pagination a, .pagination span {
            padding: 6px 14px; border-radius: 8px; font-size: 0.85rem;
            text-decoration: none; font-weight: 600; transition: all 0.2s;
        }
        .pagination a { background: #f0f0f0; color: #666; }
        .pagination a:hover { background: #6B21A8; color: white; }
        .pagination span.active { background: #6B21A8; color: white; }

        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: white; border-radius: 16px; padding: 2rem; max-width: 700px; width: 90%;
            max-height: 80vh; overflow-y: auto; position: relative;
        }
        .modal-close {
            position: absolute; top: 1rem; right: 1rem; background: #f0f0f0; border: none;
            border-radius: 50%; width: 32px; height: 32px; cursor: pointer; font-size: 1.1rem;
            display: flex; align-items: center; justify-content: center; color: #666;
        }
        .modal-close:hover { background: #e5e5e5; }

        .empty-state {
            text-align: center; padding: 3rem; color: #999;
        }
        .empty-state ion-icon { font-size: 3rem; margin-bottom: 1rem; color: #ddd; }

        .table-container { overflow-x: auto; }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; width: 100%; padding: 1.5rem; }
            .form-row { grid-template-columns: 1fr; }
            .header { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
        }
    </style>
</head>
<body>

<?php include 'includes/admin_sidebar.php'; ?>

<div class="main-content">
    <div class="header">
        <div>
            <h1><ion-icon name="send-outline" style="vertical-align: middle; color: #6B21A8;"></ion-icon> Email Sender</h1>
            <p>Compose and send custom emails to users</p>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="msg-<?php echo $msgType; ?>">
            <ion-icon name="<?php echo $msgType === 'success' ? 'checkmark-circle' : 'alert-circle'; ?>"></ion-icon>
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2 class="card-title">
            <ion-icon name="create-outline"></ion-icon> Compose Email
        </h2>

        <form method="POST" id="emailForm" onsubmit="return syncAndSubmit()">
            <input type="hidden" name="send_email" value="1">

            <div class="form-group">
                <label>Send To</label>
                <select name="send_to" id="sendTo" onchange="toggleCustomEmail()">
                    <option value="">-- Select Recipient --</option>
                    <option value="custom">Custom Email Address</option>
                    <option value="all_users">All Users</option>
                    <option value="all_vendors">All Vendors</option>
                    <option value="all_customers">All Customers</option>
                    <optgroup label="Individual Users">
                        <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>">
                            <?php echo htmlspecialchars($u['username'] . ' (' . $u['email'] . ')'); ?>
                            <?php if ($u['is_verified_vendor'] == 1) echo ' [Vendor]'; ?>
                        </option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>

            <div class="form-group" id="customEmailGroup" style="display:none;">
                <label>Email Address(es)</label>
                <input type="text" name="custom_email" placeholder="email@example.com (comma-separated for multiple)">
                <div class="help-text">Enter one or more email addresses separated by commas.</div>
            </div>

            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="subject" placeholder="Email subject line" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>CC <span style="font-weight:400;color:#999;">(optional)</span></label>
                    <input type="text" name="cc" placeholder="cc@example.com">
                </div>
                <div class="form-group">
                    <label>BCC <span style="font-weight:400;color:#999;">(optional)</span></label>
                    <input type="text" name="bcc" placeholder="bcc@example.com">
                </div>
            </div>

            <div class="form-group">
                <label>Email Body</label>
                <div id="quillEditor"></div>
                <textarea name="body" id="emailBody" required style="display:none;"></textarea>
            </div>

            <button type="submit" class="btn-send">
                <ion-icon name="paper-plane-outline"></ion-icon> Send Email
            </button>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title">
            <ion-icon name="time-outline"></ion-icon> Sent Email History
            <span style="font-size:0.8rem;font-weight:400;color:#999;margin-left:auto;"><?php echo $totalEmails; ?> total</span>
        </h2>

        <?php if (empty($sentEmails)): ?>
            <div class="empty-state">
                <ion-icon name="mail-unread-outline"></ion-icon>
                <p>No emails sent yet. Compose your first email above.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>To</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Sent At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sentEmails as $email): ?>
                        <tr>
                            <td style="max-width:200px;word-break:break-all;"><?php echo htmlspecialchars($email['to_email']); ?></td>
                            <td style="max-width:250px;"><?php echo htmlspecialchars($email['subject']); ?></td>
                            <td>
                                <span class="status-<?php echo $email['status']; ?>">
                                    <?php echo ucfirst($email['status']); ?>
                                </span>
                            </td>
                            <td style="white-space:nowrap;font-size:0.82rem;color:#666;"><?php echo $email['created_at']; ?></td>
                            <td style="white-space:nowrap;">
                                <button class="btn-view-body" onclick="viewEmailBody(<?php echo $email['id']; ?>)">View</button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this log entry?')">
                                    <input type="hidden" name="delete_email" value="1">
                                    <input type="hidden" name="delete_id" value="<?php echo $email['id']; ?>">
                                    <button type="submit" class="btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr id="body-row-<?php echo $email['id']; ?>" style="display:none;">
                            <td colspan="5" style="background:#faf8ff;padding:1rem;">
                                <div style="font-size:0.82rem;color:#666;margin-bottom:6px;"><strong>From:</strong> <?php echo htmlspecialchars($email['from_email'] ?? ''); ?></div>
                                <div style="border:1px solid #eee;border-radius:8px;padding:1rem;background:white;max-height:300px;overflow-y:auto;">
                                    <?php echo $email['body']; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>">&laquo; Prev</a>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
var quillEditor = null;

try {
    quillEditor = new Quill('#quillEditor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'align': [] }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                ['link', 'image', 'blockquote'],
                ['clean']
            ]
        }
    });
} catch(e) {
    document.getElementById('emailBody').style.display = '';
    document.getElementById('emailBody').style.minHeight = '250px';
}

function syncAndSubmit() {
    if (quillEditor) {
        document.getElementById('emailBody').value = quillEditor.root.innerHTML;
    }
    var sendTo = document.getElementById('sendTo').value;
    if (!sendTo) {
        alert('Please select a recipient.');
        return false;
    }
    var body = document.getElementById('emailBody').value;
    if (!body || body === '<p><br></p>') {
        alert('Please enter an email body.');
        return false;
    }
    return true;
}

function toggleCustomEmail() {
    var val = document.getElementById('sendTo').value;
    document.getElementById('customEmailGroup').style.display = (val === 'custom') ? '' : 'none';
}

function viewEmailBody(id) {
    var row = document.getElementById('body-row-' + id);
    if (row) {
        row.style.display = row.style.display === 'none' ? '' : 'none';
    }
}
</script>

</body>
</html>
