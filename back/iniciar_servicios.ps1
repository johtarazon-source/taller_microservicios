<#
.SYNOPSIS
    Levanta los 5 microservicios de alquiler de vehículos con el servidor embebido de PHP.

.DESCRIPTION
    auth_ms :8001 | vehiculos_ms :8002 | clientes_ms :8003 | reservas_ms :8004 | reportes_ms :8005
    Cada servicio abre su propia ventana (ciérrela para detenerlo). Requiere haber ejecutado
    "composer install" en cada carpeta *_ms. Use -Php para indicar otro ejecutable de PHP.

.EXAMPLE
    .\iniciar_servicios.ps1
#>
param(
    [string]$Php = 'php',
    [string]$Direccion = '127.0.0.1'
)

$servicios = [ordered]@{
    auth_ms      = 8001
    vehiculos_ms = 8002
    clientes_ms  = 8003
    reservas_ms  = 8004
    reportes_ms  = 8005
}

foreach ($servicio in $servicios.GetEnumerator()) {
    $carpeta = Join-Path $PSScriptRoot $servicio.Key

    if (-not (Test-Path (Join-Path $carpeta 'vendor/autoload.php'))) {
        Write-Warning "$($servicio.Key): falta vendor/. Ejecute 'composer install' en $carpeta"
        continue
    }

    Start-Process -FilePath $Php -WorkingDirectory $carpeta `
        -ArgumentList '-S', "$Direccion`:$($servicio.Value)", '-t', 'public'
    Write-Host "$($servicio.Key) -> http://$Direccion`:$($servicio.Value)"
}
