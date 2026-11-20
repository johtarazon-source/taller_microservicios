<?php

namespace App\Controllers;

use App\Models\Cliente;
use App\Models\Reserva;
use App\Models\Vehiculo;

/**
 * Resumen general del panel (portado de ReportesController/ReportesRepository del monolito).
 */
class ReportesController
{
    private const LIMITE_RESERVAS_RECIENTES = 5;
    private const LIMITE_MARCAS = 8;

    public function get_resumen(): array
    {
        Vehiculo::sincronizar_estados();

        $hoy = Reserva::hoy();
        $por_estado = Vehiculo::query()
            ->selectRaw('estado, COUNT(*) AS total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->map(fn ($total): int => (int) $total)
            ->all();

        $recientes = Reserva::with(['cliente', 'vehiculo'])->orderByDesc('id')->limit(self::LIMITE_RESERVAS_RECIENTES)->get();
        $vencidas = Reserva::with(['cliente', 'vehiculo'])
            ->where('estado', Reserva::ACTIVA)
            ->where('fecha_fin', '<', $hoy)
            ->orderBy('fecha_fin')
            ->get();

        return [
            'fecha' => $hoy,
            'clientes_activos' => (int) Reserva::where('estado', Reserva::ACTIVA)->distinct()->count('cliente_id'),
            'reservas_total' => Reserva::count(),
            'recientes' => Reserva::lista_a_array($recientes),
            'marcas' => Vehiculo::query()->distinct()->orderBy('marca')->limit(self::LIMITE_MARCAS)->pluck('marca')->all(),
            'vehiculos' => [
                'total' => array_sum($por_estado),
                Vehiculo::DISPONIBLE => $por_estado[Vehiculo::DISPONIBLE] ?? 0,
                Vehiculo::ALQUILADO => $por_estado[Vehiculo::ALQUILADO] ?? 0,
                Vehiculo::MANTENIMIENTO => $por_estado[Vehiculo::MANTENIMIENTO] ?? 0,
            ],
            'clientes' => Cliente::count(),
            'reservas_activas' => Reserva::where('estado', Reserva::ACTIVA)->count(),
            'vencidas' => Reserva::lista_a_array($vencidas),
        ];
    }
}
