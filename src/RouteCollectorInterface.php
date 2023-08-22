<?php

namespace Ruta;

interface RouteCollectorInterface
{
    public function get(string $path, string|callable $class): self;

    public function post(string $path, string|callable $class): self;

    public function put(string $path, string|callable $class): self;

    public function delete(string $path, string|callable $class): self;

    public function options(string $path, string|callable $class): self;

    public function patch(string $path, string|callable $class): self;
}
