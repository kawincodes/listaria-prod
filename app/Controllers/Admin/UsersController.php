<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class UsersController extends BaseController
{
    public function index()
    {
        $userModel = new UserModel();
        $search = $this->request->getGet('search');
        $filter = $this->request->getGet('filter');

        $builder = $userModel->orderBy('created_at', 'DESC');

        if ($search) {
            $builder->groupStart()
                ->like('full_name', $search)
                ->orLike('email', $search)
                ->groupEnd();
        }
        if ($filter === 'admin') {
            $builder->where('is_admin', 1);
        } elseif ($filter === 'vendor') {
            $builder->where('account_type', 'vendor');
        } elseif ($filter === 'banned') {
            $builder->where('status', 'banned');
        }

        $users = $builder->findAll();

        return view('admin/users', [
            'activePage' => 'users',
            'users' => $users,
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    public function update()
    {
        $userModel = new UserModel();
        $userId = $this->request->getPost('user_id');
        $action = $this->request->getPost('action');

        switch ($action) {
            case 'make_admin':
                $userModel->update($userId, ['is_admin' => 1]);
                break;
            case 'remove_admin':
                $userModel->update($userId, ['is_admin' => 0]);
                break;
            case 'ban':
                $userModel->update($userId, ['status' => 'banned']);
                break;
            case 'unban':
                $userModel->update($userId, ['status' => 'active']);
                break;
            case 'verify_vendor':
                $userModel->update($userId, ['is_verified_vendor' => 1, 'vendor_status' => 'approved']);
                break;
            case 'reject_vendor':
                $reason = $this->request->getPost('reason') ?? '';
                $userModel->update($userId, ['vendor_status' => 'rejected', 'rejection_reason' => $reason]);
                break;
        }

        return redirect()->to('/admin/users')->with('success', 'User updated successfully.');
    }

    public function delete()
    {
        $userModel = new UserModel();
        $userModel->delete($this->request->getPost('user_id'));
        return redirect()->to('/admin/users')->with('success', 'User deleted.');
    }
}
