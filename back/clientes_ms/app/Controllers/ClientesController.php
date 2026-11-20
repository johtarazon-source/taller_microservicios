<?php

namespace App\Controllers;

use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Models\Cliente;
use App\Models\Reserva;
use App\Support\Validator;

/**
 * Reglas de negocio de clientes (portadas de ClientesController del monolito).
 */
class ClientesController
{
    public function get_clientes_all(?string $buscar): array
    {
        $consulta = Cliente::query();

        if ($buscar !== null && trim($buscar) !== '') {
            $patron = '%' . trim($buscar) . '%';
            $consulta->where(function ($q) use ($patron): void {
                $q->where('nombre', 'like', $patron)
                    ->orWhere('correo', 'like', $patron)
                    ->orWhere('telefono', 'like', $patron)
                    ->orWhere('numero_licencia', 'like', $patron);
            });
        }

        return $consulta->orderBy('nombre')->get()->map(fn (Cliente $c): array => $c->to_array())->all();
    }

    public function get_cliente(int $id): array
    {
        return $this->buscar_o_lanzar_no_encontrado($id)->to_array();
    }

    public function save_cliente(array $datos): array
    {
        $campos = $this->validar($datos);
        $cliente = Cliente::create($campos);

        return Cliente::findOrFail($cliente->getKey())->to_array();
    }

    public function update_cliente(int $id, array $datos): array
    {
        $cliente = $this->buscar_o_lanzar_no_encontrado($id);
        $campos = $this->validar($datos, $id);

        $cliente->update($campos);

        return Cliente::findOrFail($id)->to_array();
    }

    public function delete_cliente(int $id): void
    {
        $cliente = $this->buscar_o_lanzar_no_encontrado($id);

        if ($cliente->reservas()->exists()) {
            throw new ConflictException('El cliente tiene reservas registradas y no se puede eliminar sin perder su historial.');
        }

        $cliente->delete();
    }

    public function get_historial(int $id): array
    {
        $this->buscar_o_lanzar_no_encontrado($id);

        $reservas = Reserva::with('vehiculo')
            ->where('cliente_id', $id)
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->get();

        return Reserva::lista_a_array($reservas);
    }

    private function buscar_o_lanzar_no_encontrado(int $id): Cliente
    {
        $cliente = Cliente::find($id);

        if ($cliente === null) {
            throw new NotFoundException('El cliente no existe.');
        }

        return $cliente;
    }

    private function validar(array $datos, ?int $id_actual = null): array
    {
        $validador = new Validator($datos);

        $campos = [
            'nombre' => $validador->texto('nombre', 'El nombre', 150, true),
            'telefono' => $validador->texto('telefono', 'El teléfono', 50),
            'correo' => $validador->correo('correo', 'El correo', 100),
            'numero_licencia' => $validador->texto('numero_licencia', 'El número de licencia', 50),
        ];

        if ($campos['numero_licencia'] !== null && !$validador->tiene_error('numero_licencia')) {
            $existente = Cliente::where('numero_licencia', $campos['numero_licencia']);

            if ($id_actual !== null) {
                $existente->where('id', '<>', $id_actual);
            }

            if ($existente->exists()) {
                $validador->agregar_error('numero_licencia', 'Ya existe un cliente con ese número de licencia.');
            }
        }

        $validador->verificar();

        return $campos;
    }
}
