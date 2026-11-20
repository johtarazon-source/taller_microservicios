<?php

use App\Clients\AuthClient;
use App\Exceptions\UnauthorizedException;
use App\Support\Peticion;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Exige un token Bearer válido; la validación se delega a auth_ms (POST /auth/validar).
 */
return function (Request $peticion, RequestHandler $manejador): Response {
    $token = Peticion::token($peticion);

    if ($token === '' || !(new AuthClient())->validar($token)) {
        throw new UnauthorizedException('Token invalido o expirado.');
    }

    return $manejador->handle($peticion);
};
