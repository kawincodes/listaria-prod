<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\BannerModel;
use App\Models\SiteSettingModel;

class HomeController extends BaseController
{
    public function index()
    {
        $productModel = new ProductModel();
        $bannerModel = new BannerModel();

        $search = $this->request->getGet('search');
        $category = $this->request->getGet('category');
        $condition = $this->request->getGet('condition');
        $sort = $this->request->getGet('sort');
        $minPrice = $this->request->getGet('min_price');
        $maxPrice = $this->request->getGet('max_price');

        $builder = $productModel->where('is_published', 1)
            ->where('approval_status', 'approved')
            ->where('status', 'available');

        if ($search) {
            $builder->groupStart()
                ->like('title', $search)
                ->orLike('brand', $search)
                ->orLike('description', $search)
                ->groupEnd();
        }
        if ($category && $category !== 'All') {
            $builder->where('category', $category);
        }
        if ($condition) {
            $builder->where('condition_tag', $condition);
        }
        if ($minPrice) {
            $builder->where('price_min >=', $minPrice);
        }
        if ($maxPrice) {
            $builder->where('price_max <=', $maxPrice);
        }

        switch ($sort) {
            case 'price_low':
                $builder->orderBy('price_min', 'ASC');
                break;
            case 'price_high':
                $builder->orderBy('price_min', 'DESC');
                break;
            case 'oldest':
                $builder->orderBy('created_at', 'ASC');
                break;
            default:
                $builder->orderBy('is_featured', 'DESC')->orderBy('created_at', 'DESC');
        }

        $products = $builder->findAll();

        $banners = $bannerModel->where('is_active', 1)
            ->orderBy('display_order', 'ASC')
            ->findAll();

        $categories = $productModel->select('category')
            ->where('is_published', 1)
            ->where('approval_status', 'approved')
            ->groupBy('category')
            ->findAll();

        return view('home/index', [
            'products' => $products,
            'banners' => $banners,
            'categories' => array_column($categories, 'category'),
            'search' => $search,
            'category' => $category,
            'condition' => $condition,
            'sort' => $sort,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
        ]);
    }
}
