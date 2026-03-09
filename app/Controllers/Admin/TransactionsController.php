<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\UserModel;

class TransactionsController extends BaseController
{
    public function index()
    {
        $orderModel = new OrderModel();
        $productModel = new ProductModel();
        $userModel = new UserModel();

        $orders = $orderModel->orderBy('created_at', 'DESC')->findAll();

        foreach ($orders as &$order) {
            $order['product'] = $productModel->find($order['product_id']);
            $order['user'] = $userModel->find($order['user_id']);
        }

        return view('admin/transactions', [
            'activePage' => 'transactions',
            'orders' => $orders,
        ]);
    }

    public function update()
    {
        $orderModel = new OrderModel();
        $orderId = $this->request->getPost('order_id');
        $data = [];

        if ($this->request->getPost('order_status')) {
            $data['order_status'] = $this->request->getPost('order_status');
        }
        if ($this->request->getPost('payment_status')) {
            $data['payment_status'] = $this->request->getPost('payment_status');
        }

        if (!empty($data)) {
            $orderModel->update($orderId, $data);
        }

        return redirect()->to('/admin/transactions')->with('success', 'Transaction updated.');
    }
}
