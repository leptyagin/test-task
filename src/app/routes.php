<?php

declare(strict_types=1);

use App\Controller\ArticleController;
use App\Controller\CategoryController;
use App\Controller\HomeController;
use App\Core\Router;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/category', [CategoryController::class, 'show']);
$router->get('/article', [ArticleController::class, 'show']);
$router->get('/articles', [ArticleController::class, 'index']);
$router->get('/categories', [CategoryController::class, 'index']);

return $router;
