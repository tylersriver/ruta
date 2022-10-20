<?php

use Ruta\Router;

it('finds routes from attributes', function() {
    $router = new Router;
    $router->loadFromAttributes(__DIR__ . '/Actions', 'Test\Actions');

    expect($router)->toBeInstanceOf(Router::class);
});

it('no classes in folder from attributes', function() {
    $router = new Router;
    $router->loadFromAttributes(__DIR__, 'Test\Actions');

    expect($router)->toBeInstanceOf(Router::class);
});