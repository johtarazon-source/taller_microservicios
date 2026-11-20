<?php

namespace App\Models;

use RuntimeException;

class Tokens
{
    private string $ruta_archivo;

    public function __construct(?string $ruta_archivo = null)
    {
        $this->ruta_archivo = $ruta_archivo ?? dirname(__DIR__, 2) . '/storage/sessions.json';
    }

    public function save_session(string $huella, string $usuario, int $expira_en): void
    {
        $sesiones = $this->leer_sesiones();
        $sesiones[$huella] = ['usuario' => $usuario, 'activo' => true, 'expira_en' => $expira_en];
        $this->escribir_sesiones($sesiones);
    }

    public function find_session(string $huella): ?array
    {
        return $this->leer_sesiones()[$huella] ?? null;
    }

    public function delete_session(string $huella): void
    {
        $sesiones = $this->leer_sesiones();

        if (isset($sesiones[$huella])) {
            unset($sesiones[$huella]);
            $this->escribir_sesiones($sesiones);
        }
    }

    private function leer_sesiones(): array
    {
        if (!file_exists($this->ruta_archivo)) {
            return [];
        }

        $archivo = fopen($this->ruta_archivo, 'r');

        if ($archivo === false) {
            return [];
        }

        flock($archivo, LOCK_SH);
        $contenido = stream_get_contents($archivo);
        flock($archivo, LOCK_UN);
        fclose($archivo);

        $sesiones = json_decode((string) $contenido, true);

        return is_array($sesiones) ? $sesiones : [];
    }

    private function escribir_sesiones(array $sesiones): void
    {
        $directorio = dirname($this->ruta_archivo);

        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            throw new RuntimeException('No se pudo crear la carpeta de sesiones.');
        }

        $archivo = fopen($this->ruta_archivo, 'c+');

        if ($archivo === false) {
            throw new RuntimeException('No se pudo escribir el archivo de sesiones.');
        }

        flock($archivo, LOCK_EX);
        ftruncate($archivo, 0);
        rewind($archivo);
        fwrite($archivo, (string) json_encode($sesiones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($archivo);
        flock($archivo, LOCK_UN);
        fclose($archivo);
    }
}
