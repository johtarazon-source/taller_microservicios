<?php

namespace App\Config;

use Illuminate\Database\Capsule\Manager as Capsule;
use PDO;
use PDOException;

const ERROR_BASE_DE_DATOS_INEXISTENTE = 1049;
const TABLAS_ESPERADAS = ['vehiculos'];

function configurar_base_de_datos(): void
{
    static $configurada = false;

    if ($configurada) {
        return;
    }

    $servidor = Env::obtener('DB_HOST', '127.0.0.1');
    $puerto = (int) Env::obtener('DB_PORT', '3306');
    $base_de_datos = Env::obtener('DB_DATABASE', 'vehiculos_db');
    $usuario = Env::obtener('DB_USERNAME', 'root');
    $contrasena = Env::obtener('DB_PASSWORD', '');

    asegurar_esquema($servidor, $puerto, $base_de_datos, $usuario, $contrasena);

    $capsule = new Capsule();
    $capsule->addConnection([
        'driver' => 'mysql',
        'host' => $servidor,
        'port' => $puerto,
        'database' => $base_de_datos,
        'username' => $usuario,
        'password' => $contrasena,
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ]);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    $configurada = true;
}

function asegurar_esquema(string $servidor, int $puerto, string $base_de_datos, string $usuario, string $contrasena): void
{
    $opciones = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

    try {
        $pdo = new PDO("mysql:host={$servidor};port={$puerto};dbname={$base_de_datos};charset=utf8mb4", $usuario, $contrasena, $opciones);
    } catch (PDOException $excepcion) {
        if (($excepcion->errorInfo[1] ?? 0) !== ERROR_BASE_DE_DATOS_INEXISTENTE) {
            throw $excepcion;
        }

        $pdo = new PDO("mysql:host={$servidor};port={$puerto};charset=utf8mb4", $usuario, $contrasena, $opciones);
        $nombre_escapado = str_replace('`', '``', $base_de_datos);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$nombre_escapado}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$nombre_escapado}`");
    }

    $marcadores = implode(', ', array_fill(0, count(TABLAS_ESPERADAS), '?'));
    $consulta = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ({$marcadores})"
    );
    $consulta->execute(TABLAS_ESPERADAS);

    if ((int) $consulta->fetchColumn() === count(TABLAS_ESPERADAS)) {
        return;
    }

    $esquema = (string) file_get_contents(dirname(__DIR__, 2) . '/database/vehiculos_ms.sql');

    foreach (explode(';', $esquema) as $sentencia) {
        $sentencia = trim($sentencia);

        if ($sentencia === '' || preg_match('/^(CREATE\s+DATABASE|USE)\b/i', $sentencia) === 1) {
            continue;
        }

        $pdo->exec($sentencia);
    }
}
