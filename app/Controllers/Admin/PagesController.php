<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SiteSettingModel;
use App\Models\CustomPageModel;

class PagesController extends BaseController
{
    public function index()
    {
        $settingModel = new SiteSettingModel();
        $pageModel = new CustomPageModel();

        $customPages = $pageModel->orderBy('created_at', 'DESC')->findAll();

        return view('admin/pages', [
            'activePage' => 'pages',
            'settings' => $settingModel->getAllSettings(),
            'customPages' => $customPages,
        ]);
    }

    public function save()
    {
        $settingModel = new SiteSettingModel();
        $key = $this->request->getPost('setting_key');
        $value = $this->request->getPost('setting_value');

        $settingModel->setSetting($key, $value);
        return redirect()->to('/admin/pages')->with('success', 'Page content saved.');
    }

    public function create()
    {
        $pageModel = new CustomPageModel();
        $slug = url_title($this->request->getPost('title'), '-', true);

        $existing = $pageModel->where('slug', $slug)->first();
        if ($existing) {
            $slug .= '-' . time();
        }

        $pageModel->insert([
            'title' => $this->request->getPost('title'),
            'slug' => $slug,
            'content' => $this->request->getPost('content') ?? '',
            'meta_description' => $this->request->getPost('meta_description') ?? '',
            'is_published' => $this->request->getPost('is_published') ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/admin/pages')->with('success', 'Custom page created.');
    }

    public function update()
    {
        $pageModel = new CustomPageModel();
        $pageId = $this->request->getPost('page_id');

        $pageModel->update($pageId, [
            'title' => $this->request->getPost('title'),
            'content' => $this->request->getPost('content'),
            'meta_description' => $this->request->getPost('meta_description'),
            'is_published' => $this->request->getPost('is_published') ?? 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/admin/pages')->with('success', 'Page updated.');
    }

    public function delete()
    {
        $pageModel = new CustomPageModel();
        $pageModel->delete($this->request->getPost('page_id'));
        return redirect()->to('/admin/pages')->with('success', 'Page deleted.');
    }
}
