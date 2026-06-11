<?php

namespace Ruta\Benchmark\Subject;

use Ruta\Benchmark\RouteSet;
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Symfony\Component\Routing\RequestContext;

class SymfonyCompiledSubject extends SymfonySubject
{
    private string $cacheFile;

    public function name(): string
    {
        return 'symfony (compiled)';
    }

    public function prepare(RouteSet $set, string $cacheDir): void
    {
        $this->set = $set;
        $this->cacheFile = $cacheDir . '/symfony-compiled.php';

        $dumper = new CompiledUrlMatcherDumper($this->collection());
        file_put_contents($this->cacheFile, $dumper->dump());

        $this->build();
    }

    public function build(): void
    {
        $this->context = new RequestContext();
        $this->matcher = new CompiledUrlMatcher(require $this->cacheFile, $this->context);
    }
}
