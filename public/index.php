<?php
session_start();

$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$base = rtrim(str_replace('\\', '/', dirname($script)), '/');
if ($base === '.' || $base === '/') {
    $base = '';
}
define('BASE_URL', $base);

if (BASE_URL !== '') {
    header_register_callback(function () {
        foreach (headers_list() as $h) {
            if (preg_match("~^Location:\\s*(/(?!/).*)$~i", $h, $m)) {
                header_remove('Location');
                header('Location: ' . BASE_URL . $m[1], true);
                break;
            }
        }
    });

    ob_start(function ($html) {
        $b = BASE_URL;
        $html = preg_replace("~\\b(href|src|action)=([\"'])/(?!/)~i", '$1=$2' . $b . '/', $html);
        $html = preg_replace("~\\bfetch\\(\\s*([\"'])/(?!/)~i", 'fetch($1' . $b . '/', $html);
        $html = preg_replace("~((?:window\\.)?location(?:\\.href)?\\s*=\\s*)([\"'])/(?!/)~i", '$1$2' . $b . '/', $html);
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
    header('Location: ' . BASE_URL . '/auth/index');
    exit;
}
