<?php

namespace App\Models;

use CodeIgniter\Model;

class BlogModel extends Model
{
    protected $table = 'blogs';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'title', 'category', 'image_path', 'content', 'created_at'
    ];
    protected $useTimestamps = false;
}
