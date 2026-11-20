<?php

namespace App\Models;

use App\Support\Formato;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    public const ACTIVA = 'activa';
    public const COMPLETADA = 'completada';
    public const CANCELADA = 'cancelada';
    public const VENCIDA = 'vencida';
    public const PROGRAMADA = 'programada';

    protected $table = 'reservas';

    protected $fillable = ['cliente_id', 'vehiculo_id', 'fecha_inicio', 'fecha_fin', 'estado'];

    public static function activas_que_ocupan(?string $inicio = null, ?string $fin = null): Builder
    {
        $consulta = self::query()->where('estado', self::ACTIVA);

        if ($inicio === null || $fin === null) {
            return $consulta->where('fecha_inicio', '<=', self::hoy());
        }

        return $consulta->where('fecha_inicio', '<=', $fin)
            ->where(function (Builder $q) use ($inicio): void {
                $q->where('fecha_fin', '>=', $inicio)->orWhere('fecha_fin', '<', self::hoy());
            });
    }

    public static function ids_vehiculos_ocupados(?string $inicio = null, ?string $fin = null): array
    {
        return self::activas_que_ocupan($inicio, $fin)->distinct()->pluck('vehiculo_id')->map(fn ($id): int => (int) $id)->all();
    }

    public static function hoy(): string
    {
        return date('Y-m-d');
    }

    public function get_estado_visible(): ?string
    {
        $estado = $this->getAttribute('estado');

        if ($estado !== self::ACTIVA) {
            return $estado;
        }

        $hoy = self::hoy();

        if ($this->getAttribute('fecha_fin') < $hoy) {
            return self::VENCIDA;
        }

        return $this->getAttribute('fecha_inicio') > $hoy ? self::PROGRAMADA : self::ACTIVA;
    }

    public function to_array(?array $cliente = null, ?array $vehiculo = null): array
    {
        $datos = [
            'id' => (int) $this->getKey(),
            'cliente_id' => (int) $this->getAttribute('cliente_id'),
            'vehiculo_id' => (int) $this->getAttribute('vehiculo_id'),
            'fecha_inicio' => $this->getAttribute('fecha_inicio'),
            'fecha_fin' => $this->getAttribute('fecha_fin'),
            'estado' => $this->getAttribute('estado'),
            'created_at' => Formato::marca_tiempo($this->getRawOriginal('created_at')),
            'updated_at' => Formato::marca_tiempo($this->getRawOriginal('updated_at')),
            'estado_visible' => $this->get_estado_visible(),
        ];

        if ($cliente !== null) {
            $datos['cliente'] = $cliente;
        }

        if ($vehiculo !== null) {
            $datos['vehiculo'] = $vehiculo;
        }

        return $datos;
    }

    public static function lista_a_array(iterable $reservas, ?array $clientes = null, ?array $vehiculos = null): array
    {
        $lista = [];

        foreach ($reservas as $reserva) {
            $lista[] = $reserva->to_array(
                $clientes[(int) $reserva->getAttribute('cliente_id')] ?? null,
                $vehiculos[(int) $reserva->getAttribute('vehiculo_id')] ?? null
            );
        }

        return $lista;
    }
}
