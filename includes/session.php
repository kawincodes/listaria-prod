<?php
if (session_status() === PHP_SESSION_NONE) {
    $sessionDir = __DIR__ . '/../sessions';
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0750, true);
    }
    if (is_writable($sessionDir)) {
        session_save_path($sessionDir);
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}
