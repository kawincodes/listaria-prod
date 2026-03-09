<?php

namespace App\Controllers;

use App\Models\UserModel;

class AuthController extends BaseController
{
    public function login()
    {
        if (session()->get('user_id')) {
            return redirect()->to('/');
        }
        return view('auth/login', [
            'error' => session()->getFlashdata('error'),
            'success' => session()->getFlashdata('success'),
            'redirect' => $this->request->getGet('redirect') ?? '/',
            'verified' => $this->request->getGet('verified'),
        ]);
    }

    public function loginPost()
    {
        $email = trim($this->request->getPost('email'));
        $password = $this->request->getPost('password');
        $redirect = $this->request->getPost('redirect') ?? '/';

        if (empty($email) || empty($password)) {
            session()->setFlashdata('error', 'Please enter email and password.');
            return redirect()->to('/login');
        }

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        if ($user && password_verify($password, $user['password'])) {
            if (empty($user['email_verified'])) {
                session()->setFlashdata('error', 'Please verify your email address to login.');
                return redirect()->to('/login');
            }

            session()->set([
                'user_id' => $user['id'],
                'full_name' => $user['full_name'],
                'account_type' => $user['account_type'] ?? 'customer',
                'is_admin' => $user['is_admin'] ?? 0,
                'role' => $user['role'] ?? '',
            ]);

            if ($user['is_admin']) {
                return redirect()->to('/admin/dashboard');
            }
            $allowedRedirect = '/';
            if ($redirect && str_starts_with($redirect, '/') && !str_starts_with($redirect, '//')) {
                $allowedRedirect = $redirect;
            }
            return redirect()->to($allowedRedirect);
        }

        session()->setFlashdata('error', 'Invalid email or password.');
        return redirect()->to('/login');
    }

    public function register()
    {
        if (session()->get('user_id')) {
            return redirect()->to('/');
        }
        return view('auth/register', [
            'error' => session()->getFlashdata('error'),
            'success' => session()->getFlashdata('success'),
            'redirect' => $this->request->getGet('redirect') ?? '/',
        ]);
    }

    public function registerPost()
    {
        $fullName = trim($this->request->getPost('full_name'));
        $email = trim($this->request->getPost('email'));
        $password = $this->request->getPost('password');
        $confirmPassword = $this->request->getPost('confirm_password');
        $accountType = $this->request->getPost('account_type') ?? 'customer';

        if (empty($fullName) || empty($email) || empty($password)) {
            session()->setFlashdata('error', 'All fields are required.');
            return redirect()->to('/register');
        }

        if ($password !== $confirmPassword) {
            session()->setFlashdata('error', 'Passwords do not match.');
            return redirect()->to('/register');
        }

        $userModel = new UserModel();
        $existing = $userModel->where('email', $email)->first();
        if ($existing) {
            session()->setFlashdata('error', 'Email already registered.');
            return redirect()->to('/register');
        }

        $verificationToken = bin2hex(random_bytes(32));
        $userModel->insert([
            'full_name' => $fullName,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'email_verified' => 1,
            'verification_token' => $verificationToken,
            'account_type' => $accountType,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        session()->setFlashdata('success', 'Registration successful! You can now log in.');
        return redirect()->to('/login');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }

    public function googleAuth()
    {
        return redirect()->to('/login');
    }

    public function verify()
    {
        $token = $this->request->getGet('token');
        if (!$token) {
            return redirect()->to('/login');
        }

        $userModel = new UserModel();
        $user = $userModel->where('verification_token', $token)->first();
        if ($user) {
            $userModel->update($user['id'], [
                'email_verified' => 1,
                'verification_token' => '',
            ]);
            session()->setFlashdata('success', 'Email verified successfully! You can now login.');
            return redirect()->to('/login?verified=1');
        }

        session()->setFlashdata('error', 'Invalid or expired verification link.');
        return redirect()->to('/login');
    }
}
