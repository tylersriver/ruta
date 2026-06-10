<?php

use Ruta\Router;
use GuzzleHttp\Psr7\ServerRequest;


it("Create and Add GET Route", function() {
    $router = new Router;
    $router->get('/test', fn() => 'test');
    $match = $router->dispatch(new ServerRequest('GET', '/test'));

    expect($match->getHandler())->toBeCallable();
    expect($match->getAttributes())->toBeArray();
    expect(count($match->getAttributes()))->toEqual(0);
    expect($match->getHandler()())->toEqual('test');

    $match = $router->dispatch(new ServerRequest('POST', '/test'));
    expect($match)->toBeNull();
});

it("Invalid route pattern", function() {
    $router = new Router;
    $router->get('test', fn() => 'test');
})->throws(\Exception::class);

it("Works with groups", function() {
    $router = new Router;
    $router->group('/test', function(Router $r) {
        return $r->get('/:id', fn() => 'test');
    });
    $match = $router->dispatch(new ServerRequest('GET', '/test/1'));

    expect($match->getHandler())->toBeCallable();
    expect($match->getAttributes())->toBeArray();
    expect(count($match->getAttributes()))->toEqual(1);
    expect($match->getAttributes()['id'])->toEqual(1);
    expect($match->getHandler()())->toEqual('test');
});

it("Create and Add GET Route with attribute", function() {
    $router = new Router;
    $router->get('/test/:id', fn() => 'test');
    $match = $router->dispatch(new ServerRequest('GET', '/test/1'));

    expect($match->getHandler())->toBeCallable();
    expect($match->getAttributes())->toBeArray();
    expect(count($match->getAttributes()))->toEqual(1);
    expect($match->getAttributes()['id'])->toEqual(1);
    expect($match->getHandler()())->toEqual('test');

    $match = $router->dispatch(new ServerRequest('POST', '/test/1'));
    expect($match)->toBeNull();
    $match = $router->dispatch(new ServerRequest('POST', '/test'));
    expect($match)->toBeNull();
});

it("Create and Add GET Route with attribute and third part", function() {
    $router = new Router;
    $router->get('/test/:id/foo', fn() => 'test');
    $match = $router->dispatch(new ServerRequest('GET', '/test/1/foo'));

    expect($match->getHandler())->toBeCallable();
    expect($match->getAttributes())->toBeArray();
    expect(count($match->getAttributes()))->toEqual(1);
    expect($match->getAttributes()['id'])->toEqual(1);
    expect($match->getHandler()())->toEqual('test');

    $match = $router->dispatch(new ServerRequest('POST', '/test/1'));
    expect($match)->toBeNull();
    $match = $router->dispatch(new ServerRequest('POST', '/test'));
    expect($match)->toBeNull();
});

it("Create and Add POST Route", function() {
    $router = new Router;
    $router->post('/test', fn() => 'test');
    $match = $router->dispatch(new ServerRequest('POST', '/test'));

    expect($match->getHandler())->toBeCallable();
    expect($match->getAttributes())->toBeArray();
    expect(count($match->getAttributes()))->toEqual(0);
    expect($match->getHandler()())->toEqual('test');

    $match = $router->dispatch(new ServerRequest('GET', '/test/1'));
    expect($match)->toBeNull();
});

it("Create and Add POST Route with attribute", function() {
    $router = new Router;
    $router->post('/test/:id', fn() => 'test');
    $match = $router->dispatch(new ServerRequest('POST', '/test/1'));

    expect($match->getHandler())->toBeCallable();
    expect($match->getAttributes())->toBeArray();
    expect(count($match->getAttributes()))->toEqual(1);
    expect($match->getAttributes()['id'])->toEqual(1);
    expect($match->getHandler()())->toEqual('test');

    $match = $router->dispatch(new ServerRequest('GET', '/test/1'));
    expect($match)->toBeNull();
    $match = $router->dispatch(new ServerRequest('GET', '/test'));
    expect($match)->toBeNull();
});

it("Create and Add PUT Route", function() {
    $router = new Router;
    $router->put('/test', fn() => 'test');
    $match = $router->dispatch(new ServerRequest('PUT', '/test'));

    expect($match->getHandler())->toBeCallable();
    expect($match->getAttributes())->toBeArray();
    expect(count($match->getAttributes()))->toEqual(0);
    expect($match->getHandler()())->toEqual('test');

    $match = $router->dispatch(new ServerRequest('GET', '/test/1'));
    expect($match)->toBeNull();
});

it("Create and Add PUT Route with attribute", function() {
    $router = new Router;
    $router->put('/test/:id', fn() => 'test');
    $match = $router->dispatch(new ServerRequest('PUT', '/test/1'));

    expect($match->getHandler())->toBeCallable();
    expect($match->getAttributes())->toBeArray();
    expect(count($match->getAttributes()))->toEqual(1);
    expect($match->getAttributes()['id'])->toEqual(1);
    expect($match->getHandler()())->toEqual('test');

    $match = $router->dispatch(new ServerRequest('GET', '/test/1'));
    expect($match)->toBeNull();
    $match = $router->dispatch(new ServerRequest('GET', '/test'));
    expect($match)->toBeNull();
});

it("Create and Add DELETE Route", function() {
    $router = new Router;
    $router->delete('/test', fn() => 'test');
    $match = $router->dispatch(new ServerRequest('DELETE', '/test'));

    expect($match->getHandler())->toBeCallable();
    expect($match->getAttributes())->toBeArray();
    expect(count($match->getAttributes()))->toEqual(0);
    expect($match->getHandler()())->toEqual('test');

    $match = $router->dispatch(new ServerRequest('GET', '/test/1'));
    expect($match)->toBeNull();
});

it("Create and Add DELETE Route with attribute", function() {
    $router = new Router;
    $router->delete('/test/:id', fn() => 'test');
    $match = $router->dispatch(new ServerRequest('DELETE', '/test/1'));

    expect($match->getHandler())->toBeCallable();
    expect($match->getAttributes())->toBeArray();
    expect(count($match->getAttributes()))->toEqual(1);
    expect($match->getAttributes()['id'])->toEqual(1);
    expect($match->getHandler()())->toEqual('test');

    $match = $router->dispatch(new ServerRequest('GET', '/test/1'));
    expect($match)->toBeNull();
    $match = $router->dispatch(new ServerRequest('GET', '/test'));
    expect($match)->toBeNull();
});

it("Create and Add OPTIONS Route", function() {
    $router = new Router;
    $router->options('/test', fn() => 'test');
    $match = $router->dispatch(new ServerRequest('OPTIONS', '/test'));

    expect($match->getHandler())->toBeCallable();
    expect($match->getAttributes())->toBeArray();
    expect(count($match->getAttributes()))->toEqual(0);
    expect($match->getHandler()())->toEqual('test');

    $match = $router->dispatch(new ServerRequest('GET', '/test/1'));
    expect($match)->toBeNull();
});

it("Create and Add OPTIONS Route with attribute", function() {
    $router = new Router;
    $router->options('/test/:id', fn() => 'test');
    $match = $router->dispatch(new ServerRequest('OPTIONS', '/test/1'));

    expect($match->getHandler())->toBeCallable();
    expect($match->getAttributes())->toBeArray();
    expect(count($match->getAttributes()))->toEqual(1);
    expect($match->getAttributes()['id'])->toEqual(1);
    expect($match->getHandler()())->toEqual('test');

    $match = $router->dispatch(new ServerRequest('GET', '/test/1'));
    expect($match)->toBeNull();
    $match = $router->dispatch(new ServerRequest('GET', '/test'));
    expect($match)->toBeNull();
});

it("Create and Add PATCH Route", function() {
    $router = new Router;
    $router->patch('/test', fn() => 'test');
    $match = $router->dispatch(new ServerRequest('PATCH', '/test'));

    expect($match->getHandler())->toBeCallable();
    expect($match->getAttributes())->toBeArray();
    expect(count($match->getAttributes()))->toEqual(0);
    expect($match->getHandler()())->toEqual('test');

    $match = $router->dispatch(new ServerRequest('PATCH', '/test/1'));
    expect($match)->toBeNull();
});

it("Create and Add PATCH Route with attribute", function() {
    $router = new Router;
    $router->patch('/test/:id', fn() => 'test');
    $match = $router->dispatch(new ServerRequest('PATCH', '/test/1'));

    expect($match->getHandler())->toBeCallable();
    expect($match->getAttributes())->toBeArray();
    expect(count($match->getAttributes()))->toEqual(1);
    expect($match->getAttributes()['id'])->toEqual(1);
    expect($match->getHandler()())->toEqual('test');

    $match = $router->dispatch(new ServerRequest('GET', '/test/1'));
    expect($match)->toBeNull();
    $match = $router->dispatch(new ServerRequest('GET', '/test'));
    expect($match)->toBeNull();
});