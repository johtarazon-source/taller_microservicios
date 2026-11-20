<?php

namespace App\Exceptions;

final class NotFoundException extends AppException
{
    public function __construct(string $mensaje = 'Recurso no encontrado.')
    {
        parent::__construct($mensaje, 404);
    }
}
