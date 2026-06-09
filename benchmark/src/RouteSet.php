<?php

namespace Ruta\Benchmark;

final class RouteSet
{
    /**
     * @param RouteDefinition[]                                $routes
     * @param array<string, array<int, array{string, string}>> $scenarios scenario name => list of [method, path] requests.
     *                                                                    Scenario names containing "not found" are expected to miss.
     */
    public function __construct(
        public readonly string $shape,
        public readonly array $routes,
        public readonly array $scenarios,
    ) {
    }

    public function count(): int
    {
        return count($this->routes);
    }
}
