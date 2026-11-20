# taller_microservicios

Sistema de alquiler de vehículos en microservicios (PHP + Slim) con un frontend estático en `front/alquiler_app`.

| Servicio | Puerto | Datos propios |
|---|---|---|
| `auth_ms` | 8001 | sesiones en `storage/sessions.json` |
| `vehiculos_ms` | 8002 | MySQL `vehiculos_db` (tabla `vehiculos`) |
| `clientes_ms` | 8003 | MySQL `clientes_db` (tabla `clientes`) |
| `reservas_ms` | 8004 | MySQL `reservas_db` (tabla `reservas`) |
| `reportes_ms` | 8005 | ninguno: compone el resumen con las API de los demás |

## Una base de datos por servicio

Cada servicio es dueño de sus datos y es el único que conecta a su base (se crea sola en el primer uso).
Ningún servicio lee tablas de otro: se hablan por HTTP reenviando el token Bearer del usuario.

- `reservas_ms` guarda `cliente_id` y `vehiculo_id` como referencias lógicas (sin claves foráneas) y consulta
  a `clientes_ms` y `vehiculos_ms` para validarlas, incrustarlas y enviar un vehículo a mantenimiento.
- `vehiculos_ms` y `clientes_ms` piden a `reservas_ms` el historial, si hay reservas antes de eliminar y,
  en vehículos, cuáles están ocupados. El estado `alquilado` ya no se guarda: se deriva de las reservas activas.
- Las URLs entre servicios se configuran en el `.env` de cada uno (ver `.env.example`).

**Limitación conocida:** el servidor embebido de PHP (`php -S`) atiende una petición a la vez. Dos peticiones
simultáneas que se crucen (p. ej. una a `reservas_ms` que llama a `vehiculos_ms` mientras otra a `vehiculos_ms` llama
a `reservas_ms`) pueden esperarse hasta agotar el tiempo (8 s) y responder 503. Para uso concurrente ejecute los
servicios tras Apache/PHP-FPM o con `PHP_CLI_SERVER_WORKERS` (Linux/macOS).

## Puesta en marcha

1. `composer install` en cada carpeta `*_ms` y `back/iniciar_servicios.ps1`.
2. Pruebe las rutas con `back/endpoints.http`.

## Configuración de un microservicio (Slim)

Cada `*_ms` sigue la misma estructura: Slim 4 + `slim/psr7`, Eloquent (`illuminate/database`, salvo `reportes_ms`),
autoload PSR-4 `App\` → `app/` y punto de entrada `public/index.php`.

1. `composer install` dentro de la carpeta del servicio.
2. Copie `.env.example` a `.env` y ajuste base de datos, URLs de otros servicios y `CORS_ALLOWED_ORIGIN`.
3. Levante el servicio: `php -S 127.0.0.1:PUERTO -t public` (o `back/iniciar_servicios.ps1` para los cinco).

Diferencias deliberadas respecto a la guía genérica de Slim:

- **Autenticación:** en lugar de un token fijo, `app/Middlewares/auth.php` valida el token Bearer contra `auth_ms`
  (`POST /auth/validar`) y se aplica por ruta con `->add($auth)`.
- **CORS:** `app/Middlewares/cors.php` usa una lista blanca (`CORS_ALLOWED_ORIGIN`) en vez de reflejar cualquier
  `Origin` con credenciales, y responde 200 a los preflight `OPTIONS` antes del enrutador.
