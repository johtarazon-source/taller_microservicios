<?php

namespace App\Clients;

/**
 * Cliente HTTP hacia vehiculos_ms: reportes_ms no tiene base de datos, compone el resumen con las API.
 */
class VehiculosClient extends ServicioClient
{
    public function __construct(string $token)
    {
        parent::__construct($token, 'vehículos', 'VEHICULOS_MS_URL', 'http://127.0.0.1:8002');
    }

    /** Todos los vehículos (ordenados por marca y modelo) con su estado vigente. */
    public function listar(): array
    {
        [$codigo_estado, $respuesta] = $this->solicitar('GET', '/vehiculos');

        return (array) $this->datos($codigo_estado, $respuesta);
    }
}
