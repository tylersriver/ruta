<?php

namespace Ruta;

class RouteMatch
{
    private array $attributes;

    private $handler;

    public function __construct(string|callable $handler, array $attributes)
    {
        $this->attributes = $attributes;
        $this->handler = $handler;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getHandler(): string|callable
    {
        return $this->handler;
    }
}
