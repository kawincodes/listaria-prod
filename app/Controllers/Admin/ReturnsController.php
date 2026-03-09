<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ReturnModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\UserModel;

class ReturnsController extends BaseController
{
    public function index()
    {
        $returnModel = new ReturnModel();
        $orderModel = new OrderModel();
        $productModel = new ProductModel();
        $userModel = new UserModel();

        $returns = $returnModel->orderBy('created_at', 'DESC')->findAll();

        foreach ($returns as &$ret) {
            $ret['order'] = $orderModel->find($ret['order_id']);
            $ret['product'] = $productModel->find($ret['product_id']);
            $ret['user'] = $userModel->find($ret['user_id']);
        }

        return view('admin/returns', [
            'activePage' => 'returns',
            'returns' => $returns,
        ]);
    }

    public function update()
    {
        $returnModel = new ReturnModel();
        $returnId = $this->request->getPost('return_id');
        $data = [
            'status' => $this->request->getPost('status'),
            'admin_comments' => $this->request->getPost('admin_comments'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->request->getPost('pickup_date')) {
            $data['pickup_date'] = $this->request->getPost('pickup_date');
        }

        $returnModel->update($returnId, $data);
        return redirect()->to('/admin/returns')->with('success', 'Return updated.');
    }
}
