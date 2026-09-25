<?php
session_start();

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$baseUrl = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if ($baseUrl === '.' || $baseUrl === '/') {
    $baseUrl = '';
}
define('BASE_URL', $baseUrl);

if (BASE_URL !== '') {
    header_register_callback(function () {
        foreach (headers_list() as $headerLine) {
            if (preg_match('~^Location:\\s*(/(?!/).*)$~i', $headerLine, $m)) {
                header_remove('Location');
                header('Location: ' . BASE_URL . $m[1], true);
                break;
            }
        }
    });

    ob_start(function ($html) {
        $base = BASE_URL;
        $html = preg_replace_callback(
            '~\\b(href|src|action)=([\"\\'])/(?!/)~i',
            fn($m) => $m[1] . '=' . $m[2] . $base . '/',
            $html
        );
        $html = preg_replace_callback(
            '~\\bfetch\\(\\s*([\"\\'])/(?!/)~i',
            fn($m) => 'fetch(' . $m[1] . $base . '/',
            $html
        );
        $html = preg_replace_callback(
            '~((?:window\\.)?location(?:\\.href)?\\s*=\\s*)([\"\\'])/(?!/)~i',
            fn($m) => $m[1] . $m[2] . $base . '/',
            $html
        );
        return $html;
    });
}

require_once '../app/config/Database.php';

$url = isset($_GET['url']) ? $_GET['url'] : 'home/index';
$url = rtrim($url, '/');
$url = explode('/', $url);

$controllerName = isset($url[0]) && $url[0] !== '' ? ucfirst($url[0]) . 'Controller' : 'HomeController';
$method = isset($url[1]) && $url[1] !== '' ? $url[1] : 'index';
$params = isset($url[2]) ? array_slice($url, 2) : [];

$controllerPath = '../app/controllers/' . $controllerName . '.php';

if (file_exists($controllerPath)) {
    require_once $controllerPath;
    $controller = new $controllerName;

    if (method_exists($controller, $method)) {
        call_user_func_array([$controller, $method], $params);
    } else {
        http_response_code(404);
        echo 'Error: El método no existe.';
    }
} else {
    header('Location: /auth/index');
    exit;
}
