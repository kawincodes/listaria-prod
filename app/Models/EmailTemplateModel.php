<?php

namespace App\Models;

use CodeIgniter\Model;

class EmailTemplateModel extends Model
{
    protected $table = 'email_templates';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'template_key', 'name', 'subject', 'body', 'variables',
        'is_active', 'updated_at'
    ];
    protected $useTimestamps = false;

    public function renderTemplate(string $key, array $vars = []): ?array
    {
        $template = $this->where('template_key', $key)->where('is_active', 1)->first();
        if (!$template) return null;

        $subject = $template['subject'];
        $body = $template['body'];

        foreach ($vars as $k => $v) {
            $subject = str_replace('{{' . $k . '}}', $v, $subject);
            $body = str_replace('{{' . $k . '}}', $v, $body);
        }

        return ['subject' => $subject, 'body' => $body];
    }
}
