<?php

namespace Ruta\Benchmark\Subject;

use Ruta\Benchmark\RouteSet;

use function FastRoute\cachedDispatcher;

class FastRouteCachedSubject extends FastRouteSubject
{
    private string $cacheFile;

    public function name(): string
    {
        return 'fastroute (cached)';
    }

    public function prepare(RouteSet $set, string $cacheDir): void
    {
        $this->set = $set;
        $this->cacheFile = $cacheDir . '/fastroute.cache.php';
        $this->build();
    }

    public function build(): void
    {
        $this->dispatcher = cachedDispatcher($this->collector(), [
            'cacheFile' => $this->cacheFile,
        ]);
    }
}
