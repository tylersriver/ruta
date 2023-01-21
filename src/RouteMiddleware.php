<?php

namespace Ruta;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class RouteMiddleware implements MiddlewareInterface
{
    private Router $router;

    private ResponseFactoryInterface $responseFactory;

    public function __construct(
        Router $router,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->router = $router;
        $this->responseFactory = $responseFactory;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $route = $this->router->dispatch($request);

        if ($route === null) {
            return $this->responseFactory->createResponse(404);
        }

        foreach ($route->getAttributes() as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $request = $request->withAttribute('request-handler', $route->getHandler());

        return $handler->handle($request);
    }
}
