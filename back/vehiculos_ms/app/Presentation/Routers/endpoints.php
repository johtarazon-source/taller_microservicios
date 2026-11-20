<?php

use App\Presentation\Repositories\VehiculosRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

use function App\Config\configurar_base_de_datos;

return function (App $app): void {
    $auth = require __DIR__ . '/../../Middlewares/auth.php';

    // Conecta la BD solo después de autenticar (las solicitudes rechazadas no la tocan).
    $base_de_datos = function (Request $peticion, RequestHandler $manejador): Response {
        configurar_base_de_datos();

        return $manejador->handle($peticion);
    };

    $app->group('/vehiculos', function (RouteCollectorProxy $grupo): void {
        $grupo->get('/disponibles', VehiculosRepository::class . ':disponibles');
        $grupo->get('/lote', VehiculosRepository::class . ':lote');
        $grupo->get('', VehiculosRepository::class . ':listar');
        $grupo->post('', VehiculosRepository::class . ':crear');
        $grupo->get('/{id:[0-9]+}', VehiculosRepository::class . ':obtener');
        $grupo->put('/{id:[0-9]+}', VehiculosRepository::class . ':actualizar');
        $grupo->delete('/{id:[0-9]+}', VehiculosRepository::class . ':eliminar');
        $grupo->get('/{id:[0-9]+}/historial', VehiculosRepository::class . ':historial');
        $grupo->post('/{id:[0-9]+}/mantenimiento', VehiculosRepository::class . ':mantenimiento');
    })->add($base_de_datos)->add($auth);
};
