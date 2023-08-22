<?php

namespace Ruta;

enum Verb: string
{
    case GET = 'GET';
    case POST = 'POST';
    case DELETE = 'DELETE';
    case OPTIONS = 'OPTIONS';
    case PATCH = 'PATCH';
    case PUT = 'PUT';
}