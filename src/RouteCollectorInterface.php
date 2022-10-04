<?php

namespace Ruta;

interface RouteCollectorInterface
{
    /**
     * @param  string          $path
     * @param  string|callable $class
     * @return Router
     */
    public function get(string $path, string|callable $class): self;

    /**
     * @param  string          $path
     * @param  string|callable $class
     * @return Router
     */
    public function post(string $path, string|callable $class): self;

    /**
     * @param  string          $path
     * @param  string|callable $class
     * @return Router
     */
    public function put(string $path, string|callable $class): self;

    /**
     * @param  string          $path
     * @param  string|callable $class
     * @return Router
     */
    public function delete(string $path, string|callable $class): self;

    /**
     * @param  string          $path
     * @param  string|callable $class
     * @return Router
     */
    public function options(string $path, string|callable $class): self;
}