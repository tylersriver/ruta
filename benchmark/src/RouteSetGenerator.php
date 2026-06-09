<?php

namespace Ruta\Benchmark;

use InvalidArgumentException;

/**
 * Deterministically generates route sets so every run (and every router)
 * benchmarks against exactly the same routes and requests.
 */
final class RouteSetGenerator
{
    private const WORDS = [
        'users', 'orders', 'items', 'posts', 'comments',
        'products', 'invoices', 'tags', 'teams', 'projects',
        'files', 'events', 'jobs', 'notes', 'tasks',
        'accounts', 'reports', 'widgets', 'regions', 'plans',
    ];

    public function generate(string $shape, int $routeCount): RouteSet
    {
        return match ($shape) {
            'rest' => $this->rest($routeCount),
            'static' => $this->static($routeCount),
            default => throw new InvalidArgumentException("Unknown shape '$shape'"),
        };
    }

    /**
     * REST style API: 6 routes per resource under a common /api/v1 prefix,
     * mixing static and parameterised segments.
     */
    private function rest(int $routeCount): RouteSet
    {
        $resourceCount = max(1, intdiv($routeCount, 6));

        $routes = [];
        $resources = [];
        for ($i = 0; $i < $resourceCount; $i++) {
            $resource = $this->word($i);
            $resources[] = $resource;

            $static = fn(string $value): array => ['type' => 'static', 'value' => $value];
            $param = fn(string $value): array => ['type' => 'param', 'value' => $value];
            $base = [$static('api'), $static('v1'), $static($resource)];
            $item = [...$base, $param('id')];

            $n = count($routes);
            $routes[] = new RouteDefinition('GET', $base, "handler_{$n}_list");
            $routes[] = new RouteDefinition('POST', $base, "handler_{$n}_create");
            $routes[] = new RouteDefinition('GET', $item, "handler_{$n}_read");
            $routes[] = new RouteDefinition('PUT', $item, "handler_{$n}_update");
            $routes[] = new RouteDefinition('DELETE', $item, "handler_{$n}_delete");
            $routes[] = new RouteDefinition('GET', [...$item, $static('history')], "handler_{$n}_history");
        }

        $first = $resources[0];
        $middle = $resources[intdiv($resourceCount, 2)];
        $last = $resources[$resourceCount - 1];

        $scenarios = [
            'static (hit)' => [
                ['GET', "/api/v1/$first"],
                ['GET', "/api/v1/$middle"],
                ['GET', "/api/v1/$last"],
            ],
            'dynamic (hit)' => [
                ['GET', "/api/v1/$first/123"],
                ['GET', "/api/v1/$middle/456"],
                ['GET', "/api/v1/$last/789"],
            ],
            'dynamic deep (hit)' => [
                ['GET', "/api/v1/$first/123/history"],
                ['GET', "/api/v1/$middle/456/history"],
                ['GET', "/api/v1/$last/789/history"],
            ],
            'not found' => [
                ['GET', '/api/v1/missing'],
                ['GET', "/api/v1/$first/123/nope"],
                ['GET', '/nope'],
            ],
        ];

        return new RouteSet('rest', $routes, $scenarios);
    }

    /**
     * Purely static documentation-style routes, three segments deep.
     */
    private function static(int $routeCount): RouteSet
    {
        $routes = [];
        $paths = [];
        for ($i = 0; $i < $routeCount; $i++) {
            $segments = [
                ['type' => 'static', 'value' => 'docs'],
                ['type' => 'static', 'value' => $this->word($i % count(self::WORDS))],
                ['type' => 'static', 'value' => 'section-' . intdiv($i, count(self::WORDS))],
            ];
            $routes[] = new RouteDefinition('GET', $segments, "handler_$i");
            $paths[] = '/' . implode('/', array_column($segments, 'value'));
        }

        $scenarios = [
            'static (hit)' => [
                ['GET', $paths[0]],
                ['GET', $paths[intdiv($routeCount, 2)]],
                ['GET', $paths[$routeCount - 1]],
            ],
            'not found' => [
                ['GET', '/docs/missing/section-0'],
                ['GET', '/nope'],
            ],
        ];

        return new RouteSet('static', $routes, $scenarios);
    }

    private function word(int $i): string
    {
        $word = self::WORDS[$i % count(self::WORDS)];
        $suffix = intdiv($i, count(self::WORDS));

        return $suffix === 0 ? $word : "$word-$suffix";
    }
}
