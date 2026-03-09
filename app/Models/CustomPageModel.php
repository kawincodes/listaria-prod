<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomPageModel extends Model
{
    protected $table = 'custom_pages';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'title', 'slug', 'content', 'meta_description',
        'is_published', 'created_at', 'updated_at'
    ];
    protected $useTimestamps = false;
}
