<?php

namespace App\Exceptions;

final class ConflictException extends AppException
{
    public function __construct(string $mensaje)
    {
        parent::__construct($mensaje, 409);
    }
}
