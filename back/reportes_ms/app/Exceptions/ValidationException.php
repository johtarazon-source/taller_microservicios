<?php

namespace App\Exceptions;

final class ValidationException extends AppException
{
    public function __construct(array $errores, string $mensaje = 'Hay datos inválidos en la solicitud.')
    {
        parent::__construct($mensaje, 422, $errores);
    }
}
