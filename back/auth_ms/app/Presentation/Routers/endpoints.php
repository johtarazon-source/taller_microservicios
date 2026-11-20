<?php

use App\Presentation\Repositories\SessionsRepository;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $auth = require __DIR__ . '/../../Middlewares/auth.php';

    $app->group('/auth', function (RouteCollectorProxy $grupo) use ($auth): void {
        $grupo->post('/login', SessionsRepository::class . ':login');
        $grupo->post('/logout', SessionsRepository::class . ':logout')->add($auth);
        $grupo->post('/validar', SessionsRepository::class . ':validar')->add($auth);
    });
};
