<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\ProductModel;
use App\Models\OrderModel;
use App\Models\NegotiationModel;
use App\Models\MessageModel;

class ProfileController extends BaseController
{
    public function index()
    {
        $userId = session()->get('user_id');
        $userModel = new UserModel();
        $productModel = new ProductModel();
        $orderModel = new OrderModel();
        $negModel = new NegotiationModel();

        $user = $userModel->find($userId);
        $myListings = $productModel->where('user_id', $userId)->orderBy('created_at', 'DESC')->findAll();
        $myOrders = $orderModel->where('user_id', $userId)->orderBy('created_at', 'DESC')->findAll();

        $sellerNegotiations = $negModel->where('seller_id', $userId)->orderBy('created_at', 'DESC')->findAll();
        $buyerNegotiations = $negModel->where('buyer_id', $userId)->orderBy('created_at', 'DESC')->findAll();

        foreach ($myOrders as &$order) {
            $order['product'] = $productModel->find($order['product_id']);
        }

        foreach ($sellerNegotiations as &$neg) {
            $neg['product'] = $productModel->find($neg['product_id']);
            $neg['buyer'] = $userModel->find($neg['buyer_id']);
        }

        foreach ($buyerNegotiations as &$neg) {
            $neg['product'] = $productModel->find($neg['product_id']);
            $neg['seller'] = $userModel->find($neg['seller_id']);
        }

        return view('profile/index', [
            'user' => $user,
            'listings' => $myListings,
            'orders' => $myOrders,
            'sellerNegotiations' => $sellerNegotiations,
            'buyerNegotiations' => $buyerNegotiations,
        ]);
    }

    public function settings()
    {
        $userModel = new UserModel();
        $user = $userModel->find(session()->get('user_id'));
        return view('profile/settings', ['user' => $user]);
    }

    public function updateSettings()
    {
        $userModel = new UserModel();
        $userId = session()->get('user_id');

        $data = [
            'full_name' => $this->request->getPost('full_name'),
            'phone' => $this->request->getPost('phone'),
            'address' => $this->request->getPost('address'),
            'whatsapp_number' => $this->request->getPost('whatsapp_number'),
            'business_name' => $this->request->getPost('business_name'),
            'business_bio' => $this->request->getPost('business_bio'),
            'gst_number' => $this->request->getPost('gst_number'),
        ];

        $profileImage = $this->request->getFile('profile_image');
        if ($profileImage && $profileImage->isValid() && !$profileImage->hasMoved()) {
            $newName = $profileImage->getRandomName();
            $profileImage->move(FCPATH . 'uploads', $newName);
            $data['profile_image'] = 'uploads/' . $newName;
        }

        $businessLogo = $this->request->getFile('business_logo');
        if ($businessLogo && $businessLogo->isValid() && !$businessLogo->hasMoved()) {
            $newName = $businessLogo->getRandomName();
            $businessLogo->move(FCPATH . 'uploads', $newName);
            $data['business_logo'] = 'uploads/' . $newName;
        }

        $userModel->update($userId, $data);
        session()->set('full_name', $data['full_name']);
        session()->setFlashdata('success', 'Profile updated successfully!');
        return redirect()->to('/profile/settings');
    }

    public function orders()
    {
        $orderModel = new OrderModel();
        $productModel = new ProductModel();

        $orders = $orderModel->where('user_id', session()->get('user_id'))
            ->orderBy('created_at', 'DESC')
            ->findAll();

        foreach ($orders as &$order) {
            $order['product'] = $productModel->find($order['product_id']);
        }

        return view('profile/orders', ['orders' => $orders]);
    }

    public function orderDetail($id)
    {
        $orderModel = new OrderModel();
        $productModel = new ProductModel();

        $order = $orderModel->find($id);
        if (!$order || $order['user_id'] != session()->get('user_id')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $order['product'] = $productModel->find($order['product_id']);
        return view('profile/order_detail', ['order' => $order]);
    }
}
