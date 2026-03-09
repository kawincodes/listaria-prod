<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\EmailTemplateModel;

class EmailTemplatesController extends BaseController
{
    public function index()
    {
        $templateModel = new EmailTemplateModel();
        $templates = $templateModel->orderBy('template_key', 'ASC')->findAll();

        return view('admin/email_templates', [
            'activePage' => 'email_templates',
            'templates' => $templates,
        ]);
    }

    public function create()
    {
        $templateModel = new EmailTemplateModel();
        $templateModel->insert([
            'template_key' => $this->request->getPost('template_key'),
            'name' => $this->request->getPost('name'),
            'subject' => $this->request->getPost('subject'),
            'body' => $this->request->getPost('body'),
            'variables' => $this->request->getPost('variables') ?? '',
            'is_active' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/admin/email-templates')->with('success', 'Template created.');
    }

    public function update()
    {
        $templateModel = new EmailTemplateModel();
        $templateModel->update($this->request->getPost('template_id'), [
            'name' => $this->request->getPost('name'),
            'subject' => $this->request->getPost('subject'),
            'body' => $this->request->getPost('body'),
            'variables' => $this->request->getPost('variables') ?? '',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/admin/email-templates')->with('success', 'Template updated.');
    }

    public function toggle()
    {
        $templateModel = new EmailTemplateModel();
        $template = $templateModel->find($this->request->getPost('template_id'));
        if ($template) {
            $templateModel->update($template['id'], [
                'is_active' => $template['is_active'] ? 0 : 1,
            ]);
        }
        return redirect()->to('/admin/email-templates')->with('success', 'Template toggled.');
    }

    public function testSend()
    {
        return redirect()->to('/admin/email-templates')->with('success', 'Test email functionality available when SMTP is configured.');
    }
}
