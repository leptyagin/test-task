<?php

declare(strict_types=1);

use App\Controller\HomeController;
use App\Core\Router;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);

return $router;
