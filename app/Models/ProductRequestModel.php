<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductRequestModel extends Model
{
    protected $table = 'product_requests';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'title', 'description', 'budget', 'status', 'created_at'
    ];
    protected $useTimestamps = false;
}
