<?php

namespace Ruta;

use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface
{
    public function dispatch(ServerRequestInterface $request): ?RouteMatch;
}