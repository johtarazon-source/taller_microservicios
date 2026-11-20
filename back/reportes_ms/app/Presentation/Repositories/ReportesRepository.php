<?php

namespace App\Presentation\Repositories;

use App\Controllers\ReportesController;
use App\Support\Peticion;
use App\Support\Respuesta;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Manejador HTTP de /resumen.
 */
class ReportesRepository
{
    public function resumen(Request $peticion, Response $respuesta): Response
    {
        return Respuesta::json($respuesta, 200, 'Resumen obtenido.', (new ReportesController(Peticion::token($peticion)))->get_resumen());
    }
}
