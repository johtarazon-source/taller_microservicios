<?php

namespace App\Models;

use App\Support\Formato;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehiculo extends Model
{
    public const DISPONIBLE = 'disponible';
    public const ALQUILADO = 'alquilado';
    public const MANTENIMIENTO = 'mantenimiento';

    protected $table = 'vehiculos';

    protected $fillable = ['marca', 'modelo', 'anio', 'categoria', 'estado'];

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'vehiculo_id');
    }

    /** Alquilado si tiene una reserva activa ya iniciada; si no, disponible (el mantenimiento no se toca). */
    public static function sincronizar_estados(): void
    {
        Capsule::connection()->update(
            "UPDATE vehiculos SET estado = CASE WHEN EXISTS (SELECT 1 FROM reservas r WHERE r.vehiculo_id = vehiculos.id "
            . "AND r.estado = 'activa' AND r.fecha_inicio <= ?) THEN 'alquilado' ELSE 'disponible' END "
            . "WHERE estado <> 'mantenimiento'",
            [date('Y-m-d')]
        );
    }

    public function to_array(): array
    {
        return [
            'id' => (int) $this->getKey(),
            'marca' => $this->getAttribute('marca'),
            'modelo' => $this->getAttribute('modelo'),
            'anio' => (int) $this->getAttribute('anio'),
            'categoria' => $this->getAttribute('categoria'),
            'estado' => $this->getAttribute('estado'),
            'created_at' => Formato::marca_tiempo($this->getRawOriginal('created_at')),
            'updated_at' => Formato::marca_tiempo($this->getRawOriginal('updated_at')),
        ];
    }
}
