<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\UserModel;
use App\Models\NegotiationModel;

class ProductController extends BaseController
{
    public function details($id)
    {
        $productModel = new ProductModel();
        $userModel = new UserModel();

        $product = $productModel->find($id);
        if (!$product) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $productModel->set('views', 'views + 1', false)->where('id', $id)->update();

        $seller = $userModel->find($product['user_id']);

        $relatedProducts = $productModel->where('category', $product['category'])
            ->where('id !=', $id)
            ->where('is_published', 1)
            ->where('approval_status', 'approved')
            ->where('status', 'available')
            ->orderBy('created_at', 'DESC')
            ->findAll(4);

        $existingNegotiation = null;
        if (session()->get('user_id')) {
            $negModel = new NegotiationModel();
            $existingNegotiation = $negModel->where('product_id', $id)
                ->where('buyer_id', session()->get('user_id'))
                ->where('status', 'active')
                ->first();
        }

        return view('product/details', [
            'product' => $product,
            'seller' => $seller,
            'relatedProducts' => $relatedProducts,
            'existingNegotiation' => $existingNegotiation,
        ]);
    }

    public function sell()
    {
        return view('product/sell');
    }

    public function sellPost()
    {
        $productModel = new ProductModel();

        $imagePaths = [];
        $files = $this->request->getFiles();
        if (isset($files['images'])) {
            foreach ($files['images'] as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $newName = $file->getRandomName();
                    $file->move(FCPATH . 'uploads', $newName);
                    $imagePaths[] = 'uploads/' . $newName;
                }
            }
        }

        $videoPath = null;
        $video = $this->request->getFile('video');
        if ($video && $video->isValid() && !$video->hasMoved()) {
            $videoName = $video->getRandomName();
            $video->move(FCPATH . 'uploads', $videoName);
            $videoPath = 'uploads/' . $videoName;
        }

        $productModel->insert([
            'title' => $this->request->getPost('title'),
            'brand' => $this->request->getPost('brand') ?? '',
            'condition_tag' => $this->request->getPost('condition_tag'),
            'price_min' => $this->request->getPost('price_min'),
            'price_max' => $this->request->getPost('price_max'),
            'image_paths' => json_encode($imagePaths),
            'category' => $this->request->getPost('category') ?? 'All',
            'description' => $this->request->getPost('description'),
            'location' => $this->request->getPost('location') ?? 'Bangalore, India',
            'video_path' => $videoPath,
            'user_id' => session()->get('user_id'),
            'is_published' => 1,
            'approval_status' => 'pending',
            'status' => 'available',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        session()->setFlashdata('success', 'Product listed successfully! It will be visible after admin approval.');
        return redirect()->to('/profile');
    }
}
