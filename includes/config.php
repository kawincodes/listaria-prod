<?php
// includes/config.php

function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load .env from project root (assuming this file is in includes/)
$envPath = __DIR__ . '/../.env';
loadEnv($envPath);

// Define Constants if they exist in env
if (getenv('SITE_ROOT_URL')) {
    if (!defined('SITE_ROOT_URL')) {
        define('SITE_ROOT_URL', getenv('SITE_ROOT_URL'));
    }
}

if (getenv('GOOGLE_CLIENT_ID')) {
    if (!defined('GOOGLE_CLIENT_ID')) {
        define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID'));
    }
}

$captchaEnabled = strtolower(getenv('CAPTCHA_ENABLED') ?: 'false');
if (!defined('CAPTCHA_ENABLED')) {
    define('CAPTCHA_ENABLED', in_array($captchaEnabled, ['true', '1', 'yes']));
}

if (getenv('TURNSTILE_SITE_KEY')) {
    if (!defined('TURNSTILE_SITE_KEY')) {
        define('TURNSTILE_SITE_KEY', getenv('TURNSTILE_SITE_KEY'));
    }
}
if (getenv('TURNSTILE_SECRET_KEY')) {
    if (!defined('TURNSTILE_SECRET_KEY')) {
        define('TURNSTILE_SECRET_KEY', getenv('TURNSTILE_SECRET_KEY'));
    }
}

if (getenv('RECAPTCHA_SITE_KEY')) {
    if (!defined('RECAPTCHA_SITE_KEY')) {
        define('RECAPTCHA_SITE_KEY', getenv('RECAPTCHA_SITE_KEY'));
    }
}
if (getenv('RECAPTCHA_SECRET_KEY')) {
    if (!defined('RECAPTCHA_SECRET_KEY')) {
        define('RECAPTCHA_SECRET_KEY', getenv('RECAPTCHA_SECRET_KEY'));
    }
}

function isCaptchaActive($pdo = null) {
    if (!defined('TURNSTILE_SITE_KEY') || !TURNSTILE_SITE_KEY) return false;
    if (!defined('TURNSTILE_SECRET_KEY') || !TURNSTILE_SECRET_KEY) return false;

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'captcha_enabled'");
            $stmt->execute();
            $val = $stmt->fetchColumn();
            if ($val !== false) return $val === '1';
        } catch (Exception $e) {}
    }

    return defined('CAPTCHA_ENABLED') && CAPTCHA_ENABLED;
}

function verifyCaptcha($token, $pdo = null) {
    if (!isCaptchaActive($pdo)) return true;
    if (empty($token)) return false;

    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'secret' => TURNSTILE_SECRET_KEY,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        error_log("Turnstile verification error: " . $err);
        return false;
    }

    $data = json_decode($response, true);
    return !empty($data['success']);
}

if (getenv('SMTP_HOST') && !defined('SMTP_HOST')) {
    define('SMTP_HOST', getenv('SMTP_HOST'));
}
if (getenv('SMTP_PORT') && !defined('SMTP_PORT')) {
    define('SMTP_PORT', getenv('SMTP_PORT'));
}
if (getenv('SMTP_USER') && !defined('SMTP_USER')) {
    define('SMTP_USER', getenv('SMTP_USER'));
}
if (getenv('SMTP_PASS') && !defined('SMTP_PASS')) {
    define('SMTP_PASS', getenv('SMTP_PASS'));
}

function getSmtpConfig($pdo = null) {
    $config = [
        'host' => defined('SMTP_HOST') ? SMTP_HOST : '',
        'port' => defined('SMTP_PORT') ? (int)SMTP_PORT : 465,
        'user' => defined('SMTP_USER') ? SMTP_USER : '',
        'pass' => defined('SMTP_PASS') ? SMTP_PASS : '',
    ];

    if ((!$config['host'] || !$config['user'] || !$config['pass']) && $pdo) {
        try {
            $s = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
            if (!$config['host']) { $s->execute(['smtp_host']); $config['host'] = $s->fetchColumn() ?: ''; }
            if (!$config['user']) { $s->execute(['smtp_user']); $config['user'] = $s->fetchColumn() ?: ''; }
            if (!$config['pass']) { $s->execute(['smtp_pass']); $config['pass'] = $s->fetchColumn() ?: ''; }
            if ($config['port'] === 465) { $s->execute(['smtp_port']); $val = $s->fetchColumn(); if ($val) $config['port'] = (int)$val; }
        } catch (Exception $e) {}
    }

    return $config;
}

function createSmtp($pdo = null) {
    require_once __DIR__ . '/SimpleSMTP.php';
    $cfg = getSmtpConfig($pdo);
    if (!$cfg['host'] || !$cfg['user'] || !$cfg['pass']) {
        throw new Exception('SMTP is not configured. Set SMTP_HOST, SMTP_USER, SMTP_PASS environment variables or configure in Site Settings.');
    }
    return new SimpleSMTP($cfg['host'], $cfg['port'], $cfg['user'], $cfg['pass']);
}
