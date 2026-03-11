<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

$blocked = [
    '/database.sqlite',
    '/php.ini',
    '/router.php',
    '/php_errors.log',
];

$blockedPrefixes = [
    '/includes/',
    '/.local/',
    '/.git/',
];

$lower = strtolower($uri);
foreach ($blocked as $b) {
    if ($lower === $b) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><body><h1>403 Forbidden</h1></body></html>';
        return true;
    }
}
foreach ($blockedPrefixes as $prefix) {
    if (str_starts_with($lower, $prefix)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><body><h1>403 Forbidden</h1></body></html>';
        return true;
    }
}

if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    $ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
    if (in_array($ext, ['php'])) {
        return false;
    }
    $safe = ['css','js','jpg','jpeg','png','gif','svg','webp','ico','woff','woff2','ttf','eot','map'];
    if (in_array($ext, $safe)) {
        return false;
    }
    http_response_code(403);
    echo '<!DOCTYPE html><html><body><h1>403 Forbidden</h1></body></html>';
    return true;
}

return false;
