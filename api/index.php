<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$routes = [
    '/' => 'index.php',
    '/index.php' => 'index.php',
    '/login' => 'login.php',
    '/login.php' => 'login.php',
    '/logout' => 'logout.php',
    '/logout.php' => 'logout.php',
    '/api' => 'api.php',
    '/api.php' => 'api.php',
    '/download-backup' => 'download_backup.php',
    '/download_backup.php' => 'download_backup.php',
];

$script = $routes[$path] ?? 'index.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . $script;
