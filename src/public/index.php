<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

/** @var \App\Core\Router $router */
$router = require dirname(__DIR__) . '/app/routes.php';

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
);
