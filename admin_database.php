<?php
require_once __DIR__ . '/includes/session.php';
require 'includes/db.php';

$activePage = 'database';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

$selectedTable = $_GET['table'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;
$search = $_GET['search'] ?? '';
$msg = $_GET['msg'] ?? '';

$columns = [];
$rows = [];
$totalRows = 0;
$totalPages = 1;
$tableInfo = [];

if ($selectedTable && in_array($selectedTable, $tables)) {
    $tableInfo = $pdo->query("PRAGMA table_info(" . $selectedTable . ")")->fetchAll();
    $columns = array_column($tableInfo, 'name');

    $countSql = "SELECT COUNT(*) FROM " . $selectedTable;
    $dataSql = "SELECT rowid AS __rowid__, * FROM " . $selectedTable;

    if ($search !== '') {
        $likeClauses = [];
        foreach ($columns as $col) {
            $likeClauses[] = "CAST(\"$col\" AS TEXT) LIKE :search";
        }
        $where = " WHERE " . implode(' OR ', $likeClauses);
        $countSql .= $where;
        $dataSql .= $where;
    }

    $dataSql .= " LIMIT :limit OFFSET :offset";

    if ($search !== '') {
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':search' => "%$search%"]);
        $totalRows = $countStmt->fetchColumn();

        $dataStmt = $pdo->prepare($dataSql);
        $dataStmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->execute();
    } else {
        $totalRows = $pdo->query($countSql)->fetchColumn();
        $dataStmt = $pdo->prepare($dataSql);
        $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $dataStmt->execute();
    }

    $rows = $dataStmt->fetchAll();
    $totalPages = max(1, ceil($totalRows / $perPage));
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Editor - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root {
            --primary: #6B21A8;
            --primary-light: #9333EA;
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --border: #e5e7eb;
            --text: #1a1a2e;
            --text-secondary: #6b7280;
            --mono: 'JetBrains Mono', monospace;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); display: flex; color: var(--text); }

        .sidebar { width: 260px; background: #1a1a1a; height: 100vh; position: fixed; padding: 0.5rem 0; color: white; z-index: 100; }
        .main-content { margin-left: 260px; padding: 1.5rem 2rem; width: calc(100% - 260px); min-height: 100vh; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .page-header h1 { font-size: 1.6rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .page-header h1 ion-icon { color: var(--primary); font-size: 1.4rem; }
        .page-header p { color: var(--text-secondary); font-size: 0.85rem; margin-top: 4px; }

        .db-layout { display: flex; gap: 1.5rem; height: calc(100vh - 120px); }

        .table-sidebar {
            width: 240px;
            flex-shrink: 0;
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .table-sidebar-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #fafafa;
        }
        .table-sidebar-header ion-icon { color: var(--primary); font-size: 1rem; }
        .table-list { flex: 1; overflow-y: auto; padding: 0.5rem; }
        .table-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.65rem 0.9rem;
            border-radius: 10px;
            color: var(--text);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.15s;
            cursor: pointer;
        }
        .table-item:hover { background: #f3e8ff; color: var(--primary); }
        .table-item.active {
            background: linear-gradient(135deg, #f3e8ff, #ede9fe);
            color: var(--primary);
            font-weight: 600;
        }
        .table-item ion-icon { font-size: 1rem; flex-shrink: 0; }
        .table-item .row-count {
            margin-left: auto;
            font-size: 0.7rem;
            background: rgba(107,33,168,0.1);
            color: var(--primary);
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
            font-family: var(--mono);
        }

        .data-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .data-toolbar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .selected-table-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            font-family: var(--mono);
        }
        .selected-table-badge ion-icon { font-size: 1rem; }

        .toolbar-search {
            display: flex;
            align-items: center;
            gap: 0;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            flex: 1;
            max-width: 350px;
        }
        .toolbar-search input {
            border: none;
            padding: 0.55rem 0.9rem;
            font-size: 0.85rem;
            flex: 1;
            outline: none;
            font-family: 'Inter', sans-serif;
            background: transparent;
        }
        .toolbar-search button {
            border: none;
            background: none;
            padding: 0.55rem 0.9rem;
            cursor: pointer;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
        }
        .toolbar-search button:hover { color: var(--primary); }

        .toolbar-actions { display: flex; gap: 0.5rem; margin-left: auto; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.55rem 1rem;
            border: none;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            font-family: 'Inter', sans-serif;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #581c87; }
        .btn-success { background: #dcfce7; color: #16a34a; }
        .btn-success:hover { background: #bbf7d0; }
        .btn-danger { background: #fee2e2; color: #dc2626; }
        .btn-danger:hover { background: #fecaca; }
        .btn-outline { background: var(--card-bg); color: var(--text); border: 1px solid var(--border); }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
        .btn-sm { padding: 0.4rem 0.75rem; font-size: 0.75rem; }
        .btn ion-icon { font-size: 1rem; }

        .data-card {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border);
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .data-table-wrap {
            flex: 1;
            overflow: auto;
        }
        .data-table-wrap::-webkit-scrollbar { height: 8px; width: 8px; }
        .data-table-wrap::-webkit-scrollbar-track { background: #f5f5f5; }
        .data-table-wrap::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }

        table { width: 100%; border-collapse: collapse; min-width: max-content; }
        thead { position: sticky; top: 0; z-index: 5; }
        th {
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            background: #fafafa;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        th.pk-col { color: var(--primary); }
        td {
            padding: 0.65rem 1rem;
            font-size: 0.83rem;
            border-bottom: 1px solid #f5f5f5;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-family: var(--mono);
            font-size: 0.8rem;
        }
        tr:hover td { background: #faf5ff; }
        td.null-val { color: #d1d5db; font-style: italic; }
        td.actions-cell { position: sticky; right: 0; background: var(--card-bg); border-left: 1px solid #f0f0f0; }
        tr:hover td.actions-cell { background: #faf5ff; }
        th.actions-header { position: sticky; right: 0; background: #fafafa; border-left: 1px solid #f0f0f0; }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1.25rem;
            border-top: 1px solid var(--border);
            background: #fafafa;
            font-size: 0.82rem;
            color: var(--text-secondary);
        }
        .pagination-links { display: flex; gap: 4px; }
        .pagination-links a, .pagination-links span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none;
            color: var(--text);
            transition: all 0.15s;
        }
        .pagination-links a:hover { background: #f3e8ff; color: var(--primary); }
        .pagination-links .active-page { background: var(--primary); color: white; font-weight: 600; }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
            text-align: center;
            flex: 1;
        }
        .empty-state ion-icon { font-size: 3.5rem; color: #e5e7eb; margin-bottom: 1rem; }
        .empty-state h3 { font-size: 1.1rem; color: var(--text); margin-bottom: 0.5rem; }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: var(--card-bg);
            border-radius: 20px;
            width: 90%;
            max-width: 700px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            animation: modalIn 0.25s ease;
        }
        @keyframes modalIn {
            from { transform: scale(0.95) translateY(10px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }
        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-header h3 { font-size: 1.05rem; display: flex; align-items: center; gap: 8px; }
        .modal-header h3 ion-icon { color: var(--primary); }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.3rem;
            cursor: pointer;
            color: var(--text-secondary);
            padding: 4px;
            border-radius: 8px;
            display: flex;
            transition: all 0.15s;
        }
        .modal-close:hover { background: #fee2e2; color: #dc2626; }
        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
        }
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            background: #fafafa;
            border-radius: 0 0 20px 20px;
        }

        .form-field { margin-bottom: 1rem; }
        .form-field label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .form-field label .pk-indicator {
            font-size: 0.6rem;
            background: #fef3c7;
            color: #d97706;
            padding: 1px 6px;
            border-radius: 4px;
            font-weight: 700;
        }
        .form-field label .type-indicator {
            font-size: 0.6rem;
            background: #ede9fe;
            color: var(--primary);
            padding: 1px 6px;
            border-radius: 4px;
            font-family: var(--mono);
        }
        .form-field input, .form-field textarea {
            width: 100%;
            padding: 0.6rem 0.9rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.85rem;
            font-family: var(--mono);
            transition: border-color 0.15s;
            background: var(--card-bg);
        }
        .form-field input:focus, .form-field textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(107,33,168,0.08);
        }
        .form-field textarea { min-height: 80px; resize: vertical; }
        .form-field input[disabled] { background: #f9fafb; color: #9ca3af; }

        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            padding: 0.85rem 1.5rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            z-index: 2000;
            display: none;
            align-items: center;
            gap: 8px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            animation: toastIn 0.3s ease;
        }
        @keyframes toastIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .toast-success { background: #16a34a; color: white; }
        .toast-error { background: #dc2626; color: white; }

        .sql-panel {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border);
            margin-bottom: 1rem;
            overflow: hidden;
        }
        .sql-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1.25rem;
            background: #1a1a2e;
            color: white;
        }
        .sql-panel-header span { font-size: 0.82rem; font-weight: 600; display: flex; align-items: center; gap: 6px; }
        .sql-textarea {
            width: 100%;
            padding: 1rem 1.25rem;
            border: none;
            background: #0f0f1a;
            color: #e2e8f0;
            font-family: var(--mono);
            font-size: 0.85rem;
            min-height: 80px;
            resize: vertical;
            outline: none;
        }
        .sql-result {
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            background: #0f0f1a;
            color: #a3e635;
            font-family: var(--mono);
            font-size: 0.8rem;
            max-height: 200px;
            overflow: auto;
            display: none;
        }
        .sql-result.error { color: #fb7185; }

        .info-chips { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .info-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 0.35rem 0.8rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 500;
            background: #f3f4f6;
            color: var(--text-secondary);
        }
        .info-chip ion-icon { font-size: 0.9rem; }
        .info-chip.purple { background: #f3e8ff; color: var(--primary); }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1><ion-icon name="server-outline"></ion-icon> Database Editor</h1>
                <p>Browse, edit, and manage your SQLite database tables</p>
            </div>
            <div class="info-chips">
                <span class="info-chip purple"><ion-icon name="layers-outline"></ion-icon> <?php echo count($tables); ?> Tables</span>
                <span class="info-chip"><ion-icon name="document-outline"></ion-icon> database.sqlite</span>
            </div>
        </div>

        <div class="sql-panel">
            <div class="sql-panel-header">
                <span><ion-icon name="terminal-outline"></ion-icon> SQL Console</span>
                <div style="display:flex;gap:0.5rem;">
                    <button class="btn btn-sm" style="background:rgba(255,255,255,0.1);color:white;" onclick="runSQL()">
                        <ion-icon name="play-outline"></ion-icon> Run
                    </button>
                    <button class="btn btn-sm" style="background:rgba(255,255,255,0.1);color:white;" onclick="clearSQL()">
                        <ion-icon name="close-outline"></ion-icon> Clear
                    </button>
                </div>
            </div>
            <textarea class="sql-textarea" id="sqlInput" placeholder="SELECT * FROM users LIMIT 10;" spellcheck="false"></textarea>
            <div class="sql-result" id="sqlResult"></div>
        </div>

        <div class="db-layout">
            <div class="table-sidebar">
                <div class="table-sidebar-header">
                    <ion-icon name="list-outline"></ion-icon> Tables
                </div>
                <div class="table-list">
                    <?php foreach ($tables as $t):
                        $cnt = $pdo->query("SELECT COUNT(*) FROM \"$t\"")->fetchColumn();
                    ?>
                    <a href="?table=<?php echo urlencode($t); ?>" class="table-item <?php echo ($selectedTable === $t) ? 'active' : ''; ?>">
                        <ion-icon name="grid-outline"></ion-icon>
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($t); ?></span>
                        <span class="row-count"><?php echo $cnt; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="data-panel">
                <?php if (!$selectedTable): ?>
                    <div class="data-card">
                        <div class="empty-state">
                            <ion-icon name="server-outline"></ion-icon>
                            <h3>Select a Table</h3>
                            <p>Choose a table from the sidebar to browse and edit its data</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="data-toolbar">
                        <div class="selected-table-badge">
                            <ion-icon name="grid-outline"></ion-icon>
                            <?php echo htmlspecialchars($selectedTable); ?>
                        </div>

                        <form method="GET" class="toolbar-search">
                            <input type="hidden" name="table" value="<?php echo htmlspecialchars($selectedTable); ?>">
                            <input type="text" name="search" placeholder="Search across all columns..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit"><ion-icon name="search-outline"></ion-icon></button>
                        </form>

                        <div class="toolbar-actions">
                            <button class="btn btn-primary" onclick="openAddModal()">
                                <ion-icon name="add-outline"></ion-icon> Add Row
                            </button>
                        </div>
                    </div>

                    <div class="data-card">
                        <?php if (empty($rows)): ?>
                            <div class="empty-state">
                                <ion-icon name="file-tray-outline"></ion-icon>
                                <h3>No Data Found</h3>
                                <p><?php echo $search ? 'No rows match your search' : 'This table is empty'; ?></p>
                            </div>
                        <?php else: ?>
                            <div class="data-table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <?php foreach ($tableInfo as $col): ?>
                                            <th class="<?php echo $col['pk'] ? 'pk-col' : ''; ?>">
                                                <?php echo htmlspecialchars($col['name']); ?>
                                                <?php if ($col['pk']): ?><span style="font-size:0.6rem;vertical-align:super;color:#d97706;">PK</span><?php endif; ?>
                                            </th>
                                            <?php endforeach; ?>
                                            <th class="actions-header">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <?php foreach ($columns as $col): ?>
                                            <td class="<?php echo is_null($row[$col]) ? 'null-val' : ''; ?>" title="<?php echo htmlspecialchars($row[$col] ?? ''); ?>">
                                                <?php echo is_null($row[$col]) ? 'NULL' : htmlspecialchars(mb_strimwidth($row[$col], 0, 100, '...')); ?>
                                            </td>
                                            <?php endforeach; ?>
                                            <td class="actions-cell">
                                                <div style="display:flex;gap:4px;">
                                                    <button class="btn btn-outline btn-sm" onclick='openEditModal(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="Edit">
                                                        <ion-icon name="create-outline"></ion-icon>
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" onclick='confirmDelete(<?php echo json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="Delete">
                                                        <ion-icon name="trash-outline"></ion-icon>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($totalPages > 1): ?>
                            <div class="pagination">
                                <span>Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $perPage, $totalRows); ?> of <?php echo number_format($totalRows); ?> rows</span>
                                <div class="pagination-links">
                                    <?php if ($page > 1): ?>
                                    <a href="?table=<?php echo urlencode($selectedTable); ?>&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>"><ion-icon name="chevron-back-outline"></ion-icon></a>
                                    <?php endif; ?>
                                    <?php
                                    $start = max(1, $page - 2);
                                    $end = min($totalPages, $page + 2);
                                    for ($i = $start; $i <= $end; $i++):
                                    ?>
                                    <a href="?table=<?php echo urlencode($selectedTable); ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="<?php echo $i === $page ? 'active-page' : ''; ?>"><?php echo $i; ?></a>
                                    <?php endfor; ?>
                                    <?php if ($page < $totalPages): ?>
                                    <a href="?table=<?php echo urlencode($selectedTable); ?>&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>"><ion-icon name="chevron-forward-outline"></ion-icon></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalTitle"><ion-icon name="create-outline"></ion-icon> <span>Edit Row</span></h3>
                <button class="modal-close" onclick="closeModal()"><ion-icon name="close-outline"></ion-icon></button>
            </div>
            <div class="modal-body" id="modalBody"></div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button class="btn btn-primary" id="modalSaveBtn" onclick="saveRow()">
                    <ion-icon name="save-outline"></ion-icon> Save
                </button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="deleteOverlay">
        <div class="modal" style="max-width:440px;">
            <div class="modal-header">
                <h3><ion-icon name="warning-outline" style="color:#dc2626;"></ion-icon> <span>Confirm Delete</span></h3>
                <button class="modal-close" onclick="closeDeleteModal()"><ion-icon name="close-outline"></ion-icon></button>
            </div>
            <div class="modal-body">
                <p style="color:var(--text-secondary);font-size:0.9rem;">Are you sure you want to delete this row? This action cannot be undone.</p>
                <div id="deleteInfo" style="margin-top:1rem;padding:0.75rem;background:#fef2f2;border-radius:10px;font-family:var(--mono);font-size:0.8rem;color:#991b1b;"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn btn-danger" id="deleteBtn" onclick="executeDelete()">
                    <ion-icon name="trash-outline"></ion-icon> Delete
                </button>
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script>
    const TABLE = <?php echo json_encode($selectedTable); ?>;
    const COLUMNS = <?php echo json_encode($tableInfo); ?>;
    const CSRF = <?php echo json_encode($csrf); ?>;

    let currentMode = 'edit';
    let currentRowData = null;

    function openEditModal(rowData) {
        currentMode = 'edit';
        currentRowData = rowData;
        document.getElementById('modalTitle').querySelector('span').textContent = 'Edit Row (rowid: ' + rowData.__rowid__ + ')';
        const body = document.getElementById('modalBody');
        body.innerHTML = '';

        COLUMNS.forEach(col => {
            const div = document.createElement('div');
            div.className = 'form-field';
            const val = rowData[col.name];
            const isPk = col.pk >= 1;
            const isLong = (val && String(val).length > 100);

            div.innerHTML = `
                <label>
                    ${col.name}
                    ${isPk ? '<span class="pk-indicator">PK</span>' : ''}
                    <span class="type-indicator">${col.type || 'TEXT'}</span>
                </label>
                ${isLong ?
                    `<textarea name="${col.name}">${val === null ? '' : escapeHtml(String(val))}</textarea>` :
                    `<input type="text" name="${col.name}" value="${val === null ? '' : escapeHtml(String(val))}" placeholder="${val === null ? 'NULL' : ''}">`
                }
            `;
            body.appendChild(div);
        });

        document.getElementById('modalOverlay').classList.add('open');
    }

    function openAddModal() {
        currentMode = 'add';
        currentRowData = null;
        document.getElementById('modalTitle').querySelector('span').textContent = 'Add New Row';
        const body = document.getElementById('modalBody');
        body.innerHTML = '';

        COLUMNS.forEach(col => {
            const div = document.createElement('div');
            div.className = 'form-field';
            const isPk = col.pk === 1;
            const hasDefault = col.dflt_value !== null;

            div.innerHTML = `
                <label>
                    ${col.name}
                    ${isPk ? '<span class="pk-indicator">PK</span>' : ''}
                    <span class="type-indicator">${col.type || 'TEXT'}</span>
                </label>
                <input type="text" name="${col.name}" placeholder="${isPk ? 'Auto-generated' : (hasDefault ? 'Default: ' + col.dflt_value : '')}" ${isPk ? 'disabled' : ''}>
            `;
            body.appendChild(div);
        });

        document.getElementById('modalOverlay').classList.add('open');
    }

    function closeModal() {
        document.getElementById('modalOverlay').classList.remove('open');
    }

    function saveRow() {
        const body = document.getElementById('modalBody');
        const data = { table: TABLE, csrf_token: CSRF };

        if (currentMode === 'edit') {
            data.action = 'update';
            data.rowid = currentRowData.__rowid__;
            data.values = {};
            COLUMNS.forEach(col => {
                const input = body.querySelector(`[name="${col.name}"]`);
                data.values[col.name] = input.value;
            });
        } else {
            data.action = 'insert';
            data.values = {};
            COLUMNS.forEach(col => {
                const input = body.querySelector(`[name="${col.name}"]`);
                if (!col.pk || input.value !== '') {
                    if (input.value !== '') {
                        data.values[col.name] = input.value;
                    }
                }
            });
        }

        fetch('api/admin_db.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast(res.message || 'Saved successfully', 'success');
                closeModal();
                setTimeout(() => location.reload(), 600);
            } else {
                showToast(res.message || 'Error saving', 'error');
            }
        })
        .catch(() => showToast('Network error', 'error'));
    }

    let deleteRowData = null;
    function confirmDelete(rowData) {
        deleteRowData = rowData;
        const pk = COLUMNS.find(c => c.pk >= 1);
        const info = pk ? `${pk.name} = ${rowData[pk.name]} (rowid: ${rowData.__rowid__})` : `rowid: ${rowData.__rowid__}`;
        document.getElementById('deleteInfo').textContent = info;
        document.getElementById('deleteOverlay').classList.add('open');
    }
    function closeDeleteModal() {
        document.getElementById('deleteOverlay').classList.remove('open');
    }
    function executeDelete() {
        fetch('api/admin_db.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', table: TABLE, rowid: deleteRowData.__rowid__, csrf_token: CSRF })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast('Row deleted', 'success');
                closeDeleteModal();
                setTimeout(() => location.reload(), 600);
            } else {
                showToast(res.message || 'Delete failed', 'error');
            }
        })
        .catch(() => showToast('Network error', 'error'));
    }

    function runSQL() {
        const sql = document.getElementById('sqlInput').value.trim();
        if (!sql) return;
        const resultDiv = document.getElementById('sqlResult');
        resultDiv.style.display = 'block';
        resultDiv.className = 'sql-result';
        resultDiv.textContent = 'Running...';

        fetch('api/admin_db.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'query', sql: sql, csrf_token: CSRF })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                if (res.data && res.data.length > 0) {
                    const cols = Object.keys(res.data[0]);
                    let out = cols.join(' | ') + '\n' + cols.map(c => '-'.repeat(c.length)).join('-+-') + '\n';
                    res.data.forEach(row => {
                        out += cols.map(c => row[c] === null ? 'NULL' : String(row[c]).substring(0, 50)).join(' | ') + '\n';
                    });
                    out += `\n${res.data.length} row(s) returned`;
                    resultDiv.textContent = out;
                } else {
                    resultDiv.textContent = res.message || 'Query executed successfully. ' + (res.affected !== undefined ? res.affected + ' row(s) affected.' : '');
                }
                resultDiv.className = 'sql-result';
            } else {
                resultDiv.textContent = 'Error: ' + (res.message || 'Query failed');
                resultDiv.className = 'sql-result error';
            }
        })
        .catch(() => {
            resultDiv.textContent = 'Network error';
            resultDiv.className = 'sql-result error';
        });
    }

    function clearSQL() {
        document.getElementById('sqlInput').value = '';
        document.getElementById('sqlResult').style.display = 'none';
    }

    function showToast(msg, type) {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className = 'toast toast-' + type;
        t.style.display = 'flex';
        setTimeout(() => { t.style.display = 'none'; }, 3000);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML.replace(/"/g, '&quot;');
    }

    document.getElementById('sqlInput')?.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            runSQL();
        }
    });
    </script>
</body>
</html>
