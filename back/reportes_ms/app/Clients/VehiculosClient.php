<?php

namespace App\Clients;

class VehiculosClient extends ServicioClient
{
    public function __construct(string $token)
    {
        parent::__construct($token, 'vehículos', 'VEHICULOS_MS_URL', 'http://127.0.0.1:8002');
    }

    public function listar(): array
    {
        [$codigo_estado, $respuesta] = $this->solicitar('GET', '/vehiculos');

        return (array) $this->datos($codigo_estado, $respuesta);
    }
}
