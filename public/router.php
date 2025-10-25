<?php
/**
 * Router for PHP built-in development server
 * Usage: php -S localhost:8000 -t public public/router.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Route API requests
if (preg_match('#^/api/#', $uri)) {
    $_SERVER['SCRIPT_NAME'] = '/api/index.php';
    require __DIR__ . '/../api/index.php';
    exit;
}

// Serve static files
$file = __DIR__ . $uri;
if (is_file($file)) {
    return false; // Serve the file directly
}

// Default to index.html for SPA routing
if (file_exists(__DIR__ . '/index.html')) {
    require __DIR__ . '/index.html';
    exit;
}

http_response_code(404);
echo '404 Not Found';
