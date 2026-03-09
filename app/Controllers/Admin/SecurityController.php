<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class SecurityController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        $db->query("CREATE TABLE IF NOT EXISTS admin_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id INTEGER,
            session_id TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            last_activity DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $db->query("CREATE TABLE IF NOT EXISTS blacklist (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            type VARCHAR(20),
            value TEXT,
            reason TEXT,
            created_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $sessions = $db->query("SELECT s.*, u.full_name, u.email FROM admin_sessions s LEFT JOIN users u ON s.admin_id = u.id ORDER BY s.last_activity DESC")->getResultArray();
        $blacklist = $db->query("SELECT * FROM blacklist ORDER BY created_at DESC")->getResultArray();

        return view('admin/security', [
            'activePage' => 'security',
            'sessions' => $sessions,
            'blacklist' => $blacklist,
        ]);
    }

    public function blacklist()
    {
        $db = \Config\Database::connect();
        $db->query("INSERT INTO blacklist (type, value, reason, created_by, created_at) VALUES (?, ?, ?, ?, ?)", [
            $this->request->getPost('type'),
            $this->request->getPost('value'),
            $this->request->getPost('reason'),
            session()->get('user_id'),
            date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('/admin/security')->with('success', 'Entry blacklisted.');
    }

    public function removeBlacklist()
    {
        $db = \Config\Database::connect();
        $db->query("DELETE FROM blacklist WHERE id = ?", [$this->request->getPost('blacklist_id')]);
        return redirect()->to('/admin/security')->with('success', 'Blacklist entry removed.');
    }

    public function terminateSession()
    {
        $db = \Config\Database::connect();
        $db->query("DELETE FROM admin_sessions WHERE id = ?", [$this->request->getPost('session_id')]);
        return redirect()->to('/admin/security')->with('success', 'Session terminated.');
    }
}
