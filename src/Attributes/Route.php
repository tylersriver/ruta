<?php

namespace Ruta\Attributes;

use Ruta\Verb;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Route
{
    public function __construct(
        public readonly Verb $method,
        public readonly string $route,
    ) {
    }
}
