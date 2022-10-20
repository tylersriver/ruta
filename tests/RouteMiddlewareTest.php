<?php

use GuzzleHttp\Psr7\HttpFactory;
use Ruta\Router;
use Ruta\RouteMiddleware;
use Test\RequestHandlerExample;
use GuzzleHttp\Psr7\ServerRequest;

it("it works", function() {
    $request = new ServerRequest('GET', '/test/1');
    $app = new RequestHandlerExample;
    $router = new Router;
    $router->get('/test/:id', fn() => 'test');

    $middleware = new RouteMiddleware($router, new HttpFactory);

    $response = $middleware->process($request, $app);
    expect($response->getStatusCode())->toEqual(200);

    $response = $middleware->process(new ServerRequest('POST', '/test/1'), $app);
    expect($response->getStatusCode())->toEqual(404);

    $response = $middleware->process(new ServerRequest('GET', '/test'), $app);
    expect($response->getStatusCode())->toEqual(404);

});