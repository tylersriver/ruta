<?php

use GuzzleHttp\Psr7\ServerRequest;
use Ruta\RouteMatch;
use Ruta\Router;
use Test\Actions\TestAction;

use function Ruta\cachedRouter;

it('creates non-cached router with function', function() {
    $router = cachedRouter(function(Router $router) {
        return $router->get('/test', fn() => 'foo');
    },  [
        'cacheEnabled' => false,
        'cacheDir' => __DIR__,
        'version' => 1
    ]);

    expect($router)->toBeInstanceOf(Router::class);
    
    $match = $router->dispatch(new ServerRequest('GET', '/test'));
    expect($match)->toBeInstanceOf(RouteMatch::class);
    expect($match->getHandler())->toBeCallable();
});

it('throws exception because of closures', function() {
    $router = cachedRouter(function(Router $router) {
        return $router->get('/test', fn() => 'foo');
    },  [
        'cacheEnabled' => true,
        'cacheDir' => __DIR__,
        'version' => 1
    ]);
})->throws(\Exception::class);

it('throws exception because of missing cache dir', function() {
    $router = cachedRouter(function(Router $router) {
        return $router->get('/test', fn() => 'foo');
    },  [
        'cacheEnabled' => true,
        'version' => 1
    ]);
})->throws(\Exception::class);


it('Creates router and cache file', function() {

    $cacheDir = __DIR__ . '/tmp'; 
    if(file_exists($cacheDir . '/routesV2.php')) {
        unlink($cacheDir . '/routesV2.php');
    }

    $router = cachedRouter(function(Router $router) {
        $router->patch('/test/:id', TestAction::class);
        return $router;
    },  [
        'cacheEnabled' => true,
        'cacheDir' => $cacheDir,
        'version' => 2
    ]);
    expect($router)->toBeInstanceOf(Router::class);

    assert(file_exists($cacheDir . '/routesV2.php'));

    $router = cachedRouter(function(Router $router) {
        return $router;
    },  [
        'cacheEnabled' => true,
        'cacheDir' => $cacheDir,
        'version' => 2
    ]);
    expect($router)->toBeInstanceOf(Router::class);

    $match = $router->dispatch(new ServerRequest('PATCH', '/test/1'));
    expect($match)->toBeInstanceOf(RouteMatch::class);
    expect($match->getHandler())->toEqual(TestAction::class);
    expect($match->getAttributes())->toBeArray()->toHaveCount(1);
    expect($match->getAttributes()['id'])->toEqual(1);
});