<?php

namespace App\Clients;

/**
 * Cliente HTTP hacia clientes_ms: reportes_ms no tiene base de datos, compone el resumen con las API.
 */
class ClientesClient extends ServicioClient
{
    public function __construct(string $token)
    {
        parent::__construct($token, 'clientes', 'CLIENTES_MS_URL', 'http://127.0.0.1:8003');
    }

    public function contar(): int
    {
        [$codigo_estado, $respuesta] = $this->solicitar('GET', '/clientes');

        return count((array) $this->datos($codigo_estado, $respuesta));
    }
}
