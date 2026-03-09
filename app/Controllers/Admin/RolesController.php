<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class RolesController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        $db->query("CREATE TABLE IF NOT EXISTS roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            permissions TEXT DEFAULT '[]',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $roles = $db->query("SELECT * FROM roles ORDER BY created_at DESC")->getResultArray();
        $admins = (new UserModel())->where('is_admin', 1)->findAll();

        return view('admin/roles', [
            'activePage' => 'roles',
            'roles' => $roles,
            'admins' => $admins,
        ]);
    }

    public function create()
    {
        $db = \Config\Database::connect();
        $permissions = $this->request->getPost('permissions') ?? [];

        $db->query("INSERT INTO roles (name, permissions, created_at) VALUES (?, ?, ?)", [
            $this->request->getPost('name'),
            json_encode($permissions),
            date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/admin/roles')->with('success', 'Role created.');
    }

    public function update()
    {
        $db = \Config\Database::connect();
        $permissions = $this->request->getPost('permissions') ?? [];

        $db->query("UPDATE roles SET name = ?, permissions = ? WHERE id = ?", [
            $this->request->getPost('name'),
            json_encode($permissions),
            $this->request->getPost('role_id'),
        ]);

        return redirect()->to('/admin/roles')->with('success', 'Role updated.');
    }

    public function delete()
    {
        $db = \Config\Database::connect();
        $db->query("DELETE FROM roles WHERE id = ?", [$this->request->getPost('role_id')]);
        return redirect()->to('/admin/roles')->with('success', 'Role deleted.');
    }

    public function assign()
    {
        $userModel = new UserModel();
        $userModel->update($this->request->getPost('user_id'), [
            'role' => $this->request->getPost('role'),
        ]);
        return redirect()->to('/admin/roles')->with('success', 'Role assigned.');
    }
}
