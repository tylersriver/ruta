<?php

use Ruta\Router;

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
});