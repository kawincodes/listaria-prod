<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? esc($pageTitle) : 'Admin' ?> - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <style>
        :root {
            --primary: #6B21A8;
            --primary-light: #7c3aed;
            --sidebar-bg: #1a1a1a;
            --sidebar-text: #a1a1aa;
            --bg: #f5f5f5;
            --card-bg: #ffffff;
            --text: #333;
            --text-muted: #666;
            --border: #e5e5e5;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }
        .sidebar { width:250px; background:var(--sidebar-bg); color:var(--sidebar-text); position:fixed; top:0; left:0; bottom:0; display:flex; flex-direction:column; overflow-y:auto; scrollbar-width:thin; scrollbar-color:#333 #1a1a1a; padding-bottom:2rem; z-index:100; }
        .sidebar::-webkit-scrollbar { width:6px; }
        .sidebar::-webkit-scrollbar-track { background:#1a1a1a; }
        .sidebar::-webkit-scrollbar-thumb { background:#333; border-radius:3px; }
        .sidebar .brand { display:flex; align-items:center; gap:10px; padding:1.25rem; font-size:1.2rem; font-weight:800; color:white; text-decoration:none; text-transform:lowercase; letter-spacing:-0.5px; flex-shrink:0; }
        .menu-item { display:flex; align-items:center; gap:10px; padding:10px 1rem; color:var(--sidebar-text); text-decoration:none; font-size:0.9rem; transition:all 0.2s; border-radius:8px; margin:2px 0.5rem; flex-shrink:0; }
        .menu-item:hover { background:#2a2a2a; color:white; }
        .menu-item.active { background:var(--primary); color:white; }
        .menu-item ion-icon { font-size:1.2rem; flex-shrink:0; }
        .main-content { margin-left:250px; flex:1; padding:2rem; min-height:100vh; }
        .admin-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; }
        .admin-header h1 { font-size:1.5rem; font-weight:700; }
        .card { background:var(--card-bg); border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.05); margin-bottom:1.5rem; }
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:2rem; }
        .stat-card { background:var(--card-bg); border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.05); }
        .stat-card .stat-value { font-size:1.8rem; font-weight:700; color:var(--primary); }
        .stat-card .stat-label { font-size:0.85rem; color:var(--text-muted); margin-top:4px; }
        table { width:100%; border-collapse:collapse; }
        th { text-align:left; padding:12px; font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); border-bottom:2px solid var(--border); font-weight:600; }
        td { padding:12px; border-bottom:1px solid var(--border); font-size:0.9rem; vertical-align:top; }
        .btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; border:none; cursor:pointer; font-size:0.85rem; font-weight:600; text-decoration:none; transition:all 0.2s; }
        .btn-primary { background:var(--primary); color:white; }
        .btn-primary:hover { background:var(--primary-light); }
        .btn-success { background:var(--success); color:white; }
        .btn-danger { background:var(--danger); color:white; }
        .btn-warning { background:var(--warning); color:white; }
        .btn-sm { padding:5px 10px; font-size:0.8rem; }
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:0.75rem; font-weight:600; }
        .badge-success { background:#dcfce7; color:#166534; }
        .badge-warning { background:#fef3c7; color:#92400e; }
        .badge-danger { background:#fee2e2; color:#991b1b; }
        .badge-info { background:#e0e7ff; color:#3730a3; }
        .form-control { width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px; font-size:0.9rem; font-family:inherit; }
        .form-control:focus { border-color:var(--primary); outline:none; }
        .alert { padding:12px 16px; border-radius:8px; margin-bottom:1rem; font-size:0.9rem; }
        .alert-success { background:#dcfce7; color:#166534; }
        .alert-error { background:#fee2e2; color:#991b1b; }
        .table-responsive { overflow-x:auto; }
        @media (max-width:768px) {
            .sidebar { transform:translateX(-100%); transition:transform 0.3s; }
            .sidebar.open { transform:translateX(0); }
            .main-content { margin-left:0; }
        }
    </style>
</head>
<body>

<?= $this->include('partials/admin_sidebar') ?>

<div class="main-content">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-error"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <?= $this->renderSection('content') ?>
</div>

</body>
</html>
