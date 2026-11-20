<?php

namespace App\Clients;

/**
 * Cliente HTTP hacia reservas_ms: vehiculos_ms no lee la base de reservas, la consulta por API.
 * El historial se pide con embeber=cliente para que reservas_ms solo llame a clientes_ms (nunca de vuelta aquí).
 */
class ReservasClient extends ServicioClient
{
    public function __construct(string $token)
    {
        parent::__construct($token, 'reservas', 'RESERVAS_MS_URL', 'http://127.0.0.1:8004');
    }

    /**
     * Ids de vehículos ocupados: sin fechas, los alquilados hoy; con rango, los que se cruzan con él.
     *
     * @return int[]
     */
    public function ids_ocupados(?string $fecha_inicio = null, ?string $fecha_fin = null): array
    {
        $consulta = array_filter(['fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin], fn (?string $valor): bool => $valor !== null);
        [$codigo_estado, $respuesta] = $this->solicitar('GET', '/reservas/ocupados', $consulta);

        return array_map('intval', (array) ($this->datos($codigo_estado, $respuesta)['vehiculo_ids'] ?? []));
    }

    public function tiene_reservas(int $vehiculo_id): bool
    {
        return $this->listar(['vehiculo_id' => $vehiculo_id, 'embeber' => 'ninguno', 'limite' => 1]) !== [];
    }

    /** Reservas del vehículo (la más reciente primero) con el cliente incrustado. */
    public function historial(int $vehiculo_id): array
    {
        return $this->listar(['vehiculo_id' => $vehiculo_id, 'embeber' => 'cliente']);
    }

    private function listar(array $consulta): array
    {
        [$codigo_estado, $respuesta] = $this->solicitar('GET', '/reservas', $consulta);

        return (array) $this->datos($codigo_estado, $respuesta);
    }
}
