<?php

namespace App\Clients;

/**
 * Cliente HTTP hacia clientes_ms: reservas_ms no lee la base de clientes, la consulta por API.
 */
class ClientesClient extends ServicioClient
{
    public function __construct(string $token)
    {
        parent::__construct($token, 'clientes', 'CLIENTES_MS_URL', 'http://127.0.0.1:8003');
    }

    /** Cliente por id; null si no existe. */
    public function obtener(int $id): ?array
    {
        [$codigo_estado, $respuesta] = $this->solicitar('GET', "/clientes/{$id}");

        if ($codigo_estado === 404) {
            return null;
        }

        return $this->datos($codigo_estado, $respuesta);
    }

    /** @return array<int, array> clientes indexados por id (los que no existen no aparecen) */
    public function por_ids(array $ids): array
    {
        $clientes = [];

        foreach ($this->bloques_de_ids($ids) as $bloque) {
            [$codigo_estado, $respuesta] = $this->solicitar('GET', '/clientes', ['ids' => implode(',', $bloque)]);

            foreach ((array) $this->datos($codigo_estado, $respuesta) as $cliente) {
                $clientes[(int) $cliente['id']] = $cliente;
            }
        }

        return $clientes;
    }
}
