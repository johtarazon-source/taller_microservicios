<?php

use App\Presentation\Repositories\ClientesRepository;
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

    $app->group('/clientes', function (RouteCollectorProxy $grupo): void {
        $grupo->get('', ClientesRepository::class . ':listar');
        $grupo->post('', ClientesRepository::class . ':crear');
        $grupo->get('/{id:[0-9]+}', ClientesRepository::class . ':obtener');
        $grupo->put('/{id:[0-9]+}', ClientesRepository::class . ':actualizar');
        $grupo->delete('/{id:[0-9]+}', ClientesRepository::class . ':eliminar');
        $grupo->get('/{id:[0-9]+}/historial', ClientesRepository::class . ':historial');
    })->add($base_de_datos)->add($auth);
};
