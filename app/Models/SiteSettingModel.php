<?php

namespace App\Models;

use CodeIgniter\Model;

class SiteSettingModel extends Model
{
    protected $table = 'site_settings';
    protected $primaryKey = 'id';
    protected $allowedFields = ['setting_key', 'setting_value', 'updated_at'];
    protected $useTimestamps = false;

    public function getSetting(string $key, string $default = ''): string
    {
        $row = $this->where('setting_key', $key)->first();
        return $row ? ($row['setting_value'] ?? $default) : $default;
    }

    public function setSetting(string $key, string $value): void
    {
        $existing = $this->where('setting_key', $key)->first();
        if ($existing) {
            $this->update($existing['id'], [
                'setting_value' => $value,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            $this->insert([
                'setting_key' => $key,
                'setting_value' => $value,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    public function getAllSettings(): array
    {
        $rows = $this->findAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
}
