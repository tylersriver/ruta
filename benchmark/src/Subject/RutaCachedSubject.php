<?php

namespace Ruta\Benchmark\Subject;

use Ruta\Benchmark\RouteSet;
use Ruta\Router;

use function Ruta\cachedRouter;

class RutaCachedSubject extends RutaSubject
{
    private string $cacheDir;

    public function name(): string
    {
        return 'ruta (cached)';
    }

    public function prepare(RouteSet $set, string $cacheDir): void
    {
        $this->set = $set;
        $this->cacheDir = $cacheDir;
        $this->build();
    }

    public function build(): void
    {
        $this->router = cachedRouter(fn(Router $router) => $this->register($router), [
            'cacheEnabled' => true,
            'cacheDir' => $this->cacheDir,
            'version' => 1,
        ]);
    }
}
