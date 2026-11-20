<?php

namespace App\Presentation\Repositories;

use App\Controllers\VehiculosController;
use App\Support\Peticion;
use App\Support\Respuesta;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Manejadores HTTP de /vehiculos: leen la petición, llaman al controlador y arman la respuesta.
 */
class VehiculosRepository
{
    public function listar(Request $peticion, Response $respuesta): Response
    {
        return Respuesta::json($respuesta, 200, 'Vehículos obtenidos.', $this->controlador($peticion)->get_vehiculos_all($peticion->getQueryParams()));
    }

    public function disponibles(Request $peticion, Response $respuesta): Response
    {
        $datos = $this->controlador($peticion)->get_vehiculos_disponibles(
            Peticion::consulta($peticion, 'fecha_inicio'),
            Peticion::consulta($peticion, 'fecha_fin')
        );

        return Respuesta::json($respuesta, 200, 'Vehículos disponibles obtenidos.', $datos);
    }

    public function lote(Request $peticion, Response $respuesta): Response
    {
        return Respuesta::json($respuesta, 200, 'Vehículos obtenidos.', $this->controlador($peticion)->get_vehiculos_lote(Peticion::consulta($peticion, 'ids')));
    }

    public function crear(Request $peticion, Response $respuesta): Response
    {
        return Respuesta::json($respuesta, 201, 'Vehículo registrado correctamente.', $this->controlador($peticion)->save_vehiculo(Peticion::cuerpo($peticion)));
    }

    public function obtener(Request $peticion, Response $respuesta, array $args): Response
    {
        return Respuesta::json($respuesta, 200, 'Vehículo obtenido.', $this->controlador($peticion)->get_vehiculo((int) $args['id']));
    }

    public function actualizar(Request $peticion, Response $respuesta, array $args): Response
    {
        return Respuesta::json($respuesta, 200, 'Vehículo actualizado correctamente.', $this->controlador($peticion)->update_vehiculo((int) $args['id'], Peticion::cuerpo($peticion)));
    }

    public function eliminar(Request $peticion, Response $respuesta, array $args): Response
    {
        $this->controlador($peticion)->delete_vehiculo((int) $args['id']);

        return Respuesta::json($respuesta, 200, 'Vehículo eliminado correctamente.');
    }

    public function mantenimiento(Request $peticion, Response $respuesta, array $args): Response
    {
        return Respuesta::json($respuesta, 200, 'Vehículo enviado a mantenimiento.', $this->controlador($peticion)->enviar_a_mantenimiento((int) $args['id']));
    }

    public function historial(Request $peticion, Response $respuesta, array $args): Response
    {
        return Respuesta::json($respuesta, 200, 'Historial del vehículo obtenido.', $this->controlador($peticion)->get_historial((int) $args['id']));
    }

    /** Se crea por petición: lleva el token del usuario para reenviarlo a reservas_ms. */
    private function controlador(Request $peticion): VehiculosController
    {
        return new VehiculosController(Peticion::token($peticion));
    }
}
