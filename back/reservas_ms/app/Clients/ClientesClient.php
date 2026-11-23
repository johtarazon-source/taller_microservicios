<?php

namespace App\Clients;

class ClientesClient extends ServicioClient
{
    public function __construct(string $token)
    {
        parent::__construct($token, 'clientes', 'CLIENTES_MS_URL', 'http://127.0.0.1:8003');
    }

    public function obtener(int $id): ?array
    {
        [$codigo_estado, $respuesta] = $this->solicitar('GET', "/clientes/{$id}");

        if ($codigo_estado === 404) {
            return null;
        }

        return $this->datos($codigo_estado, $respuesta);
    }

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
