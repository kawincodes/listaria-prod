<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    private array $superAdminRoutes = [
        'admin/roles',
        'admin/activity',
        'admin/security',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        if (!$session->get('user_id')) {
            return redirect()->to('/login');
        }
        if (!$session->get('is_admin')) {
            return redirect()->to('/');
        }

        $uri = trim($request->getUri()->getPath(), '/');
        foreach ($this->superAdminRoutes as $route) {
            if (str_starts_with($uri, $route)) {
                if ($session->get('role') !== 'super_admin') {
                    return redirect()->to('/admin/dashboard')->with('error', 'Super admin access required.');
                }
                break;
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
