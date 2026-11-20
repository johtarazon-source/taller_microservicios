<?php

namespace App\Support;

use Psr\Http\Message\ResponseInterface as Response;

/**
 * Contrato de respuesta común a todos los microservicios: { ok, mensaje, data }.
 */
class Respuesta
{
    public static function json(Response $respuesta, int $codigo_estado, string $mensaje, mixed $datos = null, bool $exito = true): Response
    {
        $respuesta->getBody()->write((string) json_encode(
            ['ok' => $exito, 'mensaje' => $mensaje, 'data' => $datos],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        ));

        return $respuesta->withStatus($codigo_estado)->withHeader('Content-Type', 'application/json');
    }
}
