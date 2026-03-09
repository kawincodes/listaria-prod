<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BannerModel;

class BannersController extends BaseController
{
    public function index()
    {
        $bannerModel = new BannerModel();
        $banners = $bannerModel->orderBy('display_order', 'ASC')->findAll();

        return view('admin/banners', [
            'activePage' => 'banners',
            'banners' => $banners,
        ]);
    }

    public function upload()
    {
        $bannerModel = new BannerModel();

        $image = $this->request->getFile('image');
        if ($image && $image->isValid() && !$image->hasMoved()) {
            $newName = 'banner_' . time() . '.' . $image->getExtension();
            $image->move(FCPATH . 'uploads/banners', $newName);

            $bannerModel->insert([
                'image_path' => 'uploads/banners/' . $newName,
                'title' => $this->request->getPost('title') ?? '',
                'link_url' => $this->request->getPost('link_url') ?? '',
                'display_order' => $this->request->getPost('display_order') ?? 0,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return redirect()->to('/admin/banners')->with('success', 'Banner uploaded.');
    }

    public function update()
    {
        $bannerModel = new BannerModel();
        $bannerId = $this->request->getPost('banner_id');

        $data = [
            'title' => $this->request->getPost('title'),
            'link_url' => $this->request->getPost('link_url'),
            'display_order' => $this->request->getPost('display_order'),
            'is_active' => $this->request->getPost('is_active') ?? 0,
        ];

        $bannerModel->update($bannerId, $data);
        return redirect()->to('/admin/banners')->with('success', 'Banner updated.');
    }

    public function delete()
    {
        $bannerModel = new BannerModel();
        $bannerModel->delete($this->request->getPost('banner_id'));
        return redirect()->to('/admin/banners')->with('success', 'Banner deleted.');
    }
}
