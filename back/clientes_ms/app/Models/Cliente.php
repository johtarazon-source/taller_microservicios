<?php

namespace App\Models;

use App\Support\Formato;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = ['nombre', 'telefono', 'correo', 'numero_licencia'];

    public function to_array(): array
    {
        return [
            'id' => (int) $this->getKey(),
            'nombre' => $this->getAttribute('nombre'),
            'telefono' => $this->getAttribute('telefono'),
            'correo' => $this->getAttribute('correo'),
            'numero_licencia' => $this->getAttribute('numero_licencia'),
            'created_at' => Formato::marca_tiempo($this->getRawOriginal('created_at')),
            'updated_at' => Formato::marca_tiempo($this->getRawOriginal('updated_at')),
        ];
    }
}
