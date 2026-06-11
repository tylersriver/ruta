<?php

namespace Ruta\Benchmark\Subject;

use Psr\Http\Message\ServerRequestInterface;
use Ruta\Benchmark\RouteSet;
use Ruta\Router;

class RutaSubject implements Subject
{
    protected RouteSet $set;

    protected Router $router;

    public function name(): string
    {
        return 'ruta';
    }

    public function prepare(RouteSet $set, string $cacheDir): void
    {
        $this->set = $set;
        $this->build();
    }

    public function build(): void
    {
        $this->router = $this->register(new Router());
    }

    public function dispatch(string $method, string $path, ServerRequestInterface $request): bool
    {
        return $this->router->dispatch($request) !== null;
    }

    protected function register(Router $router): Router
    {
        foreach ($this->set->routes as $route) {
            $path = $route->path(':');
            match ($route->method) {
                'GET' => $router->get($path, $route->handler),
                'POST' => $router->post($path, $route->handler),
                'PUT' => $router->put($path, $route->handler),
                'DELETE' => $router->delete($path, $route->handler),
                'OPTIONS' => $router->options($path, $route->handler),
            };
        }

        return $router;
    }
}
