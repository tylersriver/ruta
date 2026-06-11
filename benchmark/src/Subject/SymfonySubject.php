<?php

namespace Ruta\Benchmark\Subject;

use Psr\Http\Message\ServerRequestInterface;
use Ruta\Benchmark\RouteSet;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class SymfonySubject implements Subject
{
    protected RouteSet $set;

    protected RequestContext $context;

    protected UrlMatcherInterface $matcher;

    public function name(): string
    {
        return 'symfony';
    }

    public function prepare(RouteSet $set, string $cacheDir): void
    {
        $this->set = $set;
        $this->build();
    }

    public function build(): void
    {
        $this->context = new RequestContext();
        $this->matcher = new UrlMatcher($this->collection(), $this->context);
    }

    public function dispatch(string $method, string $path, ServerRequestInterface $request): bool
    {
        $this->context->setMethod($method);

        try {
            $this->matcher->match($path);

            return true;
        } catch (ExceptionInterface) {
            return false;
        }
    }

    protected function collection(): RouteCollection
    {
        $collection = new RouteCollection();
        foreach ($this->set->routes as $route) {
            $collection->add($route->handler, new Route(
                $route->path('{', '}'),
                ['_controller' => $route->handler],
                [],
                [],
                '',
                [],
                [$route->method],
            ));
        }

        return $collection;
    }
}
