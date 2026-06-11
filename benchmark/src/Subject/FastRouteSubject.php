<?php

namespace Ruta\Benchmark\Subject;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Psr\Http\Message\ServerRequestInterface;
use Ruta\Benchmark\RouteSet;

use function FastRoute\simpleDispatcher;

class FastRouteSubject implements Subject
{
    protected RouteSet $set;

    protected Dispatcher $dispatcher;

    public function name(): string
    {
        return 'fastroute';
    }

    public function prepare(RouteSet $set, string $cacheDir): void
    {
        $this->set = $set;
        $this->build();
    }

    public function build(): void
    {
        $this->dispatcher = simpleDispatcher($this->collector());
    }

    public function dispatch(string $method, string $path, ServerRequestInterface $request): bool
    {
        return $this->dispatcher->dispatch($method, $path)[0] === Dispatcher::FOUND;
    }

    protected function collector(): callable
    {
        return function (RouteCollector $collector): void {
            foreach ($this->set->routes as $route) {
                $collector->addRoute($route->method, $route->path('{', '}'), $route->handler);
            }
        };
    }
}
