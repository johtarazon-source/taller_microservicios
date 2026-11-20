<?php

namespace App\Controllers;

use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Models\Reserva;
use App\Models\Vehiculo;
use App\Support\Validator;

/**
 * Reglas de negocio de vehículos (portadas de VehiculosController del monolito).
 */
class VehiculosController
{
    public function get_vehiculos_all(array $filtros): array
    {
        Vehiculo::sincronizar_estados();

        $consulta = Vehiculo::query();

        if (!empty($filtros['estado']) && is_string($filtros['estado'])) {
            $consulta->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['categoria']) && is_string($filtros['categoria'])) {
            $consulta->where('categoria', $filtros['categoria']);
        }

        if (!empty($filtros['buscar']) && is_string($filtros['buscar'])) {
            $patron = '%' . trim($filtros['buscar']) . '%';
            $consulta->where(function ($q) use ($patron): void {
                $q->where('marca', 'like', $patron)
                    ->orWhere('modelo', 'like', $patron)
                    ->orWhere('categoria', 'like', $patron);
            });
        }

        return $consulta->orderBy('marca')->orderBy('modelo')->get()->map(fn (Vehiculo $v): array => $v->to_array())->all();
    }

    public function get_vehiculo(int $id): array
    {
        return $this->buscar_o_lanzar_no_encontrado($id)->to_array();
    }

    public function save_vehiculo(array $datos): array
    {
        $campos = $this->validar($datos);
        $campos['estado'] ??= Vehiculo::DISPONIBLE;

        $vehiculo = Vehiculo::create($campos);

        return Vehiculo::findOrFail($vehiculo->getKey())->to_array();
    }

    public function update_vehiculo(int $id, array $datos): array
    {
        $actual = $this->buscar_o_lanzar_no_encontrado($id);
        $campos = $this->validar($datos);

        if (($campos['estado'] ?? null) === Vehiculo::MANTENIMIENTO && $actual->getAttribute('estado') === Vehiculo::ALQUILADO) {
            throw new ConflictException('El vehículo está alquilado. Registre la devolución antes de enviarlo a mantenimiento.');
        }

        $actual->update($campos);
        Vehiculo::sincronizar_estados();

        return Vehiculo::findOrFail($id)->to_array();
    }

    public function delete_vehiculo(int $id): void
    {
        $vehiculo = $this->buscar_o_lanzar_no_encontrado($id);

        if ($vehiculo->reservas()->exists()) {
            throw new ConflictException('El vehículo tiene reservas registradas y no se puede eliminar sin perder su historial.');
        }

        $vehiculo->delete();
    }

    public function get_vehiculos_disponibles(?string $fecha_inicio, ?string $fecha_fin): array
    {
        Vehiculo::sincronizar_estados();

        $validador = new Validator(['fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin]);
        $inicio = $validador->fecha('fecha_inicio', 'La fecha de inicio') ?? Reserva::hoy();
        $fin = $validador->fecha('fecha_fin', 'La fecha de fin') ?? $inicio;

        if (!$validador->tiene_error('fecha_inicio') && !$validador->tiene_error('fecha_fin') && $fin < $inicio) {
            $validador->agregar_error('fecha_fin', 'La fecha de fin no puede ser anterior a la de inicio.');
        }

        $validador->verificar();

        $vehiculos = Vehiculo::where('estado', '<>', Vehiculo::MANTENIMIENTO)
            ->whereDoesntHave('reservas', function ($q) use ($inicio, $fin): void {
                $q->where('estado', Reserva::ACTIVA)
                    ->where('fecha_inicio', '<=', $fin)
                    ->where(function ($q2) use ($inicio): void {
                        $q2->where('fecha_fin', '>=', $inicio)->orWhere('fecha_fin', '<', Reserva::hoy());
                    });
            })
            ->orderBy('marca')
            ->orderBy('modelo')
            ->get();

        return [
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
            'vehiculos' => $vehiculos->map(fn (Vehiculo $v): array => $v->to_array())->all(),
        ];
    }

    public function get_historial(int $id): array
    {
        $this->buscar_o_lanzar_no_encontrado($id);

        $reservas = Reserva::with('cliente')
            ->where('vehiculo_id', $id)
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->get();

        return Reserva::lista_a_array($reservas);
    }

    private function buscar_o_lanzar_no_encontrado(int $id): Vehiculo
    {
        Vehiculo::sincronizar_estados();
        $vehiculo = Vehiculo::find($id);

        if ($vehiculo === null) {
            throw new NotFoundException('El vehículo no existe.');
        }

        return $vehiculo;
    }

    private function validar(array $datos): array
    {
        $validador = new Validator($datos);
        $anio_maximo = (int) date('Y') + 1;

        $campos = [
            'marca' => $validador->texto('marca', 'La marca', 100, true),
            'modelo' => $validador->texto('modelo', 'El modelo', 100, true),
            'anio' => $validador->entero('anio', 'El año', 1901, $anio_maximo, true),
            'categoria' => $validador->texto('categoria', 'La categoría', 50),
        ];

        $estado = $validador->opcion('estado', 'El estado', [Vehiculo::DISPONIBLE, Vehiculo::MANTENIMIENTO]);
        $validador->verificar();

        if ($estado !== null) {
            $campos['estado'] = $estado;
        }

        return $campos;
    }
}
