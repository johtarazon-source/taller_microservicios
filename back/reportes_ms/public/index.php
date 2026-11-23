<?php

declare(strict_types=1);

use App\Config\Env;
use App\Exceptions\AppException;
use App\Support\Respuesta;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

Env::cargar(__DIR__ . '/../.env');
date_default_timezone_set(Env::obtener('APP_TIMEZONE', 'America/Bogota'));

$mostrar_detalles_error = Env::obtener('APP_ENV', 'production') !== 'production';

$app = AppFactory::create();

(require __DIR__ . '/../app/Presentation/Routers/endpoints.php')($app);

$app->addRoutingMiddleware();

$manejador_errores = $app->addErrorMiddleware($mostrar_detalles_error, false, false);
$manejador_errores->setDefaultErrorHandler(function (Request $peticion, Throwable $excepcion) use ($app, $mostrar_detalles_error) {
    $respuesta = $app->getResponseFactory()->createResponse();

    if ($excepcion instanceof AppException) {
        return Respuesta::json($respuesta, $excepcion->get_codigo_estado(), $excepcion->getMessage(), $excepcion->get_errores(), false);
    }

    if ($excepcion instanceof HttpMethodNotAllowedException) {
        return Respuesta::json($respuesta, 405, 'Método no permitido.', null, false);
    }

    if ($excepcion instanceof HttpNotFoundException) {
        return Respuesta::json($respuesta, 404, 'La página o recurso solicitado no existe.', null, false);
    }

    error_log($excepcion->getMessage() . "\n" . $excepcion->getTraceAsString());

    return Respuesta::json($respuesta, 500, $mostrar_detalles_error ? $excepcion->getMessage() : 'Error interno del servidor.', null, false);
});

$app->add((require __DIR__ . '/../app/Middlewares/cors.php')($app->getResponseFactory()));

$app->run();
