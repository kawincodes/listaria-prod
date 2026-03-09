<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\OrderModel;

class OrderController extends BaseController
{
    public function shipping()
    {
        $productId = $this->request->getGet('product_id') ?? session()->get('checkout_product_id');
        if (!$productId) {
            return redirect()->to('/');
        }

        $productModel = new ProductModel();
        $product = $productModel->find($productId);
        if (!$product) {
            return redirect()->to('/');
        }

        session()->set('checkout_product_id', $productId);
        return view('order/shipping', ['product' => $product]);
    }

    public function shippingPost()
    {
        session()->set('shipping_info', [
            'name' => $this->request->getPost('name'),
            'phone' => $this->request->getPost('phone'),
            'address' => $this->request->getPost('address'),
            'city' => $this->request->getPost('city'),
            'state' => $this->request->getPost('state'),
            'pincode' => $this->request->getPost('pincode'),
        ]);
        return redirect()->to('/payment-method');
    }

    public function paymentMethod()
    {
        $productId = session()->get('checkout_product_id');
        if (!$productId) {
            return redirect()->to('/');
        }

        $productModel = new ProductModel();
        $product = $productModel->find($productId);
        return view('order/payment_method', ['product' => $product]);
    }

    public function payment()
    {
        $productId = session()->get('checkout_product_id');
        if (!$productId) {
            return redirect()->to('/');
        }

        $productModel = new ProductModel();
        $product = $productModel->find($productId);
        return view('order/payment', ['product' => $product]);
    }

    public function paymentPost()
    {
        return $this->placeOrder();
    }

    public function placeOrder()
    {
        $productId = session()->get('checkout_product_id');
        if (!$productId) {
            return redirect()->to('/');
        }

        $productModel = new ProductModel();
        $orderModel = new OrderModel();
        $product = $productModel->find($productId);

        $paymentMethod = $this->request->getPost('payment_method') ?? 'COD';
        $amount = $product['price_min'];

        $orderModel->insert([
            'user_id' => session()->get('user_id'),
            'product_id' => $productId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'order_status' => 'Item Collected',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $orderId = $orderModel->getInsertID();

        $productModel->update($productId, ['status' => 'sold']);

        session()->remove(['checkout_product_id', 'shipping_info']);

        return redirect()->to("/order-summary/{$orderId}");
    }

    public function summary($id)
    {
        $orderModel = new OrderModel();
        $productModel = new ProductModel();

        $order = $orderModel->find($id);
        if (!$order || $order['user_id'] != session()->get('user_id')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $order['product'] = $productModel->find($order['product_id']);
        return view('order/summary', ['order' => $order]);
    }
}
