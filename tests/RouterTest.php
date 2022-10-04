<?php

use Ruta\Router;
use GuzzleHttp\Psr7\ServerRequest;

beforeEach(function() {
    $router = new Router();
    $router->get('/test', fn() => 'test');
    $router->post('/test2', fn() => 'test');
    $router->delete('/test3', fn() => 'test');
    $router->put('/test4', fn() => 'test');
    $router->options('/test5', fn() => 'test');
    $this->router = $router;
});

it("Create and Add Route", function() {
    $router = new Router;
    $router->get('/test', fn() => 'test');
    $match = $router->dispatch(new ServerRequest('GET', '/test'));

    expect($match->getHandler())->toBeCallable();
    expect($match->getAttributes())->toBeArray();
    expect(count($match->getAttributes()))->toEqual(0);
});