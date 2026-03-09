<?php

namespace App\Controllers;

use App\Models\BlogModel;
use App\Models\UserModel;
use App\Models\WishlistModel;
use App\Models\ProductModel;
use App\Models\ProductRequestModel;
use App\Models\SiteSettingModel;
use App\Models\CustomPageModel;

class PageController extends BaseController
{
    public function blogs()
    {
        $blogModel = new BlogModel();
        $blogs = $blogModel->orderBy('created_at', 'DESC')->findAll();
        return view('pages/blogs', ['blogs' => $blogs]);
    }

    public function blogDetail($id)
    {
        $blogModel = new BlogModel();
        $blog = $blogModel->find($id);
        if (!$blog) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        return view('pages/blog_detail', ['blog' => $blog]);
    }

    public function stores()
    {
        $userModel = new UserModel();
        $stores = $userModel->where('account_type', 'vendor')
            ->where('is_public', 1)
            ->orderBy('created_at', 'DESC')
            ->findAll();
        return view('pages/stores', ['stores' => $stores]);
    }

    public function about()
    {
        $settingModel = new SiteSettingModel();
        $content = $settingModel->getSetting('about_content', '');
        return view('pages/about', ['content' => $content]);
    }

    public function terms()
    {
        $settingModel = new SiteSettingModel();
        $content = $settingModel->getSetting('terms_of_service', '');
        return view('pages/terms', ['content' => $content]);
    }

    public function privacy()
    {
        $settingModel = new SiteSettingModel();
        $content = $settingModel->getSetting('privacy_policy', '');
        return view('pages/privacy', ['content' => $content]);
    }

    public function founders()
    {
        $settingModel = new SiteSettingModel();
        return view('pages/founders', [
            'founder_1_note' => $settingModel->getSetting('founder_1_note', ''),
            'founder_1_image' => $settingModel->getSetting('founder_1_image', ''),
            'founder_2_note' => $settingModel->getSetting('founder_2_note', ''),
            'founder_2_image' => $settingModel->getSetting('founder_2_image', ''),
        ]);
    }

    public function refund()
    {
        return view('pages/refund');
    }

    public function wishlist()
    {
        $wishlistModel = new WishlistModel();
        $productModel = new ProductModel();

        $items = $wishlistModel->where('user_id', session()->get('user_id'))->findAll();
        $products = [];
        foreach ($items as $item) {
            $product = $productModel->find($item['product_id']);
            if ($product) {
                $products[] = $product;
            }
        }
        return view('pages/wishlist', ['products' => $products]);
    }

    public function requests()
    {
        $requestModel = new ProductRequestModel();
        $requests = $requestModel->orderBy('created_at', 'DESC')->findAll(20);
        return view('pages/requests', ['requests' => $requests]);
    }

    public function requestPost()
    {
        $requestModel = new ProductRequestModel();
        $requestModel->insert([
            'user_id' => session()->get('user_id'),
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'budget' => $this->request->getPost('budget'),
            'status' => 'open',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        session()->setFlashdata('success', 'Request posted successfully!');
        return redirect()->to('/requests');
    }

    public function customPage($slug)
    {
        $pageModel = new CustomPageModel();
        $page = $pageModel->where('slug', $slug)->where('is_published', 1)->first();
        if (!$page) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        return view('pages/custom', ['page' => $page]);
    }
}
