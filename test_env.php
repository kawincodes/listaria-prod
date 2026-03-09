<?php
try {
    require 'includes/db.php';
} catch (Exception $e) {
    echo "DB Connection Failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Testing Environment Configuration...\n";
echo "SITE_ROOT_URL from getenv: " . getenv('SITE_ROOT_URL') . "\n";
echo "SITE_ROOT_URL from constant: " . (defined('SITE_ROOT_URL') ? SITE_ROOT_URL : 'Not Defined') . "\n";

if (defined('SITE_ROOT_URL') && SITE_ROOT_URL === 'http://localhost:8000') {
    echo "SUCCESS: SITE_ROOT_URL loaded correctly.\n";
} else {
    echo "FAILURE: SITE_ROOT_URL not loaded or incorrect.\n";
}

if (defined('GOOGLE_CLIENT_ID')) {
    echo "SUCCESS: GOOGLE_CLIENT_ID loaded: " . GOOGLE_CLIENT_ID . "\n";
} else {
    echo "FAILURE: GOOGLE_CLIENT_ID not defined.\n";
}
?>
