<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageModel extends Model
{
    protected $table = 'messages';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'negotiation_id', 'sender_id', 'message', 'created_at'
    ];
    protected $useTimestamps = false;
}
