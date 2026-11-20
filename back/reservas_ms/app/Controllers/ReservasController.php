<?php

namespace App\Controllers;

use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Models\Cliente;
use App\Models\Reserva;
use App\Models\Vehiculo;
use App\Support\Validator;
use Illuminate\Database\Capsule\Manager as Capsule;
use RuntimeException;

/**
 * Reglas de negocio de reservas (portadas de ReservasController del monolito).
 */
class ReservasController
{
    public function get_reservas_all(array $filtros): array
    {
        Vehiculo::sincronizar_estados();

        $consulta = Reserva::with(['cliente', 'vehiculo']);

        if (!empty($filtros['estado']) && is_string($filtros['estado'])) {
            $consulta->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['cliente_id'])) {
            $consulta->where('cliente_id', (int) $filtros['cliente_id']);
        }

        if (!empty($filtros['vehiculo_id'])) {
            $consulta->where('vehiculo_id', (int) $filtros['vehiculo_id']);
        }

        return Reserva::lista_a_array($consulta->orderByDesc('fecha_inicio')->orderByDesc('id')->get());
    }

    public function get_reserva(int $id): array
    {
        return $this->buscar_o_lanzar_no_encontrado($id)->to_array();
    }

    public function save_reserva(array $datos): array
    {
        $validador = new Validator($datos);
        $cliente_id = $validador->entero('cliente_id', 'El cliente', 1, PHP_INT_MAX, true);
        $vehiculo_id = $validador->entero('vehiculo_id', 'El vehículo', 1, PHP_INT_MAX, true);
        $fecha_inicio = $validador->fecha('fecha_inicio', 'La fecha de inicio', true);
        $fecha_fin = $validador->fecha('fecha_fin', 'La fecha de fin', true);

        if ($fecha_inicio !== null && $fecha_inicio < Reserva::hoy()) {
            $validador->agregar_error('fecha_inicio', 'La fecha de inicio no puede ser anterior a hoy.');
        }

        if ($fecha_inicio !== null && $fecha_fin !== null && $fecha_fin < $fecha_inicio) {
            $validador->agregar_error('fecha_fin', 'La fecha de fin no puede ser anterior a la de inicio.');
        }

        $cliente = $cliente_id !== null ? Cliente::find($cliente_id) : null;

        if ($cliente_id !== null && $cliente === null) {
            $validador->agregar_error('cliente_id', 'El cliente no existe.');
        } elseif ($cliente !== null && trim((string) $cliente->getAttribute('numero_licencia')) === '') {
            $validador->agregar_error('cliente_id', 'El cliente no tiene número de licencia registrado; no puede alquilar.');
        }

        if ($vehiculo_id !== null && !Vehiculo::whereKey($vehiculo_id)->exists()) {
            $validador->agregar_error('vehiculo_id', 'El vehículo no existe.');
        }

        $validador->verificar();

        Vehiculo::sincronizar_estados();

        $id = $this->guardar_si_esta_libre($cliente_id, $vehiculo_id, $fecha_inicio, $fecha_fin);

        Vehiculo::sincronizar_estados();

        return $this->buscar_o_lanzar_no_encontrado($id)->to_array();
    }

    public function return_reserva(int $id, bool $mantenimiento): array
    {
        $reserva = $this->obtener_activa($id);

        if ($reserva->getAttribute('fecha_inicio') > Reserva::hoy()) {
            throw new ConflictException('La reserva aún no ha comenzado; si no se va a usar, cancélela.');
        }

        Capsule::connection()->transaction(function () use ($reserva, $mantenimiento): void {
            $reserva->update(['estado' => Reserva::COMPLETADA]);

            if ($mantenimiento) {
                Vehiculo::whereKey($reserva->getAttribute('vehiculo_id'))->update(['estado' => Vehiculo::MANTENIMIENTO]);
            }
        });

        Vehiculo::sincronizar_estados();

        return $this->buscar_o_lanzar_no_encontrado($id)->to_array();
    }

    public function cancel_reserva(int $id): array
    {
        $reserva = $this->obtener_activa($id);
        $reserva->update(['estado' => Reserva::CANCELADA]);
        Vehiculo::sincronizar_estados();

        return $this->buscar_o_lanzar_no_encontrado($id)->to_array();
    }

    /** Verifica el cruce de fechas e inserta dentro de una transacción con el vehículo bloqueado (FOR UPDATE). */
    private function guardar_si_esta_libre(int $cliente_id, int $vehiculo_id, string $fecha_inicio, string $fecha_fin): int
    {
        return Capsule::connection()->transaction(function () use ($cliente_id, $vehiculo_id, $fecha_inicio, $fecha_fin): int {
            $vehiculo = Vehiculo::whereKey($vehiculo_id)->lockForUpdate()->first();

            if ($vehiculo === null) {
                throw new RuntimeException('El vehículo no existe.');
            }

            if ($vehiculo->getAttribute('estado') === Vehiculo::MANTENIMIENTO) {
                throw new ConflictException('El vehículo está en mantenimiento y no se puede reservar.');
            }

            $hay_conflicto = Reserva::where('vehiculo_id', $vehiculo_id)
                ->where('estado', Reserva::ACTIVA)
                ->where('fecha_inicio', '<=', $fecha_fin)
                ->where(function ($q) use ($fecha_inicio): void {
                    $q->where('fecha_fin', '>=', $fecha_inicio)->orWhere('fecha_fin', '<', Reserva::hoy());
                })
                ->exists();

            if ($hay_conflicto) {
                throw new ConflictException('El vehículo ya tiene una reserva activa que se cruza con esas fechas.');
            }

            $reserva = Reserva::create([
                'cliente_id' => $cliente_id,
                'vehiculo_id' => $vehiculo_id,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'estado' => Reserva::ACTIVA,
            ]);

            return (int) $reserva->getKey();
        });
    }

    private function buscar_o_lanzar_no_encontrado(int $id): Reserva
    {
        $reserva = Reserva::with(['cliente', 'vehiculo'])->find($id);

        if ($reserva === null) {
            throw new NotFoundException('La reserva no existe.');
        }

        return $reserva;
    }

    private function obtener_activa(int $id): Reserva
    {
        $reserva = $this->buscar_o_lanzar_no_encontrado($id);

        if ($reserva->getAttribute('estado') !== Reserva::ACTIVA) {
            throw new ConflictException("La reserva ya está {$reserva->getAttribute('estado')}.");
        }

        return $reserva;
    }
}
