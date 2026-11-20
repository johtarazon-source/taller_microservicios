<?php

namespace App\Presentation\Repositories;

use App\Controllers\ClientesController;
use App\Support\Peticion;
use App\Support\Respuesta;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Manejadores HTTP de /clientes: leen la petición, llaman al controlador y arman la respuesta.
 */
class ClientesRepository
{
    private ClientesController $controlador;

    public function __construct()
    {
        $this->controlador = new ClientesController();
    }

    public function listar(Request $peticion, Response $respuesta): Response
    {
        return Respuesta::json($respuesta, 200, 'Clientes obtenidos.', $this->controlador->get_clientes_all(Peticion::consulta($peticion, 'buscar')));
    }

    public function crear(Request $peticion, Response $respuesta): Response
    {
        return Respuesta::json($respuesta, 201, 'Cliente registrado correctamente.', $this->controlador->save_cliente(Peticion::cuerpo($peticion)));
    }

    public function obtener(Request $peticion, Response $respuesta, array $args): Response
    {
        return Respuesta::json($respuesta, 200, 'Cliente obtenido.', $this->controlador->get_cliente((int) $args['id']));
    }

    public function actualizar(Request $peticion, Response $respuesta, array $args): Response
    {
        return Respuesta::json($respuesta, 200, 'Cliente actualizado correctamente.', $this->controlador->update_cliente((int) $args['id'], Peticion::cuerpo($peticion)));
    }

    public function eliminar(Request $peticion, Response $respuesta, array $args): Response
    {
        $this->controlador->delete_cliente((int) $args['id']);

        return Respuesta::json($respuesta, 200, 'Cliente eliminado correctamente.');
    }

    public function historial(Request $peticion, Response $respuesta, array $args): Response
    {
        return Respuesta::json($respuesta, 200, 'Historial del cliente obtenido.', $this->controlador->get_historial((int) $args['id']));
    }
}
