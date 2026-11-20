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
    private VehiculosController $controlador;

    public function __construct()
    {
        $this->controlador = new VehiculosController();
    }

    public function listar(Request $peticion, Response $respuesta): Response
    {
        return Respuesta::json($respuesta, 200, 'Vehículos obtenidos.', $this->controlador->get_vehiculos_all($peticion->getQueryParams()));
    }

    public function disponibles(Request $peticion, Response $respuesta): Response
    {
        $datos = $this->controlador->get_vehiculos_disponibles(
            Peticion::consulta($peticion, 'fecha_inicio'),
            Peticion::consulta($peticion, 'fecha_fin')
        );

        return Respuesta::json($respuesta, 200, 'Vehículos disponibles obtenidos.', $datos);
    }

    public function crear(Request $peticion, Response $respuesta): Response
    {
        return Respuesta::json($respuesta, 201, 'Vehículo registrado correctamente.', $this->controlador->save_vehiculo(Peticion::cuerpo($peticion)));
    }

    public function obtener(Request $peticion, Response $respuesta, array $args): Response
    {
        return Respuesta::json($respuesta, 200, 'Vehículo obtenido.', $this->controlador->get_vehiculo((int) $args['id']));
    }

    public function actualizar(Request $peticion, Response $respuesta, array $args): Response
    {
        return Respuesta::json($respuesta, 200, 'Vehículo actualizado correctamente.', $this->controlador->update_vehiculo((int) $args['id'], Peticion::cuerpo($peticion)));
    }

    public function eliminar(Request $peticion, Response $respuesta, array $args): Response
    {
        $this->controlador->delete_vehiculo((int) $args['id']);

        return Respuesta::json($respuesta, 200, 'Vehículo eliminado correctamente.');
    }

    public function historial(Request $peticion, Response $respuesta, array $args): Response
    {
        return Respuesta::json($respuesta, 200, 'Historial del vehículo obtenido.', $this->controlador->get_historial((int) $args['id']));
    }
}
