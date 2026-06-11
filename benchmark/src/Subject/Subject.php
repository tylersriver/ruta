<?php

namespace Ruta\Benchmark\Subject;

use Psr\Http\Message\ServerRequestInterface;
use Ruta\Benchmark\RouteSet;

interface Subject
{
    public function name(): string;

    /**
     * Untimed one-off preparation: remember the route set and, for cached
     * subjects, warm the cache file so build() measures the warm path.
     */
    public function prepare(RouteSet $set, string $cacheDir): void;

    /**
     * Timed: build a router that is ready to dispatch.
     */
    public function build(): void;

    /**
     * Timed: dispatch a single request.
     *
     * @return bool true when a route matched
     */
    public function dispatch(string $method, string $path, ServerRequestInterface $request): bool;
}
