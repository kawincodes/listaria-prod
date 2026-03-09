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
