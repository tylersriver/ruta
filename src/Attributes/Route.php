<?php

namespace Ruta\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Route
{
    public const GET = 'GET';
    public const POST = 'POST';
    public const DELETE = 'DELETE';
    public const PUT = 'PUT';

    public function __construct(
        public string $method,
        public string $route,
    ) {
    }
}