<?php

namespace App\Controllers;

use App\Clients\ReservasClient;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Models\Vehiculo;
use App\Support\Validator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reglas de negocio de vehículos. vehiculos_ms solo tiene su propia base (vehiculos_db): lo que depende de
 * las reservas (qué vehículos están alquilados, el historial) se consulta a reservas_ms por HTTP.
 */
class VehiculosController
{
    private ReservasClient $reservas;

    public function __construct(string $token, ?ReservasClient $reservas = null)
    {
        $this->reservas = $reservas ?? new ReservasClient($token);
    }

    public function get_vehiculos_all(array $filtros): array
    {
        $ocupados = $this->reservas->ids_ocupados();

        $consulta = Vehiculo::query();

        if (!empty($filtros['estado']) && is_string($filtros['estado'])) {
            $this->filtrar_por_estado($consulta, $filtros['estado'], $ocupados);
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

        return $this->a_arreglo($consulta->orderBy('marca')->orderBy('modelo')->get()->all(), $ocupados);
    }

    /**
     * Vehículos por ids (`ids=1,2,3`) con el estado guardado (disponible o mantenimiento). Es la ruta que usa
     * reservas_ms: no consulta reservas, así los dos servicios nunca quedan esperándose entre sí.
     */
    public function get_vehiculos_lote(?string $ids): array
    {
        $lista = array_values(array_filter(array_map('intval', explode(',', (string) $ids)), fn (int $id): bool => $id > 0));

        if ($lista === []) {
            return [];
        }

        return $this->a_arreglo(Vehiculo::whereIn('id', $lista)->orderBy('id')->get()->all(), []);
    }

    public function get_vehiculo(int $id): array
    {
        return $this->buscar_o_lanzar_no_encontrado($id)->to_array($this->esta_ocupado($id));
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
        $ocupado = $this->esta_ocupado($id);

        if (($campos['estado'] ?? null) === Vehiculo::MANTENIMIENTO && $ocupado && $actual->getAttribute('estado') !== Vehiculo::MANTENIMIENTO) {
            throw new ConflictException('El vehículo está alquilado. Registre la devolución antes de enviarlo a mantenimiento.');
        }

        $actual->update($campos);

        return Vehiculo::findOrFail($id)->to_array($ocupado);
    }

    /** Lo usa reservas_ms al devolver un vehículo que necesita revisión. No consulta reservas. */
    public function enviar_a_mantenimiento(int $id): array
    {
        $this->buscar_o_lanzar_no_encontrado($id)->update(['estado' => Vehiculo::MANTENIMIENTO]);

        return Vehiculo::findOrFail($id)->to_array();
    }

    public function delete_vehiculo(int $id): void
    {
        $vehiculo = $this->buscar_o_lanzar_no_encontrado($id);

        if ($this->reservas->tiene_reservas($id)) {
            throw new ConflictException('El vehículo tiene reservas registradas y no se puede eliminar sin perder su historial.');
        }

        $vehiculo->delete();
    }

    public function get_vehiculos_disponibles(?string $fecha_inicio, ?string $fecha_fin): array
    {
        $validador = new Validator(['fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin]);
        $inicio = $validador->fecha('fecha_inicio', 'La fecha de inicio') ?? date('Y-m-d');
        $fin = $validador->fecha('fecha_fin', 'La fecha de fin') ?? $inicio;

        if (!$validador->tiene_error('fecha_inicio') && !$validador->tiene_error('fecha_fin') && $fin < $inicio) {
            $validador->agregar_error('fecha_fin', 'La fecha de fin no puede ser anterior a la de inicio.');
        }

        $validador->verificar();

        $ocupados_en_el_rango = $this->reservas->ids_ocupados($inicio, $fin);

        $vehiculos = Vehiculo::where('estado', '<>', Vehiculo::MANTENIMIENTO)
            ->whereNotIn('id', $ocupados_en_el_rango)
            ->orderBy('marca')
            ->orderBy('modelo')
            ->get();

        return [
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
            'vehiculos' => $this->a_arreglo($vehiculos->all(), $this->reservas->ids_ocupados()),
        ];
    }

    public function get_historial(int $id): array
    {
        $this->buscar_o_lanzar_no_encontrado($id);

        return $this->reservas->historial($id);
    }

    private function buscar_o_lanzar_no_encontrado(int $id): Vehiculo
    {
        $vehiculo = Vehiculo::find($id);

        if ($vehiculo === null) {
            throw new NotFoundException('El vehículo no existe.');
        }

        return $vehiculo;
    }

    private function esta_ocupado(int $id): bool
    {
        return in_array($id, $this->reservas->ids_ocupados(), true);
    }

    /**
     * @param Vehiculo[] $vehiculos
     * @param int[]      $ocupados  ids de los vehículos alquilados ahora
     */
    private function a_arreglo(array $vehiculos, array $ocupados): array
    {
        return array_map(fn (Vehiculo $v): array => $v->to_array(in_array((int) $v->getKey(), $ocupados, true)), $vehiculos);
    }

    /** "alquilado" y "disponible" se derivan de las reservas; solo "mantenimiento" está guardado. */
    private function filtrar_por_estado(Builder $consulta, string $estado, array $ocupados): void
    {
        match ($estado) {
            Vehiculo::MANTENIMIENTO => $consulta->where('estado', Vehiculo::MANTENIMIENTO),
            Vehiculo::ALQUILADO => $consulta->where('estado', '<>', Vehiculo::MANTENIMIENTO)->whereIn('id', $ocupados),
            Vehiculo::DISPONIBLE => $consulta->where('estado', '<>', Vehiculo::MANTENIMIENTO)->whereNotIn('id', $ocupados),
            default => $consulta->whereRaw('1 = 0'),
        };
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
