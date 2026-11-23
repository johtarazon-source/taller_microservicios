<?php

namespace App\Support;

use App\Exceptions\AppException;
use Psr\Http\Message\ServerRequestInterface as Request;

class Peticion
{
    public static function token(Request $peticion): string
    {
        $cabecera = $peticion->getHeaderLine('Authorization');

        return str_starts_with($cabecera, 'Bearer ') ? trim(substr($cabecera, 7)) : '';
    }

    public static function cuerpo(Request $peticion): array
    {
        $cuerpo_crudo = (string) $peticion->getBody();
        $tipo_contenido = strtolower(trim(explode(';', $peticion->getHeaderLine('Content-Type'))[0]));
        $cuerpo_interpretado = null;

        if ($tipo_contenido === 'application/json' || str_ends_with($tipo_contenido, '+json')) {
            $datos = json_decode($cuerpo_crudo, true);
            $cuerpo_interpretado = is_array($datos) ? $datos : null;
        } elseif ($tipo_contenido === 'application/x-www-form-urlencoded') {
            parse_str($cuerpo_crudo, $cuerpo_interpretado);
        }

        if ($cuerpo_interpretado === null && trim($cuerpo_crudo) !== '') {
            throw new AppException('El cuerpo de la solicitud no es un JSON válido (UTF-8).', 400);
        }

        return (array) $cuerpo_interpretado;
    }

    public static function consulta(Request $peticion, string $nombre): ?string
    {
        $valor = $peticion->getQueryParams()[$nombre] ?? null;

        return is_string($valor) ? $valor : null;
    }
}
