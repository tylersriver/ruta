# ruta
Basic PSR-7 request router

# Usage

## Basic
```php
<?php

$r = new Ruta\Router;
$r->get('/test/:id', fn() => 'test');
$r->post('/test/:id', fn() => 'test');
$r->delete('/test/:id', fn() => 'test');

$match = $r->dispatch(new ServerRequest('GET', '/test/1'));

// The mapped callable/class to handle the route
$handler = $match->getHandler(); 
// If a dynamic route those values are stored here
$attrs = $match->getAttributes(); 
```

## In Middleware
This router comes with a pre-configured PSR compliant middlware for doing routing

```php
<?php

/** @var Psr\Http\Message\ResponseFactoryInterface $responseFactory */
$responseFactory = somePsrResponseFactory();
$middleware = new Ruta\RouteMiddleware($r, $responseFactory);

```
