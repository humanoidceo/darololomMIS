<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

use App\Core\Router;

$router = new Router();
$registerRoutes = require dirname(__DIR__) . '/config/routes.php';
$registerRoutes($router);

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';

$scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
if ($scriptName !== '/' && str_starts_with($path, $scriptName)) {
    $path = substr($path, strlen($scriptName)) ?: '/';
}

$path = rtrim($path, '/') ?: '/';

if ($path === '/favicon.ico') {
    http_response_code(204);
    exit;
}

$publicRoutes = [
    'GET:/',
    'GET:/login',
    'POST:/login',
    'GET:/articles',
    'GET:/library',
    'GET:/theses',
];

$isPublicDynamicRoute = $method === 'GET'
    && (
        preg_match('#^/library/[0-9]+$#', $path) === 1
        || preg_match('#^/theses/[0-9]+$#', $path) === 1
    );

if (!in_array($method . ':' . $path, $publicRoutes, true) && !$isPublicDynamicRoute && !auth_check()) {
    if (should_remember_intended_path($method, $path)) {
        $_SESSION['_intended'] = $path;
    }
    header('Location: ' . url('/login'));
    exit;
}

$router->dispatch($method, $path);
