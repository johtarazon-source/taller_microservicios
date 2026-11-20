<?php

use App\Presentation\Repositories\ReservasRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

use function App\Config\configurar_base_de_datos;

return function (App $app): void {
    $auth = require __DIR__ . '/../../Middlewares/auth.php';

    $base_de_datos = function (Request $peticion, RequestHandler $manejador): Response {
        configurar_base_de_datos();

        return $manejador->handle($peticion);
    };

    $app->group('/reservas', function (RouteCollectorProxy $grupo): void {
        $grupo->get('/ocupados', ReservasRepository::class . ':ocupados');
        $grupo->get('/resumen', ReservasRepository::class . ':resumen');
        $grupo->get('', ReservasRepository::class . ':listar');
        $grupo->post('', ReservasRepository::class . ':crear');
        $grupo->get('/{id:[0-9]+}', ReservasRepository::class . ':obtener');
        $grupo->post('/{id:[0-9]+}/devolucion', ReservasRepository::class . ':devolucion');
        $grupo->post('/{id:[0-9]+}/cancelacion', ReservasRepository::class . ':cancelacion');
    })->add($base_de_datos)->add($auth);
};
