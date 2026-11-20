<?php

namespace App\Support;

use DateTime;
use DateTimeZone;

class Formato
{
    /** Convierte un timestamp de BD (zona de la app) a ISO-8601 UTC, igual que el monolito. */
    public static function marca_tiempo(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $fecha = new DateTime($valor, new DateTimeZone(date_default_timezone_get()));

        return $fecha->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z');
    }
}
