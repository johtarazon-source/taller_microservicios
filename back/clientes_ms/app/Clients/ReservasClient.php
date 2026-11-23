<?php

namespace App\Clients;

class ReservasClient extends ServicioClient
{
    public function __construct(string $token)
    {
        parent::__construct($token, 'reservas', 'RESERVAS_MS_URL', 'http://127.0.0.1:8004');
    }

    public function tiene_reservas(int $cliente_id): bool
    {
        return $this->listar(['cliente_id' => $cliente_id, 'embeber' => 'ninguno', 'limite' => 1]) !== [];
    }

    public function historial(int $cliente_id): array
    {
        return $this->listar(['cliente_id' => $cliente_id, 'embeber' => 'vehiculo']);
    }

    private function listar(array $consulta): array
    {
        [$codigo_estado, $respuesta] = $this->solicitar('GET', '/reservas', $consulta);

        return (array) $this->datos($codigo_estado, $respuesta);
    }
}
