<?php

namespace App\Exceptions;

final class UnauthorizedException extends AppException
{
    public function __construct(string $mensaje = 'No autorizado.')
    {
        parent::__construct($mensaje, 401);
    }
}
