<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use LogicException;

final class Router
{
    /**
     * @var array<string, array<string, Closure>>
     */
    private array $routes = [];

    /**
     * @param (callable(): mixed)|array{0: class-string, 1: string} $handler
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /**
     * @param (callable(): mixed)|array{0: class-string, 1: string} $handler
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /**
     * @param (callable(): mixed)|array{0: class-string, 1: string} $handler
     */
    public function add(string $method, string $path, callable|array $handler): void
    {
        $this->routes[strtoupper($method)][$this->normalize($path)] = $this->toClosure($handler);
    }

    public function dispatch(string $method, string $path): void
    {
        $handler = $this->routes[strtoupper($method)][$this->normalize($path)] ?? null;

        if ($handler === null) {
            http_response_code(404);
            echo '404 Not Found';

            return;
        }

        echo (string) $handler();
    }

    /**
     * @param (callable(): mixed)|array{0: class-string, 1: string} $handler
     */
    private function toClosure(callable|array $handler): Closure
    {
        if (\is_array($handler)) {
            [$class, $action] = $handler;

            return static function () use ($class, $action): mixed {
                $callback = [new $class(), $action];

                if (!\is_callable($callback)) {
                    throw new LogicException('The route refers to an uncallable handler.');
                }

                return $callback();
            };
        }

        return Closure::fromCallable($handler);
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
