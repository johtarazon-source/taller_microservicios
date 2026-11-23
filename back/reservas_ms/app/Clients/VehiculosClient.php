<?php

namespace App\Clients;

class VehiculosClient extends ServicioClient
{
    public const DISPONIBLE = 'disponible';
    public const ALQUILADO = 'alquilado';
    public const MANTENIMIENTO = 'mantenimiento';

    public function __construct(string $token)
    {
        parent::__construct($token, 'vehículos', 'VEHICULOS_MS_URL', 'http://127.0.0.1:8002');
    }

    public function por_ids(array $ids): array
    {
        $vehiculos = [];

        foreach ($this->bloques_de_ids($ids) as $bloque) {
            [$codigo_estado, $respuesta] = $this->solicitar('GET', '/vehiculos/lote', ['ids' => implode(',', $bloque)]);

            foreach ((array) $this->datos($codigo_estado, $respuesta) as $vehiculo) {
                $vehiculos[(int) $vehiculo['id']] = $vehiculo;
            }
        }

        return $vehiculos;
    }

    public function enviar_a_mantenimiento(int $id): void
    {
        [$codigo_estado, $respuesta] = $this->solicitar('POST', "/vehiculos/{$id}/mantenimiento");
        $this->datos($codigo_estado, $respuesta);
    }
}
