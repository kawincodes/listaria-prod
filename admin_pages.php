<?php
require 'includes/db.php';
session_start();
$activePage = 'pages';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$message = '';

$pages = [
    'about_content' => [
        'title' => 'About Us',
        'icon' => 'information-circle-outline',
        'desc' => 'Main about page content (HTML supported)',
        'url' => 'about.php'
    ],
    'terms_of_service' => [
        'title' => 'Terms of Service',
        'icon' => 'document-text-outline',
        'desc' => 'Legal terms and conditions',
        'url' => 'terms.php'
    ],
    'privacy_policy' => [
        'title' => 'Privacy Policy',
        'icon' => 'shield-checkmark-outline',
        'desc' => 'Data privacy and protection policy',
        'url' => 'privacy.php'
    ],
    'founder_1_note' => [
        'title' => 'Founder 1 - Note',
        'icon' => 'person-outline',
        'desc' => 'First founder bio/description text',
        'url' => 'founders.php'
    ],
    'founder_1_image' => [
        'title' => 'Founder 1 - Image URL',
        'icon' => 'image-outline',
        'desc' => 'Image URL for the first founder',
        'url' => 'founders.php',
        'type' => 'text'
    ],
    'founder_2_note' => [
        'title' => 'Founder 2 - Note',
        'icon' => 'person-outline',
        'desc' => 'Second founder bio/description text',
        'url' => 'founders.php'
    ],
    'founder_2_image' => [
        'title' => 'Founder 2 - Image URL',
        'icon' => 'image-outline',
        'desc' => 'Image URL for the second founder',
        'url' => 'founders.php',
        'type' => 'text'
    ],
];

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['page_key'])) {
    $key = $_POST['page_key'];
    $content = $_POST['page_content'];
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $message = 'error';
    } elseif (array_key_exists($key, $pages)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$content, $key]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $content]);
        }
        $message = 'saved:' . $pages[$key]['title'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

$currentValues = [];
$keys = array_keys($pages);
$placeholders = implode(',', array_fill(0, count($keys), '?'));
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ($placeholders)");
$stmt->execute($keys);
while ($row = $stmt->fetch()) {
    $currentValues[$row['setting_key']] = $row['setting_value'];
}

$editKey = $_GET['edit'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Pages - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root { 
            --primary: #6B21A8; 
            --primary-dark: #581c87;
            --accent: #6B21A8; 
            --success: #22c55e;
            --bg: #f8f9fa; 
            --sidebar-bg: #1a1a1a;
            --text-light: #a1a1aa;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; display: flex; color: #333; }
        
        .sidebar { 
            width: 260px; 
            background: var(--sidebar-bg); 
            height: 100vh; 
            position: fixed; 
            padding: 2rem 1.5rem; 
            color: white;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }
        .brand { 
            font-size: 1.4rem; 
            font-weight: 800; 
            color: white; 
            display: flex; 
            align-items: center; 
            gap: 10px;
            margin-bottom: 3rem; 
            text-decoration: none;
        }
        .menu-item { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 1rem; 
            color: var(--text-light); 
            text-decoration: none; 
            border-radius: 12px; 
            margin-bottom: 0.5rem; 
            transition: all 0.2s; 
            font-weight: 500;
        }
        .menu-item:hover, .menu-item.active { background: #6B21A8; color: white; }
        .menu-item ion-icon { font-size: 1.2rem; }

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
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }

        .msg-success {
            background: #f0fdf4;
            color: #22c55e;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .page-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
            transition: box-shadow 0.2s, transform 0.2s;
            display: flex;
            flex-direction: column;
        }
        .page-card:hover { 
            box-shadow: 0 4px 16px rgba(0,0,0,0.08); 
            transform: translateY(-2px); 
        }

        .page-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 0.75rem;
        }

        .page-card-header ion-icon {
            font-size: 1.3rem;
            color: #6B21A8;
        }

        .page-card-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: #1a1a1a;
        }

        .page-card-desc {
            font-size: 0.82rem;
            color: #999;
            margin-bottom: 1rem;
            flex: 1;
        }

        .page-card-status {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            margin-bottom: 1rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        .status-dot.has-content { background: #22c55e; }
        .status-dot.no-content { background: #e5e5e5; }

        .page-card-actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 0.55rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.82rem;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-family: 'Inter', sans-serif;
        }
        .btn-primary { background: #6B21A8; color: white; }
        .btn-primary:hover { background: #581c87; }
        .btn-outline { background: white; color: #6B21A8; border: 1px solid #e5e5e5; }
        .btn-outline:hover { border-color: #6B21A8; }
        .btn-back { background: #f5f5f5; color: #666; }
        .btn-back:hover { background: #e5e5e5; }
        .btn-save { background: #6B21A8; color: white; padding: 0.75rem 2rem; font-size: 0.9rem; }
        .btn-save:hover { background: #581c87; }

        .editor-container {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
        }

        .editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .editor-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a1a1a;
        }

        .editor-title ion-icon { color: #6B21A8; font-size: 1.3rem; }

        .editor-tabs {
            display: flex;
            gap: 0;
            margin-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .editor-tab {
            padding: 0.75rem 1.25rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: #999;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
        }
        .editor-tab.active {
            color: #6B21A8;
            border-bottom-color: #6B21A8;
        }
        .editor-tab:hover { color: #6B21A8; }

        .editor-textarea {
            width: 100%;
            min-height: 400px;
            padding: 1rem;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 0.85rem;
            line-height: 1.6;
            resize: vertical;
            transition: border-color 0.2s;
        }
        .editor-textarea:focus { outline: none; border-color: #6B21A8; }

        .text-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s;
        }
        .text-input:focus { outline: none; border-color: #6B21A8; }

        .preview-frame {
            width: 100%;
            min-height: 400px;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            padding: 1.5rem;
            background: white;
            overflow-y: auto;
        }

        .editor-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
        }

        .char-count { font-size: 0.8rem; color: #999; }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <h1><?php echo $editKey && isset($pages[$editKey]) ? 'Edit: ' . $pages[$editKey]['title'] : 'Manage Pages'; ?></h1>
            <div>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
        </div>

        <?php if ($message): 
            $savedTitle = str_replace('saved:', '', $message);
        ?>
            <div class="msg-success">
                <ion-icon name="checkmark-circle"></ion-icon>
                "<?php echo htmlspecialchars($savedTitle); ?>" has been saved successfully.
            </div>
        <?php endif; ?>

        <?php if ($editKey && isset($pages[$editKey])): 
            $pageInfo = $pages[$editKey];
            $content = $currentValues[$editKey] ?? '';
            $isTextField = ($pageInfo['type'] ?? '') === 'text';
        ?>
            <div class="editor-container">
                <div class="editor-header">
                    <div class="editor-title">
                        <ion-icon name="<?php echo $pageInfo['icon']; ?>"></ion-icon>
                        <?php echo $pageInfo['title']; ?>
                    </div>
                    <a href="admin_pages.php" class="btn btn-back">
                        <ion-icon name="arrow-back-outline"></ion-icon> Back to Pages
                    </a>
                </div>

                <form method="POST">
                    <input type="hidden" name="page_key" value="<?php echo $editKey; ?>">

                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                    <?php if ($isTextField): ?>
                        <label style="display:block; font-weight:600; margin-bottom:0.5rem; font-size:0.9rem; color:#333;">
                            <?php echo $pageInfo['desc']; ?>
                        </label>
                        <input type="text" name="page_content" class="text-input" 
                               value="<?php echo htmlspecialchars($content); ?>" 
                               placeholder="Enter URL or text...">
                    <?php else: ?>
                        <div class="editor-tabs">
                            <div class="editor-tab active" onclick="switchTab('code')">Code Editor</div>
                            <div class="editor-tab" onclick="switchTab('preview')">Live Preview</div>
                        </div>

                        <div id="codeTab">
                            <textarea name="page_content" class="editor-textarea" id="pageEditor"
                                      placeholder="Enter page content (HTML supported)..."><?php echo htmlspecialchars($content); ?></textarea>
                        </div>

                        <div id="previewTab" style="display:none;">
                            <iframe id="previewFrame" class="preview-frame" sandbox="allow-same-origin" style="border:1px solid #e5e5e5; border-radius:10px; width:100%; min-height:400px;"></iframe>
                        </div>
                    <?php endif; ?>

                    <div class="editor-footer">
                        <div class="char-count" id="charCount">
                            <?php echo strlen($content); ?> characters
                        </div>
                        <div style="display:flex; gap:10px;">
                            <a href="<?php echo $pageInfo['url']; ?>" target="_blank" class="btn btn-outline">
                                <ion-icon name="open-outline"></ion-icon> View Page
                            </a>
                            <button type="submit" class="btn btn-save">
                                <ion-icon name="save-outline"></ion-icon> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        <?php else: ?>

            <div class="pages-grid">
                <?php foreach ($pages as $key => $page): 
                    $hasContent = !empty($currentValues[$key]);
                    $contentLength = strlen($currentValues[$key] ?? '');
                ?>
                    <div class="page-card">
                        <div class="page-card-header">
                            <ion-icon name="<?php echo $page['icon']; ?>"></ion-icon>
                            <span class="page-card-title"><?php echo $page['title']; ?></span>
                        </div>
                        <div class="page-card-desc"><?php echo $page['desc']; ?></div>
                        <div class="page-card-status">
                            <span class="status-dot <?php echo $hasContent ? 'has-content' : 'no-content'; ?>"></span>
                            <?php if ($hasContent): ?>
                                <span style="color:#22c55e;"><?php echo number_format($contentLength); ?> chars</span>
                            <?php else: ?>
                                <span style="color:#ccc;">No content yet</span>
                            <?php endif; ?>
                        </div>
                        <div class="page-card-actions">
                            <a href="admin_pages.php?edit=<?php echo $key; ?>" class="btn btn-primary">
                                <ion-icon name="create-outline"></ion-icon> Edit
                            </a>
                            <a href="<?php echo $page['url']; ?>" target="_blank" class="btn btn-outline">
                                <ion-icon name="open-outline"></ion-icon> View
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </main>

    <script>
    function switchTab(tab) {
        const codeTab = document.getElementById('codeTab');
        const previewTab = document.getElementById('previewTab');
        const tabs = document.querySelectorAll('.editor-tab');
        
        tabs.forEach(t => t.classList.remove('active'));

        if (tab === 'preview') {
            codeTab.style.display = 'none';
            previewTab.style.display = 'block';
            tabs[1].classList.add('active');
            
            const editor = document.getElementById('pageEditor');
            const previewFrame = document.getElementById('previewFrame');
            if (editor && previewFrame) {
                const doc = previewFrame.contentDocument || previewFrame.contentWindow.document;
                doc.open();
                doc.write('<!DOCTYPE html><html><head><style>body{font-family:Inter,sans-serif;padding:1rem;color:#333;line-height:1.6;}</style></head><body>' + editor.value + '</body></html>');
                doc.close();
            }
        } else {
            codeTab.style.display = 'block';
            previewTab.style.display = 'none';
            tabs[0].classList.add('active');
        }
    }

    const editor = document.getElementById('pageEditor');
    const charCount = document.getElementById('charCount');
    if (editor && charCount) {
        editor.addEventListener('input', function() {
            charCount.textContent = this.value.length + ' characters';
        });
    }
    </script>
</body>
</html>
