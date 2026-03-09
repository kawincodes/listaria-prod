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

function getCaptchaConfig($pdo = null) {
    $config = [
        'enabled' => false,
        'provider' => 'turnstile',
        'turnstile_site_key' => getenv('TURNSTILE_SITE_KEY') ?: '',
        'turnstile_secret_key' => getenv('TURNSTILE_SECRET_KEY') ?: '',
        'recaptcha_site_key' => getenv('RECAPTCHA_SITE_KEY') ?: '',
        'recaptcha_secret_key' => getenv('RECAPTCHA_SECRET_KEY') ?: '',
    ];

    $envEnabled = strtolower(getenv('CAPTCHA_ENABLED') ?: 'false');
    $config['enabled'] = in_array($envEnabled, ['true', '1', 'yes']);

    $envProvider = strtolower(getenv('CAPTCHA_PROVIDER') ?: '');
    if ($envProvider) $config['provider'] = $envProvider;

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('captcha_enabled','captcha_provider','turnstile_site_key','turnstile_secret_key','recaptcha_site_key','recaptcha_secret_key')");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            if (isset($rows['captcha_enabled'])) $config['enabled'] = $rows['captcha_enabled'] === '1';
            if (!empty($rows['captcha_provider'])) $config['provider'] = $rows['captcha_provider'];
            if (!empty($rows['turnstile_site_key'])) $config['turnstile_site_key'] = $rows['turnstile_site_key'];
            if (!empty($rows['turnstile_secret_key'])) $config['turnstile_secret_key'] = $rows['turnstile_secret_key'];
            if (!empty($rows['recaptcha_site_key'])) $config['recaptcha_site_key'] = $rows['recaptcha_site_key'];
            if (!empty($rows['recaptcha_secret_key'])) $config['recaptcha_secret_key'] = $rows['recaptcha_secret_key'];
        } catch (Exception $e) {}
    }

    return $config;
}

function isCaptchaActive($pdo = null) {
    $cfg = getCaptchaConfig($pdo);
    if (!$cfg['enabled']) return false;

    if ($cfg['provider'] === 'turnstile') {
        return !empty($cfg['turnstile_site_key']) && !empty($cfg['turnstile_secret_key']);
    } elseif ($cfg['provider'] === 'recaptcha') {
        return !empty($cfg['recaptcha_site_key']) && !empty($cfg['recaptcha_secret_key']);
    }
    return false;
}

function getCaptchaProvider($pdo = null) {
    $cfg = getCaptchaConfig($pdo);
    return $cfg['provider'];
}

function getCaptchaSiteKey($pdo = null) {
    $cfg = getCaptchaConfig($pdo);
    if ($cfg['provider'] === 'turnstile') return $cfg['turnstile_site_key'];
    if ($cfg['provider'] === 'recaptcha') return $cfg['recaptcha_site_key'];
    return '';
}

function verifyCaptcha($token, $pdo = null) {
    if (!isCaptchaActive($pdo)) return true;
    if (empty($token)) return false;

    $cfg = getCaptchaConfig($pdo);

    if ($cfg['provider'] === 'turnstile') {
        $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $secret = $cfg['turnstile_secret_key'];
    } elseif ($cfg['provider'] === 'recaptcha') {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $secret = $cfg['recaptcha_secret_key'];
    } else {
        return true;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'secret' => $secret,
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
        error_log("CAPTCHA verification error ({$cfg['provider']}): " . $err);
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
