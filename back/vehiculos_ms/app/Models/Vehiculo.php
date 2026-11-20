<?php

namespace App\Models;

use App\Support\Formato;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    public const DISPONIBLE = 'disponible';
    public const ALQUILADO = 'alquilado';
    public const MANTENIMIENTO = 'mantenimiento';

    protected $table = 'vehiculos';

    protected $fillable = ['marca', 'modelo', 'anio', 'categoria', 'estado'];

    /**
     * El estado guardado solo distingue mantenimiento; "alquilado" depende de las reservas activas
     * (reservas_ms), por eso quien lo sabe pasa `$ocupado`. Sin él, el vehículo se muestra disponible.
     */
    public function to_array(bool $ocupado = false): array
    {
        return [
            'id' => (int) $this->getKey(),
            'marca' => $this->getAttribute('marca'),
            'modelo' => $this->getAttribute('modelo'),
            'anio' => (int) $this->getAttribute('anio'),
            'categoria' => $this->getAttribute('categoria'),
            'estado' => $this->estado_visible($ocupado),
            'created_at' => Formato::marca_tiempo($this->getRawOriginal('created_at')),
            'updated_at' => Formato::marca_tiempo($this->getRawOriginal('updated_at')),
        ];
    }

    private function estado_visible(bool $ocupado): string
    {
        if ($this->getAttribute('estado') === self::MANTENIMIENTO) {
            return self::MANTENIMIENTO;
        }

        return $ocupado ? self::ALQUILADO : self::DISPONIBLE;
    }
}
