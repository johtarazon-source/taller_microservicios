<?php

namespace App\Clients;

use App\Config\Env;
use App\Exceptions\AppException;

abstract class ServicioClient
{
    private const LIMITE_IDS_POR_PETICION = 200;

    protected function __construct(
        private string $token,
        private string $nombre,
        private string $variable_url,
        private string $url_defecto
    ) {
    }

    protected function solicitar(string $metodo, string $ruta, array $consulta = [], ?array $cuerpo = null): array
    {
        $url = rtrim((string) Env::obtener($this->variable_url, $this->url_defecto), '/') . $ruta;

        if ($consulta !== []) {
            $url .= '?' . http_build_query($consulta);
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $metodo,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json', 'Authorization: Bearer ' . $this->token],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
        ]);

        if ($metodo !== 'GET') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($cuerpo ?? new \stdClass(), JSON_UNESCAPED_UNICODE));
        }

        $contenido = curl_exec($curl);
        $codigo_estado = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($contenido === false || $codigo_estado === 0 || $codigo_estado >= 500) {
            throw new AppException("El servicio de {$this->nombre} no está disponible.", 503);
        }

        $respuesta = json_decode((string) $contenido, true);

        if (!is_array($respuesta)) {
            throw new AppException("El servicio de {$this->nombre} devolvió una respuesta inválida.", 502);
        }

        return [$codigo_estado, $respuesta];
    }

    protected function datos(int $codigo_estado, array $respuesta): mixed
    {
        if ($codigo_estado >= 200 && $codigo_estado < 300) {
            return $respuesta['data'] ?? null;
        }

        $errores = is_array($respuesta['data'] ?? null) ? $respuesta['data'] : null;

        throw new AppException((string) ($respuesta['mensaje'] ?? "Error en el servicio de {$this->nombre}."), $codigo_estado, $errores);
    }

    protected function bloques_de_ids(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn (int $id): bool => $id > 0)));

        return array_chunk($ids, self::LIMITE_IDS_POR_PETICION);
    }
}
