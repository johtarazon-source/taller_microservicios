<?php

use App\Config\Env;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * CORS según CORS_ALLOWED_ORIGIN (lista separada por comas, o *). Responde 200 a los preflight OPTIONS.
 */
return function (ResponseFactoryInterface $fabrica_respuestas): callable {
    return function (Request $peticion, RequestHandler $manejador) use ($fabrica_respuestas): Response {
        $respuesta = $peticion->getMethod() === 'OPTIONS'
            ? $fabrica_respuestas->createResponse(200)
            : $manejador->handle($peticion);

        $origenes_permitidos = array_map('trim', explode(',', (string) Env::obtener('CORS_ALLOWED_ORIGIN', 'http://localhost,http://localhost:8080,http://127.0.0.1:8080')));
        $origen = $peticion->getHeaderLine('Origin');

        if (in_array('*', $origenes_permitidos, true)) {
            $respuesta = $respuesta->withHeader('Access-Control-Allow-Origin', '*');
        } elseif ($origen !== '' && in_array($origen, $origenes_permitidos, true)) {
            $respuesta = $respuesta->withHeader('Access-Control-Allow-Origin', $origen)->withHeader('Vary', 'Origin');
        }

        return $respuesta
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
    };
};
