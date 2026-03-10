<?php
require_once __DIR__ . '/includes/session.php';
require 'includes/db.php';
require_once 'includes/config.php';

$activePage = 'filemanager';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$uploadDir = __DIR__ . '/uploads/files/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$message = '';
$messageType = 'success';

$allowedExt = ['jpg','jpeg','png','gif','webp','ico','bmp','pdf','doc','docx','xls','xlsx','csv','txt','zip','mp4','mp3','woff','woff2','ttf','eot'];
$maxFileSize = 20 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $message = 'Invalid security token.';
        $messageType = 'error';
    } else {
        if (isset($_POST['delete_file'])) {
            $filename = basename($_POST['delete_file']);
            $filepath = $uploadDir . $filename;
            if (file_exists($filepath) && is_file($filepath)) {
                unlink($filepath);
                $message = 'File "' . htmlspecialchars($filename) . '" deleted.';
            } else {
                $message = 'File not found.';
                $messageType = 'error';
            }
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        if (isset($_FILES['upload_files'])) {
            $uploaded = 0;
            $errors = [];
            $files = $_FILES['upload_files'];
            $count = is_array($files['name']) ? count($files['name']) : 0;

            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    $errors[] = $files['name'][$i] . ': Upload error.';
                    continue;
                }
                if ($files['size'][$i] > $maxFileSize) {
                    $errors[] = $files['name'][$i] . ': Exceeds 20MB limit.';
                    continue;
                }
                $originalName = $files['name'][$i];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt)) {
                    $errors[] = $originalName . ': File type not allowed.';
                    continue;
                }
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $detectedMime = $finfo->file($files['tmp_name'][$i]);
                $safeMimes = [
                    'image/jpeg','image/png','image/gif','image/webp','image/x-icon','image/bmp','image/vnd.microsoft.icon',
                    'application/pdf',
                    'application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text/csv','text/plain',
                    'application/zip','application/x-zip-compressed',
                    'video/mp4','audio/mpeg',
                    'font/woff','font/woff2','font/ttf','application/vnd.ms-fontobject',
                    'application/octet-stream',
                ];
                if (!in_array($detectedMime, $safeMimes)) {
                    $errors[] = $originalName . ': File content type not allowed (' . $detectedMime . ').';
                    continue;
                }
                if (in_array($ext, ['jpg','jpeg','png','gif','webp','bmp','ico'])) {
                    $imgCheck = @getimagesize($files['tmp_name'][$i]);
                    if ($imgCheck === false && $ext !== 'ico') {
                        $errors[] = $originalName . ': Invalid image file.';
                        continue;
                    }
                }
                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
                $safeName = substr($safeName, 0, 100);
                $finalName = $safeName . '.' . $ext;
                if (file_exists($uploadDir . $finalName)) {
                    $finalName = $safeName . '_' . time() . '.' . $ext;
                }
                if (move_uploaded_file($files['tmp_name'][$i], $uploadDir . $finalName)) {
                    $uploaded++;
                } else {
                    $errors[] = $originalName . ': Failed to save.';
                }
            }

            if ($uploaded > 0) {
                $message = $uploaded . ' file(s) uploaded successfully.';
            }
            if (!empty($errors)) {
                $message .= ($message ? ' ' : '') . implode(' ', $errors);
                if ($uploaded === 0) $messageType = 'error';
            }
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
}

$files = [];
if (is_dir($uploadDir)) {
    $items = scandir($uploadDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $uploadDir . $item;
        if (is_file($path)) {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp','svg','bmp','ico']);
            $files[] = [
                'name' => $item,
                'size' => filesize($path),
                'modified' => filemtime($path),
                'ext' => $ext,
                'is_image' => $isImage,
            ];
        }
    }
}

usort($files, function($a, $b) {
    return $b['modified'] - $a['modified'];
});

function formatFileSize($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

$baseUrl = '';
if (defined('SITE_ROOT_URL') && SITE_ROOT_URL && SITE_ROOT_URL !== 'http://localhost:8000') {
    $baseUrl = rtrim(SITE_ROOT_URL, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager - Listaria Admin</title>
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

        .msg { padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; }
        .msg-success { background: #f0fdf4; color: #166534; }
        .msg-error { background: #fef2f2; color: #ef4444; }

        .upload-zone {
            background: white;
            border: 2px dashed #d8b4fe;
            border-radius: 16px;
            padding: 2.5rem;
            text-align: center;
            margin-bottom: 2rem;
            transition: all 0.2s;
            cursor: pointer;
            position: relative;
        }
        .upload-zone:hover, .upload-zone.dragover {
            border-color: var(--primary);
            background: #faf5ff;
        }
        .upload-zone ion-icon { font-size: 2.5rem; color: var(--primary); margin-bottom: 0.5rem; }
        .upload-zone h3 { font-size: 1rem; color: #333; margin-bottom: 0.3rem; }
        .upload-zone p { font-size: 0.82rem; color: #999; }
        .upload-zone input[type="file"] {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            opacity: 0; cursor: pointer;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.82rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-sm { padding: 0.35rem 0.7rem; font-size: 0.75rem; }
        .btn-outline { background: white; color: #555; border: 1px solid #e5e5e5; }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
        .btn-danger { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }
        .btn-danger:hover { background: #fee2e2; }

        .stats-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            border: 1px solid #f0f0f0;
            flex: 1;
        }
        .stat-card .stat-val { font-size: 1.3rem; font-weight: 700; color: #1a1a1a; }
        .stat-card .stat-label { font-size: 0.75rem; color: #999; margin-top: 2px; }

        .files-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .file-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #f0f0f0;
            overflow: hidden;
            transition: all 0.2s;
        }
        .file-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); transform: translateY(-2px); }

        .file-preview {
            height: 140px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .file-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .file-preview .file-icon {
            font-size: 2.5rem;
            color: #ccc;
        }
        .file-ext-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            background: #f3e8ff;
            color: var(--primary);
        }

        .file-info {
            padding: 0.8rem;
        }
        .file-name {
            font-size: 0.82rem;
            font-weight: 600;
            color: #1a1a1a;
            word-break: break-all;
            margin-bottom: 0.3rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .file-meta {
            font-size: 0.72rem;
            color: #999;
            margin-bottom: 0.6rem;
        }
        .file-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .url-display {
            display: none;
            margin-top: 6px;
            background: #f8f9fa;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 6px 8px;
            font-size: 0.72rem;
            font-family: 'JetBrains Mono', monospace;
            word-break: break-all;
            color: #555;
        }
        .url-display.show { display: block; }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #999;
        }
        .empty-state ion-icon { font-size: 3rem; color: #ddd; margin-bottom: 1rem; }
        .empty-state h3 { font-weight: 600; color: #666; margin-bottom: 0.3rem; }

        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: #1a1a1a;
            color: white;
            padding: 0.8rem 1.2rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 500;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            z-index: 9999;
            display: none;
            animation: toastIn 0.3s ease;
        }
        @keyframes toastIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .view-toggle {
            display: flex;
            gap: 4px;
            background: #f0f0f0;
            border-radius: 8px;
            padding: 3px;
        }
        .view-toggle button {
            border: none;
            background: none;
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
            color: #888;
            font-size: 1rem;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        .view-toggle button.active {
            background: white;
            color: var(--primary);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .files-list { display: none; }
        .files-list table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #f0f0f0;
        }
        .files-list th {
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
        .files-list td {
            padding: 0.7rem 1rem;
            font-size: 0.85rem;
            border-bottom: 1px solid #f8f8f8;
            vertical-align: middle;
        }
        .files-list tr:hover td { background: #faf5ff; }

        @media (max-width: 768px) {
            .files-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
            .stats-row { flex-direction: column; }
        }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <h1><ion-icon name="folder-open-outline" style="vertical-align:middle;margin-right:8px;color:var(--primary);"></ion-icon> File Manager</h1>
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="view-toggle">
                    <button class="active" onclick="setView('grid')" id="viewGrid"><ion-icon name="grid-outline"></ion-icon></button>
                    <button onclick="setView('list')" id="viewList"><ion-icon name="list-outline"></ion-icon></button>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="msg msg-<?php echo $messageType; ?>">
                <ion-icon name="<?php echo $messageType === 'success' ? 'checkmark-circle' : 'alert-circle'; ?>"></ion-icon>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="upload-zone" id="uploadZone">
                <input type="file" name="upload_files[]" multiple id="fileInput" onchange="document.getElementById('uploadForm').submit();">
                <ion-icon name="cloud-upload-outline"></ion-icon>
                <h3>Drop files here or click to upload</h3>
                <p>Max 20MB per file &bull; Images, documents, fonts, media</p>
            </div>
        </form>

        <?php
            $totalSize = 0;
            $imageCount = 0;
            foreach ($files as $f) {
                $totalSize += $f['size'];
                if ($f['is_image']) $imageCount++;
            }
        ?>
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-val"><?php echo count($files); ?></div>
                <div class="stat-label">Total Files</div>
            </div>
            <div class="stat-card">
                <div class="stat-val"><?php echo $imageCount; ?></div>
                <div class="stat-label">Images</div>
            </div>
            <div class="stat-card">
                <div class="stat-val"><?php echo formatFileSize($totalSize); ?></div>
                <div class="stat-label">Total Size</div>
            </div>
        </div>

        <?php if (empty($files)): ?>
            <div class="empty-state">
                <ion-icon name="cloud-upload-outline"></ion-icon>
                <h3>No files uploaded yet</h3>
                <p>Upload your first file using the area above</p>
            </div>
        <?php else: ?>

            <div class="files-grid" id="filesGrid">
                <?php foreach ($files as $f): ?>
                    <?php $fileUrl = 'uploads/files/' . rawurlencode($f['name']); ?>
                    <div class="file-card">
                        <div class="file-preview">
                            <?php if ($f['is_image']): ?>
                                <img src="<?php echo htmlspecialchars($fileUrl); ?>" alt="<?php echo htmlspecialchars($f['name']); ?>" loading="lazy">
                            <?php else: ?>
                                <div style="text-align:center;">
                                    <ion-icon name="document-outline" class="file-icon"></ion-icon>
                                    <div style="margin-top:4px;"><span class="file-ext-badge"><?php echo htmlspecialchars($f['ext']); ?></span></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="file-info">
                            <div class="file-name" title="<?php echo htmlspecialchars($f['name']); ?>"><?php echo htmlspecialchars($f['name']); ?></div>
                            <div class="file-meta"><?php echo formatFileSize($f['size']); ?> &bull; <?php echo date('M j, Y', $f['modified']); ?></div>
                            <div class="file-actions">
                                <button class="btn btn-outline btn-sm" onclick="copyUrl('<?php echo htmlspecialchars($fileUrl, ENT_QUOTES); ?>', this)" title="Copy URL">
                                    <ion-icon name="link-outline"></ion-icon> URL
                                </button>
                                <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank" class="btn btn-outline btn-sm" title="Open file">
                                    <ion-icon name="open-outline"></ion-icon>
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this file?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="delete_file" value="<?php echo htmlspecialchars($f['name']); ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                        <ion-icon name="trash-outline"></ion-icon>
                                    </button>
                                </form>
                            </div>
                            <div class="url-display" id="url-<?php echo md5($f['name']); ?>"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="files-list" id="filesList">
                <table>
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $f): ?>
                            <?php $fileUrl = 'uploads/files/' . rawurlencode($f['name']); ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <?php if ($f['is_image']): ?>
                                            <img src="<?php echo htmlspecialchars($fileUrl); ?>" style="width:32px;height:32px;border-radius:4px;object-fit:cover;" loading="lazy">
                                        <?php else: ?>
                                            <ion-icon name="document-outline" style="font-size:1.3rem;color:#ccc;"></ion-icon>
                                        <?php endif; ?>
                                        <span style="font-weight:500;word-break:break-all;"><?php echo htmlspecialchars($f['name']); ?></span>
                                    </div>
                                </td>
                                <td><span class="file-ext-badge"><?php echo htmlspecialchars($f['ext']); ?></span></td>
                                <td><?php echo formatFileSize($f['size']); ?></td>
                                <td><?php echo date('M j, Y h:i A', $f['modified']); ?></td>
                                <td>
                                    <div style="display:flex;gap:5px;">
                                        <button class="btn btn-outline btn-sm" onclick="copyUrl('<?php echo htmlspecialchars($fileUrl, ENT_QUOTES); ?>', this)">
                                            <ion-icon name="link-outline"></ion-icon> URL
                                        </button>
                                        <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank" class="btn btn-outline btn-sm">
                                            <ion-icon name="open-outline"></ion-icon>
                                        </a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this file?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="delete_file" value="<?php echo htmlspecialchars($f['name']); ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <ion-icon name="trash-outline"></ion-icon>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </main>

    <div class="toast" id="toast"></div>

    <script>
        var baseUrl = <?php echo json_encode($baseUrl); ?>;

        function copyUrl(relPath, btn) {
            var fullUrl = baseUrl ? baseUrl + '/' + relPath : window.location.origin + '/' + relPath;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(fullUrl).then(function() {
                    showToast('URL copied to clipboard!');
                });
            } else {
                var ta = document.createElement('textarea');
                ta.value = fullUrl;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                showToast('URL copied to clipboard!');
            }
        }

        function showToast(msg) {
            var t = document.getElementById('toast');
            t.textContent = msg;
            t.style.display = 'block';
            setTimeout(function() { t.style.display = 'none'; }, 2500);
        }

        function setView(mode) {
            var grid = document.getElementById('filesGrid');
            var list = document.getElementById('filesList');
            var btnGrid = document.getElementById('viewGrid');
            var btnList = document.getElementById('viewList');
            if (!grid || !list) return;
            if (mode === 'list') {
                grid.style.display = 'none';
                list.style.display = 'block';
                btnGrid.classList.remove('active');
                btnList.classList.add('active');
            } else {
                grid.style.display = 'grid';
                list.style.display = 'none';
                btnGrid.classList.add('active');
                btnList.classList.remove('active');
            }
        }

        var zone = document.getElementById('uploadZone');
        ['dragenter','dragover'].forEach(function(e) {
            zone.addEventListener(e, function(ev) {
                ev.preventDefault();
                zone.classList.add('dragover');
            });
        });
        ['dragleave','drop'].forEach(function(e) {
            zone.addEventListener(e, function(ev) {
                ev.preventDefault();
                zone.classList.remove('dragover');
            });
        });
        zone.addEventListener('drop', function(ev) {
            ev.preventDefault();
            var input = document.getElementById('fileInput');
            input.files = ev.dataTransfer.files;
            document.getElementById('uploadForm').submit();
        });
    </script>
</body>
</html>
