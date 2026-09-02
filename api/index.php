<?php

use ghosty\taskmgr\bridge\Init;
use ghosty\taskmgr\dto\Request;
use ghosty\taskmgr\util\HTTP\Headers;

require_once __DIR__ . '/../vendor/autoload.php';

$headers = getallheaders();
$body = stream_get_contents(fopen('php://input', 'r'));
$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

$request = new Request($uri, $method, $headers, $body);
$router = Init::init();

$response = $router->route($request);

foreach ($response->getHeaders() as $header) {
    header($header instanceof Headers ? $header->value : $header);
}

http_response_code($response->getStatusCode());

echo $response->getBody();