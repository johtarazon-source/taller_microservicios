<?php

namespace App\Controllers;

use App\Config\Env;
use App\Exceptions\AppException;
use App\Exceptions\UnauthorizedException;
use App\Models\Tokens;
use DateTimeImmutable;

class SessionsController
{
    private const ADMIN_USUARIO_POR_DEFECTO = 'admin';
    private const ADMIN_CONTRASENA_POR_DEFECTO = 'admin1234';

    private Tokens $tokens;
    private string $usuario_administrador;
    private string $contrasena_administrador;
    private int $minutos_vigencia;

    public function __construct(?Tokens $tokens = null)
    {
        $this->tokens = $tokens ?? new Tokens();

        $usuario = Env::obtener('ADMIN_USER');
        $contrasena = Env::obtener('ADMIN_PASSWORD');

        if ($usuario === null && $contrasena === null) {
            $usuario = self::ADMIN_USUARIO_POR_DEFECTO;
            $contrasena = self::ADMIN_CONTRASENA_POR_DEFECTO;
        }

        $this->usuario_administrador = $usuario ?? '';
        $this->contrasena_administrador = $contrasena ?? '';
        $this->minutos_vigencia = (int) Env::obtener('APP_TOKEN_TTL_MINUTES', '120');
    }

    public function login(array $datos): array
    {
        $usuario = trim((string) ($datos['usuario'] ?? ''));
        $contrasena = (string) ($datos['password'] ?? '');

        if ($usuario === '' || $contrasena === '') {
            throw new AppException('Usuario y contraseña son obligatorios.', 422);
        }

        if ($this->usuario_administrador === '' || $this->contrasena_administrador === '') {
            throw new AppException('El administrador no está configurado en el servidor. Defina ADMIN_USER y ADMIN_PASSWORD en el .env de auth_ms.', 500);
        }

        $usuario_valido = hash_equals($this->usuario_administrador, $usuario);
        $contrasena_valida = hash_equals($this->contrasena_administrador, $contrasena);

        if (!$usuario_valido || !$contrasena_valida) {
            throw new UnauthorizedException('Usuario o contraseña incorrectos.');
        }

        $token = bin2hex(random_bytes(32));
        $expira_en = (new DateTimeImmutable())->modify("+{$this->minutos_vigencia} minutes")->getTimestamp();
        $this->tokens->save_session($this->huella($token), $usuario, $expira_en);

        return ['token' => $token, 'usuario' => $usuario];
    }

    public function logout(string $token): void
    {
        $this->tokens->delete_session($this->huella($token));
    }

    public function validate_token(string $token): bool
    {
        if ($token === '') {
            return false;
        }

        $sesion = $this->tokens->find_session($this->huella($token));

        return $sesion !== null && ($sesion['activo'] ?? false) === true && ($sesion['expira_en'] ?? 0) > time();
    }

    private function huella(string $token): string
    {
        return hash('sha256', $token);
    }
}
