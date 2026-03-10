<?php
require 'includes/db.php';
require_once __DIR__ . '/includes/session.php';
$activePage = 'pages';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$message = '';
$messageType = 'success';

$builtinPages = [
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

function generateSlug($title) {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $message = 'Invalid security token. Please try again.';
        $messageType = 'error';
    } else {
        if (isset($_POST['page_key'])) {
            $key = $_POST['page_key'];
            $content = $_POST['page_content'];

            if (array_key_exists($key, $builtinPages)) {
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
                $message = '"' . $builtinPages[$key]['title'] . '" has been saved successfully.';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
        }

        if (isset($_POST['create_page'])) {
            $title = trim($_POST['new_title'] ?? '');
            $slug = generateSlug($title);
            $metaDesc = trim($_POST['new_meta_description'] ?? '');

            if (empty($title)) {
                $message = 'Page title is required.';
                $messageType = 'error';
            } elseif (empty($slug)) {
                $message = 'Invalid page title. Use alphanumeric characters.';
                $messageType = 'error';
            } else {
                $check = $pdo->prepare("SELECT COUNT(*) FROM custom_pages WHERE slug = ?");
                $check->execute([$slug]);
                if ($check->fetchColumn() > 0) {
                    $message = 'A page with the slug "' . $slug . '" already exists.';
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO custom_pages (title, slug, meta_description, content, is_published) VALUES (?, ?, ?, '', 1)");
                    $stmt->execute([$title, $slug, $metaDesc]);
                    $newId = $pdo->lastInsertId();
                    $message = 'Page "' . $title . '" created successfully.';
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header("Location: admin_pages.php?edit_custom=" . $newId . "&created=1");
                    exit;
                }
            }
        }

        if (isset($_POST['save_custom_page'])) {
            $pageId = (int)($_POST['custom_page_id'] ?? 0);
            $content = $_POST['page_content'] ?? '';
            $title = trim($_POST['custom_title'] ?? '');
            $slug = generateSlug($_POST['custom_slug'] ?? $title);
            $metaDesc = trim($_POST['custom_meta_description'] ?? '');
            $isPublished = isset($_POST['is_published']) ? 1 : 0;

            if ($pageId > 0 && !empty($title)) {
                if (empty($slug)) {
                    $message = 'Invalid slug. Use alphanumeric characters in the title or slug field.';
                    $messageType = 'error';
                } else {
                    $checkSlug = $pdo->prepare("SELECT COUNT(*) FROM custom_pages WHERE slug = ? AND id != ?");
                    $checkSlug->execute([$slug, $pageId]);
                    if ($checkSlug->fetchColumn() > 0) {
                        $message = 'Another page with this slug already exists.';
                        $messageType = 'error';
                    } else {
                        $stmt = $pdo->prepare("UPDATE custom_pages SET title = ?, slug = ?, content = ?, meta_description = ?, is_published = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->execute([$title, $slug, $content, $metaDesc, $isPublished, $pageId]);
                        $message = '"' . $title . '" has been saved successfully.';
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    }
                }
            }
        }

        if (isset($_POST['delete_page'])) {
            $pageId = (int)($_POST['delete_page_id'] ?? 0);
            if ($pageId > 0) {
                $stmt = $pdo->prepare("SELECT title FROM custom_pages WHERE id = ?");
                $stmt->execute([$pageId]);
                $deletedTitle = $stmt->fetchColumn();
                $pdo->prepare("DELETE FROM custom_pages WHERE id = ?")->execute([$pageId]);
                $message = '"' . $deletedTitle . '" has been deleted.';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
        }
    }
}

$currentValues = [];
$keys = array_keys($builtinPages);
$placeholders = implode(',', array_fill(0, count($keys), '?'));
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ($placeholders)");
$stmt->execute($keys);
while ($row = $stmt->fetch()) {
    $currentValues[$row['setting_key']] = $row['setting_value'];
}

$customPages = $pdo->query("SELECT * FROM custom_pages ORDER BY created_at DESC")->fetchAll();

$editKey = $_GET['edit'] ?? null;
$editCustomId = $_GET['edit_custom'] ?? null;
$showCreate = isset($_GET['create']);

$editCustomPage = null;
if ($editCustomId) {
    $stmt = $pdo->prepare("SELECT * FROM custom_pages WHERE id = ?");
    $stmt->execute([$editCustomId]);
    $editCustomPage = $stmt->fetch();
}

if (isset($_GET['created'])) {
    $message = 'Page created! You can now add content below.';
}
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
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }

        .msg { padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 8px; }
        .msg-success { background: #f0fdf4; color: #22c55e; }
        .msg-error { background: #fef2f2; color: #ef4444; }

        .section-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #999;
            font-weight: 700;
            margin-bottom: 1rem;
            margin-top: 0.5rem;
        }

        .pages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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
        .page-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); transform: translateY(-2px); }

        .page-card-header { display: flex; align-items: center; gap: 10px; margin-bottom: 0.75rem; }
        .page-card-header ion-icon { font-size: 1.3rem; color: #6B21A8; }
        .page-card-title { font-weight: 700; font-size: 0.95rem; color: #1a1a1a; flex: 1; }
        .page-card-desc { font-size: 0.82rem; color: #999; margin-bottom: 1rem; flex: 1; }
        .page-card-meta { font-size: 0.75rem; color: #bbb; margin-bottom: 0.75rem; }

        .page-card-status { display: flex; align-items: center; gap: 6px; font-size: 0.78rem; margin-bottom: 1rem; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; }
        .status-dot.has-content { background: #22c55e; }
        .status-dot.no-content { background: #e5e5e5; }
        .status-dot.draft { background: #f59e0b; }

        .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
        .badge-published { background: #dcfce7; color: #166534; }
        .badge-draft { background: #fef9c3; color: #854d0e; }
        .badge-builtin { background: #f3e8ff; color: #6B21A8; }

        .page-card-actions { display: flex; gap: 8px; margin-top: auto; }

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
        .btn-danger { background: #fef2f2; color: #ef4444; }
        .btn-danger:hover { background: #fee2e2; }
        .btn-create {
            background: white;
            color: #6B21A8;
            border: 2px dashed #d8b4fe;
            border-radius: 16px;
            padding: 2rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
            min-height: 180px;
        }
        .btn-create:hover { background: #faf5ff; border-color: #6B21A8; }
        .btn-create ion-icon { font-size: 2rem; }

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
        .editor-title { display: flex; align-items: center; gap: 10px; font-size: 1.1rem; font-weight: 700; color: #1a1a1a; }
        .editor-title ion-icon { color: #6B21A8; font-size: 1.3rem; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
        .form-row.full { grid-template-columns: 1fr; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.85rem; color: #333; }
        .form-group small { display: block; margin-top: 0.25rem; color: #999; font-size: 0.78rem; }

        .editor-tabs { display: flex; gap: 0; margin-bottom: 1rem; border-bottom: 2px solid #f0f0f0; }
        .editor-tab {
            padding: 0.75rem 1.25rem; font-size: 0.85rem; font-weight: 600; color: #999;
            cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s;
        }
        .editor-tab.active { color: #6B21A8; border-bottom-color: #6B21A8; }
        .editor-tab:hover { color: #6B21A8; }

        .editor-textarea {
            width: 100%; min-height: 400px; padding: 1rem; border: 1px solid #e5e5e5; border-radius: 10px;
            font-family: 'JetBrains Mono', 'Fira Code', monospace; font-size: 0.85rem; line-height: 1.6;
            resize: vertical; transition: border-color 0.2s;
        }
        .editor-textarea:focus { outline: none; border-color: #6B21A8; }

        .text-input {
            width: 100%; padding: 0.75rem 1rem; border: 1px solid #e5e5e5; border-radius: 10px;
            font-size: 0.9rem; font-family: 'Inter', sans-serif; transition: border-color 0.2s;
        }
        .text-input:focus { outline: none; border-color: #6B21A8; }

        .editor-footer {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #f0f0f0;
        }
        .char-count { font-size: 0.8rem; color: #999; }

        .toggle-row { display: flex; align-items: center; gap: 10px; margin-bottom: 1rem; }
        .toggle-switch { position: relative; display: inline-block; width: 44px; min-width: 44px; height: 24px; flex-shrink: 0; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; position: absolute; }
        .toggle-slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #e5e5e5; transition: 0.3s; border-radius: 24px;
        }
        .toggle-slider:before {
            position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px;
            background-color: white; transition: 0.3s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        input:checked + .toggle-slider { background-color: #6B21A8; }
        input:checked + .toggle-slider:before { transform: translateX(20px); }
        .toggle-label { font-size: 0.88rem; font-weight: 600; color: #333; }

        .create-form-card {
            background: white; border-radius: 16px; padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); border: 1px solid #f0f0f0;
        }

        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: white; border-radius: 16px; padding: 2rem; width: 90%; max-width: 460px;
            animation: zoomIn 0.2s ease;
        }
        @keyframes zoomIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .modal-box h3 { margin: 0 0 0.5rem; font-size: 1.1rem; color: #1a1a1a; }
        .modal-box p { color: #888; font-size: 0.88rem; margin-bottom: 1.5rem; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }

        .visual-editor-wrap { border: 1px solid #e5e5e5; border-radius: 10px; overflow: hidden; }
        .visual-editor-wrap:focus-within { border-color: #6B21A8; box-shadow: 0 0 0 3px rgba(107,33,168,0.08); }
        .visual-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 2px;
            padding: 8px 10px;
            background: #f8f8f8;
            border-bottom: 1px solid #e5e5e5;
            align-items: center;
        }
        .visual-toolbar button {
            width: 32px; height: 30px; border: none; background: none; cursor: pointer;
            border-radius: 6px; display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem; color: #555; transition: all 0.15s; font-weight: 600;
            font-family: 'Inter', serif;
        }
        .visual-toolbar button:hover { background: #e5e5e5; color: #1a1a1a; }
        .visual-toolbar button.active { background: #f3e8ff; color: #6B21A8; }
        .visual-toolbar select {
            padding: 4px 8px; border: 1px solid #e5e5e5; border-radius: 6px;
            font-size: 0.8rem; font-family: 'Inter', sans-serif; background: white;
            cursor: pointer; color: #555; outline: none;
        }
        .visual-toolbar select:focus { border-color: #6B21A8; }
        .toolbar-sep { width: 1px; height: 20px; background: #ddd; margin: 0 4px; }
        .visual-editor-area {
            min-height: 400px; max-height: 600px; overflow-y: auto;
            padding: 1.2rem; outline: none; font-size: 0.9rem;
            font-family: 'Inter', sans-serif; line-height: 1.7; color: #333; background: white;
        }
        .visual-editor-area:empty::before { content: attr(data-placeholder); color: #bbb; }
        .visual-editor-area h1 { font-size: 1.8rem; margin: 0.6em 0 0.3em; }
        .visual-editor-area h2 { font-size: 1.4rem; margin: 0.5em 0 0.3em; }
        .visual-editor-area h3 { font-size: 1.15rem; margin: 0.5em 0 0.3em; }
        .visual-editor-area blockquote {
            border-left: 3px solid #a855f7; margin: 0.5em 0; padding: 0.5em 1em;
            background: #faf5ff; border-radius: 0 8px 8px 0; color: #555;
        }
        .visual-editor-area a { color: #6B21A8; }
        .visual-editor-area ul, .visual-editor-area ol { padding-left: 1.5em; }
        .visual-editor-area img { max-width: 100%; border-radius: 8px; }
        .visual-editor-area pre {
            background: #1a1a1a; color: #e5e5e5; padding: 1rem; border-radius: 8px;
            font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; overflow-x: auto;
        }
        .visual-editor-area table { border-collapse: collapse; width: 100%; }
        .visual-editor-area td, .visual-editor-area th {
            border: 1px solid #e5e5e5; padding: 8px 12px; text-align: left;
        }
        .visual-editor-area th { background: #f8f8f8; font-weight: 600; }
        .color-btn-wrap { position: relative; }
        .color-btn-wrap input[type="color"] {
            position: absolute; bottom: -4px; left: 0; width: 32px; height: 0; border: none;
            padding: 0; opacity: 0; cursor: pointer;
        }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <?php
        $headerTitle = 'Manage Pages';
        if ($editKey && isset($builtinPages[$editKey])) $headerTitle = 'Edit: ' . $builtinPages[$editKey]['title'];
        elseif ($editCustomPage) $headerTitle = 'Edit: ' . htmlspecialchars($editCustomPage['title']);
        elseif ($showCreate) $headerTitle = 'Create New Page';
        ?>
        <div class="header">
            <h1><?php echo $headerTitle; ?></h1>
            <?php if (!$editKey && !$editCustomPage && !$showCreate): ?>
                <a href="admin_pages.php?create=1" class="btn btn-primary">
                    <ion-icon name="add-circle-outline"></ion-icon> Create New Page
                </a>
            <?php else: ?>
                <div>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="msg msg-<?php echo $messageType; ?>">
                <ion-icon name="<?php echo $messageType === 'success' ? 'checkmark-circle' : 'alert-circle'; ?>"></ion-icon>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($showCreate): ?>

            <div class="create-form-card">
                <div class="editor-header">
                    <div class="editor-title">
                        <ion-icon name="add-circle-outline"></ion-icon>
                        Create a New Page
                    </div>
                    <a href="admin_pages.php" class="btn btn-back">
                        <ion-icon name="arrow-back-outline"></ion-icon> Back to Pages
                    </a>
                </div>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="create_page" value="1">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Page Title</label>
                            <input type="text" name="new_title" class="text-input" required placeholder="e.g. Shipping Policy" id="newTitleInput">
                        </div>
                        <div class="form-group">
                            <label>URL Slug</label>
                            <input type="text" class="text-input" id="slugPreview" disabled placeholder="auto-generated">
                            <small>Page URL: /page.php?slug=<span id="slugText">...</span></small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Meta Description (optional)</label>
                        <input type="text" name="new_meta_description" class="text-input" placeholder="Brief description for SEO" maxlength="160">
                    </div>

                    <div style="display:flex; gap:10px; margin-top:1.5rem;">
                        <button type="submit" class="btn btn-primary" style="padding:0.75rem 2rem;">
                            <ion-icon name="add-circle-outline"></ion-icon> Create Page
                        </button>
                        <a href="admin_pages.php" class="btn btn-back">Cancel</a>
                    </div>
                </form>
            </div>

        <?php elseif ($editKey && isset($builtinPages[$editKey])): 
            $pageInfo = $builtinPages[$editKey];
            $content = $currentValues[$editKey] ?? '';
            $isTextField = ($pageInfo['type'] ?? '') === 'text';
        ?>
            <div class="editor-container">
                <div class="editor-header">
                    <div class="editor-title">
                        <ion-icon name="<?php echo $pageInfo['icon']; ?>"></ion-icon>
                        <?php echo $pageInfo['title']; ?>
                        <span class="badge badge-builtin">Built-in</span>
                    </div>
                    <a href="admin_pages.php" class="btn btn-back">
                        <ion-icon name="arrow-back-outline"></ion-icon> Back to Pages
                    </a>
                </div>

                <form method="POST">
                    <input type="hidden" name="page_key" value="<?php echo $editKey; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <?php if ($isTextField): ?>
                        <div class="form-group">
                            <label><?php echo $pageInfo['desc']; ?></label>
                            <input type="text" name="page_content" class="text-input" 
                                   value="<?php echo htmlspecialchars($content); ?>" 
                                   placeholder="Enter URL or text...">
                        </div>
                    <?php else: ?>
                        <div class="editor-tabs">
                            <div class="editor-tab active" onclick="switchTab('visual')">Visual Editor</div>
                            <div class="editor-tab" onclick="switchTab('code')">Code Editor</div>
                            <div class="editor-tab" onclick="switchTab('preview')">Live Preview</div>
                        </div>

                        <div id="visualTab">
                            <div class="visual-editor-wrap">
                                <div class="visual-toolbar">
                                    <select onchange="veCmd('formatBlock',this.value);this.selectedIndex=0;" title="Block Type">
                                        <option value="">Format</option>
                                        <option value="<p>">Paragraph</option>
                                        <option value="<h1>">Heading 1</option>
                                        <option value="<h2>">Heading 2</option>
                                        <option value="<h3>">Heading 3</option>
                                        <option value="<blockquote>">Quote</option>
                                        <option value="<pre>">Code Block</option>
                                    </select>
                                    <select onchange="veCmd('fontSize',this.value);this.selectedIndex=0;" title="Font Size">
                                        <option value="">Size</option>
                                        <option value="1">Small</option>
                                        <option value="3">Normal</option>
                                        <option value="5">Large</option>
                                        <option value="7">Huge</option>
                                    </select>
                                    <div class="toolbar-sep"></div>
                                    <button type="button" onclick="veCmd('bold')" title="Bold"><b>B</b></button>
                                    <button type="button" onclick="veCmd('italic')" title="Italic"><i>I</i></button>
                                    <button type="button" onclick="veCmd('underline')" title="Underline"><u>U</u></button>
                                    <button type="button" onclick="veCmd('strikeThrough')" title="Strikethrough"><s>S</s></button>
                                    <div class="toolbar-sep"></div>
                                    <div class="color-btn-wrap">
                                        <button type="button" onclick="this.nextElementSibling.click()" title="Text Color" style="color:var(--primary);">A</button>
                                        <input type="color" value="#6B21A8" onchange="veCmd('foreColor',this.value)">
                                    </div>
                                    <div class="color-btn-wrap">
                                        <button type="button" onclick="this.nextElementSibling.click()" title="Highlight" style="background:#fef08a;border-radius:4px;width:28px;height:26px;font-size:0.78rem;">H</button>
                                        <input type="color" value="#fef08a" onchange="veCmd('hiliteColor',this.value)">
                                    </div>
                                    <div class="toolbar-sep"></div>
                                    <button type="button" onclick="veCmd('justifyLeft')" title="Align Left"><ion-icon name="reorder-two-outline"></ion-icon></button>
                                    <button type="button" onclick="veCmd('justifyCenter')" title="Align Center"><ion-icon name="reorder-three-outline"></ion-icon></button>
                                    <button type="button" onclick="veCmd('justifyRight')" title="Align Right"><ion-icon name="reorder-two-outline" style="transform:scaleX(-1)"></ion-icon></button>
                                    <button type="button" onclick="veCmd('justifyFull')" title="Justify"><ion-icon name="reorder-four-outline"></ion-icon></button>
                                    <div class="toolbar-sep"></div>
                                    <button type="button" onclick="veCmd('insertUnorderedList')" title="Bullet List"><ion-icon name="list-outline"></ion-icon></button>
                                    <button type="button" onclick="veCmd('insertOrderedList')" title="Numbered List"><ion-icon name="reorder-four-outline"></ion-icon></button>
                                    <button type="button" onclick="veCmd('indent')" title="Indent"><ion-icon name="arrow-forward-outline" style="font-size:0.9rem;"></ion-icon></button>
                                    <button type="button" onclick="veCmd('outdent')" title="Outdent"><ion-icon name="arrow-back-outline" style="font-size:0.9rem;"></ion-icon></button>
                                    <div class="toolbar-sep"></div>
                                    <button type="button" onclick="veInsertLink()" title="Insert Link"><ion-icon name="link-outline"></ion-icon></button>
                                    <button type="button" onclick="veInsertImage()" title="Insert Image"><ion-icon name="image-outline"></ion-icon></button>
                                    <button type="button" onclick="veInsertHR()" title="Horizontal Line"><ion-icon name="remove-outline"></ion-icon></button>
                                    <button type="button" onclick="veInsertTable()" title="Insert Table"><ion-icon name="grid-outline"></ion-icon></button>
                                    <div class="toolbar-sep"></div>
                                    <button type="button" onclick="veCmd('removeFormat')" title="Clear Formatting"><ion-icon name="close-circle-outline"></ion-icon></button>
                                    <button type="button" onclick="veCmd('undo')" title="Undo"><ion-icon name="arrow-undo-outline"></ion-icon></button>
                                    <button type="button" onclick="veCmd('redo')" title="Redo"><ion-icon name="arrow-redo-outline"></ion-icon></button>
                                </div>
                                <div class="visual-editor-area" id="visualEditor" contenteditable="true" data-placeholder="Start typing your page content..."><?php echo $content; ?></div>
                            </div>
                        </div>

                        <div id="codeTab" style="display:none;">
                            <textarea name="page_content" class="editor-textarea" id="pageEditor"
                                      placeholder="Enter page content (HTML supported)..."><?php echo htmlspecialchars($content); ?></textarea>
                        </div>

                        <div id="previewTab" style="display:none;">
                            <iframe id="previewFrame" sandbox="allow-same-origin" style="border:1px solid #e5e5e5; border-radius:10px; width:100%; min-height:400px;"></iframe>
                        </div>
                    <?php endif; ?>

                    <div class="editor-footer">
                        <div class="char-count" id="charCount"><?php echo strlen($content); ?> characters</div>
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

        <?php elseif ($editCustomPage): ?>
            <div class="editor-container">
                <div class="editor-header">
                    <div class="editor-title">
                        <ion-icon name="create-outline"></ion-icon>
                        <?php echo htmlspecialchars($editCustomPage['title']); ?>
                        <?php if ($editCustomPage['is_published']): ?>
                            <span class="badge badge-published">Published</span>
                        <?php else: ?>
                            <span class="badge badge-draft">Draft</span>
                        <?php endif; ?>
                    </div>
                    <a href="admin_pages.php" class="btn btn-back">
                        <ion-icon name="arrow-back-outline"></ion-icon> Back to Pages
                    </a>
                </div>

                <form method="POST">
                    <input type="hidden" name="save_custom_page" value="1">
                    <input type="hidden" name="custom_page_id" value="<?php echo $editCustomPage['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Page Title</label>
                            <input type="text" name="custom_title" class="text-input" required
                                   value="<?php echo htmlspecialchars($editCustomPage['title']); ?>">
                        </div>
                        <div class="form-group">
                            <label>URL Slug</label>
                            <input type="text" name="custom_slug" class="text-input" required
                                   value="<?php echo htmlspecialchars($editCustomPage['slug']); ?>">
                            <small>URL: /page.php?slug=<?php echo htmlspecialchars($editCustomPage['slug']); ?></small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Meta Description</label>
                            <input type="text" name="custom_meta_description" class="text-input"
                                   value="<?php echo htmlspecialchars($editCustomPage['meta_description']); ?>"
                                   placeholder="Brief SEO description" maxlength="160">
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="toggle-row" style="margin-top:0.5rem;">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="is_published" value="1" <?php echo $editCustomPage['is_published'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span class="toggle-label">Published</span>
                            </div>
                        </div>
                    </div>

                    <div class="editor-tabs">
                        <div class="editor-tab active" onclick="switchTab('visual')">Visual Editor</div>
                        <div class="editor-tab" onclick="switchTab('code')">Code Editor</div>
                        <div class="editor-tab" onclick="switchTab('preview')">Live Preview</div>
                    </div>

                    <div id="visualTab">
                        <div class="visual-editor-wrap">
                            <div class="visual-toolbar">
                                <select onchange="veCmd('formatBlock',this.value);this.selectedIndex=0;" title="Block Type">
                                    <option value="">Format</option>
                                    <option value="<p>">Paragraph</option>
                                    <option value="<h1>">Heading 1</option>
                                    <option value="<h2>">Heading 2</option>
                                    <option value="<h3>">Heading 3</option>
                                    <option value="<blockquote>">Quote</option>
                                    <option value="<pre>">Code Block</option>
                                </select>
                                <select onchange="veCmd('fontSize',this.value);this.selectedIndex=0;" title="Font Size">
                                    <option value="">Size</option>
                                    <option value="1">Small</option>
                                    <option value="3">Normal</option>
                                    <option value="5">Large</option>
                                    <option value="7">Huge</option>
                                </select>
                                <div class="toolbar-sep"></div>
                                <button type="button" onclick="veCmd('bold')" title="Bold"><b>B</b></button>
                                <button type="button" onclick="veCmd('italic')" title="Italic"><i>I</i></button>
                                <button type="button" onclick="veCmd('underline')" title="Underline"><u>U</u></button>
                                <button type="button" onclick="veCmd('strikeThrough')" title="Strikethrough"><s>S</s></button>
                                <div class="toolbar-sep"></div>
                                <div class="color-btn-wrap">
                                    <button type="button" onclick="this.nextElementSibling.click()" title="Text Color" style="color:var(--primary);">A</button>
                                    <input type="color" value="#6B21A8" onchange="veCmd('foreColor',this.value)">
                                </div>
                                <div class="color-btn-wrap">
                                    <button type="button" onclick="this.nextElementSibling.click()" title="Highlight" style="background:#fef08a;border-radius:4px;width:28px;height:26px;font-size:0.78rem;">H</button>
                                    <input type="color" value="#fef08a" onchange="veCmd('hiliteColor',this.value)">
                                </div>
                                <div class="toolbar-sep"></div>
                                <button type="button" onclick="veCmd('justifyLeft')" title="Align Left"><ion-icon name="reorder-two-outline"></ion-icon></button>
                                <button type="button" onclick="veCmd('justifyCenter')" title="Align Center"><ion-icon name="reorder-three-outline"></ion-icon></button>
                                <button type="button" onclick="veCmd('justifyRight')" title="Align Right"><ion-icon name="reorder-two-outline" style="transform:scaleX(-1)"></ion-icon></button>
                                <button type="button" onclick="veCmd('justifyFull')" title="Justify"><ion-icon name="reorder-four-outline"></ion-icon></button>
                                <div class="toolbar-sep"></div>
                                <button type="button" onclick="veCmd('insertUnorderedList')" title="Bullet List"><ion-icon name="list-outline"></ion-icon></button>
                                <button type="button" onclick="veCmd('insertOrderedList')" title="Numbered List"><ion-icon name="reorder-four-outline"></ion-icon></button>
                                <button type="button" onclick="veCmd('indent')" title="Indent"><ion-icon name="arrow-forward-outline" style="font-size:0.9rem;"></ion-icon></button>
                                <button type="button" onclick="veCmd('outdent')" title="Outdent"><ion-icon name="arrow-back-outline" style="font-size:0.9rem;"></ion-icon></button>
                                <div class="toolbar-sep"></div>
                                <button type="button" onclick="veInsertLink()" title="Insert Link"><ion-icon name="link-outline"></ion-icon></button>
                                <button type="button" onclick="veInsertImage()" title="Insert Image"><ion-icon name="image-outline"></ion-icon></button>
                                <button type="button" onclick="veInsertHR()" title="Horizontal Line"><ion-icon name="remove-outline"></ion-icon></button>
                                <button type="button" onclick="veInsertTable()" title="Insert Table"><ion-icon name="grid-outline"></ion-icon></button>
                                <div class="toolbar-sep"></div>
                                <button type="button" onclick="veCmd('removeFormat')" title="Clear Formatting"><ion-icon name="close-circle-outline"></ion-icon></button>
                                <button type="button" onclick="veCmd('undo')" title="Undo"><ion-icon name="arrow-undo-outline"></ion-icon></button>
                                <button type="button" onclick="veCmd('redo')" title="Redo"><ion-icon name="arrow-redo-outline"></ion-icon></button>
                            </div>
                            <div class="visual-editor-area" id="visualEditor" contenteditable="true" data-placeholder="Start typing your page content..."><?php echo $editCustomPage['content']; ?></div>
                        </div>
                    </div>

                    <div id="codeTab" style="display:none;">
                        <textarea name="page_content" class="editor-textarea" id="pageEditor"
                                  placeholder="Enter page content (HTML supported)..."><?php echo htmlspecialchars($editCustomPage['content']); ?></textarea>
                    </div>

                    <div id="previewTab" style="display:none;">
                        <iframe id="previewFrame" sandbox="allow-same-origin" style="border:1px solid #e5e5e5; border-radius:10px; width:100%; min-height:400px;"></iframe>
                    </div>

                    <div class="editor-footer">
                        <div class="char-count" id="charCount"><?php echo strlen($editCustomPage['content']); ?> characters</div>
                        <div style="display:flex; gap:10px;">
                            <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $editCustomPage['id']; ?>, '<?php echo htmlspecialchars(addslashes($editCustomPage['title'])); ?>')">
                                <ion-icon name="trash-outline"></ion-icon> Delete
                            </button>
                            <a href="page.php?slug=<?php echo urlencode($editCustomPage['slug']); ?>" target="_blank" class="btn btn-outline">
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

            <?php if (count($customPages) > 0): ?>
                <div class="section-label">Custom Pages (<?php echo count($customPages); ?>)</div>
                <div class="pages-grid">
                    <?php foreach ($customPages as $cp): 
                        $hasContent = !empty($cp['content']);
                    ?>
                        <div class="page-card">
                            <div class="page-card-header">
                                <ion-icon name="document-outline"></ion-icon>
                                <span class="page-card-title"><?php echo htmlspecialchars($cp['title']); ?></span>
                                <?php if ($cp['is_published']): ?>
                                    <span class="badge badge-published">Live</span>
                                <?php else: ?>
                                    <span class="badge badge-draft">Draft</span>
                                <?php endif; ?>
                            </div>
                            <div class="page-card-desc">/page.php?slug=<?php echo htmlspecialchars($cp['slug']); ?></div>
                            <div class="page-card-status">
                                <span class="status-dot <?php echo $hasContent ? 'has-content' : ($cp['is_published'] ? 'no-content' : 'draft'); ?>"></span>
                                <?php if ($hasContent): ?>
                                    <span style="color:#22c55e;"><?php echo number_format(strlen($cp['content'])); ?> chars</span>
                                <?php else: ?>
                                    <span style="color:#ccc;">No content yet</span>
                                <?php endif; ?>
                                <span style="color:#ddd; margin-left:auto;"><?php echo date('M j, Y', strtotime($cp['updated_at'])); ?></span>
                            </div>
                            <div class="page-card-actions">
                                <a href="admin_pages.php?edit_custom=<?php echo $cp['id']; ?>" class="btn btn-primary">
                                    <ion-icon name="create-outline"></ion-icon> Edit
                                </a>
                                <a href="page.php?slug=<?php echo urlencode($cp['slug']); ?>" target="_blank" class="btn btn-outline">
                                    <ion-icon name="open-outline"></ion-icon> View
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <a href="admin_pages.php?create=1" class="btn-create">
                        <ion-icon name="add-circle-outline"></ion-icon>
                        Create New Page
                    </a>
                </div>
            <?php else: ?>
                <div class="pages-grid">
                    <a href="admin_pages.php?create=1" class="btn-create">
                        <ion-icon name="add-circle-outline"></ion-icon>
                        Create Your First Page
                    </a>
                </div>
            <?php endif; ?>

            <div class="section-label">Built-in Pages</div>
            <div class="pages-grid">
                <?php foreach ($builtinPages as $key => $page): 
                    $hasContent = !empty($currentValues[$key]);
                    $contentLength = strlen($currentValues[$key] ?? '');
                ?>
                    <div class="page-card">
                        <div class="page-card-header">
                            <ion-icon name="<?php echo $page['icon']; ?>"></ion-icon>
                            <span class="page-card-title"><?php echo $page['title']; ?></span>
                            <span class="badge badge-builtin">Built-in</span>
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

    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <h3>Delete Page</h3>
            <p>Are you sure you want to delete "<span id="deletePageName"></span>"? This cannot be undone.</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="delete_page" value="1">
                <input type="hidden" name="delete_page_id" id="deletePageId" value="">
                <div class="modal-actions">
                    <button type="button" class="btn btn-back" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger" style="padding:0.65rem 1.5rem;">
                        <ion-icon name="trash-outline"></ion-icon> Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    var activeTab = 'visual';

    function switchTab(tab) {
        var visualTab = document.getElementById('visualTab');
        var codeTab = document.getElementById('codeTab');
        var previewTab = document.getElementById('previewTab');
        var tabs = document.querySelectorAll('.editor-tab');
        var ve = document.getElementById('visualEditor');
        var ce = document.getElementById('pageEditor');

        tabs.forEach(function(t) { t.classList.remove('active'); });

        if (visualTab) visualTab.style.display = 'none';
        if (codeTab) codeTab.style.display = 'none';
        if (previewTab) previewTab.style.display = 'none';

        if (tab === 'visual') {
            if (visualTab) visualTab.style.display = 'block';
            tabs[0].classList.add('active');
            if (activeTab === 'code' && ce && ve) {
                ve.innerHTML = ce.value;
            }
        } else if (tab === 'code') {
            if (codeTab) codeTab.style.display = 'block';
            tabs[1].classList.add('active');
            if (activeTab === 'visual' && ve && ce) {
                ce.value = ve.innerHTML;
            }
        } else if (tab === 'preview') {
            if (previewTab) previewTab.style.display = 'block';
            tabs[2].classList.add('active');
            var html = '';
            if (activeTab === 'visual' && ve) {
                html = ve.innerHTML;
                if (ce) ce.value = html;
            } else if (ce) {
                html = ce.value;
            }
            var previewFrame = document.getElementById('previewFrame');
            if (previewFrame) {
                var doc = previewFrame.contentDocument || previewFrame.contentWindow.document;
                doc.open();
                doc.write('<!DOCTYPE html><html><head><style>body{font-family:Inter,sans-serif;padding:1.5rem;color:#333;line-height:1.7;max-width:800px;margin:0 auto;}img{max-width:100%;border-radius:8px;}blockquote{border-left:3px solid #a855f7;margin:0.5em 0;padding:0.5em 1em;background:#faf5ff;border-radius:0 8px 8px 0;}a{color:#6B21A8;}table{border-collapse:collapse;width:100%;}td,th{border:1px solid #e5e5e5;padding:8px 12px;}th{background:#f8f8f8;font-weight:600;}</style></head><body>' + html + '</body></html>');
                doc.close();
            }
        }
        activeTab = tab;
        updateCharCount();
    }

    function veCmd(cmd, val) {
        document.execCommand(cmd, false, val || null);
        var ve = document.getElementById('visualEditor');
        if (ve) ve.focus();
    }

    function veInsertLink() {
        var url = prompt('Enter URL:', 'https://');
        if (url) document.execCommand('createLink', false, url);
    }

    function veInsertImage() {
        var url = prompt('Enter image URL:', 'https://');
        if (url) document.execCommand('insertImage', false, url);
    }

    function veInsertHR() {
        document.execCommand('insertHTML', false, '<hr>');
    }

    function veInsertTable() {
        var rows = prompt('Number of rows:', '3');
        var cols = prompt('Number of columns:', '3');
        if (rows && cols) {
            var r = parseInt(rows), c = parseInt(cols);
            if (r > 0 && c > 0 && r <= 20 && c <= 10) {
                var html = '<table><thead><tr>';
                for (var j = 0; j < c; j++) html += '<th>Header ' + (j+1) + '</th>';
                html += '</tr></thead><tbody>';
                for (var i = 0; i < r-1; i++) {
                    html += '<tr>';
                    for (var j = 0; j < c; j++) html += '<td>&nbsp;</td>';
                    html += '</tr>';
                }
                html += '</tbody></table><p>&nbsp;</p>';
                document.execCommand('insertHTML', false, html);
            }
        }
    }

    function updateCharCount() {
        var charCount = document.getElementById('charCount');
        var ve = document.getElementById('visualEditor');
        var ce = document.getElementById('pageEditor');
        if (!charCount) return;
        var len = 0;
        if (activeTab === 'visual' && ve) len = ve.innerHTML.length;
        else if (ce) len = ce.value.length;
        charCount.textContent = len + ' characters';
    }

    var ve = document.getElementById('visualEditor');
    var ce = document.getElementById('pageEditor');

    if (ve) {
        ve.addEventListener('input', function() {
            if (ce) ce.value = this.innerHTML;
            updateCharCount();
        });
    }
    if (ce) {
        ce.addEventListener('input', updateCharCount);
    }

    var form = document.querySelector('.editor-container form, .create-form-card form');
    if (form && ve && ce) {
        form.addEventListener('submit', function() {
            if (activeTab === 'visual') {
                ce.value = ve.innerHTML;
            }
        });
    }

    var titleInput = document.getElementById('newTitleInput');
    var slugPreview = document.getElementById('slugPreview');
    var slugText = document.getElementById('slugText');
    if (titleInput) {
        titleInput.addEventListener('input', function() {
            var slug = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
            if (slugPreview) slugPreview.value = slug;
            if (slugText) slugText.textContent = slug || '...';
        });
    }

    function confirmDelete(id, name) {
        document.getElementById('deletePageId').value = id;
        document.getElementById('deletePageName').textContent = name;
        document.getElementById('deleteModal').classList.add('active');
    }
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('active');
    }
    document.getElementById('deleteModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });
    </script>
</body>
</html>
