<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\ProductModel;
use App\Models\OrderModel;
use App\Models\SupportTicketModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $userModel = new UserModel();
        $productModel = new ProductModel();
        $orderModel = new OrderModel();
        $ticketModel = new SupportTicketModel();

        $totalUsers = $userModel->countAll();
        $totalProducts = $productModel->countAll();
        $totalOrders = $orderModel->countAll();
        $pendingApprovals = $productModel->where('approval_status', 'pending')->countAllResults();
        $openTickets = $ticketModel->where('status', 'open')->countAllResults();

        $db = \Config\Database::connect();
        $totalRevenue = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM orders")->getRow()->total;

        $recentOrders = $orderModel->orderBy('created_at', 'DESC')->findAll(5);
        $recentUsers = $userModel->orderBy('created_at', 'DESC')->findAll(5);
        $pendingProducts = $productModel->where('approval_status', 'pending')
            ->orderBy('created_at', 'DESC')->findAll(10);

        foreach ($recentOrders as &$order) {
            $order['product'] = $productModel->find($order['product_id']);
            $order['user'] = $userModel->find($order['user_id']);
        }

        return view('admin/dashboard', [
            'activePage' => 'dashboard',
            'totalUsers' => $totalUsers,
            'totalProducts' => $totalProducts,
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'pendingApprovals' => $pendingApprovals,
            'openTickets' => $openTickets,
            'recentOrders' => $recentOrders,
            'recentUsers' => $recentUsers,
            'pendingProducts' => $pendingProducts,
        ]);
    }

    public function analytics()
    {
        $db = \Config\Database::connect();
        $orderModel = new OrderModel();
        $userModel = new UserModel();
        $productModel = new ProductModel();

        $monthlyRevenue = $db->query("SELECT strftime('%Y-%m', created_at) as month, SUM(amount) as total FROM orders GROUP BY month ORDER BY month DESC LIMIT 12")->getResultArray();
        $topCategories = $db->query("SELECT category, COUNT(*) as count FROM products GROUP BY category ORDER BY count DESC LIMIT 10")->getResultArray();
        $dailyOrders = $db->query("SELECT DATE(created_at) as day, COUNT(*) as count FROM orders GROUP BY day ORDER BY day DESC LIMIT 30")->getResultArray();

        return view('admin/analytics', [
            'activePage' => 'analytics',
            'monthlyRevenue' => $monthlyRevenue,
            'topCategories' => $topCategories,
            'dailyOrders' => $dailyOrders,
            'totalUsers' => $userModel->countAll(),
            'totalProducts' => $productModel->countAll(),
            'totalOrders' => $orderModel->countAll(),
        ]);
    }
}
