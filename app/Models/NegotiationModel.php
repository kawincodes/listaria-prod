<?php

namespace App\Models;

use CodeIgniter\Model;

class NegotiationModel extends Model
{
    protected $table = 'negotiations';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'product_id', 'buyer_id', 'seller_id', 'final_price',
        'status', 'created_at', 'is_read', 'is_buyer_read'
    ];
    protected $useTimestamps = false;
}
