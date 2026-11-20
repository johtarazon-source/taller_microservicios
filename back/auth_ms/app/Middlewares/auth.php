<?php

use App\Controllers\SessionsController;
use App\Exceptions\UnauthorizedException;
use App\Support\Peticion;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

return function (Request $peticion, RequestHandler $manejador): Response {
    if (!(new SessionsController())->validate_token(Peticion::token($peticion))) {
        throw new UnauthorizedException('Token invalido o expirado.');
    }

    return $manejador->handle($peticion);
};
