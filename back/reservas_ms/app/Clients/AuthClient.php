<?php

namespace App\Clients;

use App\Config\Env;
use App\Exceptions\AppException;

/**
 * Cliente HTTP (curl) hacia auth_ms: valida el token Bearer recibido.
 */
class AuthClient
{
    public function validar(string $token): bool
    {
        $url = rtrim((string) Env::obtener('AUTH_MS_URL', 'http://127.0.0.1:8001'), '/') . '/auth/validar';

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '{}',
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json', 'Authorization: Bearer ' . $token],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
        ]);

        $contenido = curl_exec($curl);
        $codigo_estado = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($contenido === false || $codigo_estado === 0 || $codigo_estado >= 500) {
            throw new AppException('El servicio de autenticación no está disponible.', 503);
        }

        $respuesta = json_decode((string) $contenido, true);

        return $codigo_estado === 200 && ($respuesta['ok'] ?? false) === true && ($respuesta['data']['valido'] ?? false) === true;
    }
}
