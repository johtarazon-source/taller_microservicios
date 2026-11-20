<?php

namespace App\Controllers;

use App\Clients\ClientesClient;
use App\Clients\VehiculosClient;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Models\Reserva;
use App\Support\Validator;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;

/**
 * Reglas de negocio de reservas. reservas_ms solo tiene su propia base (reservas_db): los datos de
 * clientes y vehículos se consultan a sus microservicios por HTTP.
 */
class ReservasController
{
    private const LIMITE_RECIENTES = 5;
    private const SEGUNDOS_ESPERA_BLOQUEO = 5;

    /** Datos de otros servicios que se incrustan en cada reserva (parámetro `embeber`). */
    private const EMBEBER_AMBOS = 'ambos';
    private const EMBEBER_CLIENTE = 'cliente';
    private const EMBEBER_VEHICULO = 'vehiculo';
    private const EMBEBER_NINGUNO = 'ninguno';

    private ClientesClient $clientes;
    private VehiculosClient $vehiculos;

    public function __construct(string $token, ?ClientesClient $clientes = null, ?VehiculosClient $vehiculos = null)
    {
        $this->clientes = $clientes ?? new ClientesClient($token);
        $this->vehiculos = $vehiculos ?? new VehiculosClient($token);
    }

    /**
     * Filtros: estado, cliente_id, vehiculo_id, limite y embeber (ambos|cliente|vehiculo|ninguno).
     * Los demás microservicios piden `embeber` parcial para no volver a llamarse entre sí.
     */
    public function get_reservas_all(array $filtros): array
    {
        $validador = new Validator($filtros);
        $embeber = $validador->opcion(
            'embeber',
            'El parámetro embeber',
            [self::EMBEBER_AMBOS, self::EMBEBER_CLIENTE, self::EMBEBER_VEHICULO, self::EMBEBER_NINGUNO]
        ) ?? self::EMBEBER_AMBOS;
        $limite = $validador->entero('limite', 'El límite', 1, 1000);
        $validador->verificar();

        $consulta = Reserva::query();

        if (!empty($filtros['estado']) && is_string($filtros['estado'])) {
            $consulta->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['cliente_id'])) {
            $consulta->where('cliente_id', (int) $filtros['cliente_id']);
        }

        if (!empty($filtros['vehiculo_id'])) {
            $consulta->where('vehiculo_id', (int) $filtros['vehiculo_id']);
        }

        $consulta->orderByDesc('fecha_inicio')->orderByDesc('id');

        if ($limite !== null) {
            $consulta->limit($limite);
        }

        return $this->a_arreglo($consulta->get(), $embeber);
    }

    public function get_reserva(int $id): array
    {
        return $this->a_arreglo(new Collection([$this->buscar_o_lanzar_no_encontrado($id)]))[0];
    }

    /**
     * Ids de vehículos ocupados por reservas activas. Sin fechas: los alquilados hoy; con
     * fecha_inicio y fecha_fin: los que se cruzan con ese rango. No llama a otros servicios.
     */
    public function get_vehiculos_ocupados(array $filtros): array
    {
        $validador = new Validator($filtros);
        $inicio = $validador->fecha('fecha_inicio', 'La fecha de inicio');
        $fin = $validador->fecha('fecha_fin', 'La fecha de fin');

        if ($inicio !== null && $fin !== null && $fin < $inicio) {
            $validador->agregar_error('fecha_fin', 'La fecha de fin no puede ser anterior a la de inicio.');
        }

        $validador->verificar();

        return ['vehiculo_ids' => Reserva::ids_vehiculos_ocupados($inicio, $fin)];
    }

    /** Cifras y listas de reservas para el panel de reportes_ms. */
    public function get_resumen(): array
    {
        $hoy = Reserva::hoy();
        $activas = Reserva::where('estado', Reserva::ACTIVA);

        $recientes = Reserva::orderByDesc('id')->limit(self::LIMITE_RECIENTES)->get();
        $vencidas = Reserva::where('estado', Reserva::ACTIVA)->where('fecha_fin', '<', $hoy)->orderBy('fecha_fin')->get();

        [$clientes, $vehiculos] = $this->cargar_relaciones($recientes->concat($vencidas), self::EMBEBER_AMBOS);

        return [
            'reservas_total' => Reserva::count(),
            'reservas_activas' => (clone $activas)->count(),
            'clientes_activos' => (int) (clone $activas)->distinct()->count('cliente_id'),
            'recientes' => Reserva::lista_a_array($recientes, $clientes, $vehiculos),
            'vencidas' => Reserva::lista_a_array($vencidas, $clientes, $vehiculos),
        ];
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

        $cliente = $cliente_id !== null ? $this->clientes->obtener($cliente_id) : null;

        if ($cliente_id !== null && $cliente === null) {
            $validador->agregar_error('cliente_id', 'El cliente no existe.');
        } elseif ($cliente !== null && trim((string) ($cliente['numero_licencia'] ?? '')) === '') {
            $validador->agregar_error('cliente_id', 'El cliente no tiene número de licencia registrado; no puede alquilar.');
        }

        $vehiculo = $vehiculo_id !== null ? ($this->vehiculos->por_ids([$vehiculo_id])[$vehiculo_id] ?? null) : null;

        if ($vehiculo_id !== null && $vehiculo === null) {
            $validador->agregar_error('vehiculo_id', 'El vehículo no existe.');
        }

        $validador->verificar();

        if (($vehiculo['estado'] ?? null) === VehiculosClient::MANTENIMIENTO) {
            throw new ConflictException('El vehículo está en mantenimiento y no se puede reservar.');
        }

        $id = $this->guardar_si_esta_libre($cliente_id, $vehiculo_id, $fecha_inicio, $fecha_fin);

        return $this->get_reserva($id);
    }

    public function return_reserva(int $id, bool $mantenimiento): array
    {
        $reserva = $this->obtener_activa($id);

        if ($reserva->getAttribute('fecha_inicio') > Reserva::hoy()) {
            throw new ConflictException('La reserva aún no ha comenzado; si no se va a usar, cancélela.');
        }

        // Si vehiculos_ms falla, la excepción revierte la devolución: la reserva sigue activa y se puede reintentar.
        Capsule::connection()->transaction(function () use ($reserva, $mantenimiento): void {
            $reserva->update(['estado' => Reserva::COMPLETADA]);

            if ($mantenimiento) {
                $this->vehiculos->enviar_a_mantenimiento((int) $reserva->getAttribute('vehiculo_id'));
            }
        });

        return $this->get_reserva($id);
    }

    public function cancel_reserva(int $id): array
    {
        $reserva = $this->obtener_activa($id);
        $reserva->update(['estado' => Reserva::CANCELADA]);

        return $this->get_reserva($id);
    }

    /**
     * Verifica el cruce de fechas e inserta con un bloqueo por vehículo (GET_LOCK), porque el vehículo vive
     * en otra base y ya no se puede bloquear su fila con FOR UPDATE.
     */
    private function guardar_si_esta_libre(int $cliente_id, int $vehiculo_id, string $fecha_inicio, string $fecha_fin): int
    {
        $conexion = Capsule::connection();
        $nombre_bloqueo = "reservas_vehiculo_{$vehiculo_id}";

        $obtenido = (int) ($conexion->selectOne('SELECT GET_LOCK(?, ?) AS obtenido', [$nombre_bloqueo, self::SEGUNDOS_ESPERA_BLOQUEO])->obtenido ?? 0);

        if ($obtenido !== 1) {
            throw new ConflictException('El vehículo se está reservando en este momento; intente de nuevo.');
        }

        try {
            if (Reserva::activas_que_ocupan($fecha_inicio, $fecha_fin)->where('vehiculo_id', $vehiculo_id)->exists()) {
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
        } finally {
            $conexion->select('SELECT RELEASE_LOCK(?)', [$nombre_bloqueo]);
        }
    }

    /** Convierte las reservas a arreglos incrustando clientes/vehículos según `$embeber`. */
    private function a_arreglo(Collection $reservas, string $embeber = self::EMBEBER_AMBOS): array
    {
        [$clientes, $vehiculos] = $this->cargar_relaciones($reservas, $embeber);

        return Reserva::lista_a_array($reservas, $clientes, $vehiculos);
    }

    /**
     * Pide a clientes_ms y vehiculos_ms (una vez por lote) los datos que se incrustan en las reservas.
     * El estado del vehículo se completa aquí: vehiculos_ms guarda solo disponible/mantenimiento y
     * "alquilado" se deriva de las reservas activas ya iniciadas, que son de este servicio.
     *
     * @return array{0: array<int, array>|null, 1: array<int, array>|null} clientes y vehículos por id
     */
    private function cargar_relaciones(Collection $reservas, string $embeber): array
    {
        $clientes = null;
        $vehiculos = null;

        if ($reservas->isNotEmpty() && in_array($embeber, [self::EMBEBER_AMBOS, self::EMBEBER_CLIENTE], true)) {
            $clientes = $this->clientes->por_ids($reservas->pluck('cliente_id')->all());
        }

        if ($reservas->isNotEmpty() && in_array($embeber, [self::EMBEBER_AMBOS, self::EMBEBER_VEHICULO], true)) {
            $vehiculos = $this->vehiculos->por_ids($reservas->pluck('vehiculo_id')->all());
            $alquilados = Reserva::ids_vehiculos_ocupados();

            foreach ($vehiculos as $id => $vehiculo) {
                if ($vehiculo['estado'] !== VehiculosClient::MANTENIMIENTO && in_array($id, $alquilados, true)) {
                    $vehiculos[$id]['estado'] = VehiculosClient::ALQUILADO;
                }
            }
        }

        return [$clientes, $vehiculos];
    }

    private function buscar_o_lanzar_no_encontrado(int $id): Reserva
    {
        $reserva = Reserva::find($id);

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
