<?php
require_once __DIR__ . '/../includes/session.php';
require __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!isset($input['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $input['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$action = $input['action'] ?? '';
$table = $input['table'] ?? '';

$allTables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);

if ($action === 'query') {
    $sql = trim($input['sql'] ?? '');
    if (empty($sql)) {
        echo json_encode(['success' => false, 'message' => 'Empty query']);
        exit;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        $firstWord = strtoupper(strtok($sql, " \t\n\r"));
        if ($firstWord === 'SELECT' || $firstWord === 'PRAGMA') {
            $data = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            $affected = $stmt->rowCount();
            echo json_encode(['success' => true, 'message' => 'Query executed', 'affected' => $affected]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if (!$table || !in_array($table, $allTables)) {
    echo json_encode(['success' => false, 'message' => 'Invalid table']);
    exit;
}

$tableInfoStmt = $pdo->query("PRAGMA table_info(\"$table\")");
$tableColumns = $tableInfoStmt->fetchAll();
$validColumns = array_column($tableColumns, 'name');
$pkColumns = array_filter($tableColumns, fn($c) => $c['pk'] === 1);

try {
    if ($action === 'update') {
        $rowid = $input['rowid'] ?? null;
        $values = $input['values'] ?? [];

        if ($rowid === null || empty($values)) {
            echo json_encode(['success' => false, 'message' => 'Missing data']);
            exit;
        }

        $setClauses = [];
        $params = [];
        foreach ($values as $col => $val) {
            if (!in_array($col, $validColumns)) continue;
            $setClauses[] = "\"$col\" = ?";
            $params[] = $val === '' ? null : $val;
        }

        if (empty($setClauses)) {
            echo json_encode(['success' => false, 'message' => 'No valid columns']);
            exit;
        }

        $params[] = $rowid;
        $sql = "UPDATE \"$table\" SET " . implode(', ', $setClauses) . " WHERE rowid = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'message' => 'Row updated']);

    } elseif ($action === 'insert') {
        $values = $input['values'] ?? [];
        if (empty($values)) {
            echo json_encode(['success' => false, 'message' => 'No data provided']);
            exit;
        }

        $cols = [];
        $placeholders = [];
        $params = [];
        foreach ($values as $col => $val) {
            if (!in_array($col, $validColumns)) continue;
            $cols[] = "\"$col\"";
            $placeholders[] = '?';
            $params[] = $val;
        }

        $sql = "INSERT INTO \"$table\" (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'message' => 'Row inserted (ID: ' . $pdo->lastInsertId() . ')']);

    } elseif ($action === 'delete') {
        $rowid = $input['rowid'] ?? null;
        if ($rowid === null) {
            echo json_encode(['success' => false, 'message' => 'Missing row identifier']);
            exit;
        }

        $sql = "DELETE FROM \"$table\" WHERE rowid = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$rowid]);
        echo json_encode(['success' => true, 'message' => 'Row deleted']);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
