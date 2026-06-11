<?php

namespace Ruta\Benchmark;

/**
 * Router-agnostic description of a single route. Each subject renders
 * the segments into its own placeholder syntax (':id' vs '{id}').
 */
final class RouteDefinition
{
    /**
     * @param string                                                 $method   HTTP method
     * @param array<int, array{type: 'static'|'param', value: string}> $segments path segments, in order
     * @param string                                                 $handler  unique handler identifier
     */
    public function __construct(
        public readonly string $method,
        public readonly array $segments,
        public readonly string $handler,
    ) {
    }

    public function path(string $paramPrefix, string $paramSuffix = ''): string
    {
        $parts = [];
        foreach ($this->segments as $segment) {
            $parts[] = $segment['type'] === 'param'
                ? $paramPrefix . $segment['value'] . $paramSuffix
                : $segment['value'];
        }

        return '/' . implode('/', $parts);
    }
}
