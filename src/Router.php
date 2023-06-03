<?php

namespace Ruta;

use Closure;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use Ruta\Attributes\Route;
use Exception;

class Router implements RouteCollectorInterface, RouterInterface
{
    private array $routeMap;

    private string $routeRegex = '/^(\/:?[\w|-]*)*$/';

    private string $currentGroup = '';

    private array $previousGroup = [];

    private bool $usesClosures = false;

    public function __construct(array $routes = [])
    {
        $this->routeMap = $routes;
    }

    /**
     * @throws Exception
     */
    private function addRoute(string $method, string $path, string|callable $class): Router
    {
        if ($class instanceof Closure) {
            $this->usesClosures = true;
        }

        $path = $this->currentGroup . $path;

        if (preg_match($this->routeRegex, $path) !== 1) {
            throw new Exception('Invalid route pattern');
        }

        $pathParts = explode('/', $path);
        unset($pathParts[0]);

        $current = &$this->routeMap[$method];
        foreach ($pathParts as $part) {
            if (!is_array($current)) {
                $current = [$current];
            }

            if (!array_key_exists($part, $current)) {
                $current[$part] = [];
            }
            $current = &$current[$part];
        }

        $current = $class;

        return $this;
    }

    /**
     * @param  string $actionDir directory that has the action classes
     * @return Router
     */
    public function loadFromAttributes(string $actionDir, string $namespace): Router
    {
        // Get list of Action classes
        $classes = glob($actionDir . '/*.php');
        if ($classes === false) {
            return $this;
        }

        // Loop classes
        foreach ($classes as $class) {
            // create fqcn
            $classParts = explode('/', $class);
            $class = $namespace . '\\' . substr(end($classParts), 0, -4);
            if (class_exists($class) === false) {
                continue;
            }

            // Get Route attribute if exists
            $reflection = new ReflectionClass($class);
            $attributes = $reflection->getAttributes(Route::class);
            if (count($attributes) <= 0) {
                continue;
            }

            // Create the route
            /**
             * @var Route
             */
            $route = $attributes[0]->newInstance();
            $this->addRoute($route->method->value, $route->route, $class);
        }

        return $this;
    }

    /**
     * Add a route group
     *
     * @param string   $name
     * @param callable $callback
     */
    public function group(string $name, callable $callback): void
    {
        $this->previousGroup[] = $this->currentGroup;
        $this->currentGroup .= $name;

        $callback($this);

        $this->currentGroup = array_pop($this->previousGroup);
    }

    public function parseRoute(ServerRequestInterface $request): ?array
    {
        // Grab URI
        $uri = $request->getUri();

        // split URI by /
        $uriParts = explode('/', $uri->getPath());
        unset($uriParts[0]);

        // Place method at front
        array_unshift($uriParts, $request->getMethod());

        // traverse the route map
        $routeMap = $this->routeMap;
        $current = &$routeMap;
        $attrs = [];
        foreach ($uriParts as $part) {
            // When the key doesn't exist we look for
            // dynamic attributes
            if (!is_array($current)) {
                return null;
            }

            if (!array_key_exists($part, $current)) {
                foreach (array_keys($current) as $key) {
                    if (substr($key, 0, 1) === ':') {
                        $attr = substr($key, 1);
                        $attrs[$attr] = $part;
                        $current = &$current[$key];
                        continue 2;
                    }
                }

                return null;
            }
            $current = &$current[$part];
        }

        if (is_array($current)) {
            if (!array_key_exists(0, $current)) {
                return null;
            }

            $current = $current[0];
        }

        return [$current, $attrs];
    }

    /**
     * @param  ServerRequestInterface $request
     * @return RouteMatch|null
     */
    public function dispatch(ServerRequestInterface $request): ?RouteMatch
    {
        // Determine the route
        $route = $this->parseRoute($request);
        if ($route === null) {
            return null;
        }

        $routeExecutable = $route[0];

        return new RouteMatch($routeExecutable, $route[1]);
    }

    public function get(string $path, string|callable $class): self
    {
        return $this->addRoute('GET', $path, $class);
    }

    public function post(string $path, string|callable $class): self
    {
        return $this->addRoute('POST', $path, $class);
    }

    public function put(string $path, string|callable $class): self
    {
        return $this->addRoute('PUT', $path, $class);
    }

    public function delete(string $path, string|callable $class): self
    {
        return $this->addRoute('DELETE', $path, $class);
    }

    public function options(string $path, string|callable $class): self
    {
        return $this->addRoute('OPTIONS', $path, $class);
    }

    public function patch(string $path, string|callable $class): self
    {
        return $this->addRoute('PATCH', $path, $class);
    }

    /**
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routeMap;
    }

    public function hasClosures(): bool
    {
        return $this->usesClosures;
    }
}
