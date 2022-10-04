<?php

namespace Ruta;

if (!function_exists('Ruta\cachedRouter')) {
    /**
     * The cachedRouter function exists to wrap the creation of a Router object
     * and on the first use cache the routes to a file to retrieve the routes
     * from on future requests.
     *
     * @param callable $routerCollector - callable to get an instance of Router with routes
     * @param array $cacheOptions - data needed to cache the routes in a file
     */
    function cachedRouter(callable $routerCollector, array $cacheOptions): Router
    {
        $cacheEnabled = (bool)(isset($cacheOptions['cacheEnabled']) ? $cacheOptions['cacheEnabled'] : false);

        // If cache disabled create router and exit
        if ($cacheEnabled === false) {
            return $routerCollector(new Router());
        }

        // Cache dir must be supplied at least when cache is enabled
        if (!isset($cacheOptions['cacheDir'])) {
            throw new \Exception('Cache dir is required in $cacheOptions when cache is enabled');
        }
        $cacheDir = $cacheOptions['cacheDir'];
        $version = $cacheOptions['version'] ?? 1;
        $cacheFilePath = $cacheDir . "/routesV$version.php";

        // Get cached routes if exist
        if (file_exists($cacheFilePath)) {
            $routesArray = require $cacheFilePath;
            return new Router($routesArray);
        }

        // Otherwise call collector and cache
        /** @var Router */
        $router = $routerCollector(new Router());
        if ($router->hasClosures()) {
            throw new \Exception(
                'Unable to cache routes because the router contains routes that resolve to anonymous functions'
            );
        }

        // Create cache and return router
        if (!is_dir($cacheDir)) {
            $created = mkdir($cacheDir, 0775, true);
            if ($created === false) {
                throw new \Exception('The cache directory is not writable ' . $cacheDir);
            }
        }
        $routesArr = $router->getRoutes();
        file_put_contents($cacheFilePath, '<?php return ' . var_export($routesArr, true) . ';');

        return $router;
    }
}