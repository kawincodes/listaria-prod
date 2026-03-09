<?php

namespace App\Models;

use CodeIgniter\Model;

class ReturnModel extends Model
{
    protected $table = 'returns';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'order_id', 'user_id', 'product_id', 'reason', 'details',
        'status', 'pickup_date', 'admin_comments', 'created_at', 'updated_at',
        'evidence_photos', 'evidence_video', 'expected_return_date'
    ];
    protected $useTimestamps = false;
}
