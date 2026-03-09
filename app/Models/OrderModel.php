<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'product_id', 'amount', 'payment_method', 'created_at',
        'order_status', 'transaction_id', 'payment_status', 'delivery_date'
    ];
    protected $useTimestamps = false;
}
