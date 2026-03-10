<?php
require_once __DIR__ . '/includes/session.php';
$_SESSION['marquee_dismissed'] = true;
http_response_code(204);
