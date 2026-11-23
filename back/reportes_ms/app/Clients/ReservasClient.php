<?php

namespace App\Clients;

class ReservasClient extends ServicioClient
{
    public function __construct(string $token)
    {
        parent::__construct($token, 'reservas', 'RESERVAS_MS_URL', 'http://127.0.0.1:8004');
    }

    public function resumen(): array
    {
        [$codigo_estado, $respuesta] = $this->solicitar('GET', '/reservas/resumen');

        return (array) $this->datos($codigo_estado, $respuesta);
    }
}
