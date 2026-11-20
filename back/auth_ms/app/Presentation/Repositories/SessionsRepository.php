<?php

namespace App\Presentation\Repositories;

use App\Controllers\SessionsController;
use App\Support\Peticion;
use App\Support\Respuesta;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Manejadores HTTP de /auth.
 */
class SessionsRepository
{
    private SessionsController $controlador;

    public function __construct()
    {
        $this->controlador = new SessionsController();
    }

    public function login(Request $peticion, Response $respuesta): Response
    {
        return Respuesta::json($respuesta, 200, 'Inicio de sesión exitoso.', $this->controlador->login(Peticion::cuerpo($peticion)));
    }

    public function logout(Request $peticion, Response $respuesta): Response
    {
        $this->controlador->logout(Peticion::token($peticion));

        return Respuesta::json($respuesta, 200, 'Sesión cerrada correctamente.');
    }

    /** Uso interno de los demás microservicios: el middleware ya comprobó el token. */
    public function validar(Request $peticion, Response $respuesta): Response
    {
        return Respuesta::json($respuesta, 200, 'Token válido.', ['valido' => true]);
    }
}
