<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'title', 'brand', 'condition_tag', 'price_min', 'price_max',
        'image_paths', 'is_published', 'created_at', 'location', 'video_path',
        'category', 'description', 'user_id', 'status', 'approval_status',
        'delivery_status', 'is_featured', 'boosted_until', 'views'
    ];
    protected $useTimestamps = false;
}
