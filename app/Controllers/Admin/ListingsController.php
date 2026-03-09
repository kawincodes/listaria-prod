<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\UserModel;

class ListingsController extends BaseController
{
    public function index()
    {
        $productModel = new ProductModel();
        $userModel = new UserModel();

        $filter = $this->request->getGet('filter');
        $search = $this->request->getGet('search');

        $builder = $productModel->orderBy('created_at', 'DESC');

        if ($search) {
            $builder->groupStart()
                ->like('title', $search)
                ->orLike('brand', $search)
                ->groupEnd();
        }
        if ($filter === 'pending') {
            $builder->where('approval_status', 'pending');
        } elseif ($filter === 'approved') {
            $builder->where('approval_status', 'approved');
        } elseif ($filter === 'rejected') {
            $builder->where('approval_status', 'rejected');
        }

        $products = $builder->findAll();

        foreach ($products as &$product) {
            $product['seller'] = $userModel->find($product['user_id']);
        }

        return view('admin/listings', [
            'activePage' => 'listings',
            'products' => $products,
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    public function approve()
    {
        $productModel = new ProductModel();
        $productModel->update($this->request->getPost('product_id'), ['approval_status' => 'approved']);
        return redirect()->to('/admin/listings')->with('success', 'Listing approved.');
    }

    public function reject()
    {
        $productModel = new ProductModel();
        $productModel->update($this->request->getPost('product_id'), ['approval_status' => 'rejected']);
        return redirect()->to('/admin/listings')->with('success', 'Listing rejected.');
    }

    public function delete()
    {
        $productModel = new ProductModel();
        $productModel->delete($this->request->getPost('product_id'));
        return redirect()->to('/admin/listings')->with('success', 'Listing deleted.');
    }
}
