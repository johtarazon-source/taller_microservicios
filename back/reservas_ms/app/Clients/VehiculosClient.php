<?php

namespace App\Clients;

/**
 * Cliente HTTP hacia vehiculos_ms: reservas_ms no lee la base de vehículos, la consulta por API.
 * Solo usa rutas que no vuelven a llamar a reservas_ms (/vehiculos/lote y /vehiculos/{id}/mantenimiento).
 */
class VehiculosClient extends ServicioClient
{
    public const DISPONIBLE = 'disponible';
    public const ALQUILADO = 'alquilado';
    public const MANTENIMIENTO = 'mantenimiento';

    public function __construct(string $token)
    {
        parent::__construct($token, 'vehículos', 'VEHICULOS_MS_URL', 'http://127.0.0.1:8002');
    }

    /**
     * Vehículos con el estado guardado (disponible o mantenimiento; "alquilado" lo deriva reservas_ms).
     *
     * @return array<int, array> vehículos indexados por id (los que no existen no aparecen)
     */
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
