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
    public function listar(Request $peticion, Response $respuesta): Response
    {
        return Respuesta::json($respuesta, 200, 'Reservas obtenidas.', $this->controlador($peticion)->get_reservas_all($peticion->getQueryParams()));
    }

    public function ocupados(Request $peticion, Response $respuesta): Response
    {
        return Respuesta::json($respuesta, 200, 'Vehículos ocupados obtenidos.', $this->controlador($peticion)->get_vehiculos_ocupados($peticion->getQueryParams()));
    }

    public function resumen(Request $peticion, Response $respuesta): Response
    {
        return Respuesta::json($respuesta, 200, 'Resumen de reservas obtenido.', $this->controlador($peticion)->get_resumen());
    }

    public function crear(Request $peticion, Response $respuesta): Response
    {
        return Respuesta::json($respuesta, 201, 'Reserva registrada correctamente.', $this->controlador($peticion)->save_reserva(Peticion::cuerpo($peticion)));
    }

    public function obtener(Request $peticion, Response $respuesta, array $args): Response
    {
        return Respuesta::json($respuesta, 200, 'Reserva obtenida.', $this->controlador($peticion)->get_reserva((int) $args['id']));
    }

    public function devolucion(Request $peticion, Response $respuesta, array $args): Response
    {
        $mantenimiento = filter_var(Peticion::cuerpo($peticion)['mantenimiento'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return Respuesta::json($respuesta, 200, 'Devolución registrada correctamente.', $this->controlador($peticion)->return_reserva((int) $args['id'], $mantenimiento));
    }

    public function cancelacion(Request $peticion, Response $respuesta, array $args): Response
    {
        return Respuesta::json($respuesta, 200, 'Reserva cancelada correctamente.', $this->controlador($peticion)->cancel_reserva((int) $args['id']));
    }

    /** Se crea por petición: lleva el token del usuario para reenviarlo a clientes_ms y vehiculos_ms. */
    private function controlador(Request $peticion): ReservasController
    {
        return new ReservasController(Peticion::token($peticion));
    }
}
