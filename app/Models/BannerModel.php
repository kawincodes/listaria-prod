<?php

namespace App\Models;

use CodeIgniter\Model;

class BannerModel extends Model
{
    protected $table = 'banners';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'image_path', 'title', 'link_url', 'start_time', 'end_time',
        'display_order', 'is_active', 'created_at'
    ];
    protected $useTimestamps = false;
}
