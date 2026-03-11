<?php
require_once __DIR__ . '/includes/session.php';
require_once 'includes/db.php';
require_once 'includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'get_recipients') {
    $sendTo = $_POST['send_to'] ?? '';
    $customEmail = trim($_POST['custom_email'] ?? '');
    $recipients = [];

    try {
        $users = $pdo->query("SELECT id, email, account_type, is_verified_vendor FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

        if ($sendTo === 'custom' && !empty($customEmail)) {
            $recipients = array_values(array_filter(array_map('trim', explode(',', $customEmail))));
        } elseif ($sendTo === 'all_users') {
            foreach ($users as $u) { if (!empty($u['email'])) $recipients[] = $u['email']; }
        } elseif ($sendTo === 'all_vendors') {
            foreach ($users as $u) {
                if (!empty($u['email']) && ($u['is_verified_vendor'] == 1 || $u['account_type'] === 'vendor')) $recipients[] = $u['email'];
            }
        } elseif ($sendTo === 'all_customers') {
            foreach ($users as $u) {
                if (!empty($u['email']) && $u['account_type'] !== 'vendor' && $u['is_verified_vendor'] != 1) $recipients[] = $u['email'];
            }
        } elseif (is_numeric($sendTo)) {
            foreach ($users as $u) {
                if ($u['id'] == $sendTo && !empty($u['email'])) { $recipients[] = $u['email']; break; }
            }
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]); exit;
    }

    $recipients = array_values(array_unique(array_filter($recipients)));
    echo json_encode(['recipients' => $recipients, 'total' => count($recipients)]);
    exit;
}

if ($action === 'send_one') {
    $to      = trim($_POST['to'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body    = $_POST['body'] ?? '';

    if (empty($to) || empty($subject) || empty($body)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']); exit;
    }

    $smtpCfg  = getSmtpConfig($pdo);
    $fromEmail = $smtpCfg['user'];
    $status    = 'failed';
    $errMsg    = '';

    try {
        $smtp   = createSmtp($pdo);
        $result = $smtp->send($to, $subject, $body, 'Listaria');
        $status = $result ? 'sent' : 'failed';
        if (!$result) $errMsg = 'SMTP send returned false';
    } catch (Exception $e) {
        $errMsg = $e->getMessage();
    }

    try {
        $pdo->prepare("INSERT INTO sent_emails (from_email, to_email, subject, body, status, sent_by, created_at) VALUES (?, ?, ?, ?, ?, ?, datetime('now'))")
            ->execute([$fromEmail, $to, $subject, $body, $status, $_SESSION['user_id']]);
    } catch (Exception $e) {}

    echo json_encode(['success' => $status === 'sent', 'to' => $to, 'error' => $errMsg]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);
