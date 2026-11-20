<?php

use App\Presentation\Repositories\ReportesRepository;
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

    $app->group('', function (RouteCollectorProxy $grupo): void {
        $grupo->get('/resumen', ReportesRepository::class . ':resumen');
    })->add($base_de_datos)->add($auth);
};
