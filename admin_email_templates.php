<?php
session_start();
require 'includes/db.php';
require 'includes/email_templates.php';

$activePage = 'email_templates';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$msg = '';
$msgType = 'success';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_templates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            template_key VARCHAR(100) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            subject TEXT NOT NULL,
            body TEXT NOT NULL,
            variables TEXT,
            is_active INTEGER DEFAULT 1,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
} catch(Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_template'])) {
        $key = $_POST['template_key'] ?? '';
        $name = $_POST['template_name'] ?? '';
        $subject = $_POST['template_subject'] ?? '';
        $body = $_POST['template_body'] ?? '';
        $variables = $_POST['template_variables'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($key && $name && $subject && $body) {
            $stmt = $pdo->prepare("SELECT id FROM email_templates WHERE template_key = ?");
            $stmt->execute([$key]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE email_templates SET name = ?, subject = ?, body = ?, variables = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE template_key = ?");
                $stmt->execute([$name, $subject, $body, $variables, $isActive, $key]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO email_templates (template_key, name, subject, body, variables, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$key, $name, $subject, $body, $variables, $isActive]);
            }
            $msg = "Template \"$name\" saved successfully!";

            try {
                $pdo->prepare("INSERT INTO admin_activity_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)")
                    ->execute([$_SESSION['user_id'], "Email template updated", "Modified template: $name", $_SERVER['REMOTE_ADDR'] ?? '']);
            } catch(Exception $e) {}
        } else {
            $msg = "Please fill in all required fields.";
            $msgType = 'error';
        }
    }

    if (isset($_POST['reset_template'])) {
        $key = $_POST['template_key'] ?? '';
        $defaults = getDefaultEmailTemplates();
        if (isset($defaults[$key])) {
            $pdo->prepare("DELETE FROM email_templates WHERE template_key = ?")->execute([$key]);
            $msg = "Template \"" . $defaults[$key]['name'] . "\" reset to default.";
        }
    }

    if (isset($_POST['send_test'])) {
        $key = $_POST['template_key'] ?? '';
        $testEmail = $_POST['test_email'] ?? '';
        if ($key && $testEmail) {
            $template = getEmailTemplate($pdo, $key);
            if ($template) {
                $defaults = getDefaultEmailTemplates();
                $vars = explode(',', $template['variables'] ?? '');
                $testData = [];
                foreach ($vars as $var) {
                    $var = trim($var);
                    if ($var) {
                        $testData[$var] = '[' . strtoupper(str_replace('_', ' ', $var)) . ']';
                    }
                }
                $rendered = renderEmailTemplate($pdo, $key, $testData);
                if ($rendered) {
                    try {
                        $smtp = createSmtp($pdo);
                        $smtp->send($testEmail, $rendered['subject'], $rendered['body'], 'Listaria');
                        $msg = "Test email sent to $testEmail!";
                    } catch(Exception $e) {
                        $msg = "Failed to send test email: " . $e->getMessage();
                        $msgType = 'error';
                    }
                }
            }
        }
    }
}

$defaults = getDefaultEmailTemplates();
$templates = [];
foreach ($defaults as $key => $def) {
    $template = getEmailTemplate($pdo, $key);
    $templates[$key] = $template;
    $templates[$key]['description'] = $def['description'];
    $templates[$key]['default_variables'] = $def['variables'];
    if (!isset($templates[$key]['variables']) || !$templates[$key]['variables']) {
        $templates[$key]['variables'] = $def['variables'];
    }
}

$editKey = $_GET['edit'] ?? '';
$editTemplate = null;
if ($editKey && isset($templates[$editKey])) {
    $editTemplate = $templates[$editKey];
    $editTemplate['key'] = $editKey;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Templates - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root { 
            --primary: #6B21A8; 
            --bg: #f8f9fa; 
            --sidebar-bg: #1a1a1a;
            --text-light: #a1a1aa;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; display:flex; color: #333; }
        
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; padding: 0.5rem 0; color: white; z-index: 100; }
        .brand { font-size: 1.2rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem; text-decoration: none; }
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
        
        .templates-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.25rem; margin-bottom: 2rem;
        }
        
        .template-card {
            background: white; border-radius: 16px; padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #f0f0f0;
            transition: all 0.2s; position: relative;
        }
        .template-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
        
        .template-card .status-badge {
            position: absolute; top: 1rem; right: 1rem; padding: 4px 10px;
            border-radius: 20px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase;
        }
        .status-active { background: #f0fdf4; color: #22c55e; }
        .status-inactive { background: #fef2f2; color: #ef4444; }
        
        .template-card .icon-wrap {
            width: 44px; height: 44px; border-radius: 12px; display: flex;
            align-items: center; justify-content: center; margin-bottom: 1rem;
            background: #f3e8ff; color: #6B21A8; font-size: 1.3rem;
        }
        
        .template-card h3 { margin: 0 0 0.5rem; font-size: 1rem; font-weight: 700; color: #1a1a1a; }
        .template-card .desc { font-size: 0.82rem; color: #666; margin-bottom: 1rem; line-height: 1.5; }
        .template-card .subject-preview { font-size: 0.78rem; color: #999; margin-bottom: 1rem; font-style: italic; }
        
        .template-card .variables { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 1rem; }
        .template-card .var-tag {
            background: #f3e8ff; color: #6B21A8; padding: 2px 8px; border-radius: 4px;
            font-size: 0.7rem; font-weight: 600; font-family: monospace;
        }
        
        .template-card .actions { display: flex; gap: 0.5rem; }
        .btn-edit, .btn-reset {
            padding: 8px 16px; border-radius: 8px; font-size: 0.82rem;
            font-weight: 600; cursor: pointer; border: none; transition: all 0.2s;
            text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
        }
        .btn-edit { background: #6B21A8; color: white; }
        .btn-edit:hover { background: #581c87; }
        .btn-reset { background: #f0f0f0; color: #666; }
        .btn-reset:hover { background: #e5e5e5; }
        
        .editor-section {
            background: white; border-radius: 16px; padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #f0f0f0;
        }
        .editor-title {
            font-size: 1.2rem; font-weight: 700; color: #1a1a1a; margin: 0 0 0.5rem;
            display: flex; align-items: center; gap: 10px;
        }
        .editor-title ion-icon { color: #6B21A8; }
        .editor-subtitle { color: #666; font-size: 0.85rem; margin-bottom: 1.5rem; }
        
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #333; font-size: 0.9rem; }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group textarea {
            width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px;
            font-size: 0.9rem; font-family: 'Inter', sans-serif; transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group textarea:focus { border-color: #6B21A8; outline: none; }
        .form-group textarea { min-height: 300px; font-family: 'Fira Code', 'Courier New', monospace; font-size: 0.82rem; line-height: 1.6; resize: vertical; }
        .form-group .help-text { font-size: 0.78rem; color: #999; margin-top: 0.35rem; }
        
        .toggle-row { display: flex; align-items: center; gap: 12px; margin-bottom: 1.25rem; }
        .toggle-switch { position: relative; width: 48px; height: 26px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background: #ccc; border-radius: 26px; transition: 0.3s;
        }
        .toggle-slider:before {
            content: ''; position: absolute; height: 20px; width: 20px; left: 3px; bottom: 3px;
            background: white; border-radius: 50%; transition: 0.3s;
        }
        .toggle-switch input:checked + .toggle-slider { background: #6B21A8; }
        .toggle-switch input:checked + .toggle-slider:before { transform: translateX(22px); }
        
        .editor-actions { display: flex; gap: 0.75rem; margin-top: 1.5rem; flex-wrap: wrap; }
        .btn-save {
            padding: 10px 24px; border-radius: 8px; font-size: 0.9rem;
            font-weight: 600; cursor: pointer; border: none; background: #6B21A8;
            color: white; transition: all 0.2s;
        }
        .btn-save:hover { background: #581c87; }
        .btn-cancel {
            padding: 10px 24px; border-radius: 8px; font-size: 0.9rem;
            font-weight: 600; cursor: pointer; border: 1px solid #ddd;
            background: white; color: #666; text-decoration: none; display: inline-flex;
            align-items: center; transition: all 0.2s;
        }
        .btn-cancel:hover { background: #f9f9f9; }
        .btn-test {
            padding: 10px 24px; border-radius: 8px; font-size: 0.9rem;
            font-weight: 600; cursor: pointer; border: 1px solid #6B21A8;
            background: white; color: #6B21A8; transition: all 0.2s;
        }
        .btn-test:hover { background: #f3e8ff; }
        
        .preview-section {
            margin-top: 1.5rem; border: 1px solid #eee; border-radius: 12px; overflow: hidden;
        }
        .preview-header {
            background: #f8f9fa; padding: 10px 16px; font-size: 0.82rem;
            font-weight: 600; color: #666; border-bottom: 1px solid #eee;
            display: flex; align-items: center; gap: 6px;
        }
        .preview-body { padding: 20px; background: white; }
        .preview-body iframe { width: 100%; min-height: 350px; border: none; }
        
        .test-email-row {
            display: flex; gap: 0.75rem; align-items: end; margin-top: 1.25rem;
            padding-top: 1.25rem; border-top: 1px solid #f0f0f0;
        }
        .test-email-row .form-group { flex: 1; margin-bottom: 0; }
        
        .tabs { display: flex; gap: 0; margin-bottom: 1.5rem; border-bottom: 2px solid #f0f0f0; }
        .tab {
            padding: 10px 20px; cursor: pointer; font-weight: 600; font-size: 0.9rem;
            color: #999; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s;
        }
        .tab.active { color: #6B21A8; border-bottom-color: #6B21A8; }
        .tab:hover { color: #6B21A8; }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        @media (max-width: 768px) {
            .templates-grid { grid-template-columns: 1fr; }
            .main-content { margin-left: 0; width: 100%; padding: 1.5rem; }
        }
    </style>
</head>
<body>

<?php include 'includes/admin_sidebar.php'; ?>

<div class="main-content">
    <div class="header">
        <div>
            <h1><ion-icon name="mail-outline" style="vertical-align: middle; color: #6B21A8;"></ion-icon> Email Templates</h1>
            <p>Customize transactional emails sent to users and sellers</p>
        </div>
        <?php if ($editKey): ?>
            <a href="admin_email_templates.php" class="btn-cancel"><ion-icon name="arrow-back-outline"></ion-icon> Back to All Templates</a>
        <?php endif; ?>
    </div>
    
    <?php if ($msg): ?>
        <div class="msg-<?php echo $msgType; ?>">
            <ion-icon name="<?php echo $msgType === 'success' ? 'checkmark-circle' : 'alert-circle'; ?>"></ion-icon>
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($editTemplate): ?>
        <div class="editor-section">
            <h2 class="editor-title">
                <ion-icon name="create-outline"></ion-icon>
                Editing: <?php echo htmlspecialchars($editTemplate['name']); ?>
            </h2>
            <p class="editor-subtitle"><?php echo htmlspecialchars($templates[$editKey]['description'] ?? ''); ?></p>
            
            <div class="tabs">
                <div class="tab active" onclick="switchTab('editor')">Editor</div>
                <div class="tab" onclick="switchTab('preview')">Preview</div>
            </div>
            
            <div id="tab-editor" class="tab-content active">
                <form method="POST" id="templateForm">
                    <input type="hidden" name="save_template" value="1">
                    <input type="hidden" name="template_key" value="<?php echo htmlspecialchars($editKey); ?>">
                    
                    <div class="form-group">
                        <label>Template Name</label>
                        <input type="text" name="template_name" value="<?php echo htmlspecialchars($editTemplate['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Subject</label>
                        <input type="text" name="template_subject" value="<?php echo htmlspecialchars($editTemplate['subject']); ?>" required>
                        <div class="help-text">You can use variables like {{variable_name}} in the subject line.</div>
                    </div>
                    
                    <div class="toggle-row">
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_active" <?php echo ($editTemplate['is_active'] ?? 1) ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <span style="font-weight: 600; font-size: 0.9rem;">Active</span>
                        <span style="font-size: 0.78rem; color: #999;">When disabled, this email will not be sent.</span>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Body (HTML)</label>
                        <textarea name="template_body" id="templateBody" required><?php echo htmlspecialchars($editTemplate['body']); ?></textarea>
                        <div class="help-text">Write HTML for the email body. Use {{variable_name}} for dynamic content.</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Available Variables</label>
                        <input type="text" name="template_variables" value="<?php echo htmlspecialchars($editTemplate['variables'] ?? ''); ?>">
                        <div class="help-text">Comma-separated list of variable names available for this template.</div>
                    </div>
                    
                    <div class="editor-actions">
                        <button type="submit" class="btn-save"><ion-icon name="save-outline" style="vertical-align:middle;margin-right:4px;"></ion-icon> Save Template</button>
                        <a href="admin_email_templates.php" class="btn-cancel">Cancel</a>
                        <button type="button" class="btn-test" onclick="document.getElementById('testEmailSection').style.display='flex'">
                            <ion-icon name="paper-plane-outline" style="vertical-align:middle;margin-right:4px;"></ion-icon> Send Test Email
                        </button>
                    </div>
                </form>
                
                <form method="POST" id="testEmailSection" class="test-email-row" style="display:none;">
                    <input type="hidden" name="send_test" value="1">
                    <input type="hidden" name="template_key" value="<?php echo htmlspecialchars($editKey); ?>">
                    <div class="form-group">
                        <label>Test Email Address</label>
                        <input type="email" name="test_email" placeholder="test@example.com" required>
                    </div>
                    <button type="submit" class="btn-save" style="height:42px;">Send Test</button>
                </form>
                
                <form method="POST" style="margin-top: 1rem;">
                    <input type="hidden" name="reset_template" value="1">
                    <input type="hidden" name="template_key" value="<?php echo htmlspecialchars($editKey); ?>">
                    <button type="submit" class="btn-reset" onclick="return confirm('Reset this template to default? Your customizations will be lost.')">
                        <ion-icon name="refresh-outline"></ion-icon> Reset to Default
                    </button>
                </form>
            </div>
            
            <div id="tab-preview" class="tab-content">
                <div class="preview-section">
                    <div class="preview-header">
                        <ion-icon name="eye-outline"></ion-icon> Email Preview
                    </div>
                    <div class="preview-body">
                        <iframe id="previewFrame" srcdoc=""></iframe>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
            function switchTab(tab) {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
                document.getElementById('tab-' + tab).classList.add('active');
                event.target.classList.add('active');
                
                if (tab === 'preview') {
                    updatePreview();
                }
            }
            
            function updatePreview() {
                var body = document.getElementById('templateBody').value;
                var frame = document.getElementById('previewFrame');
                <?php
                    $vars = explode(',', $editTemplate['variables'] ?? '');
                    $jsReplacements = '';
                    foreach ($vars as $var) {
                        $var = trim($var);
                        if ($var) {
                            $sample = strtoupper(str_replace('_', ' ', $var));
                            $jsReplacements .= "body = body.replace(/\\{\\{" . preg_quote($var, '/') . "\\}\\}/g, '[" . $sample . "]');\n";
                        }
                    }
                    echo $jsReplacements;
                ?>
                frame.srcdoc = body;
            }
        </script>
        
    <?php else: ?>
        <div class="templates-grid">
            <?php
            $icons = [
                'order_confirmation' => 'bag-check-outline',
                'shipping_update' => 'airplane-outline',
                'listing_approved' => 'checkmark-circle-outline',
                'listing_rejected' => 'close-circle-outline',
                'welcome_email' => 'person-add-outline',
                'order_delivered' => 'gift-outline',
                'support_reply' => 'chatbox-ellipses-outline',
            ];
            foreach ($templates as $key => $tpl): 
                $isCustomized = false;
                try {
                    $chk = $pdo->prepare("SELECT id FROM email_templates WHERE template_key = ?");
                    $chk->execute([$key]);
                    $isCustomized = (bool)$chk->fetch();
                } catch(Exception $e) {}
                $isActive = ($tpl['is_active'] ?? 1) == 1;
            ?>
            <div class="template-card">
                <span class="status-badge <?php echo $isActive ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                </span>
                <div class="icon-wrap">
                    <ion-icon name="<?php echo $icons[$key] ?? 'mail-outline'; ?>"></ion-icon>
                </div>
                <h3><?php echo htmlspecialchars($tpl['name']); ?></h3>
                <div class="desc"><?php echo htmlspecialchars($tpl['description'] ?? ''); ?></div>
                <div class="subject-preview">Subject: <?php echo htmlspecialchars($tpl['subject']); ?></div>
                <div class="variables">
                    <?php 
                    $vars = explode(',', $tpl['variables'] ?? $tpl['default_variables'] ?? '');
                    foreach ($vars as $var):
                        $var = trim($var);
                        if ($var):
                    ?>
                        <span class="var-tag">{{<?php echo $var; ?>}}</span>
                    <?php endif; endforeach; ?>
                </div>
                <div class="actions">
                    <a href="admin_email_templates.php?edit=<?php echo urlencode($key); ?>" class="btn-edit">
                        <ion-icon name="create-outline"></ion-icon> Edit
                    </a>
                    <?php if ($isCustomized): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="reset_template" value="1">
                        <input type="hidden" name="template_key" value="<?php echo $key; ?>">
                        <button type="submit" class="btn-reset" onclick="return confirm('Reset to default?')">
                            <ion-icon name="refresh-outline"></ion-icon> Reset
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
