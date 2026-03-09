<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BlogModel;

class BlogsController extends BaseController
{
    public function index()
    {
        $blogModel = new BlogModel();
        $blogs = $blogModel->orderBy('created_at', 'DESC')->findAll();

        return view('admin/blogs', [
            'activePage' => 'blogs',
            'blogs' => $blogs,
        ]);
    }

    public function create()
    {
        $blogModel = new BlogModel();

        $imagePath = 'https://via.placeholder.com/600x400';
        $image = $this->request->getFile('image');
        if ($image && $image->isValid() && !$image->hasMoved()) {
            $newName = $image->getRandomName();
            $image->move(FCPATH . 'uploads', $newName);
            $imagePath = 'uploads/' . $newName;
        }

        $blogModel->insert([
            'title' => $this->request->getPost('title'),
            'category' => $this->request->getPost('category'),
            'content' => $this->request->getPost('content'),
            'image_path' => $imagePath,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/admin/blogs')->with('success', 'Blog created.');
    }

    public function update()
    {
        $blogModel = new BlogModel();
        $blogId = $this->request->getPost('blog_id');

        $data = [
            'title' => $this->request->getPost('title'),
            'category' => $this->request->getPost('category'),
            'content' => $this->request->getPost('content'),
        ];

        $image = $this->request->getFile('image');
        if ($image && $image->isValid() && !$image->hasMoved()) {
            $newName = $image->getRandomName();
            $image->move(FCPATH . 'uploads', $newName);
            $data['image_path'] = 'uploads/' . $newName;
        }

        $blogModel->update($blogId, $data);
        return redirect()->to('/admin/blogs')->with('success', 'Blog updated.');
    }

    public function delete()
    {
        $blogModel = new BlogModel();
        $blogModel->delete($this->request->getPost('blog_id'));
        return redirect()->to('/admin/blogs')->with('success', 'Blog deleted.');
    }
}
