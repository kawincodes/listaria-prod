<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class ActivityController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        $db->query("CREATE TABLE IF NOT EXISTS admin_activity_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id INTEGER,
            action TEXT,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $logs = $db->query("SELECT l.*, u.full_name, u.email FROM admin_activity_logs l LEFT JOIN users u ON l.admin_id = u.id ORDER BY l.created_at DESC LIMIT 100")->getResultArray();

        return view('admin/activity', [
            'activePage' => 'activity',
            'logs' => $logs,
        ]);
    }
}
