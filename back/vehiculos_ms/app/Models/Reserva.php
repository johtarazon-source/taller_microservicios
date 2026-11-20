<?php

namespace App\Models;

use App\Support\Formato;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reserva extends Model
{
    public const ACTIVA = 'activa';
    public const COMPLETADA = 'completada';
    public const CANCELADA = 'cancelada';
    public const VENCIDA = 'vencida';
    public const PROGRAMADA = 'programada';

    protected $table = 'reservas';

    protected $fillable = ['cliente_id', 'vehiculo_id', 'fecha_inicio', 'fecha_fin', 'estado'];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
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

    /** Incrusta cliente/vehículo solo si la relación fue cargada (with), igual que el monolito. */
    public function to_array(): array
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

        if ($this->relationLoaded('cliente') && $this->getRelation('cliente') !== null) {
            $datos['cliente'] = $this->getRelation('cliente')->to_array();
        }

        if ($this->relationLoaded('vehiculo') && $this->getRelation('vehiculo') !== null) {
            $datos['vehiculo'] = $this->getRelation('vehiculo')->to_array();
        }

        return $datos;
    }

    public static function lista_a_array(iterable $reservas): array
    {
        $lista = [];

        foreach ($reservas as $reserva) {
            $lista[] = $reserva->to_array();
        }

        return $lista;
    }
}
