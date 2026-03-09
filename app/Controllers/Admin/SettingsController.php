<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SiteSettingModel;

class SettingsController extends BaseController
{
    public function index()
    {
        $settingModel = new SiteSettingModel();
        $settings = $settingModel->getAllSettings();

        return view('admin/settings', [
            'activePage' => 'settings',
            'settings' => $settings,
        ]);
    }

    public function update()
    {
        $settingModel = new SiteSettingModel();
        $fields = $this->request->getPost();

        foreach ($fields as $key => $value) {
            if ($key !== 'csrf_token') {
                $settingModel->setSetting($key, $value);
            }
        }

        return redirect()->to('/admin/settings')->with('success', 'Settings updated successfully.');
    }
}
