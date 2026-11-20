<?php

namespace App\Clients;

/**
 * Cliente HTTP hacia reservas_ms: reportes_ms no tiene base de datos, compone el resumen con las API.
 */
class ReservasClient extends ServicioClient
{
    public function __construct(string $token)
    {
        parent::__construct($token, 'reservas', 'RESERVAS_MS_URL', 'http://127.0.0.1:8004');
    }

    /** Totales de reservas y las listas de recientes y vencidas (con cliente y vehículo incrustados). */
    public function resumen(): array
    {
        [$codigo_estado, $respuesta] = $this->solicitar('GET', '/reservas/resumen');

        return (array) $this->datos($codigo_estado, $respuesta);
    }
}
