<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testDispatchesMatchingRoute(): void
    {
        $router = new Router();
        $router->get('/hello', static fn (): string => 'hello world');

        $this->expectOutputString('hello world');
        $router->dispatch('GET', '/hello');
    }

    public function testTrailingSlashIsIgnored(): void
    {
        $router = new Router();
        $router->get('/users', static fn (): string => 'ok');

        $this->expectOutputString('ok');
        $router->dispatch('GET', '/users/');
    }

    public function testMethodIsCaseInsensitive(): void
    {
        $router = new Router();
        $router->add('get', '/ping', static fn (): string => 'pong');

        $this->expectOutputString('pong');
        $router->dispatch('GET', '/ping');
    }

    public function testUnknownRouteReturns404(): void
    {
        $router = new Router();

        $this->expectOutputString('404 Not Found');
        $router->dispatch('GET', '/missing');
    }
}
