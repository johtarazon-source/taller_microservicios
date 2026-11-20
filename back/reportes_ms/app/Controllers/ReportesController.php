<?php

namespace App\Controllers;

use App\Clients\ClientesClient;
use App\Clients\ReservasClient;
use App\Clients\VehiculosClient;

class ReportesController
{
    private const LIMITE_MARCAS = 8;
    private const ESTADOS_VEHICULO = ['disponible', 'alquilado', 'mantenimiento'];

    private VehiculosClient $vehiculos;
    private ClientesClient $clientes;
    private ReservasClient $reservas;

    public function __construct(string $token, ?VehiculosClient $vehiculos = null, ?ClientesClient $clientes = null, ?ReservasClient $reservas = null)
    {
        $this->vehiculos = $vehiculos ?? new VehiculosClient($token);
        $this->clientes = $clientes ?? new ClientesClient($token);
        $this->reservas = $reservas ?? new ReservasClient($token);
    }

    public function get_resumen(): array
    {
        $vehiculos = $this->vehiculos->listar();
        $resumen_reservas = $this->reservas->resumen();

        return [
            'fecha' => date('Y-m-d'),
            'clientes_activos' => (int) ($resumen_reservas['clientes_activos'] ?? 0),
            'reservas_total' => (int) ($resumen_reservas['reservas_total'] ?? 0),
            'recientes' => $resumen_reservas['recientes'] ?? [],
            'marcas' => $this->marcas($vehiculos),
            'vehiculos' => $this->contar_por_estado($vehiculos),
            'clientes' => $this->clientes->contar(),
            'reservas_activas' => (int) ($resumen_reservas['reservas_activas'] ?? 0),
            'vencidas' => $resumen_reservas['vencidas'] ?? [],
        ];
    }

    private function contar_por_estado(array $vehiculos): array
    {
        $conteo = array_fill_keys(self::ESTADOS_VEHICULO, 0);

        foreach ($vehiculos as $vehiculo) {
            if (isset($conteo[$vehiculo['estado']])) {
                $conteo[$vehiculo['estado']]++;
            }
        }

        return ['total' => array_sum($conteo)] + $conteo;
    }

    private function marcas(array $vehiculos): array
    {
        $marcas = [];

        foreach ($vehiculos as $vehiculo) {
            $marca = (string) $vehiculo['marca'];
            $marcas[mb_strtolower($marca)] ??= $marca;
        }

        ksort($marcas);

        return array_slice(array_values($marcas), 0, self::LIMITE_MARCAS);
    }
}
