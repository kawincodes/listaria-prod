<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\NegotiationModel;
use App\Models\MessageModel;
use App\Models\ProductModel;
use App\Models\UserModel;

class ChatsController extends BaseController
{
    public function index()
    {
        $negModel = new NegotiationModel();
        $msgModel = new MessageModel();
        $productModel = new ProductModel();
        $userModel = new UserModel();

        $negotiations = $negModel->orderBy('created_at', 'DESC')->findAll();

        foreach ($negotiations as &$neg) {
            $neg['product'] = $productModel->find($neg['product_id']);
            $neg['buyer'] = $userModel->find($neg['buyer_id']);
            $neg['seller'] = $userModel->find($neg['seller_id']);
            $neg['messages'] = $msgModel->where('negotiation_id', $neg['id'])
                ->orderBy('created_at', 'ASC')->findAll();
        }

        return view('admin/chats', [
            'activePage' => 'chats',
            'negotiations' => $negotiations,
        ]);
    }
}
