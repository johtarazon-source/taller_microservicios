<?php

use App\Presentation\Repositories\ReportesRepository;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $auth = require __DIR__ . '/../../Middlewares/auth.php';

    $app->group('', function (RouteCollectorProxy $grupo): void {
        $grupo->get('/resumen', ReportesRepository::class . ':resumen');
    })->add($auth);
};
