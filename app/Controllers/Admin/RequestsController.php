<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProductRequestModel;
use App\Models\UserModel;

class RequestsController extends BaseController
{
    public function index()
    {
        $requestModel = new ProductRequestModel();
        $userModel = new UserModel();

        $requests = $requestModel->orderBy('created_at', 'DESC')->findAll();

        foreach ($requests as &$req) {
            $req['user'] = $userModel->find($req['user_id']);
        }

        return view('admin/requests', [
            'activePage' => 'requests',
            'requests' => $requests,
        ]);
    }

    public function update()
    {
        $requestModel = new ProductRequestModel();
        $requestModel->update($this->request->getPost('request_id'), [
            'status' => $this->request->getPost('status'),
        ]);
        return redirect()->to('/admin/requests')->with('success', 'Request updated.');
    }
}
