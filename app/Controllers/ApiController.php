<?php

namespace App\Controllers;

use App\Models\NegotiationModel;
use App\Models\MessageModel;
use App\Models\ProductModel;
use App\Models\WishlistModel;

class ApiController extends BaseController
{
    public function chatSend()
    {
        $userId = session()->get('user_id');
        if (!$userId) {
            return $this->response->setJSON(['error' => 'Not authenticated'])->setStatusCode(401);
        }

        $negId = $this->request->getPost('negotiation_id');
        $productId = $this->request->getPost('product_id');
        $message = trim($this->request->getPost('message'));
        $sellerId = $this->request->getPost('seller_id');

        if (!$message) {
            return $this->response->setJSON(['error' => 'Message is required']);
        }

        $negModel = new NegotiationModel();
        $msgModel = new MessageModel();

        if (!$negId && $productId && $sellerId) {
            $existing = $negModel->where('product_id', $productId)
                ->where('buyer_id', $userId)
                ->where('status', 'active')
                ->first();

            if ($existing) {
                $negId = $existing['id'];
            } else {
                $negModel->insert([
                    'product_id' => $productId,
                    'buyer_id' => $userId,
                    'seller_id' => $sellerId,
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $negId = $negModel->getInsertID();
            }
        }

        $msgModel->insert([
            'negotiation_id' => $negId,
            'sender_id' => $userId,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $neg = $negModel->find($negId);
        if ($neg) {
            if ($userId == $neg['seller_id']) {
                $negModel->update($negId, ['is_buyer_read' => 0]);
            } else {
                $negModel->update($negId, ['is_read' => 0]);
            }
        }

        return $this->response->setJSON(['success' => true, 'negotiation_id' => $negId]);
    }

    public function chatMessages($negId)
    {
        $userId = session()->get('user_id');
        if (!$userId) {
            return $this->response->setJSON(['error' => 'Not authenticated'])->setStatusCode(401);
        }

        $negModel = new NegotiationModel();
        $neg = $negModel->find($negId);
        if (!$neg || ($neg['buyer_id'] != $userId && $neg['seller_id'] != $userId && !session()->get('is_admin'))) {
            return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(403);
        }

        $msgModel = new MessageModel();
        $messages = $msgModel->where('negotiation_id', $negId)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        return $this->response->setJSON(['messages' => $messages]);
    }

    public function search()
    {
        $query = $this->request->getGet('q');
        $productModel = new ProductModel();

        $results = $productModel->where('is_published', 1)
            ->where('approval_status', 'approved')
            ->groupStart()
            ->like('title', $query)
            ->orLike('brand', $query)
            ->groupEnd()
            ->findAll(10);

        return $this->response->setJSON(['results' => $results]);
    }

    public function wishlistToggle()
    {
        $userId = session()->get('user_id');
        if (!$userId) {
            return $this->response->setJSON(['error' => 'Not authenticated'])->setStatusCode(401);
        }

        $productId = $this->request->getPost('product_id');
        $wishlistModel = new WishlistModel();

        $existing = $wishlistModel->where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $wishlistModel->delete($existing['id']);
            return $this->response->setJSON(['status' => 'removed']);
        }

        $wishlistModel->insert([
            'user_id' => $userId,
            'product_id' => $productId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON(['status' => 'added']);
    }

    public function bulkUpload()
    {
        $userId = session()->get('user_id');
        if (!$userId) {
            return $this->response->setJSON(['error' => 'Not authenticated'])->setStatusCode(401);
        }

        $file = $this->request->getFile('csv_file');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['error' => 'Invalid file']);
        }

        $productModel = new ProductModel();
        $count = 0;

        if (($handle = fopen($file->getTempName(), 'r')) !== false) {
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= 5) {
                    $productModel->insert([
                        'title' => $row[0],
                        'brand' => $row[1],
                        'condition_tag' => $row[2],
                        'price_min' => $row[3],
                        'price_max' => $row[4],
                        'category' => $row[5] ?? 'All',
                        'description' => $row[6] ?? '',
                        'image_paths' => '[]',
                        'user_id' => $userId,
                        'is_published' => 1,
                        'approval_status' => 'pending',
                        'status' => 'available',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    $count++;
                }
            }
            fclose($handle);
        }

        return $this->response->setJSON(['success' => true, 'count' => $count]);
    }

    public function validateCoupon()
    {
        $code = $this->request->getPost('code');
        return $this->response->setJSON(['valid' => false, 'message' => 'Invalid coupon code']);
    }

    public function updateListing()
    {
        $userId = session()->get('user_id');
        $productId = $this->request->getPost('product_id');

        $productModel = new ProductModel();
        $product = $productModel->find($productId);

        if (!$product || ($product['user_id'] != $userId && !session()->get('is_admin'))) {
            return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(403);
        }

        $data = [];
        $fields = ['title', 'brand', 'price_min', 'price_max', 'description', 'category', 'condition_tag', 'location'];
        foreach ($fields as $field) {
            $val = $this->request->getPost($field);
            if ($val !== null) {
                $data[$field] = $val;
            }
        }

        if (!empty($data)) {
            $productModel->update($productId, $data);
        }

        return $this->response->setJSON(['success' => true]);
    }

    public function bulkListingAction()
    {
        $userId = session()->get('user_id');
        $action = $this->request->getPost('action');
        $ids = $this->request->getPost('ids');

        if (!$ids || !is_array($ids)) {
            return $this->response->setJSON(['error' => 'No items selected']);
        }

        $productModel = new ProductModel();

        foreach ($ids as $id) {
            $product = $productModel->find($id);
            if ($product && ($product['user_id'] == $userId || session()->get('is_admin'))) {
                switch ($action) {
                    case 'delete':
                        $productModel->delete($id);
                        break;
                    case 'unpublish':
                        $productModel->update($id, ['is_published' => 0]);
                        break;
                    case 'publish':
                        $productModel->update($id, ['is_published' => 1]);
                        break;
                }
            }
        }

        return $this->response->setJSON(['success' => true]);
    }
}
