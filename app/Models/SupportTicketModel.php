<?php

namespace App\Models;

use CodeIgniter\Model;

class SupportTicketModel extends Model
{
    protected $table = 'support_tickets';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'name', 'email', 'message', 'created_at',
        'status', 'priority', 'assigned_to', 'category', 'admin_reply'
    ];
    protected $useTimestamps = false;
}
