<?php

namespace App\Presentation\Repositories;

use App\Controllers\ReservasController;
use App\Support\Peticion;
use App\Support\Respuesta;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Manejadores HTTP de /reservas: leen la petición, llaman al controlador y arman la respuesta.
 */
class ReservasRepository
{
    private ReservasController $controlador;

    public function __construct()
    {
        $this->controlador = new ReservasController();
    }

    public function listar(Request $peticion, Response $respuesta): Response
    {
        return Respuesta::json($respuesta, 200, 'Reservas obtenidas.', $this->controlador->get_reservas_all($peticion->getQueryParams()));
    }

    public function crear(Request $peticion, Response $respuesta): Response
    {
        return Respuesta::json($respuesta, 201, 'Reserva registrada correctamente.', $this->controlador->save_reserva(Peticion::cuerpo($peticion)));
    }

    public function obtener(Request $peticion, Response $respuesta, array $args): Response
    {
        return Respuesta::json($respuesta, 200, 'Reserva obtenida.', $this->controlador->get_reserva((int) $args['id']));
    }

    public function devolucion(Request $peticion, Response $respuesta, array $args): Response
    {
        $mantenimiento = filter_var(Peticion::cuerpo($peticion)['mantenimiento'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return Respuesta::json($respuesta, 200, 'Devolución registrada correctamente.', $this->controlador->return_reserva((int) $args['id'], $mantenimiento));
    }

    public function cancelacion(Request $peticion, Response $respuesta, array $args): Response
    {
        return Respuesta::json($respuesta, 200, 'Reserva cancelada correctamente.', $this->controlador->cancel_reserva((int) $args['id']));
    }
}
