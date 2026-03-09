<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'full_name', 'email', 'password', 'address', 'is_admin', 'phone',
        'kyc_status', 'role', 'wallet_balance', 'status', 'email_verified',
        'verification_token', 'profile_views', 'is_verified_vendor', 'vendor_status',
        'account_type', 'business_name', 'profile_image', 'rejection_reason',
        'vendor_applied_at', 'business_bio', 'whatsapp_number', 'gst_number',
        'is_public', 'business_logo', 'created_at'
    ];
    protected $useTimestamps = false;

    public function verifyPassword(string $email, string $password): ?array
    {
        $user = $this->where('email', $email)->first();
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return null;
    }
}
